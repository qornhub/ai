# ai_service/app.py
import os
import io
import base64
import json
import time
import hashlib
from pathlib import Path
from functools import lru_cache

from flask import Flask, request, jsonify
from typing import Tuple, Dict

app = Flask(__name__)

# Paths: app.py is in /ai_dashboard/ai/ai_service/
# Model files are in /ai_dashboard/ai/model/
BASE_DIR = Path(__file__).resolve().parent        # .../ai/ai_service
ROOT_DIR = BASE_DIR.parent                        # .../ai
MODEL_PATH = ROOT_DIR / "model" / "business_financing_model.pkl"
FEATURES_PATH = ROOT_DIR / "model" / "business_model_features.pkl"

# Defensive imports
try:
    import joblib
    import pandas as pd
    import numpy as np
    import shap
    import matplotlib.pyplot as plt
    from matplotlib.figure import Figure
    from lime.lime_tabular import LimeTabularExplainer
    from sklearn.tree import _tree
except Exception as e:
    joblib = pd = np = shap = plt = LimeTabularExplainer = _tree = None
    IMPORT_ERROR = str(e)
else:
    IMPORT_ERROR = None

# Simple in-memory cache for explanations (sha256(payload) -> dict)
EXPLAIN_CACHE: Dict[str, Dict] = {}
EXPLAIN_CACHE_MAX = 512  # keep memory bounded; simple eviction below


@lru_cache(maxsize=1)
def load_model_and_features() -> Tuple[object, list]:
    """
    Load Business Financing model + list of feature names from /ai/model/.
    """
    if IMPORT_ERROR:
        raise RuntimeError(f"Import error: {IMPORT_ERROR}")
    if not MODEL_PATH.exists() or not FEATURES_PATH.exists():
        raise FileNotFoundError(
            "Model or features file missing in /ai/model/ "
            f"(expected: {MODEL_PATH}, {FEATURES_PATH})"
        )
    model = joblib.load(str(MODEL_PATH))
    features = joblib.load(str(FEATURES_PATH))
    return model, features


def ensure_features_df(payload: dict, features: list):
    """
    Build a single-row DataFrame from JSON payload, ensuring all
    required model features exist (missing ones filled with 0).
    """
    import pandas as pd
    df = pd.DataFrame([payload])
    for f in features:
        if f not in df.columns:
            df[f] = 0
    return df[features]


def _cache_set(key: str, value: dict):
    if len(EXPLAIN_CACHE) >= EXPLAIN_CACHE_MAX:
        # pop oldest (not truly LRU but simple)
        EXPLAIN_CACHE.pop(next(iter(EXPLAIN_CACHE)))
    EXPLAIN_CACHE[key] = value


def _cache_get(key: str):
    return EXPLAIN_CACHE.get(key)


def shap_explain_with_metadata(model, df: "pd.DataFrame"):
    """
    Returns (explanation_dict, metadata)
    explanation_dict: feature -> shap_value
    metadata: {explainer_type, expected_value (if available), model_version}
    """
    start = time.perf_counter()
    expl_type = None
    expected_value = None
    explanation = None

    # prefer TreeExplainer
    try:
        expl = shap.TreeExplainer(model)
        shap_values = expl.shap_values(df)
        expl_type = "TreeExplainer"
        arr = shap_values[0] if isinstance(shap_values, list) else shap_values
        first = arr[0]
        explanation = {df.columns[i]: float(first[i]) for i in range(len(df.columns))}
        # expected value if available
        try:
            if hasattr(expl, "expected_value") and isinstance(
                expl.expected_value, (list, np.ndarray)
            ):
                # choose positive class if binary
                expected_value = float(
                    expl.expected_value[1]
                    if len(np.atleast_1d(expl.expected_value)) > 1
                    else expl.expected_value[0]
                )
            else:
                expected_value = float(expl.expected_value)
        except Exception:
            expected_value = None
    except Exception:
        # fallback KernelExplainer
        try:
            # use kmeans background
            bg = shap.kmeans(df, 10)
            expl = shap.KernelExplainer(
                lambda x: model.predict_proba(x)[:, 1],
                bg
            )
            shap_values = expl.shap_values(df)
            expl_type = "KernelExplainer"
            arr = shap_values[0] if isinstance(shap_values, list) else shap_values
            first = arr[0]
            explanation = {df.columns[i]: float(first[i]) for i in range(len(df.columns))}
            expected_value = None
        except Exception:
            return None, {
                "explainer_type": None,
                "explain_time_ms": int((time.perf_counter() - start) * 1000),
            }

    metadata = {
        "explainer_type": expl_type,
        "explain_time_ms": int((time.perf_counter() - start) * 1000),
        "model_version": getattr(model, "model_version", "unknown"),
        "expected_value": expected_value,
    }
    return explanation, metadata


def lime_explain_with_metadata(model, df: "pd.DataFrame"):
    start = time.perf_counter()
    try:
        X = df.values
        explainer = LimeTabularExplainer(
            np.vstack([X]),
            feature_names=list(df.columns),
            discretize_continuous=True,
        )
        exp = explainer.explain_instance(
            X[0],
            model.predict_proba,
            num_features=min(10, len(df.columns)),
        )
        explanation = {feat: float(val) for feat, val in exp.as_list()}
        metadata = {
            "explainer_type": "LIME",
            "explain_time_ms": int((time.perf_counter() - start) * 1000),
            "model_version": getattr(model, "model_version", "unknown"),
            "expected_value": None,
        }
        return explanation, metadata
    except Exception:
        return None, {
            "explainer_type": None,
            "explain_time_ms": int((time.perf_counter() - start) * 1000),
        }


def plot_shap_bar(shap_dict: dict, top_k: int = 10):
    """Return PNG bytes of horizontal bar chart of absolute effect (top_k features)."""
    import matplotlib.pyplot as plt

    items = sorted(shap_dict.items(), key=lambda x: abs(x[1]), reverse=True)[:top_k]
    if not items:
        return None
    labels = [k for k, v in items][::-1]
    values = [v for k, v in items][::-1]
    fig = plt.figure(figsize=(8, max(2, 0.4 * len(labels))))
    ax = fig.subplots()
    ax.barh(labels, values)
    ax.set_xlabel("SHAP value (impact on model output)")
    ax.set_title("Top feature contributions")
    fig.tight_layout()
    buf = io.BytesIO()
    fig.savefig(buf, format="png", bbox_inches="tight")
    plt.close(fig)
    buf.seek(0)
    return buf.read()


def narrative_from_shap(shap_dict: dict, prob: float = None, top_k: int = 3):
    """Produce a short human-readable narrative."""
    items = sorted(shap_dict.items(), key=lambda x: abs(x[1]), reverse=True)[:top_k]
    parts = []
    for feat, val in items:
        sign = "increased" if val > 0 else "decreased"
        parts.append(f"{feat} {sign} the approval likelihood by {abs(val):.3f}")
    prob_part = f" The model probability is {prob:.3f}." if prob is not None else ""
    return "Top reasons: " + "; ".join(parts) + "." + prob_part


def get_decision_path_if_tree(model, df: "pd.DataFrame"):
    """
    If model is a tree or ensemble with accessible estimator and decision_path, attempt to
    extract the decision path for the single instance.
    Returns list of dicts: [{feature, threshold, feature_value, direction, node_id}, ...] or None.
    NOTE: XGBoost models may not expose sklearn-style decision_path; in that case this returns None.
    """
    try:
        # pick a single estimator: tree or first estimator in ensemble
        estimator = None
        if hasattr(model, "tree_"):
            estimator = model
        elif hasattr(model, "estimators_") and len(model.estimators_) > 0:
            estimator = model.estimators_[0]
        elif hasattr(model, "estimators") and len(model.estimators) > 0:
            estimator = model.estimators[0]
        if estimator is None or _tree is None:
            return None

        # sklearn tree
        if not hasattr(estimator, "decision_path") or not hasattr(estimator, "tree_"):
            return None

        X = df.values
        node_indicator = estimator.decision_path(X)
        leave_id = estimator.apply(X)
        tree_ = estimator.tree_

        feature = tree_.feature
        threshold = tree_.threshold

        path = []
        node_index = node_indicator.indices[
            node_indicator.indptr[0]: node_indicator.indptr[1]
        ]
        for node_id in node_index:
            if feature[node_id] != _tree.TREE_UNDEFINED:
                feat_name = df.columns[feature[node_id]]
                thresh = float(threshold[node_id])
                feat_value = float(X[0, feature[node_id]])
                go_left = feat_value <= thresh
                direction = "left" if go_left else "right"
                path.append(
                    {
                        "node_id": int(node_id),
                        "feature": feat_name,
                        "threshold": thresh,
                        "feature_value": feat_value,
                        "direction": direction,
                    }
                )
        return path
    except Exception:
        return None


# ---------- Helper mappers for categories (business financing) ----------

def infer_business_type(r: dict) -> str:
    # Prefer explicit string if provided
    bt = r.get("business_type")
    if isinstance(bt, str) and bt in ("halal", "non-halal", "mixed"):
        return bt
    # Fallback from one-hot flags
    if r.get("business_type_non-halal", 0) == 1:
        return "non-halal"
    if r.get("business_type_mixed", 0) == 1:
        return "mixed"
    return "halal"


def infer_industry_category(r: dict) -> str:
    cat = r.get("industry_category")
    if isinstance(cat, str) and cat:
        return cat
    # Infer from one-hot, fallback to "F&B/retail bucket"
    if r.get("industry_category_manufacturing", 0) == 1:
        return "manufacturing"
    if r.get("industry_category_services", 0) == 1:
        return "services"
    if r.get("industry_category_transport", 0) == 1:
        return "transport"
    if r.get("industry_category_retail", 0) == 1:
        return "retail"
    if r.get("industry_category_F&B", 0) == 1:
        return "F&B"
    return "F&B"  # default bucket


def infer_financing_purpose(r: dict) -> str:
    p = r.get("financing_purpose")
    if isinstance(p, str) and p:
        return p
    if r.get("financing_purpose_equipment", 0) == 1:
        return "equipment"
    if r.get("financing_purpose_renovation", 0) == 1:
        return "renovation"
    if r.get("financing_purpose_expansion", 0) == 1:
        return "expansion"
    if r.get("financing_purpose_others", 0) == 1:
        return "others"
    return "working_capital"


def infer_contract_type(r: dict) -> str:
    c = r.get("contract_type")
    if isinstance(c, str) and c:
        return c
    if r.get("contract_type_Ijarah", 0) == 1:
        return "Ijarah"
    if r.get("contract_type_Musharakah", 0) == 1:
        return "Musharakah"
    if r.get("contract_type_Tawarruq", 0) == 1:
        return "Tawarruq"
    return "Murabahah"


def infer_collateral_type(r: dict) -> str:
    c = r.get("collateral_type")
    if isinstance(c, str) and c:
        return c
    if r.get("collateral_type_property", 0) == 1:
        return "property"
    if r.get("collateral_type_vehicle", 0) == 1:
        return "vehicle"
    if r.get("collateral_type_inventory", 0) == 1:
        return "inventory"
    if r.get("collateral_type_cash", 0) == 1:
        return "cash"
    return "none"


@app.route("/predict", methods=["POST"])
def predict():
    """
    Main prediction + human benchmark + simple bias score for BUSINESS FINANCING.

    Expected numeric-style model features (already one-hot encoded on Laravel side):
    - years_in_business, annual_revenue, net_profit, monthly_cashflow,
      existing_liabilities, credit_score, past_default, financing_amount,
      profit_rate, tenure_months, collateral_value, ...
    - business_type_* one-hot
    - industry_category_* one-hot
    - financing_purpose_* one-hot
    - contract_type_* one-hot
    - collateral_type_* one-hot

    Payload may ALSO include raw strings:
    - business_type, industry_category, financing_purpose, contract_type, collateral_type
    which are used for human rule & bias narrative.
    """
    try:
        payload = request.get_json(force=True)
        if not isinstance(payload, dict):
            return jsonify({"success": False, "error": "Payload must be JSON object"}), 400

        model, features = load_model_and_features()
        df = ensure_features_df(payload, features)

        # === AI MODEL PREDICTION ===
        if hasattr(model, "predict_proba"):
            prob = float(model.predict_proba(df)[0][1])
            pred = int(model.predict(df)[0])
        else:
            pred = int(model.predict(df)[0])
            prob = None

        # ---------- HUMAN BENCHMARK (Islamic business financing rules) ----------

        def human_rule(r: dict) -> int:
            score = 0.0

            # Core numeric fields (safe parsing)
            revenue = float(r.get("annual_revenue", 0.0) or 0.0)
            revenue = max(revenue, 1.0)  # avoid division by zero

            net_profit = float(r.get("net_profit", 0.0) or 0.0)
            monthly_cashflow = float(r.get("monthly_cashflow", 0.0) or 0.0)
            existing_liabilities = float(r.get("existing_liabilities", 0.0) or 0.0)
            credit_score = float(r.get("credit_score", 0.0) or 0.0)
            past_default = int(r.get("past_default", 0) or 0)
            financing_amount = float(r.get("financing_amount", 0.0) or 0.0)
            profit_rate = float(r.get("profit_rate", 0.0) or 0.0)
            tenure_months = int(r.get("tenure_months", 0) or 0)
            years_in_business = int(r.get("years_in_business", 0) or 0)
            collateral_value = float(r.get("collateral_value", 0.0) or 0.0)

            business_type_local = infer_business_type(r)
            industry_category_local = infer_industry_category(r)
            financing_purpose_local = infer_financing_purpose(r)
            contract_type_local = infer_contract_type(r)
            collateral_type_local = infer_collateral_type(r)

            # --- Profitability (net profit vs revenue) ---
            profit_margin = net_profit / revenue

            if profit_margin >= 0.20:
                score += 3
            elif profit_margin >= 0.10:
                score += 2
            elif profit_margin >= 0.03:
                score += 1
            elif profit_margin < 0:
                score -= 3
            else:
                score -= 1

            # --- Cashflow coverage for financing payment ---
            principal = financing_amount
            rate = profit_rate / 100.0
            tenure = max(tenure_months, 1)
            total_payable = principal * (1.0 + rate)
            monthly_payment = total_payable / tenure

            coverage_ratio = monthly_cashflow / max(monthly_payment, 1.0)

            if coverage_ratio >= 2.0:
                score += 3
            elif coverage_ratio >= 1.3:
                score += 2
            elif coverage_ratio >= 1.0:
                score += 1
            else:
                score -= 3

            # --- Leverage: existing liabilities vs revenue ---
            liability_ratio = existing_liabilities / revenue

            if liability_ratio <= 0.30:
                score += 2
            elif liability_ratio <= 0.60:
                score += 1
            else:
                score -= 2

            # --- Credit score ---
            if credit_score >= 750:
                score += 2
            elif credit_score >= 650:
                score += 1
            else:
                score -= 1

            # --- Past default strongly negative ---
            if past_default == 1:
                score -= 4

            # --- Years in business (stability) ---
            if years_in_business >= 10:
                score += 2
            elif years_in_business >= 3:
                score += 1
            else:
                score -= 1

            # --- Financing amount vs revenue ---
            fin_ratio = financing_amount / revenue
            if fin_ratio <= 0.50:
                score += 1
            elif fin_ratio > 1.50:
                score -= 2
            elif fin_ratio > 1.00:
                score -= 1

            # --- Collateral coverage ---
            cover_ratio = collateral_value / max(financing_amount, 1.0)
            if cover_ratio >= 1.5:
                score += 2
            elif cover_ratio >= 0.8:
                score += 1
            elif collateral_type_local == "none":
                score -= 1

            # --- Shariah / business type (intentional bias for analysis) ---
            if business_type_local == "halal":
                score += 1
            elif business_type_local == "mixed":
                score += 0
            elif business_type_local == "non-halal":
                score -= 2

            # --- Industry risk adjustment ---
            if industry_category_local in ["F&B", "retail"]:
                score -= 0.5
            elif industry_category_local in ["manufacturing", "services"]:
                score += 0.5

            # --- Purpose & contract compatibility (Islamic contracts) ---
            if financing_purpose_local in ["equipment", "renovation"] and contract_type_local in ["Murabahah", "Ijarah"]:
                score += 1.0
            elif financing_purpose_local in ["working_capital", "expansion"] and contract_type_local in ["Musharakah", "Tawarruq"]:
                score += 1.0
            else:
                score -= 0.5

            # --- Musharakah should have decent profit margin ---
            if contract_type_local == "Musharakah" and profit_margin < 0.05:
                score -= 1.0

            # final decision threshold (tuned to give reasonable approval rate)
            return 1 if score >= 3.0 else 0

        human_decision = human_rule(payload)

        # === SHAP EXPLANATION with LIME fallback ===
        explanation = None
        explanation_meta = {}
        # try cache first
        key = hashlib.sha256(
            json.dumps(payload, sort_keys=True).encode("utf-8")
        ).hexdigest()
        cached = _cache_get(key)
        if cached:
            explanation = cached.get("explanation")
            explanation_meta = cached.get("meta", {})
        else:
            if shap:
                explanation, explanation_meta = shap_explain_with_metadata(model, df)
            if (explanation is None or explanation_meta.get("explainer_type") is None) and LimeTabularExplainer:
                explanation, meta_lime = lime_explain_with_metadata(model, df)
                # if we have both, prefer shap; otherwise adopt lime meta
                if explanation_meta.get("explainer_type") is None:
                    explanation_meta = meta_lime
            # cache result if any
            if explanation is not None:
                _cache_set(key, {"explanation": explanation, "meta": explanation_meta})

        # === SIMPLE BIAS SCORE (non-halal one-hot) ===
        bias_score = float(df["business_type_non-halal"].iloc[0]) if "business_type_non-halal" in df.columns else 0.0

        # agreement
        agreement = 1 if pred == human_decision else 0

        return jsonify(
            {
                "success": True,
                "ai_decision": "Approved" if pred == 1 else "Rejected",
                "prediction": int(pred),
                "probability": prob,
                "explanation": explanation,
                "explanation_meta": explanation_meta,
                "bias_score": bias_score,
                "human_decision": int(human_decision),
                "agreement": int(agreement),
            }
        ), 200

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/explain", methods=["POST"])
def explain():
    """Return full SHAP (or LIME) explanation for given features in JSON."""
    try:
        payload = request.get_json(force=True)
        model, features = load_model_and_features()
        df = ensure_features_df(payload, features)

        # cache aware
        key = hashlib.sha256(
            json.dumps(payload, sort_keys=True).encode("utf-8")
        ).hexdigest()
        cached = _cache_get(key)
        if cached:
            explanation = cached.get("explanation")
            meta = cached.get("meta", {})
            return jsonify({"success": True, "explanation": explanation, "meta": meta}), 200

        explanation = None
        meta = {}
        if shap:
            explanation, meta = shap_explain_with_metadata(model, df)
        if (explanation is None or meta.get("explainer_type") is None) and LimeTabularExplainer:
            explanation, meta = lime_explain_with_metadata(model, df)

        if explanation is None:
            return jsonify({"success": False, "error": "No explainer available"}), 500

        _cache_set(key, {"explanation": explanation, "meta": meta})
        return jsonify({"success": True, "explanation": explanation, "meta": meta}), 200
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/explain_plot", methods=["POST"])
def explain_plot():
    """Return base64 PNG of top-k feature contributions."""
    try:
        payload = request.get_json(force=True)
        top_k = int(payload.get("top_k", 10))
        model, features = load_model_and_features()
        df = ensure_features_df(payload, features)

        # attempt to reuse cache
        key = hashlib.sha256(
            json.dumps(payload, sort_keys=True).encode("utf-8")
        ).hexdigest()
        cached = _cache_get(key)
        if cached and cached.get("plot_base64"):
            return jsonify({"success": True, "plot_base64": cached["plot_base64"]}), 200

        explanation = None
        meta = {}
        if shap:
            explanation, meta = shap_explain_with_metadata(model, df)
        if explanation is None and LimeTabularExplainer:
            explanation, meta = lime_explain_with_metadata(model, df)
        if explanation is None:
            return jsonify({"success": False, "error": "No explanation available"}), 500

        png_bytes = plot_shap_bar(explanation, top_k=top_k)
        if png_bytes is None:
            return jsonify({"success": False, "error": "Plot generation failed"}), 500
        b64 = base64.b64encode(png_bytes).decode("utf-8")

        # cache plot for payload
        existing = _cache_get(key) or {}
        existing["plot_base64"] = b64
        existing["explanation"] = explanation
        existing["meta"] = meta if "meta" in locals() else {}
        _cache_set(key, existing)

        return jsonify({"success": True, "plot_base64": b64}), 200
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/explain_detailed", methods=["POST"])
def explain_detailed():
    """
    Return a detailed structured explanation suitable for 'decision pathway' UI.
    """
    try:
        payload = request.get_json(force=True)
        if not isinstance(payload, dict):
            return jsonify({"success": False, "error": "Payload must be JSON object"}), 400

        model, features = load_model_and_features()
        df = ensure_features_df(payload, features)

        # prediction info
        prob = None
        try:
            if hasattr(model, "predict_proba"):
                prob = float(model.predict_proba(df)[0][1])
            else:
                prob = None
        except Exception:
            prob = None

        # try cached explanation
        key = hashlib.sha256(
            json.dumps(payload, sort_keys=True).encode("utf-8")
        ).hexdigest()
        cached = _cache_get(key)
        if cached and cached.get("explanation"):
            explanation = cached["explanation"]
            meta = cached.get("meta", {})
        else:
            explanation = None
            meta = {}
            if shap:
                explanation, meta = shap_explain_with_metadata(model, df)
            if (explanation is None or meta.get("explainer_type") is None) and LimeTabularExplainer:
                explanation, meta = lime_explain_with_metadata(model, df)
            if explanation:
                _cache_set(key, {"explanation": explanation, "meta": meta})

        if explanation is None:
            return jsonify({"success": False, "error": "No explanation available"}), 500

        # top reasons and narrative
        sorted_items = sorted(explanation.items(), key=lambda x: abs(x[1]), reverse=True)
        top_reasons = []
        for feat, val in sorted_items[:8]:
            top_reasons.append(
                {
                    "feature": feat,
                    "shap_value": val,
                    "sign": "positive" if val > 0 else "negative",
                    "magnitude": abs(val),
                }
            )
        narrative = narrative_from_shap(explanation, prob=prob, top_k=3)

        # decision path if tree-like model supports it (may be None for XGBoost)
        decision_path = get_decision_path_if_tree(model, df)

        response = {
            "success": True,
            "probability": prob,
            "prediction": int(model.predict(df)[0]) if hasattr(model, "predict") else None,
            "shap_values": explanation,
            "expected_value": meta.get("expected_value"),
            "explainer_type": meta.get("explainer_type"),
            "model_version": meta.get("model_version"),
            "explain_time_ms": meta.get("explain_time_ms"),
            "top_reasons": top_reasons,
            "narrative": narrative,
            "decision_path": decision_path,
        }
        return jsonify(response), 200

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/explain_interactive", methods=["POST"])
def explain_interactive():
    """
    Return compact arrays for client-side interactive plotting.
    """
    try:
        payload = request.get_json(force=True)
        if not isinstance(payload, dict):
            return jsonify({"success": False, "error": "Payload must be JSON object"}), 400

        model, features = load_model_and_features()
        df = ensure_features_df(payload, features)

        # attempt explanation
        key = hashlib.sha256(
            json.dumps(payload, sort_keys=True).encode("utf-8")
        ).hexdigest()
        cached = _cache_get(key)
        if cached and cached.get("explanation"):
            explanation = cached["explanation"]
            meta = cached.get("meta", {})
        else:
            explanation = None
            meta = {}
            if shap:
                explanation, meta = shap_explain_with_metadata(model, df)
            if (explanation is None or meta.get("explainer_type") is None) and LimeTabularExplainer:
                explanation, meta = lime_explain_with_metadata(model, df)
            if explanation:
                _cache_set(key, {"explanation": explanation, "meta": meta})

        if explanation is None:
            return jsonify({"success": False, "error": "No explanation available"}), 500

        feature_names = list(df.columns)
        feature_values = [float(df.iloc[0][c]) for c in feature_names]
        shap_values = [float(explanation.get(c, 0.0)) for c in feature_names]
        base_value = meta.get("expected_value", None)
        probability = None
        try:
            if hasattr(model, "predict_proba"):
                probability = float(model.predict_proba(df)[0][1])
        except Exception:
            probability = None

        return jsonify(
            {
                "success": True,
                "features": feature_names,
                "feature_values": feature_values,
                "shap_values": shap_values,
                "base_value": base_value,
                "model_probability": probability,
            }
        ), 200
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


def _group_bias_stats(df_all, group_col: str):
    """
    Helper: compute approval rate and count by group_col.
    Returns dict: {group_value: {"approval_rate": float, "count": int}, ...}
    """
    if group_col not in df_all.columns:
        return {}
    grouped = df_all.groupby(group_col)["pred"].agg(["mean", "count"])
    result = {}
    for idx, row in grouped.iterrows():
        result[str(idx)] = {
            "approval_rate": float(row["mean"]),
            "count": int(row["count"]),
        }
    return result


@app.route("/bias_report", methods=["POST"])
def bias_report():
    """
    Expects:
        {
          "items": [ {...}, ... ]
        }

    Returns:
      - approval rates by business_type (backward-compatible "rates" field)
      - extended bias stats for:
          * business_type
          * industry_category
          * financing_purpose
          * contract_type
      - disparate impact ratio for business_type:
          P(approve | non-halal) / P(approve | halal)
    """
    try:
        payload = request.get_json(force=True)
        items = payload.get("items")
        if not items or not isinstance(items, list):
            return jsonify({"success": False, "error": "Provide 'items': [ {...}, ... ]"}), 400

        model, features = load_model_and_features()
        import pandas as pd

        df_all = pd.DataFrame(items)
        # ensure features
        for f in features:
            if f not in df_all.columns:
                df_all[f] = 0
        X = df_all[features]
        # predictions
        if hasattr(model, "predict_proba"):
            probs = model.predict_proba(X)[:, 1]
            preds = model.predict(X)
        else:
            probs = [None] * len(X)
            preds = model.predict(X)

        df_all["pred"] = list(map(int, preds))

        # group by business_type: halal, mixed, non-halal
        def classify(row):
            if row.get("business_type") in ["halal", "non-halal", "mixed"]:
                return row.get("business_type")
            if row.get("business_type_non-halal", 0) == 1:
                return "non-halal"
            if row.get("business_type_mixed", 0) == 1:
                return "mixed"
            return "halal"

        df_all["biz_group"] = df_all.apply(classify, axis=1)

        # Backward-compatible structure for original "rates"
        rates = (
            df_all.groupby("biz_group")["pred"]
            .agg(["mean", "count"])
            .to_dict()
        )

        # Extended structured bias stats for UI
        business_type_bias = _group_bias_stats(df_all, "biz_group")
        industry_bias = _group_bias_stats(df_all, "industry_category")
        purpose_bias = _group_bias_stats(df_all, "financing_purpose")
        contract_bias = _group_bias_stats(df_all, "contract_type")

        # compute disparate impact ratio: P(approve | non-halal) / P(approve | halal)
        p_non = float(
            df_all[df_all["biz_group"] == "non-halal"]["pred"].mean()
        ) if "non-halal" in df_all["biz_group"].values else None
        p_halal = float(
            df_all[df_all["biz_group"] == "halal"]["pred"].mean()
        ) if "halal" in df_all["biz_group"].values else None
        di = None
        if p_non is not None and p_halal is not None and p_halal > 0:
            di = p_non / p_halal

        return jsonify(
            {
                "success": True,
                # legacy field (unchanged)
                "rates": rates,
                "disparate_impact": di,
                # new structured fields for richer dashboard
                "business_type_bias": business_type_bias,
                "industry_bias": industry_bias,
                "purpose_bias": purpose_bias,
                "contract_bias": contract_bias,
            }
        ), 200
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


if __name__ == "__main__":
    try:
        from waitress import serve
        serve(app, host="127.0.0.1", port=5000)
    except Exception:
        app.run(host="127.0.0.1", port=5000)

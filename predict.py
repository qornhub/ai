#!/usr/bin/env python3
import sys


import numpy as np
import pandas as pd
import os
os.environ["JOBLIB_MULTIPROCESSING"] = "0"
os.environ["LOKY_MAX_CPU_COUNT"] = "1"

import joblib
import json
import ast  # <-- for Windows CMD safe parsing

def json_exit(payload, exit_code=0):
    print(json.dumps(payload))
    sys.exit(exit_code)

def find_file(possible_paths):
    for p in possible_paths:
        if os.path.exists(p):
            return p
    return None

def main():
    try:
        # Read JSON input (argv[1] preferred; fallback to stdin)
        raw = None
        if len(sys.argv) > 1 and sys.argv[1].strip():
            raw = sys.argv[1]
        else:
            raw = sys.stdin.read()

        if not raw:
            raise ValueError("No input JSON provided to predict.py")

        # Try ast.literal_eval first for Windows CMD compatibility
        try:
            data = ast.literal_eval(raw)
        except Exception:
            # fallback to standard json.loads
            try:
                data = json.loads(raw)
            except Exception as e:
                raise ValueError(f"Invalid JSON input: {e}")

        if not isinstance(data, dict):
            raise ValueError("Input must be a dict of feature_name: value")

        BASE_DIR = os.path.dirname(os.path.abspath(__file__))
        model_paths = [os.path.join(BASE_DIR, 'model', 'loan_approval_model.pkl')]
        features_paths = [os.path.join(BASE_DIR, 'model', 'model_features.pkl')]

        model_file = find_file(model_paths)
        features_file = find_file(features_paths)

        if not model_file or not features_file:
            raise FileNotFoundError("Model or features file not found in ai/model/")

        # Load model + features
        model = joblib.load(model_file)
        features = joblib.load(features_file)

        # Build DataFrame
        df = pd.DataFrame([data])
        for f in features:
            if f not in df.columns:
                df[f] = 0
        df = df[features]

        # Predict
        if hasattr(model, "predict_proba"):
            prob = float(model.predict_proba(df)[0][1])
            pred = int(model.predict(df)[0])
        else:
            pred = int(model.predict(df)[0])
            prob = None

        # SHAP explanation (optional)
        explanation = None
        try:
            import shap
            explainer = shap.TreeExplainer(model)
            shap_values = explainer.shap_values(df)
            explanation = {features[i]: float(shap_values[0][i]) for i in range(len(features))}
        except Exception:
            explanation = None

        result = {
            "success": True,
            "ai_decision": "Approved" if pred == 1 else "Rejected",
            "prediction": int(pred),
            "probability": prob,
            "explanation": explanation
        }
        json_exit(result, 0)

    except Exception as e:
        json_exit({"success": False, "error": str(e)}, 1)

if __name__ == "__main__":
    main()

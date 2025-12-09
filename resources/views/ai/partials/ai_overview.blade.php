

{{-- ========== AI NARRATIVE / EXPLANATION ========== --}}
<div class="ai-narrative-card">
    <div class="ai-narrative-header">
        <div class="ai-narrative-title">
            <i class="fas fa-robot"></i>
            AI Explanation
        </div>
        <div class="ai-narrative-meta">
            <span>
                <i class="fas fa-microchip"></i>
                {{ $explain['explainer_type'] ?? 'Standard Explainer' }}
            </span>
            <span>
                <i class="fas fa-code-branch"></i>
                v{{ $explain['model_version'] ?? '1.0' }}
            </span>
            <span>
                <i class="fas fa-clock"></i>
                {{ $explain['explain_time_ms'] ?? 'N/A' }}ms
            </span>
        </div>
    </div>
    
    <div class="ai-narrative-content">
        {{ $explain['narrative'] ?? 'No narrative explanation available from the AI model for this decision.' }}
    </div>
</div>

{{-- ========== TOP FEATURE CONTRIBUTIONS ========== --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="ai-section-title">
        <i class="fas fa-chart-line"></i>
        Feature Impact Analysis
        <small class="text-muted"> (Top 12 influencing factors)</small>
    </div>
    
</div>

@php
    $shap = $explain['shap_values'] ?? [];
    $topReasons = null;

    if (!empty($shap)) {
        $topReasons = collect($shap)
            ->map(fn($v,$k)=>[
                'feature'=>$k,
                'shap_value'=>(float)$v,
                'magnitude'=>abs((float)$v),
            ])
            ->sortByDesc('magnitude')
            ->take(12)
            ->toArray();
    }
@endphp

@if ($topReasons && count($topReasons) > 0)
    <div class="feature-contributions">
        @foreach($topReasons as $r)
            @php
                $val = $r['shap_value'];
                $isPositive = $val >= 0;
                $arrow = $isPositive ? '↑' : '↓';
                $impactClass = $isPositive ? 'impact-positive' : 'impact-negative';
                $valueClass = $isPositive ? 'value-positive' : 'value-negative';
                $impactText = $isPositive ? 'Increases approval likelihood' : 'Decreases approval likelihood';
            @endphp

            <div class="feature-item">
                <div class="feature-info">
                    <div class="feature-name">{{ $r['feature'] }}</div>
                    <div class="feature-impact">
                        <span class="impact-arrow {{ $impactClass }}">{{ $arrow }}</span>
                        {{ $impactText }}
                        <span class="text-muted">• |{{ number_format(abs($val), 4) }}|</span>
                    </div>
                </div>
                <div class="feature-value {{ $valueClass }}">
                    {{ number_format($val, 4) }}
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="empty-state">
        <i class="fas fa-chart-bar"></i>
        <div>No feature contribution data available</div>
        <small class="text-muted">SHAP values not computed for this decision</small>
    </div>
@endif

{{-- ========== SHAP CHARTS ========== --}}
@if (!empty($shap))

 <!-- This is required for AJAX chart rendering -->
    <div id="ai-explain-data" data-json='@json($explain)'></div>
    <div class="ai-section-title">
        <i class="fas fa-chart-bar"></i>
        Feature Impact Visualization
    </div>

    <div class="chart-container">
        <div class="chart-title">Top Feature Contributions</div>
        <div id="shap_bar_chart" style="height: 320px;"></div>
    </div>

    <div class="chart-container">
        <div class="chart-title">Decision Waterfall Analysis</div>
        <div id="shap_waterfall" style="height: 320px;"></div>
    </div>
@endif

{{-- ========== DECISION PATH (Tree Trace) ========== --}}
@if (!empty($explain['decision_path']))
    <div class="ai-section-title">
        <i class="fas fa-sitemap"></i>
        Decision Path Analysis
    </div>

    <div class="decision-path">
        @foreach ($explain['decision_path'] as $index => $node)
            <div class="decision-node">
                <div class="node-feature">
                    {{ $node['feature'] ?? 'Unknown Feature' }}
                    <span class="text-muted" style="font-size: 0.75rem; font-weight: normal;">
                        ({{ $node['direction'] ?? 'N/A' }})
                    </span>
                </div>
                <div class="node-details">
                    <div class="node-detail">
                        <span class="node-label">Feature Value</span>
                        <span class="node-value">{{ $node['feature_value'] ?? 'N/A' }}</span>
                    </div>
                    <div class="node-detail">
                        <span class="node-label">Threshold</span>
                        <span class="node-value">{{ $node['threshold'] ?? 'N/A' }}</span>
                    </div>
                    @if(isset($node['impact']))
                    <div class="node-detail">
                        <span class="node-label">Impact</span>
                        <span class="node-value {{ $node['impact'] >= 0 ? 'impact-positive' : 'impact-negative' }}">
                            {{ number_format($node['impact'], 4) }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ======================= SHAP & WATERFALL CHART SCRIPT ======================= --}}
<script>
/**
 * Initializes all charts in the Overview section.
 * This MUST be called manually after AJAX loads the HTML.
 *
 * Usage:
 *      initAiOverviewCharts(explainObject)
 */
function initAiOverviewCharts(explain) {

    if (!explain || !explain.shap_values) return;

    // ===================== HELPERS ===================== //
    function buildShapArrays() {
        let shapMap = explain.shap_values ?? {};
        let baseValue = explain.expected_value ?? 0;
        let modelProb = explain.probability ?? null;

        const items = Object.keys(shapMap)
            .map(k => ({ k, v: Number(shapMap[k]) }))
            .sort((a, b) => Math.abs(b.v) - Math.abs(a.v));

        return { items, baseValue, modelProb };
    }

    const colors = {
        positive: 'rgba(22, 163, 74, 0.85)',
        negative: 'rgba(220, 38, 38, 0.85)',
        grid: 'rgba(229, 231, 235, 0.5)',
        text: '#374151'
    };

    // ===================== BAR CHART ===================== //
    function renderBarChart() {
        const { items } = buildShapArrays();
        if (!items.length) return;

        const top = items.slice(0, 12).reverse();
        const x = top.map(i => i.v);
        const y = top.map(i => i.k);

        Plotly.newPlot('shap_bar_chart', [{
            x: x,
            y: y,
            type: 'bar',
            orientation: 'h',
            marker: {
                color: x.map(v => v >= 0 ? colors.positive : colors.negative),
                line: { width: 1 }
            },
            hovertemplate: '<b>%{y}</b><br>SHAP Value: %{x:.4f}<extra></extra>'
        }], {
            margin: { l: 180, r: 30, t: 20, b: 40 },
            height: Math.max(320, top.length * 28),
            plot_bgcolor: 'transparent',
            paper_bgcolor: 'transparent',
            font: { family: "Inter", color: colors.text },
            xaxis: {
                gridcolor: colors.grid,
                title: { text: "SHAP Value Impact" }
            },
            yaxis: {
                gridcolor: colors.grid,
                tickfont: { size: 11 }
            },
            showlegend: false
        }, {
            displayModeBar: true,
            displaylogo: false,
            modeBarButtonsToRemove: ['lasso2d', 'select2d', 'autoScale2d'],
            responsive: true
        });
    }

    // ===================== WATERFALL CHART ===================== //
    function renderWaterfallChart() {
        const { items, baseValue, modelProb } = buildShapArrays();
        if (!items.length) return;

        const top = items.slice(0, 12);
        const contributions = top.map(i => i.v);
        const labels = top.map(i => i.k);

        const finalProb = modelProb ?? (baseValue + contributions.reduce((a, b) => a + b, 0));

        Plotly.newPlot('shap_waterfall', [{
            type: 'waterfall',
            x: [...labels, "Final Score"],
            y: [...contributions, finalProb - baseValue],
            measure: [...Array(contributions.length).fill("relative"), "total"],
            connector: { line: { color: colors.grid } },
            increasing: { marker: { color: colors.positive } },
            decreasing: { marker: { color: colors.negative } },
            totals: { marker: { color: 'rgba(59, 130, 246, 0.85)' } },
            hovertemplate: '<b>%{x}</b><br>Impact: %{y:.4f}<extra></extra>'
        }], {
            margin: { l: 50, r: 20, t: 20, b: 60 },
            height: 360,
            plot_bgcolor: 'transparent',
            paper_bgcolor: 'transparent',
            font: { family: "Inter", color: colors.text },
            xaxis: { tickangle: -45, gridcolor: colors.grid },
            yaxis: { gridcolor: colors.grid, title: "Cumulative Impact" },
            showlegend: false
        }, {
            displayModeBar: true,
            displaylogo: false,
            modeBarButtonsToRemove: ['lasso2d', 'select2d', 'autoScale2d'],
            responsive: true
        });
    }

    // ===================== RENDER BOTH ===================== //
    renderBarChart();
    renderWaterfallChart();
}
</script>






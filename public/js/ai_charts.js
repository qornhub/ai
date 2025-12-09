function initAiOverviewCharts(explain) {
    if (!explain || !explain.shap_values) return;

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

    function renderBarChart() {
        const { items } = buildShapArrays();
        if (!items.length) return;

        const top = items.slice(0, 12).reverse();
        const x = top.map(i => i.v);
        const y = top.map(i => i.k);

        Plotly.newPlot('shap_bar_chart', [{
            x, y, type: 'bar', orientation: 'h',
            marker: {
                color: x.map(v => v >= 0 ? colors.positive : colors.negative),
                line: { width: 1 }
            },
        }], {
            margin: { l: 180, r: 30, t: 20, b: 40 },
            plot_bgcolor: 'transparent',
            paper_bgcolor: 'transparent'
        });
    }

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
        }], {
            margin: { l: 50, r: 20, t: 20, b: 60 },
        });
    }

    renderBarChart();
    renderWaterfallChart();
}

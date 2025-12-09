// public/js/ai_bias_charts.js
(function () {

    console.log("ai_bias_charts.js loaded");

    // Fix Laravel Blade JSON inside data-* attributes.
    function safeBladeJSON(raw, fallback = []) {
        console.log("safeBladeJSON RAW:", raw);

        if (!raw) return fallback;

        try {
            let fixed = raw
                .replace(/&quot;/g, '"')
                .replace(/&#39;/g, "'")
                .replace(/&amp;/g, "&")
                .trim();

            console.log("safeBladeJSON CLEANED:", fixed);

            let parsed = JSON.parse(fixed);
            console.log("safeBladeJSON PARSED:", parsed);

            return parsed;

        } catch (e) {
            console.error("safeBladeJSON() FAILED:", raw, e);
            return fallback;
        }
    }

    // Professional color scheme using your CSS variables
    const colors = {
        primary: '#004aad',
        success: '#16a34a',
        error: '#dc2626',
        warning: '#f59e0b',
        grid: '#e0e4ec',
        textPrimary: '#111827',
        textSecondary: '#6b7280',
        bgMain: '#ffffff',
        bgSubtle: '#f9fafb'
    };

    function initAiBiasCharts() {
        console.log("initAiBiasCharts() CALLED");

        if (typeof Plotly === 'undefined') {
            console.error('Plotly missing — cannot draw charts');
            return;
        }

        const biasRoot = document.getElementById('bias-data');
        if (!biasRoot) {
            console.warn("initAiBiasCharts: #bias-data NOT FOUND");
            return;
        }

        console.log("biasRoot.dataset:", biasRoot.dataset);

        // Parse all datasets
        const bizLabels = safeBladeJSON(biasRoot.dataset.bizLabels, []);
        const bizValues = safeBladeJSON(biasRoot.dataset.bizValues, []);

        const indLabels = safeBladeJSON(biasRoot.dataset.indLabels, []);
        const indValues = safeBladeJSON(biasRoot.dataset.indValues, []);

        const purLabels = safeBladeJSON(biasRoot.dataset.purLabels, []);
        const purValues = safeBladeJSON(biasRoot.dataset.purValues, []);

        const conLabels = safeBladeJSON(biasRoot.dataset.conLabels, []);
        const conValues = safeBladeJSON(biasRoot.dataset.conValues, []);

        console.log("FINAL PARSED DATA:", {
            bizLabels, bizValues,
            indLabels, indValues,
            purLabels, purValues,
            conLabels, conValues
        });

        // Enhanced color picker with gradients
        function pickColors(values, highColor, lowColor) {
            return values.map(v => {
                const value = Number(v);
                if (value >= 0.7) return highColor;
                if (value >= 0.5) return adjustColor(highColor, -20);
                if (value >= 0.3) return adjustColor(lowColor, 20);
                return lowColor;
            });
        }

        function adjustColor(hex, percent) {
            const num = parseInt(hex.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return "#" + (
                0x1000000 +
                (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)
            ).toString(16).slice(1);
        }

        // Common layout configuration
        const commonLayout = {
            paper_bgcolor: 'transparent',
            plot_bgcolor: 'transparent',
            font: {
                family: 'Inter, -apple-system, BlinkMacSystemFont, system-ui, sans-serif',
                color: colors.textPrimary,
                size: 12
            },
            xaxis: {
                title: {
                    text: 'Approval Rate',
                    font: { size: 12, color: colors.textSecondary }
                },
                range: [0, 1],
                tickformat: '.0%',
                gridcolor: colors.grid,
                zerolinecolor: colors.grid,
                showgrid: true,
                tickfont: { color: colors.textSecondary }
            },
            yaxis: {
                automargin: true,
                tickpadding: 10,       // ← EXTRA SPACING YOU REQUESTED
                tickfont: { color: colors.textPrimary, size: 11 },
                showgrid: false
            },
            margin: {
                l: 200,        // ← DEFAULT LEFT SPACING (INCREASED)
                r: 30,
                t: 40,
                b: 60
            },
            showlegend: false,
        };

        const plotlyConfig = {
            displayModeBar: true,
            displaylogo: false,
            modeBarButtonsToRemove: ['pan2d', 'lasso2d', 'select2d', 'autoScale2d'],
            responsive: true,
            scrollZoom: false
        };

        const hoverTemplate = '<b>%{y}</b><br>Approval Rate: <b>%{x:.1%}</b><extra></extra>';

        // --------------------------
        // BUSINESS CHART
        // --------------------------
        if (bizLabels.length && bizValues.length && document.getElementById('chart_business')) {

            const businessTrace = {
                x: bizValues,
                y: bizLabels,
                type: 'bar',
                orientation: 'h',
                marker: {
                    color: pickColors(bizValues, colors.success, colors.error),
                    opacity: 0.85
                },
                hovertemplate: hoverTemplate,
                text: bizValues.map(v => (v * 100).toFixed(1) + '%'),
                textposition: 'outside'
            };

            const businessLayout = {
                ...commonLayout,
                margin: { ...commonLayout.margin, l: 240 },  // ← MORE LEFT SPACE
                yaxis: { ...commonLayout.yaxis, tickpadding: 12 }, // ← MORE GAP
                
            };

            Plotly.newPlot('chart_business', [businessTrace], businessLayout, plotlyConfig);
            console.log("chart_business rendered");
        }

        // --------------------------
        // INDUSTRY CHART
        // --------------------------
        if (indLabels.length && indValues.length && document.getElementById('chart_industry')) {

            const industryTrace = {
                x: indValues,
                y: indLabels,
                type: 'bar',
                orientation: 'h',
                marker: {
                    color: pickColors(indValues, '#0d9488', '#ea580c'),
                    opacity: 0.85
                },
                hovertemplate: hoverTemplate,
                text: indValues.map(v => (v * 100).toFixed(1) + '%'),
                textposition: 'outside'
            };

            const industryLayout = {
                ...commonLayout,
                margin: { ...commonLayout.margin, l: 260 },
                yaxis: { ...commonLayout.yaxis, tickpadding: 12 },
                
            };

            Plotly.newPlot('chart_industry', [industryTrace], industryLayout, plotlyConfig);
        }

        // --------------------------
        // PURPOSE CHART
        // --------------------------
        if (purLabels.length && purValues.length && document.getElementById('chart_purpose')) {

            const purposeTrace = {
                x: purValues,
                y: purLabels,
                type: 'bar',
                orientation: 'h',
                marker: {
                    color: pickColors(purValues, '#7c3aed', '#db2777'),
                    opacity: 0.85
                },
                hovertemplate: hoverTemplate,
                text: purValues.map(v => (v * 100).toFixed(1) + '%'),
                textposition: 'outside'
            };

            const purposeLayout = {
                ...commonLayout,
                margin: { ...commonLayout.margin, l: 280 },
                yaxis: { ...commonLayout.yaxis, tickpadding: 12 },
                
            };

            Plotly.newPlot('chart_purpose', [purposeTrace], purposeLayout, plotlyConfig);
        }

        // --------------------------
        // CONTRACT CHART
        // --------------------------
        if (conLabels.length && conValues.length && document.getElementById('chart_contract')) {

            const contractTrace = {
                x: conValues,
                y: conLabels,
                type: 'bar',
                orientation: 'h',
                marker: {
                    color: pickColors(conValues, '#dc2626', '#d97706'),
                    opacity: 0.85
                },
                hovertemplate: hoverTemplate,
                text: conValues.map(v => (v * 100).toFixed(1) + '%'),
                textposition: 'outside'
            };

            const contractLayout = {
                ...commonLayout,
                margin: { ...commonLayout.margin, l: 260 },
                yaxis: { ...commonLayout.yaxis, tickpadding: 12 },
                
            };

            Plotly.newPlot('chart_contract', [contractTrace], contractLayout, plotlyConfig);
        }

        window.addEventListener('resize', function () {
            ['chart_business', 'chart_industry', 'chart_purpose', 'chart_contract']
                .forEach(id => {
                    if (document.getElementById(id)) Plotly.Plots.resize(id);
                });
        });

        console.log("initAiBiasCharts COMPLETE.");
    }

    // Expose globally
    window.initAiBiasCharts = initAiBiasCharts;

    // Auto-run for full page load
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => setTimeout(initAiBiasCharts, 100));
    } else {
        setTimeout(initAiBiasCharts, 100);
    }

})();

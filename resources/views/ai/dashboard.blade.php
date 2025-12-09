<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Business Financing AI Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- ChatGPT font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Use a versioned Plotly bundle (example: v2.x) -->
    <script src="https://cdn.plot.ly/plotly-2.24.1.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/decision_show.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    
    <link rel="stylesheet" href="{{ asset('css/ai.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bias.css') }}">
<style>
    /* ============================
   SIDEBAR USER BLOCK
============================ */

.sidebar-user {
    margin-top: auto;
    padding: 16px;
    border-top: 1px solid #e0e4ec;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sidebar-user-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Avatar circle */
.sidebar-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #004aad; /* your primary color */
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    letter-spacing: 1px;
    font-size: 0.9rem;
}

.sidebar-user-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.sidebar-user-plan {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.sidebar-user .menu-button {
    border: none;
    background: transparent;
    padding: 4px;
    cursor: pointer;
    color: #6b7280;
}

.sidebar-user .menu-button:hover {
    color: #111;
}

</style>
</head>

<body>

    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">

            <div class="app-title d-flex align-items-center gap-2">
                <img src="{{ asset('images/lugo.png') }}" alt="Logo"
                    style="width:36px; height:36px; object-fit:contain;">

                <span>Chitgbd AI</span>
            </div>

            <!-- Search -->
            <form method="GET" action="{{ route('ai.index') }}">
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" value="{{ $q ?? '' }}" class="search-input"
                        placeholder="Search name or date...">
                </div>
            </form>

            <!-- ============================================
         NEW BUTTONS BELOW SEARCH
    ============================================= -->
            <button class="new-button w-100 mb-3 js-new-application" data-url="{{ route('ai.form') }}">
                <i class="bi bi-plus-circle"></i>
                New Application
            </button>

            <button class="new-button w-100 mb-4 js-load-page" data-url="{{ route('ai.bias') }}">
                <i class="fa-solid fa-scale-balanced"></i>
                Bias Detection
            </button>

            <h6 class="text-muted fw-semibold" style="font-size:13px; margin-bottom:12px;">
                Past results
            </h6>

            @forelse($decisions as $d)
                @php
                    $app = $d->applicant;
                    $title = $app ? $app->customer_name ?? "Applicant #{$app->id}" : "Decision #{$d->id}";
                    $time = $d->created_at->format('Y-m-d H:i');
                @endphp

                <div class="history-item">

                    <a href="{{ route('ai.decision_show', $d->id) }}"
                        class="text-decoration-none text-dark js-load-decision" data-id="{{ $d->id }}"
                        data-url="{{ route('ai.decision_show', $d->id) }}" style="flex:1;">

                        <div class="history-title">{{ $title }}</div>
                        <div class="history-sub">{{ $time }}</div>
                    </a>

                    <div class="dropdown">
                        <button class="menu-button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form method="POST" action="{{ route('ai.delete', $d->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="dropdown-item text-danger delete-btn">
                                        <i class="bi bi-trash"></i>
                                        Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>

                </div>

            @empty
                <div class="text-muted small">No results found.</div>
            @endforelse

            <!-- ============================== -->
<!-- USER ACCOUNT AREA (BOTTOM)     -->
<!-- ============================== -->
<div class="sidebar-user">
    <div class="sidebar-user-left">

        <!-- Avatar with initials -->
        <div class="sidebar-avatar">
            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
        </div>

        <div class="sidebar-user-info">
            <div class="sidebar-user-name">
                {{ Auth::user()->name }}
            </div>
            <div class="sidebar-user-plan text-muted">Signed in</div>
        </div>

    </div>

    <!-- Dropdown -->
    <div class="dropdown">
        <button class="menu-button" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots-vertical"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>

</div>

        </aside>


        <!-- MAIN -->
        <main class="main">

            <div id="main-content">
                <div class="welcome-box">
                    <div class="welcome-title">AI-powered loan approval prediction system specifically tailored for
                        business financing</div>
                    <div class="welcome-sub">Click “New Application” to begin analysing a financing application.</div>

                    <button type="button" class="new-button js-new-application" data-url="{{ route('ai.form') }}">
                        <i class="bi bi-plus-circle"></i>
                        New Application
                    </button>
                </div>
            </div>

            <div class="footer">
                Chitgbd AI © {{ date('Y') }}
            </div>

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function() {

    const mainContent = document.getElementById('main-content');

    // ======================================================================
    // Helper: Load HTML via AJAX and reinitialize all necessary UI features
    // ======================================================================
    function loadIntoMain(url, loadingText = "Loading...") {

        mainContent.innerHTML = `
            <div class="d-flex flex-column align-items-center w-100 mt-4">
                <div class="spinner-border spinner-border-sm mb-2"></div>
                <div class="text-muted">${loadingText}</div>
            </div>
        `;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            mainContent.classList.remove('fade-in');
            mainContent.innerHTML = html;
            mainContent.classList.add('fade-in');

            if (typeof initDecisionShowUI === "function") {
                initDecisionShowUI();
            }

            // BIAS
            const biasRoot = document.querySelector('#bias-data');
            if (biasRoot && typeof initAiBiasCharts === "function") {
                initAiBiasCharts();
            }

            // SHAP
            const explainRoot = document.querySelector('#ai-explain-data');
            if (explainRoot && typeof initAiOverviewCharts === "function") {
                const explain = JSON.parse(explainRoot.dataset.json);
                initAiOverviewCharts(explain);
            }
        })
        .catch(() => {
            mainContent.innerHTML = `
                <div class="alert alert-danger mt-4">
                    Failed to load content. Please try again.
                </div>
            `;
        });
    }

    // ======================================================================
    // NEW APPLICATION (loads form.blade via AJAX)
    // ======================================================================
    document.querySelectorAll('.js-new-application').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            loadIntoMain(this.getAttribute('data-url'), "Loading application form...");
        });
    });

    // ======================================================================
    // LOAD DECISION HISTORY (decision_show.blade)
    // ======================================================================
    document.querySelectorAll('.js-load-decision').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            loadIntoMain(this.getAttribute('data-url'), "Loading result...");
        });
    });

    // ======================================================================
    // AJAX SUBMIT – AI PREDICTION
    // ======================================================================
    document.body.addEventListener('submit', function(e) {
        const form = e.target;

        if (form.classList.contains('js-ai-form')) {
            e.preventDefault();

            const url = form.getAttribute('action');

            mainContent.innerHTML = `
                <div class="d-flex flex-column align-items-center w-100 mt-4">
                    <div class="spinner-border spinner-border-sm mb-2"></div>
                    <div class="text-muted">Running prediction...</div>
                </div>
            `;

            fetch(url, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                mainContent.innerHTML = html;

                if (typeof initDecisionShowUI === "function") initDecisionShowUI();

                const biasRoot = document.querySelector('#bias-data');
                if (biasRoot && typeof initAiBiasCharts === "function") initAiBiasCharts();

                const explainRoot = document.querySelector('#ai-explain-data');
                if (explainRoot && typeof initAiOverviewCharts === "function") {
                    const explain = JSON.parse(explainRoot.dataset.json);
                    initAiOverviewCharts(explain);
                }

                mainContent.classList.add('fade-in');
            })
            .catch(() => {
                mainContent.innerHTML = `
                    <div class="alert alert-danger mt-4">
                        Prediction failed. Please try again.
                    </div>
                `;
            });
        }
    });

    // ======================================================================
    // AJAX OVERRIDE DECISION  ⭐⭐ NEW ⭐⭐
    // ======================================================================
    document.body.addEventListener('submit', function(e) {
        const form = e.target;

        if (form.classList.contains('js-override-form')) {
            e.preventDefault();

            const url = form.getAttribute('action');
            const formData = new FormData(form);

            mainContent.innerHTML = `
                <div class="d-flex flex-column align-items-center w-100 mt-4">
                    <div class="spinner-border spinner-border-sm mb-2"></div>
                    <div class="text-muted">Saving override...</div>
                </div>
            `;

            fetch(url, {
                method: "POST",
                body: formData,
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(res => res.text())
            .then(html => {
                mainContent.innerHTML = html;

                if (typeof initDecisionShowUI === "function") initDecisionShowUI();

                // re-init SHAP
                const explainRoot = document.querySelector('#ai-explain-data');
                if (explainRoot && typeof initAiOverviewCharts === "function") {
                    const explain = JSON.parse(explainRoot.dataset.json);
                    initAiOverviewCharts(explain);
                }

                // re-init BIAS
                const biasRoot = document.querySelector('#bias-data');
                if (biasRoot && typeof initAiBiasCharts === "function") {
                    initAiBiasCharts();
                }

                mainContent.classList.add('fade-in');
            })
            .catch(() => {
                mainContent.innerHTML = `
                    <div class="alert alert-danger mt-4">
                        Failed to save override.
                    </div>
                `;
            });
        }
    });

    // ======================================================================
    // LOAD OTHER PAGES (Bias Detection)
    // ======================================================================
    document.querySelectorAll('.js-load-page').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            loadIntoMain(this.dataset.url, "Loading...");
        });
    });

});
</script>


    <!-- Your global chart renderer -->
    <script src="{{ asset('js/ai_charts.js') }}"></script>
    <!-- Use a versioned Plotly bundle (example: v2.x) -->
    <script src="https://cdn.plot.ly/plotly-2.24.1.min.js"></script>
    <script src="{{ asset('js/ai_bias_charts.js') }}"></script>
</body>

</html>

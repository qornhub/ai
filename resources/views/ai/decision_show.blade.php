@php
    $isAjax = request()->ajax();
    $result = session('result') ?? null;
@endphp

@if (!$isAjax)
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Decision — Business Financing AI</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>


    </head>

    <body>
@endif

<div class="container container-main py-4">

    @php
        $finalDecision = $decision->corrected_decision ? $decision->corrected_decision : $decision->ai_decision;
    @endphp

    {{-- Top frame: back --}}


    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- 1. Decision summary header - REFINED --}}
    <div class="decision-header mb-4"
        style="border-left-color: {{ $finalDecision === 'Approved' ? 'var(--success-color)' : 'var(--error-color)' }};">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div class="flex-grow-1">
                <h1 class="decision-status"
                    style="color: {{ $finalDecision === 'Approved' ? 'var(--success-color)' : 'var(--error-color)' }};">
                    <span class="decision-status-badge"
                        style="background: {{ $finalDecision === 'Approved' ? 'rgba(22, 163, 74, 0.1)' : 'rgba(220, 38, 38, 0.1)' }}; color: {{ $finalDecision === 'Approved' ? 'var(--success-color)' : 'var(--error-color)' }};">
                        <i class="fas fa-{{ $finalDecision === 'Approved' ? 'check-circle' : 'times-circle' }}"></i>
                        {{ $finalDecision }}
                    </span>
                </h1>
                <p class="decision-subtitle">
                    Business Financing Decision · AI-assisted with human oversight
                </p>
            </div>

            <div class="text-end">

                <div style="font-weight: 600; font-size: 0.9rem;">{{ $decision->created_at->format('M j, Y') }}
                </div>
                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                    {{ $decision->created_at->format('g:i A') }}</div>
            </div>
        </div>

        <div class="decision-meta-grid">
            <div class="meta-item">
                <div class="meta-label">Applicant ID</div>
                <div class="meta-value">{{ $decision->applicant_id }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Business Name</div>
                <div class="meta-value">{{ $decision->applicant->business_name ?? '-' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Owner Name</div>
                <div class="meta-value">{{ $decision->applicant->owner_name ?? '-' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">SSM No.</div>
                <div class="meta-value">{{ $decision->applicant->business_registration_no ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- 2. Summary card: Business & Financing Profile + Decision & Override --}}
    <div class="card-custom p-4 mb-4">
        <div class="row g-4 summary-layout">
            {{-- Left: Business & Financing Profile --}}
            <div class="col-lg-8 col-md-6 summary-left">
                {{-- Your existing left column content remains exactly the same --}}
                <div class="section-header mb-3">
                    <div class="section-title">
                        <i class="fas fa-building"></i>
                        Business & Financing Profile
                    </div>
                    <p class="section-subtext">
                        Snapshot of business characteristics, financial health and requested facility used by the AI
                        model.
                    </p>
                </div>

                {{-- BUSINESS PROFILE --}}
                <div class="form-section">
                    <div class="subsection-header">
                        <i class="fas fa-id-card"></i>
                        <h6 class="subsection-title">Business Profile</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Business Type</label>
                                <div class="form-value">{{ $decision->applicant->business_type ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Years in Business</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->years_in_business))
                                        {{ $decision->applicant->years_in_business }} years
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="form-field">
                                <label class="form-label">Industry Category</label>
                                <div class="form-value">{{ $decision->applicant->industry_category ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FINANCIAL DATA --}}
                <div class="form-section">
                    <div class="subsection-header">
                        <i class="fas fa-chart-line"></i>
                        <h6 class="subsection-title">Financial Data</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Annual Revenue</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->annual_revenue))
                                        RM {{ number_format($decision->applicant->annual_revenue, 0) }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Net Profit</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->net_profit))
                                        RM {{ number_format($decision->applicant->net_profit, 0) }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Monthly Cashflow</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->monthly_cashflow))
                                        RM {{ number_format($decision->applicant->monthly_cashflow, 0) }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Existing Liabilities</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->existing_liabilities))
                                        RM {{ number_format($decision->applicant->existing_liabilities, 0) }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FINANCING INFORMATION --}}
                <div class="form-section">
                    <div class="subsection-header">
                        <i class="fas fa-hand-holding-usd"></i>
                        <h6 class="subsection-title">Financing Information</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Financing Amount</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->financing_amount))
                                        RM {{ number_format($decision->applicant->financing_amount, 0) }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Profit Rate</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->profit_rate))
                                        {{ rtrim(rtrim(number_format($decision->applicant->profit_rate, 2), '0'), '.') }}%
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Tenure</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->tenure_months))
                                        {{ $decision->applicant->tenure_months }} months
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Contract Type</label>
                                <div class="form-value">{{ $decision->applicant->contract_type ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-field">
                                <label class="form-label">Financing Purpose</label>
                                <div class="form-value">{{ $decision->applicant->financing_purpose ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CREDIT, RISK & COLLATERAL --}}
                <div class="form-section">
                    <div class="subsection-header">
                        <i class="fas fa-shield-alt "></i>
                        <h6 class="subsection-title">Credit &amp; Risk Indicators</h6>
                    </div>
                    <div class="row g-3 ">
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Credit Score</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->credit_score))
                                        <span
                                            class="credit-score {{ $decision->applicant->credit_score >= 700 ? 'score-high' : ($decision->applicant->credit_score >= 600 ? 'score-medium' : 'score-low') }}">
                                            {{ $decision->applicant->credit_score }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Past Default</label>
                                <div class="form-value">
                                    @if (is_null($decision->applicant->past_default))
                                        -
                                    @elseif($decision->applicant->past_default)
                                        <span class="status-badge status-warning">Yes (has past default)</span>
                                    @else
                                        <span class="status-badge status-success">No</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <div class="subsection-header">
                        <i class="fas fa-home"></i>
                        <h6 class="subsection-title">Collateral</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Collateral Type</label>
                                <div class="form-value">{{ $decision->applicant->collateral_type ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <div class="form-field">
                                <label class="form-label">Collateral Value</label>
                                <div class="form-value">
                                    @if (!is_null($decision->applicant->collateral_value))
                                        RM {{ number_format($decision->applicant->collateral_value, 0) }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Decision Analysis + Override - REFINED --}}
            <div class="col-lg-4 col-md-6 summary-right">
                <div class="summary-right-content">
                    <div class="section-title">
                        <i class="fas fa-chart-bar"></i>
                        Decision Analysis
                    </div>
                    <p class="section-subtext">
                        Confidence level and alignment between AI, human benchmark, and any manual override.
                    </p>

                    {{-- Confidence Score Card --}}
                    <div class="stat-card"
                        style="border-top-color: {{ $finalDecision === 'Approved' ? 'var(--success-color)' : 'var(--error-color)' }};">
                        <div class="stat-value"
                            style="color: {{ $finalDecision === 'Approved' ? 'var(--success-color)' : 'var(--error-color)' }};">
                            {{ number_format($decision->probability * 100, 1) }}%
                        </div>
                        <div class="stat-label">Model Confidence</div>
                    </div>

                    {{-- Decision Metrics --}}
                    <div class="metric-stack">
                        <div class="metric-row">
                            <span class="metric-label">AI Decision</span>
                            <span
                                class="status-indicator {{ $decision->ai_decision === 'Approved' ? 'status-approved' : 'status-rejected' }}">
                                <i class="fas fa-{{ $decision->ai_decision === 'Approved' ? 'check' : 'times' }}"></i>
                                {{ $decision->ai_decision }}
                            </span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">Human Benchmark</span>
                            <span>
                                @if ($human_decision === 1)
                                    <span class="status-indicator status-approved">
                                        <i class="fas fa-check"></i> Approved
                                    </span>
                                @elseif($human_decision === 0)
                                    <span class="status-indicator status-rejected">
                                        <i class="fas fa-times"></i> Rejected
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.85rem;">Not provided</span>
                                @endif
                            </span>
                        </div>

                        <div class="metric-row">
                            <span class="metric-label">AI vs Human</span>
                            <span>
                                @if ($agreement === 1)
                                    <span class="status-indicator status-agree">
                                        <i class="fas fa-check-circle"></i> Agrees
                                    </span>
                                @elseif ($agreement === 0)
                                    <span class="status-indicator status-differ">
                                        <i class="fas fa-times-circle"></i> Differs
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.85rem;">N/A</span>
                                @endif
                            </span>
                        </div>

                        @if ($decision->corrected_decision)
                            @php
                                $correctedMatch =
                                    ($decision->corrected_decision === 'Approved' && $human_decision === 1) ||
                                    ($decision->corrected_decision === 'Rejected' && $human_decision === 0);
                            @endphp

                            <div class="metric-row">
                                <span class="metric-label">Override vs Human</span>
                                <span>
                                    @if ($correctedMatch)
                                        <span class="status-indicator status-agree">
                                            <i class="fas fa-check-circle"></i> Matches
                                        </span>
                                    @else
                                        <span class="status-indicator status-differ">
                                            <i class="fas fa-times-circle"></i> Differs
                                        </span>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Override Section --}}
                    <div class="override-section">
                        <div class="override-title">
                            <i class="fas fa-edit"></i>
                            Override Decision
                        </div>
                        <p class="override-subtext">
                            Use overrides for exceptional cases only. Your correction will be stored as the final
                            decision.
                        </p>

                        <form method="POST" action="{{ route('ai.override', $decision->id) }}"
                            class="js-override-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Corrected Decision</label>
                                <div style="position:relative;">
                                    <select name="corrected_decision" class="form-select"
                                        style="padding-right:40px;">
                                        <option value="Approved"
                                            {{ $decision->corrected_decision === 'Approved' ? 'selected' : '' }}>
                                            Approve Application</option>
                                        <option value="Rejected"
                                            {{ $decision->corrected_decision === 'Rejected' ? 'selected' : '' }}>Reject
                                            Application</option>
                                    </select>

                                    <i class="fas fa-chevron-down"
                                        style="
           position:absolute;
           right:14px;
           top:50%;
           transform:translateY(-50%);
           color:#555;
           pointer-events:none;">
                                    </i>
                                </div>

                            </div>

                            <button class="btn btn-warning-custom">
                                <i class="fas fa-save me-2"></i>
                                Save Override
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Analysis details: AI overview --}}
    <div class="card-custom p-3 mb-4">
        <div class="section-title px-1 pt-1 mb-2">
            <i class="fas fa-microscope"></i>
            Analysis Details
        </div>

        <div class="accordion accordion-custom" id="analysisAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseOverview" aria-expanded="true" aria-controls="collapseOverview">
                        <i class="fas fa-brain me-2"></i>
                        Detailed AI Overview
                    </button>
                </h2>

                <div id="collapseOverview" class="accordion-collapse collapse show"
                    data-bs-parent="#analysisAccordion">
                    <div class="accordion-body">
                        @include('ai.partials.ai_overview', [
                            'explain' => $explain,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>



</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
    /**
     * Initialize UI animations & override confirmation
     * Must be called BOTH:
     *  - on normal page load
     *  - after AJAX loads the decision_show HTML
     */
    function initDecisionShowUI() {

        // ============================
        // Animate stat cards on scroll
        // ============================
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -40px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        const statCards = document.querySelectorAll('.stat-card');

        statCards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(16px)';
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            observer.observe(card);
        });


        // ============================
        // Override confirmation popup
        // ============================
        const overrideForm = document.querySelector('form[action*="override"]');

        if (overrideForm) {
            overrideForm.addEventListener('submit', function(e) {

                const select = this.querySelector('select[name="corrected_decision"]');
                if (!select) return;

                const decision = select.options[select.selectedIndex].text;

                if (!confirm(`Are you sure you want to override the decision to "${decision}"?`)) {
                    e.preventDefault();
                }
            });
        }
    }

    // Run immediately for normal page load
    initDecisionShowUI();
</script>


@if (!$isAjax)
    </body>

    </html>
@endif

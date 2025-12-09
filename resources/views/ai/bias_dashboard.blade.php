@php
    $isAjax = request()->ajax();
    $result = session('result') ?? null;
@endphp

@if (!$isAjax)
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Bias Detection Dashboard — Business Financing AI</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>

    <body>
@endif

<div class="container container-main py-4">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            
            <h1 class="page-title">
                <i class="fas fa-balance-scale"></i>
                Bias Detection Dashboard
            </h1>
        </div>

        @php
            $di = $bias['disparate_impact'] ?? null;
            $diLabel = null;
            $diClass = 'status-neutral';
            if ($di !== null) {
                if ($di < 0.8) {
                    $diLabel = 'Unfair';
                    $diClass = 'status-unfair';
                } elseif ($di < 1.25) {
                    $diLabel = 'Fair';
                    $diClass = 'status-fair';
                } else {
                    $diLabel = 'Needs Review';
                    $diClass = 'status-review';
                }
            }
        @endphp

       
    </div>

    {{-- Error Handling --}}
    @if (!($bias['success'] ?? false))
        <div class="alert-custom alert-danger animate-fade-in">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.2rem;"></i>
                <div>
                    <strong>Analysis Failed</strong>
                    <div class="mt-1">{{ $bias['error'] ?? 'Unable to generate bias report.' }}</div>
                </div>
            </div>
        </div>
    @else
        {{-- =========================================================
            1. FAIRNESS OVERVIEW - DISPARATE IMPACT
        ========================================================= --}}
        <div class="card-custom mb-4 animate-fade-in">
            <div class="card-header-custom">
                <div class="section-title">
                    <i class="fas fa-gavel"></i>
                    Shariah Fairness Analysis
                </div>
                <p class="section-subtitle">
                    Measures whether non-halal businesses are systematically approved at different rates than halal
                    businesses.
                    A ratio between <strong>0.80-1.25</strong> indicates fair treatment.
                </p>
            </div>

            <div class="card-body-custom">
                @if ($di === null)
                    <div class="empty-state">
                        <i class="fas fa-chart-pie"></i>
                        <div class="empty-state-title">Insufficient Data</div>
                        <p>Not enough data to compute disparate impact (need both halal and non-halal groups).</p>
                    </div>
                @else
                    <div class="row align-items-center">
                        <div class="col-lg-4 mb-4 mb-lg-0">
                            <div class="text-center">
                                <div class="stat-value"
                                    style="color: {{ $diClass == 'status-fair' ? 'var(--success-color)' : ($diClass == 'status-unfair' ? 'var(--error-color)' : 'var(--warning-color)') }};">
                                    {{ number_format($di, 2) }}
                                </div>
                                <div class="stat-label">Disparate Impact Ratio</div>
                                <div class="mt-2">
                                    <span class="status-badge {{ $diClass }}">
                                        {{ $diLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="fairness-scale">
                                <div class="scale-labels">
                                    <span>Unfair (&lt;0.80)</span>
                                    <span>Balanced (0.80-1.25)</span>
                                    <span>Review Needed (&gt;1.25)</span>
                                </div>

                                <div class="scale-progress">
                                    <div class="scale-progress-fill"
                                        style="width: {{ max(0, min(100, ($di / 2) * 100)) }}%;
                                                background: {{ $diClass == 'status-fair' ? 'var(--success-color)' : ($diClass == 'status-unfair' ? 'var(--error-color)' : 'var(--warning-color)') }};">
                                    </div>
                                    <div class="scale-marker" style="left: {{ max(0, min(100, ($di / 2) * 100)) }}%;">
                                    </div>
                                </div>

                                <div class="scale-zones">
                                    <div class="scale-zone" style="text-align: left;">0.0</div>
                                    <div class="scale-zone">0.8</div>
                                    <div class="scale-zone">1.0</div>
                                    <div class="scale-zone">1.25</div>
                                    <div class="scale-zone" style="text-align: right;">2.0+</div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    DI = P(approve | Non-Halal) / P(approve | Halal)
                                </small>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- =========================================================
            2. BUSINESS TYPE ANALYSIS
        ========================================================= --}}
       

        @php
    $biz = $bias['business_type_bias'] ?? [];

    $biz_labels = array_keys($biz);
    $biz_values = array_values(array_map(fn($x) => $x['approval_rate'] ?? 0, $biz));

    $biz_total = array_sum(array_map(fn($x) => $x['count'] ?? 0, $biz));
@endphp


        <div class="card-custom mb-4 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="card-header-custom">
                <div class="section-title">
                    <i class="fas fa-building"></i>
                    Business Type Analysis
                </div>
                <p class="section-subtitle">
                    Approval rates across different business types to identify potential bias in Shariah-compliance
                    treatment.
                </p>
            </div>

            <div class="card-body-custom">
                <div class="metric-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ $biz_total }}</div>
                        <div class="stat-label">Total Applicants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ count($biz_labels) }}</div>
                        <div class="stat-label">Business Types</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            @if ($biz_total > 0 && count($biz_values) > 0)
                                {{ number_format((array_sum($biz_values) / count($biz_values)) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div class="stat-label">Avg Approval Rate</div>
                    </div>
                </div>

                @if (!empty($biz))
                   
                    <div class="chart-container">
                        <div class="chart-title">Approval Rates by Business Type</div>
                        <div id="chart_business" style="height: 300px;"></div>
                    </div>

                    <div class="subsection-title">Detailed Breakdown</div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Business Type</th>
                                    <th>Approval Rate</th>
                                    <th>Total Applications</th>
                                    <th>Approved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($biz as $group => $row)
                                    <tr>
                                        <td><strong>{{ $group }}</strong></td>
                                        <td><span
                                                class="fw-semibold">{{ number_format($row['approval_rate'] * 100, 1) }}%</span>
                                        </td>
                                        <td>{{ $row['count'] }}</td>
                                        <td>{{ round($row['approval_rate'] * $row['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <div class="empty-state-title">No Data Available</div>
                        <p>Insufficient data to generate business type analysis.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- =========================================================
            3. INDUSTRY CATEGORY ANALYSIS
        ========================================================= --}}
        @php
            $ind = $bias['industry_bias'] ?? [];
            $ind_labels = array_keys($ind);
            $ind_values = array_values(array_map(fn($x) => $x['approval_rate'] ?? 0, $ind));

            $ind_total = array_sum(array_map(fn($x) => $x['count'] ?? 0, $ind));
        @endphp

        <div class="card-custom mb-4 animate-fade-in" style="animation-delay: 0.2s;">
            <div class="card-header-custom">
                <div class="section-title">
                    <i class="fas fa-industry"></i>
                    Industry Category Analysis
                </div>
                <p class="section-subtitle">
                    Identifies potential bias across different industry sectors to ensure fair treatment regardless of
                    business domain.
                </p>
            </div>

            <div class="card-body-custom">
                <div class="metric-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ $ind_total }}</div>
                        <div class="stat-label">Total Applicants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ count($ind_labels) }}</div>
                        <div class="stat-label">Industry Groups</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            @if ($ind_total > 0 && count($ind_values) > 0)
                                {{ number_format((array_sum($ind_values) / count($ind_values)) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div class="stat-label">Avg Approval Rate</div>
                    </div>
                </div>

                @if (!empty($ind))
                    <div class="chart-container">
                        <div class="chart-title">Approval Rates by Industry</div>
                        
                        <div id="chart_industry" style="height: 300px;"></div>
                    </div>

                    <div class="subsection-title">Industry Performance</div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Industry Category</th>
                                    <th>Approval Rate</th>
                                    <th>Total Applications</th>
                                    <th>Approved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ind as $group => $row)
                                    <tr>
                                        <td><strong>{{ $group }}</strong></td>
                                        <td><span
                                                class="fw-semibold">{{ number_format($row['approval_rate'] * 100, 1) }}%</span>
                                        </td>
                                        <td>{{ $row['count'] }}</td>
                                        <td>{{ round($row['approval_rate'] * $row['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-industry"></i>
                        <div class="empty-state-title">No Data Available</div>
                        <p>Insufficient data to generate industry analysis.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- =========================================================
            4. FINANCING PURPOSE ANALYSIS
        ========================================================= --}}
        @php
            $pur = $bias['purpose_bias'] ?? [];
            $pur_labels = array_keys($pur);
            $pur_values = array_values(array_map(fn($x) => $x['approval_rate'] ?? 0, $pur));

            $pur_total = array_sum(array_map(fn($x) => $x['count'] ?? 0, $pur));
        @endphp

        <div class="card-custom mb-4 animate-fade-in" style="animation-delay: 0.3s;">
            <div class="card-header-custom">
                <div class="section-title">
                    <i class="fas fa-bullseye"></i>
                    Financing Purpose Analysis
                </div>
                <p class="section-subtitle">
                    Examines approval patterns across different financing purposes to detect preferential treatment.
                </p>
            </div>

            <div class="card-body-custom">
                <div class="metric-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ $pur_total }}</div>
                        <div class="stat-label">Total Applicants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ count($pur_labels) }}</div>
                        <div class="stat-label">Purpose Types</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            @if ($pur_total > 0 && count($pur_values) > 0)
                                {{ number_format((array_sum($pur_values) / count($pur_values)) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div class="stat-label">Avg Approval Rate</div>
                    </div>
                </div>

                @if (!empty($pur))
                    <div class="chart-container">
                        <div class="chart-title">Approval Rates by Financing Purpose</div>
                        <div id="chart_purpose" style="height: 300px;"></div>
                    </div>

                    <div class="subsection-title">Purpose Breakdown</div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Financing Purpose</th>
                                    <th>Approval Rate</th>
                                    <th>Total Applications</th>
                                    <th>Approved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pur as $group => $row)
                                    <tr>
                                        <td><strong>{{ $group }}</strong></td>
                                        <td><span
                                                class="fw-semibold">{{ number_format($row['approval_rate'] * 100, 1) }}%</span>
                                        </td>
                                        <td>{{ $row['count'] }}</td>
                                        <td>{{ round($row['approval_rate'] * $row['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-bullseye"></i>
                        <div class="empty-state-title">No Data Available</div>
                        <p>Insufficient data to generate purpose analysis.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- =========================================================
            5. CONTRACT TYPE ANALYSIS
        ========================================================= --}}
        @php
            $con = $bias['contract_bias'] ?? [];
            $con_labels = array_keys($con);
            $con_values = array_values(array_map(fn($x) => $x['approval_rate'] ?? 0, $con));

            $con_total = array_sum(array_map(fn($x) => $x['count'] ?? 0, $con));
        @endphp

        <div class="card-custom mb-4 animate-fade-in" style="animation-delay: 0.4s;">
            <div class="card-header-custom">
                <div class="section-title">
                    <i class="fas fa-file-contract"></i>
                    Contract Type Analysis
                </div>
                <p class="section-subtitle">
                    Evaluates approval patterns across different Islamic financing contracts to ensure balanced
                    treatment.
                </p>
            </div>

            <div class="card-body-custom">
                <div class="metric-grid">
                    <div class="stat-card">
                        <div class="stat-value">{{ $con_total }}</div>
                        <div class="stat-label">Total Applicants</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ count($con_labels) }}</div>
                        <div class="stat-label">Contract Types</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">
                            @if ($con_total > 0 && count($con_values) > 0)
                                {{ number_format((array_sum($con_values) / count($con_values)) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div class="stat-label">Avg Approval Rate</div>
                    </div>
                </div>

                @if (!empty($con))
                    <div class="chart-container">
                        <div class="chart-title">Approval Rates by Contract Type</div>
                        <div id="chart_contract" style="height: 300px;"></div>
                    </div>

                    <div class="subsection-title">Contract Performance</div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Contract Type</th>
                                    <th>Approval Rate</th>
                                    <th>Total Applications</th>
                                    <th>Approved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($con as $group => $row)
                                    <tr>
                                        <td><strong>{{ $group }}</strong></td>
                                        <td><span
                                                class="fw-semibold">{{ number_format($row['approval_rate'] * 100, 1) }}%</span>
                                        </td>
                                        <td>{{ $row['count'] }}</td>
                                        <td>{{ round($row['approval_rate'] * $row['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-file-contract"></i>
                        <div class="empty-state-title">No Data Available</div>
                        <p>Insufficient data to generate contract analysis.</p>
                    </div>
                @endif
            </div>
        </div>

    @endif

    <div id="bias-data" data-biz-values='@json($biz_values)' data-biz-labels='@json($biz_labels)'
        data-ind-values='@json($ind_values)' data-ind-labels='@json($ind_labels)'
        data-pur-values='@json($pur_values)' data-pur-labels='@json($pur_labels)'
        data-con-values='@json($con_values)' data-con-labels='@json($con_labels)'>
    </div>
</div> <!-- end main container -->
@if (!$isAjax)
    </body>
    </html>
@endif

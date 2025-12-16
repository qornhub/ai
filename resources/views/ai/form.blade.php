{{-- resources/views/ai/form.blade.php --}}
@php
    $isAjax = request()->ajax();
    $result = session('result') ?? null;
@endphp

@if(!$isAjax)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Business Financing AI — Application Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: var(--bg-main);
            font-family: Inter, sans-serif;
        }

        .section-card {
            background: #ffffff;
            padding: 22px 26px;
            border-radius: 12px;
            border: 1px solid var(--sidebar-border);
            box-shadow: var(--shadow);
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
        }

        label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-control,
        .form-select {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
        }

        .btn-submit {
            background: var(--primary-color);
            color: #fff;
            border-radius: 10px;
            padding: 12px 0;
            font-weight: 600;
            transition: 0.25s ease;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
        }
    </style>

</head>
<body>

<div class="container py-5" style="max-width: 820px;">
@endif

@if($isAjax)
<div class="w-100" style="max-width: 1200px; margin: 0 auto 40px auto;">
@endif

<h3 class="fw-bold text-center mb-4" style="color: var(--text-primary);">
    AI Business Financing Application
</h3>

<form method="POST" action="{{ route('ai.predict') }}" class="js-ai-form">
    @csrf

    {{-- ============================= --}}
    {{-- 1. BUSINESS PROFILE           --}}
    {{-- ============================= --}}
    <div class="section-card">
        <div class="section-title">1. Business Profile</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label>Case</label>
                <input type="text" name="customer_name" class="form-control"
                       value="{{ old('customer_name') }}" required>
            </div>

            <div class="col-md-6">
                <label>Business Name</label>
                <input type="text" name="business_name" class="form-control"
                       value="{{ old('business_name') }}" required>
            </div>

            <div class="col-md-6">
                <label>Owner Full Name</label>
                <input type="text" name="owner_name" class="form-control"
                       value="{{ old('owner_name') }}" required>
            </div>

            <div class="col-md-6">
                <label>SSM Registration Number</label>
                <input type="text" name="business_registration_no" class="form-control"
                       value="{{ old('business_registration_no') }}" required>
            </div>

            <div class="col-md-6">
                <label>Business Type</label>
                <div style="position:relative;">
                    <select name="business_type" class="form-select" style="padding-right:40px;" required>
                        <option value="halal" {{ old('business_type')=='halal'?'selected':'' }}>Halal</option>
                        <option value="non-halal" {{ old('business_type')=='non-halal'?'selected':'' }}>Non-Halal</option>
                        <option value="mixed" {{ old('business_type')=='mixed'?'selected':'' }}>Mixed</option>
                    </select>
                    <i class="fas fa-chevron-down"
                       style="position:absolute; right:14px; top:50%; transform:translateY(-50%);
                              pointer-events:none; color:#6b7280; font-size:14px;"></i>
                </div>
            </div>

            <div class="col-md-6">
                <label>Industry Category</label>
                <div style="position:relative;">
                    <select name="industry_category" class="form-select" style="padding-right:40px;" required>
                        <option value="F&B" {{ old('industry_category')=='F&B'?'selected':'' }}>Food & Beverage</option>
                        <option value="retail" {{ old('industry_category')=='retail'?'selected':'' }}>Retail</option>
                        <option value="transport" {{ old('industry_category')=='transport'?'selected':'' }}>Transport</option>
                        <option value="manufacturing" {{ old('industry_category')=='manufacturing'?'selected':'' }}>Manufacturing</option>
                        <option value="services" {{ old('industry_category')=='services'?'selected':'' }}>Services</option>
                    </select>
                    <i class="fas fa-chevron-down"
                       style="position:absolute; right:14px; top:50%; transform:translateY(-50%);
                              pointer-events:none; color:#6b7280; font-size:14px;"></i>
                </div>
            </div>

            <div class="col-md-6">
                <label>Years in Business</label>
                <input type="number" name="years_in_business"
                       min="0" max="50"
                       class="form-control"
                       value="{{ old('years_in_business') }}" required>
            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{-- 2. FINANCIAL DATA            --}}
    {{-- ============================= --}}
    <div class="section-card">
        <div class="section-title">2. Financial Data</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label>Annual Revenue (RM)</label>
                <input type="number" name="annual_revenue" class="form-control"
                       min="0" max="50000000" step="0.01"
                       value="{{ old('annual_revenue') }}" required>
            </div>

            <div class="col-md-6">
                <label>Net Profit (RM)</label>
                <input type="number" name="net_profit" class="form-control"
                       min="-10000000" max="10000000" step="0.01"
                       value="{{ old('net_profit') }}" required>
            </div>

            <div class="col-md-6">
                <label>Monthly Cashflow (RM)</label>
                <input type="number" name="monthly_cashflow" class="form-control"
                       min="0" max="5000000" step="0.01"
                       value="{{ old('monthly_cashflow') }}" required>
            </div>

            <div class="col-md-6">
                <label>Existing Liabilities (RM)</label>
                <input type="number" name="existing_liabilities" class="form-control"
                       min="0" max="20000000" step="0.01"
                       value="{{ old('existing_liabilities') }}" required>
            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{-- 3. FINANCING INFORMATION     --}}
    {{-- ============================= --}}
    <div class="section-card">
        <div class="section-title">3. Financing Information</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label>Financing Amount (RM)</label>
                <input type="number" name="financing_amount" class="form-control"
                       min="0" max="5000000" step="0.01"
                       value="{{ old('financing_amount') }}" required>
            </div>

            <div class="col-md-6">
                <label>Profit Rate (%)</label>
                <input type="number" step="0.01" name="profit_rate"
                       min="0" max="30"
                       class="form-control"
                       value="{{ old('profit_rate') }}" required>
            </div>

            <div class="col-md-6">
                <label>Tenure (Months)</label>
                <input type="number" name="tenure_months" class="form-control"
                       min="1" max="120"
                       value="{{ old('tenure_months') }}" required>
            </div>

            <div class="col-md-6">
                <label>Financing Purpose</label>
                <div style="position:relative;">
                    <select name="financing_purpose" class="form-select" style="padding-right:40px;" required>
                        <option value="working_capital" {{ old('financing_purpose')=='working_capital'?'selected':'' }}>Working Capital</option>
                        <option value="equipment" {{ old('financing_purpose')=='equipment'?'selected':'' }}>Equipment</option>
                        <option value="renovation" {{ old('financing_purpose')=='renovation'?'selected':'' }}>Renovation</option>
                        <option value="expansion" {{ old('financing_purpose')=='expansion'?'selected':'' }}>Expansion</option>
                        <option value="others" {{ old('financing_purpose')=='others'?'selected':'' }}>Others</option>
                    </select>
                    <i class="fas fa-chevron-down"
                       style="position:absolute; right:14px; top:50%; transform:translateY(-50%);
                              pointer-events:none; color:#6b7280; font-size:14px;"></i>
                </div>
            </div>

            <div class="col-md-12">
                <label>Islamic Contract Type</label>
                <div style="position:relative;">
                    <select name="contract_type" class="form-select" style="padding-right:40px;" required>
                        <option value="Murabahah" {{ old('contract_type')=='Murabahah'?'selected':'' }}>Murabahah</option>
                        <option value="Ijarah" {{ old('contract_type')=='Ijarah'?'selected':'' }}>Ijarah</option>
                        <option value="Musharakah" {{ old('contract_type')=='Musharakah'?'selected':'' }}>Musharakah</option>
                        <option value="Tawarruq" {{ old('contract_type')=='Tawarruq'?'selected':'' }}>Tawarruq</option>
                    </select>
                    <i class="fas fa-chevron-down"
                       style="position:absolute; right:14px; top:50%; transform:translateY(-50%);
                              pointer-events:none; color:#6b7280; font-size:14px;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{-- 4. CREDIT & RISK INDICATORS  --}}
    {{-- ============================= --}}
    <div class="section-card">
        <div class="section-title">4. Credit & Risk Indicators</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label>Credit Score (300–900)</label>
                <input type="number" name="credit_score" class="form-control"
                       min="300" max="900"
                       value="{{ old('credit_score') }}" required>
            </div>

            <div class="col-md-6">
                <label>Past Default?</label>
                <div style="position:relative;">
                    <select name="past_default" class="form-select" style="padding-right:40px;" required>
                        <option value="0" {{ old('past_default')=='0'?'selected':'' }}>No</option>
                        <option value="1" {{ old('past_default')=='1'?'selected':'' }}>Yes</option>
                    </select>
                    <i class="fas fa-chevron-down"
                       style="position:absolute; right:14px; top:50%; transform:translateY(-50%);
                              pointer-events:none; color:#6b7280; font-size:14px;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{-- 5. COLLATERAL                --}}
    {{-- ============================= --}}
    <div class="section-card">
        <div class="section-title">5. Collateral</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label>Collateral Type</label>
                <div style="position:relative;">
                    <select name="collateral_type" class="form-select" style="padding-right:40px;" required>
                        <option value="none" {{ old('collateral_type')=='none'?'selected':'' }}>None</option>
                        <option value="property" {{ old('collateral_type')=='property'?'selected':'' }}>Property</option>
                        <option value="vehicle" {{ old('collateral_type')=='vehicle'?'selected':'' }}>Vehicle</option>
                        <option value="inventory" {{ old('collateral_type')=='inventory'?'selected':'' }}>Inventory</option>
                        <option value="cash" {{ old('collateral_type')=='cash'?'selected':'' }}>Cash</option>
                    </select>
                    <i class="fas fa-chevron-down"
                       style="position:absolute; right:14px; top:50%; transform:translateY(-50%);
                              pointer-events:none; color:#6b7280; font-size:14px;"></i>
                </div>
            </div>

            <div class="col-md-6">
                <label>Collateral Value (RM)</label>
                <input type="number" name="collateral_value" class="form-control"
                       min="0" max="20000000" step="0.01"
                       value="{{ old('collateral_value') }}" required>
            </div>
        </div>
    </div>

    {{-- SUBMIT --}}
    <button type="submit" class="btn btn-submit w-100 mt-3 js-submit-form">
        Predict Approval
    </button>
</form>

{{-- ===================== --}}
{{-- VALIDATION ERRORS    --}}
{{-- ===================== --}}
@if ($errors->any())
    <div class="alert alert-danger mt-4">
        <strong>Please correct the following errors:</strong>
        <ul class="mt-2 mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($isAjax)
</div>
@endif

@if(!$isAjax)
</div>
</body>
</html>
@endif

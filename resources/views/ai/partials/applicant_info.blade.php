{{-- resources/views/ai/partials/applicant_info.blade.php --}}

<style>
    .info-table th {
        width: 40%;
        font-weight: 600;
        background: #f1f3f5;
    }
</style>

<table class="table table-bordered info-table">
    <tbody>

        <tr>
            <th>Business Name</th>
            <td>{{ $applicant->business_name }}</td>
        </tr>

        <tr>
            <th>SSM Registration No.</th>
            <td>{{ $applicant->business_registration_no }}</td>
        </tr>

        <tr>
            <th>Owner Name</th>
            <td>{{ $applicant->owner_name }}</td>
        </tr>

        <tr>
            <th>Business Type</th>
            <td>{{ $applicant->business_type }}</td>
        </tr>

        <tr>
            <th>Industry Category</th>
            <td>{{ $applicant->industry_category }}</td>
        </tr>

        <tr>
            <th>Financing Purpose</th>
            <td>{{ $applicant->financing_purpose }}</td>
        </tr>

        <tr>
            <th>Islamic Contract Type</th>
            <td>{{ $applicant->contract_type }}</td>
        </tr>

        <tr>
            <th>Years in Business</th>
            <td>{{ $applicant->years_in_business }}</td>
        </tr>

        <tr>
            <th>Credit Score</th>
            <td>{{ $applicant->credit_score }}</td>
        </tr>

        <tr>
            <th>Past Default</th>
            <td>{{ $applicant->past_default ? 'Yes' : 'No' }}</td>
        </tr>

        <tr>
            <th>Annual Revenue</th>
            <td>RM {{ number_format($applicant->annual_revenue, 2) }}</td>
        </tr>

        <tr>
            <th>Net Profit</th>
            <td>RM {{ number_format($applicant->net_profit, 2) }}</td>
        </tr>

        <tr>
            <th>Monthly Cashflow</th>
            <td>RM {{ number_format($applicant->monthly_cashflow, 2) }}</td>
        </tr>

        <tr>
            <th>Existing Liabilities</th>
            <td>RM {{ number_format($applicant->existing_liabilities, 2) }}</td>
        </tr>

        <tr>
            <th>Financing Amount</th>
            <td>RM {{ number_format($applicant->financing_amount, 2) }}</td>
        </tr>

        <tr>
            <th>Profit Rate</th>
            <td>{{ $applicant->profit_rate }}%</td>
        </tr>

        <tr>
            <th>Tenure (Months)</th>
            <td>{{ $applicant->tenure_months }}</td>
        </tr>

        <tr>
            <th>Collateral Type</th>
            <td>{{ $applicant->collateral_type }}</td>
        </tr>

        <tr>
            <th>Collateral Value</th>
            <td>RM {{ number_format($applicant->collateral_value, 2) }}</td>
        </tr>

    </tbody>
</table>

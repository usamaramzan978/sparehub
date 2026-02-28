<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Reports Summary</title>
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                color: #111827;
                margin: 28px;
            }
            h1 {
                margin: 0 0 4px 0;
                font-size: 18px;
            }
            .meta {
                color: #4b5563;
                margin-bottom: 12px;
            }
            table.summary,
            table.report {
                width: 100%;
                border-collapse: collapse;
            }
            table.summary td,
            table.report th,
            table.report td {
                border: 1px solid #d1d5db;
                padding: 8px;
            }
            table.summary {
                margin-bottom: 16px;
            }
            .k-title {
                color: #4b5563;
                font-size: 10px;
                margin-bottom: 2px;
            }
            .k-value {
                font-size: 14px;
                font-weight: 700;
            }
            h2 {
                font-size: 13px;
                margin: 14px 0 8px;
            }
            table.report th {
                background: #f3f4f6;
                text-align: left;
            }
            .text-right {
                text-align: right;
            }
            .muted {
                color: #6b7280;
            }
        </style>
    </head>
    <body>
        <h1>Reports Summary</h1>
        <div class="meta">Generated: @tenantDate($generatedAt, 'Y-m-d H:i')</div>
        @if ($filters['date_from'] !== '' || $filters['date_to'] !== '')
            <div class="meta">
                Period:
                {{ $filters['date_from'] !== '' ? $filters['date_from'] : 'Start' }}
                -
                {{ $filters['date_to'] !== '' ? $filters['date_to'] : 'End' }}
            </div>
        @endif

        <table class="summary">
            <tr>
                <td><div class="k-title">Sales Total</div><div class="k-value">{{ number_format($summary['sales_total'], 2) }}</div><div class="muted">{{ $summary['sales_count'] }} invoices</div></td>
                <td><div class="k-title">Purchases Total</div><div class="k-value">{{ number_format($summary['purchases_total'], 2) }}</div><div class="muted">{{ $summary['purchases_count'] }} orders</div></td>
                <td><div class="k-title">Sale Payments</div><div class="k-value">{{ number_format($summary['sale_payments_total'], 2) }}</div></td>
            </tr>
            <tr>
                <td><div class="k-title">Vendor Payments</div><div class="k-value">{{ number_format($summary['vendor_payments_total'], 2) }}</div></td>
                <td><div class="k-title">Receivables</div><div class="k-value">{{ number_format($summary['receivables_total'], 2) }}</div></td>
                <td><div class="k-title">Payables</div><div class="k-value">{{ number_format($summary['payables_total'], 2) }}</div></td>
            </tr>
        </table>

        <h2>Receivables</h2>
        <table class="report">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receivables as $sale)
                    <tr>
                        <td>{{ $sale->invoice_no }}</td>
                        <td>{{ $sale->customer?->name ?? '-' }}</td>
                        <td>@tenantDate($sale->invoice_date, 'Y-m-d', '')</td>
                        <td class="text-right">{{ number_format((float) $sale->balance_due, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No receivables found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Payables</h2>
        <table class="report">
            <thead>
                <tr>
                    <th>Purchase</th>
                    <th>Vendor</th>
                    <th>Date</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payables as $purchase)
                    <tr>
                        <td>{{ $purchase->purchase_no }}</td>
                        <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                        <td>@tenantDate($purchase->purchase_date, 'Y-m-d', '')</td>
                        <td class="text-right">{{ number_format((float) $purchase->outstanding_balance, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No payables found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>

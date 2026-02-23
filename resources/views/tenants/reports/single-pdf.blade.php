<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $reportTitle }}</title>
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                color: #111827;
                margin: 24px;
            }
            .header {
                margin-bottom: 14px;
            }
            .header h1 {
                margin: 0 0 4px 0;
                font-size: 18px;
            }
            .meta {
                color: #4b5563;
            }
            table.report {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 12px;
            }
            table.report th,
            table.report td {
                border: 1px solid #d1d5db;
                padding: 6px;
                text-align: left;
            }
            table.report th {
                background: #f3f4f6;
                font-weight: 700;
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
        <div class="header">
            <h1>{{ $reportTitle }}</h1>
            <div class="meta">Generated: @tenantDate($generatedAt, 'Y-m-d H:i')</div>
            @if ($filters['date_from'] !== '' || $filters['date_to'] !== '')
                <div class="meta">
                    Period:
                    {{ $filters['date_from'] !== '' ? $filters['date_from'] : 'Start' }}
                    -
                    {{ $filters['date_to'] !== '' ? $filters['date_to'] : 'End' }}
                </div>
            @endif
        </div>

        @if ($reportKey === 'sales')
            <table class="report">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-right">Grand Total</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>{{ $sale->invoice_no }}</td>
                            <td>@tenantDate($sale->invoice_date, 'Y-m-d', '')</td>
                            <td>{{ $sale->customer?->name ?? '-' }}</td>
                            <td>{{ ucfirst($sale->invoice_type->value) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $sale->status->value)) }}</td>
                            <td class="text-right">{{ number_format((float) $sale->grand_total, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $sale->balance_due, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">No sales found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if ($reportKey === 'purchases')
            <table class="report">
                <thead>
                    <tr>
                        <th>Purchase</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Status</th>
                        <th class="text-right">Grand Total</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->purchase_no }}</td>
                            <td>@tenantDate($purchase->purchase_date, 'Y-m-d', '')</td>
                            <td>{{ $purchase->vendor?->name ?? '-' }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $purchase->status->value)) }}</td>
                            <td class="text-right">{{ number_format((float) $purchase->grand_total, 2) }}</td>
                            <td class="text-right">{{ number_format((float) $purchase->balance_due, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">No purchases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if ($reportKey === 'sale-payments')
            <table class="report">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th>Paid At</th>
                        <th>Reference</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salePayments as $payment)
                        <tr>
                            <td>{{ $payment->sale?->invoice_no ?? '-' }}</td>
                            <td>{{ ucfirst($payment->payment_method->value) }}</td>
                            <td>@tenantDate($payment->paid_at, 'Y-m-d H:i')</td>
                            <td>{{ $payment->reference_no ?: '-' }}</td>
                            <td class="text-right">{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No sale payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if ($reportKey === 'vendor-payments')
            <table class="report">
                <thead>
                    <tr>
                        <th>Payment No</th>
                        <th>Vendor</th>
                        <th>Method</th>
                        <th>Paid At</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendorPayments as $payment)
                        <tr>
                            <td>{{ $payment->payment_no }}</td>
                            <td>{{ $payment->vendor?->name ?? '-' }}</td>
                            <td>{{ ucfirst($payment->payment_method->value) }}</td>
                            <td>@tenantDate($payment->paid_at, 'Y-m-d H:i')</td>
                            <td class="text-right">{{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No vendor payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        @if ($reportKey === 'receivables')
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
        @endif

        @if ($reportKey === 'payables')
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
                            <td class="text-right">{{ number_format((float) $purchase->balance_due, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No payables found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </body>
</html>

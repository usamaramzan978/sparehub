@extends('layouts.print')

@section('content')
    <style>
        body {
            background: #fff;
            padding: 12px;
            font-size: 12px;
        }
        .receipt {
            max-width: 80mm;
            margin: 0 auto;
            font-family: "Courier New", monospace;
        }
        .receipt h1,
        .receipt h2,
        .receipt h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
        }
        .receipt .line {
            border-top: 1px dashed #333;
            margin: 8px 0;
        }
        .receipt .row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: flex-start;
        }
        .receipt .muted {
            color: #555;
        }
        .receipt .items .name {
            flex: 1 1 auto;
            min-width: 0;
            word-break: break-word;
            white-space: normal;
        }
        .receipt .items-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 44px 60px 60px;
            gap: 6px 8px;
            align-items: start;
        }
        .receipt .items-grid .num {
            text-align: right;
            white-space: nowrap;
        }
        .receipt .total {
            font-weight: 700;
        }
        @media print {
            body {
                padding: 0;
            }
            .toolbar {
                display: none;
            }
        }
    </style>

    <div class="toolbar">
        <div>
            <strong>Receipt</strong>
            <div class="label-meta">Invoice {{ $sale->invoice_no }}</div>
        </div>
        <div>
            <a href="javascript:window.print();" class="btn">Print</a>
            <a href="{{ route('tenant.sales.show', $sale) }}" class="btn btn-outline">Back</a>
        </div>
    </div>

    <div class="receipt">
        <div class="row">
            <span>{{ $sale->branch?->name ?? 'Branch' }}</span>
            <span>{{ $sale->created_at->format('Y-m-d H:i') }}</span>
        </div>
        <div class="row muted">
            <span>Invoice</span>
            <span>{{ $sale->invoice_no }}</span>
        </div>
        <div class="row muted">
            <span>Customer</span>
            <span>{{ $sale->customer?->name ?? 'Walk-in' }}</span>
        </div>

        <div class="line"></div>

        <div class="items items-grid">
            <div class="muted" style="font-weight:700;">Product Name</div>
            <div class="muted num" style="font-weight:700;">Qty</div>
            <div class="muted num" style="font-weight:700;">Price</div>
            <div class="muted num" style="font-weight:700;">Total</div>
            @foreach ($sale->items as $item)
                <div class="name">
                    {{ $item->description ?: ($item->product?->name ?? $item->serviceCatalog?->name ?? 'Item') }}
                </div>
                <div class="num">{{ rtrim(rtrim(number_format((float) $item->qty, 3, '.', ''), '0'), '.') }}</div>
                <div class="num">{{ number_format((float) $item->unit_price, 2) }}</div>
                <div class="num">{{ number_format((float) $item->line_total, 2) }}</div>
            @endforeach
        </div>

        <div class="line"></div>

        <div class="row">
            <span>Subtotal</span>
            <span>{{ number_format((float) $sale->sub_total, 2) }}</span>
        </div>
        <div class="row">
            <span>Discount</span>
            <span>{{ number_format((float) $sale->discount_total, 2) }}</span>
        </div>
        <div class="row">
            <span>Tax</span>
            <span>{{ number_format((float) $sale->tax_total, 2) }}</span>
        </div>
        <div class="row total">
            <span>Total</span>
            <span>{{ number_format((float) $sale->grand_total, 2) }}</span>
        </div>

        <div class="line"></div>

        <div class="row">
            <span>Payment</span>
            <span></span>
        </div>
        @if ($sale->payments->isNotEmpty())
            @foreach ($sale->payments as $payment)
                <div class="row muted">
                    <span>{{ ucfirst($payment->payment_method->value) }}</span>
                    <span>{{ number_format((float) $payment->amount, 2) }}</span>
                </div>
            @endforeach
        @else
            <div class="row muted">
                <span>No payments recorded.</span>
                <span></span>
            </div>
        @endif
        @if (($sale->balance_due ?? 0) > 0)
            <div class="row">
                <span>Balance Due</span>
                <span>{{ number_format((float) $sale->balance_due, 2) }}</span>
            </div>
        @endif

        <div class="line"></div>
        <div class="row muted">
            <span>Thank you!</span>
            <span></span>
        </div>
    </div>

    @if (request()->boolean('auto_print'))
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
@endsection

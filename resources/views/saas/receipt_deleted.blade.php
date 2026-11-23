<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voided Receipt #REC-{{ $sale->id }} - {{ $sale->tenant->name ?? 'Retail Store' }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, monospace;
            background: #f3f4f6;
            color: #111827;
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .actions-bar {
            width: 100%;
            max-width: 480px;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.65rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-print {
            background: #10b981;
            color: white;
        }
        .btn-back {
            background: white;
            color: #374151;
            border-color: #d1d5db;
        }
        .receipt-card {
            width: 100%;
            max-width: 480px;
            background: white;
            padding: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            border: 2px dashed #ef4444;
            position: relative;
        }
        .void-watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 4rem;
            font-weight: 900;
            color: rgba(239, 68, 68, 0.13);
            border: 6px dashed rgba(239, 68, 68, 0.2);
            padding: 0.5rem 2rem;
            pointer-events: none;
            user-select: none;
            text-transform: uppercase;
            letter-spacing: 4px;
            white-space: nowrap;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .store-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: #111827;
        }
        .store-info {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        .void-banner {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 0.6rem;
            border-radius: 6px;
            text-align: center;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            margin-bottom: 1.25rem;
        }
        .audit-box {
            background: #fef2f2;
            border: 1px dashed #f87171;
            border-radius: 6px;
            padding: 0.85rem;
            margin-bottom: 1.25rem;
            font-size: 0.83rem;
            line-height: 1.5;
        }
        .audit-title {
            font-weight: 700;
            color: #b91c1c;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.78rem;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1rem;
        }
        .meta-label {
            color: #6b7280;
            font-size: 0.78rem;
        }
        .meta-val {
            font-weight: 600;
            color: #1f2937;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.5rem 0.25rem;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: #6b7280;
        }
        .items-table td {
            padding: 0.5rem 0.25rem;
            border-bottom: 1px dashed #f3f4f6;
        }
        .totals-table {
            width: 100%;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        .totals-table td {
            padding: 0.35rem 0;
        }
        .totals-table .grand-total {
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            font-size: 1.15rem;
            font-weight: 800;
            padding: 0.6rem 0;
        }
        .footer {
            text-align: center;
            font-size: 0.78rem;
            color: #9ca3af;
            border-top: 1px dashed #e5e7eb;
            padding-top: 1rem;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .actions-bar {
                display: none;
            }
            .receipt-card {
                box-shadow: none;
                max-width: 100%;
                border: 1px solid #000;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="javascript:history.back()" class="btn btn-back">← Back to SaaS Dashboard</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ Print Receipt</button>
    </div>

    <div class="receipt-card">
        <div class="void-watermark">VOIDED</div>

        <div class="void-banner">
            ⚠️ VOIDED / SOFT-DELETED TRANSACTION
        </div>

        <div class="header">
            <h1 class="store-name">{{ $sale->tenant->business_name ?? $sale->tenant->name ?? 'Retail Store' }}</h1>
            <div class="store-info">
                {{ $sale->tenant->location ?? 'Headquarters' }} &bull; {{ $sale->tenant->phone ?? '' }}
            </div>
            <div style="font-weight: 700; margin-top: 0.4rem; color: #374151;">
                RECEIPT #REC-{{ $sale->id }}
            </div>
        </div>

        <!-- Audit Trail & Deletion Reason -->
        <div class="audit-box">
            <div class="audit-title">Audit Log & Deletion Record</div>
            <div><strong>Deleted By:</strong> {{ $sale->deletedByUser->name ?? 'Store Administrator' }} ({{ $sale->deletedByUser->email ?? 'Admin' }})</div>
            <div><strong>Deleted At:</strong> {{ $sale->deleted_at->format('Y-m-d H:i:s') }}</div>
            <div><strong>Audit Reason:</strong> <em>"{{ $sale->deletion_reason ?? 'No reason supplied' }}"</em></div>
            <div style="margin-top: 0.4rem; font-weight: 700; color: #b91c1c;">
                Audit Retention Validity: 1 Year (Valid until {{ $sale->deleted_at->addYear()->format('Y-m-d H:i:s') }})
            </div>
        </div>

        <!-- Metadata -->
        <div class="meta-grid">
            <div>
                <div class="meta-label">Original Date & Time</div>
                <div class="meta-val">{{ $sale->created_at->format('Y-m-d H:i:s') }}</div>
            </div>
            <div>
                <div class="meta-label">Original Cashier</div>
                <div class="meta-val">{{ $sale->user->name ?? 'Cashier' }}</div>
            </div>
            <div>
                <div class="meta-label">Customer</div>
                <div class="meta-val">{{ $sale->customer_name ?: 'Walk-in Customer' }}</div>
            </div>
            <div>
                <div class="meta-label">Payment Method</div>
                <div class="meta-val" style="text-transform: uppercase;">{{ $sale->payment_method }}</div>
            </div>
            @if($sale->branch)
            <div style="grid-column: span 2;">
                <div class="meta-label">Branch</div>
                <div class="meta-val">{{ $sale->branch->name }} ({{ $sale->branch->code ?? 'MAIN' }})</div>
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->product->name ?? 'Item #' . $item->product_id }}</strong>
                            @if($item->product && $item->product->sku)
                                <div style="font-size: 0.75rem; color: #6b7280;">SKU: {{ $item->product->sku }}</div>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">{{ $sale->currency_code }} {{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ $sale->currency_code }} {{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Table -->
        <table class="totals-table">
            <tr>
                <td style="color: #6b7280;">Subtotal:</td>
                <td style="text-align: right; font-weight: 600;">{{ $sale->currency_code }} {{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->discount > 0)
            <tr>
                <td style="color: #6b7280;">Discount:</td>
                <td style="text-align: right; color: #ef4444;">- {{ $sale->currency_code }} {{ number_format($sale->discount, 2) }}</td>
            </tr>
            @endif
            @if($sale->tax > 0)
            <tr>
                <td style="color: #6b7280;">Tax:</td>
                <td style="text-align: right;">+ {{ $sale->currency_code }} {{ number_format($sale->tax, 2) }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <td>Total Transaction:</td>
                <td style="text-align: right;">{{ $sale->currency_code }} {{ number_format($sale->total, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #6b7280; padding-top: 0.4rem;">Amount Tendered:</td>
                <td style="text-align: right; padding-top: 0.4rem;">{{ $sale->currency_code }} {{ number_format($sale->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td style="color: #6b7280;">Change Returned:</td>
                <td style="text-align: right;">{{ $sale->currency_code }} {{ number_format($sale->change_amount, 2) }}</td>
            </tr>
        </table>

        <div class="footer">
            <p>*** AUDIT LOG REPRODUCED COPY ***</p>
            <p style="margin-top: 0.25rem;">This transaction has been removed from active revenue balances.</p>
            <p style="margin-top: 0.25rem;">Printed on {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $displayName }} - Customer Display</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3efe6;
            --panel: rgba(255, 255, 255, 0.88);
            --ink: #17212b;
            --muted: #5d6a77;
            --accent: #0f766e;
            --accent-soft: #ccfbf1;
            --line: rgba(23, 33, 43, 0.1);
            --danger: #b91c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.18), transparent 32%),
                radial-gradient(circle at bottom right, rgba(234, 179, 8, 0.16), transparent 30%),
                linear-gradient(135deg, #fcfbf7 0%, var(--bg) 100%);
            padding: 24px;
        }

        .screen {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(340px, 0.9fr);
            gap: 24px;
            min-height: calc(100vh - 48px);
        }

        .panel {
            background: var(--panel);
            backdrop-filter: blur(18px);
            border: 1px solid var(--line);
            border-radius: 28px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
        }

        .items-panel {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .hero {
            padding: 28px 32px 20px;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .hero h1,
        .summary-total strong,
        .empty-state strong {
            margin: 0;
        }

        .hero h1 {
            font-size: clamp(1.8rem, 2.5vw, 2.8rem);
            line-height: 1.1;
        }

        .hero p,
        .summary-grid span,
        .item-meta,
        .badge,
        .empty-state p,
        .footnote {
            margin: 0;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 10px 16px;
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .items-head,
        .item-row {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) 110px 110px 130px;
            gap: 16px;
            align-items: center;
        }

        .items-head {
            padding: 18px 32px 12px;
            font-size: 0.82rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .items-list {
            padding: 0 20px 20px 32px;
            overflow: auto;
            flex: 1;
        }

        .item-row {
            padding: 18px 12px 18px 0;
            border-top: 1px solid var(--line);
        }

        .item-name {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .item-meta {
            font-size: 0.92rem;
            margin-top: 4px;
        }

        .num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 700;
        }

        .summary-panel {
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .summary-top {
            display: grid;
            gap: 10px;
        }

        .summary-top h2,
        .summary-grid strong,
        .summary-note strong {
            margin: 0;
        }

        .summary-grid {
            display: grid;
            gap: 14px;
        }

        .summary-card {
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.72);
        }

        .summary-card strong {
            display: block;
            font-size: 1.5rem;
            margin-top: 6px;
            font-variant-numeric: tabular-nums;
        }

        .summary-total {
            padding: 24px;
            border-radius: 24px;
            background: linear-gradient(135deg, #115e59 0%, #0f766e 100%);
            color: #fff;
        }

        .summary-total span,
        .summary-total small {
            color: rgba(255, 255, 255, 0.82);
        }

        .summary-total strong {
            display: block;
            font-size: clamp(2.8rem, 5vw, 4.3rem);
            line-height: 1;
            margin: 12px 0;
            font-variant-numeric: tabular-nums;
        }

        .summary-note {
            border-radius: 22px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.68);
            border: 1px solid var(--line);
        }

        .summary-note.danger strong,
        .summary-note.danger span {
            color: var(--danger);
        }

        .empty-state {
            margin: auto;
            padding: 32px;
            text-align: center;
            max-width: 520px;
        }

        .empty-state strong {
            display: block;
            font-size: clamp(2rem, 3vw, 3rem);
            line-height: 1.1;
        }

        .empty-state p {
            font-size: 1.05rem;
            margin-top: 12px;
        }

        @media (max-width: 1100px) {
            .screen {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            body {
                padding: 12px;
            }

            .hero,
            .items-head,
            .items-list,
            .summary-panel {
                padding-left: 18px;
                padding-right: 18px;
            }

            .items-head,
            .item-row {
                grid-template-columns: minmax(0, 1fr) 74px 90px;
            }

            .item-row .price-col,
            .items-head .price-col {
                display: none;
            }
        }
    </style>
</head>

<body data-pos-display data-sync-channel="{{ $syncChannel }}">
    <div class="screen">
        <section class="panel items-panel">
            <div class="hero">
                <div>
                    <span class="badge" data-display-mode>Ready</span>
                    <h1>{{ $displayName }}</h1>
                    <p>{{ $branch->name }} · Customer Display</p>
                </div>
                <div class="footnote" data-display-updated>Waiting for cashier screen</div>
            </div>

            <div class="items-head">
                <div>Item</div>
                <div class="num">Qty</div>
                <div class="num price-col">Price</div>
                <div class="num">Total</div>
            </div>

            <div class="items-list" data-display-items>
                <div class="empty-state" data-display-empty>
                    <strong>Waiting for next sale</strong>
                    <p>Items and totals will appear here as the cashier updates the cart.</p>
                </div>
            </div>
        </section>

        <aside class="panel summary-panel">
            <div class="summary-top">
                <h2>Current Bill</h2>
                <span class="footnote" data-display-status>Cart is empty</span>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <span>Subtotal</span>
                    <strong data-display-subtotal>0.00</strong>
                </div>
                <div class="summary-card">
                    <span>Discount</span>
                    <strong data-display-discount>0.00</strong>
                </div>
                <div class="summary-card">
                    <span>Tax</span>
                    <strong data-display-tax>0.00</strong>
                </div>
            </div>

            <div class="summary-total">
                <span>Total Payable</span>
                <strong data-display-total>0.00</strong>
                <small data-display-payment-mode>Payment mode: cash</small>
            </div>

            <div class="summary-card">
                <span>Paid</span>
                <strong data-display-paid>0.00</strong>
            </div>

            <div class="summary-note d-none" data-display-change-note>
                <span>Change Due</span>
                <strong data-display-change>0.00</strong>
            </div>

            <div class="summary-note d-none" data-display-balance-note>
                <span>Balance Due</span>
                <strong data-display-balance>0.00</strong>
            </div>
        </aside>
    </div>

    <script>
        (function() {
            const root = document.querySelector('[data-pos-display]');
            const syncChannel = root?.dataset.syncChannel || '';
            const itemsContainer = document.querySelector('[data-display-items]');
            const emptyState = document.querySelector('[data-display-empty]');
            const modeBadge = document.querySelector('[data-display-mode]');
            const updatedAt = document.querySelector('[data-display-updated]');
            const statusLabel = document.querySelector('[data-display-status]');
            const subtotalEl = document.querySelector('[data-display-subtotal]');
            const discountEl = document.querySelector('[data-display-discount]');
            const taxEl = document.querySelector('[data-display-tax]');
            const totalEl = document.querySelector('[data-display-total]');
            const paidEl = document.querySelector('[data-display-paid]');
            const changeEl = document.querySelector('[data-display-change]');
            const balanceEl = document.querySelector('[data-display-balance]');
            const changeNote = document.querySelector('[data-display-change-note]');
            const balanceNote = document.querySelector('[data-display-balance-note]');
            const paymentModeEl = document.querySelector('[data-display-payment-mode]');

            const money = (value) => {
                const amount = Number.parseFloat(String(value ?? 0));
                return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
            };

            const formatMode = (value) => {
                const labels = {
                    cash: 'Cash',
                    online: 'Online',
                    debit: 'Loan / Partial',
                };

                return labels[value] || 'Cash';
            };

            const formatStatus = (value) => {
                const labels = {
                    posted: 'Ready to checkout',
                    draft: 'Sale on hold',
                    hold: 'Sale on hold',
                };

                return labels[value] || 'Ready';
            };

            const renderEmpty = () => {
                if (itemsContainer && emptyState && !itemsContainer.contains(emptyState)) {
                    itemsContainer.innerHTML = '';
                    itemsContainer.appendChild(emptyState);
                }

                if (modeBadge) {
                    modeBadge.textContent = 'Ready';
                }

                if (statusLabel) {
                    statusLabel.textContent = 'Cart is empty';
                }

                if (paymentModeEl) {
                    paymentModeEl.textContent = 'Payment mode: cash';
                }

                [subtotalEl, discountEl, taxEl, totalEl, paidEl, changeEl, balanceEl].forEach((element) => {
                    if (element) {
                        element.textContent = '0.00';
                    }
                });

                changeNote?.classList.add('d-none');
                balanceNote?.classList.add('d-none');
            };

            const renderItems = (items) => {
                if (!itemsContainer) {
                    return;
                }

                if (!Array.isArray(items) || items.length === 0) {
                    renderEmpty();
                    return;
                }

                itemsContainer.innerHTML = '';
                items.forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'item-row';
                    row.innerHTML = `
                        <div>
                            <div class="item-name">${item.name || 'Item'}</div>
                            <div class="item-meta">${item.type === 'service' ? 'Service' : 'Product'}${item.sku ? ` · ${item.sku}` : ''}</div>
                        </div>
                        <div class="num">${money(item.qty).replace('.00', '')}</div>
                        <div class="num price-col">${money(item.price)}</div>
                        <div class="num">${money(item.total)}</div>
                    `;
                    itemsContainer.appendChild(row);
                });
            };

            const renderSnapshot = (snapshot) => {
                if (!snapshot || typeof snapshot !== 'object') {
                    renderEmpty();
                    return;
                }

                renderItems(snapshot.items);

                if (modeBadge) {
                    modeBadge.textContent = snapshot.items?.length ? 'Live' : 'Ready';
                }

                if (updatedAt) {
                    updatedAt.textContent = snapshot.updated_at || 'Waiting for cashier screen';
                }

                if (statusLabel) {
                    statusLabel.textContent = formatStatus(snapshot.status);
                }

                if (subtotalEl) {
                    subtotalEl.textContent = money(snapshot.subtotal);
                }

                if (discountEl) {
                    discountEl.textContent = money(snapshot.discount);
                }

                if (taxEl) {
                    taxEl.textContent = money(snapshot.tax);
                }

                if (totalEl) {
                    totalEl.textContent = money(snapshot.total);
                }

                if (paidEl) {
                    paidEl.textContent = money(snapshot.paid);
                }

                if (changeEl) {
                    changeEl.textContent = money(snapshot.change_due);
                }

                if (balanceEl) {
                    balanceEl.textContent = money(snapshot.balance_due);
                }

                if (paymentModeEl) {
                    paymentModeEl.textContent = `Payment mode: ${formatMode(snapshot.payment_mode)}`;
                }

                if (Number.parseFloat(String(snapshot.change_due || 0)) > 0) {
                    changeNote?.classList.remove('d-none');
                } else {
                    changeNote?.classList.add('d-none');
                }

                if (Number.parseFloat(String(snapshot.balance_due || 0)) > 0) {
                    balanceNote?.classList.remove('d-none');
                    balanceNote?.classList.add('danger');
                } else {
                    balanceNote?.classList.add('d-none');
                    balanceNote?.classList.remove('danger');
                }
            };

            const hydrate = () => {
                if (!syncChannel) {
                    renderEmpty();
                    return;
                }

                try {
                    const snapshot = window.localStorage.getItem(syncChannel);
                    renderSnapshot(snapshot ? JSON.parse(snapshot) : null);
                } catch (error) {
                    renderEmpty();
                }
            };

            hydrate();

            if ('BroadcastChannel' in window && syncChannel) {
                const channel = new BroadcastChannel(syncChannel);
                channel.addEventListener('message', (event) => {
                    renderSnapshot(event.data);
                });
            }

            window.addEventListener('storage', (event) => {
                if (event.key !== syncChannel) {
                    return;
                }

                try {
                    renderSnapshot(event.newValue ? JSON.parse(event.newValue) : null);
                } catch (error) {
                    renderEmpty();
                }
            });
        })();
    </script>
</body>

</html>

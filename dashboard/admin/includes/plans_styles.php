<style>
    /* ── Layout ──────────────────────────────────────────── */
    .plans-grid {
        display: grid;
        grid-template-columns: 340px 1fr;
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 900px) {
        .plans-grid { grid-template-columns: 1fr; }
    }

    /* ── Plan list ───────────────────────────────────────── */
    .plan-list {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        overflow: hidden;
    }

    .plan-list-header {
        padding: 18px 20px;
        border-bottom: 1px solid var(--admin-border);
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--admin-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .plan-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid var(--admin-border);
        cursor: pointer;
        transition: background 0.15s;
        gap: 12px;
    }

    .plan-item:last-child { border-bottom: none; }
    .plan-item:hover { background: var(--admin-hover); }
    .plan-item.active { background: rgba(99,102,241,0.12); border-left: 3px solid var(--primary); }

    .plan-item-name {
        font-weight: 600;
        color: var(--admin-text);
        font-size: 0.95rem;
    }

    .plan-item-price {
        font-weight: 700;
        color: var(--primary);
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .fee-count {
        font-size: 0.75rem;
        color: var(--admin-muted);
        margin-top: 2px;
    }

    /* ── Detail panel ────────────────────────────────────── */
    .detail-panel {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        border-radius: 12px;
        overflow: hidden;
    }

    .detail-panel-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px 30px;
        color: var(--admin-muted);
        gap: 12px;
    }

    .detail-panel-empty i { font-size: 2.5rem; opacity: 0.3; }

    .detail-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--admin-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .detail-header h2 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--admin-text);
    }

    .detail-body { padding: 24px; }

    /* ── Sections ────────────────────────────────────────── */
    .section-block {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .section-block-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--admin-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
    }

    /* ── Specs ───────────────────────────────────────────── */
    .specs-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .spec-item {
        background: var(--admin-hover);
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }

    .spec-item .spec-val {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--admin-text);
    }

    .spec-item .spec-label {
        font-size: 0.72rem;
        color: var(--admin-muted);
        text-transform: uppercase;
        margin-top: 2px;
    }

    /* ── Price row ───────────────────────────────────────── */
    .price-row {
        display: grid;
        grid-template-columns: 1fr 120px auto;
        gap: 10px;
        align-items: end;
    }

    /* ── Fees list ───────────────────────────────────────── */
    .fees-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }

    .fee-row {
        display: grid;
        grid-template-columns: 1fr 90px 100px 90px 70px auto;
        gap: 8px;
        align-items: center;
        background: var(--admin-hover);
        border-radius: 8px;
        padding: 12px 14px;
    }

    .fee-row-label { font-weight: 600; font-size: 0.9rem; }

    .fee-row-type,
    .fee-row-billing {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 20px;
        text-align: center;
    }

    .type-percentage  { background: rgba(251,191,36,0.15); color: #fbbf24; }
    .type-fixed       { background: rgba(99,102,241,0.15); color: #a5b4fc; }
    .billing-setup      { background: rgba(249,115,22,0.15); color: #fb923c; }
    .billing-recurring  { background: rgba(34,197,94,0.15);  color: #4ade80; }
    .billing-short-term { background: rgba(243,156,18,0.15); color: #f39c12; }

    .fee-row-value { font-weight: 700; color: var(--admin-text); font-size: 0.9rem; }
    .fee-row-currency { color: var(--admin-muted); font-size: 0.8rem; }

    .fee-actions { display: flex; gap: 6px; }

    /* ── Icon buttons ────────────────────────────────────── */
    .btn-icon {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: all 0.15s;
    }
    .btn-icon:hover { transform: translateY(-1px); }
    .btn-icon-edit   { background: rgba(99,102,241,0.2); color: #a5b4fc; }
    .btn-icon-delete { background: rgba(239,68,68,0.15); color: #f87171; }

    /* ── Fee form ────────────────────────────────────────── */
    .add-fee-form {
        display: grid;
        grid-template-columns: 1fr 110px 110px 110px 70px auto;
        gap: 8px;
        align-items: end;
        padding-top: 14px;
        border-top: 1px solid var(--admin-border);
        margin-top: 4px;
    }

    /* ── Form controls ───────────────────────────────────── */
    .form-control {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        color: var(--admin-text);
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 0.85rem;
        width: 100%;
        box-sizing: border-box;
    }
    .form-control:focus { outline: none; border-color: var(--primary); }
    .form-control option { background: var(--admin-surface); color: var(--admin-text); }

    label.form-label {
        font-size: 0.75rem;
        color: var(--admin-muted);
        display: block;
        margin-bottom: 4px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .btn-primary-sm {
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 14px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
        transition: all 0.15s;
    }
    .btn-primary-sm:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn-primary-sm:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    .btn-outline-sm {
        background: transparent;
        color: var(--admin-muted);
        border: 1px solid var(--admin-border);
        border-radius: 6px;
        padding: 8px 14px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.15s;
    }
    .btn-outline-sm:hover { border-color: var(--primary); color: var(--admin-text); }

    /* ── Toast ───────────────────────────────────────────── */
    .toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        z-index: 9999;
        opacity: 0;
        transform: translateY(8px);
        transition: all 0.25s;
        pointer-events: none;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast-success { background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }
    .toast-error   { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; }

    /* ── Responsive: tablet (< 900px) ───────────────────── */
    @media (max-width: 900px) {
        /* plan list becomes horizontal scrollable strip */
        .plan-list-header { display: none; }

        #planListBody {
            display: flex;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 8px;
            padding: 12px;
            scrollbar-width: none;
        }
        #planListBody::-webkit-scrollbar { display: none; }

        .plan-item {
            flex-direction: column;
            align-items: flex-start;
            flex-shrink: 0;
            width: 140px;
            padding: 12px 14px;
            border-bottom: none;
            border-radius: 8px;
            border: 1px solid var(--admin-border);
            gap: 4px;
        }

        .plan-item.active {
            border-left: 1px solid var(--primary);
            border-color: var(--primary);
            background: rgba(99,102,241,0.12);
        }

        .plan-item-price { font-size: 1rem; }

        /* fee-row: hide currency column, shrink badges */
        .fee-row {
            grid-template-columns: 1fr auto auto auto auto;
        }
        .fee-row-currency { display: none; }

        /* add-fee-form: 2 cols wrapping */
        .add-fee-form {
            grid-template-columns: 1fr 1fr;
        }

        .detail-body { padding: 16px; }
    }

    /* ── Responsive: mobile (< 600px) ───────────────────── */
    @media (max-width: 600px) {
        .plans-grid { gap: 12px; }

        .plan-item { width: 120px; }

        /* price-row: stack price + currency, button below */
        .price-row {
            grid-template-columns: 1fr 90px;
            grid-template-rows: auto auto;
        }
        .price-row > div:last-child {
            grid-column: 1 / -1;
        }
        .price-row .btn-primary-sm { width: 100%; }

        /* fee-row: name + value + actions only */
        .fee-row {
            grid-template-columns: 1fr auto auto;
        }
        .fee-row-type,
        .fee-row-billing,
        .fee-row-currency { display: none; }

        /* add-fee-form: single column */
        .add-fee-form {
            grid-template-columns: 1fr;
        }
        .add-fee-form > div:last-child {
            flex-direction: row;
            gap: 8px;
        }
        .add-fee-form .btn-primary-sm,
        .add-fee-form .btn-outline-sm { width: 100%; }

        .section-block { padding: 14px; }
        .detail-body   { padding: 12px; }
    }
</style>

<style>
    .detail-card {
        background: var(--admin-card);
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .detail-header h1 {
        color: var(--admin-text);
        margin: 0;
        font-size: 1.8rem;
    }

    .header-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .user-info-section {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .info-item {
        padding: 18px;
        background: var(--admin-hover);
        border: 1px solid var(--admin-border);
        border-radius: 6px;
        border-left: 3px solid var(--primary);
    }

    .info-label {
        color: var(--admin-muted);
        font-size: 0.8rem;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        color: var(--admin-text);
        font-size: 1.1rem;
        font-weight: 600;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .data-table th,
    .data-table td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid var(--admin-border);
        color: var(--admin-text);
    }

    .data-table th {
        background: var(--admin-th-bg);
        font-weight: 600;
        color: var(--admin-muted);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }

    .data-table tbody tr { transition: background 0.2s; }
    .data-table tbody tr:hover { background: var(--admin-hover); }
    .data-table tbody tr:last-child td { border-bottom: none; }

    .back-btn, .balance-btn {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        color: var(--admin-text);
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }

    .back-btn:hover, .balance-btn:hover {
        background: var(--admin-hover);
        transform: translateY(-1px);
    }

    .balance-btn { background: var(--primary); border-color: var(--primary); color: white; }

    .section-title {
        font-size: 1.2rem;
        margin-bottom: 15px;
        color: var(--admin-text);
        font-weight: 600;
    }

    .section-title i {
        margin-right: 8px;
        color: var(--primary);
    }

    .empty-state {
        text-align: center;
        padding: 40px;
        color: var(--admin-muted);
    }

    .badge-in  { background: rgba(46,213,115,0.15); color: #4ade80; }
    .badge-out { background: rgba(239,68,68,0.15);  color: #f87171; }

    /* Modal overrides to match admin dark theme */
    .modal-overlay { z-index: 10000 !important; }

    .modal {
        background: var(--admin-card) !important;
        border: 1px solid var(--admin-border) !important;
        box-shadow: 0 20px 50px rgba(0,0,0,0.6) !important;
        color: var(--admin-text) !important;
    }

    .modal-header { border-bottom: 1px solid var(--admin-border) !important; }
    .modal-header h2 { color: var(--admin-text) !important; }
    .modal-footer { border-top: 1px solid var(--admin-border) !important; }

    .form-group { margin-bottom: 20px; }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--admin-muted) !important;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .form-control {
        background: var(--admin-surface) !important;
        border: 1px solid var(--admin-border) !important;
        color: var(--admin-text) !important;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary) !important;
    }

    /* Button overrides for admin dark theme */
    .modal-footer .btn-outline {
        background: var(--admin-surface);
        border: 1px solid var(--admin-border);
        color: var(--admin-muted);
    }
    .modal-footer .btn-outline:hover {
        background: var(--admin-hover);
        border-color: var(--admin-muted);
        color: var(--admin-text);
        transform: none;
        box-shadow: none;
    }
    .modal-footer .btn-primary {
        background: var(--primary);
        box-shadow: 0 4px 12px rgba(0,102,255,0.3);
    }
    .modal-footer .btn-danger {
        background: rgba(239,68,68,0.12);
        color: #f87171;
        border: 1px solid rgba(239,68,68,0.3);
    }
    .modal-footer .btn-danger:not(:disabled):hover {
        background: #ef4444;
        color: white;
        box-shadow: 0 4px 12px rgba(239,68,68,0.4);
        transform: none;
    }
    .modal-footer .btn-danger:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    /* Tabs */
    .tab-bar {
        display: flex;
        border-bottom: 1px solid var(--admin-border);
        padding: 0 20px;
    }

    .tab-btn {
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        color: var(--admin-muted);
        padding: 16px 20px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: -1px;
        transition: color 0.2s, border-color 0.2s;
    }

    .tab-btn:hover { color: var(--admin-text); }

    .tab-btn.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }

    /* Delete info box */
    .delete-info-box {
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.25);
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .delete-info-box .label { font-size: 0.75em; color: var(--admin-muted); margin-bottom: 2px; }
    .delete-info-box .value { font-weight: 700; color: #f87171; font-size: 1em; }
    .delete-info-box .value-secondary { font-weight: 600; color: var(--admin-text); font-size: 0.9em; }
</style>

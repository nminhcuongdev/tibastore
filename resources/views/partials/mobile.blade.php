<style>
.mobile-menu-toggle { display: none; }
@media screen {
    html[data-mobile-layout] { --desktop-display-scale: 1; }
    html[data-mobile-layout] body { zoom: 1; margin: 0; }
    html[data-mobile-layout] .layout { display: block; }
    html[data-mobile-layout] .layout > .page { width: 100%; min-width: 0; }
    html[data-mobile-layout] .content { padding: 12px 10px 28px !important; }
    html[data-mobile-layout] .app-sidebar,
    html[data-mobile-layout] .app-sidebar.is-collapsed {
        box-sizing: border-box; width: 100%; height: auto; position: static;
        display: flex; flex-direction: row; flex-wrap: wrap; padding: 10px; gap: 10px;
        overflow: visible !important; border-bottom: 1px solid #dce7f7;
    }
    html[data-mobile-layout] .app-sidebar .brand { font-size: 20px; width: auto; padding: 6px; }
    html[data-mobile-layout] .app-sidebar .brand .txt { display: inline; }
    html[data-mobile-layout] [data-brand-short],
    html[data-mobile-layout] .sidebar-toggle,
    html[data-mobile-layout] .app-sidebar .spacer { display: none !important; }
    html[data-mobile-layout] .mobile-menu-toggle {
        display: block; background: white; color: #1e40af; border: 1px solid #b9cfee;
        border-radius: 8px; min-height: 44px; padding: 8px 12px; cursor: pointer;
    }
    html[data-mobile-layout] .sidebar-display-mode { margin-left: auto; max-width: 160px; }
    html[data-mobile-layout] .app-sidebar .side-nav { width: 100%; flex-direction: row; flex-wrap: wrap; }
    html[data-mobile-layout] .app-sidebar.is-collapsed .side-nav .txt,
    html[data-mobile-layout] .app-sidebar.is-collapsed .logout .txt { display: inline; }
    html[data-mobile-layout] .app-sidebar:not(.is-mobile-open) .side-nav,
    html[data-mobile-layout] .app-sidebar:not(.is-mobile-open) > form { display: none; }
    html[data-mobile-layout] input:not([type="checkbox"]):not([type="radio"]),
    html[data-mobile-layout] select,
    html[data-mobile-layout] textarea,
    html[data-mobile-layout] button,
    html[data-mobile-layout] .button { font-size: 16px !important; min-height: 44px !important; }
    html[data-mobile-layout] input,
    html[data-mobile-layout] select,
    html[data-mobile-layout] textarea { box-sizing: border-box; max-width: 100%; }
    html[data-mobile-layout] .toolbar { flex-wrap: wrap; }
    html[data-mobile-layout] .toolbar > .field { min-width: 0; flex: 1 1 150px; }
    html[data-mobile-layout] .summary { flex-wrap: wrap; gap: 12px; }
    html[data-mobile-layout] .form-shell,
    html[data-mobile-layout] .details,
    html[data-mobile-layout] .order-schedule-dates { grid-template-columns: minmax(0, 1fr) !important; }
    html[data-mobile-layout] .table-shell,
    html[data-mobile-layout] .orders-scroll,
    html[data-mobile-layout] .order-entry-wrap {
        box-sizing: border-box; width: 100%; max-width: 100%;
        overflow: auto !important; -webkit-overflow-scrolling: touch;
        touch-action: pan-x pan-y; overscroll-behavior-x: contain;
    }
    html[data-mobile-layout] .orders-scroll { max-height: 70dvh !important; }
    html[data-mobile-layout] .orders-scroll tr[data-order-row] > td:nth-child(n) {
        position: static !important; left: auto !important;
    }
    html[data-mobile-layout] .orders-scroll thead th:nth-child(n) {
        left: auto !important; z-index: 3;
    }
    html[data-mobile-layout] .order-entry-table { display: block; min-width: 0 !important; }
    html[data-mobile-layout] .order-entry-table > colgroup,
    html[data-mobile-layout] .order-entry-table > thead { display: none; }
    html[data-mobile-layout] .order-entry-table .order-items { display: block; }
    html[data-mobile-layout] .order-entry-table .order-item {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        margin-bottom: 16px; border: 1px solid #c3d4ed; border-radius: 8px;
    }
    html[data-mobile-layout] .order-entry-table .order-item > td {
        display: block; width: auto; min-width: 0; max-width: 100%;
    }
    html[data-mobile-layout] .order-entry-table .order-item > td:nth-child(-n+3) { grid-column: 1 / -1; }
    html[data-mobile-layout] .order-entry-table .order-item > td:nth-child(4)::before { content: "Giá thuê"; }
    html[data-mobile-layout] .order-entry-table .order-item > td:nth-child(5)::before { content: "Số ngày"; }
    html[data-mobile-layout] .order-entry-table .order-item > td:nth-child(6)::before { content: "Thành tiền "; }
    html[data-mobile-layout] .order-code-layout { grid-template-columns: minmax(0, 1fr); }
    html[data-mobile-layout] .order-entry-table .entry-sr-only { position: static; width: auto; height: auto; clip-path: none; }
    html[data-mobile-layout] dialog { box-sizing: border-box; max-width: 94vw; max-height: 85dvh; overflow: auto; }
}

</style>

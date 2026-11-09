<style>
    .order-invoice-wrap { overflow-x: auto; border: 1px solid #dce4ef; border-radius: 10px; background: white; }
    .orders-scroll table.order-invoice-table { width: 100%; min-width: 850px !important; table-layout: fixed; border-collapse: collapse; background: white; color: #28374a; }
    .orders-scroll .order-invoice-table thead th { position: static; inset: auto; z-index: auto; background: #203b60; color: white; text-align: center; font-size: 11px; padding: 13px 8px; border: 0; white-space: normal; }
    .orders-scroll .order-invoice-table td { position: static; background: transparent; border: 0; border-bottom: 1px solid #e5eaf1; padding: 6px 8px; font-size: 12px; font-weight: normal; color: #28374a; white-space: normal; overflow-wrap: anywhere; vertical-align: top; }
    .orders-scroll .order-invoice-table tbody tr:nth-child(even) { background: #f8fafc; }
    .orders-scroll .order-invoice-table td:first-child { text-align: center; color: #8793a5; }
    .orders-scroll .order-invoice-table .order-invoice-number { text-align: center; }
    .orders-scroll .order-invoice-table .order-invoice-money { text-align: right; font-variant-numeric: tabular-nums; }
    .order-invoice-code { display: block; color: #203b60; font-size: 12px; margin-bottom: 3px; }
    .order-invoice-product-controls { display: flex; align-items: center; gap: 6px; }
    .order-invoice-table .code-thumb { display: block; flex: 0 0 42px; width: 42px; height: 48px; max-width: 100%; border-radius: 5px; border: 1px solid #e1e7ef; padding: 2px; background: white; }
    .order-invoice-dispatch { margin: 0; min-width: 0; }
    .orders-scroll .order-invoice-table .order-invoice-dispatch select { width: 84px; max-width: 100%; min-height: 28px; height: 28px; margin: 0; padding: 3px 18px 3px 5px; font-size: 11px; border-radius: 5px; }
    .order-invoice-table .code-thumb img { object-fit: contain; }
    .order-invoice-no-image { font-size: 10px; color: #8793a5; }
    .orders-scroll .order-invoice-table tfoot td { background: #fafbfd; padding: 11px; }
    .orders-scroll .order-invoice-table tfoot td:first-child { text-align: left; color: #516178; }
    .orders-scroll .order-invoice-table .order-invoice-total td { background: #eef2f7; border-top: 2px solid #ba904d; color: #203b60; font-weight: bold; }
    .order-invoice-note { margin-top: 12px; padding: 10px 12px; border-radius: 7px; background: #f3f6fb; color: #60718a; font-size: 12px; white-space: pre-wrap; }
</style>

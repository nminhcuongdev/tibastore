<style>
    .order-entry-table textarea[data-note] { display: block; box-sizing: border-box; width: 100%; min-width: 0; min-height: 34px; padding: 7px; font: inherit; font-size: 12px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word; overflow: hidden; resize: none; border: 1px solid #cbd9ec; border-radius: 5px; background: white; color: inherit; }
    .order-entry-wrap { overflow-x: auto; margin-top: 8px; border: 1px solid #dce5f1; border-radius: 10px; background: white; }
    .order-entry-table { width: 100%; min-width: 1120px; table-layout: fixed; border-collapse: collapse; }
    .order-entry-table > thead > tr > th { background: #203b60; color: white; font-size: 11px; padding: 15px 9px; text-align: center; border: 0; }
    .order-entry-table .order-items { display: table-row-group; }
    .order-entry-table .order-item { display: table-row; border: 0; padding: 0; background: white; }
    .order-entry-table .order-item:nth-child(even) { background: #f8fafc; }
    .order-entry-table .order-item > td { padding: 12px 8px; vertical-align: top; border-right: 1px solid #e8edf4; border-bottom: 1px solid #e1e7f0; font-size: 12px; min-width: 0; }
    .order-entry-table .order-item + .order-item > td { border-top: 3px solid #bf510d; }
    .order-entry-table .order-item > .entry-number { background: #fff0de; color: #9a3c00; font-weight: 800; border-left: 4px solid #bf510d; }
    .order-entry-table .product-picker-cell [data-search] { background: #b84a00; color: #fff; border: 2px solid #963b00; font-weight: 800; box-shadow: 0 3px 8px #9a3c0026; }
    .order-code-layout { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr)); gap: 12px; align-items: start; }
    .order-code-layout > * { min-width: 0; }
    .order-entry-table .is-stock-source .order-code-layout { grid-template-columns: minmax(0, 1fr); }
    .order-entry-table .is-stock-source > td { padding-top: 8px; padding-bottom: 8px; }
    .order-entry-table .is-stock-source .size-row { padding-bottom: 5px; gap: 4px 6px; }
    .order-entry-table .is-stock-source .size-note-field textarea {
        height: 32px; min-height: 32px !important; padding: 5px 7px !important;
    }
    .order-entry-table .is-stock-source .size-note-field:focus-within textarea { min-height: 60px !important; }
    .order-entry-table .is-stock-source .entry-code-thumb { width: 48px; height: 54px; margin: 5px 0; }
    .order-entry-table .is-stock-source [data-add-size] { margin-top: 4px; }
    .order-picker-controls { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 8px; align-items: start; }
    .order-picker-controls > * { min-width: 0; }
    .order-entry-table .order-picker-controls .entry-supply-choice { margin: 0; padding: 0; border: 0; background: none; }
    .order-picker-controls .entry-supply-choice > label { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
    @media (max-width: 860px) { .order-picker-controls { grid-template-columns: minmax(0, 1fr); } }
    @media screen { html[data-mobile-layout] .order-picker-controls { grid-template-columns: minmax(0, 1fr); } }
    .order-code-layout > .purchase-plan { margin-top: 0; }
    .purchase-plan [data-apply-purchase-state] { font-size: 12px; color: #655166; }
    .purchase-plan strong[title], .purchase-plan [data-apply-purchase-state][title] { cursor: help; }
    .order-code-layout .button { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
    .order-entry-table .product-picker-cell [data-search]::placeholder { color: #ffead6; opacity: 1; }
    .order-entry-table .product-picker-cell [data-search]:focus { background: #a64000; border-color: #7e3000; outline: 3px solid #ffc58c; outline-offset: 2px; }
    .order-entry-table input, .order-entry-table select { width: 100%; min-width: 0; padding: 7px; font-size: 12px !important; border-radius: 5px; }
    .order-entry-table input[data-block-rental], .order-entry-table input[data-block-days] { text-align: right; }
    .order-entry-table input[data-block-rental] { appearance: textfield; -moz-appearance: textfield; }
    .order-entry-table input[data-block-rental]::-webkit-inner-spin-button,
    .order-entry-table input[data-block-rental]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .order-entry-table input[data-block-rental], .order-entry-table input[data-block-days],
    .order-entry-table [data-block-subtotal] { font-variant-numeric: tabular-nums; }
    .order-entry-table .entry-amount { white-space: nowrap; }
    .order-entry-table .entry-number { text-align: center; color: #8b99ad; }
    .order-entry-table .drag-handle { padding: 8px 0; margin: 5px 0 0; font-size: 17px; justify-content: center; }
    .order-entry-table .entry-code-thumb { display: block; width: 72px; height: 82px; border: 1px solid #e0e7ef; background: white; margin: 9px auto 0; padding: 3px; border-radius: 6px; }
    .order-entry-table .entry-code-thumb:disabled { display: none; }
    .order-entry-table .entry-code-thumb img { width: 100%; height: 100%; object-fit: contain; }
    .order-entry-table .selected-product-info { margin: 0; padding: 7px 2px; border: 0; background: none; color: #203b60; font-size: 11px; line-height: 1.6; font-weight: 600; white-space: normal; overflow-wrap: anywhere; word-break: normal; min-width: 0; max-width: 100%; }
    .order-entry-table [data-category] { margin-bottom: 7px; color: #6b7e96; font-size: 11px !important; background: #f4f7fb; }
    .order-entry-table .size-rows { gap: 10px; }
    .order-entry-table .size-row { display: grid; grid-template-columns: minmax(0, 1fr) 65px 28px; gap: 6px; align-items: end; padding: 0 0 10px; border-bottom: 1px dashed #dce4ef; }
    .order-entry-table .size-row > .field:first-child { display: none; }
    .order-entry-table .size-row .field { margin: 0; gap: 4px; }
    .order-entry-table .size-row label { font-size: 10px; color: #788ba2; margin: 0; }
    .order-entry-table .size-row .size-note-field { grid-column: 1 / -1; }
    .order-entry-table .size-row .button.danger { min-width: 0; padding: 5px; font-size: 0 !important; width: 28px; min-height: 32px; overflow: hidden; }
    .order-entry-table .size-row .button.danger:after { content: '×'; font-size: 17px; }
    .order-entry-table [data-add-size] { padding: 6px 10px; margin-top: 10px; font-size: 11px; min-height: 30px; }
    .order-entry-table [data-remove-block] { padding: 4px; min-width: 25px; width: 100%; font-size: 19px; border: 0; background: none; }
    .order-entry-table .entry-amount { text-align: right; font-weight: 700; color: #203b60; padding-top: 19px !important; }
    .entry-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
    .order-entry-table .product-suggestions { position: fixed; z-index: 1000; right: auto; bottom: auto; overflow-y: auto; overscroll-behavior: contain; border: 1px solid #cfdae9; border-radius: 10px; box-shadow: 0 12px 35px #203b6030; background: white; }
    .order-entry-table .product-suggestion { display: flex; width: 100%; min-width: 0; gap: 12px; padding: 10px; border-radius: 0; text-align: left; }
    .order-entry-table .product-suggestion__info { min-width: 0; }
    .order-entry-table .product-suggestion .product-name { font-size: 12px; white-space: normal; }
    .order-entry-table .qty-warning { grid-column: 1 / -1; font-size: 11px; }
</style>

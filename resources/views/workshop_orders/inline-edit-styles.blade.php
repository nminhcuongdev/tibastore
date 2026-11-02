<style>
.workshop-value-link{display:flex;align-items:center;gap:8px;min-height:36px}.workshop-value-link[hidden]{display:none}.workshop-value-link a{color:#2447a8;font-weight:700;text-decoration:underline;text-underline-offset:3px;overflow-wrap:anywhere;white-space:normal}.workshop-value-link button{background:white;border:1px solid #bbcbe2;border-radius:5px;padding:4px 7px;cursor:pointer;font-size:11px;flex-shrink:0}
.workshop-size-editor{display:grid;gap:8px;margin-top:10px}.workshop-size-line{display:grid;grid-template-columns:minmax(0,1fr) 65px 24px;gap:5px;margin:7px 0}.workshop-size-line select,.workshop-size-line input{min-width:0!important;width:100%!important}.workshop-size-editor small{white-space:normal;color:#64748b}.workshop-size-editor button{cursor:pointer}.workshop-size-editor [hidden]{display:none!important}td[data-stock-cell]{min-width:150px;text-align:center}
table th:nth-child(3),table td:nth-child(3){width:220px;min-width:220px;max-width:260px;white-space:normal;overflow-wrap:anywhere}
tr[data-edit-row] textarea[name=size_note]{width:100%;min-width:190px;min-height:40px;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;line-height:1.6;resize:none;overflow:hidden}
[data-product-image]:focus{outline:2px solid #82a9d1;outline-offset:-2px}
[data-product-image]>.muted{max-width:130px;font-size:11px;margin-top:4px}
.workshop-add-row-actions{margin:16px 0 24px;display:flex;justify-content:flex-start}.workshop-add-row-actions button{padding:13px 20px;border:1px dashed #c4d3e7;border-radius:10px;background:white;color:#5e7491;font:inherit;font-size:13px;cursor:pointer;box-shadow:0 3px 8px #203b6008}.workshop-add-row-actions button:hover{background:#f3f7fd;border-color:#8eabc9}
table th:nth-child(15),table td:nth-child(15){min-width:115px;width:115px;text-align:center}td[data-delete-cell] .danger{white-space:nowrap;padding:9px 12px;border:1px solid #e9bfc3;background:#fff;color:#ad3342;border-radius:7px;cursor:pointer}
table thead th{ text-align:center!important; vertical-align:middle; }
table th:nth-child(7),table td:nth-child(7),table th:nth-child(9),table td:nth-child(9){width:150px;min-width:150px;max-width:180px;white-space:normal;overflow-wrap:anywhere}
table th:nth-child(8),table td:nth-child(8){width:240px;min-width:240px;max-width:300px;white-space:normal;overflow-wrap:anywhere}
tr[data-edit-row] textarea[name=buyer_note]{min-width:210px;white-space:pre-wrap;overflow-wrap:anywhere}
tr[data-edit-row] textarea[name=order_name],tr[data-edit-row] textarea[name=source]{min-width:120px;width:100%;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;line-height:1.6;overflow:hidden;resize:none;min-height:40px}
tr[data-new-row]{background:#f0f6ff!important}tr[data-new-row] td{border-top:2px solid #b5cbe6}tr[data-new-row] input,tr[data-new-row] textarea{border-color:#d6e2f2;background:white}tr[data-new-row] [data-save-state]{color:#416d9e}
tr[data-edit-row] input,tr[data-edit-row] textarea{box-sizing:border-box;width:100%;min-width:90px;border:1px solid transparent;border-radius:5px;background:transparent;font:inherit;color:inherit;padding:8px 6px}tr[data-edit-row] input:hover,tr[data-edit-row] textarea:hover{border-color:#d5dfec;background:#fff}tr[data-edit-row] input:focus,tr[data-edit-row] textarea:focus{outline:2px solid #82a9d1;background:#fff}tr[data-edit-row] input[type=number]{text-align:right;min-width:75px}tr[data-edit-row] input[type=date]{min-width:135px}tr[data-edit-row] textarea{resize:vertical;min-width:130px}tr[data-edit-row] [data-save-state]{display:block;max-width:190px;font-size:11px;color:#7386a0;white-space:normal}tr[data-edit-row].has-changes{box-shadow:inset 3px 0 #c49a50}tr[data-edit-row] .inline-save{border:1px solid #203b60;border-radius:6px;background:#203b60;color:#fff;padding:8px 16px;cursor:pointer}tr[data-edit-row] button:disabled{opacity:.5;cursor:wait}tr[data-edit-row] td{vertical-align:middle}
table th:nth-child(10),table td:nth-child(10),table th:nth-child(11),table td:nth-child(11){min-width:175px;width:175px}
tr[data-edit-row] input[type=date]{min-width:150px;width:100%;height:40px;padding:8px 10px;border:1px solid #cbd8e9;border-radius:7px;background:#fff;color:#203b60;color-scheme:light;font-size:13px;cursor:text}
tr[data-edit-row] input[type=date]:focus{border-color:#b5965e;outline:2px solid #b5965e;outline-offset:0;background:#fff}
tr[data-edit-row] input[type=date]::-webkit-calendar-picker-indicator{opacity:1;cursor:pointer;padding:2px}
</style>
<style>
tr[data-edit-row].is-arrived,tr[data-edit-row].is-arrived td{background:#a8d9b5!important}
tr[data-edit-row].is-arrived:hover td{background:#96cfa6!important}
tr[data-edit-row].is-ordered,tr[data-edit-row].is-ordered td{background:#f6c9dc!important}
tr[data-edit-row].is-ordered:hover td{background:#efb5cf!important}
tr[data-edit-row] input:not([type=hidden]),tr[data-edit-row] textarea,tr[data-edit-row] select,
tr[data-edit-row].is-arrived input:not([type=hidden]),tr[data-edit-row].is-arrived textarea,tr[data-edit-row].is-arrived select{
background:#fff;border:1px solid #d4deea;border-radius:7px;box-shadow:0 2px 5px rgba(30,55,85,.08);color:#243b58;
}
tr[data-edit-row] input:not([type=hidden]):hover,tr[data-edit-row] textarea:hover,tr[data-edit-row] select:hover{border-color:#b2c5da;box-shadow:0 3px 8px rgba(30,55,85,.12)}
tr[data-edit-row] input:not([type=hidden]):focus,tr[data-edit-row] textarea:focus,tr[data-edit-row] select:focus{background:#fff;box-shadow:0 0 0 3px rgba(91,140,190,.15),0 3px 8px rgba(30,55,85,.1)}
tr[data-edit-row].is-arrived input:focus,tr[data-edit-row].is-arrived textarea:focus{background:white}
</style>

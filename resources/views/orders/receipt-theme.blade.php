<style>
body.order-receipt-page{background:radial-gradient(ellipse at 0 10%,#fff0c9 0,transparent 45%),radial-gradient(ellipse at 100% 0,#d6efff 0,transparent 55%),#f3f8fc;background-attachment:fixed;color:#284860}
.order-receipt-page .topbar{background:#ffffffed;border-bottom:1px solid #dcebf4;padding:14px 24px}
.order-receipt-page .topbar .actions{flex-wrap:wrap;gap:9px}
.order-receipt-page .button{background:#288ac0;color:white;border:1px solid #288ac0;border-radius:8px;font-size:12px;font-weight:600;box-shadow:0 3px 8px #288ac014}
.order-receipt-page .button.secondary{background:white;color:#397594;border-color:#cee4f1;box-shadow:none}
.order-receipt-page .button:hover{filter:brightness(.97)}
.order-receipt-page .content{padding:24px clamp(12px,2vw,28px) 40px!important}
.order-receipt-page .receipt{max-width:1160px;border:1px solid #dceaf2;border-radius:16px;box-shadow:0 12px 45px #3d739414;background:#fff}
.order-receipt-page .receipt-head{display:grid;grid-template-columns:1fr auto;align-items:center;gap:24px;background:linear-gradient(115deg,#edf8ff 0%,#f5fbff 55%,#fff6dc 100%);border-bottom:2px solid #e4bd68;padding:28px 30px;position:relative}
.receipt-brand{grid-column:1/-1;display:flex;gap:14px;align-items:center;padding-bottom:18px;border-bottom:1px solid #c4deeb}
.receipt-brand img{width:86px;height:86px;object-fit:contain;background:white;border:1px solid #e4d3a6;border-radius:10px;flex-shrink:0}
.receipt-brand strong{display:block;font-size:22px;letter-spacing:2px;font-weight:700;color:#28769e}
.receipt-brand span{display:block;font-size:9px;letter-spacing:1.6px;color:#a78037;margin-top:7px}
.order-receipt-page .eyebrow{font-size:10px;letter-spacing:1.7px;color:#785518;font-weight:800;margin-bottom:8px}
.order-receipt-page .receipt-head h1{font-family:'Segoe UI',Arial,sans-serif;font-size:24px!important;font-weight:800;line-height:1.4;color:#163b59;margin:0!important;overflow-wrap:anywhere}
.order-receipt-page .order-id{font-size:12px;color:#334e68;font-weight:800}
.order-receipt-page .status-badge{margin-top:9px;padding:7px 12px;font-size:11px;font-weight:600}
.order-receipt-page .section{padding:26px 30px;border-color:#e6eff5}
.order-receipt-page .section-title{display:flex;align-items:center;gap:10px;font-size:14px!important;letter-spacing:.6px;font-weight:800;color:#17466a;margin:0 0 17px}
.order-receipt-page .section-title:before{content:'';display:block;width:4px;height:18px;background:#e5bd63;border-radius:4px}
.order-receipt-page .details{gap:12px;align-items:start}
.order-receipt-page .detail{background:#f5faff;border:1px solid #deedf6;border-radius:9px;padding:14px 16px;min-width:0;height:100%}
.order-receipt-page .detail .label{color:#334e68;font-size:11px;font-weight:800;line-height:1.5;margin-bottom:6px}
.order-receipt-page .detail .value{color:#30566e;font-size:13px;font-weight:600;line-height:1.7;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word}
.order-receipt-page .details .detail:nth-child(4),.order-receipt-page .details .detail:nth-child(5){background:#fffdf7;border-color:#f0e5c9}
.order-receipt-page .receipt-payment{background:linear-gradient(180deg,#fffdf7,white)}
.order-receipt-page .receipt-payment .detail{background:white;border-color:#eee4c9}
.order-receipt-page .receipt-payment .details .detail:first-child{background:#eff8ff;border-color:#d4eaf7}
.order-receipt-page .receipt-payment .details .detail:last-child{background:#fff1c9;border-color:#e5c578;box-shadow:inset 3px 0 #d6a542}
.order-receipt-page .receipt-payment .details .detail:last-child .value{font-size:19px;color:#87601d}
.order-receipt-page .receipt-payment .details .detail:last-child .label{color:#785518}
.order-receipt-page table thead th{background:#dff1fc;color:#17466a;border-color:#cde4f2;font-size:11px;font-weight:800}
.order-receipt-page table tbody td{border-color:#e4edf4;color:#365a73;font-size:12px;line-height:1.7}
.order-receipt-page table tbody tr:nth-child(even){background:#f7fbfe}
.order-receipt-page .table-hint{font-size:11px;color:#8aa1b1}
.order-receipt-page .table-shell{border:1px solid #ddeaf2;border-radius:9px}
@media(max-width:700px){.order-receipt-page .receipt-head{padding:20px;grid-template-columns:1fr;gap:16px}.receipt-brand{grid-column:auto}.order-receipt-page .order-id{text-align:left}.order-receipt-page .receipt-head h1{font-size:21px!important}.order-receipt-page .section{padding:20px 16px}.receipt-brand img{width:70px;height:70px}.receipt-brand strong{font-size:20px}}
@media print{body.order-receipt-page{background:white;print-color-adjust:exact;-webkit-print-color-adjust:exact}.order-receipt-page .content{padding:0!important}.order-receipt-page .receipt{max-width:none;box-shadow:none;border:0;border-radius:0}.order-receipt-page .receipt-head{padding:14px 16px;gap:14px}.receipt-brand{padding-bottom:10px}.receipt-brand img{width:62px;height:62px}.receipt-brand strong{font-size:18px}.order-receipt-page .receipt-head h1{font-size:19px!important}.order-receipt-page .section{padding:18px 16px}.order-receipt-page .detail{padding:10px 12px}.order-receipt-page .detail .value{font-size:11px}.order-receipt-page .detail .label{font-size:10px}.receipt-brand,.order-receipt-page .detail{break-inside:avoid}}
@page{size:A4 portrait;margin:8mm}
@media print{
    html,body.order-receipt-page{margin:0!important;padding:0!important;min-height:0!important}
    .order-receipt-page .sidebar,.order-receipt-page .topbar,.order-receipt-page .no-print{display:none!important}
    .order-receipt-page .layout{display:block!important;min-height:0!important}
    .order-receipt-page .content{margin:0!important;padding:0!important;width:100%!important;min-height:0!important}
    .order-receipt-page .receipt{width:194mm;max-width:100%;margin:0 auto;overflow:visible}
    .order-receipt-page .receipt-head{grid-template-columns:1fr auto;padding:8px 10px;gap:8px}
    .receipt-brand{padding-bottom:6px;gap:10px}
    .receipt-brand img{width:42px;height:42px}
    .receipt-brand strong{font-size:16px}
    .receipt-brand span{margin-top:3px}
    .order-receipt-page .receipt-head h1{font-size:18px!important}
    .order-receipt-page .section{padding:8px 10px}
    .order-receipt-page .section-title{font-size:12px!important;margin-bottom:6px;gap:6px}
    .order-receipt-page .section-title:before{height:14px;width:3px}
    .order-receipt-page .details{grid-template-columns:repeat(3,minmax(0,1fr));gap:5px}
    .order-receipt-page .detail{padding:5px 7px;border-radius:4px}
    .order-receipt-page .detail .label{font-size:10px;margin-bottom:2px}
    .order-receipt-page .detail .value{font-size:11px;line-height:1.35}
    .order-receipt-page .receipt-payment .details .detail:last-child .value{font-size:14px}
    .order-receipt-page .table-shell{overflow:visible}
    .order-receipt-page table{min-width:0!important;width:100%;table-layout:fixed}
    .order-receipt-page table th,.order-receipt-page table td{padding:4px;font-size:10px!important;line-height:1.3!important;overflow-wrap:anywhere;white-space:normal}
    .order-receipt-page table thead{display:table-header-group}
    .order-receipt-page .section-title{break-after:avoid}
    .order-receipt-page th.sortable{padding-right:4px!important;white-space:normal}
    .order-receipt-page th.sortable:after{display:none}
    .order-receipt-page .product-thumb,.order-receipt-page .product-thumb img{width:34px;height:34px;min-width:0;max-width:100%;object-fit:contain}
    .order-receipt-page .summary{padding-top:5px}
    .order-receipt-page .summary-box{padding:5px 8px;min-width:100px}
    .order-receipt-page .summary-box .value{font-size:14px}
    .order-receipt-page tr{break-inside:avoid}
}
</style>

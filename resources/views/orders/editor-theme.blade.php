<style>
body.order-editor{background:radial-gradient(ellipse at 90% 0%,#dce9ee 0,transparent 40%),radial-gradient(ellipse at 5% 30%,#eee9df 0,transparent 40%),#f1f4f8;background-attachment:fixed;color:#253953}
.order-editor .content{padding:26px clamp(16px,2.5vw,36px) 40px!important}
.order-editor-header{display:flex;align-items:center;gap:24px;position:relative;overflow:hidden;background:#1c3554;border:1px solid #314d6d;border-radius:16px;padding:24px 26px;box-shadow:0 10px 26px #1b365317;margin-bottom:22px}
.order-editor-header:after{content:'';position:absolute;width:250px;height:250px;border:1px solid #d6b26e26;border-radius:50%;right:-100px;top:-100px;pointer-events:none}
.order-editor-brand{display:flex;align-items:center;gap:12px;flex-shrink:0;padding-right:24px;border-right:1px solid #ffffff25}
.order-editor-brand img{display:block;width:90px;height:90px;object-fit:contain;background:#fff;border-radius:10px;border:1px solid #dbbd88;padding:2px}
.order-editor-eyebrow{display:block;font-size:18px;font-weight:700;letter-spacing:2px;color:#fff}
.order-editor-tagline{display:block;font-size:8px;letter-spacing:1.2px;color:#d0b88f;margin-top:8px}
.order-editor-title{flex:1;min-width:0}
.order-editor-kicker{display:block;color:#d0b88f;font-size:9px;letter-spacing:1.5px;font-weight:600;margin-bottom:9px}
.order-editor .order-editor-title h1{font-family:'Segoe UI',Arial,sans-serif;font-size:24px!important;font-weight:600;line-height:1.35;color:#fff;margin:0 0 9px!important;letter-spacing:-.4px}
.order-editor .order-editor-title p{color:#c3d1df;font-size:12px;line-height:1.7;max-width:570px}
.order-editor-back{display:inline-flex;align-items:center;gap:14px;padding:10px 13px;border:1px solid #ffffff35;border-radius:7px;font-size:11px;color:#e8eef5;white-space:nowrap;position:relative;z-index:1}
.order-editor-back:hover{background:#ffffff15}.order-editor-back span{color:#d3b17a;font-size:18px}
.order-editor .form-shell{max-width:none;border:1px solid #dae2ec;border-radius:14px;box-shadow:0 8px 35px #1c355409;gap:18px 22px;padding:24px;align-items:start;background:#fffffffa}
.order-editor-section{grid-column:1/-1;display:flex;align-items:center;gap:12px;padding:20px 0 12px;border-top:1px solid #e7ebf1;margin-top:8px}
.order-editor-section:first-child{border-top:0;padding-top:0;margin-top:0}
.order-editor-section>span{display:grid;place-items:center;flex-shrink:0;width:34px;height:36px;border:1px solid #e5d9c4;border-radius:8px;background:#faf6ee;color:#a07c42;font-size:11px;font-weight:600}
.order-editor-section h2{margin:0!important;font-size:15px!important;font-weight:600;color:#25415e}
.order-editor-section p{margin:5px 0 0;color:#8b99ab;font-size:11px}
.order-editor .form-shell>.field{align-content:start;gap:8px}
.order-editor .form-shell>.field>label{font-size:12px!important;font-weight:600;color:#526982}
.order-editor .form-shell>.field>input,.order-editor .form-shell>.field>select,.order-editor .form-shell>.field>textarea{background:#fbfcfe;border-color:#d8e1ed;border-radius:7px;padding:10px 12px;font-size:13px;box-shadow:0 1px 2px #1c355403}
.order-editor .form-shell>.field>input:focus,.order-editor .form-shell>.field>select:focus,.order-editor .form-shell>.field>textarea:focus{outline:2px solid #c1a474;outline-offset:1px;background:white;border-color:#c1a474}
.order-editor .readonly-value{border:1px solid #d4e3e6;background:#eef5f5;color:#2d6471;font-weight:700;border-radius:7px;padding:11px 12px}
.order-editor .flash{max-width:none;border:1px solid #d8e4dd;border-left:4px solid #55947c;border-radius:10px;box-shadow:0 3px 14px #1c355405;background:#fff;padding:14px 18px;color:#527168;font-size:12px}
.order-editor #draft_notice{flex-wrap:wrap}.order-editor #draft_clear{background:#f4f8f6;border-color:#d9e6df;color:#4b7466;font-size:11px}
.order-editor .flash.is-error{border-color:#edd3cf;border-left-color:#be7366;color:#9a5146}
.order-editor .actions{grid-column:1/-1;display:flex;justify-content:flex-end;gap:10px;padding-top:20px;margin-top:8px;border-top:1px solid #e5eaf0}
.order-editor .actions>.button[type=submit]{background:#203b60;border-color:#203b60;box-shadow:0 4px 10px #203b601a;color:white;padding:11px 24px;border-radius:8px}
.order-editor .actions>.button.secondary{background:#fff;color:#687d96;border-color:#d7e1ed;border-radius:8px}
@media(max-width:1100px){.order-editor-header{flex-wrap:wrap;gap:18px}.order-editor-brand{padding-right:18px}.order-editor-back{margin-left:auto}.order-editor-brand img{width:72px;height:72px}}
@media(max-width:700px){.order-editor-header{padding:20px;gap:18px}.order-editor-brand{width:100%;border-right:0;border-bottom:1px solid #ffffff25;padding:0 0 15px}.order-editor-title{flex-basis:100%}.order-editor .order-editor-title h1{font-size:21px!important}.order-editor-back{margin-left:0}.order-editor .form-shell{padding:16px;gap:15px}.order-editor .actions>.button{width:auto}.order-editor-section p{line-height:1.6}}
</style>

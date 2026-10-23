@php
    $statusPalette = [
        'chua_cho_size' => ['#ffffff', '#1e293b'],
        'da_in_file' => ['#6b21a8', '#ffffff'],
        'da_in_giay' => ['#fb923c', '#431407'],
        'dang_soan' => ['#64748b', '#ffffff'],
        'da_soan_xong' => ['#f9a8d4', '#500724'],
        'da_gui' => ['#15803d', '#ffffff'],
        'da_tra_ve' => ['#eab308', '#422006'],
        'thanh_cong' => ['#1d4ed8', '#ffffff'],
        'thanh_cong_thieu' => ['#ef2929', '#ffffff'],
        'huy_don' => ['#881337', '#ffffff'],
    ];
@endphp
<style>
    @foreach ($statusPalette as $key => [$background, $foreground])
        .row-{{ $key }} { background: {{ $background }}; color: {{ $foreground }}; }
        .row-{{ $key }} > td { background: {{ $background }}; color: {{ $foreground }}; }
        .status-{{ $key }}, .status-select.status-{{ $key }},
        .status-select option.status-{{ $key }}, #status option.status-{{ $key }},
        #status:has(option.status-{{ $key }}:checked) {
            --status-text: {{ $foreground }};
            background: {{ $background }}; color: {{ $foreground }};
        }
    @endforeach
    .status-chua_cho_size { border: 1px solid #cbd5e1; }
    tr[class*="row-"] .muted, tr[class*="row-"] .order-name,
    tr[class*="row-"] .name, tr[class*="row-"] .codes-panel__title,
    tr[class*="row-"] .quantity, tr[class*="row-"] .money { color: inherit; }
    tr[class*="row-"] .codes-panel { background: inherit; color: inherit; }
</style>

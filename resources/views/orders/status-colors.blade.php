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
        .row-{{ $key }} { background: #ffffff; color: #1e293b; }
        tr[data-order-row].row-{{ $key }} > td:nth-child(n+5):nth-child(-n+8) {
            background: {{ $background }}; color: {{ $foreground }};
        }
        .status-{{ $key }}, .status-select.status-{{ $key }},
        .status-select option.status-{{ $key }}, #status option.status-{{ $key }},
        #status:has(option.status-{{ $key }}:checked) {
            background: {{ $background }}; color: {{ $foreground }};
        }
    @endforeach
    .status-chua_cho_size { border: 1px solid #cbd5e1; }
    tr[data-order-row] > td:nth-child(n+5):nth-child(-n+8) .muted,
    tr[data-order-row] > td:nth-child(n+5):nth-child(-n+8) .name,
    tr[data-order-row] > td:nth-child(n+5):nth-child(-n+8) .quantity { color: inherit; }
</style>

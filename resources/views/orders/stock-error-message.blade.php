@php
    $stockErrorMatch = [];
    $stockErrorProduct = null;
    if (preg_match('/^Mã (.+) - size (.+):/u', $errorText, $stockErrorMatch)) {
        $stockErrorProduct = $products->first(fn ($product) => $product->code === $stockErrorMatch[1]
            && $product->size === $stockErrorMatch[2]);
    }
@endphp
@if ($stockErrorProduct)
    <a href="{{ route('products.show', $stockErrorProduct) }}" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;text-underline-offset:3px" title="Mở {{ $stockErrorProduct->code }} - size {{ $stockErrorProduct->size }} trong Kho ở tab mới">{{ rtrim($stockErrorMatch[0], ':') }}</a>{{ mb_substr($errorText, mb_strlen($stockErrorMatch[0]) - 1) }}
@else
    {{ $errorText }}
@endif

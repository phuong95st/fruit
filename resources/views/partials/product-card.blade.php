<div class="product-card" onclick="window.location.href='{{ route('product.show', $product->slug) }}'">
    <div class="pc-img {{ $product->bg }}">
        <div class="fruit-ico {{ $product->ic }}">
            <svg viewBox="0 0 24 24">{!! $product->svg !!}</svg>
        </div>
        @if($product->badge)
            <span class="pc-badge {{ $product->badge }}">{{ $product->badge_text }}</span>
        @endif
    </div>
    <div class="pc-body">
        <div class="pc-origin">{{ $product->origin }}</div>
        <div class="pc-name">{{ $product->name }}</div>
        <div class="pc-rating">
            <span class="pc-stars">
                @for($i = 1; $i <= $product->rating_stars; $i++)★@endfor
                @for($i = $product->rating_stars + 1; $i <= 5; $i++)☆@endfor
            </span>
            {{ $product->rating_value }}
        </div>
        <div class="pc-footer">
            <div>
                <span class="pc-price">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                <span class="pc-unit">/{{ $product->unit }}</span>
            </div>
            <button class="btn-add" onclick="event.stopPropagation(); addToCart({{ $product->id }}, '{{ $product->name }}')">
                <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
        </div>
    </div>
</div>

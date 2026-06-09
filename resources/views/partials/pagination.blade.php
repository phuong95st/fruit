@if ($paginator->hasPages())
    <!-- Previous Page Link -->
    @if ($paginator->onFirstPage())
        <button class="pg-btn" disabled>&lt;</button>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="pg-btn">&lt;</a>
    @endif

    <!-- Pagination Elements -->
    @foreach ($elements as $element)
        <!-- "Three Dots" Separator -->
        @if (is_string($element))
            <button class="pg-btn" disabled>{{ $element }}</button>
        @endif

        <!-- Array Of Links -->
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <button class="pg-btn on">{{ $page }}</button>
                @else
                    <a href="{{ $url }}" class="pg-btn">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    <!-- Next Page Link -->
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pg-btn">&gt;</a>
    @else
        <button class="pg-btn" disabled>&gt;</button>
    @endif
@endif

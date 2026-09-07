@if ($paginator->hasPages())
<nav class="trades-pagination" role="navigation" aria-label="Pagination">
  @if ($paginator->onFirstPage())
    <span class="trades-pagination__btn is-disabled" aria-disabled="true">Prev</span>
  @else
    <a class="trades-pagination__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
  @endif

  @foreach ($elements as $element)
    @if (is_string($element))
      <span class="trades-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
    @endif

    @if (is_array($element))
      @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
          <span class="trades-pagination__page is-active" aria-current="page">{{ $page }}</span>
        @else
          <a class="trades-pagination__page" href="{{ $url }}">{{ $page }}</a>
        @endif
      @endforeach
    @endif
  @endforeach

  @if ($paginator->hasMorePages())
    <a class="trades-pagination__btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
  @else
    <span class="trades-pagination__btn is-disabled" aria-disabled="true">Next</span>
  @endif
</nav>
@endif

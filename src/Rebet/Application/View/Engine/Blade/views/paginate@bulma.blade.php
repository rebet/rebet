@if ($paginator->hasPages() || $paginator->hasTotal())
    <div class="pagination-container {{ $paginator->hasTotal() ? 'level' : '' }}">
        {{-- Page Summary --}}
        @if ($paginator->hasTotal())
            <div class="pagination-summary level-left">
                @lang('pagination.summary', ['from' => $paginator->from(), 'to' => $paginator->to(), 'total' => $paginator->total()], $paginator->total())
            </div>
        @endif

        {{-- Page Navigation --}}
        @if ($paginator->hasPages())
            <nav class="pagination is-centered {{ $paginator->hasTotal() ? 'level-right' : '' }}" role="navigation" aria-label="pagination">
                {{-- Previous Page Link --}}
                <a class="pagination-previous" href="{{ $paginator->prevPageUrl() }}" rel="prev" aria-label="@lang('pagination.prev_page')" {{ $paginator->onFirstPage() ? 'disabled' : '' }}>&lsaquo;</a>

                {{-- Next Page Link --}}
                <a class="pagination-next" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next_page')" {{ $paginator->hasNext() ? '' : 'disabled' }}>&rsaquo;</a>

                {{-- Pagination Elements --}}
                <ul class="pagination-list">
                    @foreach ($paginator->focusPages() as $page)
                        {{-- First Page Link --}}
                        @if($page == $paginator->startOfFocusPage() && $page->gt(1))
                            <li><a class="pagination-link" href="{{ $paginator->firstPageUrl() }}" aria-label="@lang('pagination.first_page')">1</a></li>
                            <li><span class="pagination-ellipsis">&hellip;</span></li>
                        @endif

                        {{-- Page Links --}}
                        @if ($page == $paginator->page())
                            <li><a class="pagination-link is-current" aria-current="page" href="{{ $paginator->pageUrl($page) }}">{{ $page }}</a></li>
                        @else
                            <li><a class="pagination-link" href="{{ $paginator->pageUrl($page) }}">{{ $page }}</a></li>
                        @endif

                        {{-- Last Page Link --}}
                        @if($page == $paginator->endOfFocusPage() && $paginator->hasLastPage() && $page->lt($paginator->lastPage()))
                            <li><span class="pagination-ellipsis">&hellip;</span></li>
                            <li><a class="pagination-link" href="{{ $paginator->lastPageUrl() }}" aria-label="@lang('pagination.last_page')">{{ $paginator->lastPage() }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>
@endif
@if ($paginator->hasPages() || $paginator->hasTotal())
    <nav class="pagination-container"> 
        {{-- Page Summary --}}
        @if ($paginator->hasTotal())
            <div class="pagination-summary justify-content-start">
                @lang('pagination.summary', ['from' => $paginator->from(), 'to' => $paginator->to(), 'total' => $paginator->total()], $paginator->total())
            </div>
        @endif

        {{-- Page Navigation --}}
        @if ($paginator->hasPages())
            <ul class="pagination {{ $paginator->hasTotal() ? 'justify-content-end' : '' }}">
                {{-- First Page Link --}}
                @if ($paginator->hasLastPage())
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.first_page')">
                            <span class="page-link" aria-hidden="true">&laquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->firstPageUrl() }}" aria-label="@lang('pagination.first_page')">&laquo;</a>
                        </li>
                    @endif
                @endif

                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.prev_page')">
                        <span class="page-link" aria-hidden="true">&lsaquo;</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->prevPageUrl() }}" rel="prev" aria-label="@lang('pagination.prev_page')">&lsaquo;</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($paginator->focusPages() as $page)
                    {{-- Page Links --}}
                    @if ($page == $paginator->page())
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $paginator->pageUrl($page) }}">{{ $page }}</a></li>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasNext())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next_page')">&rsaquo;</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next_page')">
                        <span class="page-link" aria-hidden="true">&rsaquo;</span>
                    </li>
                @endif

                {{-- Last Page Link --}}
                @if ($paginator->hasLastPage())
                    @if ($paginator->onLastPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.last_page')">
                            <span class="page-link" aria-hidden="true">&raquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->lastPageUrl() }}" aria-label="@lang('pagination.last_page')">&raquo;</a>
                        </li>
                    @endif
                @endif
            </ul>
        @endif
    </nav>
@endif
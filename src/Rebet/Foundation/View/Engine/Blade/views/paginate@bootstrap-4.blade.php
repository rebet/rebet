@if ($paginator->hasPages())
    <nav> 
        <ul class="pagination">
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
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->prevPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($paginator->eachSidePages() as $page)
                {{-- "Three Dots" Separator --}}
                @if (is_string($page))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $page }}</span></li>
                @endif

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
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif

            {{-- First Page Link --}}
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
    </nav>
@endif
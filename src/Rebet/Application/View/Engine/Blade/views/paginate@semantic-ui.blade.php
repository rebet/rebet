@if ($paginator->hasPages() || $paginator->hasTotal())
    <nav class="pagination-container">
        {{-- Page Summary --}}
        @if ($paginator->hasTotal())
            <div class="ui pagination-summary left floated basic segment">
                @lang('pagination.summary', ['from' => $paginator->from(), 'to' => $paginator->to(), 'total' => $paginator->total()], $paginator->total())
            </div>
        @endif

        {{-- Page Navigation --}}
        @if ($paginator->hasPages())
            <div class="ui pagination menu {{ $paginator->hasTotal() ? 'right floated' : '' }}" role="navigation">
                {{-- First Page Link --}}
                @if ($paginator->hasLastPage())
                    @if ($paginator->onFirstPage())
                        <a class="icon item disabled" aria-disabled="true" aria-label="@lang('pagination.first_page')"> <i class="angle double left icon"></i> </a>
                    @else
                        <a class="icon item" href="{{ $paginator->firstPageUrl() }}" rel="prev" aria-label="@lang('pagination.first_page')"> <i class="angle double left icon"></i> </a>
                    @endif
                @endif

                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <a class="icon item disabled" aria-disabled="true" aria-label="@lang('pagination.prev_page')"> <i class="angle left icon"></i> </a>
                @else
                    <a class="icon item" href="{{ $paginator->prevPageUrl() }}" rel="prev" aria-label="@lang('pagination.prev_page')"> <i class="angle left icon"></i> </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($paginator->focusPages() as $page)
                    {{-- Page Links --}}
                    @if ($page == $paginator->page())
                        <span class="item active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="item" href="{{ $paginator->pageUrl($page) }}">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasNext())
                    <a class="icon item" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next_page')"> <i class="angle right icon"></i> </a>
                @else
                    <a class="icon item disabled" aria-disabled="true" aria-label="@lang('pagination.next_page')"> <i class="angle right icon"></i> </a>
                @endif

                {{-- Last Page Link --}}
                @if ($paginator->hasLastPage())
                    @if ($paginator->onLastPage())
                        <a class="icon item disabled" aria-disabled="true" aria-label="@lang('pagination.last_page')"> <i class="angle double right icon"></i> </a>
                    @else
                        <a class="icon item" href="{{ $paginator->lastPageUrl() }}" rel="prev" aria-label="@lang('pagination.last_page')"> <i class="angle double right icon"></i> </a>
                    @endif
                @endif
            </div>
        @endif
    </nav>
@endif
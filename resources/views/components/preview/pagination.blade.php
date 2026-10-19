@props(['paginator'])

@if ($paginator->total() > 0)
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $pages = collect([1, $lastPage]);

        for ($page = max(1, $currentPage - 2); $page <= min($lastPage, $currentPage + 2); $page++) {
            $pages->push($page);
        }

        $pages = $pages->unique()->sort()->values();
        $previousPage = null;
    @endphp

    <section class="abk-pagination" aria-label="Navigasi halaman tabel">
        <p class="abk-pagination-summary" aria-live="polite">
            Menampilkan <strong>{{ number_format($paginator->firstItem(), 0, ',', '.') }}–{{ number_format($paginator->lastItem(), 0, ',', '.') }}</strong>
            dari <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> data
        </p>

        <div class="abk-pagination-tools">
            <form method="GET" action="{{ url()->current() }}" class="abk-page-size-form">
                @foreach (request()->except(['page', 'per_halaman']) as $name => $value)
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="per_halaman">Baris per halaman</label>
                <select class="form-select form-select-sm abk-page-size" id="per_halaman" name="per_halaman" onchange="this.form.submit()">
                    @foreach ([10, 30, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($paginator->perPage() === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>

            @if ($paginator->hasPages())
                <nav class="abk-pagination-nav" aria-label="Halaman tabel">
                    @if ($paginator->onFirstPage())
                        <span class="abk-page-link disabled" aria-disabled="true" aria-label="Halaman sebelumnya">‹</span>
                    @else
                        <a class="abk-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>
                    @endif

                    @foreach ($pages as $page)
                        @if ($previousPage !== null && $page > $previousPage + 1)
                            <span class="abk-page-ellipsis" aria-hidden="true">…</span>
                        @endif

                        @if ($page === $currentPage)
                            <span class="abk-page-link active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="abk-page-link" href="{{ $paginator->url($page) }}" aria-label="Buka halaman {{ $page }}">{{ $page }}</a>
                        @endif

                        @php($previousPage = $page)
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a class="abk-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
                    @else
                        <span class="abk-page-link disabled" aria-disabled="true" aria-label="Halaman berikutnya">›</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endif

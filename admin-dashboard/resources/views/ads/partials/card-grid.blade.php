{{-- Card grid + pagination --}}
<div class="grid gap-2"
    :class="getGridClasses()">
    @foreach($ads as $ad)
        @include('ads.partials.card', ['ad' => $ad])
    @endforeach
</div>

{{-- Pagination --}}
<div class="mt-3">
    {{ $ads->links() }}
</div>

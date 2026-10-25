<p class="flex items-center gap-2 text-sm text-ink/70">
    @if ($titik)
        <span class="size-2.5 rounded-full {{ $titik }}"></span>
    @endif
    {{ $label }}
</p>
<p class="mt-2 text-3xl font-semibold">{{ number_format($nilai, 0, ',', '.') }}</p>
@if ($catatan)
    <p class="mt-1 text-xs text-ink/60">{{ $catatan }}</p>
@endif

<p class="flex items-center gap-2 text-sm text-ink/70">
    @if ($titik)
        <span class="size-2.5 rounded-full {{ $titik }}"></span>
    @endif
    {{ $label }}
</p>
<p class="mt-2 flex items-baseline gap-3">
    <span class="text-3xl font-semibold">{{ number_format($nilai, 0, ',', '.') }}</span>
    @if (isset($persen) && $persen !== null)
        <span class="text-lg font-semibold text-ok">{{ $persen }}%</span>
    @endif
</p>
@if ($catatan)
    <p class="mt-1 text-xs text-ink/60">{{ $catatan }}</p>
@endif
@if (isset($persen) && $persen !== null)
    <div class="mt-3 h-2 overflow-hidden rounded-full bg-concrete" role="progressbar"
         aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100" aria-label="Persentase selesai">
        <div class="h-full rounded-full bg-ok" style="width: {{ $persen }}%"></div>
    </div>
@endif

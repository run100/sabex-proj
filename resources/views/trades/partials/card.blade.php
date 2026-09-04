@php
  $listing = $listing ?? null;
  $wfl = $listing?->result_snapshot;
  $wflClass = $wfl === 'win' ? 'text-emerald-400' : ($wfl === 'lose' ? 'text-rose-400' : 'text-slate-300');
  $offering = $listing?->items?->where('side', 'offering')->sortBy('slot_no') ?? collect();
  $looking = $listing?->items?->where('side', 'looking_for')->sortBy('slot_no') ?? collect();
@endphp
<article class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
  <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-400">
    <div class="flex items-center gap-2">
      @if($listing?->owner?->avatar_url)
        <img src="{{ $listing->owner->avatar_url }}" alt="" width="28" height="28" class="h-7 w-7 rounded-full object-cover">
      @endif
      <a href="/u/{{ $listing?->owner?->roblox_sub }}" class="text-slate-200 hover:text-cyan-300">{{ $listing?->owner?->display_name ?: $listing?->owner?->username ?: 'Roblox player' }}</a>
      <span>{{ optional($listing?->created_at)->diffForHumans() }}</span>
    </div>
    <span class="{{ $wflClass }} font-black uppercase">{{ $wfl }} · {{ $listing?->status }}</span>
  </div>
  <div class="grid gap-4 md:grid-cols-2">
    <div>
      <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-cyan-300">Offering</h2>
      <div class="grid grid-cols-3 gap-1">
        @for($i = 1; $i <= 9; $i++)
          @php $item = $offering->firstWhere('slot_no', $i); @endphp
          <div class="flex aspect-square items-center justify-center rounded bg-slate-950/80 text-[10px] text-slate-500">
            @if($item)
              @if($item->image_url_snapshot)
                <img src="{{ $item->image_url_snapshot }}" alt="{{ $item->brainrot_name_snapshot }}" class="h-full w-full object-contain">
              @else
                {{ $item->brainrot_name_snapshot }}
              @endif
            @endif
          </div>
        @endfor
      </div>
    </div>
    <div>
      <h2 class="mb-2 text-xs font-bold uppercase tracking-wide text-emerald-300">Looking For</h2>
      <div class="grid grid-cols-3 gap-1">
        @for($i = 1; $i <= 9; $i++)
          @php $item = $looking->firstWhere('slot_no', $i); @endphp
          <div class="flex aspect-square items-center justify-center rounded bg-slate-950/80 text-[10px] text-slate-500">
            @if($item)
              @if($item->image_url_snapshot)
                <img src="{{ $item->image_url_snapshot }}" alt="{{ $item->brainrot_name_snapshot }}" class="h-full w-full object-contain">
              @else
                {{ $item->brainrot_name_snapshot }}
              @endif
            @endif
          </div>
        @endfor
      </div>
    </div>
  </div>
  <div class="mt-3 flex items-center justify-between text-sm">
    <span class="text-slate-400">{{ number_format((float) $listing?->offering_value_snapshot) }} → {{ number_format((float) $listing?->looking_value_snapshot) }}</span>
    <a href="/t/{{ $listing?->public_id }}" class="font-bold text-cyan-300 hover:text-cyan-200">View Trade</a>
  </div>
</article>

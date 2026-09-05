@extends('trades.layout')

@section('content')
@php
  $user = $tradeUser ?? null;
  $isOwner = $user && (int) $user->id === (int) $listing->owner_user_id;
  $isParticipant = $user && in_array((int) $user->id, [(int) $listing->owner_user_id, (int) $listing->counterparty_user_id], true);
@endphp
<p class="mb-4 text-sm text-slate-400">Community reference only. Finish the actual swap in Roblox / Steal a Brainrot. SAB Exist Count does not hold items.</p>

<div class="mb-4 flex flex-wrap items-start justify-between gap-3">
  <div>
    <h1 class="text-2xl font-black">{{ $listingH1 ?? ('Trade '.$listing->public_id) }}</h1>
    <p class="text-sm text-slate-400">{{ $listing->status }} · {{ $listing->views_count }} views · {{ optional($listing->created_at)->diffForHumans() }}</p>
  </div>
  <button type="button" data-copy-link class="rounded-full border border-white/15 px-3 py-1 text-sm text-slate-200">Copy trade link</button>
</div>

@include('trades.partials.card', ['listing' => $listing])

<div class="mt-6 grid gap-6 lg:grid-cols-2">
  <section class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
    <h2 class="mb-3 font-bold">W/F/L</h2>
    <p class="text-3xl font-black uppercase {{ $listing->result_snapshot === 'win' ? 'text-emerald-400' : ($listing->result_snapshot === 'lose' ? 'text-rose-400' : 'text-slate-200') }}">{{ $listing->result_snapshot }}</p>
    <p class="mt-2 text-sm text-slate-400">Offering {{ number_format((float) $listing->offering_value_snapshot) }} · Looking {{ number_format((float) $listing->looking_value_snapshot) }} · Diff {{ number_format((float) $listing->value_difference_snapshot) }} ({{ $listing->difference_percent_snapshot }}%)</p>
    <p class="mt-3 text-sm text-slate-400">Posted values stay frozen. Current SAB values may have moved.</p>
  </section>
  <section class="rounded-xl border border-white/10 bg-slate-900/70 p-4">
    <h2 class="mb-3 font-bold">Actions</h2>
    @if($listing->isOpen() && $user && !$isOwner)
      <form method="post" action="/api/v1/trading/trades/{{ $listing->public_id }}/join" data-json-form>
        @csrf
        <label class="block text-sm text-slate-400">Note
          <input name="note" maxlength="280" class="mt-1 w-full rounded border border-white/10 bg-slate-950 px-3 py-2 text-white">
        </label>
        <button class="mt-3 rounded-full bg-cyan-700 px-4 py-2 text-sm font-bold text-cyan-50">Join Trade</button>
      </form>
    @elseif($listing->isOpen() && !$user)
      <a href="/auth/roblox?return_to=/trading/{{ $listing->public_id }}" class="inline-flex rounded-full bg-cyan-700 px-4 py-2 text-sm font-bold text-cyan-50">Sign in to join</a>
    @endif
    @if($isOwner && $listing->isOpen())
      <form method="post" action="/api/v1/trading/trades/{{ $listing->public_id }}/cancel" data-json-form class="mb-4">
        @csrf
        <button class="rounded-full border border-rose-500/40 px-4 py-2 text-sm font-bold text-rose-200">Cancel listing</button>
      </form>
    @endif
    @if($isParticipant && $listing->isPendingLike())
      <div class="flex flex-wrap gap-2">
        <form method="post" action="/api/v1/trading/trades/{{ $listing->public_id }}/confirm" data-json-form>
          @csrf
          <input type="hidden" name="confirmation" value="completed">
          <button class="rounded-full bg-emerald-700 px-4 py-2 text-sm font-bold text-emerald-50">Mark Completed</button>
        </form>
        <form method="post" action="/api/v1/trading/trades/{{ $listing->public_id }}/confirm" data-json-form>
          @csrf
          <input type="hidden" name="confirmation" value="failed">
          <button class="rounded-full border border-rose-500/40 px-4 py-2 text-sm font-bold text-rose-200">Mark Failed</button>
        </form>
      </div>
    @endif
    @if($user)
      <form method="post" action="/api/v1/reports" data-json-form class="mt-4">
        @csrf
        <input type="hidden" name="listing_public_id" value="{{ $listing->public_id }}">
        <input type="hidden" name="reason" value="other">
        <label class="block text-sm text-slate-400">Report
          <input name="description" maxlength="1000" class="mt-1 w-full rounded border border-white/10 bg-slate-950 px-3 py-2 text-white">
        </label>
        <button class="mt-2 text-sm text-slate-400 hover:text-rose-300">Submit report</button>
      </form>
    @endif
    <p class="mt-3 text-sm text-rose-300" data-form-status></p>
  </section>
</div>

@if($isOwner && $listing->isOpen())
<section class="mt-6 rounded-xl border border-white/10 bg-slate-900/70 p-4">
  <h2 class="mb-3 font-bold">Join requests</h2>
  @forelse($joinRequests as $join)
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-white/5 pb-3">
      <div>
        <a class="text-cyan-300" href="{{ $join->requester?->profilePath() ?? '#' }}">{{ $join->requester?->display_name ?: $join->requester?->username }}</a>
        <span class="text-slate-500 text-sm">{{ $join->status }}</span>
        @if($join->note)<p class="text-sm text-slate-400">{{ $join->note }}</p>@endif
      </div>
      @if($join->isActive())
        <div class="flex gap-2">
          <form method="post" action="/api/v1/trading/join-requests/{{ $join->public_id }}/accept" data-json-form>@csrf<button class="rounded-full bg-cyan-700 px-3 py-1 text-sm font-bold text-cyan-50">Accept</button></form>
          <form method="post" action="/api/v1/trading/join-requests/{{ $join->public_id }}/reject" data-json-form>@csrf<button class="rounded-full border border-white/15 px-3 py-1 text-sm">Reject</button></form>
        </div>
      @endif
    </div>
  @empty
    <p class="text-slate-500">No join requests yet.</p>
  @endforelse
</section>
@endif

<section class="mt-6 rounded-xl border border-white/10 bg-slate-900/70 p-4">
  <h2 class="mb-3 font-bold">Timeline</h2>
  <ol class="space-y-2 text-sm text-slate-300">
    @foreach($listing->events->sortBy('created_at') as $event)
      <li>{{ optional($event->created_at)->format('H:i') }} · {{ $event->event_type }}@if($event->actor) · {{ $event->actor->display_name ?: $event->actor->username }}@endif</li>
    @endforeach
  </ol>
</section>
@endsection

@section('scripts')
<script>
  document.querySelector('[data-copy-link]')?.addEventListener('click', async () => {
    await navigator.clipboard.writeText(window.location.href);
  });
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  document.querySelectorAll('[data-json-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const status = document.querySelector('[data-form-status]');
      const body = {};
      new FormData(form).forEach((value, key) => { if (key !== '_token') body[key] = value; });
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify(body),
      });
      const payload = await response.json().catch(() => ({}));
      if (!payload.success) {
        if (status) status.textContent = payload.error?.message || 'Request failed';
        return;
      }
      window.location.reload();
    });
  });
</script>
@endsection

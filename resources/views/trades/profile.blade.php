@extends('trades.layout')

@section('content')
<div class="mb-6 flex items-center gap-4">
  @include('trades.partials.avatar', ['url' => $profile->avatar_url, 'size' => 72])
  <div>
    <h1 class="text-2xl font-black">{{ ($profile->display_name ?: $profile->username) }}'s Steal a Brainrot Trading Profile</h1>
    <p class="text-slate-400"><span>@</span>{{ $profile->username }}@if($profile->roblox_sub && !str_starts_with($profile->roblox_sub, 'local:')) · Roblox ID {{ $profile->roblox_sub }}@endif</p>
    @if($profile->robloxProfileUrl())
    <a class="text-cyan-300 text-sm" href="{{ $profile->robloxProfileUrl() }}" rel="nofollow noopener" target="_blank">View on Roblox</a>
    @endif
  </div>
</div>
<dl class="mb-8 grid grid-cols-2 gap-3 text-sm md:grid-cols-3">
  <div class="rounded-lg bg-slate-900/70 p-3"><dt class="text-slate-500">Posted</dt><dd class="text-xl font-black">{{ $stats['trades_posted'] }}</dd></div>
  <div class="rounded-lg bg-slate-900/70 p-3"><dt class="text-slate-500">Accepted</dt><dd class="text-xl font-black">{{ $stats['trades_accepted'] }}</dd></div>
  <div class="rounded-lg bg-slate-900/70 p-3"><dt class="text-slate-500">Completed</dt><dd class="text-xl font-black">{{ $stats['trades_completed'] }}</dd></div>
  <div class="rounded-lg bg-slate-900/70 p-3"><dt class="text-slate-500">Failed</dt><dd class="text-xl font-black">{{ $stats['trades_failed'] }}</dd></div>
  <div class="rounded-lg bg-slate-900/70 p-3"><dt class="text-slate-500">Joined</dt><dd class="text-xl font-black">{{ $stats['trades_joined'] }}</dd></div>
  <div class="rounded-lg bg-slate-900/70 p-3"><dt class="text-slate-500">Disputed</dt><dd class="text-xl font-black">{{ $stats['trades_disputed'] }}</dd></div>
</dl>
<h2 class="mb-3 text-lg font-bold">Active listings</h2>
<div class="mb-8 grid gap-4">
  @forelse($activeListings as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @empty
    <p class="text-slate-500">No open listings.</p>
  @endforelse
</div>
<h2 class="mb-3 text-lg font-bold">Completed trades</h2>
<div class="grid gap-4">
  @forelse($completedListings as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @empty
    <p class="text-slate-500">No verified completed trades.</p>
  @endforelse
</div>
@endsection

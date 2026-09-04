@extends('trades.layout')

@section('content')
<div class="mb-6 flex items-center gap-4">
  @if($profile->avatar_url)
    <img src="{{ $profile->avatar_url }}" alt="" width="72" height="72" class="h-18 w-18 rounded-full object-cover">
  @endif
  <div>
    <h1 class="text-2xl font-black">{{ $profile->display_name ?: $profile->username }}</h1>
    <p class="text-slate-400"><span>@</span>{{ $profile->username }} · Roblox ID {{ $profile->roblox_sub }}</p>
    <a class="text-cyan-300 text-sm" href="{{ $profile->profile_url ?: 'https://www.roblox.com/users/'.$profile->roblox_sub.'/profile' }}" rel="nofollow noopener" target="_blank">View on Roblox</a>
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

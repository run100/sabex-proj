@extends('trades.layout')

@section('content')
<h1 class="mb-4 text-2xl font-black">Activity</h1>
<nav class="mb-6 flex flex-wrap gap-2 text-sm">
  @foreach(['all','open','pending','completed','failed','disputed'] as $tab)
    <a href="/activity?status={{ $tab }}" class="rounded-full px-3 py-1 {{ ($status ?? 'all') === $tab ? 'bg-cyan-700 text-cyan-50' : 'border border-white/15 text-slate-300' }}">{{ ucfirst($tab) }}</a>
  @endforeach
</nav>
<div class="grid gap-4">
  @forelse($listings as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @empty
    <p class="text-slate-500">No trades in this tab.</p>
  @endforelse
</div>
@endsection

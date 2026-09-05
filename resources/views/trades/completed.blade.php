@extends('trades.layout')

@section('content')
<h1 class="mb-2 text-2xl font-black">Completed Steal a Brainrot Trades</h1>
<p class="mb-6 text-sm text-slate-400">Only trades confirmed completed by both players. These are market signals, not official prices.</p>
<div class="grid gap-4">
  @forelse($listings as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @empty
    <p class="text-slate-500">No verified completed trades yet.</p>
  @endforelse
</div>
@if(method_exists($listings, 'links'))
  {{ $listings->withQueryString()->onEachSide(1)->links('trades.partials.pagination') }}
@endif
@endsection

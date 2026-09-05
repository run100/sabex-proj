@extends('trades.layout')

@section('content')
<section class="trades-home-hero">
  <h1>Steal a Brainrot Trade Activity</h1>
  <p class="trades-home-hero__lead">Browse recent Steal a Brainrot trading activity, including newly posted, joined, pending, completed and failed trades. Search by Roblox username or user ID to view a player's trade activity.</p>
</section>
<nav class="trades-activity-tabs" aria-label="Trade status">
  @foreach(['all' => 'All', 'pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed'] as $tab => $label)
    <a href="/user/activity?status={{ $tab }}" class="trades-activity-tabs__item{{ ($status ?? 'all') === $tab ? ' is-active' : '' }}">{{ $label }} <span class="trades-activity-tabs__count">{{ $counts[$tab] ?? 0 }}</span></a>
  @endforeach
</nav>
<div class="grid gap-4">
  @forelse($listings as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @empty
    <p class="text-slate-500">No trades in this tab.</p>
  @endforelse
</div>
@if(method_exists($listings, 'links'))
  {{ $listings->withQueryString()->onEachSide(1)->links('trades.partials.pagination') }}
@endif
@endsection

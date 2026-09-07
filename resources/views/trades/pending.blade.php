@extends('trades.layout')

@section('content')
<section class="trades-home-hero">
  <h1>Pending Steal a Brainrot Trades</h1>
  <p class="trades-home-hero__lead">Browse pending Steal a Brainrot trades waiting to be finished in Roblox on SABExistCount.</p>
</section>
<div class="trades-listing-grid">
  @forelse($listings as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @empty
    <p class="text-slate-500">No pending trades right now.</p>
  @endforelse
</div>
@if(method_exists($listings, 'links'))
  {{ $listings->withQueryString()->onEachSide(1)->links('trades.partials.pagination') }}
@endif
@endsection

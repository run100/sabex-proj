@extends('trades.layout')

@section('content')
<p class="text-sm text-slate-400 mb-6">Community listings only. SAB Exist Count is not official Roblox, does not hold items, and does not complete trades. Finish exchanges in Roblox.</p>

@if(!empty($schemaMissing))
<p class="rounded border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-amber-100">Trade tables are not installed yet. Review <code>database/schema/seo-trades.sql</code> before creating them.</p>
@endif

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-2xl font-black">Recent Trades</h1>
  <div class="flex flex-wrap gap-2">
    <a href="/post" class="inline-flex items-center rounded-full bg-cyan-700 px-4 py-2 text-sm font-bold text-cyan-50 hover:bg-cyan-600">Post a Trade</a>
    <a href="/activity" class="inline-flex items-center rounded-full border border-white/15 px-4 py-2 text-sm font-bold text-slate-200 hover:border-cyan-500">See Activity</a>
    <a href="/completed" class="inline-flex items-center rounded-full border border-white/15 px-4 py-2 text-sm font-bold text-slate-200 hover:border-cyan-500">Completed Trades</a>
  </div>
</div>

@if(!$schemaMissing && ($listings ?? collect())->isEmpty())
<p class="text-slate-500">No open listings yet. Post the first one.</p>
@endif

<div class="grid gap-4">
  @foreach($listings ?? [] as $listing)
    @include('trades.partials.card', ['listing' => $listing])
  @endforeach
</div>

@if(isset($listings) && method_exists($listings, 'links'))
  <div class="mt-6 text-sm text-slate-400">{{ $listings->links() }}</div>
@endif
@endsection

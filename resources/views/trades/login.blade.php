@extends('trades.layout')

@section('content')
<h1 class="text-2xl font-black mb-3">Sign in to SAB Trades</h1>
<p class="text-slate-400 mb-6 max-w-xl">Use official Roblox OAuth. We never ask for your Roblox password.</p>
@if(!empty($schemaMissing))
<p class="mb-4 rounded border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-amber-100">Trade tables are not installed yet. Review <code>database/schema/seo-trades.sql</code> first.</p>
@elseif(!empty($oauthReady))
<form method="post" action="/auth/roblox" class="trades-card max-w-xl rounded-xl border border-white/10 bg-slate-900/70">
  @csrf
  <h2 class="mb-3 text-lg font-bold">Roblox</h2>
  <p class="mb-4 text-sm text-slate-400">Store your Roblox user id, username, and avatar.</p>
  <button type="submit" class="trades-btn">Continue with Roblox</button>
</form>
@elseif(app()->isLocal())
<p class="text-slate-400">Set <code>ROBLOX_CLIENT_ID</code>, <code>ROBLOX_CLIENT_SECRET</code>, and <code>ROBLOX_REDIRECT_URI</code> in <code>.env</code> after creating an OAuth app in the Roblox Creator Dashboard.</p>
@else
<p class="text-slate-400">Roblox sign-in is unavailable right now.</p>
@endif
@endsection

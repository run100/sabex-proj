@extends('trades.layout')

@section('content')
<h1 class="text-2xl font-black mb-3">Sign in with Roblox</h1>
<p class="text-slate-400 mb-6 max-w-xl">Use official Roblox OAuth to post listings. We store your Roblox user id, username, and avatar. We never ask for your password.</p>
@if(!empty($schemaMissing))
<p class="mb-4 rounded border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-amber-100">Trade tables are not installed yet. Review <code>database/schema/seo-trades.sql</code> first.</p>
@elseif(empty($oauthReady))
<p class="mb-4 text-slate-400">Set <code>ROBLOX_CLIENT_ID</code>, <code>ROBLOX_CLIENT_SECRET</code>, and <code>ROBLOX_REDIRECT_URI</code> in <code>.env</code> after creating an OAuth app in the Roblox Creator Dashboard.</p>
@else
<form method="post" action="/auth/roblox">
  @csrf
  <button type="submit" class="rounded-full bg-cyan-700 px-5 py-2 font-bold text-cyan-50 hover:bg-cyan-600">Continue with Roblox</button>
</form>
@endif
@endsection

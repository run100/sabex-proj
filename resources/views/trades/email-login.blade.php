@extends('trades.layout')

@section('content')
<h1 class="text-2xl font-black mb-3">Sign in with email</h1>
<p class="text-slate-400 mb-6 max-w-xl">Use your email and password.@if(\App\Support\TradeEmailAuth::registerAllowed()) New here? <a href="/auth/register" class="text-cyan-300">Create an account</a>.@endif</p>
@if(!empty($schemaMissing) || empty($identitiesReady))
<p class="mb-4 rounded border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-amber-100">Email sign-in is not installed yet. Review <code>database/schema/seo-trades-alter-auth-accounts.sql</code>.</p>
@else
<div class="max-w-md">
  @include('trades.partials.email-login-form', ['emailInputId' => 'email-standalone'])
  <p class="mt-4 text-sm text-slate-500"><a href="/auth/roblox" class="text-cyan-300">Other sign-in options</a></p>
</div>
@endif
@endsection

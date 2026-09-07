@extends('trades.layout')

@section('content')
<h1 class="text-2xl font-black mb-3">Create a SAB Trades account</h1>
<p class="text-slate-400 mb-6 max-w-xl">Choose a username, then confirm your email with the link we send. After that you can sign in with email and password.</p>
@if(!empty($schemaMissing) || empty($identitiesReady))
<p class="mb-4 rounded border border-amber-700/50 bg-amber-950/40 px-4 py-3 text-amber-100">Email sign-in is not installed yet. Review <code>database/schema/seo-trades-alter-auth-accounts.sql</code>.</p>
@else
<div class="max-w-md">
  <form method="post" action="/auth/register" class="trades-card rounded-xl border border-white/10 bg-slate-900/70">
    @csrf
    <h2 class="mb-3 text-lg font-bold">Register</h2>
    <div class="trades-field">
      <label for="register-username">Username</label>
      <input id="register-username" name="username" required minlength="3" maxlength="32" pattern="[A-Za-z0-9_]+" autocomplete="username" value="{{ old('username') }}">
    </div>
    <div class="trades-field">
      <label for="register-email">Email</label>
      <input id="register-email" type="email" name="email" required maxlength="255" autocomplete="email" value="{{ old('email') }}">
    </div>
    <div class="trades-field">
      <label for="register-password">Password</label>
      <input id="register-password" type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password">
    </div>
    <div class="trades-field">
      <label for="register-password-confirmation">Confirm password</label>
      <input id="register-password-confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password">
    </div>
    @error('username')<p class="mb-2 text-sm text-rose-300">{{ $message }}</p>@enderror
    @error('email')
      @if($message === \App\Support\TradeEmailAuth::EMAIL_ALREADY_REGISTERED)
        <p class="mb-2 rounded border border-amber-700/50 bg-amber-950/40 px-3 py-2 text-sm text-amber-100">{{ $message }}</p>
      @else
        <p class="mb-2 text-sm text-rose-300">{{ $message }}</p>
      @endif
    @enderror
    @error('password')<p class="mb-2 text-sm text-rose-300">{{ $message }}</p>@enderror
    <button type="submit" class="trades-btn">Create account</button>
  </form>
  <p class="mt-4 text-sm text-slate-500">Already have an account? <a href="/auth/roblox" class="text-cyan-300">Sign in</a></p>
</div>
@endif
@endsection

@extends('trades.layout')

@section('content')
<h1 class="text-2xl font-black mb-3">Check your email</h1>
<p class="text-slate-400 mb-6 max-w-xl">We sent a verification link to <strong class="text-slate-100">{{ $email }}</strong>. Click it to finish. The link expires in 24 hours.</p>
<div class="max-w-md">
  <form method="post" action="/auth/email/resend" class="trades-card rounded-xl border border-white/10 bg-slate-900/70">
    @csrf
    <input type="hidden" name="email" value="{{ $email }}">
    <p class="mb-4 text-sm text-slate-400">Did not get it? Send the link again.</p>
    @error('email')<p class="mb-3 text-sm text-rose-300">{{ $message }}</p>@enderror
    <button type="submit" class="trades-btn">Resend link</button>
  </form>
  <p class="mt-4 text-sm text-slate-500"><a href="/auth/roblox" class="text-cyan-300">Back to sign in</a></p>
</div>
@endsection

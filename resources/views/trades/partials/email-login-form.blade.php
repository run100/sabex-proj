<form method="post" action="/auth/email" class="trades-card rounded-xl border border-white/10 bg-slate-900/70">
  @csrf
  @php($returnTo = request()->query('return_to', $returnTo ?? ''))
  @if($returnTo !== '')
    <input type="hidden" name="return_to" value="{{ $returnTo }}">
  @endif
  <h2 class="mb-3 text-lg font-bold">Email login</h2>
  <div class="trades-field">
    <label for="{{ $emailInputId ?? 'email-login' }}">Email</label>
    <input id="{{ $emailInputId ?? 'email-login' }}" type="email" name="email" required maxlength="255" autocomplete="email" value="{{ old('email') }}">
  </div>
  <div class="trades-field">
    <label for="{{ ($emailInputId ?? 'email-login') }}-password">Password</label>
    <input id="{{ ($emailInputId ?? 'email-login') }}-password" type="password" name="password" required autocomplete="current-password">
  </div>
  @error('email')<p class="mb-3 text-sm text-rose-300">{{ $message }}</p>@enderror
  <button type="submit" class="trades-btn">Sign in</button>
</form>

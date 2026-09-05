@php
  $www = $wwwOrigin ?? \App\Support\SabHost::origin('www');
  $tradeUser = $tradeUser ?? $user ?? auth('trades')->user();
  $unread = $unreadCount ?? 0;
@endphp
<header class="border-b border-white/10 bg-slate-950/90 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ $www }}/" class="text-xl font-black tracking-tight">SAB<span class="text-cyan-400">ExistCount</span>.com</a>
    <nav class="flex flex-wrap items-center gap-4 text-sm text-slate-300">
      <a href="/trading" class="hover:text-cyan-300">Trades</a>
      <a href="/trading/new" class="hover:text-cyan-300">Post</a>
      <a href="/trading/pending" class="hover:text-cyan-300">Pending</a>
      <a href="/trading/completed" class="hover:text-cyan-300">Completed</a>
      <a href="{{ $www }}/steal-a-brainrot-trading-calculator" class="hover:text-cyan-300">Calculator</a>
      <a href="{{ $www }}/" class="hover:text-cyan-300">Exist Count</a>
      @if($tradeUser)
        <a href="/notifications" class="hover:text-cyan-300">Alerts @if($unread > 0)<span class="rounded-full bg-cyan-700 px-1.5 text-xs text-cyan-50">{{ $unread }}</span>@endif</a>
        <a href="/user/activity" class="hover:text-cyan-300">Activity</a>
        <a href="/user" class="hover:text-cyan-300">Account</a>
        @if(\App\Support\TradeProfileAccess::canAccessProfile($tradeUser))
          <a href="{{ $tradeUser->profilePath() }}" class="hover:text-cyan-300">{{ $tradeUser->display_name ?: $tradeUser->username }}</a>
        @else
          <span class="text-slate-200">{{ $tradeUser->display_name ?: $tradeUser->username }}</span>
        @endif
        <form method="post" action="/logout">
          @csrf
          <button type="submit" class="hover:text-cyan-300">Sign out</button>
        </form>
      @else
        <a href="/auth/roblox" class="text-cyan-300">Sign in</a>
      @endif
    </nav>
  </div>
</header>

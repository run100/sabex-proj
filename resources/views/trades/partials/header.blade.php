@php
  $www = $wwwOrigin ?? \App\Support\SabHost::origin('www');
  $tradeUser = $tradeUser ?? $user ?? auth('trades')->user();
  $unread = $unreadCount ?? 0;
@endphp
<header class="border-b border-white/10 bg-slate-950/90 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3">
    <a href="/" class="text-xl font-black tracking-tight">SAB<span class="text-cyan-400">Trades</span></a>
    <nav class="flex flex-wrap items-center gap-4 text-sm text-slate-300">
      <a href="/" class="hover:text-cyan-300">Trades</a>
      <a href="/post" class="hover:text-cyan-300">Post</a>
      <a href="/completed" class="hover:text-cyan-300">Completed</a>
      <a href="{{ $www }}/steal-a-brainrot-trading-calculator" class="hover:text-cyan-300">Calculator</a>
      <a href="{{ $www }}/" class="hover:text-cyan-300">Exist Count</a>
      @if($tradeUser)
        <a href="/notifications" class="hover:text-cyan-300">Alerts @if($unread > 0)<span class="rounded-full bg-cyan-700 px-1.5 text-xs text-cyan-50">{{ $unread }}</span>@endif</a>
        <a href="/activity" class="hover:text-cyan-300">Activity</a>
        <a href="/u/{{ $tradeUser->roblox_sub }}" class="hover:text-cyan-300">{{ $tradeUser->display_name ?: $tradeUser->username }}</a>
        <form method="post" action="/logout">
          @csrf
          <button type="submit" class="hover:text-cyan-300">Sign out</button>
        </form>
      @else
        <a href="/auth/roblox" class="text-cyan-300">Sign in with Roblox</a>
      @endif
    </nav>
  </div>
</header>

@php
  $emailLoginAllowed = $emailLoginAllowed ?? \App\Support\TradeEmailAuth::loginAllowed();
  $registerAllowed = $registerAllowed ?? \App\Support\TradeEmailAuth::registerAllowed();
  $linkClass = $linkClass ?? 'trades-auth-modal__link';
@endphp
@if(!empty($emailLoginAllowed))
<a href="{{ \App\Support\TradePresenter::wwwUrl('/auth/email') }}" class="{{ $linkClass }}">Email login</a>
@endif
@if(!empty($registerAllowed))
<a href="{{ \App\Support\TradePresenter::wwwUrl('/auth/register') }}" class="{{ $linkClass }}">Create an account</a>
@endif

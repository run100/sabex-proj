@php
  $avatarUrl = trim((string) ($url ?? ''));
  $avatarSize = (int) ($size ?? 28);
  $avatarClass = 'trades-avatar'.($avatarSize >= 64 ? ' trades-avatar--lg' : '');
@endphp
@if($avatarUrl !== '')
  <img class="{{ $avatarClass }}" src="{{ $avatarUrl }}" alt="" width="{{ $avatarSize }}" height="{{ $avatarSize }}">
@else
  <img class="{{ $avatarClass }} trades-avatar--default" src="/static/img/trades-default-avatar.webp" alt="" width="{{ $avatarSize }}" height="{{ $avatarSize }}">
@endif

@php
  $schemaMissing = $schemaMissing ?? ! \App\Support\TradeSchema::ready();
  $oauthReady = $oauthReady ?? app(\App\Services\Trades\RobloxOAuthService::class)->configured();
  $emailLoginAllowed = $emailLoginAllowed ?? \App\Support\TradeEmailAuth::loginAllowed();
  $oauthUnavailableCopy = $oauthUnavailableCopy ?? \App\Support\TradePresenter::oauthUnavailableCopy();
  $returnTo = request()->getRequestUri();
  if ($returnTo === '' || ! str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//')) {
      $returnTo = '/';
  }
  $robloxStartHref = \App\Support\TradePresenter::wwwUrl('/auth/roblox', [
      'start' => '1',
      'return_to' => $returnTo,
  ]);
@endphp
<div
  class="trades-auth-modal"
  data-roblox-auth-modal
  data-default-title="Sign in"
  data-default-copy="Sign in to post and manage trade ads."
  aria-hidden="true"
>
  <div class="trades-auth-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="trades-auth-modal-title">
    <button type="button" class="trades-auth-modal__close" data-auth-modal-close aria-label="Close">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
      </svg>
    </button>
    <p id="trades-auth-modal-title" class="trades-auth-modal__title" data-auth-modal-title>Sign in</p>
    <p class="trades-auth-modal__copy" data-auth-modal-copy>Sign in to post and manage trade ads.</p>
    @if(!empty($schemaMissing))
      <p class="trades-auth-modal__status">Trade tables are not installed yet.</p>
    @else
      @if(empty($oauthReady))
        <p class="trades-auth-modal__status">{{ $oauthUnavailableCopy }}</p>
      @endif
      @if(!empty($oauthReady) || app()->isLocal())
        <a href="{{ $robloxStartHref }}" class="trades-auth-modal__roblox">
          <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="currentColor" d="M5.4 3.2 20.8 7.4l-4.2 13.4L1.2 16.6 5.4 3.2Zm6.3 6.1-2.4 7.4 7.3 2.1 2.4-7.4-7.3-2.1Z"/>
          </svg>
          Continue with Roblox
        </a>
      @endif
    @endif
    @include('trades.partials.auth-actions', ['emailLoginAllowed' => $emailLoginAllowed])
    <p class="trades-auth-modal__legal">
      By continuing, you agree to our
      <a href="{{ \App\Support\TradePresenter::wwwUrl('/terms-of-service') }}">Terms of Service</a>
      and
      <a href="{{ \App\Support\TradePresenter::wwwUrl('/privacy-policy') }}">Privacy Policy</a>.
    </p>
  </div>
</div>
@once
<style>
  .trades-auth-modal {
    --trades-cyan: #0e7490;
    --trades-cyan-hover: #0891b2;
    position: fixed;
    inset: 0;
    z-index: 140;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(2, 6, 23, 0.78);
    font-family: inherit;
  }
  .trades-auth-modal.is-open {
    display: flex;
  }
  .trades-auth-modal__dialog {
    position: relative;
    width: min(34rem, calc(100% - 2rem));
    padding: 2.1rem 1.75rem 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 1rem;
    background: #0b1220;
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.5);
  }
  .trades-auth-modal__close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    display: inline-flex;
    width: 2rem;
    height: 2rem;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 9999px;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
  }
  .trades-auth-modal__close svg {
    width: 1.1rem;
    height: 1.1rem;
    flex-shrink: 0;
  }
  .trades-auth-modal__close:hover {
    color: #fff;
    background: rgba(148, 163, 184, 0.12);
  }
  .trades-auth-modal__title {
    margin: 0;
    color: #fff;
    font-size: 1.6rem;
    font-weight: 700;
  }
  .trades-auth-modal__copy {
    margin: 0.45rem 0 1.15rem;
    color: #94a3b8;
    font-size: 0.92rem;
    line-height: 1.5;
  }
  .trades-auth-modal__roblox,
  .trades-auth-modal__link {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    min-height: 3.25rem;
    margin-top: 0.65rem;
    border-radius: 0.75rem;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: none;
    box-sizing: border-box;
  }
  .trades-auth-modal__roblox {
    border: 0;
    background: var(--trades-cyan);
    color: #ecfeff;
  }
  .trades-auth-modal__roblox svg {
    width: 1.15rem;
    height: 1.15rem;
    flex-shrink: 0;
  }
  .trades-auth-modal__roblox:hover {
    background: var(--trades-cyan-hover);
  }
  .trades-auth-modal__link {
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: transparent;
    color: #e2e8f0;
  }
  .trades-auth-modal__link:first-of-type {
    margin-top: 0.85rem;
  }
  .trades-auth-modal__link:hover {
    border-color: #67e8f9;
    color: #ecfeff;
    background: rgba(14, 116, 144, 0.18);
  }
  .trades-auth-modal__status {
    margin: 0 0 0.85rem;
    padding: 0.55rem 1.1rem;
    border: 1px solid rgba(148, 163, 184, 0.4);
    border-radius: 9999px;
    color: #94a3b8;
    font-size: 0.75rem;
    line-height: 1.5;
    text-align: center;
  }
  .trades-auth-modal__legal {
    margin: 0.9rem 0 0;
    color: #94a3b8;
    font-size: 0.75rem;
    line-height: 1.5;
    text-align: center;
  }
  .trades-auth-modal__legal a {
    color: #67e8f9;
    font-weight: 700;
  }
  .trades-auth-modal__legal a:hover {
    color: #a5f3fc;
  }
  body.trades-auth-modal-open .trades-publish-bar,
  body.trades-auth-modal-open .sab-back-to-top {
    display: none;
  }
</style>
@endonce

@extends('trades.layout')

@section('content')
@php
  $calcUi = $calculatorUi ?? [];
  $noteMax = 280;
@endphp
@include('seo.sab.partials._calculator-styles')
<style>
  .trades-create svg,
  .trades-publish-bar svg,
  .trades-auth-modal svg {
    width: 18px !important;
    height: 18px !important;
    max-width: 18px !important;
    max-height: 18px !important;
    flex: 0 0 18px !important;
  }
  .trades-create-slot.is-add svg {
    width: 24px !important;
    height: 24px !important;
    max-width: 24px !important;
    max-height: 24px !important;
    flex: 0 0 24px !important;
  }
  .trades-publish-btn {
    overflow: hidden !important;
    max-height: 52px !important;
  }
  @media (min-width: 768px) {
    .trades-publish-btn {
      width: auto !important;
      max-width: 20rem !important;
    }
  }
</style>

<section
  class="trades-create"
  data-calculator-root
  data-builder-layout="grid"
  data-calculator-theme="default"
>
  <header class="trades-create__header">
    <div>
      <h1 class="trades-create__title">Create Trade Ad</h1>
      <p class="trades-create__subtitle">Set up your trade offer and find the perfect match for your items.</p>
    </div>
    <button type="button" class="trades-create__reset" data-clear>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5h6v2M8 7l.8 12h6.4L16 7"/>
      </svg>
      Reset
    </button>
  </header>

  <div class="trades-create__board">
    <section class="trades-create__side" data-side="offer">
      <h2 class="trades-create__side-title">
        <span class="trades-create__dot trades-create__dot--have" aria-hidden="true"></span>
        I Have
      </h2>
      <div class="trades-create__stats" data-totals></div>
      <div class="trades-create__slots" data-slots></div>
    </section>
    <section class="trades-create__side" data-side="receive">
      <h2 class="trades-create__side-title">
        <span class="trades-create__dot trades-create__dot--want" aria-hidden="true"></span>
        I Want
      </h2>
      <div class="trades-create__stats" data-totals></div>
      <div class="trades-create__slots" data-slots></div>
    </section>
  </div>

  <div class="trades-create__note">
    <div class="trades-create__note-head">
      <label for="trade-note">Add a note</label>
      <span><span data-note-count>0</span>/{{ $noteMax }}</span>
    </div>
    <p class="trades-create__note-help">Optional — what you are looking for, in one line.</p>
    <input
      id="trade-note"
      type="text"
      maxlength="{{ $noteMax }}"
      data-trade-note
      placeholder="e.g. Looking for a quick trade, collector items only"
    >
  </div>

  <aside class="trades-create__warning">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v6.2c0 4.6 3.1 8.2 7.5 8.8 4.4-.6 7.5-4.2 7.5-8.8V6L12 3z"/>
      <path stroke-linecap="round" d="M12 9v4M12 16h.01"/>
    </svg>
    <p>No cross-trading. Items can only be traded for other in-game items. Trading for Robux, real money, gift cards, or items from other games or platforms is against Roblox's rules and is not allowed on this website.</p>
  </aside>

  <div class="trades-publish-bar trades-create__bar" data-publish-bar>
    @if(!empty($schemaMissing))
      <p class="trades-publish-bar__hint">Trade tables are not installed yet. Review database/schema/seo-trades.sql first.</p>
    @else
      <p class="trades-publish-bar__hint" data-publish-pending hidden></p>
      <div class="trades-create__bar-fields" data-publish-fields>
        <p class="trades-create__bar-hint" data-publish-ready-hint>Add at least one item to each side before publishing.</p>
        <button type="button" class="trades-publish-btn" data-publish-trade>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 4 10.5 14.5M21 4l-6.5 17-3.8-8.7L2 8.5 21 4z"/>
          </svg>
          Publish Trade Ad
        </button>
      </div>
      <p class="trades-publish-bar__status" data-publish-status></p>
    @endif
  </div>

  @include('seo.sab.partials._brainrot-picker')
</section>
@endsection

@section('scripts')
@include('seo.sab.partials._trade-builder-boot')
@endsection

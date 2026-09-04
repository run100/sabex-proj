@extends('seo.sab.layout')

@section('content')
@php
  $productPrefix = rtrim((string) ($productUrlPrefix ?? ''), '/');
  $pagePrefix = rtrim((string) ($urlPrefix ?? ''), '/');
  $englishPrefix = rtrim((string) ($englishUrlPrefix ?? ''), '/');
  $homeHref = $pagePrefix === '' ? '/' : $pagePrefix;
  $productHref = fn (string $slug): string => $productPrefix . '/products/' . $slug . '.html';
  $pageHref = fn (string $slug): string => $pagePrefix . '/' . $slug;
  $englishPageHref = fn (string $slug): string => $englishPrefix . '/' . $slug;
  $newsHref = fn (string $slug): string => $englishPrefix . '/news/' . $slug;
  $c = $codesCopy ?? [];
@endphp

<style>
  .sab-codes-page { color: #cbd5e1; }
  .sab-codes-breadcrumb {
    display: flex;
    align-items: center;
    gap: .45rem;
    margin-bottom: 1.25rem;
    color: #94a3b8;
    font-size: .8rem;
  }
  .sab-codes-breadcrumb a { color: #67e8f9; text-decoration: none; }
  .sab-codes-hero {
    border-bottom: 1px solid rgba(148, 163, 184, .18);
    padding: 1.25rem 0 1.75rem;
  }
  .sab-codes-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    color: #86efac;
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: 0;
    text-transform: uppercase;
  }
  .sab-codes-eyebrow::before {
    width: .48rem;
    height: .48rem;
    border-radius: 9999px;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, .14);
    content: "";
  }
  .sab-codes-hero h1 {
    max-width: none;
    margin: .65rem 0 0;
    color: #f8fafc;
    font-size: 2.625rem;
    font-weight: 900;
    line-height: 1.06;
    letter-spacing: 0;
  }
  .sab-codes-hero p {
    max-width: 48rem;
    margin: .85rem 0 0;
    color: #cbd5e1;
    font-size: 1rem;
    line-height: 1.75;
  }
  .sab-codes-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem 1.25rem;
    margin-top: 1rem;
    color: #94a3b8;
    font-size: .8rem;
  }
  .sab-codes-meta strong { color: #67e8f9; }
  .sab-codes-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 2rem;
    margin-top: 2rem;
  }
  .sab-codes-section {
    scroll-margin-top: 6rem;
    padding: 0 0 2.5rem;
  }
  .sab-codes-section + .sab-codes-section {
    border-top: 1px solid rgba(148, 163, 184, .14);
    padding-top: 2.25rem;
  }
  .sab-codes-section h2 {
    margin: 0;
    color: #f1f5f9;
    font-size: 1.5rem;
    font-weight: 900;
    line-height: 1.25;
  }
  .sab-codes-section h3 {
    margin: 1.4rem 0 .45rem;
    color: #e2e8f0;
    font-size: 1rem;
    font-weight: 800;
  }
  .sab-codes-lede {
    max-width: 52rem;
    margin: .65rem 0 0;
    color: #94a3b8;
    font-size: .9rem;
    line-height: 1.7;
  }
  .sab-codes-card {
    display: grid;
    grid-template-columns: 72px minmax(0, 1fr) auto;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    border: 1px solid rgba(74, 222, 128, .3);
    border-radius: .5rem;
    background: rgba(20, 83, 45, .13);
    padding: 1rem;
  }
  .sab-codes-card img {
    width: 72px;
    height: 72px;
    border-radius: .5rem;
    background: rgba(15, 23, 42, .7);
    object-fit: cover;
  }
  .sab-codes-status {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: #86efac;
    font-size: .7rem;
    font-weight: 900;
    text-transform: uppercase;
  }
  .sab-codes-status::before {
    width: .42rem;
    height: .42rem;
    border-radius: 9999px;
    background: #4ade80;
    content: "";
  }
  .sab-codes-code {
    display: block;
    margin-top: .3rem;
    overflow-wrap: anywhere;
    color: #f8fafc;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 1.15rem;
    font-weight: 900;
  }
  .sab-codes-reward {
    margin-top: .35rem;
    color: #cbd5e1;
    font-size: .84rem;
    line-height: 1.55;
  }
  .sab-codes-reward a, .sab-codes-lede a, .sab-codes-body-link {
    color: #67e8f9;
    text-decoration: none;
  }
  .sab-codes-copy {
    min-width: 5.25rem;
    min-height: 2.5rem;
    border: 1px solid rgba(103, 232, 249, .42);
    border-radius: .5rem;
    background: rgba(8, 145, 178, .16);
    color: #a5f3fc;
    cursor: pointer;
    font-size: .78rem;
    font-weight: 900;
  }
  .sab-codes-copy:hover { background: rgba(8, 145, 178, .26); }
  .sab-codes-copy[data-copied="true"] {
    border-color: rgba(74, 222, 128, .45);
    background: rgba(22, 163, 74, .2);
    color: #bbf7d0;
  }
  .sab-codes-note {
    margin-top: .75rem;
    border-left: 3px solid #f59e0b;
    background: rgba(120, 53, 15, .14);
    padding: .75rem .9rem;
    color: #fde68a;
    font-size: .82rem;
    line-height: 1.6;
  }
  .sab-codes-pool {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .65rem;
    margin-top: 1rem;
  }
  .sab-codes-pool-item {
    display: flex;
    align-items: center;
    gap: .65rem;
    min-height: 4rem;
    border: 1px solid rgba(148, 163, 184, .18);
    border-radius: .5rem;
    background: rgba(15, 23, 42, .55);
    padding: .65rem;
    color: #e2e8f0;
    font-size: .82rem;
    font-weight: 800;
    text-decoration: none;
  }
  .sab-codes-pool-item img {
    width: 44px;
    height: 44px;
    border-radius: .375rem;
    background: rgba(30, 41, 59, .85);
    object-fit: cover;
  }
  .sab-codes-luck {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: .375rem;
    background: rgba(34, 197, 94, .16);
    color: #86efac;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .72rem;
    font-weight: 900;
  }
  .sab-codes-table-wrap {
    margin-top: 1rem;
    overflow-x: auto;
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: .5rem;
  }
  .sab-codes-table {
    width: 100%;
    min-width: 39rem;
    border-collapse: collapse;
    background: rgba(15, 23, 42, .42);
    text-align: left;
  }
  .sab-codes-table th {
    background: rgba(30, 41, 59, .78);
    color: #cbd5e1;
    font-size: .72rem;
    font-weight: 900;
    text-transform: uppercase;
  }
  .sab-codes-table th, .sab-codes-table td {
    border-bottom: 1px solid rgba(148, 163, 184, .12);
    padding: .7rem .85rem;
  }
  .sab-codes-table tbody tr:last-child td { border-bottom: 0; }
  .sab-codes-table td {
    color: #94a3b8;
    font-size: .8rem;
  }
  .sab-codes-table code {
    color: #e2e8f0;
    font-size: .78rem;
    font-weight: 800;
  }
  .sab-codes-steps {
    display: grid;
    gap: .65rem;
    margin: 1rem 0 0;
    padding: 0;
    list-style: none;
  }
  .sab-codes-steps li {
    display: grid;
    grid-template-columns: 2.25rem minmax(0, 1fr);
    gap: .75rem;
    align-items: start;
    color: #cbd5e1;
    font-size: .88rem;
    line-height: 1.65;
  }
  .sab-codes-steps b {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border: 1px solid rgba(34, 211, 238, .32);
    border-radius: .5rem;
    background: rgba(8, 145, 178, .12);
    color: #67e8f9;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .75rem;
  }
  .sab-codes-screenshot {
    margin: 1.15rem 0 0;
  }
  .sab-codes-screenshot img {
    display: block;
    width: 100%;
    height: auto;
    border: 1px solid rgba(148, 163, 184, .2);
    border-radius: .5rem;
  }
  .sab-codes-screenshot figcaption {
    margin-top: .45rem;
    color: #64748b;
    font-size: .72rem;
  }
  .sab-codes-checklist {
    display: grid;
    gap: .55rem;
    margin: 1rem 0 0;
    padding: 0;
    list-style: none;
  }
  .sab-codes-checklist li {
    border-left: 2px solid rgba(34, 211, 238, .42);
    padding-left: .75rem;
    color: #cbd5e1;
    font-size: .86rem;
    line-height: 1.6;
  }
  .sab-codes-faq {
    display: grid;
    gap: .6rem;
    margin-top: 1rem;
  }
  .sab-codes-faq details {
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: .5rem;
    background: rgba(15, 23, 42, .42);
    padding: .8rem .9rem;
  }
  .sab-codes-faq summary {
    color: #e2e8f0;
    cursor: pointer;
    font-size: .88rem;
    font-weight: 800;
  }
  .sab-codes-faq p {
    margin: .6rem 0 0;
    color: #94a3b8;
    font-size: .82rem;
    line-height: 1.65;
  }
  .sab-codes-sidebar {
    display: grid;
    align-content: start;
    gap: .85rem;
  }
  .sab-codes-side-panel {
    border: 1px solid rgba(148, 163, 184, .16);
    border-radius: .5rem;
    background: rgba(15, 23, 42, .5);
    padding: .9rem;
  }
  .sab-codes-side-panel h2 {
    margin: 0;
    color: #e2e8f0;
    font-size: .82rem;
    font-weight: 900;
  }
  .sab-codes-side-panel nav {
    display: grid;
    gap: .2rem;
    margin-top: .55rem;
  }
  .sab-codes-side-panel a {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    padding: .42rem 0;
    color: #94a3b8;
    font-size: .78rem;
    text-decoration: none;
  }
  .sab-codes-side-panel a:hover { color: #67e8f9; }
  .sab-codes-side-panel p {
    margin: .55rem 0 0;
    color: #94a3b8;
    font-size: .76rem;
    line-height: 1.6;
  }
  @media (min-width: 760px) {
    .sab-codes-pool { grid-template-columns: repeat(4, minmax(0, 1fr)); }
  }
  @media (min-width: 1024px) {
    .sab-codes-hero--en h1 { white-space: nowrap; }
    .sab-codes-layout { grid-template-columns: minmax(0, 1fr) 17rem; }
    .sab-codes-sidebar { position: sticky; top: 6.25rem; }
  }
  @media (max-width: 560px) {
    .sab-codes-hero h1 { font-size: 2rem; }
    .sab-codes-card {
      grid-template-columns: 58px minmax(0, 1fr);
      gap: .75rem;
    }
    .sab-codes-card img { width: 58px; height: 58px; }
    .sab-codes-copy { grid-column: 1 / -1; width: 100%; }
    .sab-codes-pool { grid-template-columns: minmax(0, 1fr); }
  }
</style>

<div class="sab-codes-page">
  <nav class="sab-codes-breadcrumb" aria-label="{{ $c['breadcrumb_codes'] }}">
    <a href="{{ $homeHref }}">{{ $c['breadcrumb_home'] }}</a>
    <span aria-hidden="true">/</span>
    <span>{{ $c['breadcrumb_codes'] }}</span>
  </nav>

  <header class="sab-codes-hero @if(($locale ?? 'en') === 'en') sab-codes-hero--en @endif">
    <span class="sab-codes-eyebrow">{{ $c['eyebrow'] }}</span>
    <h1>{{ $c['h1'] }}</h1>
    <p>{{ $c['hero_intro'] }}</p>
    <div class="sab-codes-meta">
      <strong>{{ $c['active_count'] }}</strong>
      <span>{{ $updatedLabelBeforeDate }}<time datetime="{{ $verifiedAt }}">{{ $verifiedLabel }}</time>{{ $updatedLabelAfterDate }}</span>
      <span>{{ $c['expired_count'] }}</span>
    </div>
  </header>

  <div class="sab-codes-layout">
    <div>
      <section id="active-codes" class="sab-codes-section">
        <h2>{{ $c['active_title'] }}</h2>
        <p class="sab-codes-lede">{{ $c['active_intro'] }}</p>

        @foreach($activeCodes as $entry)
        <article class="sab-codes-card">
          <img src="{{ $entry['reward_image'] }}" alt="{{ $entry['reward'] }}" width="72" height="72">
          <div>
            <span class="sab-codes-status">{{ $c['active_status'] }}</span>
            <code class="sab-codes-code">{{ $entry['code'] }}</code>
            <p class="sab-codes-reward">
              {{ $c['reward_label'] }}
              <a href="{{ $productHref($entry['reward_slug']) }}">{{ $entry['reward'] }}</a>.
              {{ $entry['note'] }}
            </p>
          </div>
          <button type="button" class="sab-codes-copy" data-copy-code="{{ $entry['code'] }}" data-copied="false" aria-label="{{ strtr($c['copy_aria'], ['{code}' => $entry['code']]) }}">
            <span data-copy-label>{{ $c['copy_code'] }}</span>
          </button>
        </article>
        @endforeach

        <div class="sab-codes-note">
          {{ $c['active_warning'] }}
        </div>
      </section>

      <section id="dlc-codes" class="sab-codes-section">
        <h2>{{ $c['dlc_title'] }}</h2>
        <p class="sab-codes-lede">{{ $randomDlc['description'] }}</p>
        <p class="sab-codes-lede"><strong>{{ $c['dlc_no_public'] }}</strong> {{ $c['dlc_explanation'] }}</p>

        <div class="sab-codes-pool" aria-label="{{ $c['dlc_pool_aria'] }}">
          @foreach($randomDlc['possible_rewards'] as $reward)
            @if(!empty($reward['slug']))
            <a class="sab-codes-pool-item" href="{{ $productHref($reward['slug']) }}">
              <img src="{{ $reward['image'] }}" alt="" width="44" height="44" loading="lazy" decoding="async">
              <span>{{ $reward['name'] }}</span>
            </a>
            @else
            <a class="sab-codes-pool-item" href="{{ $reward['wiki_url'] }}" target="_blank" rel="nofollow noopener noreferrer">
              <span class="sab-codes-luck" aria-hidden="true">{{ str_starts_with($reward['name'], '2x') ? '2x' : '4x' }}</span>
              <span>{{ $reward['name'] }}</span>
            </a>
            @endif
          @endforeach
        </div>

        <div class="sab-codes-note">
          {{ $c['generator_warning'] }}
        </div>
      </section>

      <section id="expired-codes" class="sab-codes-section">
        <h2>{{ $c['expired_title'] }}</h2>
        <p class="sab-codes-lede">{{ $c['expired_intro'] }}</p>
        <div class="sab-codes-table-wrap">
          <table class="sab-codes-table">
            <thead>
              <tr>
                <th scope="col">{{ $c['expired_code_header'] }}</th>
                <th scope="col">{{ $c['former_reward_header'] }}</th>
                <th scope="col">{{ $c['status_header'] }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($expiredCodes as $entry)
              <tr>
                <td><code>{{ $entry['code'] }}</code></td>
                <td>{{ $entry['reward'] }}</td>
                <td>{{ $c['expired_status'] }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>

      <section id="redeem" class="sab-codes-section">
        <h2>{{ $c['redeem_title'] }}</h2>
        <p class="sab-codes-lede">{{ $c['redeem_intro'] }}</p>
        <ol class="sab-codes-steps">
          <li><b>01</b><span>{{ $c['redeem_step_1_before'] }}<a class="sab-codes-body-link" href="https://www.roblox.com/games/109983668079237/Steal-a-Brainrot" target="_blank" rel="nofollow noopener noreferrer">{{ $c['redeem_step_1_link'] }}</a>{{ $c['redeem_step_1_after'] }}</span></li>
          <li><b>02</b><span>{{ $c['redeem_step_2_before'] }}<strong>Codes</strong>{{ $c['redeem_step_2_after'] }}</span></li>
          <li><b>03</b><span>{{ $c['redeem_step_3'] }}</span></li>
          <li><b>04</b><span>{{ $c['redeem_step_4_before'] }}<strong>Submit</strong>{{ $c['redeem_step_4_after'] }}</span></li>
        </ol>
        <figure class="sab-codes-screenshot">
          <img src="{{ $redeemScreenshot }}" alt="{{ $c['redeem_image_alt'] }}" width="1536" height="882" loading="lazy" decoding="async">
          <figcaption>{{ $c['redeem_image_caption'] }}</figcaption>
        </figure>
      </section>

      <section id="not-working" class="sab-codes-section">
        <h2>{{ $c['not_working_title'] }}</h2>
        <ul class="sab-codes-checklist">
          @foreach($c['not_working_items'] as $item)
          <li><strong>{{ $item['title'] }}</strong> {{ $item['body'] }}</li>
          @endforeach
        </ul>
      </section>

      <section id="more-codes" class="sab-codes-section">
        <h2>{{ $c['more_codes_title'] }}</h2>
        <p class="sab-codes-lede">{{ $c['more_codes_before_wiki'] }}<a href="{{ $wikiUrl }}" target="_blank" rel="nofollow noopener noreferrer">{{ $wikiLabel }}</a>{{ $c['more_codes_after_wiki'] }}</p>
        <p class="sab-codes-lede">{{ $c['trading_before_calculator'] }}<a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR) }}">{{ $c['sidebar_calculator'] }}</a>{{ $c['trading_between_calculator_exist'] }}<a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST) }}">{{ $c['sidebar_exist'] }}</a>{{ $c['trading_between_exist_value'] }}<a href="{{ $englishPageHref(\App\Services\Seo\SabRenderService::PAGE_VALUE_LIST) }}">{{ $c['sidebar_value'] }}</a>{{ $c['trading_after_value'] }}</p>
      </section>

      <section id="faq" class="sab-codes-section">
        <h2>{{ $c['faq_title'] }}</h2>
        <div class="sab-codes-faq">
          @foreach($faqItems as $faq)
          <details>
            <summary>{{ $faq['question'] }}</summary>
            <p>{{ $faq['answer'] }}</p>
          </details>
          @endforeach
        </div>
      </section>
    </div>

    <aside class="sab-codes-sidebar" aria-label="{{ $c['sidebar_aria'] }}">
      <section class="sab-codes-side-panel">
        <h2>{{ $c['sidebar_on_page'] }}</h2>
        <nav>
          <a href="#active-codes"><span>{{ $c['sidebar_active'] }}</span><strong>{{ count($activeCodes) }}</strong></a>
          <a href="#dlc-codes"><span>{{ $c['sidebar_dlc'] }}</span></a>
          <a href="#expired-codes"><span>{{ $c['sidebar_expired'] }}</span><strong>{{ count($expiredCodes) }}</strong></a>
          <a href="#redeem"><span>{{ $c['sidebar_redeem'] }}</span></a>
          <a href="#not-working"><span>{{ $c['sidebar_not_working'] }}</span></a>
          <a href="#faq"><span>{{ $c['sidebar_faq'] }}</span></a>
        </nav>
      </section>

      <section class="sab-codes-side-panel">
        <h2>{{ $c['sidebar_related'] }}</h2>
        <nav>
          <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR) }}"><span>{{ $c['sidebar_calculator'] }}</span></a>
          <a href="{{ $pageHref(\App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST) }}"><span>{{ $c['sidebar_exist'] }}</span></a>
          <a href="{{ $englishPageHref(\App\Services\Seo\SabRenderService::PAGE_VALUE_LIST) }}"><span>{{ $c['sidebar_value'] }}</span></a>
          <a href="{{ $newsHref('steal-a-brainrot-july-25-2026-crystal-mutation-5-new-brainrots') }}"><span>{{ $c['sidebar_news'] }}</span></a>
        </nav>
      </section>

      <section class="sab-codes-side-panel">
        <h2>{{ $c['sidebar_source'] }}</h2>
        <p>{{ $c['sidebar_checked_before_date'] }}<time datetime="{{ $verifiedAt }}">{{ $verifiedLabel }}</time>{{ $c['sidebar_checked_after_date'] }}</p>
        <nav>
          <a href="{{ $wikiUrl }}" target="_blank" rel="nofollow noopener noreferrer"><span>{{ $wikiLabel }}</span></a>
        </nav>
      </section>
    </aside>
  </div>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    var buttons = document.querySelectorAll('[data-copy-code]');
    var copiedLabel = @json($c['copied']);
    var copyLabel = @json($c['copy_code']);

    function fallbackCopy(value) {
      var field = document.createElement('textarea');
      field.value = value;
      field.setAttribute('readonly', '');
      field.style.position = 'fixed';
      field.style.opacity = '0';
      document.body.appendChild(field);
      field.select();
      var copied = document.execCommand('copy');
      document.body.removeChild(field);
      return copied;
    }

    buttons.forEach(function (button) {
      button.addEventListener('click', function () {
        var code = button.getAttribute('data-copy-code') || '';
        var label = button.querySelector('[data-copy-label]');
        var result = navigator.clipboard && window.isSecureContext
          ? navigator.clipboard.writeText(code).then(function () { return true; })
          : Promise.resolve(fallbackCopy(code));

        result.then(function (copied) {
          if (!copied) return;
          button.setAttribute('data-copied', 'true');
          if (label) label.textContent = copiedLabel;
          window.setTimeout(function () {
            button.setAttribute('data-copied', 'false');
            if (label) label.textContent = copyLabel;
          }, 1800);
        });
      });
    });
  })();
</script>
@endsection

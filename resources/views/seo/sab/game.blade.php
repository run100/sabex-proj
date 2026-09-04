@extends('seo.sab.layout')

@section('content')
@php
  $pagePrefix = rtrim((string) ($urlPrefix ?? ''), '/');
  $homeHref = $pagePrefix === '' ? '/' : $pagePrefix;
  $gamesHref = $pagePrefix . '/' . \App\Services\Seo\SabRenderService::gamesIndexPublicPath();
  $embedUrl = (string) ($gameEmbedUrl ?? \App\Services\Seo\SabRenderService::GAME_EMBED_URL);
  $gameSlug = (string) ($gameSlug ?? \App\Services\Seo\SabRenderService::PAGE_GAME);
  $usesPortraitMobilePlayer = $gameSlug === \App\Services\Seo\SabRenderService::PAGE_GAME_ROB;
  $gameName = (string) ($gameName ?? 'Steal a Brainrot Simulator');
  $gameH1 = (string) ($gameH1 ?? $gameName);
  $coverSrc = (string) ($gameCoverSrc ?? \App\Services\Seo\SabRenderService::GAME_COVER_SRC);
  $sections = is_array($gameSections ?? null) ? $gameSections : [];
  $related = is_array($relatedGames ?? null) ? $relatedGames : [];
  $featuredCharacters = is_array($featuredCharacters ?? null) ? $featuredCharacters : [];
  $recentChanges = is_array($recentValueChanges ?? null) ? $recentValueChanges : [];
  $codesHref = (string) ($codesHref ?? '');
  $valueChangesHref = (string) ($valueChangesHref ?? '');
  $existCountHref = $pagePrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST;
  $calculatorHref = $pagePrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR;
  $valueListHref = $pagePrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST;
@endphp

<style>
  .sab-game-page { color: #cbd5e1; max-width: 100%; min-width: 0; }
  .sab-game-breadcrumb {
    display: flex;
    align-items: center;
    gap: .45rem;
    margin-bottom: 1.25rem;
    color: #94a3b8;
    font-size: .8rem;
  }
  .sab-game-breadcrumb a { color: #67e8f9; text-decoration: none; }
  .sab-game-hero {
    border-bottom: 1px solid rgba(148, 163, 184, .18);
    padding: 0 0 1.5rem;
  }
  .sab-game-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    color: #67e8f9;
    font-size: .72rem;
    font-weight: 900;
    text-transform: uppercase;
  }
  .sab-game-hero h1 {
    margin: .65rem 0 0;
    color: #f8fafc;
    font-size: 2.25rem;
    font-weight: 900;
    line-height: 1.08;
  }
  .sab-game-hero p {
    max-width: 46rem;
    margin: .8rem 0 0;
    color: #cbd5e1;
    font-size: 1rem;
    line-height: 1.7;
  }
  .sab-game-player {
    margin-top: 1.5rem;
    background: #020617;
    border: 1px solid rgba(148, 163, 184, .2);
    width: 100%;
    max-width: 100%;
    min-width: 0;
    position: relative;
    aspect-ratio: 16 / 10;
  }
  .sab-game-player iframe,
  .sab-game-cover {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
  }
  .sab-game-cover {
    align-items: center;
    background-color: #0f172a;
    background-image: url('{{ $coverSrc }}');
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 1rem;
    padding: 1.5rem;
    text-align: center;
    z-index: 2;
  }
  .sab-game-cover::before {
    background: rgba(2, 6, 23, .58);
    content: "";
    inset: 0;
    position: absolute;
  }
  .sab-game-cover > * { position: relative; z-index: 1; }
  .sab-game-cover[hidden] { display: none; }
  .sab-game-cover-title {
    color: #f8fafc;
    font-size: 1.35rem;
    font-weight: 900;
    overflow-wrap: anywhere;
  }
  .sab-game-cover-note {
    max-width: 28rem;
    color: #94a3b8;
    font-size: .85rem;
    line-height: 1.55;
    overflow-wrap: anywhere;
  }
  .sab-game-play {
    background: #0891b2;
    border: 0;
    color: #ecfeff;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 900;
    min-height: 3rem;
    padding: 0 1.75rem;
  }
  .sab-game-play:hover { background: #0e7490; }
  .sab-game-reflow {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .85rem 1.15rem;
    width: 100%;
    margin: 0;
    padding: 1rem 1.15rem;
    background: #0891b2;
    color: #ecfeff;
  }
  .sab-game-reflow-copy {
    display: flex;
    align-items: center;
    gap: .65rem;
    min-width: 0;
  }
  .sab-game-reflow-copy svg {
    flex-shrink: 0;
    width: 1.25rem;
    height: 1.25rem;
    stroke: currentColor;
  }
  .sab-game-reflow-copy p {
    margin: 0;
    font-size: .95rem;
    font-weight: 800;
    line-height: 1.35;
  }
  .sab-game-reflow-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
  }
  .sab-game-reflow-primary {
    display: inline-flex;
    align-items: center;
    min-height: 2.25rem;
    padding: 0 .9rem;
    background: #042f2e;
    color: #67e8f9;
    font-size: .82rem;
    font-weight: 800;
    text-decoration: none;
  }
  .sab-game-reflow-primary:hover { color: #ecfeff; }
  .sab-game-reflow-secondary {
    display: inline-flex;
    align-items: center;
    min-height: 2.25rem;
    padding: 0 .75rem;
    border: 1px solid rgba(236, 254, 255, .45);
    color: #ecfeff;
    font-size: .82rem;
    font-weight: 800;
    text-decoration: none;
  }
  .sab-game-reflow-secondary:hover { border-color: #ecfeff; }
  .sab-game-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-top: .65rem;
  }
  .sab-game-fullscreen {
    background: transparent;
    border: 1px solid rgba(148, 163, 184, .35);
    color: #e2e8f0;
    cursor: pointer;
    font-size: .78rem;
    font-weight: 800;
    min-height: 2.25rem;
    padding: 0 .85rem;
  }
  .sab-game-fullscreen:hover { border-color: #67e8f9; color: #67e8f9; }
  .sab-game-player.is-expanded,
  .sab-game-player:fullscreen,
  .sab-game-player:-webkit-full-screen {
    position: fixed;
    inset: 0;
    width: 100vw;
    height: 100dvh;
    max-width: none;
    aspect-ratio: auto;
    z-index: 80;
    margin: 0;
    border: 0;
  }
  .sab-game-exit {
    display: none;
    position: fixed;
    top: calc(.75rem + env(safe-area-inset-top, 0px));
    right: calc(.75rem + env(safe-area-inset-right, 0px));
    z-index: 81;
    background: rgba(2, 6, 23, .72);
    border: 1px solid rgba(148, 163, 184, .35);
    color: #e2e8f0;
    cursor: pointer;
    font-size: .78rem;
    font-weight: 800;
    min-height: 2.25rem;
    padding: 0 .85rem;
  }
  .sab-game-player.is-expanded .sab-game-exit,
  .sab-game-player:fullscreen .sab-game-exit,
  .sab-game-player:-webkit-full-screen .sab-game-exit {
    display: block;
  }
  .sab-game-fallback {
    margin-top: .75rem;
    color: #94a3b8;
    font-size: .82rem;
    line-height: 1.55;
  }
  .sab-game-fallback a { color: #67e8f9; }
  .sab-game-section {
    border-top: 1px solid rgba(148, 163, 184, .14);
    margin-top: 2rem;
    padding-top: 1.75rem;
  }
  .sab-game-section h2 {
    margin: 0;
    color: #f1f5f9;
    font-size: 1.35rem;
    font-weight: 900;
  }
  .sab-game-section p,
  .sab-game-section li {
    color: #94a3b8;
    font-size: .92rem;
    line-height: 1.7;
  }
  .sab-game-section a { color: #67e8f9; }
  .sab-game-section ul {
    margin: .75rem 0 0;
    padding-left: 1.15rem;
  }
  .sab-game-links {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem 1.25rem;
    margin-top: .85rem;
  }
  .sab-game-faq details {
    border-top: 1px solid rgba(148, 163, 184, .12);
    padding: .85rem 0;
  }
  .sab-game-faq summary {
    color: #e2e8f0;
    cursor: pointer;
    font-weight: 800;
  }
  .sab-game-faq p { margin: .55rem 0 0; }
  .sab-game-note {
    margin-top: 1rem;
    color: #64748b;
    font-size: .8rem;
    line-height: 1.6;
  }
  @media (max-width: 639px) {
    .sab-game-hero h1 { font-size: 1.75rem; }
    .sab-game-reflow { align-items: flex-start; }
  }
  @media (max-width: 640px) {
    .sab-game-player--portrait-mobile { aspect-ratio: 9 / 16; }
  }
</style>

<div class="sab-game-page">
  <nav class="sab-game-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ $homeHref }}">Home</a>
    <span aria-hidden="true">/</span>
    <a href="{{ $gamesHref }}">Games</a>
    <span aria-hidden="true">/</span>
    <span>{{ $gameName }}</span>
  </nav>

  <header class="sab-game-hero">
    <span class="sab-game-eyebrow">{{ $gameEyebrow ?? 'Browser play' }}</span>
    <h1>{{ $gameH1 }}</h1>
    <p>{{ $gameHeroLead }}</p>
  </header>

  <div class="sab-game-player{{ $usesPortraitMobilePlayer ? ' sab-game-player--portrait-mobile' : '' }}" data-sab-game-player>
    <div class="sab-game-cover" data-sab-game-cover>
      <div class="sab-game-cover-title">{{ $gameCoverTitle ?? $gameName }}</div>
      <p class="sab-game-cover-note">{{ $gameCoverNote }}</p>
      <button type="button" class="sab-game-play" data-sab-game-play>Play</button>
    </div>
    <iframe
      class="sab-game-iframe"
      id="sab-game-frame"
      title="{{ $gameName }}"
      src="about:blank"
      data-embed-src="{{ $embedUrl }}"
      allow="autoplay; fullscreen; gamepad; clipboard-write; keyboard-map"
      sandbox="allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-popups allow-presentation allow-scripts allow-same-origin allow-downloads"
      allowfullscreen
    ></iframe>
    <button type="button" class="sab-game-exit" data-sab-game-exit>Exit</button>
  </div>
  <div class="sab-game-reflow" data-sab-game-reflow>
    <div class="sab-game-reflow-copy">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
        <circle cx="11" cy="11" r="7"/>
        <path stroke-linecap="round" d="m20 20-3.5-3.5"/>
        <path stroke-linecap="round" d="M8 11h6M11 8v6"/>
      </svg>
      <p>Just caught something rare? Check its exist count</p>
    </div>
    <div class="sab-game-reflow-links">
      <a class="sab-game-reflow-primary" href="{{ $existCountHref }}" data-sab-game-reflow-link data-dest="exist-count">Exist Count</a>
      <a class="sab-game-reflow-secondary" href="{{ $calculatorHref }}" data-sab-game-reflow-link data-dest="calculator">Calculator</a>
      <a class="sab-game-reflow-secondary" href="{{ $valueListHref }}" data-sab-game-reflow-link data-dest="value-list">Value List</a>
    </div>
  </div>
  <div class="sab-game-toolbar">
    <button type="button" class="sab-game-fullscreen" data-sab-game-fullscreen>Full screen</button>
  </div>
  <p class="sab-game-fallback">{{ $gameFallback }}</p>

  @if($featuredCharacters !== [] || $valueChangesHref !== '')
  <section class="sab-game-section">
    <h2>Exist counts and recent values</h2>
    <p>The player is for practice. Use the live rows below when you need supply or a value move before you trade.</p>
    @if($featuredCharacters !== [])
    <div class="sab-game-links">
      @foreach($featuredCharacters as $character)
      <a href="{{ $character['href'] }}">{{ $character['name'] }} · exist {{ $character['existCount'] }}@if($character['rarity'] !== '') · {{ $character['rarity'] }}@endif</a>
      @endforeach
      @if($codesHref !== '')
      <a href="{{ $codesHref }}">Codes</a>
      @endif
    </div>
    @endif
    @if($valueChangesHref !== '')
    <p>See <a href="{{ $valueChangesHref }}">recent value changes</a>@if($recentChanges !== []) for movers such as
      @foreach($recentChanges as $index => $change)
        <a href="{{ $change['productUrl'] }}">{{ $change['itemName'] }}</a>@if(!$loop->last), @endif
      @endforeach
    @endif.</p>
    @endif
  </section>
  @endif

  @foreach($sections as $section)
  <section class="sab-game-section">
    <h2>{{ $section['h2'] }}</h2>
    @foreach(($section['paragraphs'] ?? []) as $paragraph)
    <p>{{ $paragraph }}</p>
    @endforeach
    @if(!empty($section['list']))
    <ul>
      @foreach($section['list'] as $item)
      <li>{{ $item }}</li>
      @endforeach
    </ul>
    @endif
    @if(!empty($section['links']))
    <div class="sab-game-links">
      @foreach($section['links'] as $link)
      <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
      @endforeach
    </div>
    @endif
  </section>
  @endforeach

  @if($related !== [])
  <section class="sab-game-section">
    <h2>More brainrot games</h2>
    <div class="sab-game-links">
      @foreach($related as $link)
      <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
      @endforeach
    </div>
  </section>
  @endif

  <section class="sab-game-section" id="faq">
    <h2>FAQ</h2>
    <div class="sab-game-faq">
      @foreach($faqItems as $faq)
      <details>
        <summary>{{ $faq['question'] }}</summary>
        <p>{{ $faq['answer'] }}</p>
      </details>
      @endforeach
    </div>
    <p class="sab-game-note">SAB Exist Count is a community reference site. Game names and assets belong to their owners. Treat exist counts and values as informational, not official Roblox data.</p>
  </section>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    var play = document.querySelector('[data-sab-game-play]');
    var cover = document.querySelector('[data-sab-game-cover]');
    var frame = document.getElementById('sab-game-frame');
    var fullscreen = document.querySelector('[data-sab-game-fullscreen]');
    var player = document.querySelector('[data-sab-game-player]');
    var exitBtn = document.querySelector('[data-sab-game-exit]');
    var slug = @json($gameSlug);

    function enterExpanded() {
      if (!player) return;
      player.classList.add('is-expanded');
      document.body.style.overflow = 'hidden';
    }

    function exitExpanded() {
      if (!player) return;
      player.classList.remove('is-expanded');
      document.body.style.overflow = '';
    }

    function requestPlayerFullscreen() {
      if (!player) return Promise.reject();
      var req = player.requestFullscreen || player.webkitRequestFullscreen;
      if (!req) return Promise.reject();
      try {
        var result = req.call(player);
        return result && typeof result.then === 'function' ? result : Promise.resolve();
      } catch (err) {
        return Promise.reject(err);
      }
    }

    function exitPlayerFullscreen() {
      var exitFs = document.exitFullscreen || document.webkitExitFullscreen;
      if ((document.fullscreenElement || document.webkitFullscreenElement) && exitFs) {
        exitFs.call(document);
      }
      exitExpanded();
    }

    if (play && frame) {
      play.addEventListener('click', function () {
        var src = frame.getAttribute('data-embed-src') || '';
        if (!src) return;
        frame.src = src;
        if (cover) cover.hidden = true;
        if (typeof gtag === 'function') {
          gtag('event', 'game_play_click', { game_slug: slug });
        }
      });
    }

    if (exitBtn) {
      exitBtn.addEventListener('click', function () {
        exitPlayerFullscreen();
      });
    }

    document.addEventListener('fullscreenchange', function () {
      if (!document.fullscreenElement) exitExpanded();
    });
    document.addEventListener('webkitfullscreenchange', function () {
      if (!document.webkitFullscreenElement) exitExpanded();
    });

    if (fullscreen && player) {
      fullscreen.addEventListener('click', function () {
        requestPlayerFullscreen().catch(function () {
          enterExpanded();
        });
      });
    }

    document.querySelectorAll('[data-sab-game-reflow-link]').forEach(function (link) {
      link.addEventListener('click', function () {
        if (typeof gtag === 'function') {
          gtag('event', 'game_reflow_click', { dest: link.getAttribute('data-dest') || '' });
        }
      });
    });
  })();
</script>
@endsection

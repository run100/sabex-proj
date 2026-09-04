@extends('seo.sab.layout')

@section('content')
@php
  $pagePrefix = rtrim((string) ($urlPrefix ?? ''), '/');
  $homeHref = $pagePrefix === '' ? '/' : $pagePrefix;
  $hotCards = is_array($hotGameCards ?? null) ? $hotGameCards : [];
  $newCards = is_array($newGameCards ?? null) ? $newGameCards : [];
  $roadmap = is_array($gamesRoadmap ?? null) ? $gamesRoadmap : [];
  $recentChanges = is_array($recentValueChanges ?? null) ? $recentValueChanges : [];
  $codesHref = (string) ($codesHref ?? '');
  $valueChangesHref = (string) ($valueChangesHref ?? '');
@endphp

<style>
  .sab-games-page { color: #cbd5e1; }
  .sab-games-breadcrumb {
    display: flex;
    align-items: center;
    gap: .45rem;
    margin-bottom: 1.25rem;
    color: #94a3b8;
    font-size: .8rem;
  }
  .sab-games-breadcrumb a { color: #67e8f9; text-decoration: none; }
  .sab-games-hero h1 {
    margin: 0;
    color: #f8fafc;
    font-size: 2.25rem;
    font-weight: 900;
    line-height: 1.08;
  }
  .sab-games-hero p {
    max-width: 46rem;
    margin: .8rem 0 0;
    color: #cbd5e1;
    font-size: 1rem;
    line-height: 1.7;
  }
  .sab-games-search {
    margin-top: 1.25rem;
    max-width: 28rem;
  }
  .sab-games-search label {
    display: block;
    color: #94a3b8;
    font-size: .78rem;
    font-weight: 800;
    margin-bottom: .4rem;
  }
  .sab-games-search input {
    width: 100%;
    background: #020617;
    border: 1px solid rgba(148, 163, 184, .28);
    color: #f8fafc;
    font-size: .95rem;
    min-height: 2.75rem;
    padding: 0 .85rem;
  }
  .sab-games-search input:focus {
    border-color: #67e8f9;
    outline: none;
  }
  .sab-games-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    margin-top: 1.15rem;
    align-items: stretch;
  }
  .sab-games-card-wrap {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
  .sab-games-card {
    background: #0f172a;
    border: 1px solid rgba(148, 163, 184, .18);
    color: inherit;
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    text-decoration: none;
  }
  .sab-games-card:hover { border-color: #67e8f9; }
  .sab-games-card-cover {
    aspect-ratio: 16 / 10;
    background-color: #020617;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
    position: relative;
  }
  .sab-games-card-cover::after {
    background: linear-gradient(to top, rgba(2, 6, 23, .72), transparent 55%);
    content: "";
    inset: 0;
    position: absolute;
  }
  .sab-games-card-body { padding: 1rem 1rem 1.1rem; }
  .sab-games-card-tag {
    color: #67e8f9;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
  }
  .sab-games-card h3 {
    color: #f8fafc;
    font-size: 1.15rem;
    font-weight: 900;
    margin: .35rem 0 0;
  }
  .sab-games-card p {
    color: #94a3b8;
    font-size: .88rem;
    line-height: 1.55;
    margin: .45rem 0 0;
  }
  .sab-games-card-chars {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    padding: .15rem 1rem 1rem;
  }
  .sab-games-card-chars span {
    color: #67e8f9;
    font-size: .75rem;
    line-height: 1.45;
  }
  .sab-games-card-chars a {
    color: #67e8f9;
    text-decoration: none;
  }
  .sab-games-card-chars a:hover { text-decoration: underline; }
  .sab-games-section {
    border-top: 1px solid rgba(148, 163, 184, .14);
    clear: both;
    margin-top: 2rem;
    padding-top: 1.75rem;
  }
  .sab-games-section h2 {
    margin: 0;
    color: #f1f5f9;
    font-size: 1.35rem;
    font-weight: 900;
  }
  .sab-games-section p,
  .sab-games-section li {
    color: #94a3b8;
    font-size: .92rem;
    line-height: 1.7;
  }
  .sab-games-section a { color: #67e8f9; }
  .sab-games-new {
    display: flex;
    flex-direction: column;
    gap: .65rem;
    margin-top: .9rem;
  }
  .sab-games-new a {
    align-items: center;
    background: #0f172a;
    border: 1px solid rgba(148, 163, 184, .18);
    color: #e2e8f0;
    display: flex;
    gap: .85rem;
    padding: .75rem .9rem;
    text-decoration: none;
  }
  .sab-games-new a:hover { border-color: #67e8f9; }
  .sab-games-new-thumb {
    background-position: center;
    background-size: cover;
    flex: 0 0 4.5rem;
    height: 2.8rem;
  }
  .sab-games-faq details {
    border-top: 1px solid rgba(148, 163, 184, .12);
    padding: .85rem 0;
  }
  .sab-games-faq summary {
    color: #e2e8f0;
    cursor: pointer;
    font-weight: 800;
  }
  .sab-games-faq p { margin: .55rem 0 0; }
  .sab-games-note {
    margin-top: 1rem;
    color: #64748b;
    font-size: .8rem;
    line-height: 1.6;
  }
  @media (max-width: 639px) {
    .sab-games-hero h1 { font-size: 1.75rem; }
  }
</style>

<div class="sab-games-page">
  <nav class="sab-games-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ $homeHref }}">Home</a>
    <span aria-hidden="true">/</span>
    <span>Games</span>
  </nav>

  <header class="sab-games-hero">
    <h1>Brainrot Games</h1>
    <p>Play Steal a Brainrot and Rob Brainrot in your browser, then check exist counts, codes, and rarity on SAB Exist Count. No download.</p>
  </header>

  <div class="sab-games-search">
    <label for="sab-games-filter">Search games</label>
    <input id="sab-games-filter" type="search" placeholder="Steal a Brainrot, Rob Brainrot" data-sab-games-filter autocomplete="off">
  </div>

  <section class="sab-games-section" style="border-top:0;margin-top:1.5rem;padding-top:0">
    <h2>Hot Games</h2>
    <div class="sab-games-grid" data-sab-games-grid>
      @foreach($hotCards as $card)
      <article class="sab-games-card-wrap" data-sab-game-card data-search="{{ strtolower($card['name'].' '.$card['category'].' '.$card['blurb']) }}">
        <a class="sab-games-card" href="{{ $card['href'] }}">
          <div class="sab-games-card-cover" style="background-image:url('{{ $card['coverSrc'] }}')"></div>
          <div class="sab-games-card-body">
            <div class="sab-games-card-tag">{{ $card['category'] }}</div>
            <h3>{{ $card['name'] }}</h3>
            <p>{{ $card['blurb'] }}</p>
          </div>
        </a>
        @if(!empty($card['characters']))
        <div class="sab-games-card-chars">
          @foreach($card['characters'] as $character)
          <span>
            <a href="{{ $character['href'] }}">{{ $character['name'] }} · exist {{ $character['existCount'] }}@if($character['rarity'] !== '') · {{ $character['rarity'] }}@endif</a>
            @if($codesHref !== '')
            · <a href="{{ $codesHref }}">Codes</a>
            @endif
          </span>
          @endforeach
        </div>
        @endif
      </article>
      @endforeach
    </div>
  </section>

  @if($newCards !== [])
  <section class="sab-games-section">
    <h2>New Games</h2>
    <div class="sab-games-new">
      @foreach($newCards as $card)
      <a href="{{ $card['href'] }}" data-sab-game-card data-search="{{ strtolower($card['name'].' '.$card['category'].' '.$card['blurb']) }}">
        <span class="sab-games-new-thumb" style="background-image:url('{{ $card['coverSrc'] }}')"></span>
        <span>
          <strong>{{ $card['name'] }}</strong>
          <span style="display:block;color:#94a3b8;font-size:.82rem">{{ $card['blurb'] }}</span>
        </span>
      </a>
      @endforeach
    </div>
  </section>
  @endif

  @if($valueChangesHref !== '')
  <section class="sab-games-section">
    <h2>Recent value changes</h2>
    <p>Community values move. Open the <a href="{{ $valueChangesHref }}">value changes</a> page for the full list.@if($recentChanges === []) Those rows appear when this site has a fresh calculator update.@endif</p>
    @if($recentChanges !== [])
    <ul>
      @foreach($recentChanges as $change)
      <li>
        <a href="{{ $change['productUrl'] }}">{{ $change['itemName'] }}</a>
        @if(!empty($change['deltaPctLabel']) && $change['deltaPctLabel'] !== '—')
        · {{ $change['deltaPctLabel'] }}
        @endif
      </li>
      @endforeach
    </ul>
    @endif
  </section>
  @endif

  <section class="sab-games-section">
    <h2>Best Brainrot Games List</h2>
    <p>Playable pages come first. Planned titles stay on this list so you can see what is next; they do not have game URLs yet.</p>
    <ul>
      @foreach($roadmap as $entry)
      <li>
        @if(!empty($entry['href']))
        <a href="{{ $entry['href'] }}">{{ $entry['name'] }}</a>
        @else
        <strong>{{ $entry['name'] }}</strong>
        <span> · planned</span>
        @endif
        — {{ $entry['body'] }}
      </li>
      @endforeach
    </ul>
  </section>

  <section class="sab-games-section">
    <h2>What Are Brainrot Games?</h2>
    <p>Brainrot games are short meme tycoons. You collect Italian-brainrot characters, steal from unlocked bases, and earn cash while you defend your own pile. The joke is the roster; the loop is still buy, rob, lock, and grow.</p>
    <p>SAB Exist Count is a community reference for exist counts, rarity, values, and codes. The game pages on this list are unofficial browser practice rooms for that same economy, not a replacement for the data tools.</p>
  </section>

  <section class="sab-games-section">
    <h2>Brainrot Games vs Steal a Brainrot</h2>
    <p>Steal a Brainrot is the Roblox game this site documents. Brainrot games on this page are unofficial web versions you can open without the Roblox client. Use them to learn the loop; use exist counts, values, and codes when you go back to trade.</p>
    <p>If you arrived from a character page, play a round, then return to the exist count list when you need supply or rarity before a trade.</p>
  </section>

  <section class="sab-games-section">
    <h2>How to Play Brainrot Games Online</h2>
    <p>There is no download and no SAB account. Open a live game card, click Play, and stay on the tab until the browser package loads. That is the steal a brainrot online game no download path: instant play in this tab.</p>
    <p>Desktop browsers are usually steadier than phones. After a session, use Exist Count List, SAB Values, the trading calculator, and Codes. Browser progress does not sync to Roblox.</p>
  </section>

  <section class="sab-games-section" id="faq">
    <h2>FAQ</h2>
    <div class="sab-games-faq">
      @foreach($faqItems as $faq)
      <details>
        <summary>{{ $faq['question'] }}</summary>
        <p>{!! $faq['answer'] !!}</p>
      </details>
      @endforeach
    </div>
    <p class="sab-games-note">SAB Exist Count is a community reference site. Game names and assets belong to their owners. Treat exist counts and values as informational, not official Roblox data.</p>
  </section>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    var input = document.querySelector('[data-sab-games-filter]');
    var cards = document.querySelectorAll('[data-sab-game-card]');
    if (!input || !cards.length) return;
    input.addEventListener('input', function () {
      var q = (input.value || '').toLowerCase().trim();
      cards.forEach(function (card) {
        var hay = card.getAttribute('data-search') || '';
        card.hidden = q !== '' && hay.indexOf(q) === -1;
      });
    });
  })();
</script>
@endsection

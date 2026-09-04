@extends('seo.sab.layout')

@section('content')
@include('seo.sab.partials._wiki-styles')

<article class="sab-wiki">
  @include('seo.sab.partials._wiki-crumbs')
  <header class="sab-wiki-hero">
    <h1 class="sab-wiki-title">{{ $wikiH1 }}</h1>
    <p class="sab-wiki-lead">{{ $wikiLead }}</p>
    <p class="sab-wiki-meta" aria-label="Wiki summary">{{ number_format($wikiTotal) }} published items · {{ count($wikiRarityLinks ?? []) }} rarity pages · Updated {{ $wikiUpdatedDate ?? $wikiUpdatedLabel ?: 'Unknown' }}</p>
  </header>

  <nav class="sab-wiki-panel" aria-labelledby="wiki-hub-title">
    <p class="sab-wiki-nav-title" id="wiki-hub-title">Browse this Wiki</p>
    <div class="sab-wiki-hub">
      <a href="#newest"><strong>Newest</strong><span>{{ number_format(count($wikiNewest ?? [])) }} added in the last 14 days</span></a>
      <a href="{{ $allBrainrotsHref }}"><strong>All Brainrots</strong><span>{{ number_format($wikiTotal) }} published items by rarity</span></a>
      <a href="{{ $rebirthsHref }}"><strong>Rebirth List</strong><span>All 19 Rebirth requirements, rewards, and max Rebirth</span></a>
      <a href="{{ $urlPrefix }}/wiki/admin-abuse"><strong>Admin Abuse &amp; Taco Tuesday</strong><span>Next confirmed time, timezone conversion, countdown, and event FAQ</span></a>
      <a href="#recent-updates"><strong>Recent updates</strong><span>{{ number_format(count($wikiNews ?? [])) }} published tracker notes</span></a>
    </div>
  </nav>

  <section class="sab-wiki-panel" id="brainrot-rarities">
    <h2>Brainrot rarities</h2>
    <p>The standard progression is Common, Rare, Epic, Legendary, Mythic, Brainrot God, Secret, and OG. Rarity is a game classification; it does not by itself prove a low Exist Count or a high Trade Value.</p>
    <div class="sab-wiki-tools">
      @foreach(($wikiRarityLinks ?? []) as $link)
      <a class="sab-wiki-tool {{ \App\Services\Seo\SabWikiPageDefinitions::rarityCssClass($link['key'] ?? null) }}" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
      @endforeach
    </div>
  </section>

  <nav class="sab-wiki-panel" aria-labelledby="wiki-contents-title">
    <p class="sab-wiki-nav-title" id="wiki-contents-title">On this page</p>
    <ol class="sab-wiki-toc">
      <li><a href="#brainrot-rarities">Brainrot rarities</a></li>
      <li><a href="#newest">Newest Brainrots</a></li>
      <li><a href="#what-are-brainrots">What are Brainrots?</a></li>
      <li><a href="#how-to-get-brainrots">How to get Brainrots</a></li>
      <li><a href="#exist-count">Exist Count</a></li>
      <li><a href="#value-meanings">Cost, Income &amp; Value</a></li>
      <li><a href="#recent-updates">Recent updates</a></li>
      <li><a href="#data-method">Data method</a></li>
      <li><a href="#sab-tools">SAB Tools</a></li>
      <li><a href="#faq">FAQ</a></li>
    </ol>
  </nav>

  <section class="sab-wiki-panel" id="newest">
    <h2>Newest Brainrots</h2>
    <p>Items first stored or confirmed in the last 14 days. This is a catalog of recent additions, not a claim that every new in-game spawn is listed here.</p>
    @if(!empty($wikiNewest))
    <div class="sab-wiki-card-list">
      @foreach($wikiNewest as $row)
      <article class="sab-wiki-card">
        <div>
          <a href="{{ $row['productUrl'] }}">{{ $row['name'] }}</a>
          <p class="sab-wiki-card-meta">{{ $row['rarityLabel'] }}@if($row['obtainMethod']) · {{ $row['obtainMethod'] }}@endif</p>
        </div>
        <a class="sab-wiki-details" href="{{ $row['productUrl'] }}">View details →</a>
      </article>
      @endforeach
    </div>
    @else
    <p class="sab-wiki-empty">No Brainrots in this Wiki were first stored in the last 14 days.</p>
    @endif
  </section>

  <div class="sab-wiki-copy-grid">
    <section class="sab-wiki-panel" id="what-are-brainrots">
      <h2>What are Brainrots in Steal a Brainrot?</h2>
      <p>Brainrots are collectible units that generate in-game cash while placed in a player base. Each unit has a rarity and may have a base cost, base income, limited availability, mutations, traits, and a changing player-trade value.</p>
    </section>
    <section class="sab-wiki-panel" id="how-to-get-brainrots">
      <h2>How to get Brainrots</h2>
      <p>Depending on the update and item, Brainrots may come from the Conveyor Belt, events, Lucky Blocks, crafting or fusion systems, an RNG Machine, a shop, codes, or trades with other players. Exact obtain methods are only listed when they are stored as confirmed data.</p>
    </section>
    <section class="sab-wiki-panel" id="exist-count">
      <h2>What Exist Count means</h2>
      <p>Exist Count tracks supply in player possession. Confirmed counts appear as numbers. Estimated ranges carry an Estimate label. Missing data stays Unknown and is never converted into zero.</p>
      <div class="sab-wiki-tools">
        <a class="sab-wiki-tool" href="{{ $existCountHref }}">Open the Exist Count List</a>
      </div>
    </section>
  </div>

  <section class="sab-wiki-panel" id="value-meanings">
    <h2>Cost, Income, Exist Count, and Trade Value are different</h2>
    <div class="sab-wiki-definition-grid">
      <div class="sab-wiki-definition"><strong>Cost</strong><p>The stored in-game base purchase or acquisition price. It is not an Exist Count.</p></div>
      <div class="sab-wiki-definition"><strong>Income</strong><p>The stored base amount of in-game cash generated per second.</p></div>
      <div class="sab-wiki-definition"><strong>Exist Count</strong><p>A supply signal: confirmed copy count, labeled estimate, or Unknown.</p></div>
      <div class="sab-wiki-definition"><strong>Trade Value</strong><p>A community market signal used to compare offers. It can change independently of Cost or Income.</p></div>
    </div>
    <div class="sab-wiki-tools">
      <a class="sab-wiki-tool" href="{{ $valueListHref }}">Browse SAB Values</a>
      <a class="sab-wiki-tool" href="{{ $calculatorHref }}">Compare a Trade</a>
      <a class="sab-wiki-tool" href="{{ $allBrainrotsHref }}">Open the full catalog</a>
    </div>
  </section>

  <section class="sab-wiki-panel" id="recent-updates">
    <h2>Recent updates</h2>
    <p>Tracker notes and patch summaries already published on SAB Exist Count. This is not a copy of another wiki’s changelog.</p>
    @if(!empty($wikiNews))
    <div class="sab-wiki-card-list">
      @foreach($wikiNews as $article)
      <article class="sab-wiki-card">
        <div>
          <a href="{{ $article['href'] }}">{{ $article['title'] }}</a>
          @if($article['dateLabel'])
          <p class="sab-wiki-card-meta">{{ $article['dateLabel'] }}</p>
          @endif
        </div>
        <a class="sab-wiki-details" href="{{ $article['href'] }}">Read update →</a>
      </article>
      @endforeach
    </div>
    @else
    <p class="sab-wiki-empty">No published update notes are attached to this Wiki page yet.</p>
    @endif
    <div class="sab-wiki-tools">
      <a class="sab-wiki-tool" href="{{ rtrim((string) ($urlPrefix ?? ''), '/') }}/news">Open News</a>
    </div>
  </section>

  <section class="sab-wiki-panel" id="data-method">
    <h2>How this Wiki handles data</h2>
    <p>Rows come from the SAB Exist Count database. Cost and Income use stored game-stat fields, Exist Count keeps confirmed and estimated supply separate, and Trade Value follows the same current-value signal used by the SAB Values page. Eldorado is not imported as a numeric source. Local thumbnails are used when available; missing images use a text placeholder.</p>
    <p>Last stored data change: @if($wikiUpdatedAt)<time datetime="{{ $wikiUpdatedAt }}">{{ $wikiUpdatedLabel }}</time>@else Unknown @endif.</p>
  </section>

  <section class="sab-wiki-panel" id="sab-tools">
    <h2>SAB Values, Calculator &amp; Exist Count Tools</h2>
    <p>SAB Values tracks community trade value, the SAB Calculator compares offers, and SAB Exist Count helps judge supply and Brainrot rarity before you trade.</p>
    <div class="sab-wiki-hub sab-wiki-tools-grid">
      <a href="{{ $valueListHref }}">
        <strong>SAB Values</strong>
        <span>Check current value, previous value, demand, and trend. These are community references, not official prices.</span>
      </a>
      <a href="{{ $calculatorHref }}">
        <strong>SAB Calculator</strong>
        <span>Compare Your Offer and You Receive with mutations, traits, income, and a W/F/L reference.</span>
      </a>
      <a href="{{ $existCountHref }}">
        <strong>SAB Exist Count</strong>
        <span>Review confirmed counts, labeled estimates, Unknown supply data, and rarity signals.</span>
      </a>
    </div>
  </section>

  <section class="sab-wiki-panel" id="faq">
    <h2>Steal a Brainrot Wiki FAQ</h2>
    <div class="sab-wiki-faq">
      @foreach($wikiFaqItems as $faq)
      <details>
        <summary>{{ $faq['question'] }}</summary>
        <p>{!! $faq['answer_html'] ?? e($faq['answer']) !!}</p>
      </details>
      @endforeach
    </div>
  </section>
</article>
@endsection

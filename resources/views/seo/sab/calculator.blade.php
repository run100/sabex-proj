@extends(($calculatorOnly ?? false) ? 'seo.sab.layout-calculator' : 'seo.sab.layout')

@section('content')
@php
  $calcCopy = $calculatorCopy ?? [];
  $calcH1 = $calcCopy['h1'] ?? 'SAB Trade Calculator for Steal a Brainrot';
  $calcIntro = $calcCopy['intro'] ?? 'Compare values before you trade';
  $isRotTheme = (bool) ($calculatorOnly ?? false);
  $calcUi = $calculatorUi ?? [];
@endphp
@include('seo.sab.partials._calculator-styles')

@php
  $existCountListHref = $urlPrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNTS_LIST;
  $valueListHref = ($productUrlPrefix ?? '') . '/' . \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST;
  $august19NewsHref = rtrim((string) ($urlPrefix ?? ''), '/') . '/news/steal-a-brainrot-sab-values-market-watch-headless-horseman-rebounds-dragon-cannelloni-drops-august-19-2026';
  $wikiShortcutPrefix = rtrim((string) ($productUrlPrefix ?? $urlPrefix ?? ''), '/');
  $adminAbuseHref = $wikiShortcutPrefix . '/' . \App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE;
  $wikiHubHref = $wikiShortcutPrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_WIKI;
  $rebirthsHref = $wikiShortcutPrefix . '/' . \App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS;
  $calculatorFaqItems = $calculatorFaqItems ?? [];
  $popularTradeItems = $popularTradeItems ?? collect();
  $showValuesTip = empty($calculatorOnly);
  $todayTopGainers = collect($todayTopGainers ?? [])->values();
  $todayTopLosers = collect($todayTopLosers ?? [])->values();
  $showTodaySummary = $showValuesTip;
@endphp

@if($isRotTheme)
<header class="mb-6 flex items-center gap-3">
  <div class="sab-calc-hero-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-green-400">
      <path d="m16 3 4 4-4 4"></path><path d="M20 7H4"></path><path d="m8 21-4-4 4-4"></path><path d="M4 17h16"></path>
    </svg>
  </div>
  <div>
    <h1 class="text-xl font-bold text-white tracking-tight">{{ $calcH1 }}</h1>
    <p class="text-sm text-slate-500 font-normal">{{ $calcIntro }}</p>
    @if(!empty($calculatorLastUpdatedLabel))
    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-600">
      {{ $lastUpdateLabel ?? 'Last update' }}: <time datetime="{{ $calculatorLastUpdatedAt }}">{{ $calculatorLastUpdatedLabel }}</time>
    </p>
    @endif
  </div>
</header>
@else
<header class="-mx-3 border-b border-white/10 px-3 py-3 sm:-mx-4 sm:px-4">
  <div>
    <h1 class="sab-calc-intro-title">{{ $calcH1 }}</h1>
    <p class="mt-1 sab-calc-intro-copy">{{ $calcIntro }}</p>
    @if(!empty($calculatorLastUpdatedLabel))
    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
      {{ $lastUpdateLabel ?? 'Last update' }}: <time datetime="{{ $calculatorLastUpdatedAt }}">{{ $calculatorLastUpdatedLabel }}</time>
    </p>
    @endif
    {{--
    @if(($locale ?? 'en') === 'en')
    <div class="mt-2 inline-flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
      </svg>
      <a href="{{ \App\Services\Seo\SabSiteContext::EXTERNAL_MM2_CALCULATOR_URL }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline hover:text-cyan-100">New tool · MM2 Value Calculator</a>
    </div>
    @endif
    --}}
  </div>
</header>
@endif

@if($showValuesTip)
<div class="mb-3 space-y-1">
  {{--
  <div class="flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
    </svg>
    <a href="{{ $valueListHref }}" class="font-semibold underline hover:text-cyan-100">
      {{ $valuesTipLabel ?? 'SAB Values is live — see what moved today' }}
    </a>
  </div>
  --}}
  <div class="flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
    </svg>
    <a href="{{ $august19NewsHref }}" class="font-semibold underline hover:text-cyan-100">
      News · Aug 19 — SAB Values: Headless Horseman rebounds, Dragon Cannelloni drops
    </a>
  </div>
  <div class="sab-calc-trade-pills" aria-label="Trade actions">
    <a class="sab-calc-trade-pill sab-calc-trade-pill--primary" href="{{ \App\Support\TradePaths::create() }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
      Create a Trade
    </a>
    <a class="sab-calc-trade-pill" href="{{ \App\Support\TradePaths::marketplace() }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
      View Trades
    </a>
  </div>
</div>
@endif

<section id="sab-calculator" class="my-8" data-calculator-root data-builder-layout="cards" data-calculator-theme="{{ $isRotTheme ? 'rot' : 'default' }}">
  <div class="sab-calc-card overflow-hidden">
    <div class="sab-calc-trade-grid">
      <div class="sab-calc-trade-side p-4 sm:p-6" data-side="offer">
        <div class="mb-5 flex items-center justify-between">
          <h2 class="sab-calc-side-title text-lg font-bold text-white">
            <span class="sab-calc-side-badge" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7"></path><path d="M8 7h9v9"></path></svg>
            </span>
            {{ $calcUi['offerTitle'] ?? 'Your Offer' }}
          </h2>
          <span class="text-sm text-slate-500" data-count>0 {{ $calcUi['countPlural'] ?? 'items' }}</span>
        </div>
        <div class="space-y-3" data-items></div>
        <div class="mt-5 space-y-2 text-sm" data-totals></div>
      </div>

      <div class="sab-calc-compare-panel p-4 text-center">
        <button type="button" class="sab-calc-swap-btn" data-swap title="{{ $calcUi['swapTitle'] ?? 'Swap sides' }}">
          <svg viewBox="0 0 24 24" class="sab-calc-swap-icon" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m16 3 4 4-4 4"></path><path d="M20 7H4"></path><path d="m8 21-4-4 4-4"></path><path d="M4 17h16"></path>
          </svg>
        </button>
        <div class="space-y-2" data-compare>
          <p class="text-sm text-slate-500 sab-calc-compare-hint">{{ $calcUi['compareEmpty'] ?? 'Add items to compare trades' }}</p>
        </div>
      </div>

      <div class="sab-calc-trade-side p-4 sm:p-6" data-side="receive">
        <div class="mb-5 flex items-center justify-between">
          <h2 class="sab-calc-side-title text-lg font-bold text-white">
            <span class="sab-calc-side-badge" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 7 7 17"></path><path d="M16 17H7V8"></path></svg>
            </span>
            {{ $calcUi['receiveTitle'] ?? 'You Receive' }}
          </h2>
          <span class="text-sm text-slate-500" data-count>0 {{ $calcUi['countPlural'] ?? 'items' }}</span>
        </div>
        <div class="space-y-3" data-items></div>
        <div class="mt-5 space-y-2 text-sm" data-totals></div>
      </div>
    </div>
    <div class="border-t border-white/15 p-4 text-center">
      <button type="button" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-slate-500 hover:text-rose-300" data-clear>
        <span>↻</span><span>{{ $calcUi['clearAll'] ?? 'Clear All' }}</span>
      </button>
    </div>
  </div>

  <div class="sab-calc-help-panel">
    <button type="button" class="sab-calc-help-toggle" data-help-toggle>
      <span>
        <span class="block text-base font-bold text-white">{{ $calcUi['helpTitle'] ?? (($calculatorOnly ?? false) ? 'How the calculator reads a trade' : 'How values are calculated') }}</span>
        <span class="text-sm text-slate-500">{{ $calcUi['helpSubtitle'] ?? (($calculatorOnly ?? false) ? 'Value, income, mutations, and trait effects' : 'Formulas and trait bonuses') }}</span>
      </span>
      <span class="sab-calc-help-chevron">⌄</span>
    </button>
    <div class="hidden space-y-4 border-t border-white/10 p-4" data-help>
      <div class="grid gap-4 md:grid-cols-2">
        <div class="sab-calc-help-card">
          <p class="mb-2 font-bold text-emerald-400">{{ $calcUi['incomeCheckTitle'] ?? 'Income Formula' }}</p>
          <p class="font-mono text-sm text-slate-300">{{ $calcUi['incomeFormula'] ?? 'Base × (Mutation + Traits)' }}</p>
          <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ $calcUi['incomeCheckBody'] ?? 'Trait income multipliers stack additively with the mutation multiplier.' }}</p>
        </div>
        <div class="sab-calc-help-card">
          <p class="mb-2 font-bold text-yellow-400">{{ $calcUi['valueCheckTitle'] ?? 'Value Formula' }}</p>
          <p class="font-mono text-sm text-slate-300">{{ $calcUi['valueFormula'] ?? 'Mutation Value × (1 + Trait Bonuses × Streak)' }}</p>
          <p class="mt-2 text-xs leading-relaxed text-slate-500">{{ $calcUi['valueCheckBody'] ?? 'Each trait adds a percentage bonus to the mutation value.' }}</p>
        </div>
      </div>
      <div class="sab-calc-help-card" data-streak-help></div>
      <div class="sab-calc-help-card">
        <p class="mb-1 font-bold text-white">{{ $calcUi['traitValueBonuses'] ?? 'Trait Value Bonuses' }}</p>
        <p class="mb-3 text-xs text-slate-500">{{ $calcUi['bonusStackNote'] ?? 'Bonuses stack additively.' }}</p>
        <div data-trait-value-bonuses></div>
      </div>
    </div>
  </div>

  @include('seo.sab.partials._brainrot-picker')
</section>

@if($showTodaySummary)
<section class="sab-calc-today" aria-labelledby="sab-calc-today-title">
  <div class="sab-calc-today__head">
    <h2 id="sab-calc-today-title" class="sab-calc-today__title">{{ $t['calculator_today_summary_title'] ?? "Today's summary" }}</h2>
    <a href="{{ $valueListHref }}" class="sab-calc-today__more">{{ $t['calculator_today_more'] ?? 'More' }}</a>
  </div>
  @if($todayTopGainers->isNotEmpty() || $todayTopLosers->isNotEmpty())
  <div class="sab-calc-today__grid">
    <div>
      <h3 class="sab-calc-today__col-title">{{ $t['calculator_today_top_gainers'] ?? 'Top gainers' }}</h3>
      <ol class="sab-calc-today__list">
        @forelse($todayTopGainers as $change)
        <li class="sab-calc-today__row">
          <span class="sab-calc-today__rank">{{ $loop->iteration }}.</span>
          @if(!empty($change['productUrl']))
          <a class="sab-calc-today__name" href="{{ $change['productUrl'] }}">{{ $change['itemName'] ?? '—' }}</a>
          @else
          <span class="sab-calc-today__name">{{ $change['itemName'] ?? '—' }}</span>
          @endif
          <span class="sab-calc-today__pct is-up">{{ $change['deltaPctLabel'] ?? '—' }}</span>
          <span class="sab-calc-today__values">{{ $change['before'] ?? '—' }} → {{ $change['after'] ?? '—' }}</span>
        </li>
        @empty
        <li class="sab-calc-today__row"><span>—</span></li>
        @endforelse
      </ol>
    </div>
    <div>
      <h3 class="sab-calc-today__col-title">{{ $t['calculator_today_top_losers'] ?? 'Top losers' }}</h3>
      <ol class="sab-calc-today__list">
        @forelse($todayTopLosers as $change)
        <li class="sab-calc-today__row">
          <span class="sab-calc-today__rank">{{ $loop->iteration }}.</span>
          @if(!empty($change['productUrl']))
          <a class="sab-calc-today__name" href="{{ $change['productUrl'] }}">{{ $change['itemName'] ?? '—' }}</a>
          @else
          <span class="sab-calc-today__name">{{ $change['itemName'] ?? '—' }}</span>
          @endif
          <span class="sab-calc-today__pct is-down">{{ $change['deltaPctLabel'] ?? '—' }}</span>
          <span class="sab-calc-today__values">{{ $change['before'] ?? '—' }} → {{ $change['after'] ?? '—' }}</span>
        </li>
        @empty
        <li class="sab-calc-today__row"><span>—</span></li>
        @endforelse
      </ol>
    </div>
  </div>
  @else
  <p class="sab-calc-today__empty">{{ $t['calculator_today_no_movers'] ?? 'No movers today yet.' }}</p>
  @endif
</section>
@include('seo.sab.partials._wiki-shortcut-module', [
  'variant' => 'calculator',
  'lead' => 'Values do not show event timing or Rebirth unlocks. Check the Wiki if a name in the trade depends on an Admin Abuse window or a Rebirth requirement.',
  'wikiHubHref' => $wikiHubHref,
  'adminAbuseHref' => $adminAbuseHref,
  'rebirthsHref' => $rebirthsHref,
])
@endif

<section class="sab-calc-content sab-calc-copy">
  @if($calculatorOnly ?? false)
  <article class="sab-calc-guide sab-calc-guide-long">
    <section>
      <h2>What is the Steal a Brainrot Trade Calculator?</h2>
      <p>The Steal a Brainrot Trade Calculator is a practical trade checker for comparing both sides of a deal before you accept it in game. Instead of judging a trade by item names alone, you can add the Brainrots in your offer, add the Brainrots you would receive, and see how the totals compare. The calculator looks at value, income, mutation, traits, and quantity together, because a trade can look fair by name but change quickly once the exact setup is added.</p>
      <p>This is especially useful when a trade includes several Brainrots, mixed rarities, or items with different mutations. A player might offer one high-value Brainrot for multiple lower-value ones, or trade a strong Brainrot income item for something that is more desirable in Steal a Brainrot trading. By checking both total value and total income, the calculator works like a Brainrot value calculator that shows what each side is bringing to the trade.</p>
    </section>

    <section>
      <h2>How to use the SAB Trade Calculator</h2>
      <p>Start by adding the Brainrots you are offering on the left side. Then add the Brainrots you would receive on the right side. For each item, select the mutation that matches the item in the trade, then add any traits that are visible in game. If there are multiple copies of the same Brainrot, adjust the quantity so the total matches the real offer.</p>
      <p>After both sides are entered, compare total value and total income. Value is useful for checking whether the trade is balanced as a player-to-player deal. Income is useful for understanding how much each side can earn over time. The W/F/L result should be treated as a quick W/F/L trade checker before accepting, not as a final rule. A trade can still depend on demand, personal goals, rarity, and how badly each player wants the item.</p>
    </section>

    <section>
      <h2>Why mutation and traits affect trade value</h2>
      <p>Mutation and traits can change the way a Brainrot is judged in a trade. A plain version of an item may not compare the same way as a mutated version, even when the name is identical. Mutations such as Gold, Diamond, Rainbow, Candy, Bloodrot, and Galaxy can affect income, perceived rarity, SAB values, and the value players expect in return. Some mutations are mainly checked because they improve income, while others can matter because players view them as harder to get or more desirable.</p>
      <p>Traits add another layer. Several traits can stack into a stronger result, and some combinations make an item more attractive than a basic version of the same Brainrot. This is why the calculator asks for the exact mutation and traits instead of only the Brainrot name. If either side of the trade has a special setup, entering it correctly can change whether the trade looks like a win, fair trade, or lose.</p>
    </section>

    <section>
      <h2>Value vs Income: Which one matters more?</h2>
      <p>Value and income answer different questions. Steal a Brainrot value helps you judge the trade itself: whether the Brainrots you receive are likely to be worth more, less, or about the same as what you give away. Income helps you judge long-term usefulness: whether the items you receive can produce more money over time. A high-income item may be useful even when its trade value is not the highest, while a high-value item may be better for future trading even if its income is lower.</p>
      <p>For most trades, check both numbers. If value and income point in the same direction, the trade is easier to judge. If they disagree, think about your goal. Players who want faster earning may care more about income. Players who want rare items, demand, or future trading power may care more about value. The best use of the calculator is to make that tradeoff visible before you click accept.</p>
    </section>
  </article>
  @else
  <div class="sab-calc-seo-grid md:grid-cols-2">
    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['seoTitle'] ?? 'How to Use the SAB Calculator' }}</h2>
      <ol class="sab-calc-step-list sab-calc-seo-text">
        @foreach(($calcUi['seoSteps'] ?? []) as $step)
        <li>{{ $step }}</li>
        @endforeach
      </ol>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['valuesTitle'] ?? 'What Are SAB Trading Values?' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['valuesBody'] ?? '' }}</p>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['wflTitle'] ?? 'How to Check W/F/L in Steal a Brainrot' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['wflBody'] ?? '' }}</p>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['mutationsTitle'] ?? 'How Mutations and Traits Affect SAB Trade Value' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['mutationsBody'] ?? '' }}</p>
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['existCountTitle'] ?? 'Why Exist Count Matters in SAB Trading' }}</h2>
      <p class="sab-calc-seo-text">{{ $calcUi['existCountBody'] ?? '' }}</p>
      @if(!($calculatorOnly ?? false))
      <div class="mt-4 flex flex-wrap gap-2">
        <a class="sab-calc-link-pill" href="{{ $existCountListHref }}">{{ $calcUi['existCountLink'] ?? 'View Steal a Brainrot Exist Count List' }}</a>
        <a class="sab-calc-link-pill" href="{{ $valueListHref }}">{{ $calcUi['valueListLink'] ?? 'View Steal a Brainrot Value List' }}</a>
      </div>
      @endif
    </article>

    <article class="sab-calc-seo-panel">
      <h2 class="sab-calc-seo-title">{{ $calcUi['tipsTitle'] ?? 'SAB Trading Tips' }}</h2>
      <ul class="sab-calc-tip-list sab-calc-seo-text">
        @foreach(($calcUi['tips'] ?? []) as $tip)
        <li>{{ $tip }}</li>
        @endforeach
      </ul>
    </article>
  </div>
  @endif

  @if(!($calculatorOnly ?? false) && $popularTradeItems->isNotEmpty())
    <section class="sab-calc-seo-panel mt-4">
      <h2 class="sab-calc-seo-title">{{ $calcUi['popularTitle'] ?? 'Popular Brainrots to Check Before Trading' }}</h2>
      <p class="mb-3 sab-calc-seo-text">{{ $calcUi['popularBody'] ?? '' }}</p>
      <div class="flex flex-wrap gap-2">
        @foreach($popularTradeItems as $popularItem)
          @if(\App\Services\Seo\SabRenderService::shouldLinkProduct($popularItem))
          <a class="sab-calc-link-pill" href="{{ $productUrlPrefix }}/products/{{ \App\Services\Seo\SabRenderService::productPublicSlug($popularItem->slug ?? '') }}">{{ $popularItem->name }}</a>
          @endif
        @endforeach
      </div>
    </section>
  @endif

  @if(($calculatorOnly ?? false) && !empty($faqHref))
  <section id="faq" class="sab-calc-seo-panel mt-4">
    <h2 class="mb-3 sab-calc-seo-title">FAQ</h2>
    <p class="sab-calc-seo-text">
      Read how the trade checker works, how income and values are estimated, and why mutations and traits matter before you accept.
    </p>
    <p class="mt-3">
      <a class="sab-calc-link-pill" href="{{ $faqHref }}">Read FAQ</a>
    </p>
  </section>
  @elseif(!($calculatorOnly ?? false))
  <section id="faq" class="sab-calc-seo-panel mt-4">
    <h2 class="mb-3 sab-calc-seo-title">{{ $calcUi['faqTitle'] ?? 'SAB Calculator FAQ' }}</h2>
    <div class="sab-calc-faq-list">
      @foreach($calculatorFaqItems as $faqItem)
        <article class="sab-calc-faq-item">
          <h3 class="text-sm font-bold text-slate-100">{{ $faqItem['question'] }}</h3>
          <p class="mt-1 sab-calc-seo-text">{{ $faqItem['answer'] }}</p>
        </article>
      @endforeach
    </div>
  </section>
  @endif
</section>
@endsection

@section('scripts')
@include('seo.sab.partials._trade-builder-boot')
@endsection

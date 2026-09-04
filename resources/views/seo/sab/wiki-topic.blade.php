@extends('seo.sab.layout')

@section('content')
@include('seo.sab.partials._wiki-styles')

<article class="sab-wiki">
  @include('seo.sab.partials._wiki-crumbs')
  <header class="sab-wiki-hero">
    <h1 class="sab-wiki-title">{{ $wikiH1 }}</h1>
    <p class="sab-wiki-lead">{{ $wikiLead }}</p>
    <p class="sab-wiki-meta" aria-label="Topic summary">{{ $wikiMeta ?? (number_format($wikiPageTotal).' confirmed items · Updated '.($wikiUpdatedDate ?? $wikiUpdatedLabel ?: 'Unknown')) }}</p>
  </header>

  <div class="sab-wiki-tools">
    <a class="sab-wiki-tool" href="{{ $wikiHref }}">Wiki hub</a>
    <a class="sab-wiki-tool" href="{{ $allBrainrotsHref }}">All Brainrots</a>
    @foreach(($wikiTopicLinks ?? []) as $link)
    <a class="sab-wiki-tool" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
    @endforeach
  </div>

  @if(!empty($rebirthsGuide) && !empty($rebirths))
  @include('seo.sab.partials._wiki-rebirth-guide')
  @endif

  @if(($wikiPageSlug ?? '') === \App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE)
  @include('seo.sab.partials._wiki-admin-abuse-guide')
  @endif

  @if(empty($rebirthsGuide) || $wikiPageTotal > 0)
  <section class="sab-wiki-catalog" id="topic-list" aria-labelledby="topic-list-title">
    <div class="sab-wiki-section-head">
      <div>
        <h2 id="topic-list-title">{{ $wikiCatalogTitle }}</h2>
        <p class="sab-wiki-section-desc">{{ $wikiCatalogDesc }}</p>
      </div>
    </div>

    @if($wikiPageTotal > 0)
    <div class="sab-wiki-controls" data-wiki-controls>
      <label>
        <span class="sr-only">Search Brainrots</span>
        <input class="sab-wiki-search" type="search" placeholder="Search Brainrot name or data…" autocomplete="off" data-wiki-search>
      </label>
      <label>
        <span class="sr-only">Sort Brainrots</span>
        <select class="sab-wiki-sort" data-wiki-sort>
          <option value="value-desc" selected>Value ↓</option>
          <option value="value-asc">Value ↑</option>
          <option value="name-asc">A–Z</option>
          <option value="income-desc">Income: highest first</option>
          <option value="exist-asc">Exist Count: lowest first</option>
        </select>
      </label>
      <div class="sab-wiki-result" aria-live="polite" data-wiki-result>{{ number_format($wikiPageTotal) }} Brainrots shown</div>
    </div>
    @include('seo.sab.partials._wiki-table')
    @else
    <p class="sab-wiki-empty">No confirmed items are stored for this topic yet. Unconfirmed community lists are not written as facts.</p>
    @endif
  </section>
  @endif

  <section class="sab-wiki-panel" id="faq">
    <h2>{{ $wikiH1 }} FAQ</h2>
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

@section('scripts')
@include('seo.sab.partials._wiki-scripts')
@endsection

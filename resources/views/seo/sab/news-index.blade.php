@extends('seo.sab.layout')

@section('content')
<nav class="text-sm text-slate-400 mb-5">
  @php $sabBreadcrumbHomeHref = (($urlPrefix ?? '') === '') ? '/' : $urlPrefix; @endphp
  <a href="{{ $sabBreadcrumbHomeHref }}" class="hover:text-cyan-300">{{ $t['breadcrumb_home'] ?? 'Home' }}</a>
  <span class="mx-2 text-slate-600">›</span>
  <span class="text-slate-200">{{ $t['breadcrumb_news'] ?? 'News' }}</span>
</nav>

<section class="max-w-3xl">
  <header class="mb-6">
    <h1 class="text-lg md:text-xl font-bold tracking-tight text-slate-100 leading-snug mb-2">{{ $t['news_list_h1'] ?? 'News' }}</h1>
    <p class="text-sm md:text-base text-slate-400 leading-relaxed">{{ $t['news_list_intro'] ?? '' }}</p>
  </header>

  @if($articles->isEmpty())
    <p class="text-slate-500">{{ $t['news_list_empty'] ?? 'No published articles yet.' }}</p>
  @else
    <ul class="divide-y divide-white/10 border-t border-white/10">
      @foreach($articles as $row)
      @php
        $rowHref = $urlPrefix . '/news/' . $row->slug;
      @endphp
      <li>
        <a href="{{ $rowHref }}" class="group block py-4 -mx-3 px-3 rounded-lg hover:bg-white/5 transition-colors cursor-pointer">
          <article>
            <p class="text-sm font-normal text-cyan-200/95 group-hover:text-cyan-300 group-hover:underline underline-offset-2 decoration-cyan-500/50 mb-1">
              {{ $row->title }}
            </p>
            @if($row->excerpt)
            <p class="text-sm text-slate-400 line-clamp-2 mb-2">{{ $row->excerpt }}</p>
            @endif
            @php $rowDate = $row->published_at ?? $row->created_at; @endphp
            @if($rowDate)
            <div class="text-xs text-slate-500">{{ $rowDate->toFormattedDateString() }}</div>
            @endif
          </article>
        </a>
      </li>
      @endforeach
    </ul>
  @endif
</section>
@endsection

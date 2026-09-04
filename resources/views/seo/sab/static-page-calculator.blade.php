@extends('seo.sab.layout-calculator')

@section('content')
@php
  $legalSlug = (string) ($legalSlug ?? $article->slug ?? 'about-us');
  $kicker = match ($legalSlug) {
    'about-us' => 'About this site',
    'faq' => 'FAQ',
    default => 'Legal',
  };
  $pageTitle = (string) ($pageHeading ?? $article->title ?? 'About Us');
@endphp
<article class="sab-calc-legal">
  <nav class="sab-calc-legal-breadcrumb" aria-label="Breadcrumb">
    <a href="{{ ($urlPrefix ?? '') === '' ? '/' : rtrim($urlPrefix, '/') }}">Home</a>
    <span aria-hidden="true">/</span>
    <span>{{ $pageTitle }}</span>
  </nav>
  <span class="sab-calc-legal-kicker">{{ $kicker }}</span>
  <h1 class="sab-calc-legal-title">{{ $pageTitle }}</h1>
  @if(!empty($article?->excerpt))
    <p class="sab-calc-legal-lead">{{ $article->excerpt }}</p>
  @endif
  <div class="sab-legal-body">
    {!! $bodyHtml !!}
  </div>
</article>
@endsection

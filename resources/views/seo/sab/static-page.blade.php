@extends('seo.sab.layout')

@section('content')
@include('seo.sab.partials._legal_page_open')

  <div class="sab-legal-body">
    {!! $bodyHtml !!}
  </div>

@include('seo.sab.partials._legal_page_close')
@endsection

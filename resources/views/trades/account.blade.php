@extends('trades.layout')

@section('content')
<h1 class="text-2xl font-black mb-3">Your account</h1>
<p class="text-slate-400 mb-6 max-w-xl">Signed in as {{ $accountUser->display_name ?: $accountUser->username }}.</p>
@if($accountUser->email)
  <p class="text-sm text-slate-300">Email: {{ $accountUser->email }}</p>
@endif
@endsection

@if(!empty($wikiCrumbs ?? []))
<nav aria-label="Breadcrumb">
  <ol class="sab-wiki-crumbs">
    @foreach($wikiCrumbs as $index => $crumb)
      <li>
        @if($index > 0)<span aria-hidden="true"> / </span>@endif
        @if(!empty($crumb['current']))
          <span aria-current="page">{{ $crumb['name'] }}</span>
        @else
          <a href="{{ $crumb['href'] }}">{{ $crumb['name'] }}</a>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
@endif

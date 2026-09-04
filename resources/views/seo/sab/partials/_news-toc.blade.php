<nav class="sab-news-toc-nav" aria-label="{{ $t['news_toc_title'] ?? 'On this page' }}">
  @foreach($toc as $item)
  <a href="#{{ $item['id'] }}" class="sab-news-toc-link{{ ($item['level'] ?? 2) === 3 ? ' is-h3' : '' }}">{{ $item['text'] }}</a>
  @endforeach
</nav>

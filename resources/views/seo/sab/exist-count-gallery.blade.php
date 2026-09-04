@extends('seo.sab.layout')

@section('content')
<style>
  .gallery-filter.is-active {
    border-color: rgb(34, 211, 238);
    background: rgba(8, 145, 178, 0.18);
    color: rgb(165, 243, 252);
  }
  .gallery-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1.25rem;
    margin-bottom: 1.25rem;
  }
  .gallery-filter {
    line-height: 1.35;
    white-space: normal;
  }
  .gallery-filter span {
    display: inline-block;
    margin-left: 0.35rem;
  }
  .gallery-lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(2, 6, 23, 0.94);
    padding: 1rem;
  }
  .gallery-lightbox[hidden] {
    display: none;
  }
  .gallery-lightbox-inner {
    display: flex;
    flex-direction: column;
    height: 100%;
    max-width: 72rem;
    margin: 0 auto;
  }
  .gallery-lightbox-media {
    flex: 1;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    background: rgb(15, 23, 42);
  }
  .gallery-lightbox-media img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    padding: 0.75rem;
  }
</style>

<header class="-mx-4 px-4 py-5 border-b border-white/10">
  <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-100">
    {{ $t['exist_count_gallery_h1'] }}
  </h1>
  <p class="text-xs text-slate-500 mt-2">{{ $t['exist_count_gallery_stat_label'] }}: <span class="text-cyan-400 font-semibold">{{ number_format($galleryStatTotal) }}</span></p>
</header>

<section class="mt-8 mb-16">
  @if($galleryStatTotal === 0)
    <div class="rounded-lg border border-amber-400/20 bg-amber-400/10 px-4 py-4 text-sm text-amber-100">
      {{ $t['exist_count_gallery_empty'] }}
    </div>
  @else
    <div class="gallery-filter-row" data-gallery-filters>
      <button type="button" class="gallery-filter is-active shrink-0 rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-sm font-semibold text-slate-200" data-gallery-filter="all">
        {{ $t['exist_count_gallery_all'] }} <span class="text-slate-500">{{ number_format($galleryStatTotal) }}</span>
      </button>
      @foreach($galleryRarities as $rarity)
        <button type="button" class="gallery-filter rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-sm font-semibold text-slate-300 hover:border-cyan-400/60 hover:text-cyan-200" data-gallery-filter="{{ $rarity['key'] }}">
          {{ $rarity['label'] }} <span class="text-slate-500">{{ number_format($rarity['count']) }}</span>
        </button>
      @endforeach
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6" data-gallery-grid>
      @foreach($galleryItems as $item)
        @php
          $imgSrc = $item['local_image_url'] ? ('/' . ltrim($item['local_image_url'], '/')) : '';
          $remoteSrc = $item['remote_image_url'] ?? '';
        @endphp
        <article class="gallery-card rounded-lg border border-white/10 bg-slate-900/80 p-2" data-rarity="{{ $item['rarity_key'] }}">
          @if($imgSrc)
            <button type="button"
                    class="block aspect-square w-full overflow-hidden rounded-md border border-white/10 bg-slate-800"
                    data-gallery-open
                    data-name="{{ $item['name'] }}"
                    data-rarity="{{ $item['rarity'] }}"
                    data-src="{{ $imgSrc }}">
              <img src="{{ $imgSrc }}" alt="{{ $item['name'] }}" class="h-full w-full object-contain" loading="lazy" />
            </button>
          @elseif($remoteSrc)
            <button type="button"
                    class="block aspect-square w-full overflow-hidden rounded-md border border-white/10 bg-slate-800"
                    data-gallery-open
                    data-name="{{ $item['name'] }}"
                    data-rarity="{{ $item['rarity'] }}"
                    data-src="{{ $remoteSrc }}">
              <img src="{{ $remoteSrc }}" alt="{{ $item['name'] }}" class="h-full w-full object-contain" loading="lazy" />
            </button>
          @else
            <div class="flex aspect-square w-full items-center justify-center rounded-md border border-white/10 bg-slate-800 px-2 text-center text-xs text-slate-500">
              {{ $t['exist_count_gallery_image_missing'] }}
            </div>
          @endif

          <div class="mt-2 min-h-[4.5rem]">
            <h2 class="text-sm font-semibold leading-snug text-slate-100">{{ $item['name'] }}</h2>
            @if($item['rarity'])
              <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-cyan-300">{{ $item['rarity'] }}</p>
            @endif
            @if($item['download_error'])
              <p class="mt-1 text-xs text-amber-300">{{ $item['download_error'] }}</p>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @endif
</section>

<section class="mb-16 border-t border-white/10 pt-8">
  <div class="max-w-3xl">
    <h2 class="text-lg font-bold text-slate-100">{{ $t['exist_count_gallery_h1'] }}</h2>
    <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ $t['exist_count_gallery_intro'] }}</p>
    <p class="mt-3 text-sm leading-relaxed text-slate-400">{{ $t['exist_count_gallery_update_note'] }}</p>
  </div>
</section>

<section id="faq" class="mb-16 scroll-mt-20">
  <h2 class="text-xl font-black mb-4">{{ $t['exist_count_gallery_faq_h2'] }}</h2>
  <div class="grid md:grid-cols-2 gap-4">
    @foreach($t['exist_count_gallery_faq_items'] as $faqItem)
      <div class="rounded-lg border border-white/10 bg-slate-900 p-4">
        <h3 class="font-bold text-slate-100">{{ $faqItem[0] }}</h3>
        <p class="text-slate-400 text-sm mt-2 leading-relaxed">{{ $faqItem[1] }}</p>
      </div>
    @endforeach
  </div>
</section>

<div class="gallery-lightbox" hidden data-gallery-lightbox>
  <div class="gallery-lightbox-inner">
    <div class="mb-3 flex items-center justify-between gap-3">
      <div>
        <p class="text-sm font-semibold text-slate-100" data-gallery-lightbox-title></p>
        <p class="text-xs uppercase tracking-wide text-cyan-300" data-gallery-lightbox-rarity></p>
      </div>
      <button type="button" class="rounded-lg border border-white/10 bg-slate-900 px-3 py-2 text-sm font-semibold text-slate-200 hover:border-cyan-400/60 hover:text-cyan-200" data-gallery-close>
        Close
      </button>
    </div>
    <div class="gallery-lightbox-media">
      <img src="" alt="" data-gallery-lightbox-image />
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    var filterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-filter]'));
    var cards = Array.prototype.slice.call(document.querySelectorAll('.gallery-card'));
    var lightbox = document.querySelector('[data-gallery-lightbox]');
    var lightboxImage = document.querySelector('[data-gallery-lightbox-image]');
    var lightboxTitle = document.querySelector('[data-gallery-lightbox-title]');
    var lightboxRarity = document.querySelector('[data-gallery-lightbox-rarity]');

    filterButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        var selected = button.getAttribute('data-gallery-filter');
        filterButtons.forEach(function (other) { other.classList.remove('is-active'); });
        button.classList.add('is-active');
        cards.forEach(function (card) {
          var show = selected === 'all' || card.getAttribute('data-rarity') === selected;
          card.classList.toggle('hidden', !show);
        });
      });
    });

    document.querySelectorAll('[data-gallery-open]').forEach(function (button) {
      button.addEventListener('click', function () {
        if (!lightbox || !lightboxImage) return;
        var src = button.getAttribute('data-src') || '';
        var name = button.getAttribute('data-name') || '';
        var rarity = button.getAttribute('data-rarity') || '';
        lightboxImage.setAttribute('src', src);
        lightboxImage.setAttribute('alt', name);
        if (lightboxTitle) lightboxTitle.textContent = name;
        if (lightboxRarity) lightboxRarity.textContent = rarity;
        lightbox.hidden = false;
        document.body.style.overflow = 'hidden';
      });
    });

    function closeLightbox() {
      if (!lightbox || !lightboxImage) return;
      lightbox.hidden = true;
      lightboxImage.setAttribute('src', '');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-gallery-close]').forEach(function (button) {
      button.addEventListener('click', closeLightbox);
    });
    if (lightbox) {
      lightbox.addEventListener('click', function (event) {
        if (event.target === lightbox) closeLightbox();
      });
    }
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeLightbox();
    });
  })();
</script>
@endsection

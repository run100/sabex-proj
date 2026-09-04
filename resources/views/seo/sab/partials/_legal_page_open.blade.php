<div class="mx-auto max-w-5xl py-8 sm:py-10 md:py-14">
  <article class="overflow-hidden rounded-2xl border border-white/10 bg-slate-950/70 shadow-xl">
    <header class="border-b border-white/10 bg-gradient-to-br from-cyan-500/10 via-slate-900 to-purple-500/10 px-5 py-8 sm:px-8 md:px-10 md:py-10">
      <div class="mb-3 inline-flex rounded-full border border-cyan-400/30 bg-cyan-400/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-cyan-200">
        SAB Exist Count
      </div>
      <h1 class="max-w-3xl text-3xl font-black leading-tight tracking-tight text-slate-100 md:text-4xl">
        {{ $pageHeading ?? $article->title ?? 'About SAB Exist Count' }}
      </h1>
      @if(!empty($article?->excerpt))
        <p class="mt-4 max-w-3xl text-base leading-relaxed text-slate-300 md:text-lg">
          {{ $article->excerpt }}
        </p>
      @endif
    </header>
    <div class="px-5 py-8 sm:px-8 md:px-10 md:py-10">

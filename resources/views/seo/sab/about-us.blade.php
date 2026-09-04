@extends('seo.sab.layout')

@section('content')
@include('seo.sab.partials._legal_page_open')

    {{-- Intro block --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5">
      <h1 class="text-3xl md:text-4xl font-black text-slate-100 tracking-tight">About Us</h1>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count is an independent information website created to help players quickly check and understand
        Exist Count data for Steal a Brainrot items.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        In Steal a Brainrot, item counts and availability can change over time. Many players want a simple place to view
        updated item information without searching through multiple pages, screenshots, or community discussions.
        SAB Exist Count was built to make that process easier.
      </p>
    </div>

    {{-- What We Provide --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">What We Provide</h2>
      <p class="text-slate-400 text-base leading-relaxed">Our website focuses on providing useful and easy-to-read information, including:</p>
      <ul class="list-none space-y-3 pl-0 text-slate-400 text-base leading-relaxed">
        <li>Steal a Brainrot Exist Count references</li>
        <li>Item information and basic descriptions</li>
        <li>Helpful notes about item rarity and availability</li>
        <li>Simple pages designed for quick checking</li>
        <li>Regular content updates when new information is available</li>
      </ul>
    </div>

    {{-- Our Goal --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">Our Goal</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        Our goal is to make SAB Exist Count a clean, useful, and regularly updated reference site for players who care about
        Steal a Brainrot item counts. We try to keep the website simple, fast, and easy to use on both desktop and mobile devices.
      </p>
    </div>

    {{-- Accuracy and Updates --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">Accuracy and Updates</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        We do our best to keep the information on this website accurate and up to date. However, game data, item counts,
        and community information may change quickly. Because of this, the information on SAB Exist Count should be used
        as a helpful reference, not as an official source.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        If you notice outdated or incorrect information, you are welcome to contact us. We appreciate helpful feedback from users.
      </p>
    </div>

    {{-- Independent Website --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">Independent Website</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count is an independent fan-made information website. We are not affiliated with, endorsed by, sponsored by,
        or officially connected to Roblox Corporation, Steal a Brainrot, or any related rights holders.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        All game names, item names, and related references are used only for identification and informational purposes.
      </p>
    </div>

    {{-- Contact Us --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">Contact Us</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        For questions, corrections, feedback, or general inquiries, please contact us at:
        <a href="mailto:support@sabexistcount.com" class="text-cyan-400 hover:underline">support@sabexistcount.com</a>
      </p>
    </div>

@include('seo.sab.partials._legal_page_close')
@endsection

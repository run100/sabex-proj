@extends('seo.sab.layout')

@section('content')
@include('seo.sab.partials._legal_page_open')

    {{-- Intro block --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5">
      <header class="flex flex-col gap-y-2 md:gap-y-3">
        <h1 class="text-3xl md:text-4xl font-black text-slate-100 tracking-tight">Terms of Service</h1>
        <p class="text-slate-500 text-sm">Last updated: May 10, 2026</p>
      </header>
      <p class="text-slate-400 text-base leading-relaxed">
        Welcome to SAB Exist Count. These Terms of Service explain the rules and conditions for using
        <strong class="text-slate-300">sabexistcount.com</strong>. By accessing or using this website, you agree to these terms.
      </p>
    </div>

    {{-- 1. About This Website --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">1. About This Website</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count is an informational website that provides Exist Count references, item information, and related content
        for Steal a Brainrot. The website is designed to help users check and understand item availability and related game information.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        We are not the official developer, publisher, or owner of Steal a Brainrot, Roblox, or any related game brands.
        All game names, item names, and related references belong to their respective owners.
      </p>
    </div>

    {{-- 2. Use of the Website --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">2. Use of the Website</h2>
      <p class="text-slate-400 text-base leading-relaxed">You agree to use this website only for lawful and appropriate purposes. You must not:</p>
      <ul class="list-none space-y-3 pl-0 text-slate-400 text-base leading-relaxed">
        <li>Use the website in a way that may damage, disable, or overload the site</li>
        <li>Attempt to gain unauthorized access to any part of the website</li>
        <li>Copy, scrape, or republish large portions of the website without permission</li>
        <li>Use the website to spread spam, malware, or harmful content</li>
        <li>Misrepresent our website as an official game or Roblox service</li>
      </ul>
    </div>

    {{-- 3. Informational Content --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">3. Informational Content</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        The content on SAB Exist Count is provided for general informational purposes only. We try to keep information accurate
        and updated, but Exist Count data and game-related information may change frequently.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        We do not guarantee that all information on the website will always be complete, current, or error-free.
        Users should treat the data as a helpful reference rather than an official source.
      </p>
    </div>

    {{-- 4. No Official Affiliation --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">4. No Official Affiliation</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count is an independent fan-made information website. It is not affiliated with, endorsed by, sponsored by,
        or officially connected to Roblox Corporation, Steal a Brainrot, or any related rights holders.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        Any trademarks, logos, game names, or item names mentioned on this website are used for identification and informational purposes only.
      </p>
    </div>

    {{-- 5. Third-Party Links --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">5. Third-Party Links</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        Our website may contain links to third-party websites or resources. These links are provided for convenience and reference only.
        We do not control or endorse the content, policies, or practices of third-party websites.
      </p>
    </div>

    {{-- 6. Advertising --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">6. Advertising</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count may display advertisements from third-party advertising networks, including Google AdSense.
        Advertisements help support the operation and maintenance of the website.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        We are not responsible for the content of third-party advertisements or the websites they link to.
      </p>
    </div>

    {{-- 7. Limitation of Liability --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">7. Limitation of Liability</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count is provided on an &ldquo;as is&rdquo; and &ldquo;as available&rdquo; basis. We are not responsible for any loss, damage,
        misunderstanding, or issue that may result from using the information on this website.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">Your use of the website is at your own risk.</p>
    </div>

    {{-- 8. Changes to the Website --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">8. Changes to the Website</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        We may update, change, remove, or improve any part of the website at any time without prior notice.
        We may also update these Terms of Service when necessary.
      </p>
    </div>

    {{-- 9. Termination of Access --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">9. Termination of Access</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        We reserve the right to restrict or block access to the website if we believe a user is abusing the website,
        violating these terms, or creating security or operational risks.
      </p>
    </div>

    {{-- 10. Contact Us --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">10. Contact Us</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        If you have any questions about these Terms of Service, please contact us at:
        <a href="mailto:support@sabexistcount.com" class="text-cyan-400 hover:underline">support@sabexistcount.com</a>
      </p>
    </div>

@include('seo.sab.partials._legal_page_close')
@endsection

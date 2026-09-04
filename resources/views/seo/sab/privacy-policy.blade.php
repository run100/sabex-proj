@extends('seo.sab.layout')

@section('content')
@include('seo.sab.partials._legal_page_open')

    {{-- Intro block --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5">
      <header class="flex flex-col gap-y-2 md:gap-y-3">
        <h1 class="text-3xl md:text-4xl font-black text-slate-100 tracking-tight">Privacy Policy</h1>
        <p class="text-slate-500 text-sm">Last updated: May 10, 2026</p>
      </header>
      <p class="text-slate-400 text-base leading-relaxed">
        Welcome to SAB Exist Count. This Privacy Policy explains how we collect, use, and protect information when you visit
        <strong class="text-slate-300">sabexistcount.com</strong>. By using this website, you agree to the practices described in this policy.
      </p>
    </div>

    {{-- 1. Information We Collect --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">1. Information We Collect</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count is an informational website that provides Exist Count data and related information for Steal a Brainrot items.
        We do not require users to create an account, log in, or submit personal information to browse the site.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">However, we may collect limited non-personal information automatically, such as:</p>
      <ul class="list-none space-y-3 pl-0 text-slate-400 text-base leading-relaxed">
        <li>Browser type and version</li>
        <li>Device type</li>
        <li>Pages visited on our website</li>
        <li>Approximate location based on IP address</li>
        <li>Referring pages or search terms</li>
        <li>Date and time of visits</li>
      </ul>
      <p class="text-slate-400 text-base leading-relaxed">
        This information is used to understand how visitors use our website and to improve our content, performance, and user experience.
      </p>
    </div>

    {{-- 2. Cookies --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">2. Cookies and Similar Technologies</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        We may use cookies or similar technologies to improve website functionality, analyze traffic, and support advertising services.
        Cookies are small files stored on your device by your browser.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        You can choose to disable cookies through your browser settings. Please note that some parts of the website may not function
        properly if cookies are disabled.
      </p>
    </div>

    {{-- 3. Google AdSense --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">3. Google AdSense and Third-Party Advertising</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count may display advertisements provided by third-party advertising partners, including Google AdSense.
        These third-party vendors may use cookies to serve ads based on your visits to this and other websites.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        Google may use advertising cookies to help serve more relevant ads. Users may manage or opt out of personalized advertising
        through Google advertising settings.
      </p>
      <p class="text-slate-400 text-base leading-relaxed">
        We do not control the cookies or tracking technologies used by third-party advertisers. Please review the privacy policies
        of those third-party services for more information.
      </p>
    </div>

    {{-- 4. Analytics --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">4. Analytics</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        We may use analytics tools to understand website traffic, popular pages, visitor behavior, and technical performance.
        Analytics data is generally aggregated and does not personally identify individual visitors.
      </p>
    </div>

    {{-- 5. How We Use Information --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">5. How We Use Information</h2>
      <p class="text-slate-400 text-base leading-relaxed">Information collected may be used to:</p>
      <ul class="list-none space-y-3 pl-0 text-slate-400 text-base leading-relaxed">
        <li>Improve website content and layout</li>
        <li>Monitor site performance and fix technical issues</li>
        <li>Understand which pages are useful to visitors</li>
        <li>Protect the website from spam, abuse, or security risks</li>
        <li>Support advertising and analytics services</li>
      </ul>
    </div>

    {{-- 6. Third-Party Links --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">6. Third-Party Links</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        Our website may include links to third-party websites, community resources, game-related pages, or reference sources.
        We are not responsible for the privacy practices, content, or policies of external websites.
      </p>
    </div>

    {{-- 7. Data Security --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">7. Data Security</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        We take reasonable steps to keep our website secure. However, no method of transmission over the internet or electronic
        storage is completely secure. We cannot guarantee absolute security.
      </p>
    </div>

    {{-- 8. Children's Privacy --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">8. Children's Privacy</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        SAB Exist Count is intended as a general informational website. We do not knowingly collect personal information from children.
        If you believe that a child has provided personal information to us, please contact us so we can take appropriate action.
      </p>
    </div>

    {{-- 9. Changes to This Privacy Policy --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">9. Changes to This Privacy Policy</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        We may update this Privacy Policy from time to time. Any changes will be posted on this page with an updated "Last updated" date.
        Continued use of the website after changes means you accept the updated policy.
      </p>
    </div>

    {{-- 10. Contact Us --}}
    <div class="flex flex-col gap-y-4 md:gap-y-5 border-t border-white/10 pt-8 md:pt-10">
      <h2 class="text-lg md:text-xl font-bold text-slate-200">10. Contact Us</h2>
      <p class="text-slate-400 text-base leading-relaxed">
        If you have any questions about this Privacy Policy, please contact us at:
        <a href="mailto:support@sabexistcount.com" class="text-cyan-400 hover:underline">support@sabexistcount.com</a>
      </p>
    </div>

@include('seo.sab.partials._legal_page_close')
@endsection

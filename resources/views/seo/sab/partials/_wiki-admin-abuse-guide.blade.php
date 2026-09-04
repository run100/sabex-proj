@php
  $adminSchedule = is_array($adminAbuse ?? null) ? $adminAbuse : [];
  $adminEvent = is_array($adminSchedule['admin_abuse'] ?? null) ? $adminSchedule['admin_abuse'] : null;
  $tacoEvent = is_array($adminSchedule['taco_tuesday'] ?? null) ? $adminSchedule['taco_tuesday'] : null;
  $timezoneRows = is_array($adminSchedule['timezone_rows'] ?? null) ? $adminSchedule['timezone_rows'] : [];
  $mechanics = array_values(array_filter((array) ($adminSchedule['mechanics'] ?? [])));
  $mechanicCopy = [
    'Luck multipliers' => 'Hosts can raise spawn luck for a short window. That can make rarer Brainrots and Lucky Blocks appear more often than a normal session. The exact multiplier is not stored here, so this page does not invent a number.',
    'Lucky Blocks' => 'Lucky Blocks are the most common stored Admin Abuse obtain path. Open a linked Lucky Block for its Exist Count and SAB Value instead of treating every block as a guaranteed drop.',
    'Limited machines or events' => 'Some windows open a limited machine or a short event that is not in the regular shop. A recent Taco Tuesday example is the Taco Merchant, which is a dated window, not a weekly promise.',
    'Rare Brainrot spawns' => 'Rare or limited Brainrots can appear during the window and then leave the spawn pool. Confirm the stored obtain method on the product page before treating a name as an Admin Abuse exclusive.',
  ];
@endphp

<nav class="sab-wiki-panel" aria-labelledby="admin-abuse-contents-title">
  <p class="sab-wiki-nav-title" id="admin-abuse-contents-title">On this page</p>
  <ol class="sab-wiki-toc">
    <li><a href="#admin-abuse-quick">Quick answer</a></li>
    <li><a href="#what-is-admin-abuse">What is Admin Abuse</a></li>
    <li><a href="#admin-abuse-status">Current status</a></li>
    <li><a href="#admin-abuse-timezones">Timezone table</a></li>
    <li><a href="#saturday-vs-taco-tuesday">Saturday vs Taco Tuesday</a></li>
    <li><a href="#saturday-updates">Recent Saturday updates</a></li>
    <li><a href="#what-happens-admin-abuse">What typically happens</a></li>
    <li><a href="#how-to-join-admin-abuse">How to join</a></li>
    <li><a href="#after-admin-abuse">After the window</a></li>
    <li><a href="#admin-abuse-rewards">Rewards and limited drops</a></li>
    <li><a href="#topic-list">Related Brainrots</a></li>
    <li><a href="#faq">FAQ</a></li>
  </ol>
</nav>

<section class="sab-wiki-panel sab-wiki-rebirth-quick" id="admin-abuse-quick">
  <h2>Quick Answer</h2>
  @if($adminEvent || $tacoEvent)
  <p>Steal a Brainrot Admin Abuse is a developer-hosted window, not an exploit.
    @if($adminEvent)
    The next stored Admin Abuse is <strong>{{ $adminEvent['eastern_time'] }}</strong> ({{ $adminEvent['recurrence_label'] }} · {{ $adminEvent['duration_label'] }}).
    @else
    The next Admin Abuse time is not confirmed.
    @endif
    @if($tacoEvent)
    The next stored Taco Tuesday is <strong>{{ $tacoEvent['eastern_time'] }}</strong> ({{ $tacoEvent['recurrence_label'] }} · {{ $tacoEvent['duration_label'] }}).
    @else
    Taco Tuesday is not confirmed.
    @endif
    Use the timezone table if you are not on Eastern Time.</p>
  @else
  <p>The next Admin Abuse and Taco Tuesday times are not confirmed. This page does not guess a date or countdown. Admin Abuse is still a developer-hosted window, not an exploit; join only when a stored time appears on the status card.</p>
  @endif
</section>

<section class="sab-wiki-panel" id="what-is-admin-abuse">
  <h2>What is Admin Abuse in Steal a Brainrot?</h2>
  <p>Admin Abuse is a limited window where a host uses in-game admin tools to change what can spawn. It is not a shop listing and it is not a glitch. Joining the window is not a ban reason. The stored length sits on the status card; missing duration stays Not confirmed.</p>
  <p>The window applies to servers that are already open, including a private server. That is why some players open a private server: the same window still runs, with fewer steal contests. It is a community preference, not a requirement.</p>
  <p>Taco Tuesday is listed here because players search both names. It is a separate Tuesday window, not another name for Saturday Admin Abuse.</p>
</section>

<section class="sab-wiki-panel sab-wiki-schedule" id="admin-abuse-status">
  <div class="sab-wiki-schedule-head">
    <div>
      <h2>Admin Abuse schedule</h2>
      <p>Eastern Time is the reference timezone. The browser adds a local conversion and countdown when a confirmed timestamp is available.</p>
    </div>
    <span class="sab-wiki-status sab-wiki-status-{{ $adminSchedule['status'] ?? 'not_confirmed' }}">{{ $adminSchedule['status_label'] ?? 'Not Confirmed' }}</span>
  </div>
  <div class="sab-wiki-schedule-grid">
    @foreach([
      'admin_abuse' => ['label' => 'Next Admin Abuse', 'empty' => 'Admin Abuse time is not confirmed.'],
      'taco_tuesday' => ['label' => 'Next Taco Tuesday', 'empty' => 'Taco Tuesday time is not confirmed.'],
    ] as $key => $card)
    @php($event = is_array($adminSchedule[$key] ?? null) ? $adminSchedule[$key] : null)
    <article class="sab-wiki-schedule-card" data-admin-event-card="{{ $key }}">
      <h3>{{ $card['label'] }}</h3>
      @if($event)
      <p class="sab-wiki-schedule-state">{{ $event['status_label'] }}</p>
      <p class="sab-wiki-schedule-time"><time datetime="{{ $event['next_event_at'] }}">{{ $event['eastern_time'] }}</time></p>
      <p class="sab-wiki-schedule-local">Your local time: <time data-admin-abuse-local datetime="{{ $event['next_event_at'] }}">Converting…</time></p>
      <p class="sab-wiki-countdown" data-admin-abuse-countdown data-target="{{ $event['next_event_at'] }}">Countdown loading…</p>
      <p class="sab-wiki-card-meta">Usually {{ $event['recurrence_label'] }} · Duration: {{ $event['duration_label'] }}</p>
      @else
      <p class="sab-wiki-schedule-time">{{ $card['empty'] }}</p>
      <p class="sab-wiki-card-meta">Not confirmed. No date or countdown is guessed.</p>
      @endif
    </article>
    @endforeach
  </div>
  <p class="sab-wiki-source-note">Source checked: {{ $adminSchedule['source_checked_label'] ?? 'Unknown' }} · <a href="{{ \App\Services\Seo\SabWikiAdminAbuseScheduleService::ADMIN_ABUSE_SOURCE }}" rel="nofollow noopener">Admin Abuse source</a> · <a href="{{ \App\Services\Seo\SabWikiAdminAbuseScheduleService::TACO_TUESDAY_SOURCE }}" rel="nofollow noopener">Taco Tuesday source</a></p>
</section>

<section class="sab-wiki-panel" id="admin-abuse-timezones">
  <h2>Admin Abuse time by timezone</h2>
  <p>These rows convert the next stored Eastern Time occurrence. Daylight saving time is applied automatically. If a column is empty, that event is not confirmed.</p>
  @if($timezoneRows !== [])
  <div class="sab-wiki-table-wrap sab-wiki-timezone-wrap">
    <table class="sab-wiki-table sab-wiki-timezone-table">
      <thead>
        <tr>
          <th scope="col">Timezone</th>
          <th scope="col">Next Admin Abuse</th>
          <th scope="col">Next Taco Tuesday</th>
        </tr>
      </thead>
      <tbody>
        @foreach($timezoneRows as $row)
        <tr data-timezone-row="{{ $row['id'] }}">
          <td data-label="Timezone"><strong>{{ $row['label'] }}</strong></td>
          <td data-label="Next Admin Abuse">
            @if(!empty($row['admin_abuse']['label']))
            <time datetime="{{ $row['admin_abuse']['datetime'] }}">{{ $row['admin_abuse']['label'] }}</time>
            @else
            Not confirmed
            @endif
          </td>
          <td data-label="Next Taco Tuesday">
            @if(!empty($row['taco_tuesday']['label']))
            <time datetime="{{ $row['taco_tuesday']['datetime'] }}">{{ $row['taco_tuesday']['label'] }}</time>
            @else
            Not confirmed
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @else
  <p class="sab-wiki-empty">No timezone conversion is shown until a stored Admin Abuse or Taco Tuesday time exists.</p>
  @endif
  <p class="sab-wiki-schedule-note">Community schedule references can be delayed, canceled, or moved. A failed refresh keeps the last successful record and shows its checked time.</p>
</section>

<section class="sab-wiki-panel" id="saturday-vs-taco-tuesday">
  <h2>Saturday Admin Abuse vs Taco Tuesday</h2>
  <p>Keep the two windows separate. Saturday is usually tied to the weekly update. Tuesday is its own themed window. Duration uses the stored label on each card.</p>
  <div class="sab-wiki-definition-grid sab-wiki-admin-compare">
    <div class="sab-wiki-definition">
      <strong>Saturday Admin Abuse</strong>
      <p>{{ $adminEvent['recurrence_label'] ?? 'Not confirmed' }}@if($adminEvent) · {{ $adminEvent['duration_label'] }}@endif. New catalog names from that weekend belong on the <a href="{{ $newsIndexHref }}">News</a> log. Use the Saturday update notes below for dated patch summaries, not a promise of the same drops every week.</p>
    </div>
    <div class="sab-wiki-definition">
      <strong>Taco Tuesday</strong>
      <p>{{ $tacoEvent['recurrence_label'] ?? 'Not confirmed' }}@if($tacoEvent) · {{ $tacoEvent['duration_label'] }}@endif. A recent Tuesday window opened a Taco Merchant. That obtain method is Taco Merchant, not a weekly fixed drop. Read the <a href="{{ $tacoTuesdayNewsHref }}">August 18 Taco Tuesday note</a>.</p>
    </div>
  </div>
  @if(!empty($tacoMerchantItems))
  <p class="sab-wiki-admin-example">Dated Taco Merchant examples:
    @foreach($tacoMerchantItems as $index => $item)
    <a href="{{ $item['href'] }}">{{ $item['name'] }}</a>@if($index < count($tacoMerchantItems) - 1), @endif
    @endforeach
    . Check Exist Count and SAB Values on each product before treating the name as current supply.
  </p>
  @endif
</section>

<section class="sab-wiki-panel" id="saturday-updates">
  <h2>Recent Saturday updates</h2>
  <p>These are published Saturday update notes on this site. They are dated logs, not a promise of the same drops every week.</p>
  @if(!empty($saturdayUpdateNews))
  <div class="sab-wiki-card-list">
    @foreach($saturdayUpdateNews as $article)
    <article class="sab-wiki-card">
      <div>
        <a href="{{ $article['href'] }}">{{ $article['title'] }}</a>
        @if(!empty($article['dateLabel']))
        <p class="sab-wiki-card-meta">{{ $article['dateLabel'] }}</p>
        @endif
      </div>
      <a class="sab-wiki-details" href="{{ $article['href'] }}">Read update →</a>
    </article>
    @endforeach
  </div>
  @endif
  <div class="sab-wiki-tools">
    <a class="sab-wiki-tool" href="{{ $newsIndexHref }}">Open News</a>
  </div>
</section>

<section class="sab-wiki-panel" id="what-happens-admin-abuse">
  <h2>What typically happens during Admin Abuse?</h2>
  @if($mechanics !== [])
  <div class="sab-wiki-rebirth-steps">
    @foreach($mechanics as $mechanic)
    <div>
      <strong>{{ $mechanic }}</strong>
      <span>{{ $mechanicCopy[$mechanic] ?? 'This mechanic is stored on the source record. Unconfirmed extras stay off this page.' }}</span>
    </div>
    @endforeach
  </div>
  @else
  <p>No Admin Abuse mechanic is confirmed in the stored source yet. This page does not turn community guesses into game facts.</p>
  @endif
  <p>Use the related list below for Brainrots and Lucky Blocks whose stored obtain method explicitly mentions Admin Abuse, an Admin Event, or Taco Tuesday.</p>
</section>

<section class="sab-wiki-panel" id="how-to-join-admin-abuse">
  <h2>How to join Admin Abuse</h2>
  <p>This is a preparation list, not an official rulebook. Times can slip, so stay with the status card.</p>
  <div class="sab-wiki-rebirth-steps">
    <div><strong>1. Read the status card.</strong><span>If the card says Not confirmed, do not invent a start time. Live Now, Today, or Upcoming is the stored state.</span></div>
    <div><strong>2. Convert the time.</strong><span>Use the timezone table or the local conversion on the card. Eastern Time is the source display.</span></div>
    <div><strong>3. Log in early and clear space.</strong><span>Join before the stored start so you are already in a server. Clear parked copies you do not need so new drops have room. This is community prep, not a required slot count.</span></div>
    <div><strong>4. Move with the spawns.</strong><span>Lucky Blocks and limited spawns do not stay in one corner. Stay mobile, then open <a href="{{ $existCountHref }}">SAB Exist Count</a>, <a href="{{ $valueListHref }}">SAB Values</a>, and the <a href="{{ $calculatorHref }}">SAB Calculator</a> after the window.</span></div>
  </div>
  <div class="sab-wiki-rebirth-note">
    <strong>Private servers still see the same window</strong>
    <p>Some players use a private server to reduce steal contests. The stored window still applies. It is a preference, not a requirement.</p>
  </div>
</section>

<section class="sab-wiki-panel" id="after-admin-abuse">
  <h2>After the window</h2>
  <p>Exist Count and SAB Values can move after a busy window. Open the product page for any new copy before you trade. Use the <a href="{{ $calculatorHref }}">SAB Calculator</a> if the offer is a limited drop.</p>
  <div class="sab-wiki-definition-grid">
    <div class="sab-wiki-definition">
      <strong>Late join</strong>
      <p>You can still join after the stored start. The window is only as long as the stored duration, so a late join leaves less time. Extra minutes are not invented here.</p>
    </div>
    <div class="sab-wiki-definition">
      <strong>Lag during the window</strong>
      <p>Busy servers can hitch. Lower graphics or switch devices if the session stutters. This page does not treat lag as proof that the event was canceled.</p>
    </div>
  </div>
</section>

<section class="sab-wiki-panel" id="admin-abuse-rewards">
  <h2>Rewards and limited drops</h2>
  <p>Rewards and limited drops are listed only when the database stores a confirmed Admin Abuse or Taco Tuesday obtain method. Each linked item keeps its own rarity, Exist Count, and SAB Values instead of copying an external table.</p>
  @if($wikiPageTotal > 0)
  <div class="sab-wiki-card-list">
    @foreach(collect($wikiRows)->filter(fn (array $row): bool => in_array('admin-abuse', $row['topicKeys'] ?? [], true))->take(8) as $row)
    <article class="sab-wiki-card">
      <a href="{{ $row['productUrl'] }}">{{ $row['name'] }}</a>
      <span class="sab-wiki-card-meta">{{ $row['rarityLabel'] }} · {{ $row['existCountLabel'] }} Exist Count</span>
    </article>
    @endforeach
  </div>
  @else
  <p class="sab-wiki-empty">No confirmed limited drops are stored for this page yet.</p>
  @endif
</section>

<section class="sab-wiki-panel" id="admin-abuse-tools">
  <h2>Related SAB tools</h2>
  <div class="sab-wiki-tools">
    <a class="sab-wiki-tool" href="{{ $valueListHref }}">SAB Values</a>
    <a class="sab-wiki-tool" href="{{ $calculatorHref }}">SAB Calculator</a>
    <a class="sab-wiki-tool" href="{{ $existCountHref }}">SAB Exist Count</a>
    <a class="sab-wiki-tool" href="{{ $newsIndexHref }}">News and update log</a>
  </div>
</section>

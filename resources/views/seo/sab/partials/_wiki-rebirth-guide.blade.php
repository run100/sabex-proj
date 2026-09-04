@php
  $levels = $rebirths['levels'] ?? [];
  $groups = $rebirths['groups'] ?? [];
  $rebirthRarityClass = static function (string $label): string {
      $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $label), '-'));

      return 'brainrot-rarity-'.($slug !== '' ? $slug : 'default');
  };
  $brainrotLink = static function (string $name) use ($productUrlPrefix): string {
      $name = trim($name);
      if ($name === '') {
          return '';
      }

      $href = rtrim((string) $productUrlPrefix, '/').'/products/'.\Illuminate\Support\Str::slug($name);

      return '<a class="sab-wiki-rebirth-item" href="'.e($href).'">'.e($name).'</a>';
  };
  $brainrotsLinked = static function (string $raw) use ($brainrotLink): string {
      $parts = preg_split('/\s*\+\s*/', $raw) ?: [];

      return implode(' + ', array_filter(array_map($brainrotLink, $parts), static fn (string $html): bool => $html !== ''));
  };
@endphp

<section class="sab-wiki-panel sab-wiki-rebirth-quick" id="quick-answer">
  <h2>Quick Answer</h2>
  <p>Steal a Brainrot currently has <strong>19 Rebirth levels</strong>. Rebirth 19 needs <strong>$30Qa + La Grande Combinasion</strong> and pays <strong>x19 income, $250T starting cash, +1 slot, +10 seconds of base lock, and Grief Shield</strong>. The harder gate for most players is <strong>Rebirth 16</strong>: that is the first Secret requirement, Los Tralaleritos. Update 64 did not add Rebirth 20.</p>
</section>

<nav class="sab-wiki-panel" aria-label="Rebirth list contents">
  <p class="sab-wiki-nav-title">Jump to</p>
  <ol class="sab-wiki-toc">
    <li><a href="#all-rebirth-requirements">Requirements</a></li>
    <li><a href="#all-rebirth-rewards">Rewards</a></li>
    <li><a href="#how-to-rebirth">How to Rebirth</a></li>
    <li><a href="#what-do-you-lose">What You Lose</a></li>
    <li><a href="#best-time-to-rebirth">Best Time</a></li>
    <li><a href="#rebirth-tips">Tips</a></li>
    <li><a href="#rebirth-milestones">Milestones</a></li>
    <li><a href="#rebirth-19">Rebirth 19</a></li>
    <li><a href="#faq">FAQ</a></li>
  </ol>
</nav>

<section class="sab-wiki-panel" id="what-does-rebirth-do">
  <h2>What Does Rebirth Do in Steal a Brainrot?</h2>
  <p>Rebirth is a trade. You close the current run and keep a permanent upgrade. Cash, parked Brainrots, and the current base layout go away. The multiplier, starting cash, extra slot, and +10 seconds of lock time stay. Rebirth 2 and Rebirth 10 also add floors. Later levels unlock gear that only appears after that reset.</p>
  <p>That is why the button is not an auto-win. A low-supply Brainrot parked for the check can be worth more as a trade piece than as a one-time requirement. Use the <a href="{{ $existCountHref }}">SAB Exist Count List</a> and <a href="{{ $valueListHref }}">SAB Value List</a> before you spend a copy you cannot replace the same day.</p>
  <div class="sab-wiki-rebirth-note">
    <strong>Do not reset during a steal</strong>
    <p>A Brainrot still walking to your base can bounce back to the Red Carpet. A copy another player is already stealing can disappear when the reset fires. Finish the delivery, then open the confirmation screen.</p>
  </div>
</section>

<section class="sab-wiki-catalog" id="all-rebirth-requirements">
  <div class="sab-wiki-section-head">
    <div>
      <h2>All Steal a Brainrot Rebirth Requirements 1–19</h2>
      <p class="sab-wiki-section-desc">Cash is only half the check. The rarity column shows whether the next Brainrot is a farmable mid-tier or a Secret you should locate first. Use the <a href="{{ $calculatorHref }}">SAB Trading Calculator</a> to check each name's current trade value before you consume it.</p>
    </div>
  </div>
  <div class="sab-wiki-table-wrap sab-wiki-rebirth-table-wrap">
    <table class="sab-wiki-table sab-wiki-rebirth-table">
      <thead>
        <tr>
          <th>Rebirth</th>
          <th>Cash Required</th>
          <th>Brainrot Required</th>
          <th>Rarity</th>
        </tr>
      </thead>
      <tbody>
        @foreach($levels as $level)
        <tr id="rebirth-{{ $level['level'] }}" data-rebirth-requirement="{{ $level['level'] }}">
          <td data-label="Rebirth">
            <strong class="sab-wiki-rebirth-level">{{ $level['level'] }}</strong>
            @if(!empty($level['tag']))
            <span class="sab-wiki-rebirth-tag sab-wiki-rebirth-tag-{{ $level['tag_kind'] ?: 'default' }}">{{ $level['tag'] }}</span>
            @endif
          </td>
          <td data-label="Cash Required"><strong>{{ $level['cash'] }}</strong></td>
          <td data-label="Brainrot Required">{!! $brainrotsLinked($level['brainrots']) !!}</td>
          <td data-label="Rarity">
            <span class="sab-wiki-rebirth-rarities">
              @foreach(preg_split('/\s*\+\s*/', (string) $level['rarity']) as $rarityLabel)
              <span class="sab-wiki-tier {{ $rebirthRarityClass($rarityLabel) }}">{{ $rarityLabel }}</span>
              @endforeach
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>

@foreach($groups as $group)
<section class="sab-wiki-panel sab-wiki-rebirth-group" id="rebirths-{{ $group['id'] }}">
  <h2>{{ $group['title'] }}</h2>
  <figure class="sab-wiki-rebirth-figure">
    <img src="{{ $group['image'] }}" alt="{{ $group['alt'] }}" width="1800" height="1100" loading="lazy" decoding="async">
    <figcaption>{{ $group['caption'] }}</figcaption>
  </figure>
  <p>{{ $group['body'] }}</p>
</section>
@endforeach

<section class="sab-wiki-catalog" id="all-rebirth-rewards">
  <div class="sab-wiki-section-head">
    <div>
      <h2>All Steal a Brainrot Rebirth Rewards</h2>
      <p class="sab-wiki-section-desc">Every reset adds another +10 seconds of base lock time. The multiplier and starting cash are the permanent reason to reset; the gear unlock is the extra.</p>
    </div>
  </div>
  <div class="sab-wiki-table-wrap sab-wiki-rebirth-table-wrap">
    <table class="sab-wiki-table sab-wiki-rebirth-table">
      <thead>
        <tr>
          <th>Rebirth</th>
          <th>Multiplier</th>
          <th>Starting Cash</th>
          <th>Base Reward</th>
          <th>Special Unlock</th>
        </tr>
      </thead>
      <tbody>
        @foreach($levels as $level)
        <tr data-rebirth-reward="{{ $level['level'] }}">
          <td data-label="Rebirth"><strong class="sab-wiki-rebirth-level">{{ $level['level'] }}</strong></td>
          <td data-label="Multiplier"><strong class="sab-wiki-rebirth-gain">{{ $level['multiplier'] }}</strong></td>
          <td data-label="Starting Cash">{{ $level['cash_reward'] }}</td>
          <td data-label="Base Reward">{{ $level['base_reward'] ?: '-' }}</td>
          <td data-label="Special Unlock">{{ $level['special'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>

<section class="sab-wiki-panel" id="how-to-rebirth">
  <h2>How to Rebirth in Steal a Brainrot</h2>
  <p>Levels must be completed in order. The menu only offers the next reset, so you cannot skip from Rebirth 8 to Rebirth 10.</p>
  <div class="sab-wiki-rebirth-steps">
    <div><strong>1. Open the Rebirth menu.</strong><span>Use the rotating-arrows button on the left side of the Roblox UI. The next cash target and named Brainrot appear before you confirm.</span></div>
    <div><strong>2. Park the required Brainrot.</strong><span>The listed copy has to be in the base, not walking in from the Red Carpet. If two Brainrots are listed, both need to be parked.</span></div>
    <div><strong>3. Hit the cash line.</strong><span>Cash is the easier half after Rebirth 9. If the named Brainrot is still missing, keep farming it instead of sitting on a full cash bar.</span></div>
    <div><strong>4. Confirm, then refill the base.</strong><span>The reset clears parked units and drops cash to the listed starting amount. Fill the new slots immediately so the new multiplier starts paying.</span></div>
  </div>
</section>

<section class="sab-wiki-panel" id="what-do-you-lose">
  <h2>What Do You Lose When You Rebirth?</h2>
  <p>The confirmation screen is the live source. This split is what the current 19-level reset actually changes:</p>
  <div class="sab-wiki-definition-grid sab-wiki-rebirth-split">
    <div class="sab-wiki-definition">
      <strong>Reset with the run</strong>
      <p>Parked Brainrots, current cash above the new starting amount, and the current floor layout of that run. A walking delivery can bounce back to the Red Carpet. A mid-steal copy can vanish.</p>
    </div>
    <div class="sab-wiki-definition">
      <strong>Kept after the reset</strong>
      <p>The new income multiplier, the listed starting cash, extra slots, floor unlocks from Rebirth 2 and 10, +10 seconds of lock time per level, and gear already unlocked by earlier Rebirths.</p>
    </div>
  </div>
  <p>If the required Brainrot is a Secret or a low-count Brainrot God, check supply and trade value before you consume it. Open the <a href="{{ $existCountHref }}">SAB Exist Count List</a>, <a href="{{ $valueListHref }}">SAB Value List</a>, and <a href="{{ $calculatorHref }}">SAB Trading Calculator</a> first.</p>
</section>

<section class="sab-wiki-panel" id="best-time-to-rebirth">
  <h2>When Is the Best Time to Rebirth?</h2>
  <p>There is no single “always reset now” rule. The right moment changes when the next requirement stops being cash and starts being a named Brainrot.</p>
  <div class="sab-wiki-rebirth-when">
    <div>
      <strong>Rebirths 1–10</strong>
      <span>Reset as soon as Rebirth 2 or 10 is ready. The extra floor is the upgrade. Sitting on a finished check only delays the next multiplier.</span>
    </div>
    <div>
      <strong>Rebirths 9–15</strong>
      <span>Get the Brainrot God first, then finish the cash. After Rebirth 9, cash comes back faster than a missing God. A parked Cocofanto Elefanto or Girafa Celestre is the real gate.</span>
    </div>
    <div>
      <strong>Rebirths 16–19</strong>
      <span>Treat the Secret as the first requirement. Los Tralaleritos, the Rebirth 17 pair, Graipuss Medussi, and La Grande Combinasion are harder to replace than a $1Qa–$30Qa farm.</span>
    </div>
  </div>
</section>

<section class="sab-wiki-panel" id="rebirth-tips">
  <h2>Tips to Reach Max Rebirth Faster</h2>
  <div class="sab-wiki-rebirth-steps">
    <div><strong>Secure the named Brainrot before the cash.</strong><span>From Rebirth 9 onward, the listed unit is the delay. Cash refill is what the new multiplier is for.</span></div>
    <div><strong>Prioritize Rebirth 2 and Rebirth 10.</strong><span>Those two resets add floors. More parked income is how later cash lines become realistic.</span></div>
    <div><strong>Refill the base as soon as the reset finishes.</strong><span>An empty base wastes the new multiplier. Drop income units in before you start the next hunt.</span></div>
    <div><strong>Use a private server for Red Carpet hunts.</strong><span>Brainrot Gods and Secrets are easier to contest when you are not racing a full public lobby.</span></div>
    <div><strong>Watch Admin Abuse and event windows.</strong><span>Rare spawns cluster there. Do not reset in the middle of a delivery or a steal.</span></div>
    <div><strong>Move a hard-to-replace copy off the base first.</strong><span>Some players park a second account, then steal the copy back after the reset. Check <a href="{{ $existCountHref }}">Exist Count</a> and <a href="{{ $calculatorHref }}">trade value</a> before you decide the copy is expendable.</span></div>
  </div>
</section>

<section class="sab-wiki-panel" id="rebirth-milestones">
  <h2>The Most Important Rebirth Milestones</h2>
  <div class="sab-wiki-rebirth-milestones">
    <div>
      <strong>Rebirth 2 — second floor</strong>
      <span>The first reset that changes the base, not just the multiplier. Extra floor plus one slot is why this level is worth pressing the moment $1.5M and the two listed Brainrots are ready.</span>
    </div>
    <div>
      <strong>Rebirth 10 — third floor</strong>
      <span>Girafa Celestre plus $125B unlocks the third floor. From here the x9 multiplier has room to hold more parked income, which is what funds the later trillion-cash checks.</span>
    </div>
    <div>
      <strong>Rebirth 16 — first Secret</strong>
      <span>Los Tralaleritos is the first Secret on the current ladder. This is the point where a Values check matters: a low-supply copy may be worth more as a trade than as a reset token.</span>
    </div>
    <div>
      <strong>Rebirth 17 — two Secrets</strong>
      <span>Job Job Job Sahur and Chicleteira Bicicleteira are both required. It is the only current level that asks for two Secret Brainrots in one check.</span>
    </div>
    <div>
      <strong>Rebirth 19 — current max</strong>
      <span>$30Qa and La Grande Combinasion close the list. The return is x19, $250T starting cash, another slot, more lock time, and Grief Shield. Update 64 did not add a 20th level.</span>
    </div>
  </div>
</section>

<section class="sab-wiki-panel" id="rebirth-19">
  <h2>Rebirth 19 Requirements and Rewards</h2>
  <p>Rebirth 19 is the last listed reset. You need <strong>$30Qa</strong> and <strong>La Grande Combinasion</strong> parked in the base. The listed return is <strong>x19 income, $250T starting cash, +1 slot, +10 seconds of lock time, and Grief Shield</strong>.</p>
  <p>Before you spend La Grande Combinasion, check whether that copy is a normal unit or a mutated / traited one. A low Exist Count or a high SAB Value can make the same name a bad reset token.</p>
  @if(!empty($rebirth19NewsHref))
  <p>For the update that added the level, read the <a href="{{ $rebirth19NewsHref }}">Rebirth 19 Update report</a>.</p>
  @endif
  <div class="sab-wiki-rebirth-tools">
    <div>
      <strong>Using La Grande Combinasion for Rebirth 19?</strong>
      <p>Compare supply, current value, mutations, and traits before you consume the copy.</p>
    </div>
    <a class="sab-wiki-rebirth-button" href="{{ $calculatorHref }}">Check Trade Value</a>
  </div>
</section>

<section class="sab-wiki-panel" id="rebirth-20">
  <h2>Is There a Rebirth 20?</h2>
  <p>As of the Update 64 snapshot used for this page, <strong>Rebirth 20 has not been confirmed</strong>. The ladder grew to 19 when Rebirth 19 launched; later patches added Secrets and Bee content without a new Rebirth row. If a future update adds Rebirth 20, this table should gain a 20th row instead of living as a second outdated list.</p>
</section>

<section class="sab-wiki-panel" id="rebirth-sources">
  <h2>How This List Is Dated</h2>
  <p>Sammy has changed requirements before. Older public lists still show Rebirth 3 at $7.5M or stop at 15–16 levels. This page uses the current 19-level snapshot: <strong>Rebirth 3 = $12.5M</strong>, <strong>Rebirth 4 = $35M</strong>, max Rebirth = 19. The in-game Rebirth menu is the final check.</p>
  <div class="sab-wiki-rebirth-source">
    <strong>Data note</strong>
    <p>Cash, Brainrots, and rewards were checked against recent 19-level Rebirth tables and the Rebirth 19 update. Game values can change after a patch. SABExistCount treats the live menu as confirmation, not a screenshot from another site.</p>
  </div>
</section>

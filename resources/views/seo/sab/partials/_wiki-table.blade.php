@foreach($wikiGroups as $group)
<section class="sab-wiki-section" id="rarity-{{ $group['slug'] }}" data-wiki-section data-rarity="{{ $group['key'] }}">
  <div class="sab-wiki-section-head">
    <div>
      <h2>{{ $group['label'] }}@if(($wikiTableHeadingSuffix ?? '') !== '') {{ $wikiTableHeadingSuffix }}@endif</h2>
      @if(!empty($group['description']))
      <p class="sab-wiki-section-desc">{{ $group['description'] }}</p>
      @endif
    </div>
    <span class="sab-wiki-section-count"><span data-section-count>{{ number_format($group['count']) }}</span> items</span>
  </div>
  <div class="sab-wiki-table-wrap">
    <table class="sab-wiki-table">
      <thead>
        <tr>
          <th scope="col">Brainrot</th>
          <th scope="col">Rarity</th>
          <th scope="col">Demand</th>
          <th scope="col">Income</th>
          <th scope="col">Exist Count</th>
          <th scope="col">Trade Value</th>
          <th scope="col">Obtain</th>
          <th scope="col">Details</th>
        </tr>
      </thead>
      <tbody data-wiki-tbody>
        @foreach($group['rows'] as $row)
        <tr data-wiki-row
            data-search="{{ $row['search'] }}"
            data-name="{{ mb_strtolower($row['name']) }}"
            data-sort-income="{{ $row['income'] ?? '' }}"
            data-sort-exist="{{ $row['existCount'] ?? '' }}"
            data-sort-value="{{ $row['tradeValue'] ?? '' }}">
          <td data-label="Brainrot">
            <a class="sab-wiki-name" href="{{ $row['productUrl'] }}">
              @if($row['imageSrc'])
                <img class="sab-wiki-thumb" src="{{ $row['imageSrc'] }}" alt="{{ $row['name'] }}" width="64" height="64" loading="lazy" decoding="async">
              @else
                <span class="sab-wiki-thumb-placeholder" aria-hidden="true">{{ mb_strtoupper(mb_substr($row['name'], 0, 2)) }}</span>
              @endif
              <span class="sab-wiki-name-text">
                <span>{{ $row['name'] }}</span>
                <span class="sab-wiki-name-meta">
                  <span class="sab-wiki-tier {{ \App\Services\Seo\SabWikiPageDefinitions::rarityCssClass($row['rarityKey'] ?? null) }}">{{ $row['rarityLabel'] }}</span>
                </span>
              </span>
            </a>
          </td>
          <td data-label="Rarity">
            <span class="sab-wiki-rarity-cell">
              <span class="sab-wiki-tier {{ \App\Services\Seo\SabWikiPageDefinitions::rarityCssClass($row['rarityKey'] ?? null) }}">{{ $row['rarityLabel'] }}</span>
            </span>
          </td>
          <td data-label="Demand" class="sab-wiki-value {{ empty($row['demandLabel']) ? 'sab-wiki-unknown' : '' }}">
            <span class="sab-wiki-demand-cell">
              @if(!empty($row['demandLabel']))
              <span class="sab-wiki-demand {{ \App\Services\Seo\SabWikiPageDefinitions::demandCssClass($row['demandLabel']) }}">{{ $row['demandLabel'] }}</span>
              @else
              -
              @endif
              @if(!empty($row['trendLabel']))
              <svg class="sab-wiki-trend-icon {{ \App\Services\Seo\SabWikiPageDefinitions::trendCssClass($row['trendLabel']) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                @if(strtolower((string) $row['trendLabel']) === 'rising')
                <path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/>
                @elseif(strtolower((string) $row['trendLabel']) === 'lowering')
                <path d="M16 17h6v-6"/><path d="m22 17-8.5-8.5-5 5L2 7"/>
                @else
                <path d="M5 12h14"/>
                @endif
              </svg>
              @endif
            </span>
          </td>
          <td data-label="Income" class="sab-wiki-value {{ $row['income'] === null ? 'sab-wiki-unknown' : '' }}">{{ $row['incomeLabel'] }}</td>
          <td data-label="Exist Count" class="sab-wiki-value {{ $row['existCountKind'] === 'none' ? 'sab-wiki-unknown' : '' }}">
            {{ $row['existCountLabel'] }}
            @if($row['existCountBadge'])<span class="sab-wiki-estimate">{{ $row['existCountBadge'] }}</span>@endif
          </td>
          <td data-label="Trade Value" class="sab-wiki-value {{ $row['tradeValue'] === null ? 'sab-wiki-unknown' : '' }}">
            <span class="sab-wiki-value-stack">
              <span>{{ $row['tradeValueLabel'] }}</span>
              @if(!empty($row['deltaPctLabel']))
              <span class="sab-wiki-change is-{{ $row['changeDirection'] ?? 'stable' }}">{{ $row['deltaPctLabel'] }}</span>
              @endif
            </span>
          </td>
          <td data-label="Obtain" class="sab-wiki-value {{ $row['obtainMethod'] ? '' : 'sab-wiki-empty' }}">{{ $row['obtainMethod'] ?: '' }}</td>
          <td data-label="Details"><a class="sab-wiki-details" href="{{ $row['productUrl'] }}">View details →</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>
@endforeach

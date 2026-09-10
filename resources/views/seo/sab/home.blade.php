@extends('seo.sab.layout')

@section('content')
@php
  $rarityClassFor = function (?string $rarity): string {
    $rarityKey = \App\Services\Seo\SabRenderService::canonicalRarityKey($rarity);

    return match ($rarityKey) {
      'common'       => 'brainrot-rarity-common',
      'rare'         => 'brainrot-rarity-rare',
      'epic'         => 'brainrot-rarity-epic',
      'legendary'    => 'brainrot-rarity-legendary',
      'mythic'       => 'brainrot-rarity-mythic',
      'brainrot god' => 'brainrot-rarity-brainrot-god',
      'secret'       => 'brainrot-rarity-secret',
      'og'           => 'brainrot-rarity-og',
      default        => 'brainrot-rarity-default',
    };
  };
  $badgeClassFor = $rarityClassFor;
  $rarityLabelFor = fn (string $rarityKey): string => $rarityKey === 'og'
    ? 'OG'
    : \App\Services\Seo\SabRenderService::canonicalRarityLabel($rarityKey);
  $formatCount = fn ($count) => $count !== null ? \App\Services\Seo\SabRenderService::formatLargeNumber((float) $count) : '—';
  $abbrevLabel = fn (string $label): string => $label === ''
    ? ''
    : mb_strtoupper(mb_substr($label, 0, 3));
  $isDisplayMutLabel = fn (string $label): bool => $label !== ''
    && ! preg_match('/^(n\/a|na|none|null|—|-)$/iu', $label);
  $formatDate = fn ($date) => $date ? $date->locale($locale)->diffForHumans() : ($t['date_updated_recently'] ?? 'Updated recently');
  $mutColRowClass = 'flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-3';
  $mutColNameClass = 'min-w-0 font-sans text-xs leading-snug text-slate-500 group-hover:text-cyan-300 sm:text-sm sm:text-slate-300';
  $newHomeCount = (int) ($newHomeCount ?? 0);
@endphp

<style>
  #brainrot-search { color: #f1f5f9; }
  #brainrot-search::placeholder {
    color: #64748b;
    opacity: 1;
  }
  #brainrot-search::-webkit-input-placeholder { color: #64748b; }
  #brainrot-search::-moz-placeholder { color: #64748b; opacity: 1; }
  #brainrot-search:-webkit-autofill,
  #brainrot-search:-webkit-autofill:hover,
  #brainrot-search:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0 100px #0f172a inset;
    -webkit-text-fill-color: #f1f5f9;
    caret-color: #f1f5f9;
    transition: background-color 99999s ease-out 0s;
  }
  #brainrot-list tr + tr {
    border-top: 1px solid rgba(30, 41, 59, 0.58);
  }
  #brainrot-show-all-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 0;
    border-top: 1px solid rgba(30, 41, 59, 0.58);
    text-align: center;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
  }
  /* 底部按钮留在卡片内，横向滚动只作用于表格区域 */
  .sab-home-list-card > .sab-list-table-footer {
    box-sizing: border-box;
    max-width: 100%;
  }
  @media (max-width: 639px) {
    .sab-home-list-card > .sab-list-table-footer {
      padding-left: 0.375rem;
      padding-right: 0.375rem;
    }
    /* 静态 CSS 未刷新时，预览页也保持同一套移动端布局 */
    #list .sab-table-scroll-wrap {
      padding-left: 0.375rem;
      padding-right: 0.375rem;
      box-sizing: border-box;
      overflow-x: visible;
    }
    #list .sab-table-scroll-wrap table {
      table-layout: fixed;
      width: 100%;
      min-width: 100%;
    }
    #list .sab-table-scroll-wrap table thead th,
    #list .sab-table-scroll-wrap table tbody td {
      padding-left: 0.5rem;
      padding-right: 0.5rem;
      padding-top: 0.75rem;
      padding-bottom: 0.75rem;
    }
    #list .sab-home-table .sab-col-brainrot {
      width: 56%;
      padding-left: 0.375rem;
      padding-right: 0.25rem;
    }
    #list .sab-home-table .sab-col-count {
      width: 30%;
      padding-left: 0.25rem;
      padding-right: 0.25rem;
    }
    #list .sab-home-table .sab-col-mut {
      width: 14%;
      padding-left: 0.25rem;
      padding-right: 0.375rem;
    }
    #list .sab-home-table .sab-brainrot-link {
      align-items: flex-start;
      gap: 0.5rem;
      overflow: hidden;
      width: 100%;
    }
    #list .sab-home-table .sab-brainrot-thumb {
      width: 4rem;
      height: 4rem;
      padding: 0;
      border: 0;
      background: transparent;
      border-radius: 0;
      box-sizing: border-box;
      flex-shrink: 0;
      object-fit: contain;
    }
    #list .sab-home-table .sab-brainrot-name,
    #list .sab-home-table .sab-mut-name {
      overflow-wrap: anywhere;
      word-break: break-word;
    }
    #list .sab-home-table .sab-brainrot-name {
      display: block;
      line-height: 1.15;
      max-width: 100%;
      overflow: hidden;
      white-space: normal;
    }
    #list .sab-home-table .sab-count-cell {
      white-space: normal;
      overflow-wrap: anywhere;
      font-size: 0.75rem;
    }
    #list .sab-home-table .sab-exist-count-main {
      color: #67e8f9;
      font-size: 1.125rem;
      font-weight: 700;
      line-height: 1.1;
    }
    #list .sab-home-table .sab-mut-cell {
      font-size: 0.75rem;
      line-height: 1.18;
    }
    #list .sab-home-table .sab-mut-chips {
      gap: 0.25rem;
    }
    #list .sab-home-table .sab-mut-chip {
      gap: 0.25rem;
      padding: 0.2rem 0.35rem;
    }
  }
  .sab-mut-chips {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
  }
  .sab-mut-chip {
    align-items: center;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(100, 116, 139, 0.35);
    border-radius: 0;
    display: inline-flex;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    text-decoration: none;
    white-space: nowrap;
  }
  a.sab-mut-chip:hover .sab-mut-chip-label {
    color: #64748b;
  }
  .sab-mut-chip-label {
    color: #475569;
    font-size: 0.625rem;
    font-weight: 500;
    letter-spacing: 0.04em;
  }
  .sab-mut-chip-label-m::before {
    content: "M";
  }
  .sab-mut-chip-label-t::before {
    content: "T";
  }
  .sab-mut-chip-count {
    color: #64748b;
    font-family: ui-monospace, monospace;
    font-size: 0.625rem;
    font-variant-numeric: tabular-nums;
    font-weight: 400;
  }
  @media (min-width: 640px) {
    .sab-home-list-card > .sab-list-table-footer {
      padding-left: 1rem;
      padding-right: 1rem;
    }
    #list .sab-home-table {
      table-layout: fixed;
      min-width: 960px;
    }
    #list .sab-home-table thead th,
    #list .sab-home-table tbody td {
      padding-left: 1.25rem;
      padding-right: 1.25rem;
    }
    #list .sab-home-table tbody td {
      padding-top: 1.6rem;
      padding-bottom: 1.6rem;
    }
    #list .sab-home-table .sab-col-brainrot {
      width: 28%;
    }
    #list .sab-home-table .sab-col-rarity {
      width: 12%;
      padding-left: 1rem;
      padding-right: 1rem;
    }
    #list .sab-home-table .sab-col-count {
      width: 16%;
      padding-right: 2rem;
    }
    #list .sab-home-table .sab-col-mut {
      width: 22%;
      padding-left: 2rem;
      padding-right: 2rem;
    }
    #list .sab-home-table thead th:nth-child(5),
    #list .sab-home-table tbody td:nth-child(5) {
      width: 14%;
      padding-left: 2rem;
    }
    #list .sab-home-table .sab-mut-cell > div + div {
      margin-top: 0.625rem;
    }
    #list .sab-home-table .sab-mut-cell .sab-mut-count {
      color: #64748b;
      font-size: 0.8125rem;
      font-weight: 400;
      margin-left: 0.125rem;
    }
    #list .sab-home-table .sab-exist-count-main {
      font-size: 1.375rem;
    }
  }
  #brainrot-show-all {
    display: block;
    margin: 0 auto;
    text-align: center;
    text-transform: none;
  }
  @media (min-width: 640px) {
    #brainrot-show-all {
      font-size: 0.875rem;
    }
  }
  #brainrot-table-updated {
    display: block;
    width: 100%;
    text-align: right;
  }
  .sab-sort-stack {
    align-items: flex-start;
    display: inline-flex;
    flex-direction: column;
    gap: 0.125rem;
    max-width: 100%;
    width: 100%;
  }
  .sab-sort-btn {
    align-items: center;
    background: transparent;
    border: 0;
    color: #94a3b8;
    cursor: pointer;
    display: inline-grid;
    font: inherit;
    grid-template-columns: minmax(0, auto) 0.875rem;
    column-gap: 0.25rem;
    justify-content: flex-start;
    letter-spacing: inherit;
    line-height: 1.15;
    padding: 0;
    text-align: left;
    text-transform: inherit;
    white-space: nowrap;
  }
  .sab-sort-btn:hover,
  .sab-sort-btn[aria-sort="ascending"],
  .sab-sort-btn[aria-sort="descending"] {
    color: #67e8f9;
  }
  .sab-sort-indicator {
    color: #475569;
    font-size: 0.6875rem;
    line-height: 1;
    min-width: 0.875rem;
    text-align: center;
  }
  .sab-sort-label {
    display: block;
    min-width: 0;
  }
  .sab-sort-btn[aria-sort="ascending"] .sab-sort-indicator,
  .sab-sort-btn[aria-sort="descending"] .sab-sort-indicator {
    color: #67e8f9;
  }
  .sab-home-list-card {
    border: 0;
    border-radius: 0;
    box-shadow: none;
  }
  #list .sab-table-scroll-wrap {
    border: 0;
  }
  #list .sab-home-table {
    border-collapse: collapse;
  }
  #list .sab-home-table thead {
    background: rgba(15, 23, 42, 0.58);
  }
  #list .sab-home-table tbody td {
    background: rgba(15, 23, 42, 0.48);
  }
  #list .sab-home-table tbody tr.sab-rarity-row .sab-col-brainrot {
    background:
      linear-gradient(90deg, var(--rarity-row) 0%, var(--rarity-row-soft) 42%, rgba(15, 23, 42, 0.48) 100%);
    padding-left: 1.55rem;
    position: relative;
  }
  #list .sab-home-table tbody tr.sab-rarity-row .sab-col-brainrot::before {
    background: var(--rarity-line);
    bottom: 0;
    content: "";
    left: 0;
    position: absolute;
    top: 0;
    width: 5px;
  }
  #list .sab-home-table tbody tr.sab-rarity-row:hover td {
    background: rgba(30, 41, 59, 0.55);
  }
  #list .sab-home-table tbody tr.sab-rarity-row:hover .sab-col-brainrot {
    background:
      linear-gradient(90deg, var(--rarity-row-strong) 0%, var(--rarity-row-soft) 48%, rgba(30, 41, 59, 0.55) 100%);
  }
  #list .sab-home-table .sab-brainrot-thumb {
    border: 0;
    background: transparent;
    border-radius: 0;
    box-shadow: none;
  }
  .rarity-filter-btn,
  .brainrot-rarity-pill {
    --rarity-bg: rgba(51, 65, 85, 0.72);
    --rarity-bg-soft: rgba(51, 65, 85, 0.36);
    --rarity-fg: #cbd5e1;
    --rarity-fg-strong: #f8fafc;
    --rarity-ring: rgba(148, 163, 184, 0.18);
    --rarity-line: #64748b;
    --rarity-row: rgba(71, 85, 105, 0.24);
    --rarity-row-soft: rgba(71, 85, 105, 0.1);
    --rarity-row-strong: rgba(71, 85, 105, 0.34);
    --rarity-shadow: rgba(15, 23, 42, 0.2);
  }
  #rarity-tags {
    column-gap: 0.5rem;
    row-gap: 0.625rem;
  }
  .rarity-filter-btn {
    align-items: center;
    background: var(--rarity-bg-soft);
    border: 0;
    border-radius: 9999px;
    box-shadow: none;
    color: var(--rarity-fg);
    display: inline-flex;
    gap: 0.35rem;
    line-height: 1.4;
    min-height: 2rem;
    padding: 0.5rem 0.75rem;
  }
  .rarity-filter-btn:hover,
  .rarity-filter-btn[data-active="true"] {
    background: var(--rarity-bg);
    color: var(--rarity-fg-strong);
    box-shadow: none;
  }
  .rarity-filter-btn[data-active="true"] {
    transform: translateY(-1px);
  }
  .rarity-filter-btn-all {
    --rarity-bg: rgba(6, 182, 212, 0.34);
    --rarity-bg-soft: rgba(6, 182, 212, 0.14);
    --rarity-fg: #67e8f9;
    --rarity-fg-strong: #ecfeff;
    --rarity-ring: rgba(103, 232, 249, 0.34);
    --rarity-shadow: rgba(6, 182, 212, 0.14);
  }
  .rarity-filter-btn-new {
    --rarity-bg: rgba(22, 163, 74, 0.34);
    --rarity-bg-soft: rgba(22, 163, 74, 0.14);
    --rarity-fg: #86efac;
    --rarity-fg-strong: #f0fdf4;
    --rarity-ring: rgba(134, 239, 172, 0.34);
    --rarity-shadow: rgba(22, 163, 74, 0.14);
  }
  .brainrot-rarity-pill {
    background: var(--rarity-bg);
    box-shadow: none;
    color: var(--rarity-fg-strong);
    max-width: 100%;
    min-height: 1.15rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
  }
  .brainrot-title-line {
    align-items: flex-start;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    min-width: 0;
  }
  #list .sab-home-table .sab-brainrot-name-only.sab-brainrot-name {
    display: none;
  }
  @media (min-width: 640px) {
    .sab-brainrot-mobile-meta {
      display: none !important;
    }
    #list .sab-home-table .sab-brainrot-name-only.sab-brainrot-name {
      display: block;
    }
  }
  .brainrot-inline-rarity {
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.02em;
    line-height: 1;
    padding: 4px 7px;
    text-transform: uppercase;
  }
  .brainrot-new-pill {
    background: rgba(34, 211, 238, 0.13);
    border: 1px solid rgba(103, 232, 249, 0.35);
    border-radius: 999px;
    color: #67e8f9;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    line-height: 1;
    padding: 4px 7px;
    text-transform: uppercase;
    white-space: nowrap;
  }
  .brainrot-table-rarity {
    border-radius: 999px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.02em;
    line-height: 1;
    padding: 4px 7px;
    text-transform: uppercase;
  }
  .brainrot-rarity-common {
    --rarity-bg: rgba(22, 163, 74, 0.86);
    --rarity-bg-soft: rgba(22, 163, 74, 0.22);
    --rarity-fg: #86efac;
    --rarity-fg-strong: #f0fdf4;
    --rarity-ring: rgba(134, 239, 172, 0.28);
    --rarity-line: #00a113;
    --rarity-row: rgba(0, 161, 19, 0.26);
    --rarity-row-soft: rgba(0, 161, 19, 0.11);
    --rarity-row-strong: rgba(0, 161, 19, 0.34);
    --rarity-shadow: rgba(22, 163, 74, 0.18);
  }
  .brainrot-rarity-rare {
    --rarity-bg: rgba(37, 99, 235, 0.9);
    --rarity-bg-soft: rgba(37, 99, 235, 0.23);
    --rarity-fg: #93c5fd;
    --rarity-fg-strong: #eff6ff;
    --rarity-ring: rgba(147, 197, 253, 0.3);
    --rarity-line: #0b63f6;
    --rarity-row: rgba(11, 99, 246, 0.26);
    --rarity-row-soft: rgba(11, 99, 246, 0.11);
    --rarity-row-strong: rgba(11, 99, 246, 0.34);
    --rarity-shadow: rgba(37, 99, 235, 0.2);
  }
  .brainrot-rarity-epic {
    --rarity-bg: rgba(147, 51, 234, 0.88);
    --rarity-bg-soft: rgba(147, 51, 234, 0.24);
    --rarity-fg: #d8b4fe;
    --rarity-fg-strong: #faf5ff;
    --rarity-ring: rgba(216, 180, 254, 0.3);
    --rarity-line: #a119aa;
    --rarity-row: rgba(161, 25, 170, 0.28);
    --rarity-row-soft: rgba(161, 25, 170, 0.12);
    --rarity-row-strong: rgba(161, 25, 170, 0.36);
    --rarity-shadow: rgba(147, 51, 234, 0.2);
  }
  .brainrot-rarity-legendary {
    --rarity-bg: rgba(234, 179, 8, 0.9);
    --rarity-bg-soft: rgba(234, 179, 8, 0.24);
    --rarity-fg: #fde68a;
    --rarity-fg-strong: #18181b;
    --rarity-ring: rgba(253, 230, 138, 0.3);
    --rarity-line: #fff200;
    --rarity-row: rgba(250, 204, 21, 0.24);
    --rarity-row-soft: rgba(250, 204, 21, 0.1);
    --rarity-row-strong: rgba(250, 204, 21, 0.32);
    --rarity-shadow: rgba(234, 179, 8, 0.18);
  }
  .brainrot-rarity-mythic {
    --rarity-bg: rgba(239, 68, 68, 0.88);
    --rarity-bg-soft: rgba(239, 68, 68, 0.23);
    --rarity-fg: #fca5a5;
    --rarity-fg-strong: #fff1f2;
    --rarity-ring: rgba(252, 165, 165, 0.3);
    --rarity-line: #ff4d4d;
    --rarity-row: rgba(239, 68, 68, 0.25);
    --rarity-row-soft: rgba(239, 68, 68, 0.1);
    --rarity-row-strong: rgba(239, 68, 68, 0.33);
    --rarity-shadow: rgba(239, 68, 68, 0.19);
  }
  .brainrot-rarity-brainrot-god {
    --rarity-bg: rgba(217, 70, 239, 0.9);
    --rarity-bg-soft: rgba(217, 70, 239, 0.23);
    --rarity-fg: #f5d0fe;
    --rarity-fg-strong: #fdf4ff;
    --rarity-ring: rgba(245, 208, 254, 0.32);
    --rarity-line: #ff00d4;
    --rarity-row: rgba(217, 70, 239, 0.28);
    --rarity-row-soft: rgba(217, 70, 239, 0.12);
    --rarity-row-strong: rgba(217, 70, 239, 0.36);
    --rarity-shadow: rgba(217, 70, 239, 0.2);
  }
  .brainrot-rarity-og {
    --rarity-bg: rgba(250, 204, 21, 0.96);
    --rarity-bg-soft: rgba(250, 204, 21, 0.18);
    --rarity-fg: #fef08a;
    --rarity-fg-strong: #111827;
    --rarity-ring: rgba(250, 204, 21, 0.34);
    --rarity-line: #fff200;
    --rarity-row: rgba(250, 204, 21, 0.2);
    --rarity-row-soft: rgba(250, 204, 21, 0.08);
    --rarity-row-strong: rgba(250, 204, 21, 0.28);
    --rarity-shadow: rgba(250, 204, 21, 0.16);
  }
  .brainrot-rarity-secret {
    --rarity-bg: rgba(226, 232, 240, 0.9);
    --rarity-bg-soft: rgba(226, 232, 240, 0.18);
    --rarity-fg: #e2e8f0;
    --rarity-fg-strong: #0f172a;
    --rarity-ring: rgba(226, 232, 240, 0.32);
    --rarity-line: #f8fafc;
    --rarity-row: rgba(226, 232, 240, 0.22);
    --rarity-row-soft: rgba(226, 232, 240, 0.09);
    --rarity-row-strong: rgba(226, 232, 240, 0.3);
    --rarity-shadow: rgba(226, 232, 240, 0.12);
  }
  .brainrot-rarity-default {
    --rarity-bg: rgba(71, 85, 105, 0.86);
    --rarity-bg-soft: rgba(71, 85, 105, 0.24);
    --rarity-fg: #cbd5e1;
    --rarity-fg-strong: #f8fafc;
    --rarity-ring: rgba(148, 163, 184, 0.22);
    --rarity-line: #64748b;
    --rarity-row: rgba(100, 116, 139, 0.22);
    --rarity-row-soft: rgba(100, 116, 139, 0.09);
    --rarity-row-strong: rgba(100, 116, 139, 0.3);
    --rarity-shadow: rgba(15, 23, 42, 0.18);
  }
  .brainrot-signal {
    border-radius: 999px;
    font-size: 10px;
    line-height: 1;
    padding: 3px 7px;
    background: rgba(30, 41, 59, 0.52);
    border: 1px solid rgba(100, 116, 139, 0.18);
    color: #94a3b8;
    min-width: 1.65rem;
    justify-content: center;
  }
  .brainrot-signal::before {
    content: attr(data-signal-symbol);
  }
  .brainrot-signal-high {
    color: #86efac;
  }
  .brainrot-signal-medium {
    color: #cbd5e1;
  }
  .brainrot-signal-low {
    color: #c4b5fd;
  }
  @media (max-width: 640px) {
    #list .sab-home-table tbody tr.sab-rarity-row .sab-col-brainrot {
      background:
        linear-gradient(90deg, var(--rarity-row-soft) 0%, rgba(15, 23, 42, 0.48) 100%);
      padding-left: 0.875rem;
    }
    #list .sab-home-table tbody tr.sab-rarity-row .sab-col-brainrot::before {
      width: 4px;
    }
    .brainrot-inline-rarity {
      font-size: 9px;
      padding: 3px 6px;
    }
    #list .sab-home-table .sab-brainrot-thumb {
      width: 4rem;
      height: 4rem;
      padding: 0;
      border: 0;
      background: transparent;
      box-sizing: border-box;
      flex-shrink: 0;
      object-fit: contain;
    }
  }
  #rare .sab-rare-row {
    align-items: center;
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem 0.875rem;
    transition: background-color 0.15s ease;
  }
  @media (min-width: 640px) {
    #rare .sab-rare-row {
      gap: 1rem;
      padding: 0.875rem 1rem;
    }
  }
  #rare .sab-rare-row:hover {
    background: rgba(255, 255, 255, 0.04);
  }
  #rare .sab-rare-row:last-child {
    border-bottom: 0;
  }
  #rare .sab-rare-row-top3 {
    background: rgba(236, 72, 153, 0.04);
    border-left: 3px solid rgba(244, 114, 182, 0.45);
  }
  #rare .sab-rare-row-top1 {
    background: rgba(236, 72, 153, 0.07);
    border-left-color: rgba(244, 114, 182, 0.75);
  }
  #rare .sab-rare-rank {
    color: #94a3b8;
    flex-shrink: 0;
    font-size: 0.875rem;
    font-weight: 800;
    text-align: center;
    width: 2.25rem;
  }
  #rare .sab-rare-rank-top3 {
    color: #f9a8d4;
  }
  #rare .sab-rare-rank-top1 {
    color: #fbcfe8;
    font-size: 1rem;
  }
  #rare .sab-rare-thumb {
    flex-shrink: 0;
    height: 3rem;
    object-fit: contain;
    width: 3rem;
  }
  #rare .sab-rare-thumb-placeholder {
    align-items: center;
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0.5rem;
    color: #64748b;
    display: flex;
    font-size: 0.875rem;
    font-weight: 700;
    justify-content: center;
  }
  #rare .sab-rare-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  #rare .sab-rare-meta {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    margin-top: 0.25rem;
  }
  #rare .sab-rare-count {
    color: #f9a8d4;
    flex-shrink: 0;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 1.125rem;
    font-weight: 700;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    text-align: right;
    white-space: nowrap;
  }
  #rare .sab-rare-count-top3 {
    color: #fbcfe8;
    font-size: 1.25rem;
  }
</style>

<header class="-mx-4 border-b border-white/10 px-4 py-5">
  <div class="max-w-4xl overflow-x-auto">
    <h1 class="inline-flex min-w-0 max-w-full flex-nowrap items-baseline font-black tracking-tight text-slate-100 md:text-5xl" style="font-size:1.35rem">
      <span class="whitespace-nowrap">
        {{ $t['hero_h1_prefix'] }}@if(!empty($t['hero_h1_cyan'])) <span class="text-cyan-400">{{ $t['hero_h1_cyan'] }}</span>@endif
      </span>
    </h1>
    {{-- <p class="mt-4 max-w-3xl text-base md:text-lg text-slate-300 leading-8">{{ $t['hero_body'] }}</p> --}}
  </div>
</header>

@if(!empty($t['home_intro']))
<section class="mb-6 rounded-2xl border border-cyan-400/20 bg-cyan-400/5 p-5">
  <p class="text-slate-300 leading-7">{!! $t['home_intro'] !!}</p>
</section>
@endif

<section id="list" class="mb-5 mt-8 scroll-mt-20">
  <h2 class="sr-only">{{ $t['table_h2'] }}</h2>

  @if(($locale ?? 'en') === 'en')
  @php
    $adminAbuseHref = rtrim((string) ($urlPrefix ?? ''), '/') . '/' . \App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE;
  @endphp
  <div class="sab-news-home-notice mt-3 mb-1 inline-flex max-w-full items-center gap-2 text-left text-xs font-semibold leading-5 text-cyan-200" data-sab-news-home-notice>
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-cyan-300">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 14h3l7 4V6L7 10H4v4Z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M17 9a3 3 0 0 1 0 6M20 7a6 6 0 0 1 0 10"/>
    </svg>
    <a href="{{ $adminAbuseHref }}" class="font-semibold underline hover:text-cyan-100" data-sab-news-home-notice-link>New · Steal a Brainrot Admin Abuse Time Today</a>
  </div>
  <script>
    (function () {
      var link = document.querySelector('[data-sab-news-home-notice-link]');
      if (link) {
        link.addEventListener('click', function () {
          if (typeof gtag === 'function') {
            gtag('event', 'wiki_notice_click', { wiki_slug: 'admin-abuse' });
          }
        });
      }
    })();
  </script>
  @endif

  <div class="mb-3 mt-1">
    <div style="display:flex;align-items:center;background:#1e293b;border:2px solid rgb(6,182,212);border-radius:0.75rem;overflow:hidden;width:100%;max-width:520px;">
      <span style="display:flex;align-items:center;padding-left:1rem;color:rgb(34,211,238);flex-shrink:0;pointer-events:none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
      </span>
      <input id="brainrot-search" type="search"
             placeholder="{{ $t['search_placeholder'] }}"
             style="flex:1;background:transparent;padding:0.75rem 0.625rem;font-size:1rem;color:#f1f5f9;border:none;outline:none;min-width:0;-webkit-box-shadow:0 0 0 100px #1e293b inset;color-scheme:dark;caret-color:#f1f5f9;" />
    </div>
    <p id="brainrot-search-count" class="text-xs text-slate-500 mt-1.5"></p>
  </div>

  <div id="rarity-tags" class="flex flex-wrap mb-4">
    @foreach(($rarityTags ?? []) as $tagVal => $tagLabel)
    @php
      $tagRarityClass = $tagVal === '' ? 'rarity-filter-btn-all' : $rarityClassFor($tagVal);
    @endphp
    <button data-tag="{{ $tagVal }}"
            data-active="{{ $tagVal === '' ? 'true' : 'false' }}"
            class="tag-btn rarity-filter-btn {{ $tagRarityClass }} text-xs font-bold transition-colors">
      {{ $tagLabel }} <span class="rarity-filter-count">({{ $rarityTagCounts[$tagVal] ?? 0 }})</span>
    </button>
    @endforeach
    <button type="button"
            id="brainrot-new-search"
            data-active="false"
            class="rarity-filter-btn rarity-filter-btn-new text-xs font-bold transition-colors">
      NEW <span class="rarity-filter-count">({{ $newHomeCount }})</span>
    </button>
  </div>

  <div class="sab-home-list-card bg-slate-900 overflow-hidden">
    <div class="sab-table-scroll-wrap overflow-x-auto">
    <table class="sab-home-table w-full text-xs sm:text-sm">
      <caption class="sr-only">{{ $t['table_caption'] ?? 'Steal a Brainrot exist count list' }}</caption>
      <thead class="bg-white/[0.03]">
        <tr>
          <th class="sab-col-brainrot pl-2.5 pr-2.5 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t['col_brainrot'] }}</th>
          <th class="sab-col-rarity px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider hidden sm:table-cell">{{ $t['col_rarity'] }}</th>
          <th class="sab-col-count px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">
            <button type="button" class="sab-sort-btn" data-sort-key="exist" aria-sort="none">
              <span class="sab-sort-label">{!! $t['col_exist_count_header'] ?? $t['col_exist_count'] !!}</span>
              <span class="sab-sort-indicator" aria-hidden="true">↕</span>
            </button>
          </th>
          <th class="sab-col-mut px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider leading-tight"><span class="sm:hidden">{{ $t['col_mutations_traits_short'] ?? 'M/T' }}</span><span class="hidden sm:inline">{!! $t['col_mutations_traits'] ?? 'Mutations<br>Traits' !!}</span></th>
          <th class="px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider hidden md:table-cell">{{ $t['col_signal'] }}</th>
        </tr>
      </thead>
      <tbody id="brainrot-list">
        @foreach($items as $item)
        @php
          $ec = $item->total_exists;
          $ecDisplay = \App\Services\Seo\SabRenderService::resolveExistCountDisplay($item, $ec !== null ? (int) $ec : null);
          $ecSort = $ecDisplay['sort_value'] !== null ? (float) $ecDisplay['sort_value'] : '';
          $ecSortTier = match ($ecDisplay['kind']) {
            'known' => 0,
            'estimated' => 1,
            default => 2,
          };
          $rarestMutationLabel = trim((string) ($item->rarest_mutation_name ?? ''));
          $rarestTraitLabel = trim((string) ($item->rarest_trait_name ?? ''));
          if (! $isDisplayMutLabel($rarestMutationLabel)) {
            $rarestMutationLabel = '';
          }
          if (! $isDisplayMutLabel($rarestTraitLabel)) {
            $rarestTraitLabel = '';
          }
          $canOpenProduct = \App\Services\Seo\SabRenderService::shouldLinkProduct($item);
          $productSlug = \App\Services\Seo\SabRenderService::productPublicSlug($item->slug ?? '');
          $rarestMutationHref = $canOpenProduct && $rarestMutationLabel !== '' ? $productUrlPrefix . '/products/' . $productSlug . '#mutation-' . \Illuminate\Support\Str::slug($rarestMutationLabel) : '';
          $rarestTraitHref = $canOpenProduct && $rarestTraitLabel !== '' ? $productUrlPrefix . '/products/' . $productSlug . '#trait-' . \Illuminate\Support\Str::slug($rarestTraitLabel) : '';
          $isNewHomeItem = \App\Services\Seo\SabRenderService::isNewHomeItem($item);

          $rarityKey = \App\Services\Seo\SabRenderService::canonicalRarityKey($item->rarity ?? null);
          $rarityLabel = $rarityKey !== '' ? $rarityLabelFor($rarityKey) : '';
          $badgeClass = $rarityClassFor($rarityKey);
          $searchParts = array_filter([
            $item->name,
            $item->slug,
            $rarityKey,
            $rarityLabel,
            $ec !== null ? (string) (int) $ec : null,
            $ec !== null ? \App\Services\Seo\SabRenderService::formatLargeNumber((float) $ec) : null,
            $ecDisplay['kind'] === 'estimated' ? $ecDisplay['primary'] : null,
            $ecDisplay['kind'] === 'estimated' ? ($ecDisplay['short'] ?? '') : null,
            $isNewHomeItem ? 'new' : null,
          ], fn ($part) => trim((string) $part) !== '');
          $searchText = implode(' ', array_unique(array_map(fn ($part) => strtolower(trim((string) $part)), $searchParts)));

          [$sigKey, $sigClass] = \App\Services\Seo\SabRenderService::raritySignal($ec);
          $sigLabel = $t['signal_' . $sigKey] ?? $sigKey;
          $sigToneClass = match($sigKey) {
            'very_high' => 'brainrot-signal-high',
            'medium' => 'brainrot-signal-medium',
            default => 'brainrot-signal-low',
          };
          $sigSymbol = match($sigKey) {
            'very_high' => '+++',
            'medium' => '++',
            'low' => '+',
            'very_low' => '-',
            'extremely_rare' => '--',
            'near_unique' => '1',
            'lowest' => '*',
            default => '+',
          };
        @endphp
        <tr class="sab-rarity-row {{ $badgeClass }} transition-colors {{ $canOpenProduct ? 'cursor-pointer' : '' }}" data-search="{{ $searchText }}" data-rarity="{{ $rarityKey }}" data-is-new="{{ $isNewHomeItem ? '1' : '0' }}" data-sort-exist="{{ $ecSort }}" data-sort-tier="{{ $ecSortTier }}" @if($canOpenProduct) onclick="location.href='{{ $productUrlPrefix }}/products/{{ $productSlug }}'" @endif>
          <td class="sab-col-brainrot pl-2.5 pr-2.5 py-2 sm:px-4 sm:py-3 font-medium align-top">
            @if($canOpenProduct)
            <a href="{{ $productUrlPrefix }}/products/{{ $productSlug }}" class="sab-brainrot-link flex min-w-0 items-center gap-2 sm:gap-3 hover:text-cyan-300">
            @else
            <div class="sab-brainrot-link flex min-w-0 items-center gap-2 sm:gap-3">
            @endif
              @if($item->image_url || $item->local_image_url)
              @php
                $imgSrc = \App\Services\Seo\SabRenderService::listingImageSrc($item);
              @endphp
              <img src="{{ $imgSrc }}" alt="{{ $item->name }}"
                   width="64" height="64"
                   class="sab-brainrot-thumb h-14 w-14 shrink-0 object-contain sm:h-20 sm:w-20" loading="lazy" />
              @else
              <div class="sab-brainrot-thumb h-14 w-14 sm:h-20 sm:w-20 shrink-0"></div>
              @endif
              <div class="min-w-0 flex-1 overflow-hidden">
                @if($rarityLabel !== '')
                <div class="sab-brainrot-mobile-meta brainrot-title-line">
                  <span class="sab-brainrot-name min-w-0">{{ $item->name }}</span>
                  @if($isNewHomeItem)
                  <span class="brainrot-new-pill inline-flex shrink-0 items-center">NEW</span>
                  @endif
                  <span class="brainrot-rarity-pill brainrot-inline-rarity inline-flex shrink-0 items-center {{ $badgeClass }}">{{ $rarityLabel }}</span>
                </div>
                <div class="sab-brainrot-name-only sab-brainrot-name">{{ $item->name }} @if($isNewHomeItem)<span class="brainrot-new-pill ml-1 inline-flex align-middle">NEW</span>@endif</div>
                @else
                <div class="sab-brainrot-name">{{ $item->name }} @if($isNewHomeItem)<span class="brainrot-new-pill ml-1 inline-flex align-middle">NEW</span>@endif</div>
                @endif
              </div>
            @if($canOpenProduct)</a>@else</div>@endif
          </td>
          <td class="sab-col-rarity px-2 py-2 sm:px-4 sm:py-3 hidden sm:table-cell align-top">
            @if($rarityLabel !== '')
            <span class="brainrot-rarity-pill brainrot-table-rarity inline-flex items-center {{ $badgeClass }}">{{ $rarityLabel }}</span>
            @else
            <span class="text-slate-600">—</span>
            @endif
          </td>
          <td class="sab-col-count sab-count-cell px-2 py-2 sm:px-4 sm:py-3 font-mono align-top tabular-nums text-sm leading-tight whitespace-nowrap">
            @if($ecDisplay['kind'] === 'known')
            <div class="sab-exist-count-main">{{ \App\Services\Seo\SabRenderService::formatLargeNumber((float) $ec) }}</div>
            @elseif($ecDisplay['kind'] === 'estimated')
            <div class="sab-exist-count-main text-slate-400">{{ $ecDisplay['short'] ?? $ecDisplay['primary'] }}</div>
            <div class="mt-0.5 inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-yellow-400/10 text-yellow-300" title="{{ $item->name }} exist count {{ $ecDisplay['short'] ?? $ecDisplay['primary'] }}" aria-label="{{ $item->name }} exist count {{ $ecDisplay['short'] ?? $ecDisplay['primary'] }}">~</div>
            @else
            <div class="sab-exist-count-main text-slate-600">—</div>
            @endif
          </td>
          <td class="sab-col-mut sab-mut-cell px-2 py-2 sm:px-4 sm:py-3 align-top font-mono tabular-nums text-sm leading-tight">
            @if($rarestMutationLabel || $rarestTraitLabel)
            <div class="sab-mut-chips sm:hidden">
              @if($rarestMutationLabel)
                @if($rarestMutationHref)
                <a href="{{ $rarestMutationHref }}" title="{{ $rarestMutationLabel }}" onclick="event.stopPropagation()" class="sab-mut-chip">
                  <span class="sab-mut-chip-label sab-mut-chip-label-m" aria-hidden="true"></span>
                  @if($item->rarest_mutation_count !== null)
                  <span class="sab-mut-chip-count">{{ $formatCount($item->rarest_mutation_count) }}</span>
                  @endif
                </a>
                @else
                <span class="sab-mut-chip" title="{{ $rarestMutationLabel }}">
                  <span class="sab-mut-chip-label sab-mut-chip-label-m" aria-hidden="true"></span>
                  @if($item->rarest_mutation_count !== null)
                  <span class="sab-mut-chip-count">{{ $formatCount($item->rarest_mutation_count) }}</span>
                  @endif
                </span>
                @endif
              @endif
              @if($rarestTraitLabel)
                @if($rarestTraitHref)
                <a href="{{ $rarestTraitHref }}" title="{{ $rarestTraitLabel }}" onclick="event.stopPropagation()" class="sab-mut-chip">
                  <span class="sab-mut-chip-label sab-mut-chip-label-t" aria-hidden="true"></span>
                  @if($item->rarest_trait_count !== null)
                  <span class="sab-mut-chip-count">{{ $formatCount($item->rarest_trait_count) }}</span>
                  @endif
                </a>
                @else
                <span class="sab-mut-chip" title="{{ $rarestTraitLabel }}">
                  <span class="sab-mut-chip-label sab-mut-chip-label-t" aria-hidden="true"></span>
                  @if($item->rarest_trait_count !== null)
                  <span class="sab-mut-chip-count">{{ $formatCount($item->rarest_trait_count) }}</span>
                  @endif
                </span>
                @endif
              @endif
            </div>
            <div class="hidden sm:block">
              <div>
                @if($rarestMutationLabel)
                  @if($rarestMutationHref)
                  <a href="{{ $rarestMutationHref }}" onclick="event.stopPropagation()" class="group {{ $mutColRowClass }}">
                    <span class="sab-mut-name {{ $mutColNameClass }}">{{ $rarestMutationLabel }}</span>
                    @if($item->rarest_mutation_count !== null)
                    <span class="sab-mut-count whitespace-nowrap">{{ $formatCount($item->rarest_mutation_count) }}</span>
                    @endif
                  </a>
                  @else
                  <span class="{{ $mutColRowClass }}">
                    <span class="sab-mut-name min-w-0 font-sans text-xs leading-snug text-slate-500 sm:text-sm sm:text-slate-300">{{ $rarestMutationLabel }}</span>
                    @if($item->rarest_mutation_count !== null)
                    <span class="sab-mut-count whitespace-nowrap">{{ $formatCount($item->rarest_mutation_count) }}</span>
                    @endif
                  </span>
                  @endif
                @else
                <span class="font-sans text-slate-500">—</span>
                @endif
              </div>
              @if($rarestTraitLabel)
              <div class="mt-1">
                @if($rarestTraitHref)
                <a href="{{ $rarestTraitHref }}" onclick="event.stopPropagation()" class="group {{ $mutColRowClass }}">
                  <span class="sab-mut-name {{ $mutColNameClass }}">{{ $rarestTraitLabel }}</span>
                  @if($item->rarest_trait_count !== null)
                  <span class="sab-mut-count whitespace-nowrap">{{ $formatCount($item->rarest_trait_count) }}</span>
                  @endif
                </a>
                @else
                <span class="{{ $mutColRowClass }}">
                  <span class="sab-mut-name min-w-0 font-sans text-xs leading-snug text-slate-500 sm:text-sm sm:text-slate-300">{{ $rarestTraitLabel }}</span>
                  @if($item->rarest_trait_count !== null)
                  <span class="sab-mut-count whitespace-nowrap">{{ $formatCount($item->rarest_trait_count) }}</span>
                  @endif
                </span>
                @endif
              </div>
              @endif
            </div>
            @else
            <span class="font-sans text-slate-500">—</span>
            @endif
          </td>
          <td class="px-2 py-2 sm:px-4 sm:py-3 hidden md:table-cell align-top">
            @if($ec !== null)
            <span class="brainrot-signal {{ $sigToneClass }} inline-flex items-center font-medium" data-signal-symbol="{{ $sigSymbol }}" aria-hidden="true"></span>
            @else
            <span class="text-slate-600">—</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    <div id="brainrot-show-all-wrap" class="sab-list-table-footer bg-white/[0.02] py-3">
      <button id="brainrot-show-all" type="button"
              class="bg-transparent px-2 py-1 text-xs font-semibold text-cyan-300 hover:text-cyan-200 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/50">
        {{ $t['home_show_all'] }}
      </button>
      <div id="brainrot-search-empty" class="hidden px-3 py-4 text-center text-sm" aria-live="polite">
        <p class="font-semibold text-slate-100">{{ $t['search_empty_title'] ?? $t['search_empty'] }}</p>
        <p class="mt-1 text-slate-400">
          Try another Brainrot name, rarity, or count.
        </p>
      </div>
    </div>
  </div>

  <p id="brainrot-table-updated" class="text-xs text-slate-500 mt-3">{{ $t['table_updated'] }}</p>
  @php
    $wikiShortcutPrefix = rtrim((string) (
      $productUrlPrefix
        ?? (str_starts_with((string) ($urlPrefix ?? ''), '/seo/sab/preview') ? '/seo/sab/preview' : '')
    ), '/');
  @endphp
  @include('seo.sab.partials._wiki-shortcut-module', [
    'variant' => 'home',
    'lead' => 'Exist counts can move after an Admin Abuse window or a Rebirth. Open the Wiki for the current schedule and the full Rebirth requirements.',
    'wikiHubHref' => $wikiShortcutPrefix.'/'.\App\Services\Seo\SabRenderService::PAGE_WIKI,
    'adminAbuseHref' => $wikiShortcutPrefix.'/'.\App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_ADMIN_ABUSE,
    'rebirthsHref' => $wikiShortcutPrefix.'/'.\App\Services\Seo\SabWikiPageDefinitions::PAGE_WIKI_REBIRTHS,
  ])
</section>

<section id="overview" class="mb-8">
  <h2 class="mb-3 text-xl font-black text-slate-100">{{ $t['home_overview_h2'] ?? 'Overview' }}</h2>
  <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
    <div class="rounded-lg border border-white/10 bg-slate-900 px-4 py-3">
      <div class="text-2xl font-black text-cyan-400">{{ number_format($stats['total']) }}</div>
      <div class="mt-1 text-xs font-semibold uppercase leading-tight tracking-wide text-slate-500">{{ $t['stats_total'] }}</div>
    </div>
    <div class="rounded-lg border border-white/10 bg-slate-900 px-4 py-3">
      <div class="text-2xl font-black text-pink-400">{{ $stats['lowest'] !== null ? number_format($stats['lowest']) : '—' }}</div>
      <div class="mt-1 text-xs font-semibold uppercase leading-tight tracking-wide text-slate-500">{{ $t['stats_lowest'] }}</div>
    </div>
    <div class="rounded-lg border border-white/10 bg-slate-900 px-4 py-3">
      <div class="text-2xl font-black text-green-400">{{ $stats['highest'] !== null ? $formatCount($stats['highest']) : '—' }}</div>
      <div class="mt-1 text-xs font-semibold uppercase leading-tight tracking-wide text-slate-500">{{ $t['stats_highest'] }}</div>
    </div>
    <div class="rounded-lg border border-white/10 bg-slate-900 px-4 py-3">
      <div class="text-2xl font-black text-slate-200">{{ $statsMonthYear ?? date('M Y') }}</div>
      <div class="mt-1 text-xs font-semibold uppercase leading-tight tracking-wide text-slate-500">{{ $t['stats_snapshot'] }}</div>
    </div>
  </div>
</section>

<section id="rare" class="mb-16 scroll-mt-20">
  <div class="mb-4">
    <h2 class="text-xl font-black text-slate-100">{{ $t['home_latest_h2'] }}</h2>
    <p class="mt-1 text-sm text-slate-500">{{ $t['home_latest_intro'] }}</p>
  </div>
  <div class="overflow-hidden rounded-xl border border-white/10 bg-slate-900">
    @foreach($topRareItems as $rank => $entry)
    @php
      $rankNumber = $rank + 1;
      $isTop1 = $rankNumber === 1;
      $isTop3 = $rankNumber <= 3;
      $rowClass = $isTop1 ? 'sab-rare-row-top1' : ($isTop3 ? 'sab-rare-row-top3' : '');
      $rankClass = $isTop1 ? 'sab-rare-rank-top1' : ($isTop3 ? 'sab-rare-rank-top3' : '');
      $countClass = $isTop3 ? 'sab-rare-count-top3' : '';
      $rarityKey = \App\Services\Seo\SabRenderService::canonicalRarityKey($entry['rarity'] ?? '');
      $rarityLabel = $rarityKey !== '' ? $rarityLabelFor($rarityKey) : '';
      $thumbInitial = mb_strtoupper(mb_substr($entry['name'], 0, 1));
    @endphp
    <a href="{{ $productUrlPrefix }}/products/{{ $entry['slug'] }}" class="sab-rare-row group {{ $rowClass }} border-b border-white/5">
      <span class="sab-rare-rank {{ $rankClass }}">#{{ $rankNumber }}</span>
      @if($entry['imageSrc'])
      <img src="{{ $entry['imageSrc'] }}" alt="" width="48" height="48" class="sab-rare-thumb" loading="lazy" />
      @else
      <div class="sab-rare-thumb sab-rare-thumb-placeholder" aria-hidden="true">{{ $thumbInitial !== '' ? $thumbInitial : '—' }}</div>
      @endif
      <div class="min-w-0 flex-1">
        <div class="sab-rare-name font-semibold text-slate-100 group-hover:text-cyan-200">{{ $entry['name'] }}</div>
        @if($rarityLabel !== '')
        <div class="sab-rare-meta">
          <span class="brainrot-rarity-pill inline-flex items-center {{ $badgeClassFor($entry['rarity']) }}">{{ $rarityLabel }}</span>
        </div>
        @endif
      </div>
      <div class="sab-rare-count {{ $countClass }}">{{ $formatCount($entry['existCount']) }}</div>
    </a>
    @endforeach
  </div>
</section>

<section id="recent" class="mb-16 scroll-mt-20">
  <div class="mb-4">
    <h2 class="text-xl font-black text-slate-100">{{ $t['home_recent_h2'] }}</h2>
    <p class="mt-1 text-sm text-slate-500">{{ $t['home_recent_intro'] }}</p>
  </div>
  <div class="overflow-hidden rounded-xl border border-white/10 bg-slate-900">
    @foreach($recentlyChangedItems as $entry)
    <a href="{{ $productUrlPrefix }}/products/{{ $entry['slug'] }}" class="flex items-center justify-between gap-4 border-b border-white/5 px-4 py-3 hover:bg-white/[0.04] transition-colors">
      <div class="min-w-0">
        <div class="truncate font-semibold text-slate-100">{{ $entry['name'] }}</div>
        <div class="mt-1 text-xs text-slate-500">{{ $formatDate($entry['latestDate']) }}</div>
      </div>
      <div class="shrink-0 font-mono text-sm text-cyan-300">{{ $formatCount($entry['existCount']) }}</div>
    </a>
    @endforeach
  </div>
</section>

<section id="how" class="mb-16 scroll-mt-20">
  <h2 class="mb-3 text-xl font-black">{{ $t['how_h2'] }}</h2>
  <div class="max-w-3xl space-y-3 text-sm leading-6 text-slate-400">
    <p>{!! $t['how_p1'] !!}</p>
    <p>{{ $t['how_p2'] }}</p>
  </div>
</section>

<section id="updates" class="mb-16 scroll-mt-20">
  <h2 class="mb-3 text-xl font-black">{{ $t['home_update_h2'] }}</h2>
  <div class="max-w-3xl space-y-3 text-sm leading-6 text-slate-400">
    <p>{{ $t['home_update_p1'] }}</p>
    <p>{{ $t['home_update_p2'] }}</p>
  </div>
</section>

<section id="faq" class="mb-16 scroll-mt-20 mt-10 grid gap-4">
  <h2 class="text-2xl font-bold text-slate-100">{{ $t['faq_h2'] }}</h2>
  @foreach($t['faq_items'] as $faqItem)
  <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
    <h3 class="font-bold text-lg text-slate-100">{{ $faqItem[0] }}</h3>
    <p class="text-slate-300 leading-7 mt-2">{!! $faqItem[1] !!}</p>
  </div>
  @endforeach
</section>
@endsection

@section('scripts')
<script>
  (() => {
    const input = document.getElementById('brainrot-search');
    const tbody = document.getElementById('brainrot-list');
    const count = document.getElementById('brainrot-search-count');
    const empty = document.getElementById('brainrot-search-empty');
    const tagBtns = Array.from(document.querySelectorAll('#rarity-tags .tag-btn'));
    const newSearchBtn = document.getElementById('brainrot-new-search');
    const toggle = document.getElementById('brainrot-show-all');
    const toggleWrap = document.getElementById('brainrot-show-all-wrap');
    const sortBtns = Array.from(document.querySelectorAll('[data-sort-key]'));
    const PRODUCT_PREFIX = {!! json_encode($productUrlPrefix ?? '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
    const DATA_URL = {!! json_encode($homeDataUrl ?? '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
    let rows = {!! json_encode($ssrHomeRows ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
    const defaultLimit = 80;
    const showingAllTpl = {!! json_encode($t['search_showing_all']) !!};
    const showingFiltered = {!! json_encode($t['search_showing_filtered']) !!};
    const showAllLabel = {!! json_encode($t['home_show_all']) !!};
    const showTopLabel = {!! json_encode($t['home_show_top_10']) !!};
    const normalize = value => String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    const esc = value => String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
    const slugify = value => normalize(value).replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    const rarityClass = key => ({
      common: 'brainrot-rarity-common',
      rare: 'brainrot-rarity-rare',
      epic: 'brainrot-rarity-epic',
      legendary: 'brainrot-rarity-legendary',
      mythic: 'brainrot-rarity-mythic',
      'brainrot god': 'brainrot-rarity-brainrot-god',
      secret: 'brainrot-rarity-secret',
      og: 'brainrot-rarity-og',
    }[key] || 'brainrot-rarity-default');
    const signalTone = key => key === 'very_high'
      ? 'brainrot-signal-high'
      : (key === 'medium' ? 'brainrot-signal-medium' : 'brainrot-signal-low');

    let activeTag = '';
    let expanded = false;
    let activeSort = { key: '', direction: '' };

    const mutationChip = (label, countValue, href, kind) => {
      if (!label) return '';
      const countHtml = countValue != null ? `<span class="sab-mut-chip-count">${esc(countValue)}</span>` : '';
      const inner = `<span class="sab-mut-chip-label sab-mut-chip-label-${kind}" aria-hidden="true"></span>${countHtml}`;
      return href
        ? `<a href="${esc(href)}" title="${esc(label)}" onclick="event.stopPropagation()" class="sab-mut-chip">${inner}</a>`
        : `<span class="sab-mut-chip" title="${esc(label)}">${inner}</span>`;
    };

    const mutationDesktop = (label, countValue, href) => {
      if (!label) return '<span class="font-sans text-slate-500">—</span>';
      const countHtml = countValue != null ? `<span class="sab-mut-count whitespace-nowrap">${esc(countValue)}</span>` : '';
      const body = `<span class="sab-mut-name min-w-0 font-sans text-xs leading-snug text-slate-500 sm:text-sm sm:text-slate-300">${esc(label)}</span>${countHtml}`;
      return href
        ? `<a href="${esc(href)}" onclick="event.stopPropagation()" class="group flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-3">${body}</a>`
        : `<span class="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:gap-3">${body}</span>`;
    };

    const rowHtml = row => {
      const rarity = row.r || '';
      const badge = rarityClass(rarity);
      const canOpen = !!row.link && !!row.s;
      const href = canOpen ? `${PRODUCT_PREFIX}/products/${row.s}` : '';
      const mutationHref = canOpen && row.mn ? `${href}#mutation-${slugify(row.mn)}` : '';
      const traitHref = canOpen && row.tn ? `${href}#trait-${slugify(row.tn)}` : '';
      const newBadge = row.nw ? '<span class="brainrot-new-pill inline-flex shrink-0 items-center">NEW</span>' : '';
      const name = esc(row.n || '');
      const nameBlock = row.rl
        ? `<div class="sab-brainrot-mobile-meta brainrot-title-line"><span class="sab-brainrot-name min-w-0">${name}</span>${newBadge}<span class="brainrot-rarity-pill brainrot-inline-rarity inline-flex shrink-0 items-center ${badge}">${esc(row.rl)}</span></div><div class="sab-brainrot-name-only sab-brainrot-name">${name}${row.nw ? ' <span class="brainrot-new-pill ml-1 inline-flex align-middle">NEW</span>' : ''}</div>`
        : `<div class="sab-brainrot-name">${name}${row.nw ? ' <span class="brainrot-new-pill ml-1 inline-flex align-middle">NEW</span>' : ''}</div>`;
      const thumb = row.img
        ? `<img src="${esc(row.img)}" alt="${name}" width="64" height="64" class="sab-brainrot-thumb h-14 w-14 shrink-0 object-contain sm:h-20 sm:w-20" loading="lazy" />`
        : '<div class="sab-brainrot-thumb h-14 w-14 sm:h-20 sm:w-20 shrink-0"></div>';
      const linkOpen = canOpen ? `<a href="${esc(href)}" class="sab-brainrot-link flex min-w-0 items-center gap-2 sm:gap-3 hover:text-cyan-300">` : '<div class="sab-brainrot-link flex min-w-0 items-center gap-2 sm:gap-3">';
      const linkClose = canOpen ? '</a>' : '</div>';
      let countCell = '<div class="sab-exist-count-main text-slate-600">—</div>';
      if (row.k === 'known') countCell = `<div class="sab-exist-count-main">${esc(row.ecs)}</div>`;
      if (row.k === 'estimated') countCell = `<div class="sab-exist-count-main text-slate-400">${esc(row.ecs)}</div><div class="mt-0.5 inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-yellow-400/10 text-yellow-300" title="${name} exist count ${esc(row.ecs)}" aria-label="${name} exist count ${esc(row.ecs)}">~</div>`;
      const mutationCell = row.mn || row.tn
        ? `<div class="sab-mut-chips sm:hidden">${mutationChip(row.mn, row.mc, mutationHref, 'm')}${mutationChip(row.tn, row.tc, traitHref, 't')}</div><div class="hidden sm:block"><div>${mutationDesktop(row.mn, row.mc, mutationHref)}</div>${row.tn ? `<div class="mt-1">${mutationDesktop(row.tn, row.tc, traitHref)}</div>` : ''}</div>`
        : '<span class="font-sans text-slate-500">—</span>';
      const signal = row.ss
        ? `<span class="brainrot-signal ${signalTone(row.sk)} inline-flex items-center font-medium" data-signal-symbol="${esc(row.ss)}" aria-hidden="true"></span>`
        : '<span class="text-slate-600">—</span>';

      return `<tr class="sab-rarity-row ${badge} transition-colors${canOpen ? ' cursor-pointer' : ''}" data-search="${esc(row.q || '')}" data-rarity="${esc(rarity)}" data-is-new="${row.nw ? '1' : '0'}" data-sort-exist="${esc(row.e ?? '')}" data-sort-tier="${esc(row.tier ?? 2)}"${canOpen ? ` onclick="location.href='${esc(href)}'"` : ''}><td class="sab-col-brainrot pl-2.5 pr-2.5 py-2 sm:px-4 sm:py-3 font-medium align-top">${linkOpen}${thumb}<div class="min-w-0 flex-1 overflow-hidden">${nameBlock}</div>${linkClose}</td><td class="sab-col-rarity px-2 py-2 sm:px-4 sm:py-3 hidden sm:table-cell align-top">${row.rl ? `<span class="brainrot-rarity-pill brainrot-table-rarity inline-flex items-center ${badge}">${esc(row.rl)}</span>` : '<span class="text-slate-600">—</span>'}</td><td class="sab-col-count sab-count-cell px-2 py-2 sm:px-4 sm:py-3 font-mono align-top tabular-nums text-sm leading-tight whitespace-nowrap">${countCell}</td><td class="sab-col-mut sab-mut-cell px-2 py-2 sm:px-4 sm:py-3 align-top font-mono tabular-nums text-sm leading-tight">${mutationCell}</td><td class="px-2 py-2 sm:px-4 sm:py-3 hidden md:table-cell align-top">${signal}</td></tr>`;
    };

    const update = () => {
      const q = normalize(input.value);
      const filtered = rows.filter(row => {
        const matchQ = !q || normalize(row.q || '').includes(q);
        const matchTag = !activeTag || (row.r || '') === activeTag;
        return matchQ && matchTag;
      });

      const limit = expanded || q ? filtered.length : defaultLimit;
      tbody.innerHTML = filtered.slice(0, limit).map(rowHtml).join('');

      const total = rows.length;
      const showingAll = showingAllTpl.replace('{total}', total);
      const total2 = activeTag ? rows.filter(row => (row.r || '') === activeTag).length : total;
      count.textContent = q || activeTag
        ? showingFiltered.replace('{visible}', Math.min(filtered.length, limit)).replace('{total}', filtered.length)
        : (expanded ? showingAll : showingFiltered.replace('{visible}', Math.min(defaultLimit, total2)).replace('{total}', total2));

      const hasNoResults = filtered.length === 0;
      const shouldHideToggle = hasNoResults || q || filtered.length <= defaultLimit;
      const shouldHideWrap = !hasNoResults && (q || filtered.length <= defaultLimit);

      empty.style.display = hasNoResults ? 'block' : 'none';
      toggle.style.display = shouldHideToggle ? 'none' : 'block';
      if (shouldHideWrap) {
        toggleWrap.style.display = 'none';
      } else {
        toggleWrap.style.removeProperty('display');
      }
      toggle.textContent = expanded ? showTopLabel : showAllLabel;
      if (newSearchBtn) {
        newSearchBtn.dataset.active = q === 'new' ? 'true' : 'false';
      }
    };

    const sortRows = key => {
      const nextDirection = activeSort.key === key && activeSort.direction === 'asc' ? 'desc' : 'asc';
      activeSort = { key, direction: nextDirection };

      rows = [...rows].sort((a, b) => {
        if (key === 'exist') {
          const at = Number.parseInt(a.tier || '2', 10);
          const bt = Number.parseInt(b.tier || '2', 10);
          if (at !== bt) return at - bt;
        }

        const av = Number.parseFloat(a.e ?? '');
        const bv = Number.parseFloat(b.e ?? '');
        const aMissing = Number.isNaN(av);
        const bMissing = Number.isNaN(bv);

        if (aMissing && bMissing) return 0;
        if (aMissing) return 1;
        if (bMissing) return -1;

        return nextDirection === 'asc' ? av - bv : bv - av;
      });

      sortBtns.forEach(btn => {
        const isActive = btn.dataset.sortKey === key;
        btn.setAttribute('aria-sort', isActive ? (nextDirection === 'asc' ? 'ascending' : 'descending') : 'none');
        const indicator = btn.querySelector('.sab-sort-indicator');
        if (indicator) indicator.textContent = isActive ? (nextDirection === 'asc' ? '↑' : '↓') : '↕';
      });

      update();
    };

    tagBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        activeTag = btn.dataset.tag;
        expanded  = false;
        if (normalize(input.value) === 'new') {
          input.value = '';
        }
        tagBtns.forEach(b => {
          b.dataset.active = b === btn ? 'true' : 'false';
        });
        update();
      });
    });

    if (newSearchBtn) {
      newSearchBtn.addEventListener('click', () => {
        input.value = normalize(input.value) === 'new' ? '' : 'new';
        expanded = false;
        update();
        input.focus();
      });
    }

    toggle.addEventListener('click', () => {
      expanded = !expanded;
      update();
    });

    sortBtns.forEach(btn => {
      btn.addEventListener('click', () => sortRows(btn.dataset.sortKey));
    });

    input.addEventListener('input', update);
    update();

    if (DATA_URL) {
      fetch(DATA_URL, { credentials: 'same-origin' })
        .then(response => {
          if (!response.ok) throw new Error(`Exist count catalog request failed: ${response.status}`);
          return response.json();
        })
        .then(payload => {
          if (!Array.isArray(payload.rows)) throw new Error('Exist count catalog is invalid.');
          rows = payload.rows;
          update();
        })
        .catch(error => console.error('SAB home catalog failed', error));
    }
  })();
</script>
@endsection

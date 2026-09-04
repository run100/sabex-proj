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
  $listRows = $listRows ?? [];
  $listPerPage = (int) ($listPerPage ?? \App\Services\Seo\SabRenderService::EXIST_COUNTS_LIST_PER_PAGE);
  $ssrRows = array_slice($listRows, 0, $listPerPage);
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
  .sab-ecl-pagination {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    justify-content: center;
  }
  .sab-ecl-page-btn {
    align-items: center;
    background: rgba(15, 23, 42, 0.85);
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 0.5rem;
    color: #cbd5e1;
    cursor: pointer;
    display: inline-flex;
    font-size: 0.8125rem;
    font-weight: 600;
    justify-content: center;
    min-height: 2rem;
    min-width: 2rem;
    padding: 0.25rem 0.625rem;
  }
  .sab-ecl-page-btn:hover:not(:disabled) {
    border-color: rgba(34, 211, 238, 0.45);
    color: #67e8f9;
  }
  .sab-ecl-page-btn.is-active {
    background: rgba(6, 182, 212, 0.16);
    border-color: rgba(34, 211, 238, 0.55);
    color: #67e8f9;
  }
  .sab-ecl-page-btn:disabled {
    cursor: default;
    opacity: 0.4;
  }
  .sab-ecl-page-ellipsis {
    color: #64748b;
    font-size: 0.8125rem;
    padding: 0 0.25rem;
  }
  .sab-ecl-page-info {
    color: #64748b;
    font-size: 0.75rem;
    margin-top: 0.5rem;
    text-align: center;
  }
  #brainrot-list .sab-ecl-td {
    padding: 0.5rem 0.5rem;
    vertical-align: top;
  }
  @media (min-width: 640px) {
    #brainrot-list .sab-ecl-td {
      padding: 0.75rem 1rem;
    }
  }
  #brainrot-list .sab-col-brainrot.sab-ecl-td {
    padding-left: 0.625rem;
    padding-right: 0.625rem;
    font-weight: 500;
  }
  #brainrot-list tr.sab-rarity-row.is-clickable {
    cursor: pointer;
  }
  #brainrot-list .sab-ecl-link {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.5rem;
  }
  @media (min-width: 640px) {
    #brainrot-list .sab-ecl-link {
      gap: 0.75rem;
    }
  }
  #brainrot-list .sab-ecl-link:hover {
    color: #67e8f9;
  }
  #brainrot-list .sab-ecl-thumb {
    width: 3.5rem;
    height: 3.5rem;
    flex-shrink: 0;
    object-fit: contain;
  }
  @media (min-width: 640px) {
    #brainrot-list .sab-ecl-thumb {
      width: 5rem;
      height: 5rem;
    }
  }
  #brainrot-list .sab-ecl-name-wrap {
    min-width: 0;
    flex: 1 1 0%;
    overflow: hidden;
  }
  #brainrot-list .sab-ecl-muted {
    color: #475569;
  }
  #brainrot-list .sab-ecl-count {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-variant-numeric: tabular-nums;
    font-size: 0.875rem;
    line-height: 1.25;
    white-space: nowrap;
  }
  #brainrot-list .sab-ecl-est {
    color: #94a3b8;
  }
  #brainrot-list .sab-ecl-guess {
    display: inline-flex;
    align-items: center;
    margin-top: 0.125rem;
    border-radius: 0.25rem;
    padding: 0.125rem 0.375rem;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    background: rgba(250, 204, 21, 0.1);
    color: #fde047;
  }
  #brainrot-list .sab-ecl-mut-row {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
  }
  @media (min-width: 640px) {
    #brainrot-list .sab-ecl-mut-row {
      flex-direction: row;
      align-items: baseline;
      gap: 0.75rem;
    }
  }
  #brainrot-list .sab-ecl-mut-name {
    min-width: 0;
    font-family: ui-sans-serif, system-ui, sans-serif;
    font-size: 0.75rem;
    line-height: 1.375;
    color: #64748b;
  }
  @media (min-width: 640px) {
    #brainrot-list .sab-ecl-mut-name {
      font-size: 0.875rem;
      color: #cbd5e1;
    }
  }
  #brainrot-list .group:hover .sab-ecl-mut-name {
    color: #67e8f9;
  }
  #brainrot-list .sab-ecl-mut-second {
    margin-top: 0.25rem;
  }
  #brainrot-list .sab-mut-count {
    white-space: nowrap;
  }
  #brainrot-list .brainrot-rarity-pill {
    display: inline-flex;
    align-items: center;
  }
  #brainrot-list .brainrot-inline-rarity {
    flex-shrink: 0;
  }
  #brainrot-list .brainrot-signal {
    display: inline-flex;
    align-items: center;
    font-weight: 500;
  }
  .ecl-tool-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
  }
  .ecl-tool-chip {
    display: inline-flex;
    align-items: center;
    border: 1px solid rgba(34, 211, 238, 0.45);
    border-radius: 9999px;
    background: rgba(6, 182, 212, 0.12);
    padding: 0.4rem 0.85rem;
    color: #67e8f9;
    font-size: 0.8125rem;
    font-weight: 700;
    line-height: 1.2;
    text-decoration: none;
    transition: border-color 0.15s ease, color 0.15s ease, background-color 0.15s ease;
  }
  .ecl-tool-chip:hover {
    border-color: rgba(103, 232, 249, 0.85);
    background: rgba(6, 182, 212, 0.24);
    color: #ecfeff;
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

  #list .sab-home-list-card.sab-list-page-card {
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 0.75rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  }
  #list .sab-col-rarity,
  #list .sab-col-signal,
  #list .sab-col-mut-header-full,
  #list .sab-mut-desktop {
    display: none;
  }
  #list .sab-mut-chips {
    display: flex;
  }
  @media (min-width: 640px) {
    #list .sab-col-rarity {
      display: table-cell;
    }
    #list .sab-mut-chips {
      display: none !important;
    }
    #list .sab-mut-desktop {
      display: block;
    }
    #list .sab-col-mut-header-full {
      display: inline;
    }
    #list .sab-col-mut-header-short {
      display: none;
    }
  }
  @media (min-width: 768px) {
    #list .sab-col-signal {
      display: table-cell;
    }
  }

</style>

<header class="-mx-4 overflow-x-auto px-4 py-5 border-b border-white/10">
  <div class="flex flex-wrap items-center gap-2">
    <h1 class="text-xl font-black tracking-tight text-slate-100 md:text-3xl">
      {{ $t['exist_counts_list_h1_prefix'] }} <span class="text-cyan-400">{{ $t['exist_counts_list_h1_cyan'] }}</span>
    </h1>
    <span class="inline-flex items-center rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2.5 py-0.5 text-xs font-semibold text-cyan-300">{{ $t['exist_counts_list_month_badge'] ?? '' }}</span>
  </div>
  <p class="mt-4 max-w-3xl text-base md:text-lg text-slate-300 leading-8">{{ $t['exist_counts_list_intro'] }}</p>
</header>


<section id="list" class="mb-16 mt-8 scroll-mt-20">
  <h2 class="sr-only">{{ $t['exist_counts_list_table_h2'] }}</h2>

  @php
    $relatedEnglishPrefix = $productUrlPrefix
      ?? (str_starts_with((string) ($urlPrefix ?? ''), '/seo/sab/preview') ? '/seo/sab/preview' : '');
    $relatedPrefix = rtrim((string) ($urlPrefix ?? ''), '/');
    $relatedEnglish = rtrim((string) $relatedEnglishPrefix, '/');
    $toolChips = [
      [
        'href' => $relatedPrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_TRADING_CALCULATOR,
        'label' => $t['exist_counts_list_tool_calculator'] ?? 'Calculator',
      ],
      [
        'href' => $relatedEnglish . '/' . \App\Services\Seo\SabRenderService::PAGE_VALUE_LIST,
        'label' => $t['exist_counts_list_tool_value_list'] ?? 'Value List',
      ],
      [
        'href' => $relatedPrefix . '/' . \App\Services\Seo\SabRenderService::PAGE_CODES,
        'label' => $t['exist_counts_list_tool_codes'] ?? 'Codes',
      ],
      [
        'href' => $relatedEnglish . '/' . \App\Services\Seo\SabRenderService::PAGE_EXIST_COUNT_GALLERY,
        'label' => $t['exist_counts_list_tool_gallery'] ?? 'Gallery',
      ],
    ];
  @endphp
  <div class="mb-3 mt-4">
    <nav class="ecl-tool-chips" aria-label="{{ $t['exist_counts_list_tools_label'] ?? 'Tools' }}">
      @foreach($toolChips as $tool)
      <a class="ecl-tool-chip" href="{{ $tool['href'] }}">{{ $tool['label'] }}</a>
      @endforeach
    </nav>
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
  </div>


  <div class="sab-home-list-card sab-list-page-card rounded-xl border border-white/10 bg-slate-900 shadow-sm shadow-black/20 overflow-hidden">
    <div class="sab-table-scroll-wrap overflow-x-auto">
    <table class="sab-home-table w-full text-xs sm:text-sm">
      <caption class="sr-only">{{ $t['exist_counts_list_table_caption'] }}</caption>
      <thead class="bg-white/[0.03]">
        <tr>
          <th class="sab-col-brainrot pl-2.5 pr-2.5 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t['col_brainrot'] }}</th>
          <th class="sab-col-rarity px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ $t['col_rarity'] }}</th>
          <th class="sab-col-count px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">
            <button type="button" class="sab-sort-btn" data-sort-key="exist" aria-sort="none">
              <span class="sab-sort-label">Exist<br>Count</span>
              <span class="sab-sort-indicator" aria-hidden="true">↕</span>
            </button>
          </th>
          <th class="sab-col-mut px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider leading-tight"><span class="sab-col-mut-header-short">{{ $t['col_mutations_traits_short'] ?? 'M/T' }}</span><span class="sab-col-mut-header-full">{!! $t['col_mutations_traits'] ?? 'Mutations<br>Traits' !!}</span></th>
          <th class="px-2 py-2 sm:px-4 sm:py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider sab-col-signal">{{ $t['col_signal'] }}</th>
        </tr>
      </thead>
      <tbody id="brainrot-list">
        @foreach($ssrRows as $row)
          @include('seo.sab.partials._exist-counts-list-row', ['row' => $row, 'productUrlPrefix' => $productUrlPrefix ?? ''])
        @endforeach
      </tbody>
    </table>
    </div>
    <div class="sab-list-table-footer border-t border-white/10 bg-white/[0.02] px-3 py-3 sm:px-4">
      <div id="brainrot-search-empty" class="hidden text-center text-sm" aria-live="polite">
        <p class="font-semibold text-slate-100">{{ $t['search_empty_title'] ?? $t['search_empty'] }}</p>
        <p class="mt-1 text-slate-400">
          {{ $t['search_empty_body'] ?? 'Try another Brainrot name, rarity, or count.' }}
        </p>
      </div>
      <div id="brainrot-pagination-wrap">
        <nav id="brainrot-pagination" class="sab-ecl-pagination" aria-label="Pagination"></nav>
        <p id="brainrot-page-info" class="sab-ecl-page-info" aria-live="polite"></p>
      </div>
    </div>
  </div>

  <p class="text-xs text-slate-500 mt-3">{{ $t['exist_counts_list_updated'] }}</p>
</section>

<section id="faq" class="mb-16 scroll-mt-20">
  <h2 class="text-xl font-black text-slate-100 mb-4">{{ $t['exist_counts_list_faq_h2'] ?? 'Frequently Asked Questions About Steal a Brainrot Exist Count' }}</h2>
  <div class="grid gap-4 md:grid-cols-2">
    @foreach($existCountsListFaqItems ?? [] as $faqItem)
      <article class="rounded-lg border border-white/10 bg-slate-900/60 p-4">
        <h3 class="font-bold text-slate-100">{{ $faqItem['question'] }}</h3>
        <p class="text-slate-400 text-sm mt-2 leading-relaxed">{!! $faqItem['answer'] !!}</p>
      </article>
    @endforeach
  </div>
</section>
@endsection

@section('scripts')
<script>
  (() => {
    const PER_PAGE = {{ (int) $listPerPage }};
    const PRODUCT_PREFIX = {!! json_encode($productUrlPrefix ?? '') !!};
    const ALL_ROWS = {!! json_encode($listRows ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!};
    const input = document.getElementById('brainrot-search');
    const tbody = document.getElementById('brainrot-list');
    const count = document.getElementById('brainrot-search-count');
    const empty = document.getElementById('brainrot-search-empty');
    const pagWrap = document.getElementById('brainrot-pagination-wrap');
    const pagNav = document.getElementById('brainrot-pagination');
    const pageInfo = document.getElementById('brainrot-page-info');
    const tagBtns = Array.from(document.querySelectorAll('#rarity-tags .tag-btn'));
    const sortBtns = Array.from(document.querySelectorAll('[data-sort-key]'));
    const total = ALL_ROWS.length;
    const showingAll = {!! json_encode($t['search_showing_all']) !!}.replace('{total}', total);
    const showingFiltered = {!! json_encode($t['search_showing_filtered']) !!};
    const pageInfoTpl = {!! json_encode($t['exist_counts_list_page_info'] ?? '{start}–{end} of {total}') !!};
    const prevLabel = {!! json_encode($t['exist_counts_list_page_prev'] ?? 'Prev') !!};
    const nextLabel = {!! json_encode($t['exist_counts_list_page_next'] ?? 'Next') !!};
    const normalize = v => String(v || '').toLowerCase().replace(/\s+/g, ' ').trim();
    const esc = s => String(s ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
    const slugify = s => normalize(s).replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

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

    const sigTone = key => key === 'very_high'
      ? 'brainrot-signal-high'
      : (key === 'medium' ? 'brainrot-signal-medium' : 'brainrot-signal-low');

    let activeTag = '';
    let activeSort = { key: '', direction: '' };
    let currentPage = 1;
    let workingRows = ALL_ROWS.slice();

    const mutChip = (label, count, href, kind) => {
      if (!label) return '';
      const countHtml = count != null ? `<span class="sab-mut-chip-count">${esc(count)}</span>` : '';
      const inner = `<span class="sab-mut-chip-label sab-mut-chip-label-${kind}" aria-hidden="true"></span>${countHtml}`;
      if (href) {
        return `<a href="${esc(href)}" title="${esc(label)}" onclick="event.stopPropagation()" class="sab-mut-chip">${inner}</a>`;
      }
      return `<span class="sab-mut-chip" title="${esc(label)}">${inner}</span>`;
    };

    const mutDesktop = (label, count, href) => {
      if (!label) return '<span class="sab-ecl-muted">—</span>';
      const countHtml = count != null ? `<span class="sab-mut-count">${esc(count)}</span>` : '';
      const body = `<span class="sab-mut-name sab-ecl-mut-name">${esc(label)}</span>${countHtml}`;
      if (href) {
        return `<a href="${esc(href)}" onclick="event.stopPropagation()" class="sab-ecl-mut-row group">${body}</a>`;
      }
      return `<span class="sab-ecl-mut-row">${body}</span>`;
    };

    const rowHtml = row => {
      const badge = rarityClass(row.r || '');
      const canOpen = !!row.link && !!row.s;
      const href = canOpen ? `${PRODUCT_PREFIX}/products/${row.s}` : '';
      const mutHref = canOpen && row.mn ? `${href}#mutation-${slugify(row.mn)}` : '';
      const traitHref = canOpen && row.tn ? `${href}#trait-${slugify(row.tn)}` : '';
      const clickable = canOpen ? ' is-clickable' : '';
      const onclick = canOpen ? ` onclick="location.href='${esc(href)}'"` : '';
      const thumb = row.img
        ? `<img src="${esc(row.img)}" alt="${esc(row.n)}" width="64" height="64" class="sab-brainrot-thumb sab-ecl-thumb" loading="lazy" />`
        : '<div class="sab-brainrot-thumb sab-ecl-thumb"></div>';
      const nameBlock = row.rl
        ? `<div class="sab-brainrot-mobile-meta brainrot-title-line"><span class="sab-brainrot-name">${esc(row.n)}</span><span class="brainrot-rarity-pill brainrot-inline-rarity ${badge}">${esc(row.rl)}</span></div><div class="sab-brainrot-name-only sab-brainrot-name">${esc(row.n)}</div>`
        : `<div class="sab-brainrot-name">${esc(row.n)}</div>`;
      const linkOpen = canOpen ? `<a href="${esc(href)}" class="sab-brainrot-link sab-ecl-link">` : '<div class="sab-brainrot-link sab-ecl-link">';
      const linkClose = canOpen ? '</a>' : '</div>';
      const rarityCell = row.rl
        ? `<span class="brainrot-rarity-pill brainrot-table-rarity ${badge}">${esc(row.rl)}</span>`
        : '<span class="sab-ecl-muted">—</span>';
      let countCell = '<div class="sab-exist-count-main sab-ecl-muted">—</div>';
      if (row.k === 'known') {
        countCell = `<div class="sab-exist-count-main">${esc(row.ecs)}</div>`;
      } else if (row.k === 'estimated') {
        countCell = `<div class="sab-exist-count-main sab-ecl-est">${esc(row.ecs)}</div><div class="sab-ecl-guess" title="${esc(row.n)} exist count ${esc(row.ecs)}" aria-label="${esc(row.n)} exist count ${esc(row.ecs)}">~</div>`;
      }
      let mutCell = '<span class="sab-ecl-muted">—</span>';
      if (row.mn || row.tn) {
        const chips = `${mutChip(row.mn, row.mc, mutHref, 'm')}${mutChip(row.tn, row.tc, traitHref, 't')}`;
        const second = row.tn ? `<div class="sab-ecl-mut-second">${mutDesktop(row.tn, row.tc, traitHref)}</div>` : '';
        mutCell = `<div class="sab-mut-chips">${chips}</div><div class="sab-mut-desktop"><div>${mutDesktop(row.mn, row.mc, mutHref)}</div>${second}</div>`;
      }
      const signalCell = row.ss
        ? `<span class="brainrot-signal ${sigTone(row.sk)}" data-signal-symbol="${esc(row.ss)}" aria-hidden="true"></span>`
        : '<span class="sab-ecl-muted">—</span>';
      const sortExist = row.e == null ? '' : row.e;

      return `<tr class="sab-rarity-row ${badge}${clickable}" data-search="${esc(row.q || '')}" data-rarity="${esc(row.r || '')}" data-sort-exist="${esc(sortExist)}" data-sort-tier="${esc(row.tier ?? 2)}"${onclick}><td class="sab-col-brainrot sab-ecl-td">${linkOpen}${thumb}<div class="sab-ecl-name-wrap">${nameBlock}</div>${linkClose}</td><td class="sab-col-rarity sab-ecl-td">${rarityCell}</td><td class="sab-col-count sab-count-cell sab-ecl-td sab-ecl-count">${countCell}</td><td class="sab-col-mut sab-mut-cell sab-ecl-td sab-ecl-count">${mutCell}</td><td class="sab-col-signal sab-ecl-td">${signalCell}</td></tr>`;
    };

    const pageButtons = pages => {
      const MAX_VISIBLE = 7;
      if (pages <= MAX_VISIBLE) return Array.from({ length: pages }, (_, i) => i + 1);
      const buttons = [1];
      let start = Math.max(2, currentPage - 1);
      let end = Math.min(pages - 1, currentPage + 1);
      if (currentPage <= 3) { start = 2; end = 4; }
      else if (currentPage >= pages - 2) { start = pages - 3; end = pages - 1; }
      if (start > 2) buttons.push('…');
      for (let i = start; i <= end; i++) buttons.push(i);
      if (end < pages - 1) buttons.push('…');
      buttons.push(pages);
      return buttons;
    };

    const renderPagination = (pages, filteredCount) => {
      if (!pagNav || !pageInfo || !pagWrap) return;
      const hasPages = filteredCount > 0 && pages > 1;
      pagWrap.classList.toggle('hidden', filteredCount === 0);
      if (!hasPages) {
        pagNav.innerHTML = '';
        pageInfo.textContent = filteredCount > 0
          ? pageInfoTpl.replace('{start}', '1').replace('{end}', String(filteredCount)).replace('{total}', String(filteredCount))
          : '';
        return;
      }
      const start = (currentPage - 1) * PER_PAGE + 1;
      const end = Math.min(currentPage * PER_PAGE, filteredCount);
      pageInfo.textContent = pageInfoTpl
        .replace('{start}', String(start))
        .replace('{end}', String(end))
        .replace('{total}', String(filteredCount));
      const parts = [];
      parts.push(`<button type="button" class="sab-ecl-page-btn" data-page="prev" ${currentPage === 1 ? 'disabled' : ''}>${prevLabel}</button>`);
      pageButtons(pages).forEach(item => {
        if (item === '…') {
          parts.push('<span class="sab-ecl-page-ellipsis" aria-hidden="true">…</span>');
          return;
        }
        const active = item === currentPage ? ' is-active' : '';
        const aria = item === currentPage ? ' aria-current="page"' : '';
        parts.push(`<button type="button" class="sab-ecl-page-btn${active}" data-page="${item}"${aria}>${item}</button>`);
      });
      parts.push(`<button type="button" class="sab-ecl-page-btn" data-page="next" ${currentPage === pages ? 'disabled' : ''}>${nextLabel}</button>`);
      pagNav.innerHTML = parts.join('');
    };

    const update = () => {
      const q = normalize(input.value);
      const filtered = workingRows.filter(row => {
        const matchQ = !q || normalize(row.q || '').includes(q);
        const matchTag = !activeTag || (row.r || '') === activeTag;
        return matchQ && matchTag;
      });
      const pages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
      currentPage = Math.min(Math.max(1, currentPage), pages);
      const start = (currentPage - 1) * PER_PAGE;
      const slice = filtered.slice(start, start + PER_PAGE);
      tbody.innerHTML = slice.map(rowHtml).join('');
      count.textContent = q || activeTag
        ? showingFiltered.replace('{visible}', filtered.length).replace('{total}', filtered.length)
        : showingAll;
      empty.classList.toggle('hidden', filtered.length !== 0);
      renderPagination(pages, filtered.length);
    };

    const sortRows = key => {
      const nextDirection = activeSort.key === key && activeSort.direction === 'asc' ? 'desc' : 'asc';
      activeSort = { key, direction: nextDirection };
      currentPage = 1;
      workingRows = [...workingRows].sort((a, b) => {
        if (key === 'exist') {
          const at = Number(a.tier ?? 2);
          const bt = Number(b.tier ?? 2);
          if (at !== bt) return at - bt;
        }
        const av = Number.parseFloat(a.e);
        const bv = Number.parseFloat(b.e);
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
        currentPage = 1;
        tagBtns.forEach(b => { b.dataset.active = b === btn ? 'true' : 'false'; });
        update();
      });
    });
    sortBtns.forEach(btn => btn.addEventListener('click', () => sortRows(btn.dataset.sortKey)));
    pagNav?.addEventListener('click', event => {
      const btn = event.target.closest('[data-page]');
      if (!btn || btn.disabled) return;
      const target = btn.dataset.page;
      if (target === 'prev') currentPage = Math.max(1, currentPage - 1);
      else if (target === 'next') currentPage += 1;
      else currentPage = Number.parseInt(target, 10) || 1;
      update();
    });
    input.addEventListener('input', () => { currentPage = 1; update(); });
    update();
  })();
</script>
@endsection

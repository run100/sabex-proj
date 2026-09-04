<template>
  <div class="console-shell">
    <aside class="console-nav">
      <p class="console-brand">SAB Console</p>
      <el-menu :default-active="tab" @select="tab = $event">
        <el-menu-item index="items">Items</el-menu-item>
        <el-menu-item index="news">News</el-menu-item>
        <el-menu-item index="codes">Codes</el-menu-item>
        <el-menu-item index="jobs">Jobs</el-menu-item>
      </el-menu>
      <form method="post" action="/logout">
        <input type="hidden" name="_token" :value="csrf">
        <el-button class="console-logout" native-type="submit">Sign out</el-button>
      </form>
    </aside>
    <main class="console-main">
      <ItemsPanel v-if="tab === 'items'" />
      <NewsPanel v-else-if="tab === 'news'" />
      <CodesPanel v-else-if="tab === 'codes'" />
      <JobsPanel v-else />
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ItemsPanel from './panels/ItemsPanel.vue';
import NewsPanel from './panels/NewsPanel.vue';
import CodesPanel from './panels/CodesPanel.vue';
import JobsPanel from './panels/JobsPanel.vue';

const tab = ref('items');
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
</script>

<style>
:root {
  --el-color-primary: #0e7490;
  --el-bg-color: #0b1220;
  --el-bg-color-overlay: #111827;
  --el-text-color-primary: #e2e8f0;
  --el-border-color: rgba(148, 163, 184, 0.24);
  --font-heading: "Avenir Next", "Segoe UI", sans-serif;
  --font-ui: "Avenir Next", "Segoe UI", sans-serif;
  color-scheme: dark;
}
html, body, #console { margin: 0; min-height: 100%; background: #0b1220; color: #e2e8f0; font-family: var(--font-ui); }
.console-shell { display: grid; grid-template-columns: 13rem 1fr; min-height: 100vh; }
.console-nav { border-right: 1px solid rgba(148, 163, 184, 0.16); padding: 1rem 0.75rem; background: #111827; }
.console-brand { margin: 0 0 1rem; font-family: var(--font-heading); font-weight: 800; color: #67e8f9; }
.console-logout { width: 100%; margin-top: 1.25rem; }
.console-main { padding: 1.25rem 1.5rem; }
.el-menu { background: transparent; border: 0; }
</style>

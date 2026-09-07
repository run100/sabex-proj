<template>
  <el-container class="console-shell">
    <el-aside width="220px" class="console-aside">
      <p class="console-brand">Admin-SAB</p>
      <el-menu
        :default-active="route.path"
        :default-openeds="['trades']"
        background-color="#111827"
        text-color="#cbd5e1"
        active-text-color="#67e8f9"
        @select="router.push($event)"
      >
        <el-menu-item index="/items">
          <el-icon><Goods /></el-icon>
          <span>Items</span>
        </el-menu-item>
        <el-menu-item index="/sites">
          <el-icon><Monitor /></el-icon>
          <span>Sites</span>
        </el-menu-item>
        <el-menu-item index="/news">
          <el-icon><Document /></el-icon>
          <span>News</span>
        </el-menu-item>
        <el-menu-item index="/codes">
          <el-icon><Ticket /></el-icon>
          <span>Codes</span>
        </el-menu-item>
        <el-menu-item index="/users">
          <el-icon><User /></el-icon>
          <span>Users</span>
        </el-menu-item>
        <el-sub-menu index="trades">
          <template #title>
            <el-icon><List /></el-icon>
            <span>Trades</span>
          </template>
          <el-menu-item index="/listings">Listings</el-menu-item>
          <el-menu-item index="/joins">Joins</el-menu-item>
          <el-menu-item index="/reports">Reports</el-menu-item>
          <el-menu-item index="/email-codes">Email codes</el-menu-item>
        </el-sub-menu>
        <el-menu-item index="/jobs">
          <el-icon><Setting /></el-icon>
          <span>Jobs</span>
        </el-menu-item>
        <el-menu-item index="/logs">
          <el-icon><Notebook /></el-icon>
          <span>Logs</span>
        </el-menu-item>
      </el-menu>
      <form method="post" action="/logout" class="console-logout">
        <input type="hidden" name="_token" :value="csrf">
        <el-button native-type="submit">Sign out</el-button>
      </form>
    </el-aside>
    <el-container>
      <el-header class="console-header">{{ title }}</el-header>
      <el-main class="console-main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { Document, Goods, List, Monitor, Notebook, Setting, Ticket, User } from '@element-plus/icons-vue';
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const titles = {
  '/items': 'Items',
  '/sites': 'Sites',
  '/news': 'News',
  '/codes': 'Codes',
  '/users': 'Users',
  '/listings': 'Trade listings',
  '/joins': 'Trade joins',
  '/reports': 'Trade reports',
  '/email-codes': 'Email codes',
  '/jobs': 'Jobs',
  '/logs': 'Logs',
};
const title = computed(() => titles[route.path] ?? 'Items');
</script>

<style>
:root {
  --el-color-primary: #0e7490;
  --el-bg-color: #0b1220;
  --el-bg-color-overlay: #111827;
  --el-fill-color-blank: #0b1220;
  --el-text-color-primary: #e2e8f0;
  --el-border-color: rgba(148, 163, 184, 0.24);
  --el-table-bg-color: #111827;
  --el-table-tr-bg-color: #111827;
  --el-table-header-bg-color: #0b1220;
  --el-table-row-hover-bg-color: #1e293b;
  --font-heading: "Avenir Next", "Segoe UI", sans-serif;
  --font-ui: "Avenir Next", "Segoe UI", sans-serif;
  color-scheme: dark;
}
html, body, #console { margin: 0; min-height: 100%; background: #0b1220; color: #e2e8f0; font-family: var(--font-ui); }
.console-shell { min-height: 100vh; }
.console-aside {
  background: #111827;
  border-right: 1px solid rgba(148, 163, 184, 0.16);
  padding: 1rem 0.75rem 1.25rem;
  display: flex;
  flex-direction: column;
}
.console-brand { margin: 0 0 1rem 0.5rem; font-family: var(--font-heading); font-weight: 800; color: #67e8f9; }
.console-logout { margin-top: auto; padding-top: 1.25rem; }
.console-logout .el-button { width: 100%; }
.console-header {
  height: 56px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid rgba(148, 163, 184, 0.16);
  background: #0b1220;
  color: #e2e8f0;
  font-family: var(--font-heading);
  font-weight: 700;
}
.console-main { background: #0b1220; }
.el-menu { background: transparent; border: 0; }
</style>

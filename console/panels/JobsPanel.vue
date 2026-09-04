<template>
  <section class="jobs">
    <article>
      <h2>GEOFlow upsert</h2>
      <p>Read-only copy from Postgres, then checksum-copy support JSON. Does not write observations or price history.</p>
      <el-checkbox v-model="dryRun">Dry run</el-checkbox>
      <el-button type="primary" :loading="geoLoading" @click="runGeoflow">Run GEOFlow sync</el-button>
    </article>
    <article>
      <h2>rot.rocks daily refresh</h2>
      <p>Updates listed items only: current values, observations, and today’s price JSON point.</p>
      <el-button type="primary" :loading="calcLoading" @click="runCalculator">Run calculator refresh</el-button>
    </article>
    <pre v-if="log">{{ log }}</pre>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const dryRun = ref(true);
const geoLoading = ref(false);
const calcLoading = ref(false);
const log = ref('');

async function runGeoflow() {
  geoLoading.value = true;
  try {
    const data = await api('/api/jobs/geoflow', {
      method: 'POST',
      body: JSON.stringify({ dry_run: dryRun.value }),
    });
    log.value = [data.output, JSON.stringify(data.files, null, 2)].filter(Boolean).join('\n\n');
    ElMessage.success(data.ok ? 'Finished' : 'Finished with errors');
  } catch (error) {
    ElMessage.error(error.message);
    log.value = error.message;
  } finally {
    geoLoading.value = false;
  }
}

async function runCalculator() {
  calcLoading.value = true;
  try {
    const data = await api('/api/jobs/calculator', { method: 'POST', body: '{}' });
    log.value = JSON.stringify(data.result, null, 2);
    ElMessage.success('Refresh finished');
  } catch (error) {
    ElMessage.error(error.message);
    log.value = error.message;
  } finally {
    calcLoading.value = false;
  }
}
</script>

<style scoped>
.jobs { display: grid; gap: 1.25rem; max-width: 48rem; }
article { padding: 1rem; border: 1px solid rgba(148,163,184,.18); background: #111827; }
h2 { margin: 0 0 .4rem; font-size: 1.05rem; }
p { color: #94a3b8; }
.el-button { margin-top: .75rem; margin-right: .75rem; }
pre { white-space: pre-wrap; color: #cbd5e1; background: #020617; padding: 1rem; }
</style>

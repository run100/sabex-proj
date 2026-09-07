<template>
  <section>
    <el-table :data="logs" stripe v-loading="loading">
      <template #empty>
        <el-empty description="No access logs yet." />
      </template>
      <el-table-column prop="action" label="Action" width="110" />
      <el-table-column prop="actor_type" label="Actor" width="120" />
      <el-table-column prop="actor_id" label="Actor ID" width="100" />
      <el-table-column label="IP" min-width="140">
        <template #default="{ row }">
          {{ row.ip || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Subject" min-width="160">
        <template #default="{ row }">
          {{ subjectText(row) }}
        </template>
      </el-table-column>
      <el-table-column label="When" min-width="170">
        <template #default="{ row }">
          {{ formatTime(row.created_at) }}
        </template>
      </el-table-column>
      <el-table-column label="User agent" min-width="220">
        <template #default="{ row }">
          {{ row.user_agent || '—' }}
        </template>
      </el-table-column>
    </el-table>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const logs = ref([]);
const loading = ref(false);

function subjectText(row) {
  if (!row.subject_type) {
    return '—';
  }

  return row.subject_type + (row.subject_id ? ' #' + row.subject_id : '');
}

function formatTime(value) {
  if (!value) {
    return '—';
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Shanghai',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).format(date).replace('T', ' ');
}

async function load() {
  loading.value = true;
  try {
    const data = await api('/api/access-logs');
    logs.value = data.logs || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

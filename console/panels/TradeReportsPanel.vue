<template>
  <section>
    <div class="toolbar">
      <el-select v-model="status" clearable placeholder="All statuses" @change="load">
        <el-option label="open" value="open" />
        <el-option label="reviewing" value="reviewing" />
        <el-option label="resolved" value="resolved" />
        <el-option label="dismissed" value="dismissed" />
      </el-select>
    </div>
    <el-table :data="reports" stripe v-loading="loading">
      <template #empty>
        <el-empty description="No trade reports yet" />
      </template>
      <el-table-column prop="public_id" label="ID" min-width="180" />
      <el-table-column prop="listing_public_id" label="Listing" min-width="160" />
      <el-table-column label="Reporter" min-width="120">
        <template #default="{ row }">
          {{ row.reporter_username || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Reporter profile" min-width="220">
        <template #default="{ row }">
          {{ row.reporter_profile_id || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Reported" min-width="120">
        <template #default="{ row }">
          {{ row.reported_username || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Reported profile" min-width="220">
        <template #default="{ row }">
          {{ row.reported_profile_id || '—' }}
        </template>
      </el-table-column>
      <el-table-column prop="reason" label="Reason" width="130" />
      <el-table-column prop="status" label="Status" width="110" />
      <el-table-column label="Note" min-width="160">
        <template #default="{ row }">
          {{ row.resolution_note || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Action" width="220">
        <template #default="{ row }">
          <el-button size="small" @click="review(row, 'reviewing')">Review</el-button>
          <el-button size="small" type="primary" @click="review(row, 'resolved')">Resolve</el-button>
          <el-button size="small" @click="review(row, 'dismissed')">Dismiss</el-button>
        </template>
      </el-table-column>
    </el-table>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const status = ref('');
const reports = ref([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const query = status.value ? '?status=' + encodeURIComponent(status.value) : '';
    const data = await api('/api/trade-reports' + query);
    reports.value = data.reports || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

async function review(row, nextStatus) {
  try {
    const data = await api('/api/trade-reports/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify({ status: nextStatus }),
    });
    Object.assign(row, data.report || {});
    ElMessage.success('Updated');
  } catch (error) {
    ElMessage.error(error.message);
    await load();
  }
}

onMounted(load);
</script>

<style scoped>
.toolbar { display: flex; gap: .75rem; margin-bottom: 1rem; max-width: 16rem; }
</style>

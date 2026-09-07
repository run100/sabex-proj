<template>
  <section>
    <div class="toolbar">
      <el-select v-model="status" clearable placeholder="All statuses" @change="load">
        <el-option label="requested" value="requested" />
        <el-option label="accepted" value="accepted" />
        <el-option label="rejected" value="rejected" />
        <el-option label="auto_rejected" value="auto_rejected" />
        <el-option label="cancelled" value="cancelled" />
        <el-option label="expired" value="expired" />
      </el-select>
    </div>
    <el-table :data="joins" stripe v-loading="loading">
      <template #empty>
        <el-empty description="No join requests yet" />
      </template>
      <el-table-column prop="public_id" label="ID" min-width="180" />
      <el-table-column prop="listing_public_id" label="Listing" min-width="180" />
      <el-table-column label="Requester" min-width="140">
        <template #default="{ row }">
          {{ row.requester_username || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Requester profile" min-width="220">
        <template #default="{ row }">
          {{ row.requester_profile_id || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Email" min-width="180">
        <template #default="{ row }">
          {{ row.requester_email || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Owner" min-width="140">
        <template #default="{ row }">
          {{ row.owner_username || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Owner profile" min-width="220">
        <template #default="{ row }">
          {{ row.owner_profile_id || '—' }}
        </template>
      </el-table-column>
      <el-table-column prop="status" label="Status" width="130" />
      <el-table-column label="Note" min-width="160">
        <template #default="{ row }">
          {{ row.note || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Created" min-width="170">
        <template #default="{ row }">
          {{ row.created_at || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Action" width="100" fixed="right">
        <template #default="{ row }">
          <el-button
            v-if="row.status === 'requested'"
            size="small"
            type="danger"
            @click="deleteJoin(row)"
          >Delete</el-button>
        </template>
      </el-table-column>
    </el-table>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { api } from '../api';

const status = ref('');
const joins = ref([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const query = status.value ? '?status=' + encodeURIComponent(status.value) : '';
    const data = await api('/api/trade-joins' + query);
    joins.value = data.joins || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

async function deleteJoin(row) {
  try {
    await ElMessageBox.confirm(
      'Delete this send offer? This cannot be undone.',
      'Delete offer',
      { type: 'warning', confirmButtonText: 'Delete', cancelButtonText: 'Back' },
    );
  } catch {
    return;
  }
  try {
    await api('/api/trade-joins/' + row.id, { method: 'DELETE' });
    ElMessage.success('Deleted');
    await load();
  } catch (error) {
    ElMessage.error(error.message);
  }
}

onMounted(load);
</script>

<style scoped>
.toolbar { display: flex; gap: .75rem; margin-bottom: 1rem; max-width: 16rem; }
</style>

<template>
  <section>
    <p class="hint">Local accounts start unapproved. Approve to allow posting. Status cannot be set to deleted here.</p>
    <div class="toolbar">
      <el-input v-model="q" placeholder="Search username / email / profile_id" clearable @clear="load" @keyup.enter="load" />
      <el-button type="primary" @click="load">Search</el-button>
      <el-radio-group v-model="scope">
        <el-radio-button value="all">All</el-radio-button>
        <el-radio-button value="email">Email</el-radio-button>
      </el-radio-group>
    </div>
    <el-table :data="visible" stripe v-loading="loading">
      <template #empty>
        <el-empty description="No trade users in this database yet." />
      </template>
      <el-table-column prop="username" label="Username" min-width="140" />
      <el-table-column label="Profile ID" min-width="220">
        <template #default="{ row }">
          {{ row.profile_id || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Roblox UID" min-width="130">
        <template #default="{ row }">
          {{ row.roblox_user_id || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Email" min-width="200">
        <template #default="{ row }">
          {{ row.email || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Providers" min-width="160">
        <template #default="{ row }">
          <el-tag v-for="provider in row.providers || []" :key="provider" size="small" class="provider">
            {{ provider }}
          </el-tag>
          <span v-if="!(row.providers || []).length">—</span>
        </template>
      </el-table-column>
      <el-table-column label="Status" width="140">
        <template #default="{ row }">
          <el-select
            :model-value="row.account_status"
            size="small"
            @change="(value) => patch(row, { account_status: value })"
          >
            <el-option v-for="status in accountStatuses" :key="status" :label="status" :value="status" />
          </el-select>
        </template>
      </el-table-column>
      <el-table-column label="Visibility" width="130">
        <template #default="{ row }">
          <el-select
            :model-value="row.profile_visibility"
            size="small"
            @change="(value) => patch(row, { profile_visibility: value })"
          >
            <el-option v-for="value in visibilities" :key="value" :label="value" :value="value" />
          </el-select>
        </template>
      </el-table-column>
      <el-table-column label="Moderation" width="140">
        <template #default="{ row }">
          <el-select
            :model-value="row.moderation_status"
            size="small"
            @change="(value) => patch(row, { moderation_status: value })"
          >
            <el-option v-for="value in moderations" :key="value" :label="value" :value="value" />
          </el-select>
        </template>
      </el-table-column>
      <el-table-column label="Index" width="90">
        <template #default="{ row }">
          <el-switch
            :model-value="Boolean(row.profile_index_eligible)"
            @change="(value) => patch(row, { profile_index_eligible: value })"
          />
        </template>
      </el-table-column>
      <el-table-column label="Deleted" min-width="160">
        <template #default="{ row }">
          {{ row.deleted_at || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Registered IP" min-width="140">
        <template #default="{ row }">
          {{ row.registered_ip || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Last login IP" min-width="140">
        <template #default="{ row }">
          {{ row.last_login_ip || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Can post" width="90">
        <template #default="{ row }">
          {{ row.posting_approved ? 'Yes' : 'No' }}
        </template>
      </el-table-column>
      <el-table-column label="Action" width="160" fixed="right">
        <template #default="{ row }">
          <el-button
            v-if="!row.posting_approved"
            type="primary"
            size="small"
            @click="patch(row, { posting_approved: true }, 'Approved')"
          >Approve</el-button>
          <el-button
            v-else
            size="small"
            @click="patch(row, { posting_approved: false }, 'Revoked')"
          >Revoke</el-button>
        </template>
      </el-table-column>
    </el-table>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const q = ref('');
const scope = ref('all');
const users = ref([]);
const loading = ref(false);
const accountStatuses = ['active', 'suspended', 'banned'];
const visibilities = ['public', 'unlisted', 'private'];
const moderations = ['clear', 'review', 'restricted'];
const visible = computed(() => {
  if (scope.value === 'all') {
    return users.value;
  }

  return users.value.filter((row) => Boolean(row.email));
});

async function load() {
  loading.value = true;
  try {
    const data = await api('/api/trade-users?q=' + encodeURIComponent(q.value));
    users.value = data.users || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

async function patch(row, body, success) {
  try {
    const data = await api('/api/trade-users/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify(body),
    });
    Object.assign(row, data.user || {});
    ElMessage.success(success || 'Updated');
  } catch (error) {
    ElMessage.error(error.message);
    await load();
  }
}

onMounted(load);
</script>

<style scoped>
.hint { color: #94a3b8; margin: 0 0 1rem; }
.toolbar { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1rem; align-items: center; }
.toolbar .el-input { max-width: 26rem; }
.provider { margin-right: .35rem; }
</style>

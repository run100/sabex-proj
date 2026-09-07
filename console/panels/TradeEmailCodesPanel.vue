<template>
  <section>
    <p class="hint">Verification codes are masked. This list is read-only.</p>
    <el-table :data="codes" stripe v-loading="loading">
      <template #empty>
        <el-empty description="No email codes yet" />
      </template>
      <el-table-column prop="email" label="Email" min-width="220" />
      <el-table-column prop="code" label="Code" width="100" />
      <el-table-column prop="purpose" label="Purpose" width="110" />
      <el-table-column prop="attempts" label="Attempts" width="100" />
      <el-table-column label="Expires" min-width="180">
        <template #default="{ row }">
          {{ row.expires_at || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Used" min-width="180">
        <template #default="{ row }">
          {{ row.used_at || '—' }}
        </template>
      </el-table-column>
    </el-table>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const codes = ref([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const data = await api('/api/trade-email-codes');
    codes.value = data.codes || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.hint { color: #94a3b8; margin: 0 0 1rem; }
</style>

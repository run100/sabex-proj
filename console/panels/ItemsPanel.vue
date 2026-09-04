<template>
  <section>
    <div class="toolbar">
      <el-input v-model="q" placeholder="Search slug or name" clearable @clear="load" @keyup.enter="load" />
      <el-button type="primary" @click="load">Search</el-button>
    </div>
    <el-table :data="items" stripe v-loading="loading">
      <el-table-column prop="name" label="Name" min-width="180" />
      <el-table-column prop="slug" label="Slug" min-width="160" />
      <el-table-column prop="rarity" label="Rarity" width="120" />
      <el-table-column prop="total_exists" label="Exists" width="100" />
      <el-table-column label="Listed" width="100">
        <template #default="{ row }">
          <el-switch v-model="row.is_listed" @change="save(row, 'is_listed')" />
        </template>
      </el-table-column>
      <el-table-column label="Publish" width="110">
        <template #default="{ row }">
          <el-switch v-model="row.is_publish_html" @change="save(row, 'is_publish_html')" />
        </template>
      </el-table-column>
    </el-table>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const q = ref('');
const items = ref([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const data = await api('/api/items?q=' + encodeURIComponent(q.value));
    items.value = data.items || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

async function save(row, field) {
  try {
    await api('/api/items/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify({ [field]: row[field] }),
    });
    ElMessage.success('Saved');
  } catch (error) {
    ElMessage.error(error.message);
    await load();
  }
}

onMounted(load);
</script>

<style scoped>
.toolbar { display: flex; gap: .75rem; margin-bottom: 1rem; max-width: 32rem; }
</style>

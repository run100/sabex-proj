<template>
  <section>
    <el-table :data="sites" stripe v-loading="loading">
      <template #empty>
        <el-empty description="No sites" />
      </template>
      <el-table-column label="名称" min-width="180">
        <template #default="{ row }">
          <span>{{ row.name || '—' }}</span>
          <el-tag v-if="row.is_calculator_only" size="small" class="mode-tag">Calculator</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="slug" label="Slug" min-width="150" />
      <el-table-column prop="domain" label="域名" min-width="160" />
      <el-table-column prop="base_url" label="Base URL" min-width="200" />
      <el-table-column label="本地预览地址" min-width="200">
        <template #default="{ row }">
          <a v-if="row.preview_url" :href="row.preview_url" target="_blank" rel="noreferrer">{{ row.preview_url }}</a>
          <span v-else>—</span>
        </template>
      </el-table-column>
      <el-table-column prop="output_path" label="输出目录" min-width="180" show-overflow-tooltip />
      <el-table-column label="操作" width="90" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="open(row)">编辑</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-drawer v-model="drawer" title="编辑站点" size="32rem">
      <template v-if="form">
        <el-form label-position="top">
          <el-form-item label="名称">
            <el-input v-model="form.name" />
          </el-form-item>
          <el-form-item label="Slug">
            <el-input :model-value="form.slug" disabled />
          </el-form-item>
          <el-form-item label="域名">
            <el-input v-model="form.domain" />
          </el-form-item>
          <el-form-item label="Base URL">
            <el-input v-model="form.base_url" />
          </el-form-item>
          <el-form-item label="本地预览地址">
            <el-input v-model="form.local_preview_base_url" placeholder="例如 http://www.sabex.lab" />
          </el-form-item>
          <el-form-item label="输出目录">
            <el-input v-model="form.output_path" />
          </el-form-item>
          <el-form-item label="站点模式">
            <el-select v-model="form.site_mode">
              <el-option label="完整站 (full)" value="full" />
              <el-option label="仅计算器 (calculator_only)" value="calculator_only" />
            </el-select>
          </el-form-item>
          <el-form-item label="数据站点 Slug">
            <el-input v-model="form.data_site_slug" placeholder="默认同本站 slug" />
          </el-form-item>
          <el-button type="primary" @click="save">保存</el-button>
        </el-form>
      </template>
    </el-drawer>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const sites = ref([]);
const loading = ref(false);
const drawer = ref(false);
const form = ref(null);

async function load() {
  loading.value = true;
  try {
    const data = await api('/api/sites');
    sites.value = data.sites || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

function open(row) {
  form.value = { site_mode: 'full', ...row };
  drawer.value = true;
}

async function save() {
  if (!form.value?.id) {
    return;
  }
  try {
    const data = await api('/api/sites/' + form.value.id, {
      method: 'PATCH',
      body: JSON.stringify({
        name: form.value.name,
        domain: form.value.domain,
        base_url: form.value.base_url,
        output_path: form.value.output_path,
        settings: {
          site_mode: form.value.site_mode,
          data_site_slug: form.value.data_site_slug || null,
          local_preview_base_url: form.value.local_preview_base_url || null,
        },
      }),
    });
    const row = sites.value.find((site) => site.id === form.value.id);
    if (row) {
      Object.assign(row, data.site || {});
    }
    form.value = { ...(data.site || form.value) };
    ElMessage.success('已保存');
  } catch (error) {
    ElMessage.error(error.message);
  }
}

onMounted(load);
</script>

<style scoped>
.mode-tag { margin-left: .4rem; }
</style>

<template>
  <section>
    <el-table :data="articles" stripe v-loading="loading" @row-click="select">
      <el-table-column prop="title" label="Title" min-width="220" />
      <el-table-column prop="slug" label="Slug" min-width="180" />
      <el-table-column prop="status" label="Status" width="110" />
      <el-table-column prop="locale" label="Locale" width="90" />
    </el-table>
    <el-form v-if="current" class="editor" label-position="top">
      <el-form-item label="Title">
        <el-input v-model="current.title" />
      </el-form-item>
      <el-form-item label="Status">
        <el-select v-model="current.status">
          <el-option label="published" value="published" />
          <el-option label="draft" value="draft" />
        </el-select>
      </el-form-item>
      <el-form-item label="Excerpt">
        <el-input v-model="current.excerpt" type="textarea" :rows="3" />
      </el-form-item>
      <el-form-item label="Body HTML">
        <el-input v-model="current.body_html" type="textarea" :rows="10" />
      </el-form-item>
      <el-button type="primary" @click="save">Save article</el-button>
    </el-form>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const articles = ref([]);
const current = ref(null);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const data = await api('/api/news');
    articles.value = data.articles || [];
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

function select(row) {
  current.value = { ...row };
}

async function save() {
  try {
    await api('/api/news/' + current.value.id, {
      method: 'PATCH',
      body: JSON.stringify({
        title: current.value.title,
        excerpt: current.value.excerpt,
        body_html: current.value.body_html,
        status: current.value.status,
      }),
    });
    ElMessage.success('Saved');
    await load();
  } catch (error) {
    ElMessage.error(error.message);
  }
}

onMounted(load);
</script>

<style scoped>
.editor { margin-top: 1.25rem; max-width: 48rem; }
</style>

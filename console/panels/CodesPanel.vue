<template>
  <section>
    <p class="hint">Edits <code>resources/seo/sab/codes.json</code>. Invalid JSON will not save.</p>
    <el-input v-model="text" type="textarea" :rows="22" />
    <el-button class="save" type="primary" @click="save">Save codes.json</el-button>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const text = ref('');

async function load() {
  try {
    const data = await api('/api/codes');
    text.value = JSON.stringify(data.codes, null, 2);
  } catch (error) {
    ElMessage.error(error.message);
  }
}

async function save() {
  try {
    const codes = JSON.parse(text.value);
    await api('/api/codes', { method: 'PUT', body: JSON.stringify({ codes }) });
    ElMessage.success('Saved');
  } catch (error) {
    ElMessage.error(error.message);
  }
}

onMounted(load);
</script>

<style scoped>
.hint { color: #94a3b8; }
.save { margin-top: 1rem; }
code { color: #67e8f9; }
</style>

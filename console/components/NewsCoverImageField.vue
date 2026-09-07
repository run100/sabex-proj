<template>
  <div class="cover-field">
    <div class="cover-field__row">
      <el-input
        :model-value="modelValue || ''"
        placeholder="/uploads/images/sab/news/cover.png"
        @update:model-value="emit('update:modelValue', $event)"
      />
      <el-upload
        :show-file-list="false"
        accept="image/jpeg,image/png,image/webp,image/gif"
        :http-request="upload"
      >
        <el-button :loading="uploading">上传封面</el-button>
      </el-upload>
    </div>
    <img
      v-if="modelValue"
      :src="modelValue"
      alt="新闻封面预览"
      class="cover-field__preview"
    />
  </div>
</template>

<script setup>
import { ElMessage } from 'element-plus';
import { ref } from 'vue';
import { api } from '../api';

const props = defineProps({
  modelValue: { type: String, default: '' },
  seoSiteId: { type: [Number, String], default: null },
});

const emit = defineEmits(['update:modelValue']);
const uploading = ref(false);

async function upload({ file }) {
  uploading.value = true;
  try {
    const form = new FormData();
    form.append('file', file);
    if (props.seoSiteId) {
      form.append('seo_site_id', String(props.seoSiteId));
    }
    const data = await api('/api/news/images', { method: 'POST', body: form });
    emit('update:modelValue', data.url || '');
    ElMessage.success('封面图已上传');
  } catch (error) {
    ElMessage.error(error.message || '封面图上传失败');
  } finally {
    uploading.value = false;
  }
}
</script>

<style scoped>
.cover-field { width: 100%; }
.cover-field__row { display: flex; gap: .6rem; align-items: center; }
.cover-field__row .el-input { flex: 1; }
.cover-field__preview {
  margin-top: .75rem;
  width: 260px;
  max-height: 140px;
  object-fit: cover;
  border: 1px solid rgba(148, 163, 184, 0.2);
  background: #111827;
}
</style>

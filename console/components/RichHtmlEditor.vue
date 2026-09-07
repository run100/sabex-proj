<template>
  <div class="rich-html">
    <el-button-group class="rich-html__mode">
      <el-button size="small" :type="mode === 'visual' ? 'primary' : 'default'" @click="setMode('visual')">可视化</el-button>
      <el-button size="small" :type="mode === 'source' ? 'primary' : 'default'" @click="setMode('source')">源码</el-button>
    </el-button-group>
    <el-input
      v-show="mode === 'source'"
      :model-value="modelValue || ''"
      type="textarea"
      :rows="18"
      spellcheck="false"
      class="rich-html__source"
      @update:model-value="emit('update:modelValue', $event)"
    />
    <div v-show="mode === 'visual'" class="rich-html__visual">
      <Editor
        :model-value="modelValue || ''"
        license-key="gpl"
        :init="init"
        @update:modelValue="emit('update:modelValue', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import Editor from '@tinymce/tinymce-vue';
import { ElMessage } from 'element-plus';
import { computed, ref } from 'vue';
import 'tinymce/tinymce';
import 'tinymce/icons/default/icons';
import 'tinymce/models/dom/model';
import 'tinymce/plugins/autoresize';
import 'tinymce/plugins/code';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';
import 'tinymce/skins/content/default/content.min.css';
import 'tinymce/skins/ui/oxide/skin.min.css';
import 'tinymce/themes/silver/theme';
import { api } from '../api';

const props = defineProps({
  modelValue: { type: String, default: '' },
  seoSiteId: { type: [Number, String], default: null },
});

const emit = defineEmits(['update:modelValue']);
const mode = ref('visual');

function setMode(next) {
  mode.value = next;
}

const init = computed(() => ({
  height: 460,
  min_height: 360,
  menubar: false,
  branding: false,
  promotion: false,
  skin: false,
  content_css: false,
  plugins: 'link image lists table code autoresize',
  toolbar: 'undo redo | blocks | bold italic | bullist numlist blockquote | link image table | code',
  block_formats: '段落=p;标题 2=h2;标题 3=h3;代码=pre',
  convert_urls: false,
  automatic_uploads: true,
  file_picker_types: 'image',
  image_title: true,
  image_description: true,
  image_dimensions: true,
  paste_data_images: false,
  paste_block_drop: true,
  images_file_types: 'jpeg,jpg,png,webp,gif',
  images_reuse_filename: false,
  images_upload_credentials: true,
  content_style: [
    'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 14px; line-height: 1.7; color: #1f2937; }',
    'img { max-width: 100%; height: auto; }',
    'table { border-collapse: collapse; width: 100%; }',
    'td, th { border: 1px solid #d9d9d9; padding: 6px 8px; }',
    'blockquote { border-left: 3px solid #d9d9d9; margin-left: 0; padding-left: 12px; color: #4b5563; }',
  ].join('\n'),
  images_upload_handler: async (blobInfo) => {
    const form = new FormData();
    form.append('file', blobInfo.blob(), blobInfo.filename());
    if (props.seoSiteId) {
      form.append('seo_site_id', String(props.seoSiteId));
    }
    const data = await api('/api/news/images', { method: 'POST', body: form });
    if (!data.url) {
      ElMessage.error('图片上传失败');
      throw new Error('图片上传失败');
    }
    return data.url;
  },
  setup: (editor) => {
    editor.on('UploadFailure', () => {
      ElMessage.error('图片上传失败');
    });
  },
}));
</script>

<style scoped>
.rich-html { width: 100%; }
.rich-html__mode { margin-bottom: .65rem; }
.rich-html__source :deep(textarea) {
  font-family: Menlo, Consolas, "Liberation Mono", monospace;
  font-size: 13px;
  line-height: 1.6;
  min-height: 28rem;
}
</style>

<template>
  <section>
    <div class="toolbar">
      <el-select v-model="seoSiteId" placeholder="站点" clearable style="width: 12rem" @change="search">
        <el-option v-for="site in sites" :key="site.id" :label="site.name || site.slug" :value="site.id" />
      </el-select>
      <el-select v-model="type" placeholder="类型" clearable style="width: 8rem" @change="search">
        <el-option label="新闻" :value="200" />
        <el-option label="单页" :value="100" />
      </el-select>
      <el-select v-model="locale" placeholder="Locale" clearable style="width: 7rem" @change="search">
        <el-option v-for="item in locales" :key="item" :label="item" :value="item" />
      </el-select>
      <el-select v-model="status" placeholder="状态" clearable style="width: 8rem" @change="search">
        <el-option label="published" value="published" />
        <el-option label="draft" value="draft" />
      </el-select>
      <el-input v-model="title" placeholder="标题 / slug" clearable @clear="search" @keyup.enter="search" />
      <el-button type="primary" @click="search">查询</el-button>
      <strong class="total">新闻总数：{{ total }}</strong>
      <el-button type="primary" @click="create">新建新闻</el-button>
    </div>
    <el-table :data="articles" stripe v-loading="loading">
      <template #empty>
        <el-empty description="No news" />
      </template>
      <el-table-column label="封面" width="96">
        <template #default="{ row }">
          <img
            v-if="row.cover_image_url"
            :src="row.cover_image_url"
            :alt="row.title || row.slug"
            class="cover-thumb"
          />
          <span v-else>—</span>
        </template>
      </el-table-column>
      <el-table-column label="标题" min-width="220">
        <template #default="{ row }">
          <el-button link type="primary" @click="open(row)">{{ row.title || row.slug }}</el-button>
        </template>
      </el-table-column>
      <el-table-column prop="slug" label="Slug" min-width="160" />
      <el-table-column label="类型" width="90">
        <template #default="{ row }">
          <el-tag size="small" :class="row.type === 100 ? 'type-static' : 'type-news'">
            {{ row.type === 100 ? '单页' : '新闻' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="站点" min-width="140">
        <template #default="{ row }">{{ row.site?.name || '—' }}</template>
      </el-table-column>
      <el-table-column prop="locale" label="Locale" width="90" />
      <el-table-column label="状态" width="110">
        <template #default="{ row }">
          <el-tag size="small" :type="row.status === 'published' ? 'success' : 'info'">{{ row.status }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="sort_order" label="排序值" width="80" />
      <el-table-column label="系统日志" width="90">
        <template #default="{ row }">
          <el-tag v-if="row.is_system_log" size="small" type="info">Y</el-tag>
          <span v-else>—</span>
        </template>
      </el-table-column>
      <el-table-column label="发布时间" min-width="170">
        <template #default="{ row }">{{ formatTime(row.published_at) }}</template>
      </el-table-column>
      <el-table-column label="操作" width="200" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="open(row)">编辑</el-button>
          <a v-if="row.preview_path" :href="row.preview_path" target="_blank" rel="noreferrer" class="link">本地预览</a>
          <a v-if="row.live_path" :href="row.live_path" target="_blank" rel="noreferrer" class="link">线上查看</a>
        </template>
      </el-table-column>
    </el-table>
    <div v-if="lastPage > 1" class="pager">
      <el-pagination
        background
        layout="prev, pager, next"
        :current-page="page"
        :page-size="100"
        :total="total"
        @current-change="goPage"
      />
    </div>
    <el-drawer
      v-model="drawer"
      :title="form?.id ? '编辑新闻' : '新建新闻'"
      size="90vw"
      direction="rtl"
      class="news-drawer"
      destroy-on-close
    >
      <el-form v-if="form" label-position="top">
        <div class="form-grid">
          <el-form-item label="类型" required>
            <el-select v-model="form.type" style="width: 10rem">
              <el-option label="新闻" :value="200" />
              <el-option label="单页" :value="100" />
            </el-select>
          </el-form-item>
          <el-form-item label="站点" required>
            <el-select v-model="form.seo_site_id" style="width: 14rem">
              <el-option v-for="site in sites" :key="site.id" :label="site.name || site.slug" :value="site.id" />
            </el-select>
          </el-form-item>
          <el-form-item label="Locale" required>
            <el-select v-model="form.locale" style="width: 8rem">
              <el-option v-for="item in locales" :key="item" :label="item" :value="item" />
            </el-select>
          </el-form-item>
          <el-form-item label="状态" required>
            <el-select v-model="form.status" style="width: 10rem">
              <el-option label="draft" value="draft" />
              <el-option label="published" value="published" />
            </el-select>
          </el-form-item>
          <el-form-item label="排序值">
            <el-input-number v-model="form.sort_order" :min="0" :precision="0" />
          </el-form-item>
          <el-form-item label="系统日志">
            <el-checkbox v-model="form.is_system_log">系统维护文章</el-checkbox>
          </el-form-item>
          <el-form-item label="发布时间">
            <el-date-picker
              v-model="form.published_at"
              type="datetime"
              format="YYYY-MM-DD HH:mm:ss"
              value-format="YYYY-MM-DD HH:mm:ss"
              placeholder="不填写时默认当前时间 -8 小时"
            />
          </el-form-item>
        </div>
        <el-form-item label="标题" required>
          <el-input v-model="form.title" />
        </el-form-item>
        <el-form-item label="Meta Description">
          <el-input v-model="form.meta_description" />
        </el-form-item>
        <el-form-item label="摘要">
          <el-input v-model="form.excerpt" type="textarea" :rows="2" />
        </el-form-item>
        <el-form-item label="封面图">
          <NewsCoverImageField v-model="form.cover_image_url" :seo-site-id="form.seo_site_id" />
        </el-form-item>
        <el-form-item label="正文 HTML">
          <RichHtmlEditor v-model="form.body_html" :seo-site-id="form.seo_site_id" />
        </el-form-item>
        <el-button type="primary" :loading="saving" @click="save">保存</el-button>
      </el-form>
    </el-drawer>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import NewsCoverImageField from '../components/NewsCoverImageField.vue';
import RichHtmlEditor from '../components/RichHtmlEditor.vue';
import { api } from '../api';

const articles = ref([]);
const sites = ref([]);
const locales = ref(['en', 'pt', 'es', 'de', 'ru', 'fr', 'tr', 'pl']);
const loading = ref(false);
const saving = ref(false);
const drawer = ref(false);
const form = ref(null);
const seoSiteId = ref(null);
const type = ref(null);
const locale = ref('');
const status = ref('');
const title = ref('');
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

async function loadSites() {
  const data = await api('/api/sites');
  sites.value = data.sites || [];
  if (!seoSiteId.value && sites.value.length) {
    const preferred = sites.value.find((site) => site.slug === 'sab-exist-count') || sites.value[0];
    seoSiteId.value = preferred.id;
  }
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      page: String(page.value),
      limit: '100',
    });
    if (seoSiteId.value) {
      params.set('seo_site_id', String(seoSiteId.value));
    }
    if (type.value) {
      params.set('type', String(type.value));
    }
    if (locale.value) {
      params.set('locale', locale.value);
    }
    if (status.value) {
      params.set('status', status.value);
    }
    if (title.value) {
      params.set('title', title.value);
    }
    const data = await api('/api/news?' + params.toString());
    articles.value = data.articles || [];
    total.value = data.total || 0;
    lastPage.value = data.last_page || 1;
    page.value = data.page || 1;
    if (Array.isArray(data.locales) && data.locales.length) {
      locales.value = data.locales;
    }
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

function search() {
  page.value = 1;
  load();
}

function goPage(next) {
  page.value = next;
  load();
}

function create() {
  form.value = {
    id: null,
    seo_site_id: seoSiteId.value || sites.value[0]?.id || null,
    type: 200,
    locale: 'en',
    status: 'draft',
    sort_order: 10,
    is_system_log: false,
    published_at: '',
    title: '',
    meta_description: '',
    excerpt: '',
    cover_image_url: '',
    body_html: '',
  };
  drawer.value = true;
}

function open(row) {
  form.value = {
    id: row.id,
    seo_site_id: row.seo_site_id,
    type: row.type,
    locale: row.locale || 'en',
    status: row.status || 'draft',
    sort_order: row.sort_order ?? 10,
    is_system_log: Boolean(row.is_system_log),
    published_at: toPicker(row.published_at),
    title: row.title || '',
    meta_description: row.meta_description || '',
    excerpt: row.excerpt || '',
    cover_image_url: row.cover_image_url || '',
    body_html: row.body_html || '',
    slug: row.slug || '',
  };
  drawer.value = true;
}

async function save() {
  if (!form.value?.title || !form.value.seo_site_id) {
    ElMessage.error('请填写标题和站点');
    return;
  }
  saving.value = true;
  try {
    const payload = {
      seo_site_id: form.value.seo_site_id,
      type: form.value.type,
      locale: form.value.locale,
      title: form.value.title,
      excerpt: form.value.excerpt || '',
      cover_image_url: form.value.cover_image_url || '',
      body_html: form.value.body_html || '',
      status: form.value.status,
      published_at: form.value.published_at || null,
      meta_description: form.value.meta_description || '',
      sort_order: form.value.sort_order ?? 10,
      is_system_log: Boolean(form.value.is_system_log),
    };
    if (form.value.id) {
      payload.slug = form.value.slug;
      await api('/api/news/' + form.value.id, {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });
    } else {
      await api('/api/news', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
    }
    ElMessage.success('新闻已保存');
    drawer.value = false;
    await load();
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    saving.value = false;
  }
}

function toPicker(value) {
  if (!value) {
    return '';
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  const pad = (n) => String(n).padStart(2, '0');
  return [
    date.getFullYear(),
    pad(date.getMonth() + 1),
    pad(date.getDate()),
  ].join('-') + ' ' + [pad(date.getHours()), pad(date.getMinutes()), pad(date.getSeconds())].join(':');
}

function formatTime(value) {
  return toPicker(value) || '—';
}

onMounted(async () => {
  try {
    await loadSites();
  } catch (error) {
    ElMessage.error(error.message);
  }
  await load();
});
</script>

<style scoped>
.toolbar { display: flex; flex-wrap: wrap; gap: .6rem; margin-bottom: 1rem; align-items: center; }
.toolbar .el-input { max-width: 16rem; }
.total { color: #e2e8f0; font-size: .85rem; }
.pager { margin-top: 1rem; display: flex; justify-content: flex-end; }
.link { margin-left: .4rem; color: #67e8f9; font-size: .8rem; text-decoration: none; }
.cover-thumb {
  width: 72px;
  height: 40px;
  object-fit: cover;
  border: 1px solid rgba(148, 163, 184, 0.2);
  background: #111827;
}
.type-news { --el-tag-bg-color: rgba(8, 145, 178, 0.18); --el-tag-border-color: #67e8f9; --el-tag-text-color: #ecfeff; }
.type-static { --el-tag-bg-color: rgba(148, 163, 184, 0.16); --el-tag-border-color: #94a3b8; --el-tag-text-color: #e2e8f0; }
.news-drawer :deep(.el-drawer__body) { overflow: auto; }
.form-grid { display: flex; flex-wrap: wrap; gap: 0 .75rem; }
.form-grid .el-form-item { min-width: 10rem; }
</style>

<template>
  <section>
    <div class="tags">
      <button
        v-for="tag in tags"
        :key="tag.key || 'all'"
        type="button"
        class="rarity-tag"
        :class="['rarity-tag--' + (tag.key || 'all').replaceAll(' ', '-'), { 'is-on': rarity === tag.key }]"
        @click="selectRarity(tag.key)"
      >
        {{ tag.label }} ({{ tag.count }})
      </button>
    </div>
    <div class="toolbar">
      <el-input v-model="q" placeholder="商品 / slug" clearable @clear="search" @keyup.enter="search" />
      <el-select v-model="wiki" placeholder="Wiki" clearable style="width: 8rem" @change="search">
        <el-option label="找到" value="found" />
        <el-option label="跳转" value="redirected" />
        <el-option label="缺失" value="missing" />
        <el-option label="未检查" value="unknown" />
      </el-select>
      <el-select v-model="listed" placeholder="列表" clearable style="width: 7rem" @change="search">
        <el-option label="Y" value="1" />
        <el-option label="N" value="0" />
      </el-select>
      <el-select v-model="publish" placeholder="发布" clearable style="width: 7rem" @change="search">
        <el-option label="Y" value="1" />
        <el-option label="N" value="0" />
      </el-select>
      <el-select v-model="hasExist" placeholder="有 Exist" clearable style="width: 8rem" @change="search">
        <el-option label="是" value="1" />
        <el-option label="否" value="0" />
      </el-select>
      <el-select v-model="hasValue" placeholder="有 Value" clearable style="width: 8rem" @change="search">
        <el-option label="是" value="1" />
        <el-option label="否" value="0" />
      </el-select>
      <el-button type="primary" @click="search">查询</el-button>
      <strong class="total">商品总数：{{ total }}</strong>
      <el-button @click="selectUnpublished">勾选未发布</el-button>
      <el-button @click="selectPage">全选当前页</el-button>
      <el-button type="primary" :disabled="!selected.length" @click="bulkPublish">批量设为可发布</el-button>
      <el-button :disabled="!selected.length" @click="bulkDelist">批量下架</el-button>
    </div>
    <el-table ref="table" :data="items" stripe v-loading="loading" @selection-change="onSelect">
      <template #empty>
        <el-empty description="No items" />
      </template>
      <el-table-column type="selection" width="42" />
      <el-table-column label="商品" min-width="180">
        <template #default="{ row }">
          <el-button link type="primary" @click="open(row)">{{ row.name || row.slug }}</el-button>
        </template>
      </el-table-column>
      <el-table-column label="Exist Count" width="120">
        <template #default="{ row }">{{ formatNumber(row.total_exists) }}</template>
      </el-table-column>
      <el-table-column label="Base Income" width="130">
        <template #default="{ row }">
          {{ row.base_income != null && row.base_income !== '' ? formatNumber(row.base_income) + '/s' : '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Base Price (R$)" width="130">
        <template #default="{ row }">{{ formatNumber(row.base_price ?? row.base_cost) }}</template>
      </el-table-column>
      <el-table-column label="Value" width="110">
        <template #default="{ row }">{{ formatNumber(row.primary_value ?? row.value) }}</template>
      </el-table-column>
      <el-table-column label="Wiki" min-width="120">
        <template #default="{ row }">
          <a v-if="row.wiki_page_url" :href="row.wiki_page_url" target="_blank" rel="noreferrer">
            <el-tag size="small" :type="wikiType(row.wiki_page_status)">{{ wikiLabel(row.wiki_page_status) }}</el-tag>
          </a>
          <el-tag v-else size="small" :type="wikiType(row.wiki_page_status)">{{ wikiLabel(row.wiki_page_status) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="列表" width="80">
        <template #default="{ row }">
          <el-switch
            :model-value="Boolean(row.is_listed)"
            active-text="Y"
            inactive-text="N"
            inline-prompt
            @change="(value) => patch(row, { is_listed: value })"
          />
        </template>
      </el-table-column>
      <el-table-column label="发布" width="80">
        <template #default="{ row }">
          <el-switch
            :model-value="Boolean(row.is_publish_html)"
            active-text="Y"
            inactive-text="N"
            inline-prompt
            @change="(value) => patch(row, { is_publish_html: value })"
          />
        </template>
      </el-table-column>
      <el-table-column prop="rarity" label="稀有度" width="120" />
      <el-table-column prop="variants_count" label="规格数" width="80" />
      <el-table-column prop="sources_count" label="来源数" width="80" />
      <el-table-column prop="slug" label="Slug" min-width="150" />
      <el-table-column prop="sort_order" label="排序值" width="80" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button link type="primary" @click="open(row)">编辑</el-button>
          <a :href="row.preview_path" target="_blank" rel="noreferrer" class="link">本地预览</a>
          <a :href="row.live_path" target="_blank" rel="noreferrer" class="link">线上查看</a>
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
      v-model="dialog"
      :title="detail ? ('商品：' + (detail.name || detail.slug)) : '商品详情'"
      size="90vw"
      direction="rtl"
      class="item-drawer"
      destroy-on-close
    >
      <div v-loading="detailLoading" class="item-layout">
        <aside class="item-side">
          <img v-if="detailImage" :src="detailImage" :alt="detail?.name || ''" class="item-cover" />
          <dl class="item-meta">
            <div><dt>Slug</dt><dd>{{ detail?.slug || '—' }}</dd></div>
            <div><dt>游戏</dt><dd>{{ detail?.game?.name || '—' }}</dd></div>
            <div><dt>站点</dt><dd>{{ detail?.game?.site?.name || '—' }}</dd></div>
            <div><dt>稀有度</dt><dd>{{ detail?.rarity || '—' }}</dd></div>
            <div><dt>发布 HTML</dt><dd>{{ detail?.is_publish_html ? 'Y' : 'N' }}</dd></div>
            <div><dt>列表显示</dt><dd>{{ detail?.is_listed === false ? 'N' : 'Y' }}</dd></div>
            <div><dt>Total Exists</dt><dd>{{ formatNumber(detail?.total_exists) }}</dd></div>
            <div>
              <dt>Rarest Mutation</dt>
              <dd>{{ rarestLabel(detail?.rarest_mutation_name, detail?.rarest_mutation_count) }}</dd>
            </div>
            <div>
              <dt>Rarest Trait</dt>
              <dd>{{ rarestLabel(detail?.rarest_trait_name, detail?.rarest_trait_count) }}</dd>
            </div>
            <div>
              <dt>主来源页</dt>
              <dd>
                <a v-if="detail?.source_page_url" :href="detail.source_page_url" target="_blank" rel="noreferrer">打开来源</a>
                <span v-else>—</span>
              </dd>
            </div>
          </dl>
        </aside>
        <div class="item-main">
          <el-tabs v-model="activeTab">
            <el-tab-pane label="基本信息" name="basic">
              <el-form label-position="top">
                <el-form-item label="名称">
                  <el-input v-model="basic.name" />
                </el-form-item>
                <el-form-item label="Slug">
                  <el-input v-model="basic.slug" />
                </el-form-item>
                <el-form-item label="稀有度">
                  <el-input v-model="basic.rarity" />
                </el-form-item>
                <el-form-item label="排序值">
                  <el-input-number v-model="basic.sort_order" :min="0" :precision="0" />
                </el-form-item>
                <el-form-item label="生成独立 HTML">
                  <el-checkbox v-model="basic.is_publish_html">发布商品详情页</el-checkbox>
                </el-form-item>
                <el-form-item label="前台列表">
                  <el-checkbox v-model="basic.is_listed">在首页/列表页展示</el-checkbox>
                </el-form-item>
                <div class="form-grid">
                  <el-form-item label="Total Exists">
                    <el-input-number v-model="basic.total_exists" :min="0" :precision="0" />
                  </el-form-item>
                  <el-form-item label="Rarest Mutation">
                    <el-input v-model="basic.rarest_mutation_name" />
                  </el-form-item>
                  <el-form-item label="Mutation Count">
                    <el-input-number v-model="basic.rarest_mutation_count" :min="0" :precision="0" />
                  </el-form-item>
                  <el-form-item label="Rarest Trait">
                    <el-input v-model="basic.rarest_trait_name" />
                  </el-form-item>
                  <el-form-item label="Trait Count">
                    <el-input-number v-model="basic.rarest_trait_count" :min="0" :precision="0" />
                  </el-form-item>
                  <el-form-item label="Avg Rebirth">
                    <el-input-number v-model="basic.avg_rebirth" :min="0" :precision="2" />
                  </el-form-item>
                  <el-form-item label="Avg Coins">
                    <el-input v-model="basic.avg_coins_raw" />
                  </el-form-item>
                </div>
                <el-form-item label="描述">
                  <el-input v-model="basic.description" type="textarea" :rows="4" />
                </el-form-item>
                <el-form-item label="查询简介">
                  <el-input v-model="basic.summary" type="textarea" :rows="5" />
                </el-form-item>
                <el-form-item label="主来源页">
                  <el-input v-model="basic.source_page_url" />
                </el-form-item>
                <div class="form-grid">
                  <el-form-item label="Wiki 状态">
                    <el-select v-model="basic.wiki_page_status" clearable>
                      <el-option label="找到" value="found" />
                      <el-option label="跳转" value="redirected" />
                      <el-option label="缺失" value="missing" />
                      <el-option label="未检查" value="unknown" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Wiki 标题">
                    <el-input v-model="basic.wiki_page_title" />
                  </el-form-item>
                  <el-form-item label="Wiki Page ID">
                    <el-input-number v-model="basic.wiki_page_id" :min="0" :precision="0" />
                  </el-form-item>
                </div>
                <el-form-item label="Wiki 来源页">
                  <el-input v-model="basic.wiki_page_url" />
                </el-form-item>
                <el-form-item label="Wiki 检查时间">
                  <el-input :model-value="basic.wiki_checked_at || ''" disabled />
                </el-form-item>
                <el-form-item label="购买链接">
                  <el-input v-model="basic.purchase_url" placeholder="商品外部购买详情页 URL" />
                </el-form-item>
                <el-form-item label="图片 URL">
                  <el-input v-model="basic.image_url" />
                </el-form-item>
                <el-form-item label="本地图片">
                  <el-input v-model="basic.local_image_url" />
                </el-form-item>
                <el-form-item label="属性 JSON">
                  <el-input v-model="attributesText" type="textarea" :rows="6" />
                </el-form-item>
                <el-button type="primary" :loading="savingBasic" @click="saveBasic">保存基本信息</el-button>
              </el-form>
            </el-tab-pane>
            <el-tab-pane label="规格 / 变体" name="variants">
              <div v-for="(row, index) in variants" :key="row._key || row.id || index" class="subcard">
                <div class="subcard-head">
                  <strong>规格 {{ index + 1 }}</strong>
                  <el-button link type="danger" @click="variants.splice(index, 1)">移除</el-button>
                </div>
                <div class="form-grid">
                  <el-form-item label="Key"><el-input v-model="row.variant_key" /></el-form-item>
                  <el-form-item label="名称"><el-input v-model="row.variant_name" /></el-form-item>
                  <el-form-item label="类型">
                    <el-select v-model="row.variant_type">
                      <el-option label="base" value="base" />
                      <el-option label="mutation" value="mutation" />
                      <el-option label="trait" value="trait" />
                      <el-option label="value_option" value="value_option" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Mutation"><el-input v-model="row.mutation" /></el-form-item>
                  <el-form-item label="Mutation Name"><el-input v-model="row.mutation_name" /></el-form-item>
                  <el-form-item label="Trait"><el-input v-model="row.trait" /></el-form-item>
                  <el-form-item label="Trait Name"><el-input v-model="row.trait_name" /></el-form-item>
                  <el-form-item label="占比"><el-input-number v-model="row.exist_percentage" :min="0" :max="100" :precision="4" /></el-form-item>
                  <el-form-item label="排序"><el-input-number v-model="row.sort_order" :min="0" :precision="0" /></el-form-item>
                </div>
                <el-form-item label="属性 JSON">
                  <el-input v-model="row.attributes_text" type="textarea" :rows="3" />
                </el-form-item>
              </div>
              <div class="tab-actions">
                <el-button @click="addVariant">新增规格</el-button>
                <el-button type="primary" :loading="savingVariants" @click="saveVariants">保存规格</el-button>
              </div>
            </el-tab-pane>
            <el-tab-pane label="多语言内容" name="translations">
              <div v-for="(row, index) in translations" :key="row._key || row.id || index" class="subcard">
                <div class="subcard-head">
                  <strong>{{ row.locale || '语言' }}</strong>
                  <el-button link type="danger" @click="translations.splice(index, 1)">移除</el-button>
                </div>
                <div class="form-grid">
                  <el-form-item label="Locale">
                    <el-select v-model="row.locale">
                      <el-option v-for="locale in locales" :key="locale" :label="locale" :value="locale" />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="名称"><el-input v-model="row.name" /></el-form-item>
                </div>
                <el-form-item label="描述"><el-input v-model="row.description" type="textarea" :rows="3" /></el-form-item>
                <el-form-item label="SEO Title"><el-input v-model="row.seo_title" /></el-form-item>
                <el-form-item label="SEO Description"><el-input v-model="row.seo_description" /></el-form-item>
              </div>
              <div class="tab-actions">
                <el-button @click="addTranslation">新增语言</el-button>
                <el-button type="primary" :loading="savingTranslations" @click="saveTranslations">保存多语言内容</el-button>
              </div>
            </el-tab-pane>
            <el-tab-pane label="别名映射" name="aliases">
              <div v-for="(row, index) in aliases" :key="row._key || row.id || index" class="alias-row">
                <el-form-item label="来源">
                  <el-select v-model="row.seo_value_source_id" filterable>
                    <el-option v-for="source in sources" :key="source.id" :label="source.name || source.slug" :value="source.id" />
                  </el-select>
                </el-form-item>
                <el-form-item label="别名">
                  <el-input v-model="row.alias_name" />
                </el-form-item>
                <el-button type="danger" @click="aliases.splice(index, 1)">移除</el-button>
              </div>
              <div class="tab-actions">
                <el-button @click="addAlias">新增别名</el-button>
                <el-button type="primary" :loading="savingAliases" @click="saveAliases">保存别名</el-button>
              </div>
            </el-tab-pane>
            <el-tab-pane label="当前数据" name="current">
              <el-table :data="currentValues" stripe size="small">
                <el-table-column label="规格" min-width="120">
                  <template #default="{ row }">{{ row.variant?.variant_name || '—' }}</template>
                </el-table-column>
                <el-table-column label="来源" min-width="120">
                  <template #default="{ row }">{{ row.source?.name || '—' }}</template>
                </el-table-column>
                <el-table-column label="证据 URL" min-width="180" show-overflow-tooltip>
                  <template #default="{ row }">
                    <a v-if="row.evidence_url" :href="row.evidence_url" target="_blank" rel="noreferrer">{{ row.evidence_url }}</a>
                    <span v-else>—</span>
                  </template>
                </el-table-column>
                <el-table-column prop="exist_count_normalized" label="Exist Count" width="120" />
                <el-table-column prop="value_normalized" label="Value" width="100" />
                <el-table-column prop="currency" label="Currency" width="90" />
                <el-table-column prop="demand" label="Demand" width="90" />
                <el-table-column prop="confidence" label="Confidence" width="110" />
                <el-table-column label="采集时间" min-width="160">
                  <template #default="{ row }">{{ row.collected_at || '—' }}</template>
                </el-table-column>
              </el-table>
            </el-tab-pane>
            <el-tab-pane label="历史观测" name="observations">
              <el-table :data="observations" stripe size="small" v-loading="obsLoading">
                <el-table-column label="规格" min-width="120">
                  <template #default="{ row }">{{ row.variant?.variant_name || '—' }}</template>
                </el-table-column>
                <el-table-column label="来源" min-width="120">
                  <template #default="{ row }">{{ row.source?.name || '—' }}</template>
                </el-table-column>
                <el-table-column label="证据 URL" min-width="180" show-overflow-tooltip>
                  <template #default="{ row }">
                    <a v-if="row.evidence_url" :href="row.evidence_url" target="_blank" rel="noreferrer">{{ row.evidence_url }}</a>
                    <span v-else>—</span>
                  </template>
                </el-table-column>
                <el-table-column prop="exist_count_normalized" label="Exist Count" width="120" />
                <el-table-column prop="value_normalized" label="Value" width="100" />
                <el-table-column prop="demand" label="Demand" width="90" />
                <el-table-column label="观测时间" min-width="160">
                  <template #default="{ row }">{{ row.observed_at || '—' }}</template>
                </el-table-column>
                <el-table-column label="操作" width="90">
                  <template #default="{ row }">
                    <el-button link type="primary" @click="payload = row.source_payload_json">原始数据</el-button>
                  </template>
                </el-table-column>
              </el-table>
              <div v-if="obsLastPage > 1" class="pager">
                <el-pagination
                  background
                  layout="prev, pager, next"
                  :current-page="obsPage"
                  :page-size="10"
                  :total="obsTotal"
                  @current-change="loadObservations"
                />
              </div>
            </el-tab-pane>
          </el-tabs>
        </div>
      </div>
    </el-drawer>
    <el-dialog v-model="payloadOpen" title="来源原始数据" width="56rem">
      <pre class="payload">{{ payloadText }}</pre>
    </el-dialog>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { ElMessage } from 'element-plus';
import { api } from '../api';

const q = ref('');
const rarity = ref('');
const listed = ref('');
const publish = ref('');
const wiki = ref('');
const hasExist = ref('');
const hasValue = ref('');
const items = ref([]);
const tags = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const loading = ref(false);
const selected = ref([]);
const table = ref(null);
const dialog = ref(false);
const detail = ref(null);
const detailLoading = ref(false);
const activeTab = ref('basic');
const basic = ref({});
const attributesText = ref('{}');
const variants = ref([]);
const translations = ref([]);
const aliases = ref([]);
const sources = ref([]);
const locales = ref([]);
const observations = ref([]);
const obsPage = ref(1);
const obsLastPage = ref(1);
const obsTotal = ref(0);
const obsLoading = ref(false);
const savingBasic = ref(false);
const savingVariants = ref(false);
const savingTranslations = ref(false);
const savingAliases = ref(false);
const payload = ref(null);

const detailImage = computed(() => detail.value?.local_image_url || detail.value?.image_url || '');
const currentValues = computed(() => (detail.value?.variants || []).flatMap((variant) =>
  (variant.current_values || variant.currentValues || []).map((row) => ({ ...row, variant }))
));
const payloadOpen = computed({
  get: () => payload.value != null,
  set: (open) => {
    if (!open) payload.value = null;
  },
});
const payloadText = computed(() => JSON.stringify(payload.value ?? {}, null, 2));

function formatNumber(value) {
  if (value === null || value === undefined || value === '') {
    return '—';
  }
  const number = Number(value);
  if (Number.isNaN(number)) {
    return String(value);
  }

  return new Intl.NumberFormat('en-US', { maximumFractionDigits: 4 }).format(number);
}

function rarestLabel(name, count) {
  if (!name) {
    return '—';
  }

  return count ? `${name} (${count})` : name;
}

function wikiLabel(status) {
  return { found: '找到', redirected: '跳转', missing: '缺失', unknown: '未检查' }[status] || '未检查';
}

function wikiType(status) {
  return { found: 'success', redirected: '', missing: 'danger', unknown: 'info' }[status] || 'info';
}

function selectRarity(key) {
  rarity.value = key || '';
  search();
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (q.value) params.set('q', q.value);
    if (rarity.value) params.set('rarity', rarity.value);
    if (listed.value !== '' && listed.value !== null) params.set('listed', listed.value);
    if (publish.value !== '' && publish.value !== null) params.set('publish', publish.value);
    if (wiki.value) params.set('wiki_page_status', wiki.value);
    if (hasExist.value !== '' && hasExist.value !== null) params.set('has_exist_count', hasExist.value);
    if (hasValue.value !== '' && hasValue.value !== null) params.set('has_value', hasValue.value);
    params.set('page', String(page.value));
    params.set('limit', '100');
    const data = await api('/api/items?' + params.toString());
    items.value = data.items || [];
    tags.value = data.counts?.tags || [];
    page.value = data.page || 1;
    lastPage.value = data.last_page || 1;
    total.value = data.total || 0;
    selected.value = [];
    table.value?.clearSelection();
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
  selected.value = [];
  load();
}

function onSelect(rows) {
  selected.value = rows;
}

function selectUnpublished() {
  items.value.forEach((row) => {
    table.value?.toggleRowSelection(row, !row.is_publish_html);
  });
}

function selectPage() {
  items.value.forEach((row) => {
    table.value?.toggleRowSelection(row, true);
  });
}

async function open(row) {
  dialog.value = true;
  activeTab.value = 'basic';
  await loadDetail(row.id);
}

function applyDetail(item) {
  detail.value = item;
  basic.value = {
    name: item.name || '',
    slug: item.slug || '',
    rarity: item.rarity || '',
    sort_order: item.sort_order ?? 10,
    is_publish_html: Boolean(item.is_publish_html),
    is_listed: item.is_listed !== false,
    total_exists: item.total_exists ?? null,
    rarest_mutation_name: item.rarest_mutation_name || '',
    rarest_mutation_count: item.rarest_mutation_count ?? null,
    rarest_trait_name: item.rarest_trait_name || '',
    rarest_trait_count: item.rarest_trait_count ?? null,
    avg_rebirth: item.avg_rebirth == null ? null : Number(item.avg_rebirth),
    avg_coins_raw: item.avg_coins_raw || '',
    description: item.description || '',
    summary: item.summary || '',
    source_page_url: item.source_page_url || '',
    wiki_page_status: item.wiki_page_status || '',
    wiki_page_title: item.wiki_page_title || '',
    wiki_page_id: item.wiki_page_id ?? null,
    wiki_page_url: item.wiki_page_url || '',
    wiki_checked_at: item.wiki_checked_at || '',
    purchase_url: item.purchase_url || '',
    image_url: item.image_url || '',
    local_image_url: item.local_image_url || '',
  };
  attributesText.value = JSON.stringify(item.attributes_json ?? {}, null, 2);
  variants.value = (item.variants || []).map((row) => ({
    ...row,
    attributes_text: JSON.stringify(row.attributes_json ?? {}, null, 2),
  }));
  translations.value = (item.translations || []).map((row) => ({ ...row }));
  aliases.value = (item.aliases || []).map((row) => ({
    id: row.id,
    seo_value_source_id: row.seo_value_source_id,
    alias_name: row.alias_name,
  }));
  const listRow = items.value.find((row) => row.id === item.id);
  if (listRow) {
    Object.assign(listRow, {
      name: item.name,
      slug: item.slug,
      rarity: item.rarity,
      is_listed: item.is_listed,
      is_publish_html: item.is_publish_html,
      sort_order: item.sort_order,
      total_exists: item.total_exists,
      wiki_page_status: item.wiki_page_status,
      wiki_page_url: item.wiki_page_url,
      variants_count: item.variants_count,
      sources_count: item.sources_count,
      primary_value: item.primary_value,
      preview_path: item.preview_path,
      live_path: item.live_path,
    });
  }
}

async function loadDetail(id) {
  detailLoading.value = true;
  try {
    const [data, sourceData] = await Promise.all([
      api('/api/items/' + id),
      sources.value.length ? Promise.resolve({ sources: sources.value }) : api('/api/value-sources'),
    ]);
    sources.value = sourceData.sources || sources.value;
    locales.value = data.locales || data.item?.locales || [];
    applyDetail(data.item || {});
    obsPage.value = 1;
    await loadObservations(1);
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    detailLoading.value = false;
  }
}

async function loadObservations(next) {
  if (!detail.value?.id) {
    return;
  }
  obsPage.value = next || 1;
  obsLoading.value = true;
  try {
    const data = await api('/api/items/' + detail.value.id + '/observations?page=' + obsPage.value + '&limit=10');
    observations.value = data.items || [];
    obsPage.value = data.page || 1;
    obsLastPage.value = data.last_page || 1;
    obsTotal.value = data.total || 0;
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    obsLoading.value = false;
  }
}

function parseJson(text, label) {
  try {
    return text.trim() ? JSON.parse(text) : {};
  } catch {
    ElMessage.error(label + ' JSON 无法解析');
    return null;
  }
}

async function saveBasic() {
  if (!detail.value?.id) {
    return;
  }
  const attributes = parseJson(attributesText.value, '属性');
  if (attributes === null) {
    return;
  }
  savingBasic.value = true;
  try {
    const data = await api('/api/items/' + detail.value.id, {
      method: 'PATCH',
      body: JSON.stringify({ ...basic.value, attributes_json: attributes }),
    });
    applyDetail(data.item || {});
    ElMessage.success('基本信息已保存');
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    savingBasic.value = false;
  }
}

function addVariant() {
  variants.value.push({
    _key: 'v' + Date.now(),
    variant_key: 'base',
    variant_name: 'Base',
    variant_type: 'base',
    attributes_text: '{}',
  });
}

async function saveVariants() {
  if (!detail.value?.id) {
    return;
  }
  const itemsPayload = [];
  for (const row of variants.value) {
    const attributes = parseJson(row.attributes_text || '{}', '规格属性');
    if (attributes === null) {
      return;
    }
    itemsPayload.push({
      id: row.id || null,
      variant_key: row.variant_key,
      variant_name: row.variant_name,
      variant_type: row.variant_type || 'base',
      mutation: row.mutation || '',
      mutation_name: row.mutation_name || '',
      trait: row.trait || '',
      trait_name: row.trait_name || '',
      exist_percentage: row.exist_percentage ?? null,
      sort_order: row.sort_order ?? 10,
      attributes_json: attributes,
    });
  }
  savingVariants.value = true;
  try {
    const data = await api('/api/items/' + detail.value.id + '/variants', {
      method: 'PUT',
      body: JSON.stringify({ items: itemsPayload }),
    });
    applyDetail(data.item || {});
    ElMessage.success('规格已保存');
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    savingVariants.value = false;
  }
}

function addTranslation() {
  translations.value.push({
    _key: 't' + Date.now(),
    locale: locales.value[0] || 'en',
    name: '',
    description: '',
    seo_title: '',
    seo_description: '',
  });
}

async function saveTranslations() {
  if (!detail.value?.id) {
    return;
  }
  savingTranslations.value = true;
  try {
    const data = await api('/api/items/' + detail.value.id + '/translations', {
      method: 'PUT',
      body: JSON.stringify({
        items: translations.value.map((row) => ({
          locale: row.locale,
          name: row.name || '',
          description: row.description || '',
          seo_title: row.seo_title || '',
          seo_description: row.seo_description || '',
        })),
      }),
    });
    applyDetail(data.item || {});
    ElMessage.success('多语言内容已保存');
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    savingTranslations.value = false;
  }
}

function addAlias() {
  aliases.value.push({
    _key: 'a' + Date.now(),
    seo_value_source_id: sources.value[0]?.id || null,
    alias_name: '',
  });
}

async function saveAliases() {
  if (!detail.value?.id) {
    return;
  }
  savingAliases.value = true;
  try {
    const data = await api('/api/items/' + detail.value.id + '/aliases', {
      method: 'PUT',
      body: JSON.stringify({
        items: aliases.value.map((row) => ({
          id: row.id || null,
          seo_value_source_id: row.seo_value_source_id,
          alias_name: row.alias_name,
        })),
      }),
    });
    applyDetail(data.item || {});
    ElMessage.success('别名已保存');
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    savingAliases.value = false;
  }
}

async function patch(row, body) {
  try {
    const data = await api('/api/items/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify(body),
    });
    Object.assign(row, data.item || {});
    if (detail.value?.id === row.id) {
      applyDetail({ ...detail.value, ...data.item });
    }
    ElMessage.success('Saved');
  } catch (error) {
    ElMessage.error(error.message);
    await load();
  }
}

async function bulkPublish() {
  await bulk({ is_publish_html: true }, '已发布');
}

async function bulkDelist() {
  await bulk({ is_listed: false, is_publish_html: false }, '已下架');
}

async function bulk(body, success) {
  const ids = selected.value.map((row) => row.id);
  if (!ids.length) {
    return;
  }
  try {
    const data = await api('/api/items/bulk', {
      method: 'POST',
      body: JSON.stringify({ ids, ...body }),
    });
    ElMessage.success(success + ' ' + (data.updated ?? ids.length));
    selected.value = [];
    await load();
  } catch (error) {
    ElMessage.error(error.message);
  }
}

onMounted(load);
</script>

<style scoped>
.tags { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; }
.rarity-tag {
  border: 1px solid rgba(148, 163, 184, 0.28);
  background: #111827;
  color: #cbd5e1;
  border-radius: 9999px;
  padding: .2rem .7rem;
  font-size: .75rem;
  cursor: pointer;
}
.rarity-tag.is-on { border-color: #67e8f9; color: #ecfeff; background: rgba(8, 145, 178, 0.2); }
.rarity-tag--og.is-on, .rarity-tag--legendary.is-on { border-color: #eab308; color: #fde68a; }
.rarity-tag--mythic.is-on, .rarity-tag--secret.is-on { border-color: #a78bfa; color: #ddd6fe; }
.rarity-tag--rare.is-on { border-color: #38bdf8; color: #bae6fd; }
.rarity-tag--epic.is-on { border-color: #60a5fa; color: #bfdbfe; }
.rarity-tag--brainrot-god.is-on { border-color: #f472b6; color: #fbcfe8; }
.toolbar { display: flex; flex-wrap: wrap; gap: .6rem; margin-bottom: 1rem; align-items: center; }
.toolbar .el-input { max-width: 16rem; }
.total { color: #e2e8f0; font-size: .85rem; }
.pager { margin-top: 1rem; display: flex; justify-content: flex-end; }
.link { margin-left: .4rem; color: #67e8f9; font-size: .8rem; text-decoration: none; }
.item-drawer :deep(.el-drawer__body) { overflow: auto; }
.item-layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 1rem; min-height: 28rem; }
.item-side { border-right: 1px solid rgba(148, 163, 184, 0.16); padding-right: 1rem; }
.item-cover { width: 100%; border-radius: 8px; border: 1px solid rgba(148, 163, 184, 0.2); }
.item-meta { margin: 1rem 0 0; }
.item-meta > div { margin-bottom: .65rem; }
.item-meta dt { color: #94a3b8; font-size: .75rem; }
.item-meta dd { margin: .15rem 0 0; color: #e2e8f0; }
.form-grid { display: flex; flex-wrap: wrap; gap: 0 .75rem; }
.form-grid .el-form-item { min-width: 10rem; }
.subcard {
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: #111827;
  padding: .75rem 1rem 0;
  margin-bottom: .75rem;
}
.subcard-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
.alias-row { display: flex; gap: .75rem; align-items: flex-end; margin-bottom: .75rem; }
.alias-row .el-form-item { flex: 1; }
.tab-actions { display: flex; gap: .6rem; margin: .5rem 0 1rem; }
.payload { max-height: 32rem; overflow: auto; color: #cbd5e1; }
@media (max-width: 960px) {
  .item-layout { grid-template-columns: 1fr; }
  .item-side { border-right: 0; padding-right: 0; }
}
</style>

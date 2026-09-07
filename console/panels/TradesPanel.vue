<template>
  <section>
    <el-radio-group v-model="status" class="tabs" @change="load">
      <el-radio-button value="">All {{ counts.all ?? 0 }}</el-radio-button>
      <el-radio-button v-for="name in statuses" :key="name" :value="name">
        {{ name }} {{ counts[name] ?? 0 }}
      </el-radio-button>
    </el-radio-group>
    <div class="toolbar">
      <el-input v-model="q" placeholder="Search ID, owner, or email" clearable @clear="search" @keyup.enter="search" />
      <el-button type="primary" @click="search">Query</el-button>
      <el-button @click="reset">Reset</el-button>
    </div>
    <el-table :data="listings" stripe v-loading="loading" @row-click="open">
      <template #empty>
        <el-empty description="No trade listings yet" />
      </template>
      <el-table-column label="trade id" width="320" class-name="id-cell" fixed="left">
        <template #default="{ row }">
          <span class="copy-id">
            <button
              type="button"
              class="poster-link"
              @click.stop="open(row)"
            >{{ row.public_id || '—' }}</button>
            <el-button
              v-if="row.public_id"
              :icon="CopyDocument"
              link
              size="small"
              aria-label="Copy ID"
              @click.stop="copyId(row.public_id)"
            />
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Owner" min-width="260" fixed="left">
        <template #default="{ row }">
          <div
            class="owner-cell"
            :class="{ 'is-link': row.owner_id }"
            @click.stop="openUser(row.owner_id)"
          >
            <img
              class="owner-avatar"
              :src="row.owner_avatar_url || '/static/img/trades-default-avatar.webp'"
              :alt="row.owner_username || 'owner'"
              width="28"
              height="28"
            >
            <div class="owner-meta">
              <span class="poster-link">{{ row.owner_username || '—' }}</span>
              <span class="copy-id">
                <span>{{ row.owner_profile_id || '—' }}</span>
                <el-button
                  v-if="row.owner_profile_id"
                  :icon="CopyDocument"
                  link
                  size="small"
                  aria-label="Copy profile ID"
                  @click.stop="copyId(row.owner_profile_id)"
                />
              </span>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Status" width="200">
        <template #default="{ row }">
          <span @click.stop>
            <el-select
              :model-value="row.status"
              size="small"
              @change="(value) => patchListing(row, { status: value })"
            >
              <el-option v-for="name in statuses" :key="name" :label="name" :value="name" />
            </el-select>
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Result" width="110">
        <template #default="{ row }">
          <span @click.stop>
            <el-select
              :model-value="row.result_snapshot || 'na'"
              size="small"
              @change="(value) => patchListing(row, { result_snapshot: value })"
            >
              <el-option v-for="name in results" :key="name" :label="name" :value="name" />
            </el-select>
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Email" min-width="180">
        <template #default="{ row }">
          <button
            v-if="row.owner_id"
            type="button"
            class="poster-link"
            @click.stop="openUser(row.owner_id)"
          >{{ row.owner_email || '—' }}</button>
          <span v-else>{{ row.owner_email || '—' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="Counterparty" min-width="140">
        <template #default="{ row }">
          <button
            v-if="row.counterparty_id"
            type="button"
            class="poster-link"
            @click.stop="openUser(row.counterparty_id)"
          >{{ row.counterparty_username || '—' }}</button>
          <span v-else>{{ row.counterparty_username || '—' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="Offer / Look" width="130">
        <template #default="{ row }">
          {{ row.offering_items_count }}/{{ row.looking_items_count }}
        </template>
      </el-table-column>
      <el-table-column prop="offering_value_snapshot" label="Offering" width="100" />
      <el-table-column prop="looking_value_snapshot" label="Looking" width="100" />
      <el-table-column label="Diff %" width="90">
        <template #default="{ row }">
          {{ row.difference_percent_snapshot ?? '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Views" width="110">
        <template #default="{ row }">
          <span @click.stop>
            <el-input-number
              :model-value="row.views_count ?? 0"
              :min="0"
              :controls="false"
              size="small"
              @change="(value) => patchListing(row, { views_count: value ?? 0 })"
            />
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Top" width="80">
        <template #default="{ row }">
          <span @click.stop>
            <el-switch
              :model-value="row.is_top === 'Y'"
              @change="(on) => patchListing(row, { is_top: on ? 'Y' : 'N' })"
            />
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Hot" width="80">
        <template #default="{ row }">
          <span @click.stop>
            <el-switch
              :model-value="row.is_hot === 'Y'"
              @change="(on) => patchListing(row, { is_hot: on ? 'Y' : 'N' })"
            />
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Sort" width="110">
        <template #default="{ row }">
          <span @click.stop>
            <el-input-number
              :model-value="row.sort_order ?? 0"
              :min="0"
              :max="999999"
              :controls="false"
              size="small"
              @change="(value) => patchListing(row, { sort_order: value ?? 0 })"
            />
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Posted IP" min-width="140">
        <template #default="{ row }">
          {{ row.posted_ip || '—' }}
        </template>
      </el-table-column>
      <el-table-column label="Expires" min-width="220">
        <template #default="{ row }">
          <span @click.stop>
            <el-date-picker
              :model-value="row.expires_at || null"
              type="datetime"
              size="small"
              clearable
              @change="(value) => patchListing(row, { expires_at: expireValue(value) })"
            />
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Action" width="340" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click.stop="open(row)">Detail</el-button>
          <el-button
            v-if="row.status === 'hidden'"
            size="small"
            @click.stop="unhide(row)"
          >Unhide</el-button>
          <el-button
            v-if="canClose(row)"
            size="small"
            type="primary"
            @click.stop="forceClose(row)"
          >Force close</el-button>
          <el-button
            v-if="row.status !== 'hidden'"
            size="small"
            @click.stop="hide(row)"
          >Hide</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-drawer v-model="drawer" title="Listing detail" size="60%">
      <template v-if="detail">
        <section class="block">
          <p class="label">Posted by</p>
          <button
            v-if="detail.owner_id"
            type="button"
            class="poster poster-link"
            @click="openUser(detail.owner_id)"
          >{{ detail.owner_username || '—' }}</button>
          <p v-else class="poster">{{ detail.owner_username || '—' }}</p>
          <p class="meta">{{ detail.owner_email || '—' }}</p>
          <p class="meta">
            <span class="copy-id">
              <span>{{ detail.owner_profile_id || '—' }}</span>
              <el-button
                v-if="detail.owner_profile_id"
                :icon="CopyDocument"
                link
                size="small"
                aria-label="Copy profile ID"
                @click="copyId(detail.owner_profile_id)"
              />
            </span>
          </p>
          <p class="label next">Accepted with</p>
          <button
            v-if="detail.counterparty_id"
            type="button"
            class="poster poster-link"
            @click="openUser(detail.counterparty_id)"
          >{{ detail.counterparty_username || '—' }}</button>
          <p v-else class="poster">{{ detail.counterparty_username || '—' }}</p>
          <p class="meta">{{ detail.counterparty_email || '—' }}</p>
          <p class="meta">
            <span class="copy-id">
              <span>{{ detail.counterparty_profile_id || '—' }}</span>
              <el-button
                v-if="detail.counterparty_profile_id"
                :icon="CopyDocument"
                link
                size="small"
                aria-label="Copy profile ID"
                @click="copyId(detail.counterparty_profile_id)"
              />
            </span>
          </p>
        </section>
        <section class="block">
          <p class="meta">
            <span class="copy-id">
              <span>{{ detail.public_id || '—' }}</span>
              <el-button
                v-if="detail.public_id"
                :icon="CopyDocument"
                link
                size="small"
                aria-label="Copy listing ID"
                @click="copyId(detail.public_id)"
              />
            </span>
          </p>
          <div class="editors">
            <div>
              <p class="label">Status</p>
              <el-select
                :model-value="detail.status"
                size="small"
                @change="(value) => patchListing(detail, { status: value })"
              >
                <el-option v-for="name in statuses" :key="name" :label="name" :value="name" />
              </el-select>
              <div class="status-actions">
                <el-button
                  v-if="detail.status === 'hidden'"
                  size="small"
                  @click="unhide(detail)"
                >Unhide</el-button>
                <el-button
                  v-if="canClose(detail)"
                  size="small"
                  type="primary"
                  @click="forceClose(detail)"
                >Force close</el-button>
                <el-button
                  v-if="detail.status !== 'hidden'"
                  size="small"
                  @click="hide(detail)"
                >Hide</el-button>
              </div>
            </div>
            <div>
              <p class="label">Result</p>
              <el-select
                :model-value="detail.result_snapshot || 'na'"
                size="small"
                @change="(value) => patchListing(detail, { result_snapshot: value })"
              >
                <el-option v-for="name in results" :key="name" :label="name" :value="name" />
              </el-select>
            </div>
            <div>
              <p class="label">Views</p>
              <el-input-number
                :model-value="detail.views_count ?? 0"
                :min="0"
                :controls="false"
                size="small"
                @change="(value) => patchListing(detail, { views_count: value ?? 0 })"
              />
            </div>
            <div>
              <p class="label">Expires</p>
              <el-date-picker
                :model-value="detail.expires_at || null"
                type="datetime"
                size="small"
                clearable
                @change="(value) => patchListing(detail, { expires_at: expireValue(value) })"
              />
            </div>
            <div>
              <p class="label">Top</p>
              <el-switch
                :model-value="detail.is_top === 'Y'"
                @change="(on) => patchListing(detail, { is_top: on ? 'Y' : 'N' })"
              />
            </div>
            <div>
              <p class="label">Hot</p>
              <el-switch
                :model-value="detail.is_hot === 'Y'"
                @change="(on) => patchListing(detail, { is_hot: on ? 'Y' : 'N' })"
              />
            </div>
            <div>
              <p class="label">Sort</p>
              <el-input-number
                :model-value="detail.sort_order ?? 0"
                :min="0"
                :max="999999"
                :controls="false"
                size="small"
                @change="(value) => patchListing(detail, { sort_order: value ?? 0 })"
              />
            </div>
          </div>
          <p class="label next">Note</p>
          <el-input
            :model-value="detail.note || ''"
            type="textarea"
            :rows="2"
            maxlength="280"
            @change="(value) => patchListing(detail, { note: value || null })"
          />
          <dl class="facts">
            <div>
              <dt>Offering</dt>
              <dd>{{ detail.offering_value_snapshot ?? '—' }}</dd>
            </div>
            <div>
              <dt>Looking</dt>
              <dd>{{ detail.looking_value_snapshot ?? '—' }}</dd>
            </div>
            <div>
              <dt>Diff</dt>
              <dd>{{ detail.value_difference_snapshot ?? '—' }}</dd>
            </div>
            <div>
              <dt>Diff %</dt>
              <dd>{{ detail.difference_percent_snapshot ?? '—' }}</dd>
            </div>
            <div>
              <dt>Posted IP</dt>
              <dd>{{ detail.posted_ip || '—' }}</dd>
            </div>
            <div>
              <dt>Created</dt>
              <dd>{{ formatTime(detail.created_at) }}</dd>
            </div>
            <div>
              <dt>Accepted</dt>
              <dd>{{ formatTime(detail.accepted_at) }}</dd>
            </div>
            <div>
              <dt>Pending</dt>
              <dd>{{ formatTime(detail.pending_at) }}</dd>
            </div>
            <div>
              <dt>Completed</dt>
              <dd>{{ formatTime(detail.completed_at) }}</dd>
            </div>
            <div>
              <dt>Failed</dt>
              <dd>{{ formatTime(detail.failed_at) }}</dd>
            </div>
            <div>
              <dt>Cancelled</dt>
              <dd>{{ formatTime(detail.cancelled_at) }}</dd>
            </div>
          </dl>
        </section>
        <h3>Offering</h3>
        <p class="hint">Items the poster is giving</p>
        <el-table :data="sideItems('offering')" size="small">
          <template #empty>
            <el-empty description="No offering items" />
          </template>
          <el-table-column label="" width="56">
            <template #default="{ row }">
              <img v-if="row.image" :src="row.image" :alt="row.name" class="thumb">
              <span v-else class="thumb-empty">[image]</span>
            </template>
          </el-table-column>
          <el-table-column prop="slot_no" label="#" width="50" />
          <el-table-column prop="name" label="Name" min-width="140" />
          <el-table-column prop="slug" label="Slug" min-width="120" />
          <el-table-column prop="mutation" label="Mutation" min-width="110" />
          <el-table-column prop="base_value" label="Base" width="80" />
          <el-table-column prop="final_value" label="Value" width="80" />
          <el-table-column prop="income" label="Income" width="80" />
          <el-table-column prop="demand" label="Demand" width="90" />
          <el-table-column prop="exist_count" label="Exist" width="80" />
          <el-table-column label="Traits" min-width="160">
            <template #default="{ row }">
              {{ traitText(row) }}
            </template>
          </el-table-column>
        </el-table>
        <h3>Looking for</h3>
        <p class="hint">Items the poster wants</p>
        <el-table :data="sideItems('looking_for')" size="small">
          <template #empty>
            <el-empty description="No looking-for items" />
          </template>
          <el-table-column label="" width="56">
            <template #default="{ row }">
              <img v-if="row.image" :src="row.image" :alt="row.name" class="thumb">
              <span v-else class="thumb-empty">[image]</span>
            </template>
          </el-table-column>
          <el-table-column prop="slot_no" label="#" width="50" />
          <el-table-column prop="name" label="Name" min-width="140" />
          <el-table-column prop="slug" label="Slug" min-width="120" />
          <el-table-column prop="mutation" label="Mutation" min-width="110" />
          <el-table-column prop="base_value" label="Base" width="80" />
          <el-table-column prop="final_value" label="Value" width="80" />
          <el-table-column prop="income" label="Income" width="80" />
          <el-table-column prop="demand" label="Demand" width="90" />
          <el-table-column prop="exist_count" label="Exist" width="80" />
          <el-table-column label="Traits" min-width="160">
            <template #default="{ row }">
              {{ traitText(row) }}
            </template>
          </el-table-column>
        </el-table>
        <h3>Joins</h3>
        <el-table :data="detail.join_requests || []" size="small">
          <template #empty>
            <el-empty description="No join requests" />
          </template>
          <el-table-column prop="public_id" label="ID" min-width="160" />
          <el-table-column label="Requester" min-width="140">
            <template #default="{ row }">
              <el-button
                v-if="row.requester_id"
                link
                type="primary"
                @click="openUser(row.requester_id)"
              >{{ row.requester_username || '—' }}</el-button>
              <span v-else>{{ row.requester_username || '—' }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Email" min-width="160">
            <template #default="{ row }">
              {{ row.requester_email || '—' }}
            </template>
          </el-table-column>
          <el-table-column prop="status" label="Status" width="120" />
          <el-table-column label="Note" min-width="140">
            <template #default="{ row }">
              {{ row.note || '—' }}
            </template>
          </el-table-column>
          <el-table-column label="Created" min-width="170">
            <template #default="{ row }">
              {{ formatTime(row.created_at) }}
            </template>
          </el-table-column>
          <el-table-column label="Action" width="100" fixed="right">
            <template #default="{ row }">
              <el-button
                v-if="row.status === 'requested'"
                size="small"
                type="danger"
                @click="deleteJoin(row)"
              >Delete</el-button>
            </template>
          </el-table-column>
        </el-table>
        <h3>Confirmations</h3>
        <el-table :data="detail.confirmations || []" size="small">
          <template #empty>
            <el-empty description="No confirmations" />
          </template>
          <el-table-column label="User" min-width="140">
            <template #default="{ row }">
              {{ row.username || '—' }}
            </template>
          </el-table-column>
          <el-table-column label="Email" min-width="160">
            <template #default="{ row }">
              {{ row.email || '—' }}
            </template>
          </el-table-column>
          <el-table-column prop="confirmation" label="Result" width="110" />
          <el-table-column label="Note" min-width="140">
            <template #default="{ row }">
              {{ row.note || '—' }}
            </template>
          </el-table-column>
          <el-table-column label="At" min-width="170">
            <template #default="{ row }">
              {{ formatTime(row.created_at) }}
            </template>
          </el-table-column>
        </el-table>
        <h3>Timeline</h3>
        <el-table :data="detail.events || []" size="small">
          <template #empty>
            <el-empty description="No events" />
          </template>
          <el-table-column prop="type" label="Event" min-width="160" />
          <el-table-column label="Actor" min-width="140">
            <template #default="{ row }">
              {{ row.actor || '—' }}
            </template>
          </el-table-column>
          <el-table-column label="At" min-width="170">
            <template #default="{ row }">
              {{ formatTime(row.at) }}
            </template>
          </el-table-column>
        </el-table>
        <h3>Reports</h3>
        <el-table :data="detail.reports || []" size="small">
          <template #empty>
            <el-empty description="No reports" />
          </template>
          <el-table-column prop="public_id" label="ID" min-width="160" />
          <el-table-column prop="reason" label="Reason" min-width="120" />
          <el-table-column prop="status" label="Status" width="120" />
        </el-table>
      </template>
      <template #footer>
        <el-button v-if="detail && detail.status === 'hidden'" @click="unhide(detail)">Unhide</el-button>
        <el-button v-if="detail && canClose(detail)" type="primary" @click="forceClose(detail)">Force close</el-button>
        <el-button v-if="detail && detail.status !== 'hidden'" @click="hide(detail)">Hide</el-button>
      </template>
    </el-drawer>
    <el-drawer
      v-model="userDrawer"
      :title="userDetail?.username || 'User detail'"
      size="40%"
      append-to-body
    >
      <template v-if="userDetail">
        <section class="block user-head">
          <img
            v-if="userDetail.avatar_url"
            :src="userDetail.avatar_url"
            :alt="userDetail.username || 'avatar'"
            class="user-avatar"
          >
          <div class="user-avatar user-avatar-empty" v-else aria-hidden="true"></div>
          <div>
            <p class="poster">{{ userDetail.display_name || userDetail.username || '—' }}</p>
            <p class="meta">{{ userDetail.username || '—' }}</p>
          </div>
        </section>
        <dl class="facts">
          <div>
            <dt>Email</dt>
            <dd>{{ userDetail.email || '—' }}</dd>
          </div>
          <div>
            <dt>Profile ID</dt>
            <dd>
              <span class="copy-id">
                <span>{{ userDetail.profile_id || '—' }}</span>
                <el-button
                  v-if="userDetail.profile_id"
                  :icon="CopyDocument"
                  link
                  size="small"
                  aria-label="Copy profile ID"
                  @click="copyId(userDetail.profile_id)"
                />
              </span>
            </dd>
          </div>
          <div>
            <dt>Public ID</dt>
            <dd>
              <span class="copy-id">
                <span>{{ userDetail.public_id || '—' }}</span>
                <el-button
                  v-if="userDetail.public_id"
                  :icon="CopyDocument"
                  link
                  size="small"
                  aria-label="Copy public ID"
                  @click="copyId(userDetail.public_id)"
                />
              </span>
            </dd>
          </div>
          <div>
            <dt>Roblox UID</dt>
            <dd>{{ userDetail.roblox_user_id || '—' }}</dd>
          </div>
          <div>
            <dt>Roblox sub</dt>
            <dd>{{ userDetail.roblox_sub || '—' }}</dd>
          </div>
          <div>
            <dt>Profile URL</dt>
            <dd>
              <a
                v-if="userDetail.profile_url"
                :href="userDetail.profile_url"
                target="_blank"
                rel="noopener noreferrer"
                class="user-link"
              >{{ userDetail.profile_url }}</a>
              <span v-else>—</span>
            </dd>
          </div>
          <div>
            <dt>Providers</dt>
            <dd>{{ (userDetail.providers || []).join(', ') || '—' }}</dd>
          </div>
          <div>
            <dt>Status</dt>
            <dd>{{ userDetail.account_status || '—' }}</dd>
          </div>
          <div>
            <dt>Visibility</dt>
            <dd>{{ userDetail.profile_visibility || '—' }}</dd>
          </div>
          <div>
            <dt>Moderation</dt>
            <dd>{{ userDetail.moderation_status || '—' }}</dd>
          </div>
          <div>
            <dt>Index</dt>
            <dd>{{ userDetail.profile_index_eligible ? 'Yes' : 'No' }}</dd>
          </div>
          <div>
            <dt>Can post</dt>
            <dd>{{ userDetail.posting_approved ? 'Yes' : 'No' }}</dd>
          </div>
          <div>
            <dt>Registered IP</dt>
            <dd>{{ userDetail.registered_ip || '—' }}</dd>
          </div>
          <div>
            <dt>Last login IP</dt>
            <dd>{{ userDetail.last_login_ip || '—' }}</dd>
          </div>
          <div>
            <dt>Last login</dt>
            <dd>{{ formatTime(userDetail.last_login_at) }}</dd>
          </div>
          <div>
            <dt>Created</dt>
            <dd>{{ formatTime(userDetail.created_at) }}</dd>
          </div>
          <div>
            <dt>Deleted</dt>
            <dd>{{ formatTime(userDetail.deleted_at) }}</dd>
          </div>
        </dl>
      </template>
    </el-drawer>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { CopyDocument } from '@element-plus/icons-vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { api } from '../api';

const statuses = [
  'open',
  'pending',
  'pending_confirmation',
  'completed',
  'failed',
  'disputed',
  'cancelled',
  'expired',
  'hidden',
];
const results = ['win', 'fair', 'lose', 'na'];
const closable = ['open', 'pending', 'pending_confirmation', 'hidden'];
const status = ref('');
const q = ref('');
const appliedQ = ref('');
const listings = ref([]);
const counts = ref({ all: 0 });
const loading = ref(false);
const drawer = ref(false);
const detail = ref(null);
const userDrawer = ref(false);
const userDetail = ref(null);

function tagType(value) {
  if (value === 'open') {
    return 'success';
  }
  if (value === 'completed') {
    return '';
  }
  if (value === 'failed' || value === 'cancelled' || value === 'hidden') {
    return 'info';
  }
  if (value === 'disputed' || value === 'expired') {
    return 'danger';
  }

  return 'warning';
}

function canClose(row) {
  return Boolean(row && closable.includes(row.status));
}

async function copyId(value) {
  const text = String(value || '');
  if (!text) {
    return;
  }
  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
    } else {
      const input = document.createElement('textarea');
      input.value = text;
      input.setAttribute('readonly', '');
      input.style.position = 'fixed';
      input.style.left = '-9999px';
      document.body.appendChild(input);
      input.select();
      const ok = document.execCommand('copy');
      document.body.removeChild(input);
      if (!ok) {
        throw new Error('Copy failed');
      }
    }
    ElMessage.success('Copied');
  } catch (error) {
    ElMessage.error(error.message || 'Copy failed');
  }
}

function expireValue(value) {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date.toISOString();
}

function formatTime(value) {
  if (!value) {
    return '—';
  }
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Shanghai',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).format(date).replace('T', ' ');
}

function sideItems(side) {
  return (detail.value?.items || []).filter((row) => row.side === side);
}

function traitText(row) {
  const traits = row.traits || [];
  if (!traits.length) {
    return '—';
  }

  return traits.map((trait) => {
    if (trait.value_multiplier && trait.value_multiplier !== 1) {
      return trait.name + ' x' + trait.value_multiplier;
    }

    return trait.name;
  }).join(', ');
}

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (status.value) {
      params.set('status', status.value);
    }
    if (appliedQ.value) {
      params.set('q', appliedQ.value);
    }
    const query = params.toString() ? '?' + params.toString() : '';
    const data = await api('/api/trade-listings' + query);
    listings.value = data.listings || [];
    counts.value = data.counts || { all: 0 };
  } catch (error) {
    ElMessage.error(error.message);
  } finally {
    loading.value = false;
  }
}

function search() {
  appliedQ.value = q.value;
  load();
}

function reset() {
  q.value = '';
  appliedQ.value = '';
  status.value = '';
  load();
}

async function openUser(id) {
  if (!id) {
    return;
  }
  try {
    const data = await api('/api/trade-users/' + id);
    userDetail.value = data.user || null;
    if (!userDetail.value) {
      ElMessage.error('User not found');
      return;
    }
    userDrawer.value = true;
  } catch (error) {
    ElMessage.error(error.message);
  }
}

async function open(row) {
  try {
    const data = await api('/api/trade-listings/' + row.id);
    detail.value = data.listing;
    drawer.value = true;
  } catch (error) {
    ElMessage.error(error.message);
  }
}

async function refreshDetail() {
  if (!detail.value?.id) {
    return;
  }
  try {
    const data = await api('/api/trade-listings/' + detail.value.id);
    detail.value = data.listing;
  } catch (error) {
    ElMessage.error(error.message);
  }
}

async function patchListing(row, payload) {
  try {
    const data = await api('/api/trade-listings/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
    Object.assign(row, data.listing || {});
    if (detail.value?.id === row.id) {
      Object.assign(detail.value, data.listing || {});
    }
    if (payload.status) {
      await afterStatusChange(row);
    }
    ElMessage.success('Updated');
  } catch (error) {
    ElMessage.error(error.message);
    await load();
    if (detail.value?.id === row.id) {
      await refreshDetail();
    }
  }
}

async function hide(row) {
  try {
    const data = await api('/api/trade-listings/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify({ status: 'hidden' }),
    });
    Object.assign(row, data.listing || {});
    ElMessage.success('Hidden');
    await afterStatusChange(row);
  } catch (error) {
    ElMessage.error(error.message);
  }
}

async function unhide(row) {
  try {
    const data = await api('/api/trade-listings/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify({ status: 'unhidden' }),
    });
    Object.assign(row, data.listing || {});
    ElMessage.success('Visible again');
    await afterStatusChange(row);
  } catch (error) {
    ElMessage.error(error.message);
  }
}

async function afterStatusChange(row) {
  await load();
  if (detail.value?.id === row.id) {
    await refreshDetail();
  }
}

async function deleteJoin(row) {
  try {
    await ElMessageBox.confirm(
      'Delete this send offer? This cannot be undone.',
      'Delete offer',
      { type: 'warning', confirmButtonText: 'Delete', cancelButtonText: 'Back' },
    );
  } catch {
    return;
  }
  try {
    await api('/api/trade-joins/' + row.id, { method: 'DELETE' });
    ElMessage.success('Deleted');
    await refreshDetail();
  } catch (error) {
    ElMessage.error(error.message);
  }
}

async function forceClose(row) {
  try {
    await ElMessageBox.confirm(
      'Force close this listing? It will be marked cancelled.',
      'Force close',
      { type: 'warning', confirmButtonText: 'Force close', cancelButtonText: 'Back' },
    );
  } catch {
    return;
  }
  try {
    const data = await api('/api/trade-listings/' + row.id, {
      method: 'PATCH',
      body: JSON.stringify({ status: 'cancelled' }),
    });
    Object.assign(row, data.listing || {});
    ElMessage.success('Closed');
    await afterStatusChange(row);
  } catch (error) {
    ElMessage.error(error.message);
  }
}

onMounted(load);
</script>

<style scoped>
.tabs { display: flex; flex-wrap: wrap; margin-bottom: 1rem; }
.toolbar { display: flex; flex-wrap: wrap; gap: .75rem; margin-bottom: 1rem; align-items: center; }
.toolbar .el-input { max-width: 22rem; }
.block { margin-bottom: 1.25rem; }
.label { margin: 0 0 .25rem; color: #94a3b8; font-size: .8rem; letter-spacing: .02em; }
.label.next { margin-top: 1rem; }
.poster { margin: 0; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 700; }
.poster-link {
  display: inline;
  padding: 0;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
  text-align: left;
}
.poster-link:hover { color: #67e8f9; }
.owner-cell {
  display: flex;
  align-items: center;
  gap: .5rem;
  min-width: 0;
}
.owner-cell.is-link { cursor: pointer; }
.owner-cell.is-link:hover { color: #67e8f9; }
.owner-avatar {
  width: 28px;
  height: 28px;
  flex: 0 0 auto;
  object-fit: cover;
  background: #1e293b;
}
.owner-meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
  line-height: 1.25;
}
.owner-meta .copy-id {
  color: #94a3b8;
  font-size: .8rem;
}
.copy-id {
  display: inline-flex;
  align-items: center;
  gap: .15rem;
  white-space: nowrap;
}
.copy-id > span,
.copy-id > .poster-link {
  flex: 0 0 auto;
}
.copy-id .el-button {
  flex: 0 0 auto;
  color: #94a3b8;
}
.copy-id .el-button:hover {
  color: #67e8f9;
}
:deep(.id-cell) {
  overflow: visible;
  text-overflow: clip;
}
:deep(.id-cell .cell) {
  overflow: visible;
  text-overflow: clip;
  white-space: nowrap;
}
.user-head { display: flex; align-items: center; gap: .75rem; }
.user-avatar { width: 48px; height: 48px; object-fit: cover; }
.user-avatar-empty { background: #1e293b; }
.user-link { color: #67e8f9; word-break: break-all; }
.meta, .note, .hint, .result { color: #94a3b8; }
.hint { margin: -.25rem 0 .5rem; font-size: .8rem; }
.result { margin-left: .5rem; }
.note { margin-top: .5rem; }
.facts {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .75rem 1rem;
  margin: 1rem 0 0;
}
.editors {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: .75rem 1rem;
  margin: .75rem 0 0;
}
.status-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin-top: .5rem;
}
.facts dt { color: #94a3b8; font-size: .75rem; }
.facts dd { margin: .15rem 0 0; }
h3 { margin: 1.25rem 0 .5rem; font-size: .95rem; }
.thumb { width: 36px; height: 36px; object-fit: cover; display: block; }
.thumb-empty { color: #64748b; font-size: .7rem; }
</style>

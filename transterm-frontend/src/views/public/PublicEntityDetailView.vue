<template>
  <div>
    <PageHeaderBlock
      :title="`${entity?.title || 'Entity'} details`"
      :subtitle="`ID: ${route.params.id}`"
    >
      <template #actions>
        <el-button @click="goBack">Back</el-button>
      </template>
    </PageHeaderBlock>

    <el-card class="card-shadow" v-loading="loading">
      <el-descriptions :column="2" border>
        <el-descriptions-item
          v-for="field in entity?.detailSummary || []"
          :key="field.label"
          :label="field.label"
        >
          {{ renderSummary(field) }}
        </el-descriptions-item>
      </el-descriptions>
    </el-card>

    <el-card
      v-if="canComment"
      class="card-shadow comment-box"
      header="Add comment"
    >
      <el-form label-position="top" @submit.prevent="submitComment">
        <el-form-item label="Comment" :error="commentError">
          <el-input
            v-model="commentBody"
            type="textarea"
            :rows="4"
            maxlength="2000"
            show-word-limit
          />
        </el-form-item>
        <el-button type="primary" :loading="commentSaving" @click="submitComment">
          Submit
        </el-button>
      </el-form>
    </el-card>

    <el-card
      v-for="section in entity?.detailSections || []"
      :key="section.title"
      class="card-shadow detail-section"
      :header="section.title"
    >
      <el-empty
        v-if="sectionRows(section).length === 0"
        description="No data"
      />
      <el-table v-else :data="sectionRows(section)" stripe>
        <el-table-column
          v-for="column in section.columns || []"
          :key="column.key + column.label"
          :label="column.label"
          :min-width="column.minWidth || 140"
        >
          <template #default="{ row }">
            {{ renderSectionCell(row, column) }}
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-card class="card-shadow detail-section raw-card">
      <template #header>
        <div class="raw-header">
          <span>Raw payload</span>
          <el-button type="primary" link @click="rawVisible = true">
            Open drawer
          </el-button>
        </div>
      </template>
      <pre class="json-preview">{{ JSON.stringify(item, null, 2) }}</pre>
    </el-card>

    <JsonPreviewDrawer
      v-model="rawVisible"
      title="Raw payload"
      :data="item"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { getPublicEntity } from '@/config/publicEntities'
import { resolvePath } from '@/utils/object'
import { getApiErrorMessage, getValidationErrors } from '@/utils/errors'
import { useAuthStore } from '@/stores/auth'
import { userApi } from '@/api/user'
import PageHeaderBlock from '@/components/common/PageHeaderBlock.vue'
import JsonPreviewDrawer from '@/components/common/JsonPreviewDrawer.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const entityKey = computed(() => route.meta?.entityKey)
const entity = computed(() => getPublicEntity(entityKey.value))

const loading = ref(false)
const item = ref(null)
const rawVisible = ref(false)

const commentBody = ref('')
const commentSaving = ref(false)
const commentError = ref('')

const canComment = computed(
  () => entityKey.value === 'terms' && authStore.isAuthenticated,
)

function renderSummary(field) {
  if (typeof field?.formatter === 'function') {
    return field.formatter(item.value)
  }

  return resolvePath(item.value, field?.key, '-')
}

function sectionRows(section) {
  if (typeof section?.rows === 'function') {
    return section.rows(item.value) || []
  }

  return []
}

function renderSectionCell(row, column) {
  if (typeof column?.formatter === 'function') {
    return column.formatter(row)
  }

  return resolvePath(row, column?.key, '-')
}

async function load() {
  if (!entity.value) {
    return
  }

  loading.value = true

  try {
    item.value = await entity.value.get(route.params.id)
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to load details.'))
    await router.push(`/${entityKey.value}`)
  } finally {
    loading.value = false
  }
}

async function submitComment() {
  commentError.value = ''

  if (!commentBody.value.trim()) {
    commentError.value = 'Comment body is required.'
    return
  }

  commentSaving.value = true

  try {
    await userApi.createTermComment(route.params.id, {
      body: commentBody.value,
    })

    ElMessage.success('Comment created successfully.')
    commentBody.value = ''
    await load()
  } catch (error) {
    const validation = getValidationErrors(error)
    commentError.value = validation?.body?.[0] || getApiErrorMessage(error, 'Unable to create comment.')
  } finally {
    commentSaving.value = false
  }
}

function goBack() {
  router.push(`/${entityKey.value}`)
}

watch(
  () => [route.params.id, entityKey.value],
  () => {
    load()
  },
  { immediate: true },
)
</script>

<style scoped>
.detail-section,
.comment-box {
  margin-top: 1rem;
}

.raw-card {
  margin-top: 1rem;
}

.raw-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.json-preview {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 12px;
  line-height: 1.4;
}
</style>


<template>
  <div>
    <PageHeaderBlock
      :title="pageTitle"
      :subtitle="pageSubtitle"
    />

    <EntityFiltersCard
      :model="filters"
      :filters="filterDefinitions"
    >
      <template #actions>
        <el-button type="primary" @click="applyFilters">Apply</el-button>
        <el-button @click="resetFilters">Reset</el-button>
      </template>
    </EntityFiltersCard>

    <ServerTableCard
      :rows="rows"
      :columns="columns"
      :loading="loading"
      :renderer="renderCell"
      :page="page"
      :per-page="perPage"
      :total="total"
      :page-sizes="[10, 20, 50]"
      :actions-width="220"
      @update:page="changePage"
      @update:perPage="changePerPage"
    >
      <template #actions="{ row }">
        <template v-if="isAdmin">
          <el-button
            v-if="!row.is_spam"
            type="warning"
            link
            @click="markSpam(row)"
          >
            Mark spam
          </el-button>
          <el-button
            v-else
            type="success"
            link
            @click="unmarkSpam(row)"
          >
            Unmark spam
          </el-button>
          <el-button type="danger" link @click="remove(row)">Delete</el-button>
        </template>

        <template v-else>
          <el-button type="primary" link @click="openEdit(row)">Edit</el-button>
          <el-button type="danger" link @click="remove(row)">Delete</el-button>
        </template>
      </template>
    </ServerTableCard>

    <el-dialog
      v-if="!isAdmin"
      v-model="dialogVisible"
      title="Edit comment"
      width="560px"
      destroy-on-close
    >
      <el-form label-position="top">
        <el-form-item label="Comment body" :error="editError">
          <el-input
            v-model="editBody"
            type="textarea"
            :rows="5"
            maxlength="2000"
            show-word-limit
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">Cancel</el-button>
        <el-button type="primary" :loading="editSaving" @click="saveEdit">
          Save
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { adminApi } from '@/api/admin'
import { userApi } from '@/api/user'
import { cleanQuery } from '@/utils/object'
import { formatDate } from '@/utils/date'
import { getApiErrorMessage, getValidationErrors } from '@/utils/errors'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { assignFilterValues } from '@/composables/useFilters'
import PageHeaderBlock from '@/components/common/PageHeaderBlock.vue'
import EntityFiltersCard from '@/components/common/EntityFiltersCard.vue'
import ServerTableCard from '@/components/common/ServerTableCard.vue'

const route = useRoute()

const isAdmin = computed(() => route.meta?.commentScope === 'admin')
const pageTitle = computed(() => (isAdmin.value ? 'Admin: Comment Moderation' : 'My Comments'))
const pageSubtitle = computed(() =>
  isAdmin.value
    ? 'Mark spam, unmark spam, and delete comments.'
    : 'Manage comments added to terms.',
)

const {
  loading,
  rows,
  page,
  perPage,
  total,
  runPageRequest,
  resetPage,
  resetPagination,
  handlePageChange,
  handlePerPageChange,
} = usePaginatedList(10)

const adminDefaultFilters = {
  search: '',
  user_id: null,
  term_id: null,
  is_spam: '',
  id_order: 'desc',
}

const userDefaultFilters = {
  term_id: null,
  is_spam: '',
}

const filters = reactive({})

const adminFilterDefinitions = [
  { key: 'search', label: 'Search', type: 'text', placeholder: 'comment body...' },
  { key: 'user_id', label: 'User ID', type: 'number' },
  { key: 'term_id', label: 'Term ID', type: 'number' },
  {
    key: 'is_spam',
    label: 'Spam status',
    type: 'select',
    options: [
      { label: 'Spam', value: true },
      { label: 'Not spam', value: false },
    ],
  },
  {
    key: 'id_order',
    label: 'ID order',
    type: 'select',
    options: [
      { label: 'Desc', value: 'desc' },
      { label: 'Asc', value: 'asc' },
    ],
  },
]

const userFilterDefinitions = [
  { key: 'term_id', label: 'Term ID', type: 'number' },
  {
    key: 'is_spam',
    label: 'Spam status',
    type: 'select',
    options: [
      { label: 'Spam', value: true },
      { label: 'Not spam', value: false },
    ],
  },
]

const filterDefinitions = computed(() =>
  isAdmin.value ? adminFilterDefinitions : userFilterDefinitions,
)

const columns = computed(() => {
  if (isAdmin.value) {
    return [
      { key: 'id', label: 'ID', width: 90 },
      { key: 'user', label: 'User', minWidth: 150 },
      { key: 'term_id', label: 'Term ID', width: 100 },
      { key: 'body', label: 'Body', minWidth: 260 },
      { key: 'is_spam', label: 'Spam', width: 90 },
      { key: 'created_at', label: 'Created', minWidth: 170 },
    ]
  }

  return [
    { key: 'id', label: 'ID', width: 90 },
    { key: 'term_id', label: 'Term ID', width: 100 },
    { key: 'body', label: 'Comment', minWidth: 280 },
    { key: 'is_spam', label: 'Spam', width: 90 },
    { key: 'created_at', label: 'Created', minWidth: 170 },
  ]
})

const dialogVisible = ref(false)
const editingId = ref(null)
const editBody = ref('')
const editSaving = ref(false)
const editError = ref('')

function currentDefaultFilters() {
  return isAdmin.value ? adminDefaultFilters : userDefaultFilters
}

function resetFilterState() {
  for (const key of Object.keys(filters)) {
    delete filters[key]
  }

  assignFilterValues(filters, currentDefaultFilters())
}

function renderCell(row, column) {
  switch (column.key) {
    case 'user':
      return row.user?.username || row.user?.email || row.user_id
    case 'is_spam':
      return row.is_spam ? 'Yes' : 'No'
    case 'created_at':
      return formatDate(row.created_at)
    default:
      return row[column.key] ?? '-'
  }
}

async function load() {
  try {
    await runPageRequest(() =>
      isAdmin.value
        ? adminApi.listComments(
          cleanQuery({
            ...filters,
            page: page.value,
            per_page: perPage.value,
          }),
        )
        : userApi.listMyComments(
          cleanQuery({
            ...filters,
            page: page.value,
            per_page: perPage.value,
          }),
        ),
    )
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to load comments.'))
  }
}

function applyFilters() {
  resetPage()
  return load()
}

function resetFilters() {
  resetFilterState()
  resetPage()
  return load()
}

function changePage(value) {
  return handlePageChange(value, load)
}

function changePerPage(value) {
  return handlePerPageChange(value, load)
}

function openEdit(row) {
  editingId.value = row.id
  editBody.value = row.body
  editError.value = ''
  dialogVisible.value = true
}

async function saveEdit() {
  if (isAdmin.value || !editingId.value) {
    return
  }

  editError.value = ''

  if (!editBody.value.trim()) {
    editError.value = 'Comment body is required.'
    return
  }

  editSaving.value = true

  try {
    const response = await userApi.updateMyComment(editingId.value, { body: editBody.value })
    ElMessage.success(response.message || 'Comment updated.')
    dialogVisible.value = false
    await load()
  } catch (error) {
    const validation = getValidationErrors(error)
    editError.value = validation?.body?.[0] || getApiErrorMessage(error, 'Unable to update comment.')
  } finally {
    editSaving.value = false
  }
}

async function remove(row) {
  try {
    await ElMessageBox.confirm('Delete this comment?', 'Confirmation', { type: 'warning' })

    const response = isAdmin.value
      ? await adminApi.deleteComment(row.id)
      : await userApi.deleteMyComment(row.id)

    ElMessage.success(response.message || 'Comment deleted.')
    await load()
  } catch (error) {
    if (error === 'cancel' || error === 'close') {
      return
    }

    ElMessage.error(getApiErrorMessage(error, 'Unable to delete comment.'))
  }
}

async function markSpam(row) {
  if (!isAdmin.value) {
    return
  }

  try {
    const response = await adminApi.markCommentSpam(row.id)
    ElMessage.success(response.message || 'Comment marked as spam.')
    await load()
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to mark spam.'))
  }
}

async function unmarkSpam(row) {
  if (!isAdmin.value) {
    return
  }

  try {
    const response = await adminApi.unmarkCommentSpam(row.id)
    ElMessage.success(response.message || 'Comment unmarked.')
    await load()
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to unmark spam.'))
  }
}

watch(
  isAdmin,
  async () => {
    resetFilterState()
    resetPagination()
    await load()
  },
  { immediate: true },
)
</script>

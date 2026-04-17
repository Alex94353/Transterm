<template>
  <admin-page-shell title="Audit Logs">
    <template #toolbar>
      <admin-toolbar>
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search action"
          width="220px"
          :secondary-value="filters.actorId"
          show-secondary
          secondary-placeholder="Actor ID"
          secondary-width="120px"
          @update:secondary-value="(value) => (filters.actorId = value)"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <el-input
          v-model="filters.targetUserId"
          clearable
          placeholder="Target user ID"
          style="width: 140px"
          @keyup.enter="handleFiltersChange"
          @clear="handleFiltersChange"
        />
        <admin-filter-select
          v-model="filters.auditableType"
          width="180px"
          :options="auditableTypeOptions"
          @change="handleFiltersChange"
        />
        <admin-id-order-control v-model="filters.idOrder" @change="handleFiltersChange" />
      </admin-toolbar>
    </template>

    <el-table :data="logs" stripe style="width: 100%" :loading="isLoading">
      <el-table-column prop="id" label="ID" width="90" />
      <el-table-column label="Created At" width="180">
        <template #default="{ row }">
          {{ formatDate(row.created_at) }}
        </template>
      </el-table-column>
      <el-table-column prop="action" label="Action" min-width="280" show-overflow-tooltip />
      <el-table-column label="Actor" min-width="220" show-overflow-tooltip>
        <template #default="{ row }">
          {{ formatUserCell(row.actor) }}
        </template>
      </el-table-column>
      <el-table-column label="Target User" min-width="220" show-overflow-tooltip>
        <template #default="{ row }">
          {{ formatUserCell(row.target_user) }}
        </template>
      </el-table-column>
      <el-table-column label="Entity" min-width="170" show-overflow-tooltip>
        <template #default="{ row }">
          {{ formatEntity(row) }}
        </template>
      </el-table-column>
      <el-table-column label="Metadata" min-width="260" show-overflow-tooltip>
        <template #default="{ row }">
          {{ formatMetadata(row.metadata) }}
        </template>
      </el-table-column>
    </el-table>

    <admin-pagination
      v-model:current-page="pagination.page"
      v-model:page-size="pagination.perPage"
      :total="pagination.total"
      @current-change="handlePageChange"
      @size-change="handlePageSizeChange"
    />
  </admin-page-shell>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import AdminFilterSelect from '../../components/Admin/AdminFilterSelect.vue'
import AdminIdOrderControl from '../../components/Admin/AdminIdOrderControl.vue'
import AdminPageShell from '../../components/Admin/AdminPageShell.vue'
import AdminPagination from '../../components/Admin/AdminPagination.vue'
import AdminSearchBar from '../../components/Admin/AdminSearchBar.vue'
import AdminToolbar from '../../components/Admin/AdminToolbar.vue'
import { useAdminList } from '../../composables/useAdminList'
import { isRequestCanceled } from '../../services/api'
import adminService from '../../services/adminService'

const logs = ref([])
const isLoading = ref(false)
const filters = reactive({
  actorId: '',
  targetUserId: '',
  auditableType: 'all',
  idOrder: 'desc',
})
const auditableTypeOptions = [
  { label: 'All entities', value: 'all' },
  { label: 'User', value: 'App\\Models\\User' },
  { label: 'Glossary', value: 'App\\Models\\Glossary' },
  { label: 'Term', value: 'App\\Models\\Term' },
  { label: 'Editor request', value: 'App\\Models\\EditorRoleRequest' },
]
const {
  searchQuery,
  appliedSearch,
  pagination,
  bindDebouncedSearch,
  runSearch,
  runClearSearch,
  runFiltersChange,
  runPageChange,
  runPageSizeChange,
} = useAdminList({ defaultPerPage: 20 })
let latestRequestId = 0

onMounted(() => {
  fetchLogs()
})

const toOptionalInteger = (value) => {
  const normalized = Number(value)
  return Number.isInteger(normalized) && normalized > 0 ? normalized : null
}

const fetchLogs = async () => {
  const requestId = ++latestRequestId
  isLoading.value = true
  try {
    const params = {
      page: pagination.page,
      per_page: pagination.perPage,
      id_order: filters.idOrder,
    }

    if (appliedSearch.value.trim()) {
      params.action = appliedSearch.value.trim()
    }

    const actorId = toOptionalInteger(filters.actorId)
    if (actorId) {
      params.actor_id = actorId
    }

    const targetUserId = toOptionalInteger(filters.targetUserId)
    if (targetUserId) {
      params.target_user_id = targetUserId
    }

    if (filters.auditableType !== 'all') {
      params.auditable_type = filters.auditableType
    }

    const response = await adminService.getAuditLogs(params, {
      cancelKey: 'admin:audit-logs:list',
    })

    if (requestId !== latestRequestId) return

    const payload = response.data || {}
    logs.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? logs.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load audit logs')
  } finally {
    if (requestId === latestRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchLogs)

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}

const formatUserCell = (user) => {
  if (!user) return '-'
  return `${user.name || user.username || 'User'} (#${user.id})`
}

const formatEntity = (row) => {
  if (!row?.auditable_type && !row?.auditable_id) {
    return '-'
  }

  const typeName = String(row.auditable_type || '')
    .split('\\')
    .pop() || 'Model'
  const entityId = row?.auditable_id ? `#${row.auditable_id}` : '#-'

  return `${typeName} ${entityId}`
}

const formatMetadata = (metadata) => {
  if (!metadata || typeof metadata !== 'object') {
    return '-'
  }

  const entries = Object.entries(metadata)
  if (entries.length === 0) {
    return '-'
  }

  return entries
    .slice(0, 3)
    .map(([key, value]) => `${key}: ${String(value)}`)
    .join(' | ')
}

const handleSearch = () => {
  runSearch(fetchLogs)
}

const handleClearSearch = () => {
  runClearSearch(fetchLogs)
}

const handleFiltersChange = () => {
  runFiltersChange(fetchLogs)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchLogs)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchLogs)
}
</script>

<style scoped>
</style>

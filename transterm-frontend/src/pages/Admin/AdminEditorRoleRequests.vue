<template>
  <admin-page-shell title="Editor Role Requests">
    <template #toolbar>
      <admin-toolbar label="Filter:">
        <admin-filter-select
          v-model="statusFilter"
          width="170px"
          :options="statusFilterOptions"
          @change="handleFilterChange"
        />
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search user/email"
          width="220px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <admin-id-order-control v-model="idOrder" @change="handleFilterChange" />
      </admin-toolbar>
    </template>

    <el-table :data="requests" stripe style="width: 100%" :loading="isLoading">
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column label="User" min-width="170" show-overflow-tooltip>
        <template #default="{ row }">
          {{ row.user?.name || row.user?.username || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Email" min-width="220" show-overflow-tooltip>
        <template #default="{ row }">
          {{ row.user?.email || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Role At Request" width="150">
        <template #default="{ row }">
          {{ row.requester_role_name || getPrimaryRoleName(row.user) || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Status" width="120">
        <template #default="{ row }">
          <el-tag :type="statusTagType(row.status)">
            {{ formatStatus(row.status) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Requested At" width="170">
        <template #default="{ row }">
          {{ formatDate(row.created_at) }}
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="190">
        <template #default="{ row }">
          <el-space v-if="row.status === 'pending'" :size="8">
            <el-button type="success" text size="small" @click="handleApprove(row.id)">
              Approve
            </el-button>
            <el-popconfirm title="Reject this request?" @confirm="handleReject(row.id)">
              <template #reference>
                <el-button type="danger" text size="small">Reject</el-button>
              </template>
            </el-popconfirm>
          </el-space>
          <span v-else>-</span>
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
import { onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import AdminPageShell from '../../components/Admin/AdminPageShell.vue'
import AdminFilterSelect from '../../components/Admin/AdminFilterSelect.vue'
import AdminPagination from '../../components/Admin/AdminPagination.vue'
import AdminIdOrderControl from '../../components/Admin/AdminIdOrderControl.vue'
import AdminSearchBar from '../../components/Admin/AdminSearchBar.vue'
import AdminToolbar from '../../components/Admin/AdminToolbar.vue'
import { useAdminList } from '../../composables/useAdminList'
import { isRequestCanceled } from '../../services/api'
import adminService from '../../services/adminService'

const requests = ref([])
const isLoading = ref(false)
const statusFilter = ref('pending')
const statusFilterOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'All', value: 'all' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]
const idOrder = ref('desc')
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
} = useAdminList()
let latestRequestId = 0

onMounted(() => {
  fetchRequests()
})

const fetchRequests = async () => {
  const requestId = ++latestRequestId
  isLoading.value = true
  try {
    const params = {
      page: pagination.page,
      per_page: pagination.perPage,
      id_order: idOrder.value,
    }

    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value
    }

    if (appliedSearch.value.trim()) {
      params.search = appliedSearch.value.trim()
    }

    const response = await adminService.getEditorRoleRequests(params, {
      cancelKey: 'admin:editor-role-requests:list',
    })
    if (requestId !== latestRequestId) return

    const payload = response.data || {}
    requests.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? requests.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load editor role requests')
  } finally {
    if (requestId === latestRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchRequests)

const handleApprove = async (requestId) => {
  try {
    await adminService.approveEditorRoleRequest(requestId)
    ElMessage.success('Editor role granted')
    fetchRequests()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to approve request')
  }
}

const handleReject = async (requestId) => {
  try {
    await adminService.rejectEditorRoleRequest(requestId)
    ElMessage.success('Request rejected')
    fetchRequests()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to reject request')
  }
}

const formatStatus = (status) => {
  const value = String(status || '').toLowerCase()
  if (value === 'approved') return 'Approved'
  if (value === 'rejected') return 'Rejected'
  return 'Pending'
}

const statusTagType = (status) => {
  const value = String(status || '').toLowerCase()
  if (value === 'approved') return 'success'
  if (value === 'rejected') return 'danger'
  return 'warning'
}

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}

const getPrimaryRoleName = (user) => user?.roles?.[0]?.name || null

const handleFilterChange = () => {
  runFiltersChange(fetchRequests)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchRequests)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchRequests)
}

const handleSearch = () => {
  runSearch(fetchRequests)
}

const handleClearSearch = () => {
  runClearSearch(fetchRequests)
}
</script>

<style scoped>
</style>


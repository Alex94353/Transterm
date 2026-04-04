<template>
  <admin-page-shell title="Manage Users">
    <template #toolbar>
      <admin-toolbar>
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search name, email, username"
          width="260px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <admin-filter-select
          v-model="filters.activated"
          width="140px"
          :options="activatedFilterOptions"
          @change="handleFiltersChange"
        />
        <admin-filter-select
          v-model="filters.banned"
          width="140px"
          :options="bannedFilterOptions"
          @change="handleFiltersChange"
        />
        <admin-filter-select
          v-model="filters.roleId"
          clearable
          width="160px"
          :options="roleOptions"
          option-label-key="name"
          option-value-key="id"
          placeholder="Select role"
          @change="handleFiltersChange"
        />
        <admin-id-order-control v-model="filters.idOrder" @change="handleFiltersChange" />
      </admin-toolbar>
    </template>

    <el-table
      :data="users"
      stripe
      style="width: 100%"
      :loading="isLoading"
    >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="Name" width="150" />
        <el-table-column prop="email" label="Email" show-overflow-tooltip />
        <el-table-column label="Status" width="100">
          <template #default="{ row }">
            <el-tag :type="row.activated ? 'success' : 'danger'">
              {{ row.activated ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Roles" width="150">
          <template #default="{ row }">
            <el-tag
              v-for="role in (row.roles || [])"
              :key="role.id"
              style="margin-right: 5px"
            >
              {{ role.name }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Ban" width="100">
          <template #default="{ row }">
            <el-tag :type="row.banned ? 'danger' : 'info'">
              {{ row.banned ? 'Banned' : 'No' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <admin-table-actions
              :row="row"
              :show-delete="false"
              @edit="handleEdit"
            >
              <template #append="{ row: currentRow }">
                <el-popconfirm
                  v-if="!currentRow.banned"
                  title="Ban this user?"
                  @confirm="handleBan(currentRow.id)"
                >
                  <template #reference>
                    <el-button type="warning" text size="small">
                      Ban
                    </el-button>
                  </template>
                </el-popconfirm>
                <el-popconfirm
                  v-else
                  title="Unban this user?"
                  @confirm="handleUnban(currentRow.id)"
                >
                  <template #reference>
                    <el-button type="info" text size="small">
                      Unban
                    </el-button>
                  </template>
                </el-popconfirm>
              </template>
            </admin-table-actions>
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

    <admin-form-dialog
      v-model="dialogVisible"
      title="Edit User"
      width="500px"
      :loading="isSubmitting"
      @save="handleSave"
    >
      <el-form :model="formData" label-width="120px">
        <el-form-item label="Name">
          <el-input v-model="formData.name" disabled />
        </el-form-item>
        <el-form-item label="Email">
          <el-input v-model="formData.email" disabled />
        </el-form-item>
        <el-form-item label="Status">
          <el-radio-group v-model="formData.activated">
            <el-radio :label="true">Active</el-radio>
            <el-radio :label="false">Inactive</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="Role">
          <el-select-v2
            v-model="formData.roleId"
            :options="roleDialogOptions"
            placeholder="Select role"
            clearable
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
    </admin-form-dialog>
  </admin-page-shell>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import AdminFormDialog from '../../components/Admin/AdminFormDialog.vue'
import AdminFilterSelect from '../../components/Admin/AdminFilterSelect.vue'
import AdminIdOrderControl from '../../components/Admin/AdminIdOrderControl.vue'
import AdminPageShell from '../../components/Admin/AdminPageShell.vue'
import AdminPagination from '../../components/Admin/AdminPagination.vue'
import AdminSearchBar from '../../components/Admin/AdminSearchBar.vue'
import AdminTableActions from '../../components/Admin/AdminTableActions.vue'
import AdminToolbar from '../../components/Admin/AdminToolbar.vue'
import { useAdminList } from '../../composables/useAdminList'
import { isRequestCanceled } from '../../services/api'
import adminService from '../../services/adminService'

const users = ref([])
const roleOptions = ref([])
const roleDialogOptions = ref([])
const activatedFilterOptions = [
  { label: 'All status', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]
const bannedFilterOptions = [
  { label: 'All bans', value: 'all' },
  { label: 'Banned', value: 'banned' },
  { label: 'Not banned', value: 'not_banned' },
]
const isLoading = ref(false)
const isSubmitting = ref(false)
const dialogVisible = ref(false)
const editingId = ref(null)
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
let latestUsersRequestId = 0

const filters = reactive({
  activated: 'all',
  banned: 'all',
  roleId: null,
  idOrder: 'desc',
})

const formData = reactive({
  name: '',
  email: '',
  activated: true,
  roleId: null,
})

onMounted(() => {
  fetchRoles()
  fetchUsers()
})

const fetchRoles = async () => {
  try {
    const response = await adminService.getRoles(
      { per_page: 100 },
      { cancelKey: 'admin:users:roles:lookup' },
    )
    roleOptions.value = response.data?.data || []
    roleDialogOptions.value = roleOptions.value.map((role) => ({
      label: role.name,
      value: role.id,
    }))
  } catch (err) {
    if (isRequestCanceled(err)) return
    roleOptions.value = []
    roleDialogOptions.value = []
  }
}

const fetchUsers = async () => {
  const requestId = ++latestUsersRequestId
  isLoading.value = true
  try {
    const params = {
      page: pagination.page,
      per_page: pagination.perPage,
      id_order: filters.idOrder,
    }

    if (appliedSearch.value.trim()) {
      params.search = appliedSearch.value.trim()
    }

    if (filters.activated === 'active') {
      params.activated = true
    } else if (filters.activated === 'inactive') {
      params.activated = false
    }

    if (filters.banned === 'banned') {
      params.banned = true
    } else if (filters.banned === 'not_banned') {
      params.banned = false
    }

    if (filters.roleId) {
      params.role_id = filters.roleId
    }

    const response = await adminService.getUsers(params, {
      cancelKey: 'admin:users:list',
    })
    if (requestId !== latestUsersRequestId) return
    const payload = response.data || {}
    users.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? users.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestUsersRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load users')
  } finally {
    if (requestId === latestUsersRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchUsers)

const handleEdit = (user) => {
  editingId.value = user.id
  formData.name = user.name
  formData.email = user.email
  formData.activated = user.activated
  formData.roleId = user.roles?.[0]?.id ?? null
  dialogVisible.value = true
}

const handleSave = async () => {
  isSubmitting.value = true
  try {
    await adminService.updateUser(editingId.value, {
      activated: formData.activated,
      role_id: formData.roleId || undefined,
    })
    ElMessage.success('User updated successfully')
    dialogVisible.value = false
    fetchUsers()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to update user')
  } finally {
    isSubmitting.value = false
  }
}

const handleBan = async (userId) => {
  try {
    await adminService.banUser(userId)
    ElMessage.success('User banned successfully')
    fetchUsers()
  } catch {
    ElMessage.error('Failed to ban user')
  }
}

const handleUnban = async (userId) => {
  try {
    await adminService.unbanUser(userId)
    ElMessage.success('User unbanned successfully')
    fetchUsers()
  } catch {
    ElMessage.error('Failed to unban user')
  }
}

const handleSearch = () => {
  runSearch(fetchUsers)
}

const handleClearSearch = () => {
  runClearSearch(fetchUsers)
}

const handleFiltersChange = () => {
  runFiltersChange(fetchUsers)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchUsers)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchUsers)
}
</script>

<style scoped>
</style>

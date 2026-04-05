<template>
  <admin-page-shell title="Manage Field Groups">
    <template #toolbar>
      <admin-toolbar>
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search group name or code"
          width="260px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <admin-id-order-control
          v-model="filters.idOrder"
          @change="handleFiltersChange"
        />
        <el-button type="success" @click="handleCreate">
          <el-icon><plus /></el-icon>
          New Group
        </el-button>
      </admin-toolbar>
    </template>

    <el-table
      :data="fieldGroups"
      stripe
      style="width: 100%"
      :loading="isLoading"
    >
      <el-table-column prop="id" label="ID" width="90" />
      <el-table-column prop="name" label="Name" min-width="260" show-overflow-tooltip />
      <el-table-column prop="code" label="Code" min-width="220" show-overflow-tooltip />
      <el-table-column label="Fields" width="120">
        <template #default="{ row }">
          {{ row.fields_count ?? 0 }}
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="200">
        <template #default="{ row }">
          <admin-table-actions
            :row="row"
            :show-delete="(row.fields_count ?? 0) === 0"
            delete-confirm="Delete this field group?"
            @edit="handleEdit"
            @delete="({ id }) => handleDelete(id)"
          >
            <template #append="{ row: currentRow }">
              <el-tag
                v-if="(currentRow.fields_count ?? 0) > 0"
                type="warning"
                effect="plain"
                size="small"
              >
                Has fields
              </el-tag>
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
      :title="dialogTitle"
      width="520px"
      :loading="isSubmitting"
      @save="handleSave"
    >
      <el-form :model="formData" label-width="120px">
        <el-form-item label="Name">
          <el-input v-model="formData.name" placeholder="Field group name" />
        </el-form-item>
        <el-form-item label="Code">
          <el-input v-model="formData.code" placeholder="group_code" />
        </el-form-item>
      </el-form>
    </admin-form-dialog>
  </admin-page-shell>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import AdminFormDialog from '../../components/Admin/AdminFormDialog.vue'
import AdminIdOrderControl from '../../components/Admin/AdminIdOrderControl.vue'
import AdminPageShell from '../../components/Admin/AdminPageShell.vue'
import AdminPagination from '../../components/Admin/AdminPagination.vue'
import AdminSearchBar from '../../components/Admin/AdminSearchBar.vue'
import AdminTableActions from '../../components/Admin/AdminTableActions.vue'
import AdminToolbar from '../../components/Admin/AdminToolbar.vue'
import { useAdminList } from '../../composables/useAdminList'
import { isRequestCanceled } from '../../services/api'
import adminService from '../../services/adminService'

const fieldGroups = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const dialogVisible = ref(false)
const isEditMode = ref(false)
const editingId = ref(null)
const dialogTitle = ref('New Field Group')
let latestRequestId = 0

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

const filters = reactive({
  idOrder: 'desc',
})

const formData = reactive({
  name: '',
  code: '',
})

onMounted(() => {
  fetchFieldGroups()
})

const fetchFieldGroups = async () => {
  const requestId = ++latestRequestId
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

    const response = await adminService.getFieldGroups(params, {
      cancelKey: 'admin:field-groups:list',
    })
    if (requestId !== latestRequestId) return
    const payload = response.data || {}
    fieldGroups.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? fieldGroups.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load field groups')
  } finally {
    if (requestId === latestRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchFieldGroups)

const handleCreate = () => {
  isEditMode.value = false
  dialogTitle.value = 'New Field Group'
  formData.name = ''
  formData.code = ''
  dialogVisible.value = true
}

const handleEdit = (group) => {
  isEditMode.value = true
  editingId.value = group.id
  dialogTitle.value = 'Edit Field Group'
  formData.name = group.name || ''
  formData.code = group.code || ''
  dialogVisible.value = true
}

const handleSave = async () => {
  const normalizedName = formData.name?.trim()
  const normalizedCode = formData.code?.trim()

  if (!normalizedName || !normalizedCode) {
    ElMessage.warning('Name and code are required')
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      name: normalizedName,
      code: normalizedCode,
    }

    if (isEditMode.value) {
      await adminService.updateFieldGroup(editingId.value, payload)
      ElMessage.success('Field group updated successfully')
    } else {
      await adminService.createFieldGroup(payload)
      ElMessage.success('Field group created successfully')
    }
    dialogVisible.value = false
    fetchFieldGroups()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save field group')
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await adminService.deleteFieldGroup(id)
    ElMessage.success('Field group deleted successfully')
    fetchFieldGroups()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to delete field group')
  }
}

const handleSearch = () => {
  runSearch(fetchFieldGroups)
}

const handleClearSearch = () => {
  runClearSearch(fetchFieldGroups)
}

const handleFiltersChange = () => {
  runFiltersChange(fetchFieldGroups)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchFieldGroups)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchFieldGroups)
}
</script>

<style scoped>
</style>

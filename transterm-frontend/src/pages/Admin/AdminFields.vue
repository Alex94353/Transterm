<template>
  <admin-page-shell title="Manage Fields">
    <template #toolbar>
      <admin-toolbar>
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search field name or code"
          width="260px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <admin-filter-select
          v-model="filters.fieldGroupId"
          clearable
          width="240px"
          :options="fieldGroupOptions"
          option-label-key="name"
          option-value-key="id"
          placeholder="Select field group"
          @change="handleFiltersChange"
        />
        <admin-id-order-control
          v-model="filters.idOrder"
          @change="handleFiltersChange"
        />
        <el-button type="success" @click="handleCreate">
          <el-icon><plus /></el-icon>
          New Field
        </el-button>
      </admin-toolbar>
    </template>

    <el-table
      :data="fields"
      stripe
      style="width: 100%"
      :loading="isLoading"
    >
      <el-table-column prop="id" label="ID" width="90" />
      <el-table-column prop="name" label="Name" min-width="240" show-overflow-tooltip />
      <el-table-column prop="code" label="Code" width="180" />
      <el-table-column label="Field Group" min-width="220" show-overflow-tooltip>
        <template #default="{ row }">
          {{ row.field_group?.name || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Glossaries" width="110">
        <template #default="{ row }">
          {{ row.glossaries_count ?? 0 }}
        </template>
      </el-table-column>
      <el-table-column label="Terms" width="100">
        <template #default="{ row }">
          {{ row.terms_count ?? 0 }}
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="220">
        <template #default="{ row }">
          <admin-table-actions
            :row="row"
            :show-delete="(row.glossaries_count ?? 0) === 0 && (row.terms_count ?? 0) === 0"
            delete-confirm="Delete this field?"
            @edit="handleEdit"
            @delete="({ id }) => handleDelete(id)"
          >
            <template #append="{ row: currentRow }">
              <el-tag
                v-if="(currentRow.glossaries_count ?? 0) > 0 || (currentRow.terms_count ?? 0) > 0"
                type="warning"
                effect="plain"
                size="small"
              >
                In use
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
      width="540px"
      :loading="isSubmitting"
      @save="handleSave"
    >
      <el-form :model="formData" label-width="120px">
        <el-form-item label="Field Group">
          <el-select-v2
            v-model="formData.field_group_id"
            :options="fieldGroupSelectOptions"
            placeholder="Select field group"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Name">
          <el-input v-model="formData.name" placeholder="Field name" />
        </el-form-item>
        <el-form-item label="Code">
          <el-input v-model="formData.code" placeholder="field_code" />
        </el-form-item>
      </el-form>
    </admin-form-dialog>
  </admin-page-shell>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
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
import { getSafeDeleteErrorMessage } from '../../services/errorMessages'

const fields = ref([])
const fieldGroups = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const dialogVisible = ref(false)
const isEditMode = ref(false)
const editingId = ref(null)
const dialogTitle = ref('New Field')
let latestFieldsRequestId = 0

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
  fieldGroupId: null,
  idOrder: 'desc',
})

const formData = reactive({
  field_group_id: null,
  name: '',
  code: '',
})

const fieldGroupOptions = computed(() => fieldGroups.value)

const fieldGroupSelectOptions = computed(() =>
  fieldGroups.value.map((group) => ({
    label: group.name,
    value: group.id,
  })),
)

onMounted(() => {
  fetchFieldGroups()
  fetchFields()
})

const fetchFieldGroups = async () => {
  try {
    const response = await adminService.getFieldGroups(
      { per_page: 200 },
      { cancelKey: 'admin:fields:groups:lookup' },
    )
    fieldGroups.value = response.data?.data || []
  } catch (err) {
    if (isRequestCanceled(err)) return
    fieldGroups.value = []
    ElMessage.error('Failed to load field groups')
  }
}

const fetchFields = async () => {
  const requestId = ++latestFieldsRequestId
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

    if (filters.fieldGroupId) {
      params.field_group_id = filters.fieldGroupId
    }

    const response = await adminService.getFields(params, {
      cancelKey: 'admin:fields:list',
    })
    if (requestId !== latestFieldsRequestId) return
    const payload = response.data || {}
    fields.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? fields.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestFieldsRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load fields')
  } finally {
    if (requestId === latestFieldsRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchFields)

const handleCreate = () => {
  isEditMode.value = false
  dialogTitle.value = 'New Field'
  formData.field_group_id = null
  formData.name = ''
  formData.code = ''
  dialogVisible.value = true
}

const handleEdit = (field) => {
  isEditMode.value = true
  editingId.value = field.id
  dialogTitle.value = 'Edit Field'
  formData.field_group_id = field.field_group?.id || field.field_group_id || null
  formData.name = field.name || ''
  formData.code = field.code || ''
  dialogVisible.value = true
}

const handleSave = async () => {
  const normalizedName = formData.name?.trim()
  const normalizedCode = formData.code?.trim()

  if (!formData.field_group_id || !normalizedName || !normalizedCode) {
    ElMessage.warning('Field group, name and code are required')
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      field_group_id: formData.field_group_id,
      name: normalizedName,
      code: normalizedCode,
    }

    if (isEditMode.value) {
      await adminService.updateField(editingId.value, payload)
      ElMessage.success('Field updated successfully')
    } else {
      await adminService.createField(payload)
      ElMessage.success('Field created successfully')
    }
    dialogVisible.value = false
    fetchFields()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save field')
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await adminService.deleteField(id)
    ElMessage.success('Field deleted successfully')
    fetchFields()
  } catch (err) {
    ElMessage.error(getSafeDeleteErrorMessage(err, 'field'))
  }
}

const handleSearch = () => {
  runSearch(fetchFields)
}

const handleClearSearch = () => {
  runClearSearch(fetchFields)
}

const handleFiltersChange = () => {
  runFiltersChange(fetchFields)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchFields)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchFields)
}
</script>

<style scoped>
</style>

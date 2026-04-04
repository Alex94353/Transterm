<template>
  <admin-page-shell title="Manage References">
    <template #toolbar>
      <admin-toolbar>
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search source, type, language"
          width="260px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <admin-filter-select
          v-model="filters.type"
          :options="typeSelectOptions"
          clearable
          placeholder="Select type"
          width="160px"
          @change="handleFiltersChange"
        />
        <admin-filter-select
          v-model="filters.language"
          :options="languageSelectOptions"
          clearable
          placeholder="Select language"
          width="160px"
          @change="handleFiltersChange"
        />
        <admin-id-order-control v-model="filters.idOrder" @change="handleFiltersChange" />
        <el-button type="success" @click="handleCreate">
          <el-icon><plus /></el-icon>
          New Reference
        </el-button>
      </admin-toolbar>
    </template>

    <el-table
      :data="references"
      stripe
      style="width: 100%"
      :loading="isLoading"
    >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="source" label="Source" min-width="320" show-overflow-tooltip />
        <el-table-column prop="type" label="Type" width="140" />
        <el-table-column prop="language" label="Language" width="140" />
        <el-table-column label="User" width="180">
          <template #default="{ row }">
            {{ row.user?.name || row.user?.username || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <admin-table-actions
              :row="row"
              delete-confirm="Delete this reference?"
              @edit="handleEdit"
              @delete="({ id }) => handleDelete(id)"
            />
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
      width="500px"
      :loading="isSubmitting"
      @save="handleSave"
    >
      <el-form :model="formData" label-width="100px">
        <el-form-item label="Source">
          <el-input v-model="formData.source" type="textarea" :rows="4" />
        </el-form-item>
        <el-form-item label="Type">
          <el-input v-model="formData.type" />
        </el-form-item>
        <el-form-item label="Language">
          <el-input v-model="formData.language" />
        </el-form-item>
      </el-form>
    </admin-form-dialog>
  </admin-page-shell>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import AdminFilterSelect from '../../components/Admin/AdminFilterSelect.vue'
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

const references = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const dialogVisible = ref(false)
const isEditMode = ref(false)
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
let latestReferencesRequestId = 0

const filters = reactive({
  type: '',
  language: '',
  idOrder: 'desc',
})

const typeOptions = ref([])
const languageOptions = ref([])
const typeSelectOptions = computed(() => typeOptions.value.map((option) => ({ label: option, value: option })))
const languageSelectOptions = computed(() =>
  languageOptions.value.map((option) => ({ label: option, value: option })),
)

const formData = reactive({
  source: '',
  type: '',
  language: '',
})

const dialogTitle = ref('New Reference')

onMounted(() => {
  fetchReferences()
})

const fetchReferences = async () => {
  const requestId = ++latestReferencesRequestId
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

    if (filters.type?.trim()) {
      params.type = filters.type.trim()
    }

    if (filters.language?.trim()) {
      params.language = filters.language.trim()
    }

    const response = await adminService.getReferences(params, {
      cancelKey: 'admin:references:list',
    })
    if (requestId !== latestReferencesRequestId) return
    const payload = response.data || {}
    const rows = payload.data || []

    references.value = rows
    pagination.total = payload?.meta?.total ?? payload.total ?? rows.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage

    // Keep dropdown options practical based on currently visible dataset.
    typeOptions.value = Array.from(new Set(rows.map((item) => item.type).filter(Boolean)))
    languageOptions.value = Array.from(new Set(rows.map((item) => item.language).filter(Boolean)))
  } catch (err) {
    if (requestId !== latestReferencesRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load references')
  } finally {
    if (requestId === latestReferencesRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchReferences)

const handleCreate = () => {
  isEditMode.value = false
  dialogTitle.value = 'New Reference'
  formData.source = ''
  formData.type = ''
  formData.language = ''
  dialogVisible.value = true
}

const handleEdit = (reference) => {
  isEditMode.value = true
  editingId.value = reference.id
  dialogTitle.value = 'Edit Reference'
  formData.source = reference.source || ''
  formData.type = reference.type || ''
  formData.language = reference.language || ''
  dialogVisible.value = true
}

const handleSave = async () => {
  isSubmitting.value = true
  try {
    const payload = {
      source: formData.source,
      type: formData.type || null,
      language: formData.language || null,
    }

    if (isEditMode.value) {
      await adminService.updateReference(editingId.value, payload)
      ElMessage.success('Reference updated successfully')
    } else {
      await adminService.createReference(payload)
      ElMessage.success('Reference created successfully')
    }
    dialogVisible.value = false
    fetchReferences()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save reference')
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await adminService.deleteReference(id)
    ElMessage.success('Reference deleted successfully')
    fetchReferences()
  } catch {
    ElMessage.error('Failed to delete reference')
  }
}

const handleSearch = () => {
  runSearch(fetchReferences)
}

const handleClearSearch = () => {
  runClearSearch(fetchReferences)
}

const handleFiltersChange = () => {
  runFiltersChange(fetchReferences)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchReferences)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchReferences)
}
</script>

<style scoped>
</style>

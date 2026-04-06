<template>
  <admin-page-shell title="Manage Glossaries">
    <template #toolbar>
      <admin-toolbar>
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search title or description"
          width="250px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <admin-filter-select
          v-model="filters.languagePairId"
          clearable
          placeholder="Select language pair"
          width="220px"
          :options="languagePairOptions"
          option-label-key="label"
          option-value-key="id"
          @change="handleFiltersChange"
        />
        <admin-filter-select
          v-model="filters.fieldId"
          clearable
          placeholder="Select field"
          width="180px"
          :options="fields"
          option-label-key="name"
          option-value-key="id"
          @change="handleFiltersChange"
        />
        <admin-filter-select
          v-if="canManageApproval"
          v-model="filters.approved"
          width="130px"
          :options="approvedFilterOptions"
          @change="handleFiltersChange"
        />
        <admin-filter-select
          v-model="filters.isPublic"
          width="130px"
          :options="visibilityFilterOptions"
          @change="handleFiltersChange"
        />
        <admin-id-order-control v-model="filters.idOrder" @change="handleFiltersChange" />
        <el-button type="success" @click="handleCreate">
          <el-icon><plus /></el-icon>
          New Glossary
        </el-button>
      </admin-toolbar>
    </template>

    <el-empty
      v-if="!isLoading && glossaries.length === 0"
      description="No glossaries found"
    />

    <el-table
      v-else
      :data="glossaries"
      stripe
      style="width: 100%"
      :loading="isLoading"
    >
      <el-table-column label="Title" min-width="230" show-overflow-tooltip>
        <template #default="{ row }">
          {{ getGlossaryTitle(row) }}
        </template>
      </el-table-column>
      <el-table-column label="Description" min-width="260" show-overflow-tooltip>
        <template #default="{ row }">
          {{ getGlossaryDescription(row) }}
        </template>
      </el-table-column>
      <el-table-column label="Language Pair" width="200">
        <template #default="{ row }">
          <span v-if="row.language_pair">
            {{ row.language_pair.source_language?.name }} ->
            {{ row.language_pair.target_language?.name }}
          </span>
        </template>
      </el-table-column>
      <el-table-column label="Field" width="150">
        <template #default="{ row }">
          {{ row.field?.name || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Terms" width="90">
        <template #default="{ row }">
          {{ row.terms_count ?? 0 }}
        </template>
      </el-table-column>
      <el-table-column label="Status" width="180">
        <template #default="{ row }">
          <el-tag :type="row.approved ? 'success' : 'warning'" style="margin-right: 6px">
            {{ row.approved ? 'Approved' : 'Pending' }}
          </el-tag>
          <el-tag :type="row.is_public ? 'primary' : 'info'">
            {{ row.is_public ? 'Public' : 'Private' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="220">
        <template #default="{ row }">
          <admin-table-actions
            :row="row"
            :show-delete="(row.terms_count ?? 0) === 0"
            delete-confirm="Delete this glossary?"
            @edit="handleEdit"
            @delete="({ id }) => handleDelete(id)"
          >
            <template #append="{ row: currentRow }">
              <el-tag
                v-if="(currentRow.terms_count ?? 0) > 0"
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
      :loading="isSubmitting"
      @save="handleSave"
    >
      <el-form :model="formData" label-width="120px">
        <el-form-item label="Language Pair">
          <el-select-v2
            v-model="formData.language_pair_id"
            :options="languagePairSelectOptions"
            placeholder="Select language pair"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Field">
          <el-select-v2
            v-model="formData.field_id"
            :options="fieldSelectOptions"
            placeholder="Select field"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Title">
          <el-input v-model="formData.title" placeholder="Glossary title" />
        </el-form-item>
        <el-form-item label="Description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="3"
            placeholder="Glossary description"
          />
        </el-form-item>
        <el-form-item v-if="canManageApproval" label="Approved">
          <el-switch v-model="formData.approved" />
        </el-form-item>
        <el-form-item label="Public">
          <el-switch v-model="formData.is_public" />
        </el-form-item>
        <el-alert
          title="Title and description are saved to glossary translations."
          type="info"
          :closable="false"
          show-icon
        />
      </el-form>
    </admin-form-dialog>
  </admin-page-shell>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
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
import { useAuthStore } from '../../stores/auth'

const glossaries = ref([])
const languagePairs = ref([])
const fields = ref([])
const authStore = useAuthStore()
const canManageApproval = computed(() => authStore.isAdmin)
const approvedFilterOptions = [
  { label: 'All', value: 'all' },
  { label: 'Approved', value: 'approved' },
  { label: 'Pending', value: 'pending' },
]
const visibilityFilterOptions = [
  { label: 'All', value: 'all' },
  { label: 'Public', value: 'public' },
  { label: 'Private', value: 'private' },
]
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
let latestGlossariesRequestId = 0

const filters = reactive({
  languagePairId: null,
  fieldId: null,
  approved: 'all',
  isPublic: 'all',
  idOrder: 'desc',
})

const formData = reactive({
  language_pair_id: null,
  field_id: null,
  title: '',
  description: '',
  translation_language_id: null,
  approved: false,
  is_public: false,
})

const dialogTitle = ref('New Glossary')

onMounted(() => {
  fetchGlossaries()
  fetchLanguagePairs()
  fetchFields()
})

const fetchGlossaries = async () => {
  const requestId = ++latestGlossariesRequestId
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

    if (filters.languagePairId) {
      params.language_pair_id = filters.languagePairId
    }

    if (filters.fieldId) {
      params.field_id = filters.fieldId
    }

    if (canManageApproval.value) {
      if (filters.approved === 'approved') {
        params.approved = true
      } else if (filters.approved === 'pending') {
        params.approved = false
      }
    }

    if (filters.isPublic === 'public') {
      params.is_public = true
    } else if (filters.isPublic === 'private') {
      params.is_public = false
    }

    const response = await adminService.adminGetGlossaries(params, {
      cancelKey: 'admin:glossaries:list',
    })
    if (requestId !== latestGlossariesRequestId) return
    const payload = response.data || {}
    glossaries.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? glossaries.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestGlossariesRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load glossaries')
  } finally {
    if (requestId === latestGlossariesRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchGlossaries)

const fetchLanguagePairs = async () => {
  try {
    const response = await adminService.getLanguagePairs(
      { per_page: 100 },
      { cancelKey: 'admin:glossaries:language-pairs:lookup' },
    )
    languagePairs.value = response.data.data || response.data || []
  } catch (err) {
    if (isRequestCanceled(err)) return
    ElMessage.error('Failed to load language pairs')
  }
}

const fetchFields = async () => {
  try {
    const response = await adminService.getFields(
      { per_page: 100 },
      { cancelKey: 'admin:glossaries:fields:lookup' },
    )
    fields.value = response.data.data || response.data || []
  } catch (err) {
    if (isRequestCanceled(err)) return
    fields.value = []
  }
}

const handleCreate = () => {
  isEditMode.value = false
  dialogTitle.value = 'New Glossary'
  formData.language_pair_id = null
  formData.field_id = null
  formData.title = ''
  formData.description = ''
  formData.translation_language_id = null
  formData.approved = false
  formData.is_public = false
  dialogVisible.value = true
}

const getSourceLanguageIdByPairId = (languagePairId) => {
  const pair = languagePairs.value.find((item) => Number(item.id) === Number(languagePairId))
  return pair?.source_language?.id ?? null
}

const getPrimaryTranslation = (glossary) => glossary?.translations?.[0] ?? null

watch(() => formData.language_pair_id, (languagePairId) => {
  if (isEditMode.value) return
  formData.translation_language_id = getSourceLanguageIdByPairId(languagePairId)
})

const handleEdit = (glossary) => {
  isEditMode.value = true
  editingId.value = glossary.id
  const primaryTranslation = getPrimaryTranslation(glossary)
  dialogTitle.value = 'Edit Glossary'
  formData.language_pair_id = glossary.language_pair?.id || null
  formData.field_id = glossary.field?.id || null
  formData.title = primaryTranslation?.title || ''
  formData.description = primaryTranslation?.description || ''
  formData.translation_language_id = primaryTranslation?.language_id
    || getSourceLanguageIdByPairId(glossary.language_pair?.id)
    || null
  formData.approved = canManageApproval.value ? !!glossary.approved : false
  formData.is_public = !!glossary.is_public
  dialogVisible.value = true
}

const handleSave = async () => {
  const normalizedTitle = formData.title?.trim()
  if (!normalizedTitle) {
    ElMessage.warning('Title is required')
    return
  }

  isSubmitting.value = true
  try {
    const translationLanguageId = formData.translation_language_id
      || getSourceLanguageIdByPairId(formData.language_pair_id)

    const payload = {
      language_pair_id: formData.language_pair_id,
      field_id: formData.field_id,
      title: normalizedTitle,
      description: formData.description?.trim() || null,
      translation_language_id: translationLanguageId || undefined,
      is_public: formData.is_public,
    }

    if (canManageApproval.value) {
      payload.approved = formData.approved
    }

    if (isEditMode.value) {
      await adminService.updateGlossary(editingId.value, payload)
      ElMessage.success('Glossary updated successfully')
    } else {
      await adminService.createGlossary(payload)
      ElMessage.success('Glossary created successfully')
    }
    dialogVisible.value = false
    fetchGlossaries()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save glossary')
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await adminService.deleteGlossary(id)
    ElMessage.success('Glossary deleted successfully')
    fetchGlossaries()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to delete glossary')
  }
}

const getGlossaryTitle = (glossary) => {
  return glossary.translations?.[0]?.title || `Glossary #${glossary.id}`
}

const getGlossaryDescription = (glossary) => {
  return glossary.translations?.[0]?.description || '-'
}

const getLanguagePairLabel = (pair) => {
  if (!pair) return '-'
  return `${pair.source_language?.name || '-'} -> ${pair.target_language?.name || '-'}`
}

const languagePairOptions = computed(() => {
  return languagePairs.value.map((pair) => ({
    id: pair.id,
    label: getLanguagePairLabel(pair),
  }))
})

const languagePairSelectOptions = computed(() => {
  return languagePairs.value.map((pair) => ({
    label: getLanguagePairLabel(pair),
    value: pair.id,
  }))
})

const fieldSelectOptions = computed(() => {
  return fields.value.map((field) => ({
    label: field.name,
    value: field.id,
  }))
})

const handleSearch = () => {
  runSearch(fetchGlossaries)
}

const handleClearSearch = () => {
  runClearSearch(fetchGlossaries)
}

const handleFiltersChange = () => {
  runFiltersChange(fetchGlossaries)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchGlossaries)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchGlossaries)
}
</script>

<style scoped>
</style>

<template>
  <admin-page-shell title="Manage Terms">
    <template #toolbar>
      <admin-toolbar>
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search term title/definition"
          width="260px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <el-select-v2
          v-model="filters.glossaryId"
          :options="glossarySelectOptions"
          clearable
          placeholder="Select glossary"
          style="width: 200px"
          @change="handleFiltersChange"
        />
        <el-select-v2
          v-model="filters.fieldId"
          :options="fieldSelectOptions"
          clearable
          placeholder="Select field"
          style="width: 180px"
          @change="handleFiltersChange"
        />
        <admin-id-order-control v-model="filters.idOrder" @change="handleFiltersChange" />
        <el-button type="success" @click="handleCreate">
          <el-icon><plus /></el-icon>
          New Term
        </el-button>
      </admin-toolbar>
    </template>

    <el-table
      :data="terms"
      stripe
      style="width: 100%"
      :loading="isLoading"
    >
        <el-table-column label="Term" min-width="240" show-overflow-tooltip>
          <template #default="{ row }">
            {{ getTermTitle(row) }}
          </template>
        </el-table-column>
        <el-table-column label="Definition" min-width="280" show-overflow-tooltip>
          <template #default="{ row }">
            {{ getTermDefinition(row) }}
          </template>
        </el-table-column>
        <el-table-column label="Glossary" width="150">
          <template #default="{ row }">
            {{ getGlossaryLabel(row.glossary) }}
          </template>
        </el-table-column>
        <el-table-column label="Field" width="150">
          <template #default="{ row }">
            {{ row.field?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <admin-table-actions
              :row="row"
              delete-confirm="Delete this term?"
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
      width="600px"
      :loading="isSubmitting"
      @save="handleSave"
    >
      <el-form :model="formData" label-width="120px">
        <el-form-item label="Glossary">
          <el-select-v2
            v-model="formData.glossary_id"
            :options="glossarySelectOptions"
            placeholder="Select glossary"
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
          <el-input v-model="formData.title" placeholder="Term title" />
        </el-form-item>
        <el-form-item label="Definition">
          <el-input
            v-model="formData.definition"
            type="textarea"
            :rows="3"
            placeholder="Term definition"
          />
        </el-form-item>
        <el-alert
          title="Title and definition are saved to term translations."
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
import AdminIdOrderControl from '../../components/Admin/AdminIdOrderControl.vue'
import AdminPageShell from '../../components/Admin/AdminPageShell.vue'
import AdminPagination from '../../components/Admin/AdminPagination.vue'
import AdminSearchBar from '../../components/Admin/AdminSearchBar.vue'
import AdminTableActions from '../../components/Admin/AdminTableActions.vue'
import AdminToolbar from '../../components/Admin/AdminToolbar.vue'
import { useAdminList } from '../../composables/useAdminList'
import { isRequestCanceled } from '../../services/api'
import adminService from '../../services/adminService'

const terms = ref([])
const glossaries = ref([])
const fields = ref([])
const glossarySelectOptions = computed(() =>
  glossaries.value.map((glossary) => ({
    label: getGlossaryLabel(glossary),
    value: glossary.id,
  })),
)
const fieldSelectOptions = computed(() =>
  fields.value.map((field) => ({
    label: field.name,
    value: field.id,
  })),
)
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
let latestTermsRequestId = 0

const filters = reactive({
  glossaryId: null,
  fieldId: null,
  idOrder: 'desc',
})

const formData = reactive({
  glossary_id: null,
  field_id: null,
  title: '',
  definition: '',
  translation_language_id: null,
})

const dialogTitle = ref('New Term')

onMounted(() => {
  fetchTerms()
  fetchGlossaries()
  fetchFields()
})

const fetchTerms = async () => {
  const requestId = ++latestTermsRequestId
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

    if (filters.glossaryId) {
      params.glossary_id = filters.glossaryId
    }

    if (filters.fieldId) {
      params.field_id = filters.fieldId
    }

    const response = await adminService.adminGetTerms(params, {
      cancelKey: 'admin:terms:list',
    })
    if (requestId !== latestTermsRequestId) return
    const payload = response.data || {}
    terms.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? terms.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestTermsRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load terms')
  } finally {
    if (requestId === latestTermsRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchTerms)

const fetchGlossaries = async () => {
  try {
    const response = await adminService.adminGetGlossaries(
      { per_page: 100 },
      { cancelKey: 'admin:terms:glossaries:lookup' },
    )
    glossaries.value = response.data.data || response.data || []
  } catch (err) {
    if (isRequestCanceled(err)) return
    glossaries.value = []
  }
}

const fetchFields = async () => {
  try {
    const response = await adminService.getFields(
      { per_page: 100 },
      { cancelKey: 'admin:terms:fields:lookup' },
    )
    fields.value = response.data.data || response.data || []
  } catch (err) {
    if (isRequestCanceled(err)) return
    fields.value = []
  }
}

const handleCreate = () => {
  isEditMode.value = false
  dialogTitle.value = 'New Term'
  formData.glossary_id = null
  formData.field_id = null
  formData.title = ''
  formData.definition = ''
  formData.translation_language_id = null
  dialogVisible.value = true
}

const getPrimaryTranslation = (term) => term?.translations?.[0] ?? null

const getSourceLanguageIdByGlossaryId = (glossaryId) => {
  const glossary = glossaries.value.find((item) => Number(item.id) === Number(glossaryId))
  return glossary?.language_pair?.source_language?.id ?? glossary?.translations?.[0]?.language_id ?? null
}

watch(() => formData.glossary_id, (glossaryId) => {
  if (isEditMode.value) return
  formData.translation_language_id = getSourceLanguageIdByGlossaryId(glossaryId)
})

const handleEdit = (term) => {
  isEditMode.value = true
  const primaryTranslation = getPrimaryTranslation(term)
  editingId.value = term.id
  dialogTitle.value = 'Edit Term'
  formData.glossary_id = term.glossary_id
  formData.field_id = term.field_id
  formData.title = primaryTranslation?.title || ''
  formData.definition = primaryTranslation?.definition || ''
  formData.translation_language_id = primaryTranslation?.language_id
    || getSourceLanguageIdByGlossaryId(term.glossary_id)
    || null
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
      || getSourceLanguageIdByGlossaryId(formData.glossary_id)

    const payload = {
      glossary_id: formData.glossary_id,
      field_id: formData.field_id,
      title: normalizedTitle,
      definition: formData.definition?.trim() || null,
      translation_language_id: translationLanguageId || undefined,
    }

    if (isEditMode.value) {
      await adminService.updateTerm(editingId.value, payload)
      ElMessage.success('Term updated successfully')
    } else {
      await adminService.createTerm(payload)
      ElMessage.success('Term created successfully')
    }
    dialogVisible.value = false
    fetchTerms()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save term')
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async (id) => {
  try {
    await adminService.deleteTerm(id)
    ElMessage.success('Term deleted successfully')
    fetchTerms()
  } catch {
    ElMessage.error('Failed to delete term')
  }
}

const getTermTitle = (term) => {
  return term.translations?.[0]?.title || `Term #${term.id}`
}

const getTermDefinition = (term) => {
  return term.translations?.[0]?.definition || '-'
}

const getGlossaryLabel = (glossary) => {
  if (!glossary) return '-'
  return glossary.translations?.[0]?.title || `Glossary #${glossary.id}`
}

const handleSearch = () => {
  runSearch(fetchTerms)
}

const handleClearSearch = () => {
  runClearSearch(fetchTerms)
}

const handleFiltersChange = () => {
  runFiltersChange(fetchTerms)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchTerms)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchTerms)
}
</script>

<style scoped>
</style>

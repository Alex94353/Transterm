<template>
  <admin-page-shell title="Manage Languages">

      <el-tabs v-model="activeTab">
        <el-tab-pane label="Languages" name="languages">
          <admin-toolbar>
            <admin-search-bar
              v-model="languageFilters.search"
              :secondary-value="languageFilters.code"
              show-secondary
              placeholder="Search by name or code"
              secondary-placeholder="Code filter"
              width="260px"
              secondary-width="160px"
              @update:secondary-value="(value) => (languageFilters.code = value)"
              @search="handleLanguageSearch"
            />
            <admin-id-order-control
              v-model="languageFilters.idOrder"
              show-reset
              @change="handleLanguageSearch"
              @reset="resetLanguageFilters"
            />
            <el-button type="success" @click="openLanguageCreate">
              <el-icon><plus /></el-icon>
              New Language
            </el-button>
          </admin-toolbar>

          <el-table :data="languages" stripe style="width: 100%" :loading="isLanguagesLoading">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="code" label="Code" width="120" />
            <el-table-column prop="name" label="Name" min-width="220" />
            <el-table-column prop="flag_path" label="Flag Path" min-width="220" show-overflow-tooltip>
              <template #default="{ row }">
                {{ row.flag_path || '-' }}
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="200">
              <template #default="{ row }">
                <admin-table-actions
                  :row="row"
                  delete-confirm="Delete this language?"
                  @edit="openLanguageEdit"
                  @delete="({ id }) => deleteLanguage(id)"
                />
              </template>
            </el-table-column>
          </el-table>

          <admin-pagination
            v-model:current-page="languagePagination.page"
            v-model:page-size="languagePagination.perPage"
            :total="languagePagination.total"
            @current-change="handleLanguagePageChange"
            @size-change="handleLanguagePageSizeChange"
          />
        </el-tab-pane>

        <el-tab-pane label="Language Pairs" name="pairs">
          <admin-toolbar>
            <admin-filter-select
              v-model="pairFilters.sourceLanguageId"
              clearable
              placeholder="Select source language"
              width="220px"
              :options="languageOptions"
              option-label-key="label"
              option-value-key="id"
              @change="handlePairFiltersChange"
            />
            <admin-filter-select
              v-model="pairFilters.targetLanguageId"
              clearable
              placeholder="Select target language"
              width="220px"
              :options="languageOptions"
              option-label-key="label"
              option-value-key="id"
              @change="handlePairFiltersChange"
            />
            <admin-id-order-control
              v-model="pairFilters.idOrder"
              show-reset
              @change="handlePairFiltersChange"
              @reset="resetPairFilters"
            />
            <el-button type="success" @click="openPairCreate">
              <el-icon><plus /></el-icon>
              New Pair
            </el-button>
          </admin-toolbar>

          <el-table :data="languagePairs" stripe style="width: 100%" :loading="isPairsLoading">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column label="Source" min-width="220">
              <template #default="{ row }">
                {{ row.source_language?.name }} ({{ row.source_language?.code }})
              </template>
            </el-table-column>
            <el-table-column label="Target" min-width="220">
              <template #default="{ row }">
                {{ row.target_language?.name }} ({{ row.target_language?.code }})
              </template>
            </el-table-column>
            <el-table-column label="Pair" min-width="260">
              <template #default="{ row }">
                {{ row.source_language?.name }} -> {{ row.target_language?.name }}
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="200">
              <template #default="{ row }">
                <admin-table-actions
                  :row="row"
                  delete-confirm="Delete this pair?"
                  @edit="openPairEdit"
                  @delete="({ id }) => deletePair(id)"
                />
              </template>
            </el-table-column>
          </el-table>

          <admin-pagination
            v-model:current-page="pairPagination.page"
            v-model:page-size="pairPagination.perPage"
            :total="pairPagination.total"
            @current-change="handlePairPageChange"
            @size-change="handlePairPageSizeChange"
          />
        </el-tab-pane>
      </el-tabs>

      <admin-form-dialog
        v-model="languageDialogVisible"
        :title="languageDialogTitle"
        width="520px"
        :loading="isLanguageSubmitting"
        @save="saveLanguage"
      >
        <el-form :model="languageForm" label-width="120px">
          <el-form-item label="Name">
            <el-input v-model="languageForm.name" placeholder="English" />
          </el-form-item>
          <el-form-item label="Code">
            <el-input v-model="languageForm.code" placeholder="en" />
          </el-form-item>
          <el-form-item label="Flag Path">
            <el-input v-model="languageForm.flag_path" placeholder="/flags/en.svg" />
          </el-form-item>
        </el-form>
      </admin-form-dialog>

      <admin-form-dialog
        v-model="pairDialogVisible"
        :title="pairDialogTitle"
        width="520px"
        :loading="isPairSubmitting"
        @save="savePair"
      >
        <el-form :model="pairForm" label-width="120px">
          <el-form-item label="Source language">
            <el-select-v2
              v-model="pairForm.source_language_id"
              :options="languageSelectOptions"
              placeholder="Select source"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="Target language">
            <el-select-v2
              v-model="pairForm.target_language_id"
              :options="languageSelectOptions"
              placeholder="Select target"
              style="width: 100%"
            />
          </el-form-item>
        </el-form>
      </admin-form-dialog>
  </admin-page-shell>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
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
import adminService from '../../services/adminService'

const activeTab = ref('languages')

const languages = ref([])
const languageOptions = ref([])
const languagePairs = ref([])
const languageSelectOptions = ref([])

const isLanguagesLoading = ref(false)
const isPairsLoading = ref(false)

const languageFilters = reactive({
  search: '',
  code: '',
  idOrder: 'desc',
})
const SEARCH_DEBOUNCE_MS = 350
let languageSearchDebounceTimer

const pairFilters = reactive({
  sourceLanguageId: null,
  targetLanguageId: null,
  idOrder: 'desc',
})

const languagePagination = reactive({
  page: 1,
  perPage: 10,
  total: 0,
})

const pairPagination = reactive({
  page: 1,
  perPage: 10,
  total: 0,
})

const languageDialogVisible = ref(false)
const isLanguageEditMode = ref(false)
const editingLanguageId = ref(null)
const isLanguageSubmitting = ref(false)
const languageDialogTitle = ref('New Language')
const languageForm = reactive({
  name: '',
  code: '',
  flag_path: '',
})

const pairDialogVisible = ref(false)
const isPairEditMode = ref(false)
const editingPairId = ref(null)
const isPairSubmitting = ref(false)
const pairDialogTitle = ref('New Language Pair')
const pairForm = reactive({
  source_language_id: null,
  target_language_id: null,
})

onMounted(() => {
  fetchLanguages()
  fetchLanguagePairs()
  fetchLanguageOptions()
})

onBeforeUnmount(() => {
  clearTimeout(languageSearchDebounceTimer)
})

watch(() => languageFilters.search, (newValue, oldValue) => {
  if (newValue === oldValue) return
  clearTimeout(languageSearchDebounceTimer)
  languageSearchDebounceTimer = setTimeout(() => {
    languagePagination.page = 1
    fetchLanguages()
  }, SEARCH_DEBOUNCE_MS)
})

const fetchLanguages = async () => {
  isLanguagesLoading.value = true
  try {
    const params = {
      page: languagePagination.page,
      per_page: languagePagination.perPage,
      id_order: languageFilters.idOrder,
    }

    if (languageFilters.search.trim()) {
      params.search = languageFilters.search.trim()
    }

    if (languageFilters.code.trim()) {
      params.code = languageFilters.code.trim()
    }

    const response = await adminService.getLanguages(params)
    const payload = response.data || {}
    languages.value = payload.data || []
    languagePagination.total = payload?.meta?.total ?? payload.total ?? languages.value.length
    languagePagination.page = payload?.meta?.current_page ?? languagePagination.page
    languagePagination.perPage = payload?.meta?.per_page ?? languagePagination.perPage
  } catch {
    ElMessage.error('Failed to load languages')
  } finally {
    isLanguagesLoading.value = false
  }
}

const fetchLanguageOptions = async () => {
  try {
    const response = await adminService.getLanguages({ per_page: 200 })
    languageOptions.value = (response.data?.data || []).map((language) => ({
      ...language,
      label: `${language.name} (${language.code})`,
    }))
    languageSelectOptions.value = languageOptions.value.map((language) => ({
      label: language.label,
      value: language.id,
    }))
  } catch {
    languageOptions.value = []
    languageSelectOptions.value = []
  }
}

const fetchLanguagePairs = async () => {
  isPairsLoading.value = true
  try {
    const params = {
      page: pairPagination.page,
      per_page: pairPagination.perPage,
      id_order: pairFilters.idOrder,
    }

    if (pairFilters.sourceLanguageId) {
      params.source_language_id = pairFilters.sourceLanguageId
    }

    if (pairFilters.targetLanguageId) {
      params.target_language_id = pairFilters.targetLanguageId
    }

    const response = await adminService.getLanguagePairs(params)
    const payload = response.data || {}
    languagePairs.value = payload.data || []
    pairPagination.total = payload?.meta?.total ?? payload.total ?? languagePairs.value.length
    pairPagination.page = payload?.meta?.current_page ?? pairPagination.page
    pairPagination.perPage = payload?.meta?.per_page ?? pairPagination.perPage
  } catch {
    ElMessage.error('Failed to load language pairs')
  } finally {
    isPairsLoading.value = false
  }
}

const handleLanguageSearch = () => {
  clearTimeout(languageSearchDebounceTimer)
  languagePagination.page = 1
  fetchLanguages()
}

const resetLanguageFilters = () => {
  languageFilters.search = ''
  languageFilters.code = ''
  languageFilters.idOrder = 'desc'
  languagePagination.page = 1
  fetchLanguages()
}

const handlePairFiltersChange = () => {
  pairPagination.page = 1
  fetchLanguagePairs()
}

const resetPairFilters = () => {
  pairFilters.sourceLanguageId = null
  pairFilters.targetLanguageId = null
  pairFilters.idOrder = 'desc'
  pairPagination.page = 1
  fetchLanguagePairs()
}

const handleLanguagePageChange = (page) => {
  languagePagination.page = page
  fetchLanguages()
}

const handleLanguagePageSizeChange = (size) => {
  languagePagination.perPage = size
  languagePagination.page = 1
  fetchLanguages()
}

const handlePairPageChange = (page) => {
  pairPagination.page = page
  fetchLanguagePairs()
}

const handlePairPageSizeChange = (size) => {
  pairPagination.perPage = size
  pairPagination.page = 1
  fetchLanguagePairs()
}

const openLanguageCreate = () => {
  isLanguageEditMode.value = false
  languageDialogTitle.value = 'New Language'
  languageForm.name = ''
  languageForm.code = ''
  languageForm.flag_path = ''
  languageDialogVisible.value = true
}

const openLanguageEdit = (language) => {
  isLanguageEditMode.value = true
  editingLanguageId.value = language.id
  languageDialogTitle.value = 'Edit Language'
  languageForm.name = language.name || ''
  languageForm.code = language.code || ''
  languageForm.flag_path = language.flag_path || ''
  languageDialogVisible.value = true
}

const saveLanguage = async () => {
  isLanguageSubmitting.value = true
  try {
    const payload = {
      name: languageForm.name,
      code: languageForm.code,
      flag_path: languageForm.flag_path || null,
    }

    if (isLanguageEditMode.value) {
      await adminService.updateLanguage(editingLanguageId.value, payload)
      ElMessage.success('Language updated successfully')
    } else {
      await adminService.createLanguage(payload)
      ElMessage.success('Language created successfully')
    }

    languageDialogVisible.value = false
    fetchLanguages()
    fetchLanguageOptions()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save language')
  } finally {
    isLanguageSubmitting.value = false
  }
}

const deleteLanguage = async (id) => {
  try {
    await adminService.deleteLanguage(id)
    ElMessage.success('Language deleted successfully')
    fetchLanguages()
    fetchLanguageOptions()
  } catch {
    ElMessage.error('Failed to delete language')
  }
}

const openPairCreate = () => {
  isPairEditMode.value = false
  pairDialogTitle.value = 'New Language Pair'
  pairForm.source_language_id = null
  pairForm.target_language_id = null
  pairDialogVisible.value = true
}

const openPairEdit = (pair) => {
  isPairEditMode.value = true
  editingPairId.value = pair.id
  pairDialogTitle.value = 'Edit Language Pair'
  pairForm.source_language_id = pair.source_language_id
  pairForm.target_language_id = pair.target_language_id
  pairDialogVisible.value = true
}

const savePair = async () => {
  isPairSubmitting.value = true
  try {
    const payload = {
      source_language_id: pairForm.source_language_id,
      target_language_id: pairForm.target_language_id,
    }

    if (isPairEditMode.value) {
      await adminService.updateLanguagePair(editingPairId.value, payload)
      ElMessage.success('Language pair updated successfully')
    } else {
      await adminService.createLanguagePair(payload)
      ElMessage.success('Language pair created successfully')
    }

    pairDialogVisible.value = false
    fetchLanguagePairs()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to save language pair')
  } finally {
    isPairSubmitting.value = false
  }
}

const deletePair = async (id) => {
  try {
    await adminService.deleteLanguagePair(id)
    ElMessage.success('Language pair deleted successfully')
    fetchLanguagePairs()
  } catch {
    ElMessage.error('Failed to delete language pair')
  }
}
</script>

<style scoped>
:deep(.toolbar) {
  margin-bottom: 14px;
}
</style>

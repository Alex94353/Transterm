<template>
  <div>
    <PageHeaderBlock
      :title="entity?.title || 'Admin'"
      subtitle="CRUD interface bound to backend API."
    />

    <EntityFiltersCard
      :model="filters"
      :filters="entity?.filters || []"
    >
      <template #actions>
        <el-button type="primary" @click="applyFilters">Apply filters</el-button>
        <el-button @click="resetFilters">Reset</el-button>
        <el-button
          v-if="canCreate"
          type="success"
          @click="openCreate"
        >
          Create
        </el-button>
      </template>
    </EntityFiltersCard>

    <ServerTableCard
      :rows="rows"
      :columns="entity?.columns || []"
      :loading="loading"
      :renderer="renderAdminCell"
      :page="page"
      :per-page="perPage"
      :total="total"
      :actions-width="190"
      @update:page="changePage"
      @update:perPage="changePerPage"
    >
      <template #actions="{ row }">
        <el-button type="primary" link @click="openView(row)">View</el-button>
        <el-button v-if="canEdit" type="primary" link @click="openEdit(row)">Edit</el-button>
        <el-button v-if="canDelete" type="danger" link @click="remove(row)">Delete</el-button>
      </template>
    </ServerTableCard>

    <EntityFormDialog
      ref="entityFormRef"
      v-model="dialogVisible"
      :title="editingId ? 'Edit entry' : 'Create entry'"
      :submit-label="editingId ? 'Save' : 'Create'"
      :form-model="form"
      :fields="entity?.formFields || []"
      :rules="formRules"
      :server-errors="serverErrors"
      :lookups="lookups"
      :saving="saving"
      @cancel="dialogVisible = false"
      @submit="submit"
    />

    <JsonPreviewDrawer
      v-model="viewVisible"
      title="Entry details"
      :data="viewItem"
    />
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getAdminEntity, renderAdminCell } from '@/config/adminEntities'
import { cleanQuery } from '@/utils/object'
import { getApiErrorMessage, getValidationErrors } from '@/utils/errors'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { resetFilterModel } from '@/composables/useFilters'
import { useAuthStore } from '@/stores/auth'
import PageHeaderBlock from '@/components/common/PageHeaderBlock.vue'
import EntityFiltersCard from '@/components/common/EntityFiltersCard.vue'
import ServerTableCard from '@/components/common/ServerTableCard.vue'
import EntityFormDialog from '@/components/common/EntityFormDialog.vue'
import JsonPreviewDrawer from '@/components/common/JsonPreviewDrawer.vue'

const route = useRoute()
const authStore = useAuthStore()

const entityKey = computed(() => route.meta?.entityKey)
const entity = computed(() => getAdminEntity(entityKey.value))
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

const lookups = reactive({})
const filters = reactive({})
const form = reactive({})
const serverErrors = reactive({})
const lookupsLoaded = ref(false)
const lookupsLoading = ref(false)
let lookupsRequest = null

const dialogVisible = ref(false)
const entityFormRef = ref(null)
const saving = ref(false)
const editingId = ref(null)

const viewVisible = ref(false)
const viewItem = ref(null)

const canCreate = computed(() => {
  if (!entity.value?.permissions?.create) {
    return true
  }

  return authStore.hasPermission(entity.value.permissions.create)
})

const canEdit = computed(() => {
  if (!entity.value?.permissions?.update) {
    return true
  }

  return authStore.hasPermission(entity.value.permissions.update)
})

const canDelete = computed(() => {
  if (!entity.value?.permissions?.delete) {
    return true
  }

  return authStore.hasPermission(entity.value.permissions.delete)
})

const formRules = computed(() => {
  const rules = {}

  for (const field of entity.value?.formFields || []) {
    if (!field.required) {
      continue
    }

    rules[field.key] = [{ required: true, message: `${field.label} is required.`, trigger: 'blur' }]
  }

  return rules
})

function clearObject(target) {
  for (const key of Object.keys(target)) {
    delete target[key]
  }
}

function setupFilters() {
  resetFilterModel(filters, entity.value?.filters || [], entity.value?.defaultFilters || {})
}

function setupForm(initial = null) {
  clearObject(form)
  const defaults = entity.value?.formDefaults || {}

  for (const field of entity.value?.formFields || []) {
    let value = defaults[field.key]

    if (initial && initial[field.key] !== undefined) {
      value = initial[field.key]
    }

    if (value === undefined) {
      value = field.multiple ? [] : field.type === 'switch' ? false : ''
    }

    form[field.key] = value
  }
}

function clearServerErrors() {
  clearObject(serverErrors)
}

function buildPayload() {
  const payload = {}

  for (const field of entity.value?.formFields || []) {
    let value = form[field.key]

    if (field.type === 'text' || field.type === 'textarea') {
      if (typeof value === 'string') {
        value = value.trim()
      }
    }

    if ((value === '' || value === undefined) && !field.required) {
      value = field.multiple ? [] : null
    }

    payload[field.key] = value
  }

  return typeof entity.value?.toPayload === 'function'
    ? entity.value.toPayload(payload)
    : payload
}

async function loadLookups() {
  if (lookupsRequest) {
    return lookupsRequest
  }

  lookupsLoading.value = true
  clearObject(lookups)
  lookupsLoaded.value = false

  const lookupEntityKey = entityKey.value
  lookupsRequest = (async () => {
    if (!entity.value?.loadLookups) {
      lookupsLoaded.value = true
      return
    }

    try {
      const loaded = await entity.value.loadLookups()

      if (lookupEntityKey !== entityKey.value) {
        return
      }

      Object.assign(lookups, loaded || {})
      lookupsLoaded.value = true
    } catch (error) {
      ElMessage.error(getApiErrorMessage(error, 'Unable to load dictionaries for the form.'))
    }
  })().finally(() => {
    lookupsLoading.value = false
    lookupsRequest = null
  })

  return lookupsRequest
}

async function ensureLookupsLoaded() {
  if (lookupsLoaded.value || !entity.value?.loadLookups) {
    return
  }

  await loadLookups()
}

async function load() {
  if (!entity.value) {
    return
  }

  try {
    await runPageRequest(() =>
      entity.value.list(
        cleanQuery({
          ...filters,
          page: page.value,
          per_page: perPage.value,
        }),
      ),
    )
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to load data.'))
  }
}

function applyFilters() {
  resetPage()
  return load()
}

function resetFilters() {
  setupFilters()
  resetPage()
  return load()
}

function changePage(value) {
  return handlePageChange(value, load)
}

function changePerPage(value) {
  return handlePerPageChange(value, load)
}

function openCreate() {
  editingId.value = null
  clearServerErrors()
  setupForm()
  void ensureLookupsLoaded()
  dialogVisible.value = true
}

async function openEdit(row) {
  editingId.value = row.id
  clearServerErrors()

  try {
    const [item] = await Promise.all([
      entity.value.get(row.id),
      ensureLookupsLoaded(),
    ])
    const mapped = typeof entity.value?.fromItem === 'function' ? entity.value.fromItem(item) : item
    setupForm(mapped || {})
    dialogVisible.value = true
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to open entry.'))
  }
}

async function openView(row) {
  try {
    viewItem.value = await entity.value.get(row.id)
    viewVisible.value = true
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to load entry details.'))
  }
}

async function submit() {
  const valid = await entityFormRef.value?.validate()
  if (!valid) {
    return
  }

  clearServerErrors()
  saving.value = true

  try {
    const payload = buildPayload()

    if (editingId.value) {
      const response = await entity.value.update(editingId.value, payload)
      ElMessage.success(response.message || 'Updated successfully.')
    } else {
      const response = await entity.value.create(payload)
      ElMessage.success(response.message || 'Created successfully.')
    }

    dialogVisible.value = false
    await load()
  } catch (error) {
    Object.assign(serverErrors, getValidationErrors(error))
    ElMessage.error(getApiErrorMessage(error, 'Unable to save entry.'))
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  try {
    await ElMessageBox.confirm('Delete this entry?', 'Confirmation', { type: 'warning' })

    const response = await entity.value.remove(row.id)
    ElMessage.success(response?.message || 'Deleted successfully.')
    await load()
  } catch (error) {
    if (error === 'cancel' || error === 'close') {
      return
    }

    ElMessage.error(getApiErrorMessage(error, 'Unable to delete entry.'))
  }
}

watch(
  entity,
  async () => {
    setupFilters()
    setupForm()
    clearServerErrors()
    clearObject(lookups)
    lookupsLoaded.value = false
    resetPagination()
    await load()
    void ensureLookupsLoaded()
  },
  { immediate: true },
)
</script>

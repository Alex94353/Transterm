<template>
  <div>
    <PageHeaderBlock
      title="Admin: Users"
      subtitle="User management, role assignment, and ban controls."
    />

    <EntityFiltersCard
      :model="filters"
      :filters="filterDefinitions"
      @change="handleFilterChange"
    >
      <template #actions>
        <el-button type="primary" @click="applyFilters">Apply</el-button>
        <el-button @click="resetFilters">Reset</el-button>
      </template>
    </EntityFiltersCard>

    <ServerTableCard
      :rows="rows"
      :columns="columns"
      :loading="loading"
      :renderer="renderCell"
      :page="page"
      :per-page="perPage"
      :total="total"
      :page-sizes="[10, 20, 50]"
      :actions-width="250"
      @update:page="changePage"
      @update:perPage="changePerPage"
    >
      <template #actions="{ row }">
        <el-button type="primary" link @click="openView(row)">View</el-button>
        <el-button type="primary" link @click="openEdit(row)">Edit</el-button>
        <el-button
          v-if="!row.banned"
          type="warning"
          link
          @click="ban(row)"
        >
          Ban
        </el-button>
        <el-button
          v-else
          type="success"
          link
          @click="unban(row)"
        >
          Unban
        </el-button>
      </template>
    </ServerTableCard>

    <EntityFormDialog
      ref="editDialogRef"
      v-model="editVisible"
      title="Edit user"
      submit-label="Save"
      width="680px"
      :form-model="editForm"
      :fields="editFields"
      :rules="editRules"
      :server-errors="serverErrors"
      :lookups="editLookups"
      :saving="editSaving"
      @cancel="editVisible = false"
      @submit="saveEdit"
    />

    <JsonPreviewDrawer
      v-model="viewVisible"
      title="User details"
      :data="viewUser"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { adminApi } from '@/api/admin'
import { publicApi } from '@/api/public'
import { cleanQuery } from '@/utils/object'
import { getApiErrorMessage, getValidationErrors } from '@/utils/errors'
import { getListItems } from '@/utils/pagination'
import { getCachedLookup } from '@/utils/lookupCache'
import { usePaginatedList } from '@/composables/usePaginatedList'
import { assignFilterValues } from '@/composables/useFilters'
import PageHeaderBlock from '@/components/common/PageHeaderBlock.vue'
import EntityFiltersCard from '@/components/common/EntityFiltersCard.vue'
import ServerTableCard from '@/components/common/ServerTableCard.vue'
import EntityFormDialog from '@/components/common/EntityFormDialog.vue'
import JsonPreviewDrawer from '@/components/common/JsonPreviewDrawer.vue'

const {
  loading,
  rows,
  page,
  perPage,
  total,
  runPageRequest,
  resetPage,
  handlePageChange,
  handlePerPageChange,
} = usePaginatedList(10)

const roleOptions = ref([])
const countryOptions = ref([])
const lookupsLoaded = ref(false)
const lookupsLoading = ref(false)
let lookupsRequest = null

const LOOKUP_CACHE_TTL_MS = 10 * 60 * 1000

const defaultFilters = {
  search: '',
  id: null,
  role_id: null,
  activated: '',
  banned: '',
  visible: '',
  country_id: null,
  id_order: 'desc',
  quick_status: null,
}
const filters = reactive({ ...defaultFilters })

const quickStatusOptions = [
  {
    value: 'activation',
    label: 'Activation',
    children: [
      { value: 'activated_true', label: 'Activated users' },
      { value: 'activated_false', label: 'Not activated users' },
    ],
  },
  {
    value: 'ban',
    label: 'Ban',
    children: [
      { value: 'banned_true', label: 'Banned users' },
      { value: 'banned_false', label: 'Not banned users' },
    ],
  },
  {
    value: 'visibility',
    label: 'Visibility',
    children: [
      { value: 'visible_true', label: 'Visible profiles' },
      { value: 'visible_false', label: 'Hidden profiles' },
    ],
  },
]

const filterDefinitions = computed(() => [
  { key: 'search', label: 'Search', type: 'text', placeholder: 'username, email, name...' },
  { key: 'id', label: 'ID', type: 'number' },
  {
    key: 'quick_status',
    label: 'Quick status filter',
    type: 'cascader',
    options: quickStatusOptions,
    props: {
      expandTrigger: 'hover',
      emitPath: false,
    },
  },
  {
    key: 'role_id',
    label: 'Role',
    type: 'select',
    options: roleOptions.value,
  },
  {
    key: 'activated',
    label: 'Activated',
    type: 'select',
    options: [
      { label: 'Yes', value: true },
      { label: 'No', value: false },
    ],
  },
  {
    key: 'banned',
    label: 'Banned',
    type: 'select',
    options: [
      { label: 'Yes', value: true },
      { label: 'No', value: false },
    ],
  },
  {
    key: 'visible',
    label: 'Visible',
    type: 'select',
    options: [
      { label: 'Yes', value: true },
      { label: 'No', value: false },
    ],
  },
  {
    key: 'country_id',
    label: 'Country',
    type: 'select',
    options: countryOptions.value,
  },
  {
    key: 'id_order',
    label: 'ID order',
    type: 'select',
    options: [
      { label: 'Desc', value: 'desc' },
      { label: 'Asc', value: 'asc' },
    ],
  },
])

const columns = [
  { key: 'id', label: 'ID', width: 90 },
  { key: 'username', label: 'Username', minWidth: 140 },
  { key: 'email', label: 'Email', minWidth: 190 },
  { key: 'full_name', label: 'Name', minWidth: 170 },
  { key: 'roles', label: 'Roles', minWidth: 160 },
  { key: 'activated', label: 'Activated', width: 100 },
  { key: 'banned', label: 'Banned', width: 100 },
  { key: 'country', label: 'Country', minWidth: 140 },
]

const editVisible = ref(false)
const editDialogRef = ref(null)
const editSaving = ref(false)
const editingId = ref(null)

const viewVisible = ref(false)
const viewUser = ref(null)

const serverErrors = reactive({})
const editForm = reactive({
  role_id: null,
  username: '',
  email: '',
  name: '',
  surname: '',
  language: '',
  activated: false,
  visible: false,
  country_id: null,
})

const editFields = [
  { key: 'role_id', label: 'Role', type: 'select', lookup: 'roles' },
  { key: 'username', label: 'Username', type: 'text' },
  { key: 'email', label: 'Email', type: 'text' },
  { key: 'name', label: 'Name', type: 'text' },
  { key: 'surname', label: 'Surname', type: 'text' },
  { key: 'language', label: 'Language', type: 'text' },
  { key: 'country_id', label: 'Country', type: 'select', lookup: 'countries' },
  { key: 'activated', label: 'Activated', type: 'switch' },
  { key: 'visible', label: 'Visible', type: 'switch' },
]

const editRules = {
  email: [{ type: 'email', message: 'Invalid email.', trigger: 'blur' }],
}

const editLookups = computed(() => ({
  roles: roleOptions.value,
  countries: countryOptions.value,
}))

function renderCell(row, column) {
  switch (column.key) {
    case 'full_name':
      return `${row.name || ''} ${row.surname || ''}`.trim()
    case 'roles':
      return (row.roles || []).map((role) => role.name).join(', ') || '-'
    case 'activated':
      return row.activated ? 'Yes' : 'No'
    case 'banned':
      return row.banned ? 'Yes' : 'No'
    case 'country':
      return row.country?.name || '-'
    default:
      return row[column.key] ?? '-'
  }
}

function clearServerErrors() {
  Object.keys(serverErrors).forEach((key) => {
    delete serverErrors[key]
  })
}

function applyQuickStatus(value) {
  if (!value) {
    return
  }

  if (value.startsWith('activated_')) {
    filters.activated = value === 'activated_true'
  }

  if (value.startsWith('banned_')) {
    filters.banned = value === 'banned_true'
  }

  if (value.startsWith('visible_')) {
    filters.visible = value === 'visible_true'
  }
}

function handleFilterChange(payload) {
  if (payload.key === 'quick_status') {
    applyQuickStatus(payload.value)
  }
}

async function loadLookups() {
  if (lookupsRequest) {
    return lookupsRequest
  }

  lookupsLoading.value = true
  lookupsRequest = (async () => {
    try {
      const [roles, countries] = await Promise.all([
        getCachedLookup(
          'admin:roles',
          () => adminApi.roles.list({ per_page: 200, id_order: 'asc' }),
          { ttlMs: LOOKUP_CACHE_TTL_MS },
        ),
        getCachedLookup(
          'public:countries',
          () => publicApi.listCountries({ per_page: 300 }),
          { ttlMs: LOOKUP_CACHE_TTL_MS },
        ),
      ])

      roleOptions.value = getListItems(roles).map((role) => ({
        label: role.name,
        value: role.id,
      }))

      countryOptions.value = getListItems(countries).map((country) => ({
        label: country.name,
        value: country.id,
      }))
      lookupsLoaded.value = true
    } catch (error) {
      ElMessage.error(getApiErrorMessage(error, 'Unable to load dictionaries.'))
    }
  })().finally(() => {
    lookupsLoading.value = false
    lookupsRequest = null
  })

  return lookupsRequest
}

async function ensureLookupsLoaded() {
  if (lookupsLoaded.value) {
    return
  }

  await loadLookups()
}

async function load() {
  try {
    await runPageRequest(() =>
      adminApi.listUsers(
        cleanQuery({
          search: filters.search,
          id: filters.id,
          role_id: filters.role_id,
          activated: filters.activated,
          banned: filters.banned,
          visible: filters.visible,
          country_id: filters.country_id,
          id_order: filters.id_order,
          page: page.value,
          per_page: perPage.value,
        }),
      ),
    )
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to load users.'))
  }
}

function applyFilters() {
  resetPage()
  return load()
}

function resetFilters() {
  assignFilterValues(filters, defaultFilters)
  resetPage()
  return load()
}

function changePage(value) {
  return handlePageChange(value, load)
}

function changePerPage(value) {
  return handlePerPageChange(value, load)
}

async function openView(row) {
  try {
    viewUser.value = await adminApi.getUser(row.id)
    viewVisible.value = true
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to open user details.'))
  }
}

async function openEdit(row) {
  clearServerErrors()
  editingId.value = row.id

  try {
    const [user] = await Promise.all([
      adminApi.getUser(row.id),
      ensureLookupsLoaded(),
    ])

    editForm.role_id = user?.roles?.[0]?.id || null
    editForm.username = user?.username || ''
    editForm.email = user?.email || ''
    editForm.name = user?.name || ''
    editForm.surname = user?.surname || ''
    editForm.language = user?.language || ''
    editForm.activated = Boolean(user?.activated)
    editForm.visible = Boolean(user?.visible)
    editForm.country_id = user?.country_id || user?.country?.id || null

    editVisible.value = true
  } catch (error) {
    ElMessage.error(getApiErrorMessage(error, 'Unable to open user form.'))
  }
}

async function saveEdit() {
  if (!editingId.value) {
    return
  }

  clearServerErrors()
  const valid = await editDialogRef.value?.validate()
  if (!valid) {
    return
  }

  editSaving.value = true

  try {
    const response = await adminApi.updateUser(editingId.value, {
      role_id: editForm.role_id,
      username: editForm.username,
      email: editForm.email,
      name: editForm.name,
      surname: editForm.surname || null,
      language: editForm.language || null,
      activated: editForm.activated,
      visible: editForm.visible,
      country_id: editForm.country_id || null,
    })

    ElMessage.success(response.message || 'User updated.')
    editVisible.value = false
    await load()
  } catch (error) {
    Object.assign(serverErrors, getValidationErrors(error))
    ElMessage.error(getApiErrorMessage(error, 'Unable to update user.'))
  } finally {
    editSaving.value = false
  }
}

async function ban(row) {
  try {
    const promptResult = await ElMessageBox.prompt(
      'Optional ban reason',
      `Ban user #${row.id}`,
      {
        inputPlaceholder: 'Reason',
        confirmButtonText: 'Ban',
        cancelButtonText: 'Cancel',
      },
    ).catch((error) => {
      if (error === 'cancel' || error === 'close') {
        return { canceled: true }
      }

      throw error
    })

    if (promptResult?.canceled) {
      return
    }

    const reason = promptResult?.value || null

    const response = await adminApi.banUser(row.id, {
      ban_reason: reason,
    })

    ElMessage.success(response.message || 'User banned.')
    await load()
  } catch (error) {
    if (error?.canceled) {
      return
    }

    ElMessage.error(getApiErrorMessage(error, 'Unable to ban user.'))
  }
}

async function unban(row) {
  try {
    await ElMessageBox.confirm(`Unban user #${row.id}?`, 'Confirmation', {
      type: 'warning',
    })

    const response = await adminApi.unbanUser(row.id)
    ElMessage.success(response.message || 'User unbanned.')
    await load()
  } catch (error) {
    if (error === 'cancel' || error === 'close') {
      return
    }

    ElMessage.error(getApiErrorMessage(error, 'Unable to unban user.'))
  }
}

onMounted(async () => {
  await load()
  void ensureLookupsLoaded()
})
</script>

<template>
  <main-layout>
    <div class="teacher-tools-page">
      <el-card>
        <template #header>
          <div class="card-header">
            <span class="title">Teacher Tools</span>
          </div>
        </template>
        <el-alert
          title="Teacher tools show only eligible glossaries and students. IDs are internal and used only by action buttons."
          type="info"
          :closable="false"
          show-icon
        />
      </el-card>

      <el-card v-if="canApproveGlossary" shadow="never" class="tool-card">
        <template #header>
          <span>Pending Glossaries</span>
        </template>

        <div class="toolbar">
          <el-input
            v-model="glossarySearch"
            placeholder="Search glossary title, student name, or email"
            clearable
            @keyup.enter="handleGlossarySearch"
            @clear="handleGlossarySearch"
          />
          <el-select v-model="glossaryStatus" style="width: 170px" @change="handleGlossarySearch">
            <el-option label="Pending" value="pending" />
            <el-option label="Approved" value="approved" />
            <el-option label="All" value="all" />
          </el-select>
          <el-button type="primary" @click="handleGlossarySearch">Search</el-button>
        </div>

        <el-table :data="glossaries" stripe style="width: 100%" :loading="isGlossariesLoading">
          <el-table-column label="Glossary Title" min-width="220" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.title || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="Student" min-width="200" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.owner?.full_name || row.owner?.name || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="Email" min-width="220" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.owner?.email || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="Created At" width="180">
            <template #default="{ row }">
              {{ formatDate(row.created_at) }}
            </template>
          </el-table-column>
          <el-table-column label="Status" width="120">
            <template #default="{ row }">
              <el-tag :type="row.approved ? 'success' : 'warning'">
                {{ row.approved ? 'Approved' : 'Pending' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="170">
            <template #default="{ row }">
              <el-space :size="8">
                <el-button type="primary" text size="small" @click="openGlossary(row.id)">
                  View
                </el-button>
                <el-button
                  type="success"
                  text
                  size="small"
                  :disabled="row.approved"
                  :loading="approvingGlossaryId === row.id"
                  @click="approveGlossary(row)"
                >
                  Approve
                </el-button>
              </el-space>
            </template>
          </el-table-column>
        </el-table>

        <el-pagination
          class="pagination"
          background
          layout="total, sizes, prev, pager, next"
          :total="glossaryPagination.total"
          :current-page="glossaryPagination.page"
          :page-size="glossaryPagination.perPage"
          :page-sizes="[5, 10, 20]"
          @current-change="handleGlossaryPageChange"
          @size-change="handleGlossaryPageSizeChange"
        />
      </el-card>

      <el-card v-if="canAssignEditor" shadow="never" class="tool-card">
        <template #header>
          <span>Students Eligible For Editor</span>
        </template>

        <div class="toolbar">
          <el-input
            v-model="studentSearch"
            placeholder="Search student by first name, last name, or email"
            clearable
            @keyup.enter="handleStudentSearch"
            @clear="handleStudentSearch"
          />
          <el-switch
            v-model="studentsWithoutEditorOnly"
            inline-prompt
            active-text="Without Editor"
            inactive-text="All Students"
            @change="handleStudentSearch"
          />
          <el-button type="primary" @click="handleStudentSearch">Search</el-button>
        </div>

        <el-table :data="students" stripe style="width: 100%" :loading="isStudentsLoading">
          <el-table-column label="Full Name" min-width="220" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.full_name || row.name || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="Email" min-width="240" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.email || '-' }}
            </template>
          </el-table-column>
          <el-table-column label="Base Role" width="140">
            <template #default="{ row }">
              {{ row.base_role || 'Student' }}
            </template>
          </el-table-column>
          <el-table-column label="Editor" width="120">
            <template #default="{ row }">
              <el-tag :type="row.has_editor_role ? 'success' : 'info'">
                {{ row.has_editor_role ? 'Yes' : 'No' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="160">
            <template #default="{ row }">
              <el-button
                type="warning"
                text
                size="small"
                :disabled="row.has_editor_role"
                :loading="assigningEditorId === row.id"
                @click="assignEditor(row)"
              >
                Assign Editor
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <el-pagination
          class="pagination"
          background
          layout="total, sizes, prev, pager, next"
          :total="studentPagination.total"
          :current-page="studentPagination.page"
          :page-size="studentPagination.perPage"
          :page-sizes="[5, 10, 20]"
          @current-change="handleStudentPageChange"
          @size-change="handleStudentPageSizeChange"
        />
      </el-card>

      <el-dialog
        v-model="glossaryDetailVisible"
        title="Glossary Details"
        width="760px"
        destroy-on-close
      >
        <el-skeleton :loading="glossaryDetailLoading" animated :rows="8">
          <template #default>
            <template v-if="glossaryDetail">
              <el-descriptions :column="1" border>
                <el-descriptions-item label="ID">
                  {{ glossaryDetail.id }}
                </el-descriptions-item>
                <el-descriptions-item label="Status">
                  <el-tag :type="glossaryDetail.approved ? 'success' : 'warning'">
                    {{ glossaryDetail.approved ? 'Approved' : 'Pending' }}
                  </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="Student">
                  {{ glossaryDetail.owner?.full_name || glossaryDetail.owner?.name || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="Email">
                  {{ glossaryDetail.owner?.email || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="Field">
                  {{ glossaryDetail.field?.name || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="Language Pair">
                  {{
                    [glossaryDetail.language_pair?.source_language?.code, glossaryDetail.language_pair?.target_language?.code]
                      .filter(Boolean)
                      .join(' -> ') || '-'
                  }}
                </el-descriptions-item>
                <el-descriptions-item label="Terms Count">
                  {{ glossaryDetail.terms_count ?? 0 }}
                </el-descriptions-item>
                <el-descriptions-item label="Created At">
                  {{ formatDate(glossaryDetail.created_at) }}
                </el-descriptions-item>
              </el-descriptions>

              <el-divider>Translations</el-divider>

              <el-table
                :data="glossaryDetail.translations || []"
                stripe
                style="width: 100%"
              >
                <el-table-column label="Language" width="170">
                  <template #default="{ row }">
                    {{ row.language?.code || row.language?.name || '-' }}
                  </template>
                </el-table-column>
                <el-table-column label="Title" min-width="220" show-overflow-tooltip>
                  <template #default="{ row }">
                    {{ row.title || '-' }}
                  </template>
                </el-table-column>
                <el-table-column label="Description" min-width="260" show-overflow-tooltip>
                  <template #default="{ row }">
                    {{ row.description || '-' }}
                  </template>
                </el-table-column>
              </el-table>
            </template>
            <el-empty v-else description="No glossary details to display" />
          </template>
        </el-skeleton>
      </el-dialog>
    </div>
  </main-layout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import MainLayout from '../components/Layout/MainLayout.vue'
import teacherService from '../services/teacherService'
import { isRequestCanceled } from '../services/api'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()

const canApproveGlossary = computed(() => authStore.isAdmin || authStore.hasPermission('glossary.approve'))
const canAssignEditor = computed(() => authStore.isAdmin || authStore.hasPermission('editor.assign'))

const glossarySearch = ref('')
const glossaryStatus = ref('pending')
const glossaries = ref([])
const isGlossariesLoading = ref(false)
const approvingGlossaryId = ref(null)
let latestGlossaryRequestId = 0

const glossaryPagination = reactive({
  page: 1,
  perPage: 10,
  total: 0,
})
const glossaryDetailVisible = ref(false)
const glossaryDetailLoading = ref(false)
const glossaryDetail = ref(null)

const studentSearch = ref('')
const studentsWithoutEditorOnly = ref(true)
const students = ref([])
const isStudentsLoading = ref(false)
const assigningEditorId = ref(null)
let latestStudentRequestId = 0

const studentPagination = reactive({
  page: 1,
  perPage: 10,
  total: 0,
})

onMounted(() => {
  if (canApproveGlossary.value) {
    fetchGlossaries()
  }

  if (canAssignEditor.value) {
    fetchStudents()
  }
})

const fetchGlossaries = async () => {
  const requestId = ++latestGlossaryRequestId
  isGlossariesLoading.value = true
  try {
    const params = {
      page: glossaryPagination.page,
      per_page: glossaryPagination.perPage,
      status: glossaryStatus.value,
    }

    if (glossarySearch.value.trim()) {
      params.search = glossarySearch.value.trim()
    }

    const response = await teacherService.getGlossaries(params, {
      cancelKey: 'teacher:glossaries:list',
    })
    if (requestId !== latestGlossaryRequestId) return

    const payload = response.data || {}
    glossaries.value = payload.data || []
    glossaryPagination.total = payload?.meta?.total ?? payload.total ?? glossaries.value.length
    glossaryPagination.page = payload?.meta?.current_page ?? glossaryPagination.page
    glossaryPagination.perPage = payload?.meta?.per_page ?? glossaryPagination.perPage
  } catch (err) {
    if (requestId !== latestGlossaryRequestId || isRequestCanceled(err)) return
    ElMessage.error(err.response?.data?.message || 'Failed to load glossaries')
  } finally {
    if (requestId === latestGlossaryRequestId) {
      isGlossariesLoading.value = false
    }
  }
}

const fetchStudents = async () => {
  const requestId = ++latestStudentRequestId
  isStudentsLoading.value = true
  try {
    const params = {
      page: studentPagination.page,
      per_page: studentPagination.perPage,
      without_editor: studentsWithoutEditorOnly.value,
    }

    if (studentSearch.value.trim()) {
      params.search = studentSearch.value.trim()
    }

    const response = await teacherService.getStudents(params, {
      cancelKey: 'teacher:students:list',
    })
    if (requestId !== latestStudentRequestId) return

    const payload = response.data || {}
    students.value = payload.data || []
    studentPagination.total = payload?.meta?.total ?? payload.total ?? students.value.length
    studentPagination.page = payload?.meta?.current_page ?? studentPagination.page
    studentPagination.perPage = payload?.meta?.per_page ?? studentPagination.perPage
  } catch (err) {
    if (requestId !== latestStudentRequestId || isRequestCanceled(err)) return
    ElMessage.error(err.response?.data?.message || 'Failed to load students')
  } finally {
    if (requestId === latestStudentRequestId) {
      isStudentsLoading.value = false
    }
  }
}

const handleGlossarySearch = () => {
  glossaryPagination.page = 1
  fetchGlossaries()
}

const handleStudentSearch = () => {
  studentPagination.page = 1
  fetchStudents()
}

const handleGlossaryPageChange = (page) => {
  glossaryPagination.page = page
  fetchGlossaries()
}

const handleGlossaryPageSizeChange = (size) => {
  glossaryPagination.perPage = size
  glossaryPagination.page = 1
  fetchGlossaries()
}

const handleStudentPageChange = (page) => {
  studentPagination.page = page
  fetchStudents()
}

const handleStudentPageSizeChange = (size) => {
  studentPagination.perPage = size
  studentPagination.page = 1
  fetchStudents()
}

const approveGlossary = async (row) => {
  if (!row?.id) return

  approvingGlossaryId.value = row.id
  try {
    const response = await teacherService.approveGlossary(row.id)
    ElMessage.success(response?.data?.message || 'Glossary approved successfully')
    fetchGlossaries()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to approve glossary')
  } finally {
    approvingGlossaryId.value = null
  }
}

const assignEditor = async (row) => {
  if (!row?.id) return

  assigningEditorId.value = row.id
  try {
    const response = await teacherService.assignEditorToStudent(row.id)
    ElMessage.success(response?.data?.message || 'Editor role assigned successfully')
    fetchStudents()
  } catch (err) {
    ElMessage.error(err.response?.data?.message || 'Failed to assign Editor role')
  } finally {
    assigningEditorId.value = null
  }
}

const openGlossary = async (id) => {
  if (!id) return

  glossaryDetailVisible.value = true
  glossaryDetailLoading.value = true
  glossaryDetail.value = null

  try {
    const response = await teacherService.getGlossary(id, {
      cancelKey: `teacher:glossary:detail:${id}`,
    })
    glossaryDetail.value = response?.data?.glossary || null
  } catch (err) {
    if (isRequestCanceled(err)) return

    glossaryDetailVisible.value = false
    ElMessage.error(err.response?.data?.message || 'Failed to load glossary details')
  } finally {
    glossaryDetailLoading.value = false
  }
}

const formatDate = (value) => {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}
</script>

<style scoped>
.teacher-tools-page {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.title {
  font-size: 18px;
  font-weight: 700;
}

.tool-card {
  border: 1px solid var(--tt-border);
}

.toolbar {
  display: grid;
  grid-template-columns: 1fr auto auto;
  gap: 12px;
  margin-bottom: 14px;
}

.pagination {
  margin-top: 14px;
  justify-content: flex-end;
}

@media (max-width: 900px) {
  .toolbar {
    grid-template-columns: 1fr;
  }
}
</style>

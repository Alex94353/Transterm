<template>
  <admin-page-shell title="Manage Comments">
    <template #toolbar>
      <admin-toolbar label="Filter:">
        <admin-filter-select
          v-model="spamFilter"
          width="160px"
          :options="spamFilterOptions"
          @change="handleFilterChange"
        />
        <admin-search-bar
          v-model="searchQuery"
          placeholder="Search comments"
          width="220px"
          @search="handleSearch"
          @clear="handleClearSearch"
        />
        <admin-id-order-control v-model="idOrder" @change="handleFilterChange" />
      </admin-toolbar>
    </template>

    <el-table :data="comments" stripe style="width: 100%" :loading="isLoading">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="Comment" min-width="320" show-overflow-tooltip>
          <template #default="{ row }">
            {{ row.body }}
          </template>
        </el-table-column>
        <el-table-column label="User" width="180">
          <template #default="{ row }">
            {{ row.user?.name || row.user?.username || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Term ID" width="100">
          <template #default="{ row }">
            {{ row.term_id || row.term?.id || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Status" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_spam ? 'danger' : 'success'">
              {{ row.is_spam ? 'Spam' : 'Normal' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="260">
          <template #default="{ row }">
            <admin-table-actions
              :row="row"
              :show-edit="false"
              delete-confirm="Delete this comment?"
              @delete="({ id }) => handleDelete(id)"
            >
              <template #prepend="{ row: currentRow }">
                <el-button
                  v-if="!currentRow.is_spam"
                  type="warning"
                  text
                  size="small"
                  @click="handleMarkSpam(currentRow.id)"
                >
                  Mark spam
                </el-button>
                <el-button
                  v-else
                  type="primary"
                  text
                  size="small"
                  @click="handleUnmarkSpam(currentRow.id)"
                >
                  Unmark
                </el-button>
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
  </admin-page-shell>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import AdminPageShell from '../../components/Admin/AdminPageShell.vue'
import AdminFilterSelect from '../../components/Admin/AdminFilterSelect.vue'
import AdminPagination from '../../components/Admin/AdminPagination.vue'
import AdminIdOrderControl from '../../components/Admin/AdminIdOrderControl.vue'
import AdminSearchBar from '../../components/Admin/AdminSearchBar.vue'
import AdminTableActions from '../../components/Admin/AdminTableActions.vue'
import AdminToolbar from '../../components/Admin/AdminToolbar.vue'
import { useAdminList } from '../../composables/useAdminList'
import { isRequestCanceled } from '../../services/api'
import adminService from '../../services/adminService'

const comments = ref([])
const isLoading = ref(false)
const spamFilter = ref('all')
const spamFilterOptions = [
  { label: 'All', value: 'all' },
  { label: 'Only spam', value: 'spam' },
  { label: 'Only normal', value: 'normal' },
]
const idOrder = ref('desc')
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
let latestCommentsRequestId = 0

onMounted(() => {
  fetchComments()
})

const fetchComments = async () => {
  const requestId = ++latestCommentsRequestId
  isLoading.value = true
  try {
    const params = {
      page: pagination.page,
      per_page: pagination.perPage,
      id_order: idOrder.value,
    }

    if (spamFilter.value === 'spam') {
      params.is_spam = true
    } else if (spamFilter.value === 'normal') {
      params.is_spam = false
    }

    if (appliedSearch.value.trim()) {
      params.search = appliedSearch.value.trim()
    }

    const response = await adminService.getComments(params, {
      cancelKey: 'admin:comments:list',
    })
    if (requestId !== latestCommentsRequestId) return
    const payload = response.data || {}
    comments.value = payload.data || []
    pagination.total = payload?.meta?.total ?? payload.total ?? comments.value.length
    pagination.page = payload?.meta?.current_page ?? pagination.page
    pagination.perPage = payload?.meta?.per_page ?? pagination.perPage
  } catch (err) {
    if (requestId !== latestCommentsRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load comments')
  } finally {
    if (requestId === latestCommentsRequestId) {
      isLoading.value = false
    }
  }
}

bindDebouncedSearch(fetchComments)

const handleMarkSpam = async (commentId) => {
  try {
    await adminService.markSpam(commentId)
    ElMessage.success('Comment marked as spam')
    fetchComments()
  } catch {
    ElMessage.error('Failed to mark comment as spam')
  }
}

const handleUnmarkSpam = async (commentId) => {
  try {
    await adminService.unmarkSpam(commentId)
    ElMessage.success('Comment unmarked')
    fetchComments()
  } catch {
    ElMessage.error('Failed to unmark comment')
  }
}

const handleDelete = async (commentId) => {
  try {
    await adminService.deleteComment(commentId)
    ElMessage.success('Comment deleted')
    fetchComments()
  } catch {
    ElMessage.error('Failed to delete comment')
  }
}

const handleFilterChange = () => {
  runFiltersChange(fetchComments)
}

const handlePageChange = (page) => {
  runPageChange(page, fetchComments)
}

const handlePageSizeChange = (size) => {
  runPageSizeChange(size, fetchComments)
}

const handleSearch = () => {
  runSearch(fetchComments)
}

const handleClearSearch = () => {
  runClearSearch(fetchComments)
}
</script>

<style scoped>
</style>

<template>
  <admin-page-shell :title="dashboardTitle" :show-back="false">

    <el-row :gutter="20" style="margin-bottom: 30px">
        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-number">{{ stats.users }}</div>
              <div class="stat-label">Total Users</div>
            </div>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-number">{{ stats.glossaries }}</div>
              <div class="stat-label">Glossaries</div>
            </div>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-number">{{ stats.terms }}</div>
              <div class="stat-label">Terms</div>
            </div>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-number">{{ stats.comments }}</div>
              <div class="stat-label">Comments</div>
            </div>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-number">{{ stats.pendingEditorRequests }}</div>
              <div class="stat-label">Pending Editor Requests</div>
            </div>
          </el-card>
        </el-col>
    </el-row>

    <el-divider />

    <h3>Management</h3>
    <el-row :gutter="20">
        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/glossaries')">
            <template #header>
              <el-icon><document-copy /></el-icon>
              Glossaries
            </template>
            <p>Manage glossaries and language pairs</p>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/terms')">
            <template #header>
              <el-icon><menu-icon /></el-icon>
              Terms
            </template>
            <p>Manage terms and translations</p>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/users')">
            <template #header>
              <el-icon><user /></el-icon>
              Users
            </template>
            <p>Manage user accounts and roles</p>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/editor-role-requests')">
            <template #header>
              <el-icon><bell /></el-icon>
              Editor Requests
            </template>
            <p>
              Review access requests from User/Student accounts
              <span v-if="stats.pendingEditorRequests > 0">
                ({{ stats.pendingEditorRequests }} pending)
              </span>
            </p>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/languages')">
            <template #header>
              <el-icon><connection /></el-icon>
              Languages
            </template>
            <p>Manage languages and language pairs</p>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/fields')">
            <template #header>
              <el-icon><collection-tag /></el-icon>
              Fields
            </template>
            <p>Manage terminology fields</p>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/field-groups')">
            <template #header>
              <el-icon><folder-opened /></el-icon>
              Field Groups
            </template>
            <p>Manage field group categories</p>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/references')">
            <template #header>
              <el-icon><link-icon /></el-icon>
              References
            </template>
            <p>Manage terminology references</p>
          </el-card>
        </el-col>

        <el-col v-if="authStore.isAdmin" :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/editor/comments')">
            <template #header>
              <el-icon><chat-dot-round /></el-icon>
              Comments
            </template>
            <p>Moderate user comments and spam</p>
          </el-card>
        </el-col>
    </el-row>
  </admin-page-shell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage, ElNotification } from 'element-plus'
import AdminPageShell from '../components/Admin/AdminPageShell.vue'
import adminService from '../services/adminService'
import { isRequestCanceled } from '../services/api'
import { useAuthStore } from '../stores/auth'
import {
  DocumentCopy,
  Menu as MenuIcon,
  User,
  Connection,
  CollectionTag,
  FolderOpened,
  Link as LinkIcon,
  ChatDotRound,
  Bell,
} from '@element-plus/icons-vue'

const authStore = useAuthStore()
const dashboardTitle = computed(() => (authStore.isAdmin ? 'Admin Dashboard' : 'Editor Dashboard'))
const stats = ref({
  users: 0,
  glossaries: 0,
  terms: 0,
  comments: 0,
  pendingEditorRequests: 0,
})
let latestStatsRequestId = 0
let lastPendingNotificationCount = 0

const extractTotal = (response) => {
  const payload = response?.data
  if (!payload) return 0

  if (typeof payload.total === 'number') return payload.total
  if (typeof payload?.meta?.total === 'number') return payload.meta.total
  if (typeof payload?.data?.total === 'number') return payload.data.total

  if (Array.isArray(payload.data)) return payload.data.length
  return 0
}

const fetchStats = async () => {
  const requestId = ++latestStatsRequestId
  try {
    if (authStore.isAdmin) {
      const [usersRes, glossariesRes, termsRes, commentsRes, editorRequestsRes] = await Promise.all([
        adminService.getUsers({ per_page: 1 }, { cancelKey: 'admin:dashboard:users' }),
        adminService.adminGetGlossaries({ per_page: 1 }, { cancelKey: 'admin:dashboard:glossaries' }),
        adminService.adminGetTerms({ per_page: 1 }, { cancelKey: 'admin:dashboard:terms' }),
        adminService.getComments({ per_page: 1 }, { cancelKey: 'admin:dashboard:comments' }),
        adminService.getEditorRoleRequests(
          { status: 'pending', per_page: 1 },
          { cancelKey: 'admin:dashboard:editor-role-requests' },
        ),
      ])

      if (requestId !== latestStatsRequestId) return

      stats.value = {
        users: extractTotal(usersRes),
        glossaries: extractTotal(glossariesRes),
        terms: extractTotal(termsRes),
        comments: extractTotal(commentsRes),
        pendingEditorRequests: extractTotal(editorRequestsRes),
      }

      if (
        stats.value.pendingEditorRequests > 0 &&
        stats.value.pendingEditorRequests !== lastPendingNotificationCount
      ) {
        ElNotification.warning({
          title: 'Editor Requests',
          message: `You have ${stats.value.pendingEditorRequests} pending editor request(s).`,
          duration: 6000,
        })
      }
      lastPendingNotificationCount = stats.value.pendingEditorRequests

      return
    }

    const [glossariesRes, termsRes] = await Promise.all([
      adminService.adminGetGlossaries({ per_page: 1 }, { cancelKey: 'admin:dashboard:glossaries' }),
      adminService.adminGetTerms({ per_page: 1 }, { cancelKey: 'admin:dashboard:terms' }),
    ])

    if (requestId !== latestStatsRequestId) return

    stats.value = {
      users: 0,
      glossaries: extractTotal(glossariesRes),
      terms: extractTotal(termsRes),
      comments: 0,
      pendingEditorRequests: 0,
    }
  } catch (err) {
    if (requestId !== latestStatsRequestId || isRequestCanceled(err)) return
    ElMessage.error('Failed to load dashboard statistics')
  }
}

onMounted(() => {
  fetchStats()
})
</script>

<style scoped>
.stat-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.stat-card:hover {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.stat-content {
  text-align: center;
  padding: 20px;
}

.stat-number {
  font-size: 32px;
  font-weight: bold;
  color: var(--tt-accent);
  margin-bottom: 10px;
}

.stat-label {
  font-size: 14px;
  color: var(--tt-muted);
}

h3 {
  font-size: 18px;
  font-weight: bold;
  margin-bottom: 20px;
  color: var(--tt-ink);
}

.admin-menu-card {
  cursor: pointer;
  transition: all 0.3s ease;
  height: 100%;
}

.admin-menu-card:hover {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.admin-menu-card p {
  color: var(--tt-muted);
  font-size: 14px;
}

:deep(.el-card__header) {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--tt-ink);
  font-weight: bold;
  padding: 15px;
}

:deep(.el-icon) {
  font-size: 20px;
}
</style>

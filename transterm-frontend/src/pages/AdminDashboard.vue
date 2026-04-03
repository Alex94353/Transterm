<template>
  <admin-page-shell title="Admin Dashboard" :show-back="false">

    <el-row :gutter="20" style="margin-bottom: 30px">
        <el-col :xs="24" :sm="12" :md="6">
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

        <el-col :xs="24" :sm="12" :md="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-number">{{ stats.comments }}</div>
              <div class="stat-label">Comments</div>
            </div>
          </el-card>
        </el-col>
    </el-row>

    <el-divider />

    <h3>Management</h3>
    <el-row :gutter="20">
        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/admin/glossaries')">
            <template #header>
              <el-icon><document-copy /></el-icon>
              Glossaries
            </template>
            <p>Manage glossaries and language pairs</p>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/admin/terms')">
            <template #header>
              <el-icon><menu-icon /></el-icon>
              Terms
            </template>
            <p>Manage terms and translations</p>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/admin/users')">
            <template #header>
              <el-icon><user /></el-icon>
              Users
            </template>
            <p>Manage user accounts and roles</p>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/admin/languages')">
            <template #header>
              <el-icon><connection /></el-icon>
              Languages
            </template>
            <p>Manage languages and language pairs</p>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/admin/references')">
            <template #header>
              <el-icon><link-icon /></el-icon>
              References
            </template>
            <p>Manage terminology references</p>
          </el-card>
        </el-col>

        <el-col :xs="24" :sm="12" :md="8">
          <el-card class="admin-menu-card" @click="$router.push('/admin/comments')">
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
import { onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import AdminPageShell from '../components/Admin/AdminPageShell.vue'
import adminService from '../services/adminService'
import {
  DocumentCopy,
  Menu as MenuIcon,
  User,
  Connection,
  Link as LinkIcon,
  ChatDotRound,
} from '@element-plus/icons-vue'

const stats = ref({
  users: 0,
  glossaries: 0,
  terms: 0,
  comments: 0,
})

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
  try {
    const [usersRes, glossariesRes, termsRes, commentsRes] = await Promise.all([
      adminService.getUsers({ per_page: 1 }),
      adminService.adminGetGlossaries({ per_page: 1 }),
      adminService.adminGetTerms({ per_page: 1 }),
      adminService.getComments({ per_page: 1 }),
    ])

    stats.value = {
      users: extractTotal(usersRes),
      glossaries: extractTotal(glossariesRes),
      terms: extractTotal(termsRes),
      comments: extractTotal(commentsRes),
    }
  } catch {
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

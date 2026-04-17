<template>
  <div class="navbar">
    <el-container>
      <el-header class="navbar-header">
        <div class="navbar-logo">
          <router-link to="/" class="logo-text">
            <strong>Transterm</strong>
          </router-link>
        </div>

        <el-menu
          :default-active="activeMenu"
          mode="horizontal"
          class="navbar-menu"
          :ellipsis="false"
          @select="handleMenuSelect"
        >
          <el-menu-item index="/glossaries">
            <router-link to="/glossaries">Glossaries</router-link>
          </el-menu-item>

          <el-menu-item v-if="authStore.isAuthenticated" index="/my-comments">
            <router-link to="/my-comments">My Comments</router-link>
          </el-menu-item>

          <el-menu-item v-if="canSeeTeacherTools" index="/teacher/tools">
            <router-link to="/teacher/tools">Teacher Tools</router-link>
          </el-menu-item>

          <el-menu-item v-if="authStore.canAccessManagement" :index="managementBasePath">
            <router-link :to="managementBasePath">{{ managementLabel }}</router-link>
          </el-menu-item>

          <el-sub-menu v-if="authStore.isAuthenticated" index="user">
            <template #title>
              <el-icon><avatar /></el-icon>
              {{ authStore.user?.name }}
            </template>
            <el-menu-item index="/profile">
              <router-link to="/profile">Profile</router-link>
            </el-menu-item>
            <el-menu-item index="/logout" @click="handleLogout">Logout</el-menu-item>
          </el-sub-menu>

          <el-menu-item v-else index="/login">
            <router-link to="/login">Login</router-link>
          </el-menu-item>
        </el-menu>

        <el-button
          class="theme-toggle"
          text
          :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'"
          @click="toggleTheme"
        >
          <el-icon>
            <Sunny v-if="theme === 'dark'" />
            <Moon v-else />
          </el-icon>
        </el-button>
      </el-header>
    </el-container>
  </div>
</template>

<script setup>
defineOptions({
  name: 'AppNavbar',
})

import { computed, onBeforeMount, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { Avatar, Moon, Sunny } from '@element-plus/icons-vue'

const router = useRouter()
const authStore = useAuthStore()
const theme = ref('light')

const applyTheme = (value) => {
  theme.value = value
  document.documentElement.classList.toggle('dark-theme', value === 'dark')
  localStorage.setItem('theme', value)
}

const toggleTheme = () => {
  applyTheme(theme.value === 'dark' ? 'light' : 'dark')
}

onBeforeMount(() => {
  const savedTheme = localStorage.getItem('theme') || 'light'
  applyTheme(savedTheme)
})

const activeMenu = computed(() => {
  const path = router.currentRoute.value.path

  if (path.startsWith('/admin')) {
    return '/admin'
  }

  if (path.startsWith('/editor')) {
    return '/editor'
  }

  if (path.startsWith('/teacher/tools')) {
    return '/teacher/tools'
  }

  if (path.startsWith('/glossaries')) {
    return '/glossaries'
  }

  return path
})

const managementLabel = computed(() => {
  if (authStore.isAdmin) {
    return 'Admin'
  }

  return 'Editor'
})

const managementBasePath = computed(() => (authStore.isAdmin ? '/admin' : '/editor'))

const canSeeTeacherTools = computed(() => {
  if (!authStore.isAuthenticated) return false

  return (
    authStore.isAdmin ||
    authStore.hasPermission?.('glossary.approve') ||
    authStore.hasPermission?.('editor.assign')
  )
})

const handleMenuSelect = (key) => {
  if (key && key.startsWith('/')) {
    router.push(key)
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.navbar {
  background-color: var(--tt-surface);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.navbar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.navbar-logo {
  margin-right: 40px;
}

.logo-text {
  font-size: 24px;
  font-weight: bold;
  color: var(--tt-accent);
  text-decoration: none;
}

.navbar-logo a:hover {
  color: var(--tt-accent-hover);
}

.navbar-menu {
  flex: 1;
}

.theme-toggle {
  margin-left: 12px;
  color: var(--tt-ink);
}

.theme-toggle :deep(.el-icon) {
  font-size: 18px;
}

:deep(.el-menu--horizontal) {
  border-bottom: none;
  background: transparent;
}

:deep(.el-menu--horizontal > .el-menu-item),
:deep(.el-menu--horizontal > .el-sub-menu .el-sub-menu__title) {
  color: var(--tt-ink);
}

:deep(.el-menu--horizontal > .el-menu-item.is-active),
:deep(.el-menu--horizontal > .el-sub-menu.is-active .el-sub-menu__title) {
  color: var(--tt-accent) !important;
}
</style>

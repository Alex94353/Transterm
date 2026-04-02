<template>
  <el-container class="app-shell">
    <el-aside class="app-aside" width="260px">
      <div class="brand">Transterm</div>

      <el-menu :default-active="activePath" router class="app-menu">
        <el-menu-item v-for="item in publicMenu" :key="item.path" :index="item.path">
          <span>{{ item.label }}</span>
        </el-menu-item>

        <el-menu-item
          v-if="authStore.isAuthenticated"
          index="/profile"
        >
          Profile
        </el-menu-item>
        <el-menu-item
          v-if="authStore.isAuthenticated"
          index="/my-comments"
        >
          My comments
        </el-menu-item>

        <el-sub-menu v-if="authStore.canAccessAdmin" index="admin">
          <template #title>Admin</template>
          <el-menu-item
            v-for="item in adminMenu"
            :key="item.path"
            :index="item.path"
          >
            {{ item.label }}
          </el-menu-item>
        </el-sub-menu>
      </el-menu>
    </el-aside>

    <el-container>
      <el-header class="app-header">
        <div class="header-left">
          <h2>{{ currentRouteTitle }}</h2>
        </div>

        <div class="header-right">
          <template v-if="authStore.isAuthenticated">
            <el-tag effect="plain">
              {{ userDisplay }}
            </el-tag>
            <el-button type="danger" plain size="small" @click="handleLogout">
              Logout
            </el-button>
          </template>

          <template v-else>
            <el-button size="small" @click="$router.push('/login')">Login</el-button>
            <el-button size="small" type="primary" @click="$router.push('/register')">
              Register
            </el-button>
          </template>
        </div>
      </el-header>

      <el-main class="app-main">
        <router-view />
      </el-main>
    </el-container>
  </el-container>
</template>

<script setup>
import { computed } from 'vue'
import { ElMessage } from 'element-plus'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const publicMenu = [
  { path: '/terms', label: 'Terms' },
  { path: '/glossaries', label: 'Glossaries' },
  { path: '/references', label: 'References' },
]

const fullAdminMenu = [
  { path: '/admin/comments', label: 'Comment moderation' },
  { path: '/admin/users', label: 'Users', permission: 'user.view' },
  { path: '/admin/glossaries', label: 'Glossaries', permission: 'glossary.view-any' },
  { path: '/admin/terms', label: 'Terms', permission: 'term.view-any' },
  { path: '/admin/references', label: 'References', permission: 'reference.view-any' },
]

const adminMenu = computed(() =>
  fullAdminMenu.filter((item) => !item.permission || authStore.hasPermission(item.permission)),
)

const activePath = computed(() => {
  if (route.path.startsWith('/admin/')) {
    return route.path
  }

  if (route.path.startsWith('/terms/')) {
    return '/terms'
  }
  if (route.path.startsWith('/glossaries/')) {
    return '/glossaries'
  }
  if (route.path.startsWith('/references/')) {
    return '/references'
  }

  return route.path
})

const currentRouteTitle = computed(() => route.meta?.title || 'Transterm')

const userDisplay = computed(
  () =>
    authStore.user?.username ||
    authStore.user?.email ||
    `${authStore.user?.name || ''} ${authStore.user?.surname || ''}`.trim(),
)

async function handleLogout() {
  await authStore.logout()
  await router.push('/login')
  ElMessage.success('Logged out.')
}
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
}

.app-aside {
  border-right: 1px solid var(--tt-border);
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(10px);
  padding-top: 0.75rem;
}

.brand {
  margin: 0 0.85rem 0.9rem;
  padding: 0.85rem 0.9rem;
  border-radius: 12px;
  background: var(--tt-accent-soft);
  color: #0d4674;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.app-menu {
  border-right: none;
  background: transparent;
}

.app-header {
  background: rgba(255, 255, 255, 0.72);
  border-bottom: 1px solid var(--tt-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 1rem;
}

.header-left h2 {
  margin: 0;
  font-size: 1.1rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.app-main {
  padding: 1rem;
}

@media (max-width: 920px) {
  .app-aside {
    width: 210px !important;
  }
}
</style>

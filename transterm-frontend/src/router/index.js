import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const HomePage = () => import('../pages/HomePage.vue')
const LoginPage = () => import('../pages/LoginPage.vue')
const RegisterPage = () => import('../pages/RegisterPage.vue')
const GlossariesPage = () => import('../pages/GlossariesPage.vue')
const GlossaryDetailPage = () => import('../pages/GlossaryDetailPage.vue')
const TermDetailPage = () => import('../pages/TermDetailPage.vue')
const UserCommentsPage = () => import('../pages/UserCommentsPage.vue')
const UserProfilePage = () => import('../pages/UserProfilePage.vue')
const TeacherToolsPage = () => import('../pages/TeacherToolsPage.vue')
const AdminDashboard = () => import('../pages/AdminDashboard.vue')
const AdminComments = () => import('../pages/Admin/AdminComments.vue')
const AdminFields = () => import('../pages/Admin/AdminFields.vue')
const AdminFieldGroups = () => import('../pages/Admin/AdminFieldGroups.vue')
const AdminGlossaries = () => import('../pages/Admin/AdminGlossaries.vue')
const AdminLanguages = () => import('../pages/Admin/AdminLanguages.vue')
const AdminReferences = () => import('../pages/Admin/AdminReferences.vue')
const AdminTerms = () => import('../pages/Admin/AdminTerms.vue')
const AdminUsers = () => import('../pages/Admin/AdminUsers.vue')
const AdminEditorRoleRequests = () => import('../pages/Admin/AdminEditorRoleRequests.vue')
const AdminAuditLogs = () => import('../pages/Admin/AdminAuditLogs.vue')
const NotFoundPage = () => import('../pages/NotFoundPage.vue')

const buildManagementRoutes = (prefix) => [
  {
    path: prefix,
    component: AdminDashboard,
    meta: { title: 'Management Dashboard', requiresManagement: true },
  },
  {
    path: `${prefix}/glossaries`,
    component: AdminGlossaries,
    meta: { title: 'Management Glossaries', requiresManagement: true },
  },
  {
    path: `${prefix}/terms`,
    component: AdminTerms,
    meta: { title: 'Management Terms', requiresManagement: true },
  },
  {
    path: `${prefix}/users`,
    component: AdminUsers,
    meta: { title: 'Admin Users', requiresAdmin: true },
  },
  {
    path: `${prefix}/editor-role-requests`,
    component: AdminEditorRoleRequests,
    meta: { title: 'Editor Role Requests', requiresAdmin: true },
  },
  {
    path: `${prefix}/audit-logs`,
    component: AdminAuditLogs,
    meta: { title: 'Audit Logs', requiresAdmin: true },
  },
  {
    path: `${prefix}/languages`,
    component: AdminLanguages,
    meta: { title: 'Admin Languages', requiresAdmin: true },
  },
  {
    path: `${prefix}/references`,
    component: AdminReferences,
    meta: { title: 'Admin References', requiresAdmin: true },
  },
  {
    path: `${prefix}/fields`,
    component: AdminFields,
    meta: { title: 'Admin Fields', requiresAdmin: true },
  },
  {
    path: `${prefix}/field-groups`,
    component: AdminFieldGroups,
    meta: { title: 'Admin Field Groups', requiresAdmin: true },
  },
  {
    path: `${prefix}/comments`,
    component: AdminComments,
    meta: { title: 'Admin Comments', requiresAdmin: true },
  },
]

const routes = [
  {
    path: '/',
    component: HomePage,
    meta: { title: 'Home' },
  },
  {
    path: '/login',
    component: LoginPage,
    meta: { title: 'Login', guestOnly: true },
  },
  {
    path: '/register',
    component: RegisterPage,
    meta: { title: 'Register', guestOnly: true },
  },
  {
    path: '/glossaries',
    component: GlossariesPage,
    meta: { title: 'Glossaries' },
  },
  {
    path: '/glossaries/:id',
    component: GlossaryDetailPage,
    meta: { title: 'Glossary Details' },
  },
  {
    path: '/terms/:id',
    component: TermDetailPage,
    meta: { title: 'Term Details' },
  },
  {
    path: '/my-comments',
    component: UserCommentsPage,
    meta: { title: 'My Comments', requiresAuth: true },
  },
  {
    path: '/profile',
    component: UserProfilePage,
    meta: { title: 'Profile', requiresAuth: true },
  },
  {
    path: '/teacher/tools',
    component: TeacherToolsPage,
    meta: { title: 'Teacher Tools', requiresTeacherTools: true },
  },
  ...buildManagementRoutes('/admin'),
  ...buildManagementRoutes('/editor'),
  {
    path: '/:pathMatch(.*)*',
    component: NotFoundPage,
    meta: { title: 'Not Found' },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (authStore.token && !authStore.user) {
    try {
      await authStore.getCurrentUser()
    } catch {
      // token is cleared in store on failure
    }
  }

  const isManagementPath = to.path === '/admin'
    || to.path.startsWith('/admin/')
    || to.path === '/editor'
    || to.path.startsWith('/editor/')

  if (isManagementPath && authStore.isAuthenticated && authStore.canAccessManagement) {
    const preferredPrefix = authStore.isAdmin ? '/admin' : '/editor'
    const alternativePrefix = preferredPrefix === '/admin' ? '/editor' : '/admin'

    if (to.path === alternativePrefix || to.path.startsWith(`${alternativePrefix}/`)) {
      return {
        path: preferredPrefix + to.path.slice(alternativePrefix.length),
        query: to.query,
        hash: to.hash,
      }
    }
  }

  if (to.meta.requiresAdmin) {
    if (!authStore.isAuthenticated) {
      return { path: '/login', query: { redirect: to.fullPath } }
    }
    if (!authStore.isAdmin) {
      return '/'
    }
  }

  if (to.meta.requiresManagement) {
    if (!authStore.isAuthenticated) {
      return { path: '/login', query: { redirect: to.fullPath } }
    }
    if (!authStore.canAccessManagement) {
      return '/'
    }
  }

  if (to.meta.requiresTeacherTools) {
    if (!authStore.isAuthenticated) {
      return { path: '/login', query: { redirect: to.fullPath } }
    }

    const canUseTeacherTools =
      authStore.isAdmin ||
      authStore.hasPermission?.('glossary.approve') ||
      authStore.hasPermission?.('editor.assign')

    if (!canUseTeacherTools) {
      return '/'
    }
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return '/'
  }

  return true
})

router.afterEach((to) => {
  const title = to.meta?.title ? `${to.meta.title} - Transterm` : 'Transterm'
  document.title = title
})


export default router

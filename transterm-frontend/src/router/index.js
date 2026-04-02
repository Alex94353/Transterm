import { createRouter, createWebHistory } from 'vue-router'
import { ElMessage } from 'element-plus'
import { APP_TITLE } from '@/config/env'
import { useAuthStore } from '@/stores/auth'

const AppLayout = () => import('@/layouts/AppLayout.vue')
const AuthLayout = () => import('@/layouts/AuthLayout.vue')
const LoginView = () => import('@/views/auth/LoginView.vue')
const RegisterView = () => import('@/views/auth/RegisterView.vue')
const PublicEntityListView = () => import('@/views/public/PublicEntityListView.vue')
const PublicEntityDetailView = () => import('@/views/public/PublicEntityDetailView.vue')
const ProfileView = () => import('@/views/user/ProfileView.vue')
const CommentsListView = () => import('@/views/shared/CommentsListView.vue')
const AdminCrudEntityView = () => import('@/views/admin/AdminCrudEntityView.vue')
const AdminUsersView = () => import('@/views/admin/AdminUsersView.vue')

const routes = [
  {
    path: '/login',
    component: AuthLayout,
    meta: { requiresGuest: true, title: 'Login' },
    children: [
      {
        path: '',
        component: LoginView,
        meta: { requiresGuest: true, title: 'Login' },
      },
    ],
  },
  {
    path: '/register',
    component: AuthLayout,
    meta: { requiresGuest: true, title: 'Register' },
    children: [
      {
        path: '',
        component: RegisterView,
        meta: { requiresGuest: true, title: 'Register' },
      },
    ],
  },
  {
    path: '/',
    component: AppLayout,
    children: [
      {
        path: '',
        redirect: '/terms',
      },
      {
        path: 'terms',
        component: PublicEntityListView,
        meta: { entityKey: 'terms', title: 'Terms' },
      },
      {
        path: 'terms/:id',
        component: PublicEntityDetailView,
        meta: { entityKey: 'terms', title: 'Term details' },
      },
      {
        path: 'glossaries',
        component: PublicEntityListView,
        meta: { entityKey: 'glossaries', title: 'Glossaries' },
      },
      {
        path: 'glossaries/:id',
        component: PublicEntityDetailView,
        meta: { entityKey: 'glossaries', title: 'Glossary details' },
      },
      {
        path: 'references',
        component: PublicEntityListView,
        meta: { entityKey: 'references', title: 'References' },
      },
      {
        path: 'references/:id',
        component: PublicEntityDetailView,
        meta: { entityKey: 'references', title: 'Reference details' },
      },
      {
        path: 'profile',
        component: ProfileView,
        meta: { requiresAuth: true, title: 'My profile' },
      },
      {
        path: 'my-comments',
        component: CommentsListView,
        meta: { requiresAuth: true, title: 'My comments', commentScope: 'user' },
      },
      {
        path: 'admin/comments',
        component: CommentsListView,
        meta: { requiresAuth: true, requiresAdmin: true, title: 'Admin comments', commentScope: 'admin' },
      },
      {
        path: 'admin/users',
        component: AdminUsersView,
        meta: {
          requiresAuth: true,
          requiresAdmin: true,
          permission: 'user.view',
          title: 'Admin users',
        },
      },
      {
        path: 'admin/glossaries',
        component: AdminCrudEntityView,
        meta: {
          requiresAuth: true,
          requiresAdmin: true,
          permission: 'glossary.view-any',
          entityKey: 'glossaries',
          title: 'Admin glossaries',
        },
      },
      {
        path: 'admin/terms',
        component: AdminCrudEntityView,
        meta: {
          requiresAuth: true,
          requiresAdmin: true,
          permission: 'term.view-any',
          entityKey: 'terms',
          title: 'Admin terms',
        },
      },
      {
        path: 'admin/references',
        component: AdminCrudEntityView,
        meta: {
          requiresAuth: true,
          requiresAdmin: true,
          permission: 'reference.view-any',
          entityKey: 'references',
          title: 'Admin references',
        },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/terms',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  await authStore.init()

  if (to.meta?.requiresGuest && authStore.isAuthenticated) {
    return '/terms'
  }

  if (to.meta?.requiresAuth && !authStore.isAuthenticated) {
    return {
      path: '/login',
      query: { redirect: to.fullPath },
    }
  }

  if (to.meta?.requiresAdmin && !authStore.canAccessAdmin) {
    ElMessage.warning('Admin access is required.')
    return '/terms'
  }

  if (to.meta?.permission && !authStore.hasPermission(to.meta.permission)) {
    ElMessage.warning('Access denied by permission policy.')
    return '/terms'
  }

  return true
})

router.afterEach((to) => {
  const title = to.meta?.title ? `${to.meta.title} | ${APP_TITLE}` : APP_TITLE
  document.title = title
})

export default router

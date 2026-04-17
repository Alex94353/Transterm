import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

const shared = vi.hoisted(() => ({
  router: {
    currentRoute: { value: { path: '/' } },
    push: vi.fn(),
  },
  authStore: {
    isAuthenticated: true,
    canAccessManagement: true,
    isAdmin: true,
    hasPermission: vi.fn(),
    user: { name: 'Alex' },
    logout: vi.fn(),
  },
}))

vi.mock('vue-router', () => ({
  useRouter: () => shared.router,
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => shared.authStore,
}))

import Navbar from '@/components/Layout/Navbar.vue'

const buildWrapper = () =>
  mount(Navbar, {
    global: {
      stubs: {
        RouterLink: { template: '<a><slot /></a>' },
        ElContainer: { template: '<div><slot /></div>' },
        ElHeader: { template: '<header><slot /></header>' },
        ElMenu: {
          props: ['defaultActive'],
          template: '<nav data-test="menu" :data-active="defaultActive"><slot /></nav>',
        },
        ElMenuItem: { template: '<div class="menu-item"><slot /></div>' },
        ElSubMenu: { template: '<div class="submenu"><slot /><slot name="title" /></div>' },
        ElButton: { template: '<button><slot /></button>' },
        ElIcon: { template: '<i><slot /></i>' },
        Avatar: true,
        Moon: true,
        Sunny: true,
      },
    },
  })

describe('Navbar', () => {
  beforeEach(() => {
    shared.router.currentRoute.value = { path: '/' }
    shared.router.push.mockReset()
    shared.authStore.logout.mockReset()
    shared.authStore.isAuthenticated = true
    shared.authStore.canAccessManagement = true
    shared.authStore.isAdmin = true
    shared.authStore.hasPermission.mockReset()
    shared.authStore.hasPermission.mockReturnValue(false)
    shared.authStore.user = { name: 'Alex' }
  })

  it('shows Admin label for admin users', () => {
    const wrapper = buildWrapper()

    expect(wrapper.text()).toContain('Admin')
  })

  it('shows Editor label for non-admin managers', () => {
    shared.authStore.isAdmin = false

    const wrapper = buildWrapper()

    expect(wrapper.text()).toContain('Editor')
    expect(wrapper.text()).not.toContain('Admin Dashboard')
  })

  it('hides management menu when access is denied', () => {
    shared.authStore.canAccessManagement = false

    const wrapper = buildWrapper()

    expect(wrapper.text()).not.toContain('Editor')
    expect(wrapper.text()).not.toContain('Admin')
  })

  it('normalizes active menu for editor sub-routes', () => {
    shared.router.currentRoute.value = { path: '/editor/glossaries' }

    const wrapper = buildWrapper()

    expect(wrapper.get('[data-test="menu"]').attributes('data-active')).toBe('/editor')
  })

  it('shows Teacher Tools item for users with glossary.approve permission', () => {
    shared.authStore.isAdmin = false
    shared.authStore.canAccessManagement = false
    shared.authStore.hasPermission.mockImplementation((permissionName) => permissionName === 'glossary.approve')

    const wrapper = buildWrapper()

    expect(wrapper.text()).toContain('Teacher Tools')
  })
})

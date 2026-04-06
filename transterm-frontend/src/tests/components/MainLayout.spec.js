import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

const shared = vi.hoisted(() => ({
  authStore: {
    token: null,
    user: null,
    getCurrentUser: vi.fn(),
  },
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => shared.authStore,
}))

import MainLayout from '@/components/Layout/MainLayout.vue'

const buildWrapper = () =>
  mount(MainLayout, {
    global: {
      stubs: {
        Navbar: { template: '<nav>Navbar</nav>' },
        ElMain: { template: '<main><slot /></main>' },
        ElFooter: { template: '<footer><slot /></footer>' },
        RouterView: { template: '<div />' },
      },
    },
  })

describe('MainLayout', () => {
  beforeEach(() => {
    shared.authStore.token = null
    shared.authStore.user = null
    shared.authStore.getCurrentUser.mockReset()
    shared.authStore.getCurrentUser.mockResolvedValue({ id: 1 })
  })

  it('loads current user on mount when token exists and user is missing', async () => {
    shared.authStore.token = 'token-1'
    shared.authStore.user = null

    buildWrapper()

    await Promise.resolve()
    expect(shared.authStore.getCurrentUser).toHaveBeenCalledTimes(1)
  })

  it('does not load current user when token is absent', async () => {
    shared.authStore.token = null

    buildWrapper()

    await Promise.resolve()
    expect(shared.authStore.getCurrentUser).not.toHaveBeenCalled()
  })
})

import { describe, expect, it, vi } from 'vitest'

async function createRouterWithAuthState(overrides = {}) {
  vi.resetModules()

  const authState = {
    token: null,
    user: null,
    isAuthenticated: false,
    isAdmin: false,
    canAccessManagement: false,
    getCurrentUser: vi.fn().mockResolvedValue(null),
    ...overrides,
  }

  vi.doMock('@/stores/auth', () => ({
    useAuthStore: () => authState,
  }))

  const module = await import('@/router')
  return { router: module.default, authState }
}

describe('router', () => {
  it('exposes expected public and management routes', async () => {
    const { router } = await createRouterWithAuthState()
    const routePaths = router.getRoutes().map((route) => route.path)

    expect(routePaths).toContain('/')
    expect(routePaths).toContain('/login')
    expect(routePaths).toContain('/register')
    expect(routePaths).toContain('/glossaries')
    expect(routePaths).toContain('/editor')
    expect(routePaths).toContain('/editor/users')
    expect(routePaths).toContain('/:pathMatch(.*)*')
  })

  it('redirects legacy /admin path to /editor and preserves query/hash', async () => {
    const { router } = await createRouterWithAuthState()
    const adminRoute = router.getRoutes().find((route) => route.path === '/admin/:pathMatch(.*)*')

    expect(adminRoute).toBeTruthy()
    expect(typeof adminRoute.redirect).toBe('function')

    const redirected = adminRoute.redirect({
      params: { pathMatch: ['users', '42'] },
      query: { tab: 'roles' },
      hash: '#section',
    })

    expect(redirected).toEqual({
      path: '/editor/users/42',
      query: { tab: 'roles' },
      hash: '#section',
    })
  })

  it(
    'redirects unauthorized requiresAuth route to login with redirect query',
    async () => {
      const { router } = await createRouterWithAuthState({
        isAuthenticated: false,
      })

      await router.push('/profile')

      expect(router.currentRoute.value.path).toBe('/login')
      expect(router.currentRoute.value.query.redirect).toBe('/profile')
    },
    15000
  )

  it('blocks non-admin users from admin-only routes', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: true,
      isAdmin: false,
      canAccessManagement: true,
    })

    await router.push('/editor/users')

    expect(router.currentRoute.value.path).toBe('/')
  })

  it('blocks users without management access from editor dashboard', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: true,
      isAdmin: false,
      canAccessManagement: false,
    })

    await router.push('/editor')

    expect(router.currentRoute.value.path).toBe('/')
  })

  it('redirects authenticated guest-only navigation to home', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: true,
      isAdmin: false,
      canAccessManagement: false,
    })

    await router.push('/login')

    expect(router.currentRoute.value.path).toBe('/')
  })

  it('calls getCurrentUser when token exists but user is missing', async () => {
    const getCurrentUser = vi.fn().mockResolvedValue({ id: 11 })
    const { router } = await createRouterWithAuthState({
      token: 'stale-token',
      user: null,
      isAuthenticated: true,
      getCurrentUser,
    })

    await router.push('/glossaries')

    expect(getCurrentUser).toHaveBeenCalledTimes(1)
  })

  it('updates document title after navigation', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: false,
    })

    await router.push('/login')

    expect(document.title).toBe('Login - Transterm')
  })
})

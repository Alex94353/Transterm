import { describe, expect, it, vi } from 'vitest'

async function createRouterWithAuthState(overrides = {}) {
  vi.resetModules()

  const authState = {
    token: null,
    user: null,
    isAuthenticated: false,
    isAdmin: false,
    canAccessManagement: false,
    hasPermission: vi.fn().mockReturnValue(false),
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
    expect(routePaths).toContain('/admin')
    expect(routePaths).toContain('/admin/users')
    expect(routePaths).toContain('/admin/audit-logs')
    expect(routePaths).toContain('/editor')
    expect(routePaths).toContain('/editor/users')
    expect(routePaths).toContain('/editor/audit-logs')
    expect(routePaths).toContain('/teacher/tools')
    expect(routePaths).toContain('/:pathMatch(.*)*')
  })

  it(
    'redirects admin users from /editor/* to /admin/*',
    async () => {
      const { router } = await createRouterWithAuthState({
        isAuthenticated: true,
        isAdmin: true,
        canAccessManagement: true,
      })

      await router.push('/editor/users?tab=roles#section')

      expect(router.currentRoute.value.path).toBe('/admin/users')
      expect(router.currentRoute.value.query.tab).toBe('roles')
      expect(router.currentRoute.value.hash).toBe('#section')
    },
    15000
  )

  it('redirects editor users from /admin/* to /editor/*', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: true,
      isAdmin: false,
      canAccessManagement: true,
    })

    await router.push('/admin/terms?search=a#s')

    expect(router.currentRoute.value.path).toBe('/editor/terms')
    expect(router.currentRoute.value.query.search).toBe('a')
    expect(router.currentRoute.value.hash).toBe('#s')
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

    await router.push('/admin/users')

    expect(router.currentRoute.value.path).toBe('/')
  })

  it('blocks users without management access from editor dashboard', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: true,
      isAdmin: false,
      canAccessManagement: false,
    })

    await router.push('/admin')

    expect(router.currentRoute.value.path).toBe('/')
  })

  it('allows teacher tools route for users with teacher permissions', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: true,
      hasPermission: vi.fn().mockImplementation((permissionName) => permissionName === 'glossary.approve'),
    })

    await router.push('/teacher/tools')

    expect(router.currentRoute.value.path).toBe('/teacher/tools')
  })

  it('blocks teacher tools route without required permissions', async () => {
    const { router } = await createRouterWithAuthState({
      isAuthenticated: true,
      hasPermission: vi.fn().mockReturnValue(false),
    })

    await router.push('/teacher/tools')

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

  it(
    'loads major public, user, and management routes for smoke coverage',
    async () => {
      const { router } = await createRouterWithAuthState({
        isAuthenticated: true,
        isAdmin: true,
        canAccessManagement: true,
        token: 'admin-token',
        user: { id: 1, name: 'Admin' },
      })

      const routesToVisit = [
        '/admin',
        '/admin/glossaries',
        '/admin/terms',
        '/admin/users',
        '/admin/editor-role-requests',
        '/admin/audit-logs',
        '/admin/languages',
        '/admin/references',
        '/admin/fields',
        '/admin/field-groups',
        '/admin/comments',
        '/teacher/tools',
        '/glossaries/1',
        '/terms/1',
        '/my-comments',
        '/profile',
      ]

      for (const route of routesToVisit) {
        await router.push(route)
      }

      expect(router.currentRoute.value.path).toBe('/profile')
      expect(router.getRoutes().length).toBeGreaterThan(10)
    },
    30000
  )
})

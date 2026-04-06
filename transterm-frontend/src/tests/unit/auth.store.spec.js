import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const mockAuthService = vi.hoisted(() => ({
  register: vi.fn(),
  login: vi.fn(),
  resendVerificationEmail: vi.fn(),
  logout: vi.fn(),
  getMe: vi.fn(),
}))

vi.mock('@/services/authService', () => ({
  default: mockAuthService,
}))

import { useAuthStore } from '@/stores/auth'

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    Object.values(mockAuthService).forEach((fn) => fn.mockReset())
    vi.spyOn(console, 'error').mockImplementation(() => {})
  })

  it('starts unauthenticated without token', () => {
    const store = useAuthStore()

    expect(store.isAuthenticated).toBe(false)
    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
  })

  it('returns requiresActivation on inactive register response', async () => {
    const store = useAuthStore()
    mockAuthService.register.mockResolvedValue({
      data: {
        user: { id: 1, activated: false },
        verification_email_sent: true,
      },
    })

    const response = await store.register({ email: 'qa@student.ukf.sk' })

    expect(response.requiresActivation).toBe(true)
    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(localStorage.getItem('auth_token')).toBeNull()
  })

  it('stores token and refreshes user from /me on successful register', async () => {
    const store = useAuthStore()
    mockAuthService.register.mockResolvedValue({
      data: {
        access_token: 'token-1',
        user: { id: 1, activated: true, name: 'A' },
        can_access_management: false,
      },
    })
    mockAuthService.getMe.mockResolvedValue({
      data: {
        user: { id: 1, name: 'Updated' },
        can_access_management: true,
      },
    })

    await store.register({ email: 'qa@ukf.sk' })

    expect(store.token).toBe('token-1')
    expect(store.user.name).toBe('Updated')
    expect(store.canAccessManagement).toBe(true)
    expect(localStorage.getItem('auth_token')).toBe('token-1')
  })

  it('normalizes email on resend verification request', async () => {
    const store = useAuthStore()
    mockAuthService.resendVerificationEmail.mockResolvedValue({ data: { message: 'ok' } })

    await store.resendVerificationEmail('  Qa_User@Student.UKF.sk  ')

    expect(mockAuthService.resendVerificationEmail).toHaveBeenCalledWith({
      email: 'qa_user@student.ukf.sk',
    })
  })

  it('stores token and user on login', async () => {
    const store = useAuthStore()
    mockAuthService.login.mockResolvedValue({
      data: {
        access_token: 'token-login',
        user: {
          id: 8,
          name: 'Editor',
          roles: [{ name: 'Editor', permissions: [{ name: 'editor.access' }] }],
        },
        can_access_management: true,
      },
    })
    mockAuthService.getMe.mockRejectedValue(new Error('ignore /me failure'))

    await store.login('editor@ukf.sk', 'Password123!')

    expect(store.isAuthenticated).toBe(true)
    expect(store.token).toBe('token-login')
    expect(store.user.name).toBe('Editor')
    expect(store.canAccessManagement).toBe(true)
  })

  it('canAccessManagement falls back to role/permission when backend flag missing', async () => {
    const store = useAuthStore()
    mockAuthService.login.mockResolvedValue({
      data: {
        access_token: 'token-login',
        user: {
          id: 2,
          name: 'Admin',
          roles: [{ name: 'Admin', permissions: [] }],
        },
      },
    })
    mockAuthService.getMe.mockRejectedValue(new Error('ignore /me failure'))

    await store.login('admin', 'Password123!')

    expect(store.canAccessManagement).toBe(true)
    expect(store.isAdmin).toBe(true)
  })

  it('logout clears local state even when API call fails', async () => {
    const store = useAuthStore()
    store.token = 'token-a'
    store.user = { id: 3 }
    localStorage.setItem('auth_token', 'token-a')
    mockAuthService.logout.mockRejectedValue(new Error('network'))

    await store.logout()

    expect(store.token).toBeNull()
    expect(store.user).toBeNull()
    expect(localStorage.getItem('auth_token')).toBeNull()
  })

  it('getCurrentUser returns null when token is absent', async () => {
    const store = useAuthStore()

    const user = await store.getCurrentUser()

    expect(user).toBeNull()
    expect(mockAuthService.getMe).not.toHaveBeenCalled()
  })

  it('getCurrentUser clears stale token on failure', async () => {
    const store = useAuthStore()
    store.token = 'stale-token'
    localStorage.setItem('auth_token', 'stale-token')
    mockAuthService.getMe.mockRejectedValue(new Error('Unauthorized'))

    await expect(store.getCurrentUser()).rejects.toThrow('Unauthorized')
    expect(store.token).toBeNull()
    expect(localStorage.getItem('auth_token')).toBeNull()
  })
})

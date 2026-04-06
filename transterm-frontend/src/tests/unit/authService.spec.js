import { beforeEach, describe, expect, it, vi } from 'vitest'

const mockApi = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
}))

vi.mock('@/services/api', () => ({
  default: mockApi,
}))

import authService from '@/services/authService'

describe('authService', () => {
  beforeEach(() => {
    mockApi.get.mockReset()
    mockApi.post.mockReset()
  })

  it('calls register endpoint', () => {
    const payload = { email: 'qa@student.ukf.sk' }
    authService.register(payload)

    expect(mockApi.post).toHaveBeenCalledWith('/auth/register', payload)
  })

  it('calls login endpoint', () => {
    const payload = { login: 'user', password: 'secret' }
    authService.login(payload)

    expect(mockApi.post).toHaveBeenCalledWith('/auth/login', payload)
  })

  it('calls resend verification endpoint', () => {
    const payload = { email: 'qa@ukf.sk' }
    authService.resendVerificationEmail(payload)

    expect(mockApi.post).toHaveBeenCalledWith('/auth/email/verification-notification', payload)
  })

  it('calls logout endpoint', () => {
    authService.logout()

    expect(mockApi.post).toHaveBeenCalledWith('/auth/logout')
  })

  it('calls me endpoint', () => {
    authService.getMe()

    expect(mockApi.get).toHaveBeenCalledWith('/auth/me')
  })
})

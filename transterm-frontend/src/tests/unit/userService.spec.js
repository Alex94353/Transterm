import { beforeEach, describe, expect, it, vi } from 'vitest'

const mockApi = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}))

vi.mock('@/services/api', () => ({
  default: mockApi,
}))

import userService from '@/services/userService'

describe('userService', () => {
  beforeEach(() => {
    mockApi.get.mockReset()
    mockApi.post.mockReset()
    mockApi.put.mockReset()
    mockApi.delete.mockReset()
  })

  it('calls profile endpoints', () => {
    userService.getProfile()
    userService.updateProfile({ name: 'Alex' })

    expect(mockApi.get).toHaveBeenCalledWith('/user/profile')
    expect(mockApi.put).toHaveBeenCalledWith('/user/profile', { name: 'Alex' })
  })

  it('calls editor role request endpoints', () => {
    userService.getLatestEditorRoleRequest()
    userService.requestEditorRole({ reason: 'domain expert' })
    userService.requestEditorRole()

    expect(mockApi.get).toHaveBeenCalledWith('/user/editor-role-requests/latest')
    expect(mockApi.post).toHaveBeenNthCalledWith(1, '/user/editor-role-requests', { reason: 'domain expert' })
    expect(mockApi.post).toHaveBeenNthCalledWith(2, '/user/editor-role-requests', {})
  })

  it('calls user comment endpoints', () => {
    userService.getUserComments({ page: 3 })
    userService.updateComment(22, { body: 'Updated comment' })
    userService.deleteComment(22)

    expect(mockApi.get).toHaveBeenCalledWith('/user/comments', { params: { page: 3 } })
    expect(mockApi.put).toHaveBeenCalledWith('/user/comments/22', { body: 'Updated comment' })
    expect(mockApi.delete).toHaveBeenCalledWith('/user/comments/22')
  })
})

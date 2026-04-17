import { beforeEach, describe, expect, it, vi } from 'vitest'

const mockApi = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
}))

vi.mock('@/services/api', () => ({
  default: mockApi,
}))

import adminService from '@/services/adminService'

describe('adminService', () => {
  beforeEach(() => {
    mockApi.get.mockReset()
    mockApi.post.mockReset()
    mockApi.put.mockReset()
    mockApi.patch.mockReset()
    mockApi.delete.mockReset()
  })

  it('uses editor endpoint for glossary list', () => {
    const params = { search: 'term' }
    const config = { cancelKey: 'x' }
    adminService.adminGetGlossaries(params, config)

    expect(mockApi.get).toHaveBeenCalledWith('/editor/glossaries', { params, ...config })
  })

  it('uses editor endpoint for term list', () => {
    const params = { page: 2 }
    adminService.adminGetTerms(params)

    expect(mockApi.get).toHaveBeenCalledWith('/editor/terms', { params })
  })

  it('uses editor endpoint for create glossary', () => {
    const payload = { title: 'g' }
    adminService.createGlossary(payload)

    expect(mockApi.post).toHaveBeenCalledWith('/editor/glossaries', payload)
  })

  it('uses admin endpoint for references list', () => {
    const params = { per_page: 10 }
    adminService.getReferences(params)

    expect(mockApi.get).toHaveBeenCalledWith('/admin/references', { params })
  })

  it('uses admin endpoint for editor role approvals', () => {
    adminService.approveEditorRoleRequest(99, { note: 'ok' })

    expect(mockApi.patch).toHaveBeenCalledWith('/admin/editor-role-requests/99/approve', { note: 'ok' })
  })

  it('uses admin endpoint for user list', () => {
    const params = { role_id: 4 }
    adminService.getUsers(params)

    expect(mockApi.get).toHaveBeenCalledWith('/admin/users', { params })
  })

  it('uses dedicated admin endpoints for base role and editor toggles', () => {
    adminService.setUserBaseRole(7, 'Teacher')
    adminService.grantUserEditorRole(7)
    adminService.revokeUserEditorRole(7)

    expect(mockApi.patch).toHaveBeenNthCalledWith(1, '/admin/users/7/base-role', { base_role: 'Teacher' })
    expect(mockApi.patch).toHaveBeenNthCalledWith(2, '/admin/users/7/editor/grant')
    expect(mockApi.patch).toHaveBeenNthCalledWith(3, '/admin/users/7/editor/revoke')
  })

  it('uses admin endpoint for banning user', () => {
    adminService.banUser(5)

    expect(mockApi.patch).toHaveBeenCalledWith('/admin/users/5/ban')
  })

  it('uses admin endpoint for audit logs list', () => {
    adminService.getAuditLogs({ action: 'admin.user', per_page: 20 })

    expect(mockApi.get).toHaveBeenCalledWith('/admin/audit-logs', {
      params: { action: 'admin.user', per_page: 20 },
    })
  })

  it('uses editor endpoint for lookup language pairs', () => {
    adminService.getLanguagePairs({ per_page: 100 })

    expect(mockApi.get).toHaveBeenCalledWith('/editor/language-pairs', { params: { per_page: 100 } })
  })
})

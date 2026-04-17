import { beforeEach, describe, expect, it, vi } from 'vitest'

const mockApi = vi.hoisted(() => ({
  get: vi.fn(),
  patch: vi.fn(),
}))

vi.mock('@/services/api', () => ({
  default: mockApi,
}))

import teacherService from '@/services/teacherService'

describe('teacherService', () => {
  beforeEach(() => {
    mockApi.get.mockReset()
    mockApi.patch.mockReset()
  })

  it('calls teacher glossaries list endpoint', () => {
    teacherService.getGlossaries({ search: 'term' })

    expect(mockApi.get).toHaveBeenCalledWith('/teacher/glossaries', {
      params: { search: 'term' },
    })
  })

  it('calls approve glossary endpoint', () => {
    teacherService.approveGlossary(15)

    expect(mockApi.patch).toHaveBeenCalledWith('/teacher/glossaries/15/approve')
  })

  it('calls teacher glossary detail endpoint', () => {
    teacherService.getGlossary(15)

    expect(mockApi.get).toHaveBeenCalledWith('/teacher/glossaries/15', {})
  })

  it('calls teacher students list endpoint', () => {
    teacherService.getStudents({ search: 'anna' })

    expect(mockApi.get).toHaveBeenCalledWith('/teacher/students', {
      params: { search: 'anna' },
    })
  })

  it('calls assign editor endpoint for student', () => {
    teacherService.assignEditorToStudent(77)

    expect(mockApi.patch).toHaveBeenCalledWith('/teacher/students/77/assign-editor')
  })
})

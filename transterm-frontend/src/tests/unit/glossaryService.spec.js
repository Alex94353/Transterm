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

import glossaryService from '@/services/glossaryService'

describe('glossaryService', () => {
  beforeEach(() => {
    mockApi.get.mockReset()
    mockApi.post.mockReset()
    mockApi.put.mockReset()
    mockApi.delete.mockReset()
  })

  it('uses public glossary endpoints', () => {
    const params = { search: 'term' }
    const requestConfig = { cancelKey: 'public:glossaries:list' }

    glossaryService.getGlossaries(params, requestConfig)
    glossaryService.getGlossary(11, requestConfig)

    expect(mockApi.get).toHaveBeenNthCalledWith(1, '/glossaries', { params, ...requestConfig })
    expect(mockApi.get).toHaveBeenNthCalledWith(2, '/glossaries/11', requestConfig)
  })

  it('uses public term endpoints and comments endpoint', () => {
    const params = { glossary_id: 5, page: 2 }

    glossaryService.getTerms(params, { cancelKey: 'public:terms:list' })
    glossaryService.getTerm(33)
    glossaryService.addComment(33, { body: 'Useful note' })

    expect(mockApi.get).toHaveBeenNthCalledWith(1, '/terms', {
      params,
      cancelKey: 'public:terms:list',
    })
    expect(mockApi.get).toHaveBeenNthCalledWith(2, '/terms/33', {})
    expect(mockApi.post).toHaveBeenCalledWith('/terms/33/comments', { body: 'Useful note' })
  })

  it('uses admin glossary endpoints', () => {
    glossaryService.adminGetGlossaries({ page: 1 })
    glossaryService.adminGetGlossary(1)
    glossaryService.adminCreateGlossary({ title: 'A' })
    glossaryService.adminUpdateGlossary(1, { title: 'B' })
    glossaryService.adminDeleteGlossary(1)

    expect(mockApi.get).toHaveBeenNthCalledWith(1, '/editor/glossaries', { params: { page: 1 } })
    expect(mockApi.get).toHaveBeenNthCalledWith(2, '/editor/glossaries/1')
    expect(mockApi.post).toHaveBeenCalledWith('/editor/glossaries', { title: 'A' })
    expect(mockApi.put).toHaveBeenCalledWith('/editor/glossaries/1', { title: 'B' })
    expect(mockApi.delete).toHaveBeenCalledWith('/editor/glossaries/1')
  })

  it('uses admin term endpoints', () => {
    glossaryService.adminGetTerms({ per_page: 50 })
    glossaryService.adminGetTerm(7)
    glossaryService.adminCreateTerm({ title: 'CPU' })
    glossaryService.adminUpdateTerm(7, { title: 'GPU' })
    glossaryService.adminDeleteTerm(7)

    expect(mockApi.get).toHaveBeenNthCalledWith(1, '/editor/terms', { params: { per_page: 50 } })
    expect(mockApi.get).toHaveBeenNthCalledWith(2, '/editor/terms/7')
    expect(mockApi.post).toHaveBeenCalledWith('/editor/terms', { title: 'CPU' })
    expect(mockApi.put).toHaveBeenCalledWith('/editor/terms/7', { title: 'GPU' })
    expect(mockApi.delete).toHaveBeenCalledWith('/editor/terms/7')
  })
})

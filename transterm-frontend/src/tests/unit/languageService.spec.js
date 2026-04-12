import { beforeEach, describe, expect, it, vi } from 'vitest'

const mockApi = vi.hoisted(() => ({
  get: vi.fn(),
}))

vi.mock('@/services/api', () => ({
  default: mockApi,
}))

import languageService from '@/services/languageService'

describe('languageService', () => {
  beforeEach(() => {
    mockApi.get.mockReset()
  })

  it('calls language endpoints with params', () => {
    languageService.getLanguages({ search: 'en' })
    languageService.getLanguage(1)

    expect(mockApi.get).toHaveBeenNthCalledWith(1, '/languages', { params: { search: 'en' } })
    expect(mockApi.get).toHaveBeenNthCalledWith(2, '/languages/1')
  })

  it('calls language pair endpoints with params', () => {
    languageService.getLanguagePairs({ page: 2 })
    languageService.getLanguagePair(4)

    expect(mockApi.get).toHaveBeenNthCalledWith(1, '/language-pairs', { params: { page: 2 } })
    expect(mockApi.get).toHaveBeenNthCalledWith(2, '/language-pairs/4')
  })

  it('calls countries endpoint with params', () => {
    languageService.getCountries({ q: 'slovak' })

    expect(mockApi.get).toHaveBeenCalledWith('/countries', { params: { q: 'slovak' } })
  })
})

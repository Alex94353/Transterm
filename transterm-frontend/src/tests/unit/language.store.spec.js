import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const mockLanguageService = vi.hoisted(() => ({
  getLanguages: vi.fn(),
  getLanguagePairs: vi.fn(),
  getCountries: vi.fn(),
}))

vi.mock('@/services/languageService', () => ({
  default: mockLanguageService,
}))

import { useLanguageStore } from '@/stores/language'

describe('language store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockLanguageService.getLanguages.mockReset()
    mockLanguageService.getLanguagePairs.mockReset()
    mockLanguageService.getCountries.mockReset()
  })

  it('fetchLanguages stores nested payload data', async () => {
    mockLanguageService.getLanguages.mockResolvedValue({
      data: {
        data: [{ id: 1, name: 'Slovak' }],
      },
    })
    const store = useLanguageStore()

    const result = await store.fetchLanguages()

    expect(result).toEqual({ data: [{ id: 1, name: 'Slovak' }] })
    expect(store.languages).toEqual([{ id: 1, name: 'Slovak' }])
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetchLanguages falls back to raw payload shape', async () => {
    mockLanguageService.getLanguages.mockResolvedValue({
      data: [{ id: 2, name: 'English' }],
    })
    const store = useLanguageStore()

    await store.fetchLanguages()

    expect(store.languages).toEqual([{ id: 2, name: 'English' }])
  })

  it('fetchLanguagePairs stores backend message on failure', async () => {
    const error = {
      response: {
        data: {
          message: 'Pairs unavailable',
        },
      },
    }
    mockLanguageService.getLanguagePairs.mockRejectedValue(error)
    const store = useLanguageStore()

    await expect(store.fetchLanguagePairs()).rejects.toBe(error)
    expect(store.error).toBe('Pairs unavailable')
    expect(store.loading).toBe(false)
  })

  it('fetchCountries sets default error message when backend message is missing', async () => {
    const error = new Error('Network down')
    mockLanguageService.getCountries.mockRejectedValue(error)
    const store = useLanguageStore()

    await expect(store.fetchCountries()).rejects.toBe(error)
    expect(store.error).toBe('Failed to fetch countries')
    expect(store.loading).toBe(false)
  })
})

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const mockGlossaryService = vi.hoisted(() => ({
  getGlossaries: vi.fn(),
  getGlossary: vi.fn(),
  getTerms: vi.fn(),
  getTerm: vi.fn(),
  addComment: vi.fn(),
}))

vi.mock('@/services/glossaryService', () => ({
  default: mockGlossaryService,
}))

import { useGlossaryStore } from '@/stores/glossary'

function deferred() {
  let resolve
  let reject
  const promise = new Promise((res, rej) => {
    resolve = res
    reject = rej
  })
  return { promise, resolve, reject }
}

describe('glossary store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    Object.values(mockGlossaryService).forEach((fn) => fn.mockReset())
  })

  it('fetchGlossaries normalizes list data and computes total count', async () => {
    mockGlossaryService.getGlossaries.mockResolvedValue({
      data: {
        data: [
          {
            id: 7,
            translations: [{ title: 'IT glossary', description: 'Technical terms' }],
          },
          {
            id: 8,
            translations: [],
          },
        ],
      },
    })
    const store = useGlossaryStore()

    const result = await store.fetchGlossaries({ search: 'it' })

    expect(result).toEqual({
      data: [
        { id: 7, translations: [{ title: 'IT glossary', description: 'Technical terms' }] },
        { id: 8, translations: [] },
      ],
    })
    expect(mockGlossaryService.getGlossaries).toHaveBeenCalledWith(
      { search: 'it', page: 1, per_page: 20 },
      { cancelKey: 'public:glossaries:list' }
    )
    expect(store.glossaries[0].name).toBe('IT glossary')
    expect(store.glossaries[1].name).toBe('Glossary #8')
    expect(store.totalGlossaries).toBe(2)
    expect(store.loading).toBe(false)
  })

  it('fetchGlossaries ignores duplicate in-flight query with same fingerprint', async () => {
    const pending = deferred()
    mockGlossaryService.getGlossaries.mockReturnValueOnce(pending.promise)
    const store = useGlossaryStore()

    const firstRequest = store.fetchGlossaries({ search: 'same' })
    const secondResult = await store.fetchGlossaries({ search: 'same' })

    expect(secondResult).toBeNull()
    expect(mockGlossaryService.getGlossaries).toHaveBeenCalledTimes(1)

    pending.resolve({ data: { data: [] } })
    await firstRequest
    expect(store.loading).toBe(false)
  })

  it('fetchGlossary loads glossary details with first page of terms', async () => {
    mockGlossaryService.getGlossary.mockResolvedValue({
      data: {
        data: {
          id: 3,
          translations: [{ title: 'Medicine', description: 'Medical glossary' }],
        },
      },
    })
    mockGlossaryService.getTerms.mockResolvedValue({
      data: {
        data: [
          {
            id: 41,
            translations: [{ title: 'Anamnesis', definition: 'Patient history' }],
            comments: [{ id: 1, body: 'Commonly used' }],
            glossary: {
              id: 3,
              translations: [{ title: 'Medicine' }],
            },
          },
        ],
        meta: {
          current_page: 1,
          last_page: 2,
        },
      },
    })
    const store = useGlossaryStore()

    await store.fetchGlossary(3)

    expect(mockGlossaryService.getGlossary).toHaveBeenCalledWith(3, {
      cancelKey: 'public:glossary:detail',
    })
    expect(mockGlossaryService.getTerms).toHaveBeenCalledWith(
      { glossary_id: 3, per_page: 30, page: 1 },
      { cancelKey: 'public:glossary:terms:first-page' }
    )
    expect(store.currentGlossary.name).toBe('Medicine')
    expect(store.currentGlossary.terms[0].name).toBe('Anamnesis')
    expect(store.currentGlossary.terms[0].translations[0].content).toBe('Patient history')
    expect(store.currentGlossary.terms[0].comments[0].content).toBe('Commonly used')
    expect(store.hasMoreGlossaryTerms).toBe(true)
  })

  it('loadMoreGlossaryTerms returns null when prerequisites are not met', async () => {
    const store = useGlossaryStore()

    const result = await store.loadMoreGlossaryTerms()

    expect(result).toBeNull()
    expect(mockGlossaryService.getTerms).not.toHaveBeenCalled()
  })

  it('loadMoreGlossaryTerms appends term page and updates hasMore flag', async () => {
    mockGlossaryService.getTerms.mockResolvedValue({
      data: {
        data: [
          {
            id: 52,
            translations: [{ title: 'Pathology', definition: 'Disease study' }],
            comments: [],
          },
        ],
        meta: {
          current_page: 2,
          last_page: 2,
        },
      },
    })
    const store = useGlossaryStore()
    store.currentGlossary = {
      id: 3,
      terms: [{ id: 41, name: 'Anamnesis' }],
    }
    store.hasMoreGlossaryTerms = true

    await store.loadMoreGlossaryTerms()

    expect(mockGlossaryService.getTerms).toHaveBeenCalledWith({
      glossary_id: 3,
      per_page: 30,
      page: 2,
    })
    expect(store.currentGlossary.terms).toHaveLength(2)
    expect(store.currentGlossary.terms[1].name).toBe('Pathology')
    expect(store.hasMoreGlossaryTerms).toBe(false)
    expect(store.loadingMoreGlossaryTerms).toBe(false)
  })

  it('fetchTerms normalizes terms list', async () => {
    mockGlossaryService.getTerms.mockResolvedValue({
      data: {
        data: [
          {
            id: 80,
            translations: [{ title: 'Firewall', definition: 'Network filter' }],
            comments: [{ body: 'Security related' }],
          },
        ],
      },
    })
    const store = useGlossaryStore()

    await store.fetchTerms({ page: 4 })

    expect(mockGlossaryService.getTerms).toHaveBeenCalledWith({
      search: '',
      page: 4,
      per_page: 20,
    })
    expect(store.terms[0].name).toBe('Firewall')
    expect(store.terms[0].definition).toBe('Network filter')
    expect(store.terms[0].comments[0].content).toBe('Security related')
  })

  it('fetchTerm stores normalized term detail', async () => {
    mockGlossaryService.getTerm.mockResolvedValue({
      data: {
        data: {
          id: 99,
          translations: [{ title: 'Kernel', definition: 'Core system component' }],
          comments: [],
        },
      },
    })
    const store = useGlossaryStore()

    await store.fetchTerm(99)

    expect(mockGlossaryService.getTerm).toHaveBeenCalledWith(99, {
      cancelKey: 'public:term:detail',
    })
    expect(store.currentTerm.name).toBe('Kernel')
    expect(store.currentTerm.definition).toBe('Core system component')
    expect(store.loading).toBe(false)
  })

  it('fetchTerm stores backend message when request fails', async () => {
    const error = {
      response: {
        data: {
          message: 'Term fetch failed',
        },
      },
    }
    mockGlossaryService.getTerm.mockRejectedValue(error)
    const store = useGlossaryStore()

    await expect(store.fetchTerm(1000)).rejects.toBe(error)
    expect(store.error).toBe('Term fetch failed')
    expect(store.loading).toBe(false)
  })

  it('addComment maps content/body payload and setFilters merges state', async () => {
    mockGlossaryService.addComment.mockResolvedValue({
      data: { id: 1, body: 'Saved' },
    })
    const store = useGlossaryStore()

    await store.addComment(77, { content: 'New comment' })
    await store.addComment(77, { body: 'Already normalized' })
    store.setFilters({ search: 'cache', per_page: 50 })

    expect(mockGlossaryService.addComment).toHaveBeenNthCalledWith(1, 77, { body: 'New comment' })
    expect(mockGlossaryService.addComment).toHaveBeenNthCalledWith(2, 77, { body: 'Already normalized' })
    expect(store.filters).toEqual({
      search: 'cache',
      page: 1,
      per_page: 50,
    })
    expect(store.loading).toBe(false)
  })
})

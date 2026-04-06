import { defineComponent, nextTick } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { useAdminList } from '@/composables/useAdminList'

describe('useAdminList', () => {
  const mountComposable = (options = {}) => {
    let composable

    const Harness = defineComponent({
      setup() {
        composable = useAdminList(options)
        return () => null
      },
    })

    const wrapper = mount(Harness)
    return { ...composable, wrapper }
  }

  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('debounces search, applies query and resets page', async () => {
    const fetcher = vi.fn()
    const { searchQuery, appliedSearch, pagination, bindDebouncedSearch, wrapper } = mountComposable({
      debounceMs: 300,
    })

    pagination.page = 4
    bindDebouncedSearch(fetcher)

    searchQuery.value = 'term'
    await nextTick()

    vi.advanceTimersByTime(299)
    expect(fetcher).not.toHaveBeenCalled()

    vi.advanceTimersByTime(1)
    expect(fetcher).toHaveBeenCalledTimes(1)
    expect(appliedSearch.value).toBe('term')
    expect(pagination.page).toBe(1)
    wrapper.unmount()
  })

  it('runSearch applies search immediately', () => {
    const fetcher = vi.fn()
    const { searchQuery, appliedSearch, pagination, runSearch, wrapper } = mountComposable()

    pagination.page = 3
    searchQuery.value = 'abc'

    runSearch(fetcher)

    expect(appliedSearch.value).toBe('abc')
    expect(pagination.page).toBe(1)
    expect(fetcher).toHaveBeenCalledTimes(1)
    wrapper.unmount()
  })

  it('runClearSearch clears query and invokes fetcher', () => {
    const fetcher = vi.fn()
    const { searchQuery, appliedSearch, pagination, runClearSearch, wrapper } = mountComposable()

    searchQuery.value = 'x'
    appliedSearch.value = 'x'
    pagination.page = 2

    runClearSearch(fetcher)

    expect(searchQuery.value).toBe('')
    expect(appliedSearch.value).toBe('')
    expect(pagination.page).toBe(1)
    expect(fetcher).toHaveBeenCalledTimes(1)
    wrapper.unmount()
  })

  it('runFiltersChange resets page and fetches', () => {
    const fetcher = vi.fn()
    const { pagination, runFiltersChange, wrapper } = mountComposable()

    pagination.page = 9
    runFiltersChange(fetcher)

    expect(pagination.page).toBe(1)
    expect(fetcher).toHaveBeenCalledTimes(1)
    wrapper.unmount()
  })

  it('runPageChange updates page and fetches', () => {
    const fetcher = vi.fn()
    const { pagination, runPageChange, wrapper } = mountComposable()

    runPageChange(5, fetcher)

    expect(pagination.page).toBe(5)
    expect(fetcher).toHaveBeenCalledTimes(1)
    wrapper.unmount()
  })

  it('runPageSizeChange updates size, resets page and fetches', () => {
    const fetcher = vi.fn()
    const { pagination, runPageSizeChange, wrapper } = mountComposable({ defaultPerPage: 10 })

    pagination.page = 3
    runPageSizeChange(50, fetcher)

    expect(pagination.perPage).toBe(50)
    expect(pagination.page).toBe(1)
    expect(fetcher).toHaveBeenCalledTimes(1)
    wrapper.unmount()
  })
})

import { onBeforeUnmount, reactive, ref, watch } from 'vue'

export function useAdminList(options = {}) {
  const {
    defaultPerPage = 10,
    debounceMs = 350,
  } = options

  const searchQuery = ref('')
  const appliedSearch = ref('')

  const pagination = reactive({
    page: 1,
    perPage: defaultPerPage,
    total: 0,
  })

  let debounceTimer

  const bindDebouncedSearch = (fetcher) => {
    watch(searchQuery, (newValue, oldValue) => {
      if (newValue === oldValue) return
      clearTimeout(debounceTimer)
      debounceTimer = setTimeout(() => {
        appliedSearch.value = newValue
        pagination.page = 1
        fetcher()
      }, debounceMs)
    })
  }

  const runSearch = (fetcher) => {
    clearTimeout(debounceTimer)
    appliedSearch.value = searchQuery.value
    pagination.page = 1
    fetcher()
  }

  const runClearSearch = (fetcher) => {
    searchQuery.value = ''
    appliedSearch.value = ''
    pagination.page = 1
    fetcher()
  }

  const runFiltersChange = (fetcher) => {
    pagination.page = 1
    fetcher()
  }

  const runPageChange = (page, fetcher) => {
    pagination.page = page
    fetcher()
  }

  const runPageSizeChange = (size, fetcher) => {
    pagination.perPage = size
    pagination.page = 1
    fetcher()
  }

  onBeforeUnmount(() => {
    clearTimeout(debounceTimer)
  })

  return {
    searchQuery,
    appliedSearch,
    pagination,
    bindDebouncedSearch,
    runSearch,
    runClearSearch,
    runFiltersChange,
    runPageChange,
    runPageSizeChange,
  }
}

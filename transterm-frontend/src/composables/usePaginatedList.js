import { ref } from 'vue'
import { getListItems, getListMeta } from '@/utils/pagination'

export function usePaginatedList(initialPerPage = 10) {
  const loading = ref(false)
  const rows = ref([])
  const page = ref(1)
  const perPage = ref(initialPerPage)
  const total = ref(0)

  function applyResponse(response) {
    const meta = getListMeta(response)

    rows.value = getListItems(response)
    page.value = meta.current_page
    perPage.value = meta.per_page
    total.value = meta.total
  }

  async function runPageRequest(request) {
    loading.value = true

    try {
      const response = await request()
      applyResponse(response)
      return response
    } finally {
      loading.value = false
    }
  }

  function resetPage() {
    page.value = 1
  }

  function resetPagination() {
    page.value = 1
    perPage.value = initialPerPage
  }

  function handlePageChange(value, reload) {
    page.value = value
    return reload()
  }

  function handlePerPageChange(value, reload) {
    perPage.value = value
    page.value = 1
    return reload()
  }

  return {
    loading,
    rows,
    page,
    perPage,
    total,
    runPageRequest,
    resetPage,
    resetPagination,
    handlePageChange,
    handlePerPageChange,
  }
}

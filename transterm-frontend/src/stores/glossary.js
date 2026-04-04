import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import glossaryService from '../services/glossaryService'

function pickTranslationText(translations = [], field, fallback = '') {
  if (!Array.isArray(translations) || translations.length === 0) return fallback
  return translations[0]?.[field] || fallback
}

function normalizeGlossary(glossary) {
  if (!glossary) return null

  return {
    ...glossary,
    name: pickTranslationText(glossary.translations, 'title', `Glossary #${glossary.id}`),
    description: pickTranslationText(glossary.translations, 'description', ''),
  }
}

function normalizeTerm(term) {
  if (!term) return null

  const normalizedTranslations = Array.isArray(term.translations)
    ? term.translations.map((translation) => ({
      ...translation,
      content: translation.definition || translation.title || '',
    }))
    : []

  const normalizedComments = Array.isArray(term.comments)
    ? term.comments.map((comment) => ({
      ...comment,
      content: comment.body || '',
    }))
    : []

  const glossaryName = pickTranslationText(term.glossary?.translations, 'title', term.glossary?.name || '')
  const primaryTranslation = normalizedTranslations[0] || null

  return {
    ...term,
    name: primaryTranslation?.title || `Term #${term.id}`,
    definition: primaryTranslation?.definition || '',
    translations: normalizedTranslations,
    comments: normalizedComments,
    glossary: term.glossary
      ? {
        ...term.glossary,
        name: glossaryName,
      }
      : null,
  }
}

function buildQueryFingerprint(value) {
  if (Array.isArray(value)) {
    return value.map((item) => buildQueryFingerprint(item))
  }

  if (value && typeof value === 'object') {
    return Object.keys(value)
      .sort()
      .reduce((acc, key) => {
        acc[key] = buildQueryFingerprint(value[key])
        return acc
      }, {})
  }

  return value
}

export const useGlossaryStore = defineStore('glossary', () => {
  const GLOSSARY_TERMS_PER_PAGE = 30

  const glossaries = ref([])
  const currentGlossary = ref(null)
  const terms = ref([])
  const currentTerm = ref(null)
  const hasMoreGlossaryTerms = ref(false)
  const loadingMoreGlossaryTerms = ref(false)
  const loading = ref(false)
  const error = ref(null)
  const filters = ref({
    search: '',
    page: 1,
    per_page: 20,
  })
  let latestGlossariesRequestId = 0
  let latestGlossariesFingerprint = null
  let latestGlossaryRequestId = 0
  let latestTermRequestId = 0
  let nextGlossaryTermsPage = 2

  const totalGlossaries = computed(() => glossaries.value.length)

  async function fetchGlossaries(params = {}) {
    const queryParams = {
      ...filters.value,
      ...params,
    }
    const fingerprint = JSON.stringify(buildQueryFingerprint(queryParams))

    if (loading.value && fingerprint === latestGlossariesFingerprint) {
      return null
    }

    const requestId = ++latestGlossariesRequestId
    latestGlossariesFingerprint = fingerprint
    loading.value = true
    error.value = null
    try {
      const response = await glossaryService.getGlossaries(queryParams, {
        cancelKey: 'public:glossaries:list',
      })
      if (requestId !== latestGlossariesRequestId) return null
      const items = response.data.data || response.data || []
      glossaries.value = Array.isArray(items) ? items.map(normalizeGlossary) : []
      return response.data
    } catch (err) {
      if (requestId !== latestGlossariesRequestId) return null
      error.value = err.response?.data?.message || 'Failed to fetch glossaries'
      throw err
    } finally {
      if (requestId === latestGlossariesRequestId) {
        loading.value = false
      }
    }
  }

  async function fetchGlossary(id) {
    const requestId = ++latestGlossaryRequestId
    loading.value = true
    error.value = null
    currentGlossary.value = null
    hasMoreGlossaryTerms.value = false
    nextGlossaryTermsPage = 2

    try {
      const [response, termsResponse] = await Promise.all([
        glossaryService.getGlossary(id, {
          cancelKey: 'public:glossary:detail',
        }),
        glossaryService.getTerms({
          glossary_id: id,
          per_page: GLOSSARY_TERMS_PER_PAGE,
          page: 1,
        }, {
          cancelKey: 'public:glossary:terms:first-page',
        }),
      ])

      if (requestId !== latestGlossaryRequestId) return null

      const glossaryData = normalizeGlossary(response.data.data || response.data)

      const termsData = termsResponse.data.data || termsResponse.data || []
      const normalizedTerms = Array.isArray(termsData) ? termsData.map(normalizeTerm) : []
      const termsMeta = termsResponse.data?.meta || {}
      const currentPage = Number(termsMeta.current_page || 1)
      const lastPage = Number(termsMeta.last_page || 1)
      hasMoreGlossaryTerms.value = currentPage < lastPage
      nextGlossaryTermsPage = currentPage + 1

      currentGlossary.value = {
        ...glossaryData,
        terms: normalizedTerms,
      }

      return response.data
    } catch (err) {
      if (requestId !== latestGlossaryRequestId) return null
      error.value = err.response?.data?.message || 'Failed to fetch glossary'
      throw err
    } finally {
      if (requestId === latestGlossaryRequestId) {
        loading.value = false
      }
    }
  }

  async function loadMoreGlossaryTerms() {
    if (!currentGlossary.value || !hasMoreGlossaryTerms.value || loadingMoreGlossaryTerms.value) {
      return null
    }

    const glossaryId = currentGlossary.value.id
    const page = nextGlossaryTermsPage
    loadingMoreGlossaryTerms.value = true

    try {
      const response = await glossaryService.getTerms({
        glossary_id: glossaryId,
        per_page: GLOSSARY_TERMS_PER_PAGE,
        page,
      })

      if (!currentGlossary.value || currentGlossary.value.id !== glossaryId) {
        return null
      }

      const items = response.data.data || response.data || []
      const normalizedTerms = Array.isArray(items) ? items.map(normalizeTerm) : []
      currentGlossary.value = {
        ...currentGlossary.value,
        terms: [...(currentGlossary.value.terms || []), ...normalizedTerms],
      }

      const termsMeta = response.data?.meta || {}
      const currentPage = Number(termsMeta.current_page || page)
      const lastPage = Number(termsMeta.last_page || currentPage)
      hasMoreGlossaryTerms.value = currentPage < lastPage
      nextGlossaryTermsPage = currentPage + 1

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load more terms'
      throw err
    } finally {
      loadingMoreGlossaryTerms.value = false
    }
  }

  async function fetchTerms(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await glossaryService.getTerms({
        ...filters.value,
        ...params,
      })
      const items = response.data.data || response.data || []
      terms.value = Array.isArray(items) ? items.map(normalizeTerm) : []
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch terms'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchTerm(id) {
    const requestId = ++latestTermRequestId
    loading.value = true
    error.value = null
    currentTerm.value = null
    try {
      const response = await glossaryService.getTerm(id, {
        cancelKey: 'public:term:detail',
      })
      if (requestId !== latestTermRequestId) return null
      currentTerm.value = normalizeTerm(response.data.data || response.data)
      return response.data
    } catch (err) {
      if (requestId !== latestTermRequestId) return null
      error.value = err.response?.data?.message || 'Failed to fetch term'
      throw err
    } finally {
      if (requestId === latestTermRequestId) {
        loading.value = false
      }
    }
  }

  async function addComment(termId, data) {
    loading.value = true
    error.value = null
    try {
      const response = await glossaryService.addComment(termId, {
        body: data.body || data.content || '',
      })
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to add comment'
      throw err
    } finally {
      loading.value = false
    }
  }

  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  return {
    glossaries,
    currentGlossary,
    terms,
    currentTerm,
    hasMoreGlossaryTerms,
    loadingMoreGlossaryTerms,
    loading,
    error,
    filters,
    totalGlossaries,
    fetchGlossaries,
    fetchGlossary,
    fetchTerms,
    fetchTerm,
    loadMoreGlossaryTerms,
    addComment,
    setFilters,
  }
})

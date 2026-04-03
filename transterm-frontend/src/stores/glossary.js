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

export const useGlossaryStore = defineStore('glossary', () => {
  const glossaries = ref([])
  const currentGlossary = ref(null)
  const terms = ref([])
  const currentTerm = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const filters = ref({
    search: '',
    page: 1,
    per_page: 20,
  })

  const totalGlossaries = computed(() => glossaries.value.length)

  async function fetchGlossaries(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await glossaryService.getGlossaries({
        ...filters.value,
        ...params,
      })
      const items = response.data.data || response.data || []
      glossaries.value = Array.isArray(items) ? items.map(normalizeGlossary) : []
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch glossaries'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchGlossary(id) {
    loading.value = true
    error.value = null
    try {
      const response = await glossaryService.getGlossary(id)
      const glossaryData = normalizeGlossary(response.data.data || response.data)

      const termsResponse = await glossaryService.getTerms({
        glossary_id: id,
        per_page: 100,
      })

      const termsData = termsResponse.data.data || termsResponse.data || []
      const normalizedTerms = Array.isArray(termsData) ? termsData.map(normalizeTerm) : []

      currentGlossary.value = {
        ...glossaryData,
        terms: normalizedTerms,
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch glossary'
      throw err
    } finally {
      loading.value = false
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
    loading.value = true
    error.value = null
    try {
      const response = await glossaryService.getTerm(id)
      currentTerm.value = normalizeTerm(response.data.data || response.data)
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch term'
      throw err
    } finally {
      loading.value = false
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
    loading,
    error,
    filters,
    totalGlossaries,
    fetchGlossaries,
    fetchGlossary,
    fetchTerms,
    fetchTerm,
    addComment,
    setFilters,
  }
})

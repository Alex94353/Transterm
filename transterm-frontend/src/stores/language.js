import { defineStore } from 'pinia'
import { ref } from 'vue'
import languageService from '../services/languageService'

export const useLanguageStore = defineStore('language', () => {
  const languages = ref([])
  const languagePairs = ref([])
  const countries = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchLanguages() {
    loading.value = true
    error.value = null
    try {
      const response = await languageService.getLanguages()
      languages.value = response.data.data || response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch languages'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchLanguagePairs() {
    loading.value = true
    error.value = null
    try {
      const response = await languageService.getLanguagePairs()
      languagePairs.value = response.data.data || response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch language pairs'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchCountries() {
    loading.value = true
    error.value = null
    try {
      const response = await languageService.getCountries()
      countries.value = response.data.data || response.data
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch countries'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    languages,
    languagePairs,
    countries,
    loading,
    error,
    fetchLanguages,
    fetchLanguagePairs,
    fetchCountries,
  }
})

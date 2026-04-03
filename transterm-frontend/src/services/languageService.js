import api from './api'

export const languageService = {
  getLanguages: (params) => api.get('/languages', { params }),
  getLanguage: (id) => api.get(`/languages/${id}`),

  getLanguagePairs: (params) => api.get('/language-pairs', { params }),
  getLanguagePair: (id) => api.get(`/language-pairs/${id}`),

  getCountries: (params) => api.get('/countries', { params }),
}

export default languageService

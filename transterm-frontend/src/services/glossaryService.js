import api from './api'

export const glossaryService = {
  // Public endpoints
  getGlossaries: (params, requestConfig = {}) => api.get('/glossaries', { params, ...requestConfig }),
  getGlossary: (id, requestConfig = {}) => api.get(`/glossaries/${id}`, requestConfig),

  // Terms
  getTerms: (params, requestConfig = {}) => api.get('/terms', { params, ...requestConfig }),
  getTerm: (id, requestConfig = {}) => api.get(`/terms/${id}`, requestConfig),

  // Comments
  addComment: (termId, data) => api.post(`/terms/${termId}/comments`, data),

  // Admin endpoints
  adminGetGlossaries: (params) => api.get('/editor/glossaries', { params }),
  adminGetGlossary: (id) => api.get(`/editor/glossaries/${id}`),
  adminCreateGlossary: (data) => api.post('/editor/glossaries', data),
  adminUpdateGlossary: (id, data) => api.put(`/editor/glossaries/${id}`, data),
  adminDeleteGlossary: (id) => api.delete(`/editor/glossaries/${id}`),

  // Admin Terms
  adminGetTerms: (params) => api.get('/editor/terms', { params }),
  adminGetTerm: (id) => api.get(`/editor/terms/${id}`),
  adminCreateTerm: (data) => api.post('/editor/terms', data),
  adminUpdateTerm: (id, data) => api.put(`/editor/terms/${id}`, data),
  adminDeleteTerm: (id) => api.delete(`/editor/terms/${id}`),
}

export default glossaryService

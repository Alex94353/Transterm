import api from './api'

export const glossaryService = {
  // Public endpoints
  getGlossaries: (params) => api.get('/glossaries', { params }),
  getGlossary: (id) => api.get(`/glossaries/${id}`),

  // Terms
  getTerms: (params) => api.get('/terms', { params }),
  getTerm: (id) => api.get(`/terms/${id}`),

  // Comments
  addComment: (termId, data) => api.post(`/terms/${termId}/comments`, data),

  // Admin endpoints
  adminGetGlossaries: (params) => api.get('/admin/glossaries', { params }),
  adminGetGlossary: (id) => api.get(`/admin/glossaries/${id}`),
  adminCreateGlossary: (data) => api.post('/admin/glossaries', data),
  adminUpdateGlossary: (id, data) => api.put(`/admin/glossaries/${id}`, data),
  adminDeleteGlossary: (id) => api.delete(`/admin/glossaries/${id}`),

  // Admin Terms
  adminGetTerms: (params) => api.get('/admin/terms', { params }),
  adminGetTerm: (id) => api.get(`/admin/terms/${id}`),
  adminCreateTerm: (data) => api.post('/admin/terms', data),
  adminUpdateTerm: (id, data) => api.put(`/admin/terms/${id}`, data),
  adminDeleteTerm: (id) => api.delete(`/admin/terms/${id}`),
}

export default glossaryService

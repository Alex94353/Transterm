import api from './api'

export const adminService = {
  // Comments
  getComments: (params, requestConfig = {}) => api.get('/admin/comments', { params, ...requestConfig }),
  markSpam: (id) => api.patch(`/admin/comments/${id}/spam`),
  unmarkSpam: (id) => api.patch(`/admin/comments/${id}/unspam`),
  deleteComment: (id) => api.delete(`/admin/comments/${id}`),

  // Glossaries
  adminGetGlossaries: (params, requestConfig = {}) => api.get('/admin/glossaries', { params, ...requestConfig }),
  getGlossary: (id, requestConfig = {}) => api.get(`/admin/glossaries/${id}`, requestConfig),
  createGlossary: (data) => api.post('/admin/glossaries', data),
  updateGlossary: (id, data) => api.put(`/admin/glossaries/${id}`, data),
  deleteGlossary: (id) => api.delete(`/admin/glossaries/${id}`),

  // Terms
  adminGetTerms: (params, requestConfig = {}) => api.get('/admin/terms', { params, ...requestConfig }),
  getTerm: (id, requestConfig = {}) => api.get(`/admin/terms/${id}`, requestConfig),
  createTerm: (data) => api.post('/admin/terms', data),
  updateTerm: (id, data) => api.put(`/admin/terms/${id}`, data),
  deleteTerm: (id) => api.delete(`/admin/terms/${id}`),

  // References
  getReferences: (params, requestConfig = {}) => api.get('/admin/references', { params, ...requestConfig }),
  getReference: (id, requestConfig = {}) => api.get(`/admin/references/${id}`, requestConfig),
  createReference: (data) => api.post('/admin/references', data),
  updateReference: (id, data) => api.put(`/admin/references/${id}`, data),
  deleteReference: (id) => api.delete(`/admin/references/${id}`),

  // Fields
  getFields: (params, requestConfig = {}) => api.get('/admin/fields', { params, ...requestConfig }),
  getField: (id, requestConfig = {}) => api.get(`/admin/fields/${id}`, requestConfig),
  createField: (data) => api.post('/admin/fields', data),
  updateField: (id, data) => api.put(`/admin/fields/${id}`, data),
  deleteField: (id) => api.delete(`/admin/fields/${id}`),

  // Field Groups
  getFieldGroups: (params, requestConfig = {}) => api.get('/admin/field-groups', { params, ...requestConfig }),
  getFieldGroup: (id, requestConfig = {}) => api.get(`/admin/field-groups/${id}`, requestConfig),
  createFieldGroup: (data) => api.post('/admin/field-groups', data),
  updateFieldGroup: (id, data) => api.put(`/admin/field-groups/${id}`, data),
  deleteFieldGroup: (id) => api.delete(`/admin/field-groups/${id}`),

  // Languages
  getLanguages: (params, requestConfig = {}) => api.get('/admin/languages', { params, ...requestConfig }),
  getLanguage: (id, requestConfig = {}) => api.get(`/admin/languages/${id}`, requestConfig),
  createLanguage: (data) => api.post('/admin/languages', data),
  updateLanguage: (id, data) => api.put(`/admin/languages/${id}`, data),
  deleteLanguage: (id) => api.delete(`/admin/languages/${id}`),

  // Language Pairs
  getLanguagePairs: (params, requestConfig = {}) => api.get('/admin/language-pairs', { params, ...requestConfig }),
  getLanguagePair: (id, requestConfig = {}) => api.get(`/admin/language-pairs/${id}`, requestConfig),
  createLanguagePair: (data) => api.post('/admin/language-pairs', data),
  updateLanguagePair: (id, data) => api.put(`/admin/language-pairs/${id}`, data),
  deleteLanguagePair: (id) => api.delete(`/admin/language-pairs/${id}`),

  // Users
  getUsers: (params, requestConfig = {}) => api.get('/admin/users', { params, ...requestConfig }),
  getUser: (id, requestConfig = {}) => api.get(`/admin/users/${id}`, requestConfig),
  updateUser: (id, data) => api.put(`/admin/users/${id}`, data),
  banUser: (id) => api.patch(`/admin/users/${id}/ban`),
  unbanUser: (id) => api.patch(`/admin/users/${id}/unban`),

  // Roles
  getRoles: (params, requestConfig = {}) => api.get('/admin/roles', { params, ...requestConfig }),
  getRole: (id, requestConfig = {}) => api.get(`/admin/roles/${id}`, requestConfig),
  createRole: (data) => api.post('/admin/roles', data),
  updateRole: (id, data) => api.put(`/admin/roles/${id}`, data),
  deleteRole: (id) => api.delete(`/admin/roles/${id}`),

  // Permissions
  getPermissions: (params, requestConfig = {}) => api.get('/admin/permissions', { params, ...requestConfig }),
  getPermission: (id, requestConfig = {}) => api.get(`/admin/permissions/${id}`, requestConfig),
  createPermission: (data) => api.post('/admin/permissions', data),
  updatePermission: (id, data) => api.put(`/admin/permissions/${id}`, data),
  deletePermission: (id) => api.delete(`/admin/permissions/${id}`),
}

export default adminService

import api from './api'

export const adminService = {
  // Comments
  getComments: (params) => api.get('/admin/comments', { params }),
  markSpam: (id) => api.patch(`/admin/comments/${id}/spam`),
  unmarkSpam: (id) => api.patch(`/admin/comments/${id}/unspam`),
  deleteComment: (id) => api.delete(`/admin/comments/${id}`),

  // Glossaries
  adminGetGlossaries: (params) => api.get('/admin/glossaries', { params }),
  getGlossary: (id) => api.get(`/admin/glossaries/${id}`),
  createGlossary: (data) => api.post('/admin/glossaries', data),
  updateGlossary: (id, data) => api.put(`/admin/glossaries/${id}`, data),
  deleteGlossary: (id) => api.delete(`/admin/glossaries/${id}`),

  // Terms
  adminGetTerms: (params) => api.get('/admin/terms', { params }),
  getTerm: (id) => api.get(`/admin/terms/${id}`),
  createTerm: (data) => api.post('/admin/terms', data),
  updateTerm: (id, data) => api.put(`/admin/terms/${id}`, data),
  deleteTerm: (id) => api.delete(`/admin/terms/${id}`),

  // References
  getReferences: (params) => api.get('/admin/references', { params }),
  getReference: (id) => api.get(`/admin/references/${id}`),
  createReference: (data) => api.post('/admin/references', data),
  updateReference: (id, data) => api.put(`/admin/references/${id}`, data),
  deleteReference: (id) => api.delete(`/admin/references/${id}`),

  // Fields
  getFields: (params) => api.get('/admin/fields', { params }),
  getField: (id) => api.get(`/admin/fields/${id}`),
  createField: (data) => api.post('/admin/fields', data),
  updateField: (id, data) => api.put(`/admin/fields/${id}`, data),
  deleteField: (id) => api.delete(`/admin/fields/${id}`),

  // Field Groups
  getFieldGroups: (params) => api.get('/admin/field-groups', { params }),
  getFieldGroup: (id) => api.get(`/admin/field-groups/${id}`),
  createFieldGroup: (data) => api.post('/admin/field-groups', data),
  updateFieldGroup: (id, data) => api.put(`/admin/field-groups/${id}`, data),
  deleteFieldGroup: (id) => api.delete(`/admin/field-groups/${id}`),

  // Languages
  getLanguages: (params) => api.get('/admin/languages', { params }),
  getLanguage: (id) => api.get(`/admin/languages/${id}`),
  createLanguage: (data) => api.post('/admin/languages', data),
  updateLanguage: (id, data) => api.put(`/admin/languages/${id}`, data),
  deleteLanguage: (id) => api.delete(`/admin/languages/${id}`),

  // Language Pairs
  getLanguagePairs: (params) => api.get('/admin/language-pairs', { params }),
  getLanguagePair: (id) => api.get(`/admin/language-pairs/${id}`),
  createLanguagePair: (data) => api.post('/admin/language-pairs', data),
  updateLanguagePair: (id, data) => api.put(`/admin/language-pairs/${id}`, data),
  deleteLanguagePair: (id) => api.delete(`/admin/language-pairs/${id}`),

  // Users
  getUsers: (params) => api.get('/admin/users', { params }),
  getUser: (id) => api.get(`/admin/users/${id}`),
  updateUser: (id, data) => api.put(`/admin/users/${id}`, data),
  banUser: (id) => api.patch(`/admin/users/${id}/ban`),
  unbanUser: (id) => api.patch(`/admin/users/${id}/unban`),

  // Roles
  getRoles: (params) => api.get('/admin/roles', { params }),
  getRole: (id) => api.get(`/admin/roles/${id}`),
  createRole: (data) => api.post('/admin/roles', data),
  updateRole: (id, data) => api.put(`/admin/roles/${id}`, data),
  deleteRole: (id) => api.delete(`/admin/roles/${id}`),

  // Permissions
  getPermissions: (params) => api.get('/admin/permissions', { params }),
  getPermission: (id) => api.get(`/admin/permissions/${id}`),
  createPermission: (data) => api.post('/admin/permissions', data),
  updatePermission: (id, data) => api.put(`/admin/permissions/${id}`, data),
  deletePermission: (id) => api.delete(`/admin/permissions/${id}`),
}

export default adminService

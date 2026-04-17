import api from './api'

export const teacherService = {
  getGlossaries: (params, requestConfig = {}) =>
    api.get('/teacher/glossaries', { params, ...requestConfig }),
  getGlossary: (glossaryId, requestConfig = {}) =>
    api.get(`/teacher/glossaries/${glossaryId}`, requestConfig),
  approveGlossary: (glossaryId) => api.patch(`/teacher/glossaries/${glossaryId}/approve`),
  getStudents: (params, requestConfig = {}) =>
    api.get('/teacher/students', { params, ...requestConfig }),
  assignEditorToStudent: (userId) => api.patch(`/teacher/students/${userId}/assign-editor`),
  grantEditorToStudent: (userId) => api.patch(`/teacher/students/${userId}/assign-editor`),
}

export default teacherService

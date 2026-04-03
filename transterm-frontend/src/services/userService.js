import api from './api'

export const userService = {
  // Profile
  getProfile: () => api.get('/user/profile'),
  updateProfile: (data) => api.put('/user/profile', data),

  // Comments
  getUserComments: (params) => api.get('/user/comments', { params }),
  updateComment: (id, data) => api.put(`/user/comments/${id}`, data),
  deleteComment: (id) => api.delete(`/user/comments/${id}`),
}

export default userService

import api from './api'

export const authService = {
  register: (data) => api.post('/auth/register', data),

  login: (data) => api.post('/auth/login', data),

  resendVerificationEmail: (data) => api.post('/auth/email/verification-notification', data),

  logout: () => api.post('/auth/logout'),

  getMe: () => api.get('/auth/me'),
}

export default authService

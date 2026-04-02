import http from '@/api/http'
import { extractEntity } from '@/api/helpers'

export const userApi = {
  async getProfile() {
    const { data } = await http.get('/user/profile')
    return extractEntity(data, ['user'])
  },

  async updateProfile(payload) {
    const { data } = await http.put('/user/profile', payload)
    return {
      message: data?.message,
      user: extractEntity(data, ['user']),
    }
  },

  async listMyComments(params = {}) {
    const { data } = await http.get('/user/comments', { params })
    return data
  },

  async updateMyComment(commentId, payload) {
    const { data } = await http.put(`/user/comments/${commentId}`, payload)
    return {
      message: data?.message,
      comment: extractEntity(data),
    }
  },

  async deleteMyComment(commentId) {
    const { data } = await http.delete(`/user/comments/${commentId}`)
    return data
  },

  async createTermComment(termId, payload) {
    const { data } = await http.post(`/terms/${termId}/comments`, payload)
    return {
      message: data?.message,
      comment: extractEntity(data),
    }
  },
}

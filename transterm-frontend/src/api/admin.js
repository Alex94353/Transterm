import http from '@/api/http'
import { extractEntity } from '@/api/helpers'

function createCrudApi(basePath, entityKeys = []) {
  return {
    async list(params = {}) {
      const { data } = await http.get(basePath, { params })
      return data
    },

    async get(id) {
      const { data } = await http.get(`${basePath}/${id}`)
      return extractEntity(data, entityKeys)
    },

    async create(payload) {
      const { data } = await http.post(basePath, payload)
      return {
        message: data?.message,
        item: extractEntity(data, entityKeys),
      }
    },

    async update(id, payload) {
      const { data } = await http.put(`${basePath}/${id}`, payload)
      return {
        message: data?.message,
        item: extractEntity(data, entityKeys),
      }
    },

    async remove(id) {
      const { data } = await http.delete(`${basePath}/${id}`)
      return data
    },
  }
}

function createListApi(basePath) {
  return {
    async list(params = {}) {
      const { data } = await http.get(basePath, { params })
      return data
    },
  }
}

const termsCrud = createCrudApi('/admin/terms')
const glossariesCrud = createCrudApi('/admin/glossaries')
const referencesCrud = createCrudApi('/admin/references')
const fieldsList = createListApi('/admin/fields')
const languagePairsList = createListApi('/admin/language-pairs')
const rolesList = createListApi('/admin/roles')

export const adminApi = {
  terms: termsCrud,
  glossaries: glossariesCrud,
  references: referencesCrud,
  fields: fieldsList,
  languagePairs: languagePairsList,
  roles: rolesList,

  async listUsers(params = {}) {
    const { data } = await http.get('/admin/users', { params })
    return data
  },

  async getUser(id) {
    const { data } = await http.get(`/admin/users/${id}`)
    return extractEntity(data, ['user'])
  },

  async updateUser(id, payload) {
    const { data } = await http.put(`/admin/users/${id}`, payload)
    return {
      message: data?.message,
      user: extractEntity(data, ['user']),
    }
  },

  async banUser(id, payload) {
    const { data } = await http.patch(`/admin/users/${id}/ban`, payload)
    return {
      message: data?.message,
      user: extractEntity(data, ['user']),
    }
  },

  async unbanUser(id) {
    const { data } = await http.patch(`/admin/users/${id}/unban`)
    return {
      message: data?.message,
      user: extractEntity(data, ['user']),
    }
  },

  async listComments(params = {}) {
    const { data } = await http.get('/admin/comments', { params })
    return data
  },

  async markCommentSpam(id) {
    const { data } = await http.patch(`/admin/comments/${id}/spam`)
    return data
  },

  async unmarkCommentSpam(id) {
    const { data } = await http.patch(`/admin/comments/${id}/unspam`)
    return data
  },

  async deleteComment(id) {
    const { data } = await http.delete(`/admin/comments/${id}`)
    return data
  },
}

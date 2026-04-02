import http from '@/api/http'
import { extractEntity } from '@/api/helpers'

async function fetchList(path, params = {}) {
  const { data } = await http.get(path, { params })
  return data
}

async function fetchOne(path) {
  const { data } = await http.get(path)
  return extractEntity(data)
}

export const publicApi = {
  listTerms: (params) => fetchList('/terms', params),
  getTerm: (id) => fetchOne(`/terms/${id}`),

  listGlossaries: (params) => fetchList('/glossaries', params),
  getGlossary: (id) => fetchOne(`/glossaries/${id}`),

  listReferences: (params) => fetchList('/references', params),
  getReference: (id) => fetchOne(`/references/${id}`),

  listCountries: (params) => fetchList('/countries', params),
}

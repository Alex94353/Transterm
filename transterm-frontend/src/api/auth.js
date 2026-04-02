import http from '@/api/http'

export async function login(payload) {
  const { data } = await http.post('/auth/login', payload)
  return data
}

export async function register(payload) {
  const { data } = await http.post('/auth/register', payload)
  return data
}

export async function logout() {
  const { data } = await http.post('/auth/logout')
  return data
}

export async function getMe() {
  const { data } = await http.get('/auth/me')
  return data
}


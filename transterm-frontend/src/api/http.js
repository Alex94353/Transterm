import axios from 'axios'
import { API_URL } from '@/config/env'
import { getAccessToken } from '@/utils/storage'
import { getApiErrorMessage } from '@/utils/errors'

const http = axios.create({
  baseURL: API_URL,
  timeout: 20000,
})

http.interceptors.request.use((config) => {
  const token = getAccessToken()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  config.headers.Accept = 'application/json'

  return config
})

http.interceptors.response.use(
  (response) => response,
  (error) => {
    error.friendlyMessage = getApiErrorMessage(error)

    if (error?.response?.status === 401) {
      window.dispatchEvent(new CustomEvent('transterm:unauthorized'))
    }

    return Promise.reject(error)
  },
)

export default http


import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_URL || '/api'
const pendingByCancelKey = new Map()

export function isRequestCanceled(error) {
  return (
    axios.isCancel(error) ||
    error?.code === 'ERR_CANCELED' ||
    error?.name === 'CanceledError'
  )
}

function releaseCancelKey(config) {
  const cancelKey = config?.cancelKey
  const controller = config?.__abortController
  if (!cancelKey || !controller) return

  const activeController = pendingByCancelKey.get(cancelKey)
  if (activeController === controller) {
    pendingByCancelKey.delete(cancelKey)
  }
}

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Add token to requests
api.interceptors.request.use((config) => {
  if (config.cancelKey) {
    const previousController = pendingByCancelKey.get(config.cancelKey)
    if (previousController) {
      previousController.abort()
    }

    const controller = new AbortController()
    config.signal = controller.signal
    config.__abortController = controller
    pendingByCancelKey.set(config.cancelKey, controller)
  }

  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle errors
api.interceptors.response.use(
  (response) => {
    releaseCancelKey(response.config)
    return response
  },
  (error) => {
    releaseCancelKey(error.config)

    if (isRequestCanceled(error)) {
      return Promise.reject(error)
    }

    const status = error.response?.status
    const message = error.response?.data?.message
    const shouldResetAuth =
      status === 401 ||
      (status === 403 &&
        (message === 'Your account is not activated.' || message === 'Your account is banned.'))

    if (shouldResetAuth) {
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api

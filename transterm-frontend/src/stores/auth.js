import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService from '../services/authService'

function extractErrorMessage(err, fallback) {
  const message = err.response?.data?.message
  const errors = err.response?.data?.errors

  if (message) return message

  if (errors && typeof errors === 'object') {
    const firstKey = Object.keys(errors)[0]
    if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey][0]) {
      return errors[firstKey][0]
    }
  }

  return fallback
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => !!token.value)

  const isAdmin = computed(() => {
    if (!user.value) return false

    const hasAdminRole = user.value.roles?.some((role) => role.name?.toLowerCase() === 'admin')
    if (hasAdminRole) return true

    return (
      user.value.roles?.some((role) =>
        role.permissions?.some((permission) => permission.name === 'admin.access'),
      ) || false
    )
  })

  async function register(payload) {
    loading.value = true
    error.value = null
    try {
      const response = await authService.register(payload)
      const registeredUser = response.data.user || null
      const accessToken = response.data.access_token || response.data.token || null

      // Newly created accounts can be inactive on backend.
      // In that case protected endpoints return 403, so we keep user as guest.
      if (registeredUser && registeredUser.activated === false) {
        token.value = null
        user.value = null
        localStorage.removeItem('auth_token')

        return {
          ...response.data,
          requiresActivation: true,
        }
      }

      token.value = accessToken
      user.value = registeredUser

      if (token.value) {
        localStorage.setItem('auth_token', token.value)
        try {
          const meResponse = await authService.getMe()
          user.value = meResponse.data.user
        } catch {
          // fallback to register response user
        }
      }

      return response.data
    } catch (err) {
      error.value = extractErrorMessage(err, 'Registration failed')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function login(login, password) {
    loading.value = true
    error.value = null
    try {
      const response = await authService.login({ login, password })
      token.value = response.data.access_token || response.data.token || null
      user.value = response.data.user

      if (token.value) {
        localStorage.setItem('auth_token', token.value)
        try {
          const meResponse = await authService.getMe()
          user.value = meResponse.data.user
        } catch {
          // fallback to login response user
        }
      }

      return response.data
    } catch (err) {
      error.value = extractErrorMessage(err, 'Login failed')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authService.logout()
    } catch (err) {
      console.error('Logout error:', err)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  async function getCurrentUser() {
    if (!token.value) return null

    loading.value = true
    try {
      const response = await authService.getMe()
      user.value = response.data.user
      return response.data.user
    } catch (err) {
      console.error('Failed to get current user:', err)
      token.value = null
      localStorage.removeItem('auth_token')
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    register,
    login,
    logout,
    getCurrentUser,
  }
})

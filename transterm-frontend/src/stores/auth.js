import { defineStore } from 'pinia'
import * as authApi from '@/api/auth'
import { clearAccessToken, getAccessToken, setAccessToken } from '@/utils/storage'

let unauthorizedListenerBound = false

function flattenPermissions(user) {
  const names = new Set()

  for (const role of user?.roles || []) {
    for (const permission of role?.permissions || []) {
      if (permission?.name) {
        names.add(permission.name)
      }
    }
  }

  return Array.from(names)
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: getAccessToken(),
    user: null,
    initialized: false,
    initializing: false,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token && state.user),

    roleNames: (state) => (state.user?.roles || []).map((role) => role.name),

    permissionNames: (state) => flattenPermissions(state.user),

    canAccessAdmin() {
      return this.roleNames.includes('Admin') || this.permissionNames.includes('admin.access')
    },
  },

  actions: {
    hasPermission(permissionName) {
      if (!permissionName) {
        return true
      }

      return this.canAccessAdmin || this.permissionNames.includes(permissionName)
    },

    setToken(token) {
      this.token = token

      if (token) {
        setAccessToken(token)
      } else {
        clearAccessToken()
      }
    },

    clearSession() {
      this.setToken(null)
      this.user = null
    },

    async fetchMe() {
      const data = await authApi.getMe()
      this.user = data?.user || null
      return this.user
    },

    async login(payload) {
      const response = await authApi.login(payload)
      this.setToken(response?.access_token || null)
      await this.fetchMe()
      return response
    },

    async register(payload) {
      const response = await authApi.register(payload)
      this.setToken(response?.access_token || null)
      await this.fetchMe()
      return response
    },

    async logout() {
      try {
        if (this.token) {
          await authApi.logout()
        }
      } catch {
        // ignore transport errors on logout
      } finally {
        this.clearSession()
      }
    },

    async init() {
      if (this.initialized || this.initializing) {
        return
      }

      this.initializing = true

      if (!unauthorizedListenerBound) {
        window.addEventListener('transterm:unauthorized', () => {
          this.clearSession()
        })
        unauthorizedListenerBound = true
      }

      try {
        this.token = getAccessToken()

        if (this.token) {
          await this.fetchMe()
        }
      } catch {
        this.clearSession()
      } finally {
        this.initialized = true
        this.initializing = false
      }
    },
  },
})


/**
 * API Configuration
 *
 * This file contains all API-related configuration for the frontend.
 * Most settings can be overridden via environment variables.
 */

function resolveApiBaseUrl() {
  const raw = (import.meta.env.VITE_API_URL || '/api').trim()
  const normalized = raw.replace(/\/+$/, '') || '/api'

  if (!import.meta.env.PROD) {
    return normalized
  }

  if (normalized === '/api') {
    return normalized
  }

  try {
    const parsed = new URL(normalized)
    const isLocalhost =
      parsed.hostname === 'localhost' ||
      parsed.hostname === '127.0.0.1' ||
      parsed.hostname === '::1'

    if (isLocalhost) {
      return '/api'
    }

    if (parsed.protocol !== 'https:') {
      throw new Error('VITE_API_URL must use HTTPS in production')
    }

    if (!parsed.pathname.endsWith('/api')) {
      throw new Error('VITE_API_URL should end with /api in production')
    }
  } catch {
    throw new Error(
      `[Transterm] Invalid VITE_API_URL "${raw}". Use "/api" or an absolute HTTPS URL ending with "/api".`
    )
  }

  return normalized
}

const config = {
  // API Base URL
  api: {
    baseUrl: resolveApiBaseUrl(),
    timeout: import.meta.env.VITE_API_TIMEOUT || 10000,
  },

  // Application
  app: {
    name: 'Transterm',
    version: '1.0.0',
    environment: import.meta.env.MODE,
  },

  // Features
  features: {
    enableComments: true,
    enableAdmin: true,
    enableSearch: true,
  },

  // Storage keys
  storage: {
    authToken: 'auth_token',
    currentUser: 'current_user',
  },

  // Pagination
  pagination: {
    defaultPageSize: 20,
    pageSizeOptions: [10, 20, 50, 100],
  },

  // Cache durations (in milliseconds)
  cache: {
    glossaries: 5 * 60 * 1000, // 5 minutes
    languages: 30 * 60 * 1000, // 30 minutes
  },
}

export default config

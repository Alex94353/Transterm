/**
 * API Configuration
 *
 * This file contains all API-related configuration for the frontend.
 * Most settings can be overridden via environment variables.
 */

const config = {
  // API Base URL
  api: {
    baseUrl: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
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

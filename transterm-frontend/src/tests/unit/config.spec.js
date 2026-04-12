import { afterEach, describe, expect, it, vi } from 'vitest'

async function importConfigWithEnv(env = {}) {
  vi.resetModules()
  vi.unstubAllEnvs()

  for (const [key, value] of Object.entries(env)) {
    vi.stubEnv(key, value)
  }

  const module = await import('@/config')
  return module.default
}

describe('config', () => {
  afterEach(() => {
    vi.unstubAllEnvs()
  })

  it('exports expected sections and defaults', async () => {
    const config = await importConfigWithEnv({
      VITE_API_URL: '/api',
      VITE_API_TIMEOUT: '',
    })

    expect(config).toBeDefined()
    expect(config.api.baseUrl).toBe('/api')
    expect(config.api.timeout).toBe(10000)
    expect(config.app.name).toBe('Transterm')
    expect(config.features.enableAdmin).toBe(true)
    expect(config.storage.authToken).toBe('auth_token')
    expect(config.pagination.pageSizeOptions).toEqual([10, 20, 50, 100])
    expect(config.cache.glossaries).toBe(300000)
    expect(config.cache.languages).toBe(1800000)
  })

  it('normalizes VITE_API_URL by trimming and removing trailing slashes', async () => {
    const config = await importConfigWithEnv({
      VITE_API_URL: '  /api///  ',
    })

    expect(config.api.baseUrl).toBe('/api')
  })

  it('falls back to /api when VITE_API_URL becomes empty after trim', async () => {
    const config = await importConfigWithEnv({
      VITE_API_URL: '   ',
    })

    expect(config.api.baseUrl).toBe('/api')
  })

  it('uses VITE_API_TIMEOUT when provided', async () => {
    const config = await importConfigWithEnv({
      VITE_API_TIMEOUT: '7000',
    })

    expect(config.api.timeout).toBe('7000')
  })
})

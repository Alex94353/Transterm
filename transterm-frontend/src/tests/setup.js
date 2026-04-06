import { afterEach, vi } from 'vitest'

afterEach(() => {
  vi.clearAllMocks()
  vi.restoreAllMocks()
  localStorage.clear()
  document.documentElement.classList.remove('dark-theme')
  document.title = 'Transterm'
})

import { describe, expect, it, vi } from 'vitest'
import {
  debounce,
  formatDate,
  formatDateTime,
  generateId,
  hasPermission,
  hasRole,
  isValidUrl,
  truncateText,
} from '@/utils'

describe('utils', () => {
  it('formats date with provided locale', () => {
    const value = formatDate('2024-01-02T00:00:00Z', 'en-US')

    expect(value).toBe('1/2/2024')
  })

  it('formats date time to a non-empty string', () => {
    const value = formatDateTime('2024-01-02T12:34:56Z')

    expect(typeof value).toBe('string')
    expect(value.length).toBeGreaterThan(0)
  })

  it('truncates text only when needed', () => {
    expect(truncateText('short', 10)).toBe('short')
    expect(truncateText('abcdefghijklmnopqrstuvwxyz', 5)).toBe('abcde...')
    expect(truncateText('', 5)).toBe('')
  })

  it('validates url strings', () => {
    expect(isValidUrl('https://example.com/path')).toBe(true)
    expect(isValidUrl('not-an-url')).toBe(false)
  })

  it('generates deterministic id shape', () => {
    vi.spyOn(Date, 'now').mockReturnValue(1700000000000)
    vi.spyOn(Math, 'random').mockReturnValue(0.123456789)

    const id = generateId()

    expect(id).toMatch(/^id_1700000000000_[a-z0-9]{9}$/)
  })

  it('debounces calls and preserves latest args and this context', () => {
    vi.useFakeTimers()

    const callback = vi.fn()
    const ctx = {
      value: 5,
      run: debounce(function (a, b) {
        callback(this.value, a, b)
      }, 200),
    }

    ctx.run(1, 2)
    ctx.run(3, 4)
    vi.advanceTimersByTime(199)
    expect(callback).not.toHaveBeenCalled()

    vi.advanceTimersByTime(1)
    expect(callback).toHaveBeenCalledTimes(1)
    expect(callback).toHaveBeenCalledWith(5, 3, 4)

    vi.useRealTimers()
  })

  it('checks permissions and roles safely', () => {
    const user = {
      permissions: [{ name: 'terms.edit' }],
      roles: [{ name: 'editor' }],
    }

    expect(hasPermission(user, 'terms.edit')).toBe(true)
    expect(hasPermission(user, 'terms.delete')).toBe(false)
    expect(hasPermission(null, 'anything')).toBe(false)

    expect(hasRole(user, 'editor')).toBe(true)
    expect(hasRole(user, 'admin')).toBe(false)
    expect(hasRole(undefined, 'editor')).toBe(false)
  })
})

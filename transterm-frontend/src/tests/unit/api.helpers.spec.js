import { describe, expect, it } from 'vitest'
import { isRequestCanceled } from '@/services/api'

describe('api helpers', () => {
  it('detects axios cancel error by code', () => {
    expect(isRequestCanceled({ code: 'ERR_CANCELED' })).toBe(true)
  })

  it('detects cancel error by name', () => {
    expect(isRequestCanceled({ name: 'CanceledError' })).toBe(true)
  })

  it('returns false for regular errors', () => {
    expect(isRequestCanceled({ message: 'Server error' })).toBe(false)
  })

  it('returns false for nullish values', () => {
    expect(isRequestCanceled(undefined)).toBe(false)
    expect(isRequestCanceled(null)).toBe(false)
  })
})

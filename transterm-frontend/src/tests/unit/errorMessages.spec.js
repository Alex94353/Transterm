import { describe, expect, it } from 'vitest'
import { getSafeApiErrorMessage, getSafeDeleteErrorMessage } from '@/services/errorMessages'

describe('errorMessages helpers', () => {
  it('returns safe 422 delete message instead of backend details', () => {
    const err = {
      response: {
        status: 422,
        data: {
          message: 'Cannot delete language in use (source pairs: 2, target pairs: 1).',
        },
      },
    }

    expect(getSafeDeleteErrorMessage(err, 'language')).toBe('This item cannot be deleted right now.')
  })

  it('maps common delete statuses to safe generic text', () => {
    expect(getSafeDeleteErrorMessage({ response: { status: 403 } }, 'field'))
      .toBe('You do not have permission to perform this action.')

    expect(getSafeDeleteErrorMessage({ response: { status: 404 } }, 'field'))
      .toBe('The requested item no longer exists.')

    expect(getSafeDeleteErrorMessage({ response: { status: 500 } }, 'field'))
      .toBe('Server error. Please try again later.')
  })

  it('returns network-safe message when there is no response object', () => {
    expect(getSafeDeleteErrorMessage({ message: 'Network Error' }, 'field'))
      .toBe('Unable to reach the server. Please try again.')
  })

  it('falls back for unknown statuses in generic helper', () => {
    expect(getSafeApiErrorMessage({ response: { status: 409 } }, 'Operation failed'))
      .toBe('Operation failed')
  })
})

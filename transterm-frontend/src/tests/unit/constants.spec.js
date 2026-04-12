import { describe, expect, it } from 'vitest'
import {
  HTTP_STATUS,
  PERMISSIONS,
  ROLES,
  SORT_OPTIONS,
  UI_MESSAGES,
  VALIDATION_MESSAGES,
} from '@/constants'

describe('constants', () => {
  it('defines expected role constants', () => {
    expect(ROLES.ADMIN).toBe('admin')
    expect(ROLES.USER).toBe('user')
    expect(ROLES.MODERATOR).toBe('moderator')
  })

  it('defines expected permission constants', () => {
    expect(PERMISSIONS.ADMIN_ACCESS).toBe('admin.access')
    expect(PERMISSIONS.CREATE_GLOSSARY).toBe('glossaries.create')
    expect(PERMISSIONS.DELETE_TERM).toBe('terms.delete')
  })

  it('defines expected http status constants', () => {
    expect(HTTP_STATUS.OK).toBe(200)
    expect(HTTP_STATUS.CREATED).toBe(201)
    expect(HTTP_STATUS.NOT_FOUND).toBe(404)
    expect(HTTP_STATUS.INTERNAL_SERVER_ERROR).toBe(500)
  })

  it('returns formatted validation messages for dynamic helpers', () => {
    expect(VALIDATION_MESSAGES.MIN_LENGTH(8)).toBe('Must be at least 8 characters')
    expect(VALIDATION_MESSAGES.MAX_LENGTH(30)).toBe('Must not exceed 30 characters')
  })

  it('defines expected ui messages and sort options', () => {
    expect(UI_MESSAGES.SUCCESS_LOGIN).toBe('Login successful')
    expect(UI_MESSAGES.ERROR_NETWORK).toBe('Network error. Please check your connection.')
    expect(SORT_OPTIONS.NAME_ASC).toBe('name_asc')
    expect(SORT_OPTIONS.DATE_OLD).toBe('date_old')
  })
})

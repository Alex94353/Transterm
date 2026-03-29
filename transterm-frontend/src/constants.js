/**
 * Application Constants
 *
 * This file contains constant values used throughout the application.
 */

export const ROLES = {
  ADMIN: 'admin',
  USER: 'user',
  MODERATOR: 'moderator',
}

export const PERMISSIONS = {
  ADMIN_ACCESS: 'admin.access',
  CREATE_GLOSSARY: 'glossaries.create',
  EDIT_GLOSSARY: 'glossaries.edit',
  DELETE_GLOSSARY: 'glossaries.delete',
  CREATE_TERM: 'terms.create',
  EDIT_TERM: 'terms.edit',
  DELETE_TERM: 'terms.delete',
  MODERATE_COMMENTS: 'comments.moderate',
}

export const HTTP_STATUS = {
  OK: 200,
  CREATED: 201,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  INTERNAL_SERVER_ERROR: 500,
}

export const VALIDATION_MESSAGES = {
  REQUIRED: 'This field is required',
  EMAIL: 'Please enter a valid email address',
  MIN_LENGTH: (length) => `Must be at least ${length} characters`,
  MAX_LENGTH: (length) => `Must not exceed ${length} characters`,
}

export const UI_MESSAGES = {
  SUCCESS_CREATE: 'Created successfully',
  SUCCESS_UPDATE: 'Updated successfully',
  SUCCESS_DELETE: 'Deleted successfully',
  SUCCESS_LOGIN: 'Login successful',
  SUCCESS_LOGOUT: 'Logged out successfully',
  ERROR_GENERIC: 'An error occurred. Please try again.',
  ERROR_NETWORK: 'Network error. Please check your connection.',
  ERROR_UNAUTHORIZED: 'You are not authorized to perform this action.',
  CONFIRM_DELETE: 'Are you sure you want to delete this?',
}

export const SORT_OPTIONS = {
  NAME_ASC: 'name_asc',
  NAME_DESC: 'name_desc',
  DATE_NEW: 'date_new',
  DATE_OLD: 'date_old',
}

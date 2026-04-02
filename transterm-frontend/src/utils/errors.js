export function getApiErrorMessage(error, fallback = 'Request failed.') {
  const status = error?.response?.status
  const message = error?.response?.data?.message

  if (message) {
    return message
  }

  if (status === 401) {
    return 'Authentication required.'
  }

  if (status === 403) {
    return 'You do not have access to this action.'
  }

  if (status === 422) {
    return 'Validation failed.'
  }

  if (status >= 500) {
    return 'Server error. Please try again later.'
  }

  return fallback
}

export function getValidationErrors(error) {
  return error?.response?.status === 422 ? error?.response?.data?.errors || {} : {}
}


function toHttpStatus(error) {
  const status = Number(error?.response?.status)
  return Number.isFinite(status) ? status : null
}

export function getSafeApiErrorMessage(error, fallbackMessage = 'Request failed', statusMessages = {}) {
  const status = toHttpStatus(error)

  if (status && statusMessages[status]) {
    return statusMessages[status]
  }

  if (!error?.response) {
    return 'Unable to reach the server. Please try again.'
  }

  return fallbackMessage
}

export function getSafeDeleteErrorMessage(error, entityName = 'item') {
  const normalizedEntity = String(entityName || 'item').toLowerCase()

  return getSafeApiErrorMessage(
    error,
    `Failed to delete ${normalizedEntity}`,
    {
      403: 'You do not have permission to perform this action.',
      404: 'The requested item no longer exists.',
      422: 'This item cannot be deleted right now.',
      500: 'Server error. Please try again later.',
      502: 'Service temporarily unavailable. Please try again later.',
      503: 'Service temporarily unavailable. Please try again later.',
    },
  )
}

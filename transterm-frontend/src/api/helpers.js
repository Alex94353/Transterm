export function extractEntity(payload, keys = []) {
  if (!payload) {
    return null
  }

  if (payload.data && !Array.isArray(payload.data)) {
    return payload.data
  }

  for (const key of keys) {
    if (payload[key] !== undefined) {
      return payload[key]
    }
  }

  return payload
}

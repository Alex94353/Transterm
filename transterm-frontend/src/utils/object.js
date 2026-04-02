export function resolvePath(object, path, fallback = '') {
  if (!path) {
    return fallback
  }

  return path.split('.').reduce((value, key) => {
    if (value === null || value === undefined) {
      return undefined
    }

    return value[key]
  }, object) ?? fallback
}

export function cleanQuery(params = {}) {
  const output = {}

  Object.entries(params).forEach(([key, value]) => {
    if (value === '' || value === null || value === undefined) {
      return
    }

    output[key] = value
  })

  return output
}


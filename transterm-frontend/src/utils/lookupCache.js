const lookupCache = new Map()
const pendingLookups = new Map()

function isFresh(entry) {
  return entry && entry.expiresAt > Date.now()
}

export async function getCachedLookup(key, loader, options = {}) {
  const ttlMs = Number(options.ttlMs || 5 * 60 * 1000)
  const cached = lookupCache.get(key)

  if (isFresh(cached)) {
    return cached.value
  }

  if (pendingLookups.has(key)) {
    return pendingLookups.get(key)
  }

  const promise = Promise.resolve()
    .then(loader)
    .then((value) => {
      lookupCache.set(key, {
        value,
        expiresAt: Date.now() + ttlMs,
      })

      return value
    })
    .finally(() => {
      pendingLookups.delete(key)
    })

  pendingLookups.set(key, promise)

  return promise
}

export function clearLookupCache(prefix = null) {
  if (!prefix) {
    lookupCache.clear()
    pendingLookups.clear()
    return
  }

  for (const key of Array.from(lookupCache.keys())) {
    if (String(key).startsWith(prefix)) {
      lookupCache.delete(key)
    }
  }

  for (const key of Array.from(pendingLookups.keys())) {
    if (String(key).startsWith(prefix)) {
      pendingLookups.delete(key)
    }
  }
}

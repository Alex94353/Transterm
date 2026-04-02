function toNumberOr(value, fallback) {
  const number = Number(value)
  return Number.isFinite(number) ? number : fallback
}

export function getListItems(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  return Array.isArray(payload?.data) ? payload.data : []
}

export function getListMeta(payload) {
  const items = getListItems(payload)
  const meta = payload?.meta || payload || {}

  return {
    current_page: toNumberOr(meta?.current_page, 1),
    last_page: toNumberOr(meta?.last_page, 1),
    per_page: toNumberOr(meta?.per_page, 10),
    total: toNumberOr(meta?.total, items.length),
  }
}

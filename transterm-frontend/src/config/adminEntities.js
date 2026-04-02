import { adminApi } from '@/api/admin'
import { resolvePath } from '@/utils/object'
import { getCachedLookup } from '@/utils/lookupCache'

function firstTitle(item) {
  return item?.title || item?.translations?.[0]?.title || '-'
}

function languagePairLabel(pair) {
  if (!pair) {
    return '-'
  }

  const source = pair?.source_language?.code || pair?.source_language?.name || '?'
  const target = pair?.target_language?.code || pair?.target_language?.name || '?'

  return `${source} -> ${target}`
}

const LOOKUP_CACHE_TTL_MS = 10 * 60 * 1000

async function loadLookupList(cacheKey, loader, labelFormatter, valueKey = 'id') {
  return getCachedLookup(
    cacheKey,
    async () => {
      const response = await loader({ per_page: 200, id_order: 'asc' })
      const items = Array.isArray(response?.data) ? response.data : []

      return items.map((item) => ({
        label: labelFormatter(item),
        value: item[valueKey],
      }))
    },
    { ttlMs: LOOKUP_CACHE_TTL_MS },
  )
}

export const adminEntities = {
  glossaries: {
    key: 'glossaries',
    title: 'Admin: Glossaries',
    permissions: {
      create: 'glossary.create',
      update: 'glossary.update',
      delete: 'glossary.delete',
    },
    list: adminApi.glossaries.list,
    get: adminApi.glossaries.get,
    create: adminApi.glossaries.create,
    update: adminApi.glossaries.update,
    remove: adminApi.glossaries.remove,
    defaultFilters: {
      search: '',
      id: '',
      language_pair_id: '',
      field_id: '',
      owner_id: '',
      approved: '',
      is_public: '',
      id_order: 'desc',
    },
    filters: [
      { key: 'search', label: 'Search', type: 'text', placeholder: 'title or description...' },
      { key: 'id', label: 'ID', type: 'number' },
      { key: 'language_pair_id', label: 'Pair ID', type: 'number' },
      { key: 'field_id', label: 'Field ID', type: 'number' },
      { key: 'owner_id', label: 'Owner ID', type: 'number' },
      {
        key: 'approved',
        label: 'Approved',
        type: 'select',
        options: [
          { label: 'Yes', value: true },
          { label: 'No', value: false },
        ],
      },
      {
        key: 'is_public',
        label: 'Public',
        type: 'select',
        options: [
          { label: 'Yes', value: true },
          { label: 'No', value: false },
        ],
      },
      {
        key: 'id_order',
        label: 'ID order',
        type: 'select',
        options: [
          { label: 'Desc', value: 'desc' },
          { label: 'Asc', value: 'asc' },
        ],
      },
    ],
    columns: [
      { key: 'id', label: 'ID', width: 90 },
      { key: 'title', label: 'Title', formatter: firstTitle },
      { key: 'pair', label: 'Pair', formatter: (row) => languagePairLabel(row?.language_pair) },
      { key: 'field.name', label: 'Field' },
      { key: 'owner.username', label: 'Owner' },
      { key: 'approved', label: 'Approved', formatter: (row) => (row?.approved ? 'Yes' : 'No') },
      { key: 'is_public', label: 'Public', formatter: (row) => (row?.is_public ? 'Yes' : 'No') },
      { key: 'terms_count', label: 'Terms', width: 80 },
    ],
    formDefaults: {
      language_pair_id: null,
      field_id: null,
      approved: false,
      is_public: false,
    },
    formFields: [
      {
        key: 'language_pair_id',
        label: 'Language pair',
        type: 'select',
        required: true,
        lookup: 'languagePairs',
      },
      {
        key: 'field_id',
        label: 'Field',
        type: 'select',
        required: true,
        lookup: 'fields',
      },
      { key: 'approved', label: 'Approved', type: 'switch' },
      { key: 'is_public', label: 'Public', type: 'switch' },
    ],
    async loadLookups() {
      const [languagePairs, fields] = await Promise.all([
        loadLookupList('admin:languagePairs', adminApi.languagePairs.list, languagePairLabel),
        loadLookupList('admin:fields', adminApi.fields.list, (item) => item.name),
      ])

      return { languagePairs, fields }
    },
  },

  terms: {
    key: 'terms',
    title: 'Admin: Terms',
    permissions: {
      create: 'term.create',
      update: 'term.update',
      delete: 'term.delete',
    },
    list: adminApi.terms.list,
    get: adminApi.terms.get,
    create: adminApi.terms.create,
    update: adminApi.terms.update,
    remove: adminApi.terms.remove,
    defaultFilters: {
      search: '',
      id: '',
      glossary_id: '',
      field_id: '',
      created_by: '',
      id_order: 'desc',
    },
    filters: [
      { key: 'search', label: 'Search', type: 'text', placeholder: 'translation text...' },
      { key: 'id', label: 'ID', type: 'number' },
      { key: 'glossary_id', label: 'Glossary ID', type: 'number' },
      { key: 'field_id', label: 'Field ID', type: 'number' },
      { key: 'created_by', label: 'Creator ID', type: 'number' },
      {
        key: 'id_order',
        label: 'ID order',
        type: 'select',
        options: [
          { label: 'Desc', value: 'desc' },
          { label: 'Asc', value: 'asc' },
        ],
      },
    ],
    columns: [
      { key: 'id', label: 'ID', width: 90 },
      { key: 'title', label: 'Title', formatter: firstTitle },
      { key: 'field.name', label: 'Field' },
      { key: 'glossary', label: 'Glossary', formatter: (row) => row?.glossary_title || row?.glossary?.translations?.[0]?.title || '-' },
      { key: 'creator.username', label: 'Creator' },
      { key: 'translations_count', label: 'Translations', width: 120 },
      { key: 'comments_count', label: 'Comments', width: 110 },
    ],
    formDefaults: { glossary_id: null, field_id: null },
    formFields: [
      {
        key: 'glossary_id',
        label: 'Glossary',
        type: 'select',
        required: true,
        lookup: 'glossaries',
      },
      {
        key: 'field_id',
        label: 'Field',
        type: 'select',
        required: true,
        lookup: 'fields',
      },
    ],
    async loadLookups() {
      const [glossaries, fields] = await Promise.all([
        loadLookupList(
          'admin:glossaries',
          adminApi.glossaries.list,
          (item) => `#${item.id} ${item?.title || item?.translations?.[0]?.title || 'Untitled'}`,
        ),
        loadLookupList('admin:fields', adminApi.fields.list, (item) => item.name),
      ])

      return { glossaries, fields }
    },
  },

  references: {
    key: 'references',
    title: 'Admin: References',
    permissions: {
      create: 'reference.create',
      update: 'reference.update',
      delete: 'reference.delete',
    },
    list: adminApi.references.list,
    get: adminApi.references.get,
    create: adminApi.references.create,
    update: adminApi.references.update,
    remove: adminApi.references.remove,
    defaultFilters: {
      search: '',
      id: '',
      user_id: '',
      type: '',
      language: '',
      id_order: 'desc',
    },
    filters: [
      { key: 'search', label: 'Search', type: 'text', placeholder: 'source, type, language...' },
      { key: 'id', label: 'ID', type: 'number' },
      { key: 'user_id', label: 'User ID', type: 'number' },
      { key: 'type', label: 'Type', type: 'text' },
      { key: 'language', label: 'Language', type: 'text' },
      {
        key: 'id_order',
        label: 'ID order',
        type: 'select',
        options: [
          { label: 'Desc', value: 'desc' },
          { label: 'Asc', value: 'asc' },
        ],
      },
    ],
    columns: [
      { key: 'id', label: 'ID', width: 90 },
      { key: 'source', label: 'Source' },
      { key: 'type', label: 'Type' },
      { key: 'language', label: 'Language' },
      { key: 'user.username', label: 'User' },
      { key: 'term_references_count', label: 'Links', width: 90 },
    ],
    formDefaults: { source: '', type: '', language: '', user_id: null },
    formFields: [
      { key: 'source', label: 'Source', type: 'textarea', required: true },
      { key: 'type', label: 'Type', type: 'text' },
      { key: 'language', label: 'Language', type: 'text' },
      { key: 'user_id', label: 'User', type: 'select', lookup: 'users' },
    ],
    async loadLookups() {
      return {
        users: await loadLookupList(
          'admin:users',
          adminApi.listUsers,
          (item) => `${item.username || item.email} (#${item.id})`,
        ),
      }
    },
  },
}

export function getAdminEntity(entityKey) {
  return adminEntities[entityKey] || null
}

export function renderAdminCell(row, column) {
  if (typeof column?.formatter === 'function') {
    return column.formatter(row)
  }

  return resolvePath(row, column?.key, '-')
}

import { publicApi } from '@/api/public'
import { formatDate } from '@/utils/date'
import { resolvePath } from '@/utils/object'

function firstTranslationTitle(entity) {
  return entity?.translations?.[0]?.title || '-'
}

function glossaryPair(entity) {
  const source = entity?.language_pair?.source_language?.code || '?'
  const target = entity?.language_pair?.target_language?.code || '?'
  return `${source} -> ${target}`
}

function termGlossaryTitle(entity) {
  return entity?.glossary?.translations?.[0]?.title || '-'
}

function flattenTermReferences(term) {
  const rows = []

  for (const translation of term?.translations || []) {
    for (const reference of translation?.references || []) {
      rows.push({
        translation_title: translation?.title || '-',
        source: reference?.reference?.source || '-',
        type: reference?.reference?.type || reference?.reference_type || '-',
      })
    }
  }

  return rows
}

export const publicEntities = {
  terms: {
    key: 'terms',
    title: 'Terms',
    description: 'Browse terminology entries and full translation context.',
    list: publicApi.listTerms,
    get: publicApi.getTerm,
    filters: [
      { key: 'search', label: 'Search', type: 'text', placeholder: 'title, definition...' },
      { key: 'id', label: 'ID', type: 'number' },
      { key: 'glossary_id', label: 'Glossary ID', type: 'number' },
      { key: 'field_id', label: 'Field ID', type: 'number' },
      { key: 'created_by', label: 'Author ID', type: 'number' },
      {
        key: 'approved',
        label: 'Approved glossary',
        type: 'select',
        options: [
          { label: 'Yes', value: true },
          { label: 'No', value: false },
        ],
      },
      {
        key: 'is_public',
        label: 'Public glossary',
        type: 'select',
        options: [
          { label: 'Yes', value: true },
          { label: 'No', value: false },
        ],
      },
    ],
    columns: [
      { key: 'id', label: 'ID', width: 80 },
      { key: 'title', label: 'Title', formatter: (row) => row?.translations?.[0]?.title || '-' },
      { key: 'field', label: 'Field', formatter: (row) => row?.field?.name || '-' },
      { key: 'glossary', label: 'Glossary', formatter: termGlossaryTitle },
      { key: 'comments_count', label: 'Comments', width: 110 },
      { key: 'translations_count', label: 'Translations', width: 120 },
      { key: 'created_at', label: 'Created', formatter: (row) => formatDate(row?.created_at) },
    ],
    detailSummary: [
      { label: 'ID', key: 'id' },
      { label: 'Glossary ID', key: 'glossary_id' },
      { label: 'Field ID', key: 'field_id' },
      { label: 'Created by', key: 'created_by' },
      { label: 'Created', formatter: (item) => formatDate(item?.created_at) },
      { label: 'Updated', formatter: (item) => formatDate(item?.updated_at) },
    ],
    detailSections: [
      {
        title: 'Translations',
        rows: (item) => item?.translations || [],
        columns: [
          { key: 'language.code', label: 'Language' },
          { key: 'title', label: 'Title' },
          { key: 'plural', label: 'Plural' },
          { key: 'definition', label: 'Definition' },
          { key: 'context', label: 'Context' },
          { key: 'synonym', label: 'Synonym' },
          { key: 'notes', label: 'Notes' },
        ],
      },
      {
        title: 'References',
        rows: flattenTermReferences,
        columns: [
          { key: 'translation_title', label: 'Translation' },
          { key: 'source', label: 'Source' },
          { key: 'type', label: 'Type' },
        ],
      },
      {
        title: 'Comments',
        rows: (item) => item?.comments || [],
        columns: [
          { key: 'user.username', label: 'Author' },
          { key: 'body', label: 'Comment' },
          { key: 'is_spam', label: 'Spam', formatter: (row) => (row?.is_spam ? 'Yes' : 'No') },
          { key: 'created_at', label: 'Created', formatter: (row) => formatDate(row?.created_at) },
        ],
      },
    ],
  },

  glossaries: {
    key: 'glossaries',
    title: 'Glossaries',
    description: 'Public glossaries with language pairs and translations.',
    list: publicApi.listGlossaries,
    get: publicApi.getGlossary,
    filters: [
      { key: 'search', label: 'Search', type: 'text', placeholder: 'glossary title...' },
      { key: 'field_id', label: 'Field ID', type: 'number' },
      { key: 'owner_id', label: 'Owner ID', type: 'number' },
      { key: 'language_pair_id', label: 'Pair ID', type: 'number' },
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
    ],
    columns: [
      { key: 'id', label: 'ID', width: 80 },
      { key: 'title', label: 'Title', formatter: firstTranslationTitle },
      { key: 'pair', label: 'Pair', formatter: glossaryPair },
      { key: 'field.name', label: 'Field' },
      { key: 'approved', label: 'Approved', formatter: (row) => (row?.approved ? 'Yes' : 'No') },
      { key: 'is_public', label: 'Public', formatter: (row) => (row?.is_public ? 'Yes' : 'No') },
      { key: 'terms_count', label: 'Terms', width: 80 },
    ],
    detailSummary: [
      { label: 'ID', key: 'id' },
      { label: 'Owner', formatter: (item) => item?.owner?.username || item?.owner?.email || '-' },
      { label: 'Field', key: 'field.name' },
      { label: 'Pair', formatter: glossaryPair },
      { label: 'Approved', formatter: (item) => (item?.approved ? 'Yes' : 'No') },
      { label: 'Public', formatter: (item) => (item?.is_public ? 'Yes' : 'No') },
      { label: 'Created', formatter: (item) => formatDate(item?.created_at) },
    ],
    detailSections: [
      {
        title: 'Translations',
        rows: (item) => item?.translations || [],
        columns: [
          { key: 'language_id', label: 'Language ID' },
          { key: 'title', label: 'Title' },
          { key: 'description', label: 'Description' },
        ],
      },
      {
        title: 'Terms',
        rows: (item) => item?.terms || [],
        columns: [
          { key: 'id', label: 'Term ID' },
          {
            key: 'translations',
            label: 'Main title',
            formatter: (row) => row?.translations?.[0]?.title || '-',
          },
          { key: 'created_at', label: 'Created', formatter: (row) => formatDate(row?.created_at) },
        ],
      },
    ],
  },

  references: {
    key: 'references',
    title: 'References',
    description: 'Reference sources connected to term translations.',
    list: publicApi.listReferences,
    get: publicApi.getReference,
    filters: [
      { key: 'search', label: 'Search', type: 'text', placeholder: 'source...' },
      { key: 'id', label: 'ID', type: 'number' },
      { key: 'user_id', label: 'User ID', type: 'number' },
      { key: 'type', label: 'Type', type: 'text' },
      { key: 'language', label: 'Language', type: 'text' },
    ],
    columns: [
      { key: 'id', label: 'ID', width: 80 },
      { key: 'source', label: 'Source' },
      { key: 'type', label: 'Type' },
      { key: 'language', label: 'Language' },
      { key: 'user.username', label: 'User' },
      { key: 'term_references_count', label: 'Links', width: 90 },
    ],
    detailSummary: [
      { label: 'ID', key: 'id' },
      { label: 'Source', key: 'source' },
      { label: 'Type', key: 'type' },
      { label: 'Language', key: 'language' },
      { label: 'User', formatter: (item) => item?.user?.username || item?.user?.email || '-' },
      { label: 'Created', formatter: (item) => formatDate(item?.created_at) },
    ],
    detailSections: [
      {
        title: 'Linked translations',
        rows: (item) => item?.term_references || [],
        columns: [
          { key: 'id', label: 'ID' },
          { key: 'term_translation.title', label: 'Translation' },
          { key: 'term_translation.language.code', label: 'Language' },
          { key: 'type', label: 'Type' },
        ],
      },
    ],
  },
}

export function getPublicEntity(entityKey) {
  return publicEntities[entityKey] || null
}

export function renderValueByColumn(row, column) {
  if (typeof column?.formatter === 'function') {
    return column.formatter(row)
  }

  return resolvePath(row, column?.key, '-')
}

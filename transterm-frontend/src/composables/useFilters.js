export function resetFilterModel(model, definitions = [], defaults = {}) {
  for (const key of Object.keys(model)) {
    delete model[key]
  }

  for (const field of definitions) {
    const defaultValue = defaults[field.key]

    if (defaultValue !== undefined) {
      model[field.key] = field.type === 'number' && defaultValue === '' ? null : defaultValue
      continue
    }

    model[field.key] = field.type === 'number' ? null : ''
  }
}

export function assignFilterValues(model, values = {}) {
  for (const [key, value] of Object.entries(values)) {
    model[key] = value
  }
}

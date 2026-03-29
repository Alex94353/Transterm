/**
 * Utility Functions
 */

/**
 * Format date to readable string
 * @param {Date|string} date - Date to format
 * @param {string} format - Format string (en-US by default)
 * @returns {string} Formatted date
 */
export function formatDate(date, format = 'en-US') {
  return new Date(date).toLocaleDateString(format)
}

/**
 * Format date and time
 * @param {Date|string} date - Date to format
 * @returns {string} Formatted date and time
 */
export function formatDateTime(date) {
  return new Date(date).toLocaleString()
}

/**
 * Truncate text to specified length with ellipsis
 * @param {string} text - Text to truncate
 * @param {number} length - Maximum length
 * @returns {string} Truncated text
 */
export function truncateText(text, length = 100) {
  if (!text || text.length <= length) return text
  return text.substring(0, length) + '...'
}

/**
 * Check if URL is valid
 * @param {string} url - URL to check
 * @returns {boolean} Whether URL is valid
 */
export function isValidUrl(url) {
  try {
    new URL(url)
    return true
  } catch (err) {
    return false
  }
}

/**
 * Generate unique ID
 * @returns {string} Unique ID
 */
export function generateId() {
  return `id_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
}

/**
 * Debounce function
 * @param {function} func - Function to debounce
 * @param {number} delay - Delay in milliseconds
 * @returns {function} Debounced function
 */
export function debounce(func, delay = 300) {
  let timeoutId
  return function (...args) {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => {
      func.apply(this, args)
    }, delay)
  }
}

/**
 * Check if user has permission
 * @param {Object} user - User object
 * @param {string} permission - Permission to check
 * @returns {boolean} Whether user has permission
 */
export function hasPermission(user, permission) {
  if (!user || !user.permissions) return false
  return user.permissions.some(p => p.name === permission)
}

/**
 * Check if user has role
 * @param {Object} user - User object
 * @param {string} role - Role to check
 * @returns {boolean} Whether user has role
 */
export function hasRole(user, role) {
  if (!user || !user.roles) return false
  return user.roles.some(r => r.name === role)
}

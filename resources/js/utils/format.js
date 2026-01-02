/**
 * Format a number for display in the UI
 * Uses en-US locale with 2-8 decimal places
 * @param {string|number} value - The number to format
 * @returns {string} Formatted number string
 */
export function formatNumber(value) {
    if (!value) return '0.00'
    const num = parseFloat(value)
    if (isNaN(num)) return '0.00'
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8,
    })
}


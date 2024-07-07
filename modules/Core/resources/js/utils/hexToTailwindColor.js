
import hexRgb from 'hex-rgb'

/**
 * Convert the given hex color to Tailwind compatible color.
 * @param {string} hex
 * @returns {string}
 */
function hexToTailwindColor(hex) {
  const [r, g, b] = hexRgb(hex, { format: 'array' })

  return r + ', ' + g + ', ' + b
}

export default hexToTailwindColor

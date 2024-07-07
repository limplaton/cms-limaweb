
function getTextWidth(text, font) {
  let canvas =
    getTextWidth.canvas ||
    (getTextWidth.canvas = document.createElement('canvas'))

  let context = canvas.getContext('2d')
  context.font = font

  return context.measureText(text).width
}

export default getTextWidth

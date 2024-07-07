
function strTruncate(text, length, suffix = '...') {
  return text.substring(0, length) + suffix
}

export default strTruncate

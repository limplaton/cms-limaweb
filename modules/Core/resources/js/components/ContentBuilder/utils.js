
export const addExternalScript = (src, callback) => {
  if (isScriptAlreadyIncluded(src)) {
    if (callback) callback()

    return
  }

  var script = document.createElement('script')

  script.onload = () => {
    if (callback) callback()
  }
  script.src = src
  document.head.appendChild(script)
}

export const addGoogleFontsStyle = fonts => {
  fonts.forEach(font => {
    addExternalStyle(
      `https://fonts.googleapis.com/css2?family=${decodeURI(font.name)}${font.stylesQueryString
      }&display=swap`
    )
  })
}

export const addExternalStyle = (url, prepend = false) => {
  if (!isStyleAlreadyIncluded(url)) {
    var link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = url

    if (!prepend) {
      document.head.appendChild(link)
    } else {
      document.head.prepend(link)
    }
  }
}

export const isScriptAlreadyIncluded = src => {
  // a utility to programmatically load js file (for loading language file).
  // (You can also use <script src=".."> include in html)
  const scripts = document.getElementsByTagName('script')
  for (let i = 0; i < scripts.length; i++)
    if (scripts[i].getAttribute('src') === src) return true

  return false
}

export const isStyleAlreadyIncluded = url => {
  const styles = document.getElementsByTagName('link')
  for (let i = 0; i < styles.length; i++)
    if (styles[i].getAttribute('href') === url) return true

  return false
}

export const removeExternalStyle = url => {
  const styles = document.getElementsByTagName('link')

  for (let i = 0; i < styles.length; i++)
    if (styles[i].getAttribute('href') === url) {
      styles[i].parentNode.removeChild(styles[i])
    }
}

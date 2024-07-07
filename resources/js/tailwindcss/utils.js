
// https://tailwindcss.com/docs/customizing-colors#naming-your-colors
// https://github.com/adamwathan/tailwind-css-variable-text-opacity-demo

const colorVariantWithOpacity = ({
  opacityVariable,
  opacityValue,
  color,
  key,
}) => {
  if (opacityValue !== undefined) {
    return `rgba(var(--color-${color}-${key}), ${opacityValue})`
  }

  if (opacityVariable !== undefined) {
    return `rgba(var(--color-${color}-${key}), var(${opacityVariable}, 1))`
  }

  return `rgb(var(--color-${color}-${key}))`
}

const colorVariantCallback = (attributes, color, key) =>
  colorVariantWithOpacity({
    opacityVariable: attributes.opacityVariable,
    opacityValue: attributes.opacityValue,
    color: color,
    key: key,
  })

const generateColorVariant = color => ({
  50: attributes => colorVariantCallback(attributes, color, 50),
  100: attributes => colorVariantCallback(attributes, color, 100),
  200: attributes => colorVariantCallback(attributes, color, 200),
  300: attributes => colorVariantCallback(attributes, color, 300),
  400: attributes => colorVariantCallback(attributes, color, 400),
  500: attributes => colorVariantCallback(attributes, color, 500),
  600: attributes => colorVariantCallback(attributes, color, 600),
  700: attributes => colorVariantCallback(attributes, color, 700),
  800: attributes => colorVariantCallback(attributes, color, 800),
  900: attributes => colorVariantCallback(attributes, color, 900),
})

export { colorVariantWithOpacity, generateColorVariant }

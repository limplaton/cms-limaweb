
const VALID_PLACEMENTS = [
  'top',
  'top-start',
  'top-end',
  'right',
  'right-start',
  'right-end',
  'bottom',
  'bottom-start',
  'bottom-end',
  'left',
  'left-start',
  'left-end',
]

const VALID_VARIANTS = ['dark', 'light']

function findModifier(availableModifiers, modifiers) {
  return availableModifiers.reduce((acc, cur) => {
    if (modifiers[cur]) acc = cur

    return acc
  }, '')
}

const updateAttributes = (el, binding) => {
  const { modifiers, value } = binding

  const placement = findModifier(VALID_PLACEMENTS, modifiers) || 'top'
  const variant = findModifier(VALID_VARIANTS, modifiers) || 'dark'

  if (!value) {
    clearElementTooltip(el)

    return
  }

  el.setAttribute('v-tooltip', value)

  if (!el.getAttribute('v-tooltip-placement')) {
    el.setAttribute('v-tooltip-placement', placement)
  }

  if (!el.getAttribute('v-tooltip-variant')) {
    el.setAttribute('v-tooltip-variant', variant)
  }
}

function clearElementTooltip(el) {
  el.removeAttribute('v-tooltip')
  el.removeAttribute('v-tooltip-placement')
  el.removeAttribute('v-tooltip-variant')
}

export default {
  beforeMount: (el, binding) => updateAttributes(el, binding),
  updated: (el, binding) => updateAttributes(el, binding),
  beforeUnmount: el => clearElementTooltip(el),
}

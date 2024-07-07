
import plugin from 'tailwindcss/plugin'

export default plugin(function ({ addComponents, theme }) {
  const components = {}

  const variants = ['primary', 'warning', 'danger', 'success', 'info']

  variants.forEach(variant => {
    components['.chart-' + variant] = {
      '.ct-point,.ct-line,.ct-bar,.ct-slice-donut': {
        stroke: theme('colors.' + variant + '.500') + ' !important',
      },
      '.ct-slice-pie,.ct-slice-donut-solid,.ct-area': {
        fill: theme('colors.' + variant + '.500') + ' !important',
      },
    }
  })

  components['.ct-label'] = {
    '@apply text-neutral-500 dark:text-neutral-200': {},
  }

  components['.chartist-tooltip'] = {
    '@apply !max-w-lg !rounded-md !bg-neutral-800 !text-white !text-center !text-sm':
      {},
    '&:before': {
      display: 'none',
    },
  }

  addComponents(components)
})

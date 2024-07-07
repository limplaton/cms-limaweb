/** @type {import('tailwindcss').Config} */

import colors from 'tailwindcss/colors'
import defaultTheme from 'tailwindcss/defaultTheme'

import { generateColorVariant } from './resources/js/tailwindcss/utils'

export default {
  content: [],

  safelist: [],

  darkMode: 'class', // or 'media' or 'class'

  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
      },
    },

    colors: {
      transparent: 'transparent',
      current: 'currentColor',

      black: colors.black,
      white: colors.white,

      neutral: generateColorVariant('neutral'),
      danger: generateColorVariant('danger'),
      warning: generateColorVariant('warning'),
      success: generateColorVariant('success'),
      info: generateColorVariant('info'),
      primary: generateColorVariant('primary'),
    },
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  },
}

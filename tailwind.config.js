/** @type {import('tailwindcss').Config} */

import aspectRatio from '@tailwindcss/aspect-ratio'
import forms from '@tailwindcss/forms'
import typoegraphy from '@tailwindcss/typography'
import scrollbar from 'tailwind-scrollbar'
import colors from 'tailwindcss/colors'
import defaultTheme from 'tailwindcss/defaultTheme'

import tailwindAll from './resources/js/tailwindcss/plugins/all'
import tailwindChartist from './resources/js/tailwindcss/plugins/chartist'
import tailwindMail from './resources/js/tailwindcss/plugins/mail'
import tailwindTinyMCE from './resources/js/tailwindcss/plugins/tinymce'
import { generateColorVariant } from './resources/js/tailwindcss/utils'

export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './modules/**/resources/js/**/*.vue',
    './modules/**/resources/js/**/*.js',
    './modules/**/resources/**/*.blade.php',
    './public/static/contentbuilder/contentbuilder/plugins/*.js',
  ],

  safelist: [
    'tox',
    'tox-tinymce',
    {
      pattern: /(ct|chart|chartist)-.*/,
    },
  ],

  darkMode: 'class',

  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter var', ...defaultTheme.fontFamily.sans],
        signature: ['Dancing Script', 'cursive'],
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
  plugins: [
    forms,
    aspectRatio,
    typoegraphy,
    scrollbar({ nocompatible: true }),
    tailwindAll,
    tailwindTinyMCE,
    tailwindChartist,
    tailwindMail,
  ],
}


import plugin from 'tailwindcss/plugin'

const round = num =>
  num
    .toFixed(7)
    .replace(/(\.[0-9]+?)0+$/, '$1')
    .replace(/\.0$/, '')

const rem = px => `${round(px / 16)}rem`
const em = (px, base) => `${round(px / base)}em`

export default plugin(function ({ addComponents, theme }) {
  addComponents({
    '.mail-text': {
      color: theme('colors.neutral.700'),
      fontSize: rem(14),
      lineHeight: round(24 / 14),
      p: {
        marginTop: em(16, 14),
        marginBottom: em(16, 14),
      },
      '[class~="lead"]': {
        fontSize: em(18, 14),
        lineHeight: round(28 / 18),
        marginTop: em(16, 18),
        marginBottom: em(16, 18),
        // color: theme('colors.neutral.700'),
        color: 'inherit',
      },
      strong: {
        // color: theme('colors.neutral.800'),
        color: 'inherit',
        fontWeight: '600',
      },
      h1: {
        fontSize: em(30, 14),
        marginTop: '0',
        marginBottom: em(24, 30),
        lineHeight: round(36 / 30),
        // color: theme('colors.neutral.800'),
        color: 'inherit',
        fontWeight: '800',
      },
      'h1 strong': {
        fontWeight: '900',
      },
      h2: {
        fontSize: em(20, 14),
        marginTop: em(32, 20),
        marginBottom: em(16, 20),
        lineHeight: round(28 / 20),
        // color: theme('colors.neutral.800'),
        color: 'inherit',
        fontWeight: '700',
      },
      'h2 strong': {
        fontWeight: '800',
      },
      h3: {
        fontSize: em(18, 14),
        marginTop: em(28, 18),
        marginBottom: em(8, 18),
        lineHeight: round(28 / 18),
        // color: theme('colors.neutral.800'),
        color: 'inherit',
        fontWeight: '600',
      },
      'h3 strong': {
        fontWeight: '700',
      },
      h4: {
        marginTop: em(20, 14),
        marginBottom: em(8, 14),
        lineHeight: round(20 / 14),
        // color: theme('colors.neutral.800'),
        color: 'inherit',
        fontWeight: '600',
      },
      'h4 strong': {
        fontWeight: '700',
      },
      figure: {
        marginTop: em(24, 14),
        marginBottom: em(24, 14),
      },
      'figure > *': {
        marginTop: '0',
        marginBottom: '0',
      },
      figcaption: {
        fontSize: em(12, 14),
        lineHeight: round(16 / 12),
        marginTop: em(8, 12),
        // color: theme('colors.neutral.600'),
        color: 'inherit',
      },
      'h2 code': {
        fontSize: em(18, 20),
      },
      'h3 code': {
        fontSize: em(16, 18),
      },
      pre: {
        fontSize: em(12, 14),
        lineHeight: round(20 / 12),
        marginTop: em(20, 12),
        marginBottom: em(20, 12),
        borderRadius: rem(4),
        paddingTop: em(8, 12),
        paddingRight: em(12, 12),
        paddingBottom: em(8, 12),
        paddingLeft: em(12, 12),
        color: theme('colors.neutral.200'),
        backgroundColor: theme('colors.neutral.900'),
        overflowX: 'auto',
        fontWeight: '400',
      },
      'pre code': {
        backgroundColor: 'transparent',
        borderWidth: '0',
        borderRadius: '0',
        padding: '0',
        fontWeight: 'inherit',
        color: 'inherit',
        fontSize: 'inherit',
        fontFamily: 'inherit',
        lineHeight: 'inherit',
      },
      'pre code::before': {
        content: 'none',
      },
      'pre code::after': {
        content: 'none',
      },
      ol: {
        marginTop: em(16, 14),
        marginBottom: em(16, 14),
        paddingLeft: em(22, 14),
        listStyleType: 'decimal',
      },
      ul: {
        marginTop: em(16, 14),
        marginBottom: em(16, 14),
        paddingLeft: em(22, 14),
        listStyleType: 'disc',
      },
      li: {
        marginTop: em(4, 14),
        marginBottom: em(4, 14),
      },
      'ol > li': {
        paddingLeft: em(6, 14),
      },
      'ul > li': {
        paddingLeft: em(6, 14),
      },
      '> ul > li p': {
        marginTop: em(8, 14),
        marginBottom: em(8, 14),
      },
      '> ul > li > *:first-child': {
        marginTop: em(16, 14),
      },
      '> ul > li > *:last-child': {
        marginBottom: em(16, 14),
      },
      '> ol > li > *:first-child': {
        marginTop: em(16, 14),
      },
      '> ol > li > *:last-child': {
        marginBottom: em(16, 14),
      },
      'ul ul, ul ol, ol ul, ol ol': {
        marginTop: em(8, 14),
        marginBottom: em(8, 14),
      },
      'ol[type="A"]': {
        listStyleType: 'upper-alpha',
      },
      'ol[type="a"]': {
        listStyleType: 'lower-alpha',
      },
      'ol[type="A" s]': {
        listStyleType: 'upper-alpha',
      },
      'ol[type="a" s]': {
        listStyleType: 'lower-alpha',
      },
      'ol[type="I"]': {
        listStyleType: 'upper-roman',
      },
      'ol[type="i"]': {
        listStyleType: 'lower-roman',
      },
      'ol[type="I" s]': {
        listStyleType: 'upper-roman',
      },
      'ol[type="i" s]': {
        listStyleType: 'lower-roman',
      },
      'ol[type="1"]': {
        listStyleType: 'decimal',
      },
      'ol > li::marker': {
        fontWeight: '400',
        color: 'var(--tw-prose-counters)',
      },
      'ul > li::marker': {
        color: 'var(--tw-prose-bullets)',
      },
      hr: {
        marginTop: em(40, 14),
        marginBottom: em(40, 14),
        borderColor: theme('colors.neutral.200'),
        borderTopWidth: 1,
      },
      table: {
        display: 'revert',
        borderCollapse: 'collapse',
        borderColor: theme('colors.neutral.400'),
      },
      'table[border="1"]': {
        borderWidth: '1px',
      },
      'table[border="0"]': {
        borderWidth: '0px',
      },
      'table[border="1"] td, table[border="1"] th': {
        borderWidth: '1px',
        borderColor: theme('colors.neutral.400'),
      },
      blockquote: {
        fontWeight: '400',
        fontStyle: 'italic',
        // color: theme('colors.neutral.700'),
        color: 'inherit',
        borderLeftWidth: '0.25rem',
        borderLeftColor: theme('colors.neutral.300'),
        quotes: '"\\201C""\\201D""\\2018""\\2019"',
        marginTop: em(24, 18),
        marginBottom: em(24, 18),
        paddingLeft: em(20, 18),
      },
      'blockquote p:first-of-type::before': {
        content: 'open-quote',
      },
      'blockquote p:last-of-type::after': {
        content: 'close-quote',
      },
      a: {
        color: theme('colors.primary.600'),
        textDecoration: 'underline',
        fontWeight: '400',
      },
      code: {
        fontSize: em(12, 14),
        color: theme('colors.neutral.900'),
        fontWeight: '500',
      },
      'code::before': {
        content: '"`"',
      },
      'code::after': {
        content: '"`"',
      },
      'a code': {
        color: theme('colors.primary.600'),
      },
      'img, svg, video, canvas, audio, iframe, embed, object': {
        display: 'revert',
        verticalAlign: 'revert',
      },
    },
  })
})

import globalVue from '@crm/vite-plugin-global-vue'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import unfonts from 'unplugin-fonts/vite'
import { defineConfig } from 'vite'

const moduleAliasRegex = /@\/([a-zA-Z]+)\/(.*)/

export default defineConfig({
  resolve: {
    alias: [
      {
        find: moduleAliasRegex,
        replacement: '/modules/$1/resources/js/$2',
      },
    ],
  },
  plugins: [
    // eslint-disable-next-line prettier/prettier
    laravel([
      'resources/js/app.js',
      'resources/css/contentbuilder/theme.css'
    ]),
    unfonts({
      custom: {
        families: [
          {
            name: 'Dancing Script',
            local: 'Dancing Script',
            src: './public/fonts/DancingScript-Regular.ttf',
          },
        ],
      },
    }),
    globalVue(),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  server: {
    hmr: {
      host: 'localhost',
    },
  },
})

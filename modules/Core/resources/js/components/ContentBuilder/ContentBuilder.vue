<template>
  <IOverlay :show="!initialized && !disabled">
    <div class="contentbuilder"></div>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <div v-if="disabled" class="contentbuilder" v-html="modelValue"></div>
  </IOverlay>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTimeoutFn } from '@vueuse/core'

import { useApp } from '@/Core/composables/useApp'
import { randomString } from '@/Core/utils'

import {
  addExternalScript,
  addExternalStyle,
  removeExternalStyle,
} from './utils'

const props = defineProps({
  modelValue: {},
  disabled: Boolean,
  placeholders: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()
const { appUrl, scriptConfig, isDarkMode, locale } = useApp()

const internalContent = ref('')
const initialized = ref(false)
const filesDraftId = randomString()

const colors = scriptConfig('favourite_colors').concat([
  '#388d3c',
  '#616161',
  '#e64918',
  '#d32f2f',
  '#5d4038',
])

// The editor uses the _txt variable to store the translations, will add them manually
// becauase of the ability the user to perform translations via the translator module.
window._txt = window.lang[locale.value].core.contentbuilder.builder

const sectionDeleteButtonsClasses = ['elm-remove', 'cell-remove', 'row-remove']

let builder = null
const editorCssUrl = builderUrl('contentbuilder/contentbuilder.css')
const editorJsUrl = builderUrl('contentbuilder/contentbuilder.min.js')

watch(
  () => props.disabled,
  (newVal, oldVal) => {
    if (newVal) {
      destroyBuilder()
    } else if (!newVal && oldVal) {
      initializeBuilder()
    }
  },
  { immediate: true }
)

watch(internalContent, newVal => {
  if (newVal != props.modelValue) {
    emit('update:modelValue', newVal)
  }
})

watch(
  () => props.modelValue,
  newVal => {
    if (newVal != internalContent.value) {
      if (builder) {
        builder.loadHtml(newVal || '')
      }
    }
  },
  { immediate: true }
)

/**
 * Destroy the builder instance
 */
function destroyBuilder() {
  let addSnippetsMoreButton = document.querySelector('.add-more')

  if (addSnippetsMoreButton) {
    addSnippetsMoreButton.removeEventListener('click', viewSnippets)
  }

  document.removeEventListener('click', ensureDeleteModalButtonIsFocused)

  if (builder) {
    builder.destroy()
  }

  builder = null
}

function viewSnippets() {
  builder.viewSnippets()

  // Cheap way to style the contents in the snippets iframe
  let modal = document.querySelector('.is-modal.snippets')
  let snippetsIframe = modal.querySelector('iframe')

  if (snippetsIframe) {
    snippetsIframe.classList.add('rounded-lg')

    let contents =
      snippetsIframe.contentDocument || snippetsIframe.contentWindow.document

    let style = document.createElement('style')
    contents.head.appendChild(style)

    let styles = `
          .light *::-webkit-scrollbar-thumb { background-color: rgba(148, 163, 184, 0.7); }
          .is-pop-close {margin-top: 5px; margin-right: 5px;}
           svg { fill: #475569; !important;}
          .is-pop-close:hover svg {fill: #64748b; !important;}
          .is-category-list>div {background:#e2e8f0 !important;}
          .is-more-categories {margin-top: 1px;}
          .is-more-categories a {color:white;}
          .is-category-list a {background: #f1f5f9 !important; color: #334155 !important}
          .is-category-list a.active, .is-category-list a:hover {background: #cbd5e1 !important;}
          .is-more-categories a {color:#334155 !important;}
          .is-more-categories a.active, .is-more-categories a:hover, .is-more-categories a:focus {background:#cbd5e1 !important;}
          .is-design-list, .is-more-categories a {background:white !important;}
        `

    let darkStyles = `
          .light *::-webkit-scrollbar-thumb { background-color: rgba(148, 163, 184, 0.5); }
          .is-pop-close {margin-top: 5px; margin-right: 5px;}
           svg { fill: white; !important;}
          .is-pop-close:hover svg {fill: #cbd5e1; !important;}
          .is-category-list>div {background:#1e293a !important;}
          .is-more-categories {margin-top: 1px;}
          .is-more-categories a {color:white;}
          .is-category-list a {background: #64748b !important; color: white !important}
          .is-category-list a.active, .is-category-list a:hover {background: #475569 !important;}
          .is-more-categories a.active, .is-more-categories a:hover, .is-more-categories a:focus {background:#475569 !important;}
          .is-design-list, .is-more-categories a {background:#0f172a !important;}
        `

    style.appendChild(
      document.createTextNode(isDarkMode.value ? darkStyles : styles)
    )
  }
}

/**
 * Create a content builder location URL
 */
function builderUrl(glue) {
  return `${appUrl}/static/contentbuilder/${glue}`
}

/**
 * Convert url/base64 to File instance
 */
async function urltoFileInstance(url, filename, mimeType) {
  mimeType = mimeType || (url.match(/^data:([^;]+);/) || '')[1]
  const res = await fetch(url)
  const buf = await res.arrayBuffer()

  return new File([buf], filename, { type: mimeType })
}

/**
 * Handle file upload
 */
function handleFileUpload(file) {
  return new Promise((resolve, reject) => {
    const fd = new FormData()
    fd.append('file', file)

    Innoclapps.request()
      .post(`/media/pending/${filesDraftId}`, fd)
      .then(({ data }) => resolve(data.preview_path))
      .catch(error => {
        // Nginx 413 Request Entity Too Large
        let message =
          error.message && error.message.includes('413')
            ? t('core::app.file_too_large')
            : error.response.data.message

        Innoclapps.error(message)

        reject(message)
      })
  })
}

/**
 * Save the base64 embedded images as pending attachments
 */
function saveBase64Images() {
  return new Promise((resolve, reject) => {
    // Probably disabled or not yet initialized?
    if (!builder) {
      resolve()

      return
    }

    // Save all embedded base64 images first
    builder.saveImages(
      '',
      () => {
        // This callback is called after the images are uploaded
        // NOTE: The contentbuilder uses setTimeout for success and it's not accurate
        // Sometimes may fail, perhaps add additional timeout here?
        internalContent.value = builder.html()
        resolve()
      },
      (img, base64, filename) => {
        urltoFileInstance(img.getAttribute('src'), filename)
          .then(file => {
            handleFileUpload(file)
              .then(url => img.setAttribute('src', url))
              .catch(err => reject(err))
          })
          .catch(err => reject(err))
      }
    )
  })
}

/**
 * Initialize the builder
 */
function initializeBuilder() {
  if (props.disabled) {
    return
  }

  destroyBuilder()

  const selector = '.contentbuilder'

  // eslint-disable-next-line no-undef
  builder = new ContentBuilder({
    container: selector,
    colors: colors,
    useCssClasses: false,
    toolStyle: 'gray',
    outlineStyle: 'grayoutline',
    rowHtmlEditor: false,
    columnHtmlEditor: false,
    enableDragResize: true,
    // rowTool: 'left',
    customTags: props.placeholders.map(placeholder => {
      let tagWithInterpolation = `${placeholder.interpolation_start} ${placeholder.tag} ${placeholder.interpolation_end}`

      return [
        placeholder.description || tagWithInterpolation,
        tagWithInterpolation,
      ]
    }),
    buttons: [
      'bold',
      'italic',
      'underline',
      'formatting',
      'formatPara',
      'color',
      'align',
      'createLink',
      'tags',
      '|',
      'undo',
      'redo',
      // 'zoom',
      'more',
    ],

    buttonsMore: [
      'textsettings',
      'icon',
      'image',
      '|',
      'list',
      'font',
      // '|',
      // 'html',
      // 'preferences',
    ],

    elementButtons: [
      'left',
      'center',
      'right',
      'full',
      'undo',
      'redo',
      // 'zoom',
      'more',
    ],

    elementButtonsMore: [
      // '|',
      // 'html',
      // 'preferences'
    ],

    iconButtons: [
      'icon',
      'color',
      'textsettings',
      'createLink',
      '|',
      'undo',
      'redo',
      // 'zoom',
      'more',
    ],

    // see CSS at bottom - .is-elementrte-tool button[data-plugin="more"]
    // Hides empty "button not found" button
    iconButtonsMore: [
      // '|',
      // 'html',
      // 'preferences'
    ],

    onChange: () => {
      internalContent.value = builder.html()
    },

    snippetUrl: builderUrl('assets/minimalist-blocks/content.js'), // Snippet file
    snippetPath: builderUrl('assets/minimalist-blocks/'), // Location of snippets' assets
    modulePath: builderUrl('assets/modules/'),
    assetPath: builderUrl('assets/'),
    fontAssetPath: builderUrl('assets/fonts/'),
    // When set to true, none of the elementButtons, elementButtonsMore, iconButtons and iconButtonsMore works
    // toolbarAddSnippetButton: true,
    maxColumns: 3, // best to fit in PDF

    snippetCategories: [
      [120, t('core::contentbuilder.snippets.basic')],
      [118, t('core::contentbuilder.snippets.text')],
      [101, t('core::contentbuilder.snippets.headline')],
      [119, t('core::contentbuilder.snippets.buttons')],
      [102, t('core::contentbuilder.snippets.photos')],
      [103, t('core::contentbuilder.snippets.profile')],
      [116, t('core::contentbuilder.snippets.contact')],
      [104, t('core::contentbuilder.snippets.products')],
      [105, t('core::contentbuilder.snippets.features')],
      [106, t('core::contentbuilder.snippets.process')],
      [107, t('core::contentbuilder.snippets.pricing')],
      [108, t('core::contentbuilder.snippets.skills')],
      [109, t('core::contentbuilder.snippets.achievements')],
      [110, t('core::contentbuilder.snippets.quotes')],
      [111, t('core::contentbuilder.snippets.partners')],
      [112, t('core::contentbuilder.snippets.as_featured_on')],
      // [113, 'Page Not Found'],
      // [114, 'Coming Soon'],
      [115, t('core::contentbuilder.snippets.help_and_faq')],
    ],

    defaultSnippetCategory: 120, // the default category is 'Basic'

    // Load plugins (without using config.js file)
    plugins: [
      {
        name: 'pagebreak',
        showInMainToolbar: false,
        showInElementToolbar: false,
      },
      {
        name: 'products',
        showInMainToolbar: false,
        showInElementToolbar: false,
      },
      {
        name: 'signatures',
        showInMainToolbar: false,
        showInElementToolbar: false,
      },
    ],

    pluginPath: builderUrl('contentbuilder/'), // Location of the plugin scripts

    // Can be replaced with your own file/asset manager application
    imageSelect: builderUrl('assets.html'),
    fileSelect: builderUrl('assets.html'),
    videoSelect: builderUrl('assets.html'),

    onMediaUpload: e => {
      handleFileUpload(e.target.files[0]).then(url => {
        builder.returnUrl(url)
      })
    },

    onVideoUpload: e => {
      handleFileUpload(e.target.files[0]).then(url => {
        builder.returnUrl(url)
      })
    },
  })

  builder.loadSnippets(builderUrl('assets/minimalist-blocks/content.js'))

  builder.loadHtml(props.modelValue)
  initialized.value = true
}

function focusModalDeleteButton(e) {
  sectionDeleteButtonsClasses.forEach(className => {
    if (e.target.classList.contains(className)) {
      let modal = document.querySelector('.is-modal.is-confirm')

      if (modal) {
        modal.querySelector('button').focus()
      }

      return
    }
  })
}

function ensureDeleteModalButtonIsFocused() {
  document.addEventListener('click', focusModalDeleteButton)
}

function prepareAddMoreButton() {
  // We will get the add more button and remove all event listeners
  // Next we will attach new click event listener to use the component function
  // to invoke the view snippets modal as this modal has custom styles injected in the iframe
  let addSnippetsMoreButton = document.querySelector('.add-more')

  if (addSnippetsMoreButton) {
    let newAddSnippetsMoreButton = addSnippetsMoreButton.cloneNode(true)

    // Clone and replace with the new instances, clone removed all previos event listeners
    addSnippetsMoreButton.parentNode.replaceChild(
      newAddSnippetsMoreButton,
      addSnippetsMoreButton
    )

    // Add the new click event listener to use the component function to show the snippets modal
    newAddSnippetsMoreButton.addEventListener('click', viewSnippets)
  }
}

onMounted(() => {
  // Prepend to allow override editor css via app.css and theme.css easily
  addExternalStyle(editorCssUrl, true)
  addExternalScript(editorJsUrl, initializeBuilder)

  useTimeoutFn(() => {
    prepareAddMoreButton()
    ensureDeleteModalButtonIsFocused()
  }, 1000)
})

onBeforeUnmount(() => {
  // Remove the actual editor styles as it's messing with the application default CSS
  removeExternalStyle(editorCssUrl)
  destroyBuilder()
})

defineExpose({ saveBase64Images, viewSnippets })
</script>

<style lang="scss">
@import './resources/scss/contenteditable.scss?inline';
.is-modal.snippets.active .is-modal-overlay {
  background: rgba(30, 41, 59, 0.5) !important;
}

.is-elementrte-tool button[data-plugin='more'] {
  display: none !important;
}

.is-rte-pop.rte-customtag-options {
  > div {
    width: 300px !important;
    height: 400px;
    button {
      font-size: 12.5px !important;
      justify-content: start;
    }
  }
  &.active {
    min-height: 400px;
  }
}
</style>

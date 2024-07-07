<template>
  <MailEditorPlaceholders
    v-if="placeholders && componentReady"
    v-show="placeholdersVisible"
    :placeholders="placeholders"
    @inserted="$emit('placeholderInserted')"
  />

  <TinyMCE
    v-model="model"
    v-bind="$attrs"
    :disabled="isDisabled"
    :init="mergedConfig"
  />
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import TinyMCE from '@tinymce/tinymce-vue'
import { useTimeoutFn } from '@vueuse/core'
import cloneDeep from 'lodash/cloneDeep'
import each from 'lodash/each'

import localeMaps from '@/Core/components/Editor/localeMaps'
import { useApp } from '@/Core/composables/useApp'
import { randomString } from '@/Core/utils'

import { insertPlaceholder } from '../composables/useMessagePlaceholders'

import MailEditorPlaceholders from './MailEditorPlaceholders.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  disabled: Boolean,
  placeholder: { type: String, default: '' },
  withDrop: Boolean,
  placeholders: Object,
  placeholdersDisabled: Boolean,
  placeholdersParseInProgress: Boolean,
})

const emit = defineEmits(['init', 'placeholderInserted', 'templateSelected'])

const model = defineModel({ default: '' })

const placeholdersAutocompleteTrigger = '--'
const templatesAutocompleteTrigger = '[['

const { t } = useI18n()
const { scriptConfig, isDarkMode, locale } = useApp()

const imagesDraftId = randomString()
const placeholdersVisible = ref(false)
const componentReady = ref(false)

let availableTemplates = null
let selectionBookmark = null
let editor = null

const defaultConfig = ref({
  cache_suffix: '?v=' + scriptConfig('version'),
  branding: false,
  promotion: false,
  height: 200,
  min_height: 200,
  max_height: 800,
  menubar: false,
  contextmenu: false,
  statusbar: false,
  // images_upload_handler: handleImageUpload,
  language: Object.hasOwn(localeMaps, locale.value)
    ? localeMaps[locale.value]
    : locale.value,
  automatic_uploads: true,
  images_reuse_filename: true,
  paste_data_images: props.withDrop,
  relative_urls: false,
  remove_script_host: false,
  placeholder:
    props.placeholder ||
    t('mailclient::mail.new_message_placeholder', {
      trigger: placeholdersAutocompleteTrigger,
    }),
  browser_spellcheck: true,
  body_class: `${isDarkMode.value ? 'dark ' : ''}${props.placeholdersDisabled ? 'placeholders-disabled' : ''}`,
  content_style: `
      .mce-content-body.dark {background: #0f172a;}
      .mce-content-body.dark[data-mce-placeholder]:not(.mce-visualblocks)::before {
          color: #64748b;
      }

      ._placeholder:focus {
          outline: 0;
      }

      ._placeholder {
          box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.050);
          border: 0.0625rem solid #cad1d7;
          min-width: 120px;
          display: inline-block;
          height: 29px;
          border-radius: 4px;
          line-height: 25px;
          padding-right: 0.7rem;
          padding-left: 0.7rem;
      }

      .mce-content-body.dark ._placeholder {
          background-color: #1e293b;
          border-color: #334155;
          color: white;
      }

      ._placeholder[data-autofilled] {
          background-color: #f2f9ff;
      }

      .mce-content-body.dark ._placeholder[data-autofilled] {
          border-color: rgba(14, 165, 233, 0.2);
          background-color: rgba(14, 165, 233, 0.2);
      }

      .placeholders-disabled ._placeholder {
          pointer-events: none !important;
          cursor: not-allowed !important;
          background-color: #f4f5f7 !important;
          opacity: 0.8;
      }

      .mce-content-body.dark.placeholders-disabled ._placeholder {
          background-color: #0f172a !important;
      }`,
  block_formats: `${tinymce.util.I18n.translate(
    'Paragraph'
  )}=p; ${tinymce.util.I18n.translate(
    'Heading 1'
  )}=h1; ${tinymce.util.I18n.translate(
    'Heading 2'
  )}=h2; ${tinymce.util.I18n.translate(
    'Heading 3'
  )}=h3;  ${tinymce.util.I18n.translate('Heading 4')}=h4`,

  setup: instance => {
    if (props.placeholders) {
      configurePlaceholders(instance)
    }

    createTemplatesAutocompleter(instance)

    instance.on('click', e => {
      if (e.target.classList.contains('_placeholder')) {
        e.preventDefault()
        editPlaceholder(editor, e)
      }
    })

    instance.on('init', e => {
      editor = e.target
      componentReady.value = true
      emit('init')
    })
  },
  plugins: [
    'quickbars',
    'advlist',
    'lists',
    'autolink',
    'link',
    'image',
    'media',
    'table',
    'autoresize',
    'emoticons',
  ],
  toolbar: `
        placeholders |
        blocks |
        bold italic underline strikethrough |
        table |
        alignment bullist numlist |
        emoticons blockquote removeformat undo redo`,
  toolbar_location: 'bottom',
  quickbars_insert_toolbar: false,
  quickbars_selection_toolbar:
    'bold italic underline | forecolor backcolor quicklink | alignleft aligncenter alignright | blockquote blocks',
  quickbars_image_toolbar: 'image | alignleft aligncenter alignright | remove',
  toolbar_groups: {
    alignment: {
      icon: 'align-left',
      tooltip: tinymce.util.I18n.translate('Alignment'),
      items: 'alignleft aligncenter alignright | alignjustify',
    },
  },
  link_context_toolbar: true,
})

const mergedConfig = computed(() => ({
  ...defaultConfig.value,
  ...(isDarkMode.value ? { skin: 'oxide-dark', content_css: 'dark' } : {}),
}))

const isDisabled = computed(
  () => props.disabled || props.placeholdersParseInProgress
)

/**
 * https://www.tiny.cloud/docs/tinymce/latest/apis/tinymce.dom.bookmarkmanager/#getBookmark
 * When placeholders are parsed, the content undergoes modification. In such scenarios, it's essential to maintain
 * the original focus that existed prior to the parsing of placeholders and subsequent content alteration.
 * This addresses issues where, after writing some text and inserting a dynamic placeholder marked by "--",
 * the placeholders get parsed, leading to the cursor unexpectedly moving to the beginning of the content.
 */
watch(
  () => props.placeholdersParseInProgress,
  newVal => {
    if (newVal) {
      selectionBookmark = editor.selection.getBookmark(2, true)
    } else if (!newVal && selectionBookmark) {
      editor.selection.moveToBookmark(selectionBookmark)
      selectionBookmark = null

      /**
       * When a template is selected and it significantly alters the content, the bookmark ends up selecting all the text.
       * Consequently, restoring from this bookmark leads to the entire text being highlighted, which is not desired.
       * In such instances, the cursor should instead be positioned at the beginning of the text.
       * On the other hand, when inserting something like a field placeholder, the bookmarking and selection process
       * functions correctly, positioning the cursor right after the newly inserted placeholder.
       */
      if (
        tinymce.activeEditor.selection.getSel().focusNode.tagName === 'HTML'
      ) {
        tinymce.activeEditor.selection.collapse(true)
      }
    }
  },
  { flush: 'post' }
)

/**
 * When the newVal is null and there is content in the editor, TinyMCE won't trigger the update
 * because it expects the value to be a string in order to trigger reactivity to update the editor content.
 */
watch(model, newVal => {
  if (newVal === null) {
    model.value = ''
  }
})

function focus() {
  editor.focus()
}

function updatePlaceholderValue(elm, value) {
  if (elm.tagName.toLowerCase() === 'input') {
    elm.setAttribute('value', value)
  } else {
    elm.textContent = value
  }
}

function editPlaceholder(editor, e) {
  const tag = e.target.dataset.tag
  const inputType = e.target.tagName.toLowerCase() // textarea or input
  const windowTitle = e.target.placeholder
  const initialValue = e.target.value

  editor.windowManager.open({
    title: windowTitle,
    size: 'normal',
    body: {
      type: 'panel',
      items: [{ type: inputType, name: 'placeholder' }],
    },
    initialData: {
      placeholder: initialValue,
    },
    buttons: [
      {
        type: 'cancel',
        text: t('core::app.cancel'),
      },
      {
        type: 'submit',
        text: t('core::app.save'),
        primary: true,
      },
    ],
    onSubmit: function (api) {
      const data = api.getData()
      const el = e.currentTarget.querySelector('[data-tag="' + tag + '"]')

      updatePlaceholderValue(el, data.placeholder)
      delete el.dataset.autofilled

      editor.fire('change')

      api.close()
    },
  })
}

function configurePlaceholders(editor) {
  editor.ui.registry.addToggleButton('placeholders', {
    icon: 'accordion-toggle',
    active: placeholdersVisible.value,
    text: t('core::fields.fields'),
    onAction: api => {
      placeholdersVisible.value = !placeholdersVisible.value
      api.setActive(placeholdersVisible.value)
    },
  })

  createPlaceholdersAutocompleter(editor)
}

function filterPlaceholders(pattern) {
  const result = cloneDeep(props.placeholders)

  each(result, (group, groupName) => {
    result[groupName].placeholders = group.placeholders.filter(
      p =>
        group.label.toLowerCase() == pattern.toLowerCase() ||
        p.description.toLowerCase().indexOf(pattern.toLowerCase()) !== -1
    )
  })

  return result
}

function createPlaceholdersAutocompleter(editor) {
  editor.ui.registry.addAutocompleter('placeholders', {
    trigger: placeholdersAutocompleteTrigger,
    minChars: 0,
    columns: 1,
    highlightOn: ['main-text'],

    fetch: function (pattern) {
      return new Promise(resolve => {
        const results = []

        each(filterPlaceholders(pattern), group => {
          results.push(
            ...group.placeholders.map(placeholder =>
              createAutocompleterCardWithTwoSections(
                { ...placeholder, group_label: group.label },
                placeholder.description,
                group.label
              )
            )
          )
        })

        resolve(results)
      })
    },

    onAction: function (autocompleteApi, rng, value) {
      editor.selection.setRng(rng || 0)

      insertPlaceholder(value, value.group_label)

      // Wait till the editor content is updated
      useTimeoutFn(() => emit('placeholderInserted'), 500)

      autocompleteApi.hide()
    },
  })
}

async function retrieveAllTemplates() {
  const result = await Innoclapps.request('/mails/templates')
  availableTemplates = result.data
}

async function filterTemplates(pattern) {
  if (availableTemplates === null) {
    await retrieveAllTemplates()
  }

  return availableTemplates.filter(
    t =>
      t.name.toLowerCase().indexOf(pattern.toLowerCase()) !== -1 ||
      t.subject.toLowerCase().indexOf(pattern.toLowerCase()) !== -1
  )
}

function createTemplatesAutocompleter(editor) {
  // Clear the available templates list in case the user
  // creates new template and then go back to add a template via
  // the auto completer
  editor.on('blur', () => {
    availableTemplates = null
  })

  editor.ui.registry.addAutocompleter('templates', {
    trigger: templatesAutocompleteTrigger,
    minChars: 1,
    columns: 1,
    highlightOn: ['main-text'],

    fetch: function (pattern) {
      return new Promise(resolve => {
        filterTemplates(pattern).then(templates => {
          resolve(
            templates.map(template =>
              createAutocompleterCardWithTwoSections(
                template,
                template.name,
                template.subject
              )
            )
          )
        })
      })
    },

    onAction: function (autocompleteApi, rng, value) {
      // Remove the match pattern and text
      editor.selection.setRng(rng || 0)
      editor.insertContent('')

      emit('templateSelected', value)

      autocompleteApi.hide()
    },
  })
}

function createAutocompleterCardWithTwoSections(value, text1, text2) {
  return {
    type: 'cardmenuitem',
    value: value,
    label: text1,
    items: [
      {
        type: 'cardcontainer',
        direction: 'vertical',
        items: [
          {
            type: 'cardtext',
            text: text1,
            name: 'main-text',
          },
          {
            type: 'cardtext',
            text: text2,
          },
        ],
      },
    ],
  }
}

// eslint-disable-next-line no-unused-vars
function handleImageUpload(blobInfo, progress) {
  const file = blobInfo.blob()

  return new Promise((resolve, reject) => {
    if (!/^image\//.test(file.type)) {
      reject({
        message: t('validation.image', {
          attribute: file.name,
        }),
        remove: true,
      })

      return
    }

    const fd = new FormData()
    fd.append('file', file, blobInfo.filename())

    Innoclapps.request()
      .post(`/media/pending/${imagesDraftId}`, fd)
      .then(({ data }) => resolve(data.preview_url))
      .catch(error => {
        // Nginx 413 Request Entity Too Large
        let message =
          error.message && error.message.includes('413')
            ? t('core::app.file_too_large')
            : error.response.data.message

        reject({ message: message, remove: true })
      })
  })
}

defineExpose({ focus })
</script>

<template>
  <div v-show="visible" class="mx-auto max-w-6xl">
    <div class="mb-8 flex justify-end space-x-2 text-right">
      <IModal
        id="saveAsTemplateModal"
        size="sm"
        :ok-text="$t('core::app.save')"
        :ok-disabled="saveTemplateForm.busy"
        :title="$t('documents::document.template.save_as_template')"
        static-backdrop
        form
        @shown="templatesModalShownHandler"
        @submit="saveContentAsTemplate"
      >
        <IFormGroup
          label-for="name"
          :label="$t('documents::document.template.name')"
          required
        >
          <IFormInput
            id="name"
            ref="inputNameRef"
            v-model="saveTemplateForm.name"
          />

          <IFormError :error="saveTemplateForm.getError('name')" />
        </IFormGroup>

        <IFormGroup>
          <IFormCheckboxField>
            <IFormCheckbox v-model:checked="saveTemplateForm.is_shared" />

            <IFormCheckboxLabel
              :text="$t('documents::document.template.share_with_team_members')"
            />
          </IFormCheckboxField>

          <IFormError :error="saveTemplateForm.getError('is_shared')" />
        </IFormGroup>
      </IModal>

      <IButton
        icon="AdjustmentsVertical"
        class="shrink-0 self-start"
        basic
        @click="showSettings = !showSettings"
      />

      <IButton
        icon="Sparkles"
        variant="secondary"
        class="shrink-0 self-start"
        :disabled="document.status === 'accepted'"
        :text="$t('core::contentbuilder.builder.Snippets')"
        @click="showSnippets"
      />

      <IButton
        v-show="form.content"
        v-dialog="'saveAsTemplateModal'"
        variant="secondary"
        icon="Bookmark"
        class="shrink-0 self-start"
        :text="$t('documents::document.template.save_as_template')"
      />

      <div class="relative w-72">
        <ICustomSelect
          v-model="selectedTemplate"
          label="name"
          :clearable="false"
          :loading="templatesAreBeingLoaded"
          :disabled="document.status === 'accepted'"
          :placeholder="$t('documents::document.template.insert_template')"
          :options="templatesForOptions"
          @option-selected="templateSelectedHandler"
        />

        <a
          v-show="!templatesAreBeingLoaded"
          href="#"
          class="absolute right-9 top-2.5 text-neutral-400 hover:text-neutral-600 focus:outline-none"
          @click.prevent.stop="loadTemplates"
        >
          <Icon icon="Refresh" class="size-4" />
        </a>

        <ILink
          target="_blank"
          class="inline-flex items-center"
          :to="{ name: 'document-templates-index' }"
        >
          {{ $t('documents::document.template.manage') }}
          <Icon icon="ExternalLink" class="ml-2 size-4" />
        </ILink>
      </div>
    </div>

    <div
      v-show="showSettings"
      class="space-y-1 border-b border-neutral-200 pb-3 dark:border-neutral-500/30"
    >
      <ITextBlockDark
        class="mb-4 inline-flex w-full items-center border-b border-neutral-200 pb-2 font-medium dark:border-neutral-500/30"
      >
        <Icon icon="Document" class="mr-1.5 size-4" />
        {{ $t('documents::pdf.settings') }}
      </ITextBlockDark>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          for="pdf-padding"
          class="mt-2.5 w-32"
          :label="$t('documents::pdf.padding')"
        />

        <div class="w-72">
          <IFormInput
            id="pdf-padding"
            v-model="form.pdf.padding"
            :placeholder="$t('documents::pdf.no_padding')"
          />

          <IFormError :error="form.getError('pdf.padding')" />
        </div>
      </div>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          for="pdf-font"
          class="mt-2.5 w-32"
          :label="$t('documents::pdf.default_font')"
        />

        <div class="w-72">
          <ICustomSelect
            v-model="form.pdf.font"
            input-id="pdf-font"
            :placeholder="
              $t('documents::document.settings.inherits_setting_from_brand')
            "
            :options="fontNames"
          />

          <IFormError :error="form.getError('pdf.font')" />
        </div>

        <span
          v-i-tooltip="
            $t('documents::pdf.default_font_info', {
              fontName: 'DejaVu Sans',
            })
          "
          class="mt-2.5"
        >
          <Icon
            icon="QuestionMarkCircle"
            class="size-5 text-neutral-500 dark:text-neutral-200"
          />
        </span>
      </div>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          for="pdf-size"
          class="mt-2.5 w-32"
          :label="$t('documents::pdf.size')"
        />

        <div class="w-72">
          <ICustomSelect
            v-model="form.pdf.size"
            input-id="pdf-size"
            :options="['a4', 'letter']"
            :placeholder="
              $t('documents::document.settings.inherits_setting_from_brand')
            "
          />

          <IFormError :error="form.getError('pdf.size')" />
        </div>
      </div>

      <div class="grid auto-cols-max grid-flow-col gap-4">
        <IFormLabel
          for="pdf-orientation"
          class="mt-2.5 w-32"
          :label="$t('documents::pdf.orientation')"
        />

        <div class="w-72">
          <ICustomSelect
            v-model="form.pdf.orientation"
            input-id="pdf-orientation"
            :options="['portrait', 'landscape']"
            :placeholder="
              $t('documents::document.settings.inherits_setting_from_brand')
            "
          />

          <IFormError :error="form.getError('pdf.orientation')" />
        </div>
      </div>
    </div>

    <div class="mt-10">
      <IAlert v-if="displayPlaceholdersMessage" class="mb-4">
        <IAlertBody>
          {{ $t('documents::document.placeholders_replacement_info') }}
        </IAlertBody>
      </IAlert>

      <IAlert v-if="displayProductsMissingMessage" class="mb-4">
        <IAlertBody>
          <I18nT
            scope="global"
            tag="span"
            class="inline-flex items-center"
            :keypath="'documents::document.products_snippet_missing'"
          >
            <template #icon>
              <Icon icon="PlusSolid" class="size-5" />
            </template>
          </I18nT>
        </IAlertBody>
      </IAlert>

      <IAlert v-if="displaySignaturesMissingMessage" class="mb-4">
        <IAlertBody>
          <I18nT
            scope="global"
            tag="span"
            class="inline-flex items-center"
            :keypath="'documents::document.signatures_snippet_missing'"
          >
            <template #icon>
              <Icon icon="PlusSolid" class="size-5" />
            </template>
          </I18nT>
        </IAlertBody>
      </IAlert>

      <div
        class="prose prose-sm prose-neutral relative max-w-none dark:prose-invert"
      >
        <ContentBuilder
          v-if="becameVisible"
          ref="builderRef"
          v-model="form.content"
          :disabled="document.status === 'accepted'"
          :placeholders="placeholders"
        />
      </div>
    </div>
  </div>
</template>

<!-- eslint-disable vue/no-mutating-props -->
<script setup>
import { computed, ref, watch } from 'vue'
import { useTimeoutFn, whenever } from '@vueuse/core'
import find from 'lodash/find'
import omit from 'lodash/omit'
import sortBy from 'lodash/sortBy'

import ContentBuilder from '@/Core/components/ContentBuilder/ContentBuilder.vue'
import { addGoogleFontsStyle } from '@/Core/components/ContentBuilder/utils'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import propsDefinition from './formSectionProps'

const props = defineProps({
  ...propsDefinition,
  isReady: { default: true, type: Boolean },
})

const { scriptConfig } = useApp()

const fonts = scriptConfig('contentbuilder.fonts')
const fontNames = computed(() => fonts.map(font => font['font-family']))

const becameVisible = ref(false)
const builderRef = ref(null)
const inputNameRef = ref(null)
const templates = ref([])
const selectedTemplate = ref(null)
const templatesLoaded = ref(false)
const templatesAreBeingLoaded = ref(false)
const placeholders = scriptConfig('documents.placeholders')
const showSettings = ref(false)

const { form: saveTemplateForm } = useForm({
  name: '',
  content: '',
  is_shared: false,
})

watch(
  () => props.document.updated_at,
  () => {
    if (props.visible) {
      addUsedDocumentGoogleFonts()
    }
  }
)

whenever(
  () => props.visible,
  () => {
    !templatesLoaded.value && loadTemplates()
    becameVisible.value = true
    addUsedDocumentGoogleFonts()
  },
  { immediate: true }
)

const contentHasPlaceholders = computed(
  () => props.form.content && props.form.content.indexOf('{{ ') > -1
)

const contentContainsPlaceholdersFromResources = computed(
  () =>
    props.form.content && props.form.content.match(/(contact.|deal.|company.)/)
)

const displayProductsMissingMessage = computed(
  () =>
    props.form.billable.products.length > 0 &&
    (!props.form.content ||
      props.form.content.indexOf('products-section') === -1)
)

const displaySignaturesMissingMessage = computed(
  () =>
    props.form.signers.length > 0 &&
    (!props.form.content ||
      props.form.content.indexOf('signatures-section') === -1)
)

const displayPlaceholdersMessage = computed(
  () =>
    props.document.associations_count === 0 &&
    props.isReady &&
    props.document.id &&
    contentHasPlaceholders.value &&
    contentContainsPlaceholdersFromResources.value
)

// Removes content for performance reasons e.q. avoid watching long contents
const templatesForOptions = computed(() =>
  sortBy(templates.value, ['name', 'asc']).map(t => omit(t, ['content']))
)

function addUsedDocumentGoogleFonts() {
  addGoogleFontsStyle(props.document.google_fonts || [])
}

function showSnippets() {
  builderRef.value.viewSnippets()
}

function templatesModalShownHandler() {
  inputNameRef.value.focus()
  saveTemplateForm.content = props.form.content
}

/**
 * Save the current content as template
 */
function saveContentAsTemplate() {
  saveTemplateForm.post('/document-templates').then(template => {
    templates.value.push(template)
    Innoclapps.dialog().hide('saveAsTemplateModal')
  })
}

/**
 * Template selected handler
 */
function templateSelectedHandler(option) {
  addGoogleFontsStyle(option.google_fonts)

  if (props.form.content === null) {
    props.form.content = ''
  }

  let template = find(templates.value, ['id', option.id])
  props.form.content += template.content

  if (template.view_type) {
    props.form.view_type = template.view_type
  }

  useTimeoutFn(() => (selectedTemplate.value = null), 500)
}

/**
 * Fetch the document templates
 */
function loadTemplates() {
  templatesAreBeingLoaded.value = true

  Innoclapps.request('document-templates', {
    params: { per_page: 100 },
  })
    .then(({ data: pagination }) => {
      templates.value = pagination.data
      templatesLoaded.value = true
    })
    .finally(() => (templatesAreBeingLoaded.value = false))
}

defineExpose({
  builderRef,
})
</script>

<style>
body:not(.document-section-content) #divSnippetHandle {
  display: none;
}
</style>

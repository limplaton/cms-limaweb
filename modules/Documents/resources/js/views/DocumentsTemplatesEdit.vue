<template>
  <Teleport to="body">
    <div
      class="absolute inset-0 z-40 h-full max-h-full w-full bg-white dark:bg-neutral-900"
    >
      <!-- navbar start -->
      <div
        class="sticky top-0 z-50 border-b border-neutral-200 bg-neutral-100 dark:border-neutral-500/30 dark:bg-neutral-800"
      >
        <div class="container mx-auto">
          <div class="mx-auto max-w-6xl">
            <div class="px-3 py-4 sm:-mx-1">
              <div
                class="flex items-center justify-between space-x-4 sm:space-x-0"
              >
                <ILink
                  class="sm:!text-base/6"
                  :text="$t('core::app.exit')"
                  @click="$router.back"
                />

                <IExtendedDropdown
                  placement="bottom-end"
                  :disabled="form.busy"
                  :loading="form.busy"
                  :text="$t('core::app.save')"
                  @click="save"
                >
                  <IDropdownMenu>
                    <IDropdownItem
                      :text="$t('core::app.save_and_exit')"
                      @click="saveAndExit"
                    />
                  </IDropdownMenu>
                </IExtendedDropdown>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- navbar end -->
      <div class="h-full min-h-full overflow-y-auto">
        <IOverlay class="h-full" :show="templateBeingFetched">
          <div class="px-4 py-6 sm:px-0">
            <div class="container mx-auto max-w-6xl">
              <IFormGroup
                label-for="templateName"
                :label="$t('documents::document.template.name')"
                required
              >
                <IFormInput id="templateName" v-model="form.name" />

                <IFormError :error="form.getError('name')" />
              </IFormGroup>

              <IFormGroup>
                <IFormCheckboxField>
                  <IFormCheckbox v-model:checked="form.is_shared" />

                  <IFormCheckboxLabel
                    :text="
                      $t('documents::document.template.share_with_team_members')
                    "
                  />
                </IFormCheckboxField>

                <IFormError :error="form.getError('is_shared')" />
              </IFormGroup>

              <IFormGroup class="mt-6">
                <div class="flex">
                  <IFormLabel
                    as="p"
                    :label="$t('documents::document.view_type.html_view_type')"
                  />

                  <span
                    v-t="'core::app.optional'"
                    class="ml-1 self-end text-xs text-neutral-600 dark:text-neutral-200"
                  />
                </div>

                <IText
                  class="-mt-px mb-3"
                  :text="$t('documents::document.view_type.template_info')"
                />

                <FormViewTypes v-model="form.view_type" />

                <IFormError :error="form.getError('view_type')" />
              </IFormGroup>

              <IFormGroup class="mt-6">
                <div
                  class="prose prose-sm prose-neutral relative max-w-none dark:prose-invert"
                  style="padding-bottom: 200px"
                >
                  <ContentBuilder
                    ref="builderRef"
                    v-model="form.content"
                    :placeholders="placeholders"
                  />

                  <IFormError :error="form.getError('content')" />
                </div>
              </IFormGroup>
            </div>
          </div>
        </IOverlay>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeMount, onBeforeUnmount, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

import ContentBuilder from '@/Core/components/ContentBuilder/ContentBuilder.vue'
import { addGoogleFontsStyle } from '@/Core/components/ContentBuilder/utils'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'

import FormViewTypes from '../components/DocumentFormViewTypes.vue'

const emit = defineEmits(['updated'])

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { scriptConfig } = useApp()

const builderRef = ref(null)

const placeholders = scriptConfig('documents.placeholders')
const templateBeingFetched = ref(true)
const template = ref(null)

const { form } = useForm({
  name: null,
  is_shared: false,
  content: '',
  view_type: null,
})

/**
 * Fetch the given template
 */
function fetch(id) {
  Innoclapps.request(`/document-templates/${id}`)
    .then(({ data }) => {
      template.value = data

      prepareForm(data)

      addGoogleFontsStyle(data.google_fonts)
    })
    .finally(() => (templateBeingFetched.value = false))
}

/**
 * Prepare the form for the given template object
 */
function prepareForm(template) {
  form.set({
    name: template.name,
    is_shared: template.is_shared,
    content: template.content,
    view_type: template.view_type,
  })
}

/**
 * Save the template and exit
 */
function saveAndExit() {
  save().then(() => router.back())
}

/**
 * Save the template
 */
async function save() {
  form.busy = true
  await builderRef.value.saveBase64Images()

  // Wait till update:modelValue event is properly propagated
  await nextTick()

  let updatedTemplate = await form
    .put(`/document-templates/${template.value.id}`)
    .catch(e => {
      if (e.isValidationError()) {
        Innoclapps.error(t('core::app.form_validation_failed'), 3000)
      }

      return Promise.reject(e)
    })

  addGoogleFontsStyle(updatedTemplate.google_fonts)

  prepareForm(updatedTemplate)

  emit('updated', updatedTemplate)

  return updatedTemplate
}

onBeforeMount(() => {
  fetch(route.params.id)
  document.body.classList.add('overflow-y-hidden')
})

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-y-hidden')
})
</script>

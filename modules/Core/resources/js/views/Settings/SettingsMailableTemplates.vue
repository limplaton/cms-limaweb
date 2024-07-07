<template>
  <ICardHeader>
    <div class="flex items-center space-x-4">
      <ICardHeading :text="$t('core::mail_template.mail_templates')" />

      <div class="flex items-center space-x-2">
        <ITextDark class="font-medium" :text="$t('core::app.locale')" />

        <IDropdown>
          <IDropdownButton :text="selectedLocale" basic />

          <IDropdownMenu>
            <IDropdownItem
              v-for="locale in locales"
              :key="locale"
              :text="locale"
              :active="selectedLocale === locale"
              condensed
              @click="setActiveLocale(locale)"
            />
          </IDropdownMenu>
        </IDropdown>
      </div>
    </div>

    <ICardActions class="-mt-4 sm:-mt-0">
      <IDropdown placement="bottom-end">
        <IDropdownButton class="-ml-3.5 sm:ml-0" basic>
          <span class="max-w-[13rem] truncate" v-text="selectedTemplate.name" />
        </IDropdownButton>

        <IDropdownMenu>
          <div class="px-3 py-2 font-medium">
            <ITextDark :text="$t('core::mail_template.choose_to_edit')" />
          </div>

          <IDropdownSeparator />

          <IDropdownItem
            v-for="template in templates"
            :key="template.id"
            :text="template.name"
            :active="selectedTemplate.id === template.id"
            condensed
            @click="setActiveTemplate(template, true)"
          />
        </IDropdownMenu>
      </IDropdown>
    </ICardActions>
  </ICardHeader>

  <ICard as="form" :overlay="isLoading" @submit.prevent="submit">
    <ICardBody>
      <IFormGroup
        label-for="subject"
        :label="$t('core::mail_template.subject')"
        required
      >
        <IFormInput id="subject" v-model="form.subject" name="subject" />

        <IFormError :error="form.getError('subject')" />
      </IFormGroup>

      <IFormGroup>
        <div class="mb-2 flex items-center">
          <!--
                <IDropdownSelect :items="['HTML', 'Text']"
                v-model="templateType" />
              -->
          <IFormLabel :label="$t('core::mail_template.message')" required />
        </div>

        <div v-show="isHtmlTemplateType">
          <Editor
            v-if="componentReady"
            v-model="form.html_template"
            :config="{
              urlconverter_callback: placeholderURLConverter,
            }"
            :auto-completer="editorAutoCompleter"
            absolute-urls
            minimal
          />
        </div>

        <div v-show="!isHtmlTemplateType">
          <IFormTextarea
            v-model="form.text_template"
            name="text_template"
            :rows="8"
          />
        </div>

        <IFormError :error="form.getError('html_template')" />

        <IFormError :error="form.getError('text_template')" />
      </IFormGroup>

      <IFormGroup
        v-if="
          selectedTemplate.placeholders &&
          selectedTemplate.placeholders.length > 0
        "
      >
        <ITextDark
          class="mb-1 font-medium"
          :text="$t('core::mail_template.placeholders.placeholders')"
        />

        <TextPlaceholders :placeholders="selectedTemplate.placeholders" />
      </IFormGroup>
    </ICardBody>

    <ICardFooter class="text-right">
      <IButton
        type="submit"
        variant="primary"
        :disabled="form.busy"
        :text="$t('core::app.save')"
      />
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'

import TextPlaceholders from '@/Core/components/TextPlaceholders.vue'
import { useApp } from '@/Core/composables/useApp'
import { useForm } from '@/Core/composables/useForm'
import { useLoader } from '@/Core/composables/useLoader'

const { t } = useI18n()
const { setLoading, isLoading } = useLoader()
const { locales } = useApp()

const componentReady = ref(false)
const { form } = useForm()
const templateType = ref('HTML') // or text
const templates = ref([]) // in locale templates
const selectedTemplate = ref({}) // active template
const selectedLocale = ref('en') // default selected locale

watch(selectedLocale, fetch)

const isHtmlTemplateType = computed(() => templateType.value === 'HTML')

const editorAutoCompleter = computed(() => ({
  id: 'placeholders',
  trigger: '{',
  list: selectedTemplate.value.placeholders
    .filter(p => p.tag !== 'action_button')
    .map(p => ({
      value: `${p.interpolation_start} ${p.tag} ${p.interpolation_end}`,
      text: `${p.interpolation_start} ${p.tag} ${p.interpolation_end} - ${p.description}`,
    })),
}))

function submit() {
  form.put(`/mailable-templates/${selectedTemplate.value.id}`).then(data => {
    let index = findIndex(templates.value, ['id', parseInt(data.id)])
    templates.value[index] = data

    // Re-set the data so the isDirty() method returns false
    setActiveTemplate(data)

    Innoclapps.success(t('core::mail_template.updated'))
  })
}

function fetch() {
  setLoading(true)

  Innoclapps.request(`/mailable-templates/${selectedLocale.value}/locale`)
    .then(({ data }) => {
      templates.value = data

      // If previous template selected, keep it selected
      // Otherwise find the template with the same name
      // We find by name because the template may have different id
      setActiveTemplate(
        Object.keys(selectedTemplate.value).length === 0
          ? data[0]
          : find(templates.value, ['name', selectedTemplate.value.name])
      )

      componentReady.value = true
    })
    .finally(() => setLoading(false))
}

async function setActiveLocale(newLocale) {
  if (newLocale !== selectedLocale.value && form.isDirty()) {
    await Innoclapps.confirm({
      message: t('core::mail_template.changes_not_saved_warning'),
      confirmText: t('core::app.discard_changes'),
    })
  }
  selectedLocale.value = newLocale
}

async function setActiveTemplate(mailableTemplate, dirtyCheck = false) {
  if (
    dirtyCheck &&
    mailableTemplate.id !== selectedTemplate.value.id &&
    form.isDirty()
  ) {
    await Innoclapps.confirm({
      message: t('core::mail_template.changes_not_saved_warning'),
      confirmText: t('core::app.discard_changes'),
    })
  }

  selectedTemplate.value = mailableTemplate

  form.set({
    subject: mailableTemplate.subject,
    html_template: mailableTemplate.html_template,
    text_template: mailableTemplate.text_template,
  })
}

/**
 * Merge field url converter callback
 *
 * @param  {String} url
 * @param  {Node} node
 * @param  {Boolean} on_save
 * @param  {String} name
 *
 * @return {String}
 */
// eslint-disable-next-line no-unused-vars
function placeholderURLConverter(url, node, on_save, name) {
  if (url.indexOf('%7B%7B%20') > -1 && url.indexOf('%20%7D%7D') > -1) {
    url = url.replace('%7B%7B%20', '{').replace('%20%7D%7D', '}')
  }

  return url
}

function prepareComponent() {
  fetch()
}

// Mail templates component must make the request each time is created
// this helps to seed any missing templates in database
prepareComponent()
</script>

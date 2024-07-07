<template>
  <ICard
    class="group"
    :class="[
      '[&>[data-slot=header]]:py-3',
      {
        'border border-primary-400 dark:border-primary-500': editing,
        'border transition duration-75 hover:border-primary-400 dark:hover:border-primary-500':
          !editing,
      },
    ]"
  >
    <ICardHeader>
      <ICardHeading class="text-sm/6" :text="sectionHeading" />

      <ICardActions>
        <IButton
          icon="PencilAlt"
          :class="[
            canEditSection
              ? 'opacity-100 md:opacity-0 md:group-hover:opacity-100'
              : 'opacity-0',
          ]"
          basic
          small
          @click="setEditingMode"
        />

        <IButton
          class="opacity-100 md:opacity-0 md:group-hover:opacity-100"
          icon="Trash"
          basic
          small
          @click="requestSectionRemove"
        />
      </ICardActions>
    </ICardHeader>

    <ICardBody>
      <ITextBlockDark v-show="!editing">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <p v-html="section.label" />
      </ITextBlockDark>

      <template v-if="editing">
        <IFormGroup
          label-for="resourceName"
          :label="$t('webforms::form.sections.field.resourceName')"
        >
          <ICustomSelect
            v-model="resourceName"
            label="label"
            field-id="resourceName"
            :clearable="false"
            :options="availableResources"
            :reduce="resource => resource.id"
          />
        </IFormGroup>

        <IFormGroup :label="$t('core::fields.label')">
          <Editor
            v-model="label"
            default-tag="div"
            :config="{
              toolbar: 'bold italic underline link removeformat',
              quickbars_insert_toolbar: false,
              quickbars_selection_toolbar: false,
            }"
            :with-image="false"
            minimal
          />
        </IFormGroup>

        <IFormGroup>
          <IFormCheckboxField>
            <IFormCheckbox v-model:checked="isRequired" />

            <IFormCheckboxLabel :text="$t('core::fields.is_required')" />
          </IFormCheckboxField>

          <IFormCheckboxField>
            <IFormCheckbox v-model:checked="multiple" />

            <IFormCheckboxLabel
              :text="$t('webforms::form.sections.file.multiple')"
            />
          </IFormCheckboxField>
        </IFormGroup>

        <div class="space-x-2 text-right">
          <IButton
            variant="secondary"
            :text="$t('core::app.cancel')"
            @click="editing = false"
          />

          <IButton
            variant="primary"
            :disabled="saveIsDisabled"
            :text="$t('core::app.save')"
            @click="requestSectionSave"
          />
        </div>
      </template>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import find from 'lodash/find'

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
  availableResources: { required: true },
})

const emit = defineEmits(['updateSectionRequested', 'removeSectionRequested'])

const { t } = useI18n()

const editing = ref(false)
const label = ref(null)
const isRequired = ref(false)
const resourceName = ref(null)
const multiple = ref(false)

const canEditSection = computed(() => !editing.value)

const resourceSingularLabel = computed(() => {
  return find(props.availableResources, {
    id: props.section.resourceName,
  }).label
})

const sectionHeading = computed(
  () =>
    resourceSingularLabel.value +
    ' | ' +
    (props.section.multiple
      ? t('webforms::form.sections.file.files')
      : t('webforms::form.sections.file.file')) +
    (!props.section.isRequired ? ' ' + t('core::fields.optional') : '')
)

const saveIsDisabled = computed(() => label.value === null || label.value == '')

function requestSectionSave() {
  emit('updateSectionRequested', {
    resourceName: resourceName.value,
    label: label.value,
    isRequired: isRequired.value,
    multiple: multiple.value,
  })

  editing.value = false
}

function requestSectionRemove() {
  emit('removeSectionRequested')
}

function setEditingMode() {
  resourceName.value = props.section.resourceName
  label.value = props.section.label
  isRequired.value = props.section.isRequired
  multiple.value = props.section.multiple

  editing.value = true
}
</script>

<template>
  <ICard
    class="group"
    :class="[
      '[&>[data-slot=header]]:py-3',
      {
        'border border-danger-500': !originalField,
        'border border-primary-400 dark:border-primary-500': editing,
        'border transition duration-75 hover:border-primary-400 dark:hover:border-primary-500':
          !editing && originalField,
      },
    ]"
  >
    <ICardHeader>
      <ICardHeading class="truncate text-sm/6">
        {{ sectionHeading }}

        <ITextSmall
          v-if="originalField && !section.isRequired"
          class="inline font-normal"
        >
          {{ $t('core::fields.optional') }}
        </ITextSmall>
      </ICardHeading>

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
            @option-selected="field = null"
          />
        </IFormGroup>

        <IFormGroup label-for="field" :label="$t('core::fields.field')">
          <ICustomSelect
            v-model="field"
            label="label"
            field-id="field"
            :clearable="false"
            :selectable="field => field.disabled"
            :options="availableFields"
            @option-selected="handleFieldChanged"
          />
        </IFormGroup>

        <IFormGroup v-show="field !== null" :label="$t('core::fields.label')">
          <Editor
            v-model="fieldLabel"
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

        <div class="text-right">
          <div class="flex items-center justify-between">
            <IFormCheckboxField v-show="field !== null">
              <IFormCheckbox
                v-model:checked="isRequired"
                :disabled="fieldMustBeRequired"
              />

              <IFormCheckboxLabel :text="$t('core::fields.is_required')" />
            </IFormCheckboxField>

            <div class="space-x-2">
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
          </div>
        </div>
      </template>
    </ICardBody>
  </ICard>
</template>

<script setup>
import { computed, ref, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import find from 'lodash/find'

import { useFieldSection } from '../../composables/useSectionField'

const props = defineProps({
  index: { type: Number },
  form: { type: Object, required: true },
  section: { required: true, type: Object },
  companiesFields: { required: true },
  contactsFields: { required: true },
  dealsFields: { required: true },
  availableResources: { required: true },
})

const emit = defineEmits(['updateSectionRequested', 'removeSectionRequested'])

const { t } = useI18n()

const editing = ref(false)
const resourceName = ref(props.section.resourceName)

const currentResourceFields = computed(
  () => props[resourceName.value + 'Fields']
)

/**
 * Original field before edit
 */
const originalField = find(currentResourceFields.value, {
  attribute: props.section.attribute,
})

const {
  field,
  availableFields,
  handleFieldChanged,
  fieldLabel,
  isRequired,
  fieldMustBeRequired,
  generateRequestAttribute,
} = useFieldSection(
  resourceName,
  currentResourceFields,
  toRef(props.form, 'sections')
)

/**
 * Indicates whether the user can edit the section
 * Returns false if the field is deleted as well, when no original field found
 */
const canEditSection = computed(() => !editing.value && originalField)

const resourceSingularLabel = computed(() => {
  return find(props.availableResources, {
    id: props.section.resourceName,
  }).label
})

const sectionHeading = computed(() => {
  if (!originalField) {
    return t('core::fields.no_longer_available')
  }

  return resourceSingularLabel.value + ' | ' + originalField.label
})

const saveIsDisabled = computed(
  () =>
    fieldLabel.value === null || fieldLabel.value == '' || field.value == null
)

function requestSectionSave() {
  let data = {
    isRequired: isRequired.value,
    label: fieldLabel.value,
    resourceName: resourceName.value,
    attribute: field.value.attribute,
  }

  // Field changed, re-generate request attribute data
  if (
    !originalField ||
    resourceName.value != props.section.resourceName ||
    field.value.attribute != originalField.attribute
  ) {
    data.requestAttribute = generateRequestAttribute()
  }

  emit('updateSectionRequested', data)

  editing.value = false
}

function setEditingMode() {
  field.value = originalField
  resourceName.value = props.section.resourceName
  fieldLabel.value = props.section.label
  isRequired.value = props.section.isRequired

  editing.value = true
}

function requestSectionRemove() {
  emit('removeSectionRequested')
}
</script>

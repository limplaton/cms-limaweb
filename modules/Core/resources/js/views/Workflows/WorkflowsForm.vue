<template>
  <form @submit.prevent="submit">
    <div class="mx-auto max-w-2xl p-4">
      <h2
        class="mb-4 text-lg font-medium text-neutral-700 dark:text-neutral-200"
        v-text="workflow.title"
      />

      <IFormGroup
        label-for="title"
        :label="$t('core::workflow.title')"
        required
      >
        <IFormInput id="title" v-model="form.title" />

        <IFormError :error="form.getError('title')" />
      </IFormGroup>

      <IFormGroup
        class="mb-4"
        label-for="description"
        :label="$t('core::workflow.description')"
        optional
      >
        <IFormTextarea id="description" v-model="form.description" rows="2" />

        <IFormError :error="form.getError('description')" />
      </IFormGroup>

      <IFormGroup :label="$t('core::workflow.when')" required>
        <ICustomSelect
          v-model="trigger"
          input-id="trigger"
          label="name"
          :clearable="false"
          :options="triggers"
          @option-selected="handleTriggerChange"
        />

        <IFormError :error="form.getError('trigger_type')" />
      </IFormGroup>

      <IFormGroup
        v-if="hasChangeField"
        :label="$t('core::workflow.field_change_to')"
        required
      >
        <FormFields
          :fields="fields"
          :form="form"
          :only="trigger.change_field.attribute"
          @update-field-value="form.fill($event.attribute, $event.value)"
          @set-initial-value="form.set($event.attribute, $event.value)"
        />
      </IFormGroup>

      <IFormGroup
        v-if="trigger"
        :class="{ 'mt-3': hasChangeField }"
        :label="$t('core::workflow.then')"
        required
      >
        <ICustomSelect
          v-if="trigger"
          v-model="action"
          input-id="action"
          label="name"
          :clearable="false"
          :options="trigger.actions"
          @option-selected="handleActionChange"
        />

        <IFormError :error="form.getError('action_type')" />

        <div
          v-if="action?.placeholders && action.placeholders.length > 0"
          class="mt-3"
        >
          <ITextDark
            class="mb-1 font-medium"
            :text="$t('core::mail_template.placeholders.placeholders')"
          />

          <TextPlaceholders :placeholders="action.placeholders" />
        </div>
      </IFormGroup>

      <IFormGroup v-if="hasActionFields">
        <FormFields
          :fields="fields"
          :form="form"
          :except="hasChangeField ? trigger.change_field.attribute : []"
          @update-field-value="form.fill($event.attribute, $event.value)"
          @set-initial-value="form.set($event.attribute, $event.value)"
        />
      </IFormGroup>
    </div>

    <div
      class="border-t border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-500/30 dark:bg-neutral-800"
    >
      <div class="flex items-center justify-end">
        <IFormSwitchField
          class="mr-4 border-r border-neutral-200 pr-4 dark:border-neutral-500/30"
        >
          <IFormSwitchLabel :text="$t('core::app.active')" />

          <IFormSwitch v-model="form.is_active" />
        </IFormSwitchField>

        <IButton
          variant="secondary"
          :text="$t('core::app.cancel')"
          @click="cancel"
        />

        <IButton
          class="ml-2"
          variant="primary"
          :disabled="form.busy"
          :text="$t('core::app.save')"
          @click="submit"
        />
      </div>
    </div>
  </form>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import cloneDeep from 'lodash/cloneDeep'
import find from 'lodash/find'
import findIndex from 'lodash/findIndex'

import TextPlaceholders from '@/Core/components/TextPlaceholders.vue'
import { useForm } from '@/Core/composables/useForm'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  show: Boolean,
  workflow: { required: true, type: Object },
  triggers: { required: true, type: Array },
})

const emit = defineEmits(['update:workflow', 'deleteRequested', 'hide'])

const { t } = useI18n()

const { fields, findField, hydrateFields } = useResourceFields()
const { form } = useForm()

// Selected trigger
const trigger = ref(null)

// Selected action
const action = ref(null)

/**
 * Watch the action change
 *
 * We need to remove the old fields and add the new ones
 * in the same time keeps the CHANGEFIELD in the DOM.
 */
watch(action, (newVal, oldVal) => {
  // Remove any previous fields
  if (oldVal && oldVal.fields) {
    const currentlyHasChangeField = hasChangeField.value

    oldVal.fields.forEach(field => {
      // We don't remove the change field as this field is trigger based
      if (
        !currentlyHasChangeField ||
        (currentlyHasChangeField &&
          field.attribute !== trigger.value.change_field.attribute)
      ) {
        const fidx = findIndex(fields.value, ['attribute', field.attribute])

        if (fidx !== -1) {
          fields.value.splice(
            findIndex(fields.value, ['attribute', field.attribute]),
            1
          )
        }
      }
    })
  }

  // Add any new fields
  if (newVal && newVal.fields) {
    newVal.fields.forEach(field => {
      if (!findField(field.attribute)) {
        fields.value.push(cloneDeep(field))
      }
    })
  }
})

const hasChangeField = computed(() => {
  if (!trigger.value) return false

  return Boolean(trigger.value.change_field)
})

const hasActionFields = computed(() => {
  if (!action.value) return false

  return action.value.fields.length > 0
})

function update() {
  form.put(`/workflows/${props.workflow.id}`).then(data => {
    emit('update:workflow', { ...data, key: props.workflow.key })
    emit('hide')
    Innoclapps.success(t('core::workflow.updated'))
  })
}

function create() {
  form.post('/workflows').then(data => {
    emit('update:workflow', { ...data, key: props.workflow.key })
    emit('hide')
    Innoclapps.success(t('core::workflow.created'))
  })
}

async function submit() {
  // Wait for the active switch to update
  await nextTick()

  props.workflow.id ? update() : create()
}

function handleTriggerChange(trigger) {
  action.value = null

  setFormData({
    title: form.title,
    description: form.description,
    is_active: form.is_active,
  })

  fields.value = hasChangeField.value ? [trigger.change_field] : []

  form.fill('trigger_type', trigger.identifier)
}

function handleActionChange(action) {
  form.fill('action_type', action.identifier || null)
}

function cancel() {
  if (props.workflow.id) {
    emit('hide')

    return
  }

  requestDelete()
}

function requestDelete() {
  emit('deleteRequested', props.workflow)
}

function setFormData(data = {}) {
  form.clear().set({
    trigger_type: data.trigger || null,
    action_type: data.action || null,
    title: data.title || null,
    description: data.description || null,
    is_active: data.is_active || true,
  })
}

function setWorkflowForUpdate() {
  trigger.value = find(props.triggers, [
    'identifier',
    props.workflow.trigger_type,
  ])

  action.value = find(trigger.value.actions, [
    'identifier',
    props.workflow.action_type,
  ])

  // Set the fields for update
  let updateFields = hasActionFields.value ? cloneDeep(action.value.fields) : []

  if (hasChangeField.value) {
    updateFields.push(trigger.value.change_field)
  }

  // Avoid duplicate field id's as the fields
  // are inline for all workflows
  updateFields = updateFields.map(field => {
    field.id = field.attribute + '-' + props.index

    return field
  })

  if (!fields.value.length) {
    fields.value = updateFields
  }
  hydrateFields(props.workflow.data)
}

onMounted(() => {
  setFormData({
    title: props.workflow.title,
    description: props.workflow.description,
    is_active: props.workflow.is_active,
    trigger: props.workflow.trigger_type,
    action: props.workflow.action_type,
  })

  if (props.workflow.id) {
    setWorkflowForUpdate()
  }
})
</script>

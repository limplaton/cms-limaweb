<template>
  <IPopover
    v-if="canBeInlineEdited"
    v-slot="{ hide }"
    adaptive-width
    shift
    flip
    @show="handlePopoverShowEvent"
    @hide="handlePopoverHideEvent"
  >
    <IPopoverButton
      v-bind="$attrs"
      icon="Pencil"
      :class="[
        'absolute right-0.5 z-20 my-auto -mt-0.5 mr-4 ',
        { '!block': popoverVisible },
      ]"
      small
      @click="editAction"
    />

    <IPopoverPanel :class="widthClass">
      <IOverlay :show="!hasFields">
        <form @submit.prevent="save(hide)">
          <IPopoverHeader>
            <IPopoverHeading :text="$t('core::fields.update_field')" />
          </IPopoverHeader>

          <IPopoverBody>
            <FormFields
              v-if="inlineEditReady"
              :fields="fields"
              :form="form"
              :resource-name="resourceName"
              :resource-id="resourceId"
              :is-floating="isFloating"
              :collapsed="false"
              @update-field-value="form.fill($event.attribute, $event.value)"
              @set-initial-value="form.set($event.attribute, $event.value)"
            />

            <slot name="after-inline-edit-form-fields" :hide-popover="hide" />
          </IPopoverBody>

          <IPopoverFooter class="flex items-center justify-end space-x-2">
            <IButton
              :text="$t('core::app.cancel')"
              :disabled="!inlineEditReady"
              basic
              @click="cancel(hide)"
            />

            <IButton
              type="submit"
              variant="primary"
              :text="$t('core::app.save')"
              :disabled="form.busy || !inlineEditReady"
              :loading="form.busy"
              @click="save(hide)"
            />
          </IPopoverFooter>
        </form>
      </IOverlay>
    </IPopoverPanel>
  </IPopover>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useTimeoutFn } from '@vueuse/core'
import castArray from 'lodash/castArray'
import cloneDeep from 'lodash/cloneDeep'
import get from 'lodash/get'
import isFunction from 'lodash/isFunction'

import { CancelToken } from '@/Core/services/HTTP'

import { useForm } from '../composables/useForm'
import { useResourceable } from '../composables/useResourceable'
import { useResourceFields } from '../composables/useResourceFields'

defineOptions({ inheritAttrs: false })

const props = defineProps([
  'field',
  'fieldFetcher',
  'resource',
  'resourceName',
  'resourceId',
  'editAction',
  'isFloating',
  'width',
])

const emit = defineEmits(['updated'])

const inlineEditReady = ref(false)
const popoverVisible = ref(false)
let saveCancelToken = null

const inlineEditField = ref({})

const { form } = useForm()
const { updateResource } = useResourceable(props.resourceName)
const { fields, hasFields, hydrateFields } = useResourceFields()

const widthClass = computed(() => {
  switch (props.field.inlineEditPanelWidth) {
    case 'medium':
      return 'w-screen sm:w-80'
    case 'large':
      return 'w-screen sm:w-96'
    default:
      return ''
  }
})

function cancel(hide) {
  if (saveCancelToken) {
    saveCancelToken()
    saveCancelToken = null
  }

  hide()
}

async function getFieldsForInlineEdit() {
  let field = await new Promise(resolve => {
    return isFunction(props.fieldFetcher)
      ? resolve(props.fieldFetcher())
      : resolve(props.field)
  })

  inlineEditField.value = field

  if (field.inlineEditWith !== null) {
    field = field.inlineEditWith
  }

  return field
}

async function handlePopoverShowEvent() {
  popoverVisible.value = true
  let availableFields = cloneDeep(castArray(await getFieldsForInlineEdit()))
  availableFields.forEach(f => (f.width = 'full'))

  fields.value = availableFields
  hydrateFields(props.resource)

  inlineEditReady.value = true
}

function handlePopoverHideEvent() {
  form.errors.clear()
  inlineEditReady.value = false
  fields.value = []

  useTimeoutFn(() => {
    popoverVisible.value = false
  }, 300)
}

async function save(hide) {
  const updatedResource = await updateResource(form, props.resourceId, {
    cancelToken: new CancelToken(token => (saveCancelToken = token)),
  })

  hide()
  emit('updated', updatedResource)
}

const canBeInlineEdited = computed(() => {
  return (
    props.resource.authorizations.update &&
    props.field.inlineEditDisabled !== true &&
    !get(props.resource, `_edit_disabled.${props.field.attribute}`) &&
    (props.field.inlineEditWith !== null || props.field.applicableForUpdate)
  )
})
</script>

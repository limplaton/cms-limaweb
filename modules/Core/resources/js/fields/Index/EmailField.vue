<template>
  <BaseIndexField
    v-slot="{ hasValue, value }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <IDropdown v-if="hasValue" @show="$emit('show')">
      <IDropdownButton :text="value" link no-caret />

      <IDropdownMenu>
        <slot name="start" :email="value" />

        <IButtonCopy
          as="IDropdownItem"
          :success-message="$t('core::fields.email_copied')"
          :text="value"
        >
          {{ $t('core::app.copy') }}
        </IButtonCopy>

        <IDropdownItem
          :href="'mailto:' + value"
          :text="$t('core::app.open_in_app')"
        />
      </IDropdownMenu>
    </IDropdown>

    <span v-else>&mdash;</span>
  </BaseIndexField>
</template>

<script setup>
defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])

defineEmits(['show'])
</script>

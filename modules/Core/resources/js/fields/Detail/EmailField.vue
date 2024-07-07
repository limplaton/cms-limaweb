<template>
  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <IDropdown v-if="hasValue" @show="$emit('show')">
      <IDropdownButton class="break-all" :text="value" link no-caret />

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
  </BaseDetailField>
</template>

<script setup>
defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])

defineEmits(['show'])
</script>

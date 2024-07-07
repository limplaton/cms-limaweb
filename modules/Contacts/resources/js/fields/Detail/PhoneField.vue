<template>
  <BaseDetailField
    v-slot="{ hasValue, value }"
    :field="field"
    :is-floating="isFloating"
    :resource="resource"
    :resource-name="resourceName"
    :resource-id="resourceId"
  >
    <template v-if="hasValue">
      <div v-for="(phone, index) in value" :key="index">
        <IDropdown>
          <IDropdownButton
            v-i-tooltip="$t('contacts::fields.phone.types.' + phone.type)"
            :text="phone.number"
            link
            no-caret
          />

          <IDropdownMenu>
            <IButtonCopy
              as="IDropdownItem"
              :text="phone.number"
              :success-message="$t('contacts::fields.phone.copied')"
            >
              {{ $t('core::app.copy') }}
            </IButtonCopy>

            <IDropdownItem
              :href="'tel:' + phone.number"
              :text="$t('core::app.open_in_app')"
            />
          </IDropdownMenu>
        </IDropdown>
      </div>
    </template>

    <span v-else>&mdash;</span>
  </BaseDetailField>
</template>

<script setup>
defineProps(['resource', 'resourceName', 'resourceId', 'field', 'isFloating'])
</script>

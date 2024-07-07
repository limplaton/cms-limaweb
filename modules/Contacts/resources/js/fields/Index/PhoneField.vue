<template>
  <BaseIndexField
    v-slot="{ hasValue, value }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <template v-if="hasValue">
      <div
        v-for="(phone, index) in value"
        :key="index"
        :class="!column.wrap ? 'mr-2.5 inline last:mr-0' : ''"
      >
        <IDropdown>
          <IDropdownButton
            v-i-tooltip="$t('contacts::fields.phone.types.' + phone.type)"
            :text="phone.number"
            link
            no-caret
          />

          <IDropdownMenu>
            <slot name="start" :phone="phone" />

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
  </BaseIndexField>
</template>

<script setup>
defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>

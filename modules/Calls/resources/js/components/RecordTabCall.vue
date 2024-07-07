<template>
  <ITab v-memo="[resource.calls_count]">
    <Icon icon="Phone" />

    {{ $t('calls::call.calls') }}

    <IBadge
      v-show="resource.calls_count"
      variant="neutral"
      :text="resource.calls_count"
      pill
    />
  </ITab>
</template>

<script setup>
defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})
</script>

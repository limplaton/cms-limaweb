<template>
  <ITab v-memo="[resource.notes_count]">
    <Icon icon="PencilAlt" />

    {{ $t('notes::note.notes') }}

    <IBadge
      v-show="resource.notes_count"
      variant="neutral"
      :text="resource.notes_count"
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

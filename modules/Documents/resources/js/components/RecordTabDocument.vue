<template>
  <ITab v-memo="[badge, badgeVariant]">
    <Icon icon="DocumentText" />

    {{ $t('documents::document.documents') }}

    <IBadge v-show="badge" :text="badge" :variant="badgeVariant" pill />
  </ITab>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  resourceName: { required: true, type: String },
  resourceId: { required: true, type: [String, Number] },
  resource: { required: true, type: Object },
})

const badge = computed(() =>
  props.resource.draft_documents_count > 0
    ? props.resource.draft_documents_count
    : props.resource.documents_count
)

const badgeVariant = computed(() => {
  return (props.resource.documents || []).filter(
    document => document.status === 'draft'
  ).length > 0
    ? 'danger'
    : 'neutral'
})
</script>

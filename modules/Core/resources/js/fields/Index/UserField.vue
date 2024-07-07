<template>
  <BaseIndexField
    v-slot="{ hasValue, value }"
    :resource-name="resourceName"
    :resource-id="resourceId"
    :row="row"
    :field="field"
  >
    <span v-if="hasValue">
      <IAvatar
        class="mr-1"
        size="xs"
        :src="value.avatar_url"
        :alt="value.name"
      />

      <span>
        {{ value.name }}
      </span>
    </span>

    <span v-else>&mdash;</span>
  </BaseIndexField>
</template>

<script setup>
defineProps(['column', 'row', 'field', 'resourceName', 'resourceId'])
</script>

<template>
  <IButton basic small @click="$emit('update:collapsed', !collapsed)">
    {{
      $t(collapsed ? 'core::fields.more_fields' : 'core::fields.hide_fields', {
        count: total,
      })
    }}
    <Icon :icon="collapsed ? 'ChevronDownSolid' : 'ChevronUpSolid'" />
  </IButton>
</template>

<script setup>
defineProps({
  collapsed: { required: true, type: Boolean },
  total: { type: Number, required: true },
})

defineEmits(['update:collapsed'])
</script>

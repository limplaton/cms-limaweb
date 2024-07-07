<template>
  <IBadge variant="info" :text="$t('core::filters.static_rule_indicator')" />
</template>

<script setup>
import propsDefinition from './props'

defineOptions({ inheritAttrs: false })

defineProps(propsDefinition)
</script>

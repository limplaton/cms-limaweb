<template>
  <div class="space-x-0.5">
    <IButton
      v-i-tooltip.bottom="$t('core::app.list_view')"
      icon="TableCells"
      :to="{ name: 'activity-index' }"
      :active="active === 'index'"
      basic
    />

    <IButton
      v-i-tooltip.bottom="$t('activities::calendar.calendar')"
      icon="Calendar"
      :to="{ name: 'activity-calendar' }"
      :active="active === 'calendar'"
      basic
    />
  </div>
</template>

<script setup>
defineProps({ active: { type: String, required: true } })
</script>

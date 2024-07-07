<template>
  <div class="space-x-0.5">
    <IButton
      v-i-tooltip.bottom="$t('core::app.list_view')"
      icon="TableCells"
      :to="{ name: 'deal-index' }"
      :active="active === 'index'"
      basic
    />

    <IButton
      v-i-tooltip.bottom="$t('deals::board.board')"
      icon="ViewColumns"
      :to="{ name: 'deal-board' }"
      :active="active === 'board'"
      basic
    />
  </div>
</template>

<script setup>
defineProps({ active: { type: String, required: true } })
</script>

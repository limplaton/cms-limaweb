<template>
  <div
    class="flex items-center space-x-1 text-base text-neutral-600 dark:text-white sm:text-sm"
  >
    <span v-t="'core::table.length_menu.show'"></span>

    <IDropdown>
      <IDropdownButton :disabled="disabled" :text="perPage || '--'" basic />

      <IDropdownMenu>
        <IDropdownItem
          v-for="option in perPageOptions"
          :key="option"
          :active="perPage == option"
          :text="option"
          @click="emitEvents(option)"
        />
      </IDropdownMenu>
    </IDropdown>

    <span v-t="'core::table.length_menu.entries'"></span>
  </div>
</template>

<script setup>
defineProps({
  disabled: Boolean,
  perPage: Number,
  perPageOptions: Array,
})

const emit = defineEmits(['change', 'update:perPage'])

function emitEvents(option) {
  emit('update:perPage', option)
  emit('change', option)
}
</script>

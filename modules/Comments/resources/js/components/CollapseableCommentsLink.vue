<template>
  <ILink class="group mt-1 inline-flex items-center" basic>
    <span
      class="mr-3 size-5 text-neutral-500 group-hover:text-neutral-700 dark:text-neutral-300 dark:group-hover:text-white"
    >
      <Icon v-show="!loading" icon="ChatAlt" class="size-5" />

      <ISpinner v-if="loading" class="mt-px size-4" />
    </span>

    <span
      v-t="{
        path: 'comments::comment.total',
        args: { total: total },
      }"
    />

    <Icon
      class="ml-2 size-4"
      :icon="collapsed ? 'ChevronDownSolid' : 'ChevronRightSolid'"
    />
  </ILink>
</template>

<script setup>
defineProps({
  total: { type: Number, required: true },
  collapsed: { type: Boolean, default: true },
  loading: { type: Boolean, default: false },
})
</script>

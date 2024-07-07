<template>
  <ICard
    :class="{
      'h-56 md:h-52': size === 'md',
      'h-64': size === 'lg',
      'animate-pulse': pulse,
    }"
  >
    <ICardBody>
      <div class="mt-2 flex">
        <div
          class="mr-10 h-5 grow rounded bg-neutral-200 dark:bg-neutral-800"
        />

        <div class="h-5 w-36 rounded bg-neutral-200 dark:bg-neutral-800" />
      </div>

      <div
        class="mt-7 w-full bg-neutral-200 dark:bg-neutral-800"
        :class="{ 'h-28': size === 'md', 'h-40': size === 'lg' }"
      />
    </ICardBody>
  </ICard>
</template>

<script setup>
defineProps({
  pulse: Boolean,
  size: { type: String, default: 'md' },
})
</script>

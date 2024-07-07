<template>
  <Teleport to="#navbar-actions">
    <slot name="actions" />
  </Teleport>

  <main
    id="main"
    :class="[
      'relative flex-1 focus:outline-none',
      scrollable ? 'overflow-y-auto' : '',
    ]"
    v-bind="$attrs"
  >
    <div :class="{ 'py-8 sm:py-6': !noPadding }">
      <div :class="['mx-auto', { 'px-4 sm:px-6 lg:px-8': !noPadding }]">
        <div :class="{ 'sm:py-4': !noPadding }">
          <IOverlay :show="overlay">
            <div
              v-if="$slots.actions"
              :class="[
                'mb-4 flex justify-end lg:hidden',
                { 'px-4 pt-10 sm:px-6 lg:px-8': noPadding },
              ]"
            >
              <slot name="actions" />
            </div>

            <slot />
          </IOverlay>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

defineProps({
  overlay: Boolean,
  noPadding: Boolean,
  scrollable: { type: Boolean, default: true },
})
</script>

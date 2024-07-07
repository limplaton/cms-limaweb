<template>
  <ILink
    data-slot="control"
    :href="href"
    :class="[
      'group flex items-center rounded-lg text-base font-semibold focus:outline-none',

      isActive
        ? 'active bg-neutral-200/60 text-primary-600 dark:bg-neutral-900/40 dark:text-primary-400'
        : 'text-neutral-700 hover:bg-neutral-200/50 hover:text-primary-600 dark:text-white dark:hover:dark:bg-neutral-900/40 dark:hover:text-primary-400',

      'px-3 py-2 sm:text-sm',

      '[&>[data-slot=icon]]:-ml-1 [&>[data-slot=icon]]:mr-3 [&>[data-slot=icon]]:size-5 [&>[data-slot=icon]]:sm:size-4',

      '[&>[data-slot=badge]]:-my-0.5 [&>[data-slot=badge]]:ml-auto',
    ]"
    plain
    @click="navigate"
  >
    <slot />
  </ILink>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ILink from '../ILink.vue'

defineOptions({
  name: 'IVerticalNavigationItem',
})

const props = defineProps({
  to: [String, Object],
  active: Boolean,
  href: { type: String, default: '#' },
})

const router = useRouter()
const route = useRoute()

const isActive = computed(() => {
  if (props.active) return true
  if (!props.to) return false

  const { path } = router.resolve(props.to)

  return route.path == path || route.path.startsWith(path)
})

function navigate() {
  if (!props.to) return

  const { path } = router.resolve(props.to)

  if (route.path == path) return

  router.push(props.to)
}
</script>

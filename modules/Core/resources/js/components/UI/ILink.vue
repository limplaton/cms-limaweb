<template>
  <ILinkBase
    v-if="isExternalLink"
    target="_blank"
    rel="noopener noreferrer"
    class="inline-flex items-center"
    :href="href"
    v-bind="$attrs"
    @click="$emit('click', $event)"
  >
    <slot>
      {{ text }}
    </slot>

    <Icon
      v-if="!Object.hasOwn($attrs, 'download')"
      icon="ExternalLink"
      class="ml-2 size-5 shrink-0 sm:size-4"
    />
  </ILinkBase>

  <RouterLink
    v-else-if="isRouterLink"
    v-slot="{ isActive, href: routerLinkHref, navigate }"
    v-bind="$props"
    custom
  >
    <ILinkBase
      v-bind="$attrs"
      :href="determineRouterLinkHref(routerLinkHref)"
      :class="isActive ? activeClass : inactiveClass"
      @click="navigate($event), $emit('click', $event)"
    >
      <slot>{{ text }}</slot>
    </ILinkBase>
  </RouterLink>

  <ILinkBase
    v-else
    v-bind="$attrs"
    :href="href || '#'"
    @click.prevent="$emit('click', $event)"
  >
    <slot>{{ text }}</slot>
  </ILinkBase>
</template>

<script setup>
import { computed } from 'vue'

import ILinkBase from './ILinkBase.vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  text: [String, Number],
  href: String,

  // RouterLink
  to: [String, Object],
  activeClass: String,
  exactActiveClass: String,
  inactiveClass: String,
})

defineEmits(['click'])

const isExternalLink = computed(
  () =>
    props.href &&
    typeof props.href === 'string' &&
    props.href.startsWith('http')
)

const isRouterLink = computed(() => Boolean(props.to))

/**
 * "RouterLink" allows providing custom "href" attribute, different from the one the router generated,
 *  we will allow this too for convenience.
 *
 * @param {String} currentHref
 */
function determineRouterLinkHref(currentHref) {
  return props.href && props.href !== currentHref ? props.href : currentHref
}
</script>

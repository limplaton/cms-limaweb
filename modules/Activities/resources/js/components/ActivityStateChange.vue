<template>
  <span
    v-i-tooltip="tooltipContent"
    class="inline-block"
    :v-tooltip-placement="tooltipPlacement"
  >
    <ILink :class="linkClasses" plain @click="changeState">
      <ISpinner
        v-if="resourceBeingUpdated"
        :class="[
          'size-4',
          isCompleted
            ? 'text-neutral-500 dark:text-neutral-300'
            : 'text-success-500 dark:text-success-400',
        ]"
      />

      <span
        v-else
        :class="[
          'flex size-4 items-center justify-center rounded-full border',
          isCompleted
            ? 'border-success-300 dark:border-success-400'
            : 'border-neutral-300 dark:border-neutral-600',
        ]"
      >
        <Icon v-if="isCompleted" icon="Check"></Icon>
      </span>
    </ILink>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { useResourceable } from '@/Core/composables/useResourceable'

const props = defineProps({
  activityId: { required: true, type: Number },
  isCompleted: { required: true, type: Boolean },
  tooltipPlacement: { type: String, default: 'top' },
  disabled: Boolean,
})

const emit = defineEmits(['changed'])

const { t } = useI18n()

const { updateResource, resourceBeingUpdated } = useResourceable(
  Innoclapps.resourceName('activities')
)

const linkClasses = computed(() => {
  let classes = ['inline-block mr-0.5 focus:outline-none']

  if (props.isCompleted) {
    classes.push(
      'text-success-500 hover:text-neutral-500 dark:text-success-400 dark:hover:text-neutral-300'
    )
  } else {
    classes.push(
      'text-neutral-500 hover:text-success-600 dark:text-neutral-300 dark:hover:text-success-400'
    )
  }

  if (props.disabled || resourceBeingUpdated.value) {
    classes.push('pointer-events-none opacity-60')
  }

  return classes
})

const tooltipContent = computed(() => {
  if (props.disabled) {
    return t('users::user.not_authorized')
  }

  if (props.isCompleted) {
    return t('activities::activity.mark_as_incomplete')
  }

  return t('activities::activity.mark_as_completed')
})

/**
 * Change state
 */
function changeState() {
  updateResource({ is_completed: !props.isCompleted }, props.activityId).then(
    activity => emit('changed', activity)
  )
}
</script>

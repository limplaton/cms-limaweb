<template>
  <ILink
    class="group flex w-full items-center"
    tabindex="-1"
    plain
    @click="$emit('click')"
  >
    <span class="flex items-center px-4 py-2 text-base font-medium sm:text-sm">
      <span
        :class="[
          'flex size-7 shrink-0 items-center justify-center rounded-full',
          {
            'bg-success-500 group-hover:bg-success-700 dark:bg-success-400 dark:group-hover:bg-success-500':
              (dealStatus === 'won' && dealStageIsCurrentOrBehindStage) ||
              dealStatus === 'open',

            'border border-success-500 bg-transparent group-hover:border-success-600 dark:border-success-400 dark:group-hover:border-success-600':
              dealStatus === 'won' && currentStageIsAfterDealStage,

            'bg-danger-500 group-hover:bg-danger-700 dark:bg-danger-400 dark:group-hover:bg-danger-600':
              dealStatus === 'lost' && dealStageIsCurrentOrBehindStage,

            'border border-danger-500 bg-transparent group-hover:border-danger-600 dark:border-danger-400 dark:group-hover:border-danger-500':
              dealStatus === 'lost' && currentStageIsAfterDealStage,
          },
        ]"
      >
        <ISpinner v-if="requestInProgress" class="size-6" />

        <Icon
          v-else-if="dealStatus === 'open' || dealStatus === 'won'"
          icon="Check"
          :class="[
            'size-6',
            dealStatus === 'open'
              ? 'text-white'
              : {
                  'text-white': dealStageIsCurrentOrBehindStage,
                  'text-success-500': currentStageIsAfterDealStage,
                },
          ]"
        />
        <!-- lost -->
        <Icon
          v-else
          icon="XSolid"
          :class="[
            'size-6',
            {
              'text-white': dealStageIsCurrentOrBehindStage,
              'text-danger-500': currentStageIsAfterDealStage,
            },
          ]"
        />
      </span>

      <span
        class="ml-4 text-base font-medium text-neutral-900 dark:text-neutral-200 sm:text-sm"
      >
        <slot />
      </span>
    </span>
  </ILink>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  dealStatus: { type: String, required: true },
  requestInProgress: Boolean,
  stageIndex: { required: true, type: Number },
  dealStageIndex: { required: true, type: Number },
})

defineEmits(['click'])

const currentStageIsAfterDealStage = computed(
  () => props.stageIndex > props.dealStageIndex
)

const dealStageIsCurrentOrBehindStage = computed(
  () => props.stageIndex <= props.dealStageIndex
)
</script>

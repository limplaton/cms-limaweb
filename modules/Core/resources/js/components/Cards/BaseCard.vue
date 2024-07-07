<template>
  <IOverlay :show="isCardLoading">
    <div
      class="rounded-lg bg-white shadow ring-1 ring-neutral-200 dark:bg-neutral-900 dark:ring-neutral-500/30"
      v-bind="$attrs"
    >
      <div class="group flex flex-col items-center text-lg md:flex-row">
        <div class="truncate md:grow">
          <div class="flex items-center px-6 pt-4 md:pb-4">
            <ITextDisplay
              class="-mt-0.5 ml-10 truncate whitespace-nowrap font-semibold sm:ml-0"
              :text="card.name"
            />

            <div class="ml-2 flex items-center space-x-1.5">
              <span
                v-if="card.helpText"
                v-i-tooltip.bottom.light="card.helpText"
                class="flex"
              >
                <IButton
                  class="pointer-events-none"
                  icon="QuestionMarkCircle"
                  basic
                  small
                />
              </span>

              <IButton
                icon="Refresh"
                :class="[
                  'group-hover:opacity-100',
                  isCardLoading ? 'opacity-100' : 'opacity-0',
                ]"
                :loading="isCardLoading"
                basic
                small
                @click="fetchCard(true)"
              />
            </div>
          </div>
        </div>

        <div class="flex shrink-0 space-x-1 px-3 sm:-mt-0 sm:px-6 sm:py-3">
          <slot name="actions" />

          <IDropdown v-if="card.withUserSelection" placement="bottom-end">
            <IDropdownButton
              :text="selectedUser?.name || $t('core::app.all')"
              basic
            />

            <IDropdownMenu class="max-h-64">
              <IDropdownItem
                v-for="user in usersForSelection"
                :key="user.id"
                :text="user.name"
                :active="selectedUser && selectedUser.id === user.id"
                @click="selectedUser = user"
              />

              <IDropdownSeparator />

              <IDropdownItem
                :text="$t('core::app.all')"
                :active="!selectedUser"
                @click="selectedUser = null"
              />
            </IDropdownMenu>
          </IDropdown>

          <IDropdown v-if="hasRanges" placement="bottom-end">
            <IDropdownButton
              class="shrink-0"
              :text="selectedRange.label"
              basic
            />

            <IDropdownMenu>
              <IDropdownItem
                v-for="range in card.ranges"
                :key="range.value"
                :text="range.label"
                :active="range.value === selectedRange.value"
                @click="selectedRange = range"
              />
            </IDropdownMenu>
          </IDropdown>
        </div>
      </div>

      <slot />
    </div>
  </IOverlay>
</template>

<script setup>
import { computed, ref, unref, watch } from 'vue'
import { onMounted } from 'vue'
import find from 'lodash/find'

import { useApp } from '@/Core/composables/useApp'
import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'
import { useLoader } from '@/Core/composables/useLoader'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  card: Object,
  loading: Boolean,
  reloadOnQueryStringChange: { type: Boolean, default: true },
  requestQueryString: Object,
})

const emit = defineEmits(['retrieved'])

const { setLoading, isLoading } = useLoader()
const { users } = useApp()

const selectedUser = ref(null)

const selectedRange = ref(
  find(props.card.ranges, ['value', props.card.range]) || props.card.ranges[0]
)

const usersForSelection = computed(() =>
  [...(props.card.users || users.value)].map(user => ({
    id: user.id,
    name: user.name,
  }))
)

const isCardLoading = computed(() => props.loading || isLoading.value)
const hasRanges = computed(() => props.card.ranges.length > 0)

if (
  props.card.withUserSelection !== false &&
  typeof props.card.withUserSelection === 'number'
) {
  selectedUser.value = find(usersForSelection.value, [
    'id',
    props.card.withUserSelection,
  ])
}

watch(selectedUser, () => fetchCard())
watch(selectedRange, () => fetchCard())

watch(
  () => props.requestQueryString,
  () => {
    props.reloadOnQueryStringChange && fetchCard()
  },
  { deep: true }
)

async function fetchCard(reloadCache = false) {
  setLoading(true)

  let queryString = {
    range: unref(selectedRange)?.value,
    ...(props.requestQueryString || {}),
    reload_cache: reloadCache === true,
  }

  if (props.card.withUserSelection) {
    queryString.user_id = selectedUser.value ? selectedUser.value.id : null
  }

  try {
    const { data: card } = await Innoclapps.request(
      `/cards/${props.card.uriKey}`,
      {
        params: queryString,
      }
    )

    emit('retrieved', {
      card: card,
      requestQueryString: queryString,
    })
  } finally {
    setLoading(false)
  }
}

onMounted(() => {
  if (props.card.refreshOnActionExecuted) {
    useGlobalEventListener('action-executed', fetchCard)
  }

  useGlobalEventListener('refresh-cards', fetchCard)

  if (props.card.floatingResource) {
    useGlobalEventListener(
      ['floating-resource-updated', 'floating-resource-action-executed'],
      updatedData => {
        if (
          updatedData.resourceName === props.card.floatingResource.resourceName
        ) {
          fetchCard(true)
        }
      }
    )
  }
})
</script>

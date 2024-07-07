<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-1 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
    >
      <div
        v-show="visible"
        ref="searchResultsRef"
        class="z-30 -ml-12 w-full max-w-full sm:max-w-xl md:-ml-0 md:max-w-2xl lg:-ml-px lg:max-w-3xl"
        :style="{ ...floatingStyles }"
      >
        <div
          class="overflow-hidden rounded-b-lg border-b border-neutral-200 bg-white shadow-lg dark:border-neutral-500/30 dark:bg-neutral-800 sm:border-x"
        >
          <div
            class="sm:grid sm:auto-cols-max sm:grid-flow-col sm:grid-cols-3 sm:gap-x-4"
          >
            <div
              class="border-b border-neutral-200 bg-neutral-50/40 px-0 dark:border-neutral-500/30 dark:bg-neutral-900/50 sm:border-b-0 sm:border-r sm:px-3 sm:py-6"
            >
              <ul
                class="flex space-x-2 overflow-x-auto p-3 sm:block sm:space-x-0 sm:overflow-x-visible sm:p-0"
              >
                <li class="shrink-0 sm:mb-4">
                  <IButton
                    class="justify-start gap-x-3"
                    variant="info"
                    :active="activeSearchResultsSection === 'all'"
                    block
                    ghost
                    @click="activeSearchResultsSection = 'all'"
                  >
                    <Icon icon="Bars3" class="!size-5" />
                    {{ $t('core::resource.all_resources') }}
                  </IButton>
                </li>

                <li
                  v-for="resource in result"
                  :key="resource.title"
                  class="shrink-0 sm:mb-0.5 sm:last:mb-0"
                >
                  <IButton
                    class="justify-start gap-x-3 truncate"
                    :active="activeSearchResultsSection === resource.title"
                    basic
                    block
                    @click="activeSearchResultsSection = resource.title"
                  >
                    <Icon
                      class="!size-5 text-neutral-500 dark:text-neutral-400"
                      :icon="resource.icon"
                    />
                    {{ resource.title }}
                    <span
                      v-show="resource.data.length"
                      class="ml-auto opacity-70"
                      v-text="resource.data.length"
                    />
                  </IButton>
                </li>
              </ul>

              <div
                v-show="history.length > 0"
                class="hidden pl-3 pr-0.5 sm:block"
              >
                <ITextDark
                  v-t="'core::app.recent_search_history'"
                  class="mt-5 font-medium"
                />

                <ul>
                  <li
                    v-for="(text, index) in history"
                    :key="text"
                    class="flex items-center justify-between"
                  >
                    <ILink
                      class="block max-w-40 truncate"
                      :title="text"
                      :text="text"
                      basic
                      @click="$emit('historyChoosen', text)"
                    />

                    <IButton
                      icon="XSolid"
                      basic
                      small
                      @click="$emit('historyRemoved', index)"
                    />
                  </li>
                </ul>
              </div>
            </div>

            <div class="col-span-2">
              <div
                class="max-h-screen overflow-y-auto px-4 py-3 sm:pl-1 sm:pr-5 lg:h-[34rem] lg:max-h-[40rem] lg:min-h-full"
              >
                <div class="py-1">
                  <ITextBlockDark
                    v-show="
                      !hasAnySearchResults &&
                      activeSearchResultsSection === 'all'
                    "
                    class="-mb-8 flex min-h-full flex-col items-center text-balance text-center sm:mt-16 sm:pt-2.5"
                  >
                    <Icon
                      class="my-4 size-10 text-neutral-400"
                      icon="MagnifyingGlassSolid"
                    />
                    {{ $t('core::app.no_search_results') }}
                  </ITextBlockDark>

                  <div
                    v-for="resource in result"
                    v-show="
                      resource.title === activeSearchResultsSection ||
                      activeSearchResultsSection === 'all'
                    "
                    :key="resource.title"
                    class="pt-2 sm:pt-[1.1rem]"
                  >
                    <ITextDark
                      v-show="resource.data.length"
                      class="font-medium"
                    >
                      {{ resource.title }}
                      <ITextSmall class="ml-1 inline">
                        ({{ resource.data.length }})
                      </ITextSmall>
                    </ITextDark>

                    <ITextBlockDark
                      v-if="
                        !resource.data.length &&
                        activeSearchResultsSection !== 'all'
                      "
                      class="flex flex-col items-center text-balance pb-6 text-center sm:mt-16"
                    >
                      <Icon
                        class="my-4 size-8 text-neutral-400"
                        :icon="resource.icon"
                      />
                      {{ $t('core::app.no_search_results') }}
                    </ITextBlockDark>

                    <div
                      v-for="record in resource.data"
                      :key="record.path"
                      class="mt-1"
                    >
                      <ILink
                        class="group relative mb-2 block whitespace-normal rounded-lg border border-neutral-200 bg-neutral-50/60 py-2 pl-5 pr-12 text-base/6 text-neutral-600 ease-in-out hover:border-info-200 hover:bg-info-50 hover:text-info-600 dark:border-neutral-600 dark:bg-neutral-400/10 dark:text-white dark:hover:border-info-400/30 dark:hover:bg-info-800/20 dark:hover:text-info-400 sm:text-sm/6"
                        :href="record.path"
                        plain
                        @click="handleResultItemClickEvent(record, resource)"
                      >
                        <span
                          class="block truncate font-medium"
                          v-text="record.display_name"
                        />

                        <ITextSmall v-if="record.created_at">
                          {{ $t('core::app.created_at') }}
                          {{ localizedDateTime(record.created_at) }}
                        </ITextSmall>

                        <Icon
                          class="absolute right-4 top-6 size-5 text-neutral-800 dark:text-primary-200 group-hover:dark:text-info-400 sm:size-4"
                          :icon="
                            resource.action === 'float'
                              ? 'Window'
                              : 'ChevronRight'
                          "
                        />
                      </ILink>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { autoUpdate, useFloating } from '@floating-ui/vue'
import { onClickOutside, useParentElement } from '@vueuse/core'

import { useDates } from '../composables/useDates'
import { useFloatingResourceModal } from '../composables/useFloatingResourceModal'

const props = defineProps({
  result: { type: Array, required: true },
  history: { type: Array, required: true },
  visible: { type: Boolean, required: true },
})

const emit = defineEmits(['update:visible', 'historyChoosen', 'historyRemoved'])

const router = useRouter()
const route = useRoute()

const { localizedDateTime } = useDates()
const { floatResourceInDetailMode } = useFloatingResourceModal()

const wrapper = useParentElement()
const searchResultsRef = ref(null)

const { floatingStyles } = useFloating(wrapper, searchResultsRef, {
  strategy: 'fixed',
  placement: 'bottom-start',
  whileElementsMounted: autoUpdate,
})

const activeSearchResultsSection = ref('all')

const hasAnySearchResults = computed(() =>
  props.result.some(result => result.data.length > 0)
)

function hideResult() {
  if (props.visible === true) {
    emit('update:visible', false)
  }
}

function handleResultItemClickEvent(item, resource) {
  if (resource.action === 'float') {
    floatResourceInDetailMode({
      resourceName: item.resourceName,
      resourceId: item.id,
    })
  } else {
    router.push(item.path)
  }
}

onClickOutside(searchResultsRef, hideResult, {
  // dialog and popper are ignored becauase of the float action, so the search results are staying open
  ignore: ['#navSearchInput', '.dialog', '.__popper'],
})

watch(() => route.path, hideResult)
</script>

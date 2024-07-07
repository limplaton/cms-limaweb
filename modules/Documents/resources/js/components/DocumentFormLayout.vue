<template>
  <div
    class="absolute inset-0 z-40 h-full max-h-full min-h-screen w-full bg-white dark:bg-neutral-900"
  >
    <!-- navbar start -->
    <div
      class="sticky top-0 z-50 border-b border-neutral-200 bg-neutral-100 dark:border-neutral-500/30 dark:bg-neutral-800"
    >
      <div class="container mx-auto">
        <div class="mx-auto max-w-6xl">
          <div class="px-3 py-4 sm:px-0">
            <div
              class="flex items-center justify-between space-x-4 sm:space-x-0"
            >
              <ILink
                class="sm:!text-base/6"
                :text="$t('core::app.exit')"
                @click="$emit('exitRequested')"
              />

              <IFormSelect
                class="block sm:hidden"
                :model-value="activeSection"
                @update:model-value="updateActiveSection($event)"
              >
                <option
                  v-for="section in sections"
                  :key="section.id"
                  :value="section.id"
                >
                  {{ section.name }}
                </option>
              </IFormSelect>

              <div class="hidden justify-center sm:flex">
                <ILink
                  v-for="(section, index) in sections"
                  :key="section.id"
                  variant="primary"
                  :basic="section.id !== activeSection"
                  :class="[
                    'flex items-center font-medium sm:!text-base/6',
                    index !== sections.length - 1 ? 'mr-6 space-x-4' : '',
                  ]"
                  @click="updateActiveSection(section.id)"
                >
                  <div class="inline-flex">
                    <span v-text="section.name"></span>

                    <IBadge
                      v-show="section.badge"
                      class="-mt-px ml-1.5"
                      :variant="section.badgeVariant || 'primary'"
                      :text="section.badge"
                      pill
                    />
                  </div>

                  <Icon
                    v-if="index !== sections.length - 1"
                    icon="ChevronRight"
                    class="mt-0.5 size-4 shrink-0 text-neutral-400 dark:text-neutral-500"
                  />
                </ILink>
              </div>

              <slot name="actions" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- navbar end -->
    <div class="h-full min-h-full overflow-y-auto">
      <div class="px-4 py-6 sm:px-0">
        <div style="padding-bottom: 200px">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeMount, onBeforeUnmount, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  activeSection: { required: true, type: String },
  totalProducts: Number,
  totalSigners: Number,
  remainingSigners: Number,
})

const emit = defineEmits(['exitRequested', 'update:active-section'])

const { t } = useI18n()

const sections = computed(() => [
  { name: t('documents::document.sections.details'), id: 'details' },
  {
    name: t('documents::document.sections.products'),
    id: 'products',
    badge: props.totalProducts,
  },
  {
    name: t('documents::document.sections.signature'),
    id: 'signature',
    // On create, show primary badge with total signers
    // On edit, if all signed, show success badge with total signers
    // On edit, if not all signed, show warning badge with total left to sign
    badge:
      props.totalSigners > 0 && props.remainingSigners > 0
        ? props.remainingSigners
        : props.totalSigners,
    badgeVariant:
      props.remainingSigners > 0
        ? 'warning'
        : props.remainingSigners === 0 && props.totalSigners > 0
          ? 'success'
          : 'primary',
  },
  { name: t('documents::document.sections.content'), id: 'content' },
  { name: t('documents::document.sections.send'), id: 'send' },
])

function updateActiveSection(section) {
  emit('update:active-section', section)
}

onBeforeMount(() => {
  document.body.classList.add('overflow-y-hidden')
})

onBeforeUnmount(() => {
  document.body.classList.remove('overflow-y-hidden')
  document.body.classList.remove('document-section-' + props.activeSection)
})

watch(
  () => props.activeSection,
  (newVal, oldVal) => {
    document.body.classList.remove('document-section-' + oldVal)
    document.body.classList.add('document-section-' + newVal)
  },
  { immediate: true }
)
</script>

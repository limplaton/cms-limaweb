<template>
  <FloatVirtual
    enter="transition-opacity duration-200 ease-out"
    enter-from="opacity-0"
    enter-to="opacity-100"
    :show="show"
    portal
    flip
    @initial="configureMenu"
  >
    <div
      class="w-56 divide-y divide-neutral-200 overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-neutral-200 dark:divide-neutral-500/30 dark:bg-neutral-800 dark:ring-neutral-700"
    >
      <div class="px-4 py-2">
        <ITextBlockDark class="font-medium" :text="label" />
      </div>

      <div v-if="isSortable" class="px-1.5 py-2">
        <div class="flex flex-col space-y-1">
          <ILink
            class="flex items-center rounded-lg p-1 px-2 text-base/6 text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 dark:text-white dark:hover:bg-neutral-700 sm:text-sm/6"
            basic
            plain
            @click="emitAndClose('sortAsc', attribute)"
          >
            <Icon icon="ArrowUpSolid" class="mr-2 size-4" />
            {{ $t('core::app.sort_ascending') }}
          </ILink>

          <ILink
            class="flex items-center rounded-lg p-1 px-2 text-base/6 text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 dark:text-white dark:hover:bg-neutral-700 sm:text-sm/6"
            basic
            plain
            @click="emitAndClose('sortDesc', attribute)"
          >
            <Icon icon="ArrowDownSolid" class="mr-2 size-4" />
            {{ $t('core::app.sort_descending') }}
          </ILink>
        </div>
      </div>

      <div v-if="!isPrimary && customizeable" class="px-1.5 py-2">
        <div class="flex flex-col space-y-1">
          <ILink
            class="flex items-center rounded-lg p-1 px-2 text-base/6 text-neutral-700 hover:bg-neutral-100 hover:text-neutral-900 dark:text-white dark:hover:bg-neutral-700 sm:text-sm/6"
            basic
            plain
            @click="emitAndClose('updated', { visible: false })"
          >
            <Icon icon="Eye" class="mr-2 size-4" />
            {{ $t('core::app.hide') }}
          </ILink>
        </div>
      </div>

      <div v-if="customizeable">
        <div class="-ml-px px-4 py-2">
          <IFormCheckboxField>
            <IFormCheckbox
              :checked="wrap"
              @update:checked="$emit('updated', { wrap: $event })"
            />

            <IFormCheckboxLabel :text="$t('core::table.wrap_column')" />
          </IFormCheckboxField>
        </div>
      </div>
    </div>
  </FloatVirtual>
</template>

<script setup>
import { computed, ref, watchEffect } from 'vue'
import { FloatVirtual, useOutsideClick } from '@headlessui-float/vue'
import { useParentElement } from '@vueuse/core'

defineProps([
  'attribute',
  'label',
  'wrap',
  'isSortable',
  'isPrimary',
  'customizeable',
])

const emit = defineEmits(['sortAsc', 'sortDesc', 'updated'])

const parentEl = useParentElement()

const show = ref(false)

function emitAndClose(event, args) {
  emit(event, args)
  show.value = false
}

function configureMenu({ reference, floating }) {
  function showHeaderMenu(e) {
    if (
      e.target.tagName === 'INPUT' // e.q. toggle all checkbox
    ) {
      return
    }

    e.preventDefault()

    reference.value = {
      getBoundingClientRect() {
        return {
          width: 0,
          height: 0,
          x: e.clientX,
          y: e.clientY,
          top: e.clientY,
          left: e.clientX,
          right: e.clientX,
          bottom: e.clientY,
        }
      },
    }

    show.value = true
  }

  watchEffect(onInvalidate => {
    if (parentEl.value) {
      parentEl.value.addEventListener('click', showHeaderMenu)

      onInvalidate(() =>
        parentEl.value.removeEventListener('click', showHeaderMenu)
      )
    }
  })

  useOutsideClick(
    floating,
    () => {
      show.value = false
    },
    computed(() => show.value)
  )
}
</script>

<template>
  <RadioGroup v-model="model">
    <RadioGroupLabel class="sr-only">Select web form logo type</RadioGroupLabel>

    <div class="grid max-w-lg grid-cols-1 gap-y-6 sm:grid-cols-3 sm:gap-x-4">
      <RadioGroupOption
        v-for="logoType in types"
        :key="logoType.id"
        v-slot="{ checked, active }"
        as="template"
        :value="logoType.value"
      >
        <div
          :class="[
            checked
              ? 'border-transparent'
              : 'border-neutral-300 dark:border-neutral-500/30',
            active ? 'border-primary-600 ring-2 ring-primary-600' : '',
            'relative flex cursor-pointer flex-col rounded-lg border bg-white p-4 shadow-sm focus:outline-none dark:bg-neutral-500/10',
          ]"
        >
          <span class="flex flex-1">
            <RadioGroupLabel
              as="span"
              class="block self-start truncate text-base font-medium text-neutral-900 dark:text-white sm:text-sm"
            >
              {{ logoType.title }}
            </RadioGroupLabel>

            <Icon
              icon="CheckCircle"
              :class="[
                !checked ? 'invisible' : '',
                'ml-auto size-5 text-primary-600',
              ]"
            />
          </span>

          <RadioGroupDescription class="mt-auto">
            <div
              class="mt-2 h-[130px] w-full overflow-hidden p-3"
              :style="{ backgroundColor: backgroundColor }"
            >
              <Icon
                v-if="logoType.value === 'dark'"
                icon="RocketLaunch"
                class="mx-auto mb-2 size-5"
              />

              <Icon
                v-else-if="logoType.value === 'light'"
                icon="RocketLaunch"
                class="mx-auto mb-2 size-5 text-white"
              />

              <div
                class="flex flex-col items-center rounded-md bg-white px-3 py-2"
              >
                <div
                  class="mt-1 h-2 w-full rounded-sm border bg-neutral-50"
                  :style="{
                    borderColor: primaryColor,
                  }"
                />

                <div
                  class="mt-1 h-2 w-full rounded-sm border bg-neutral-50"
                  :style="{
                    borderColor: primaryColor,
                  }"
                />

                <div
                  class="mt-1 h-2 w-full rounded-sm border bg-neutral-50"
                  :style="{
                    borderColor: primaryColor,
                  }"
                />

                <div
                  v-if="logoType.value === null"
                  class="mt-1 h-2 w-full rounded-sm border bg-neutral-50"
                  :style="{
                    borderColor: primaryColor,
                  }"
                />

                <div
                  v-if="logoType.value === null"
                  class="mt-1 h-2 w-full rounded-sm border bg-neutral-50"
                  :style="{
                    borderColor: primaryColor,
                  }"
                />

                <div
                  class="mt-3 h-3 w-full rounded"
                  :style="{
                    backgroundColor: primaryColor,
                  }"
                />
              </div>
            </div>
          </RadioGroupDescription>

          <span
            aria-hidden="true"
            :class="[
              active ? 'border' : 'border-2',
              checked ? 'border-primary-600' : 'border-transparent',
              'pointer-events-none absolute -inset-px rounded-lg',
            ]"
          />
        </div>
      </RadioGroupOption>
    </div>
  </RadioGroup>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import {
  RadioGroup,
  RadioGroupDescription,
  RadioGroupLabel,
  RadioGroupOption,
} from '@headlessui/vue'

defineProps({
  modelValue: String,
  backgroundColor: String,
  primaryColor: String,
})

const model = defineModel()

const { t } = useI18n()

const types = [
  {
    id: 'form_no_logo',
    title: t('core::app.no'),
    value: null,
  },
  {
    id: 'form_logo_light',
    value: 'light',
    title: t('core::app.logo.light'),
  },
  {
    id: 'form_logo_dark',
    value: 'dark',
    title: t('core::app.logo.dark'),
  },
]
</script>

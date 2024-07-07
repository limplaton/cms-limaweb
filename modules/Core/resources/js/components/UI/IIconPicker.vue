<template>
  <RadioGroup
    :model-value="modelValue"
    :disabled="disabled"
    @update:model-value="pickIcon"
  >
    <div class="flex flex-wrap gap-x-1.5 gap-y-2" v-bind="$attrs">
      <RadioGroupOption
        v-for="icon in icons"
        :key="icon[valueField]"
        v-slot="{ checked }"
        as="template"
        :name="name"
        :value="icon[valueField]"
      >
        <div
          v-i-tooltip="icon.tooltip"
          :class="[
            'flex cursor-pointer items-center justify-center rounded-lg text-neutral-700 ring-1 ring-neutral-300 dark:text-white dark:ring-neutral-500/30',
            'px-2.5 py-2',
            disabled ? 'pointer-events-none opacity-50' : '',
            checked
              ? 'bg-neutral-50 dark:bg-neutral-500/40'
              : 'bg-white hover:bg-neutral-50 dark:bg-neutral-500/10 dark:hover:bg-neutral-500/40',
          ]"
        >
          <RadioGroupLabel as="span">
            <Icon class="size-5" :icon="icon.icon" />
          </RadioGroupLabel>
        </div>
      </RadioGroupOption>
    </div>
  </RadioGroup>
</template>

<script setup>
import { ref, watch } from 'vue'
import { RadioGroup, RadioGroupLabel, RadioGroupOption } from '@headlessui/vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  disabled: Boolean,
  valueField: { type: String, default: 'icon' },
  name: String,
  icons: {
    type: Array,
    default: function () {
      return [
        'Mail',
        'PencilAlt',
        'OfficeBuilding',
        'Phone',
        'Calendar',
        'Collection',
        'Bell',
        'AtSymbol',
        'Briefcase',
        'Chat',
        'CheckCircle',
        'BookOpen',
        'Camera',
        'Truck',
        'Folder',
        'DeviceMobile',
        'Users',
        'CreditCard',
        'Clock',
        'ShieldExclamation',
        'WrenchScrewdriver',
        'ShoppingBag',
        'Film',
        'Gift',
        'Inbox',
        'Key',
        'LockClosed',
        'PaintBrush',
        'Bookmark',
        'AcademicCap',
        'ArchiveBox',
        'BugAnt',
        'CodeBracket',
      ].map(icon => ({ icon, tooltip: null }))
    },
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const selected = ref(props.modelValue)

function pickIcon(icon) {
  selected.value = icon
  updateModelValue(icon)
}

function updateModelValue(value) {
  emit('update:modelValue', value)
  emit('change', value)
}

watch(() => props.modelValue, pickIcon)
</script>

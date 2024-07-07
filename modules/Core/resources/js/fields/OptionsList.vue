<template>
  <div v-if="totalOptions > 0" :class="eachOnNewLine ? 'space-y-0.5' : ''">
    <component
      :is="onOptionClick ? ILink : 'span'"
      v-for="(option, index) in options"
      :key="index"
      :class="eachOnNewLine ? 'block' : 'mr-1.5 last:mr-0'"
      :plain="onOptionClick && !displayAsBadges ? false : undefined"
      :to="
        onOptionClick && onOptionClick.action === 'redirect'
          ? onOptionClick.to.replace('{id}', option.id)
          : undefined
      "
      @click="
        onOptionClick && onOptionClick.action === 'float'
          ? floatResource({
              resourceName: onOptionClick.resourceName,
              resourceId: option.id,
              mode: onOptionClick.mode || 'detail',
            })
          : undefined
      "
    >
      <component
        :is="displayAsBadges ? IBadge : 'span'"
        :variant="
          displayAsBadges && !option.swatch_color ? 'neutral' : undefined
        "
        :icon="option.icon && displayAsBadges ? option.icon : undefined"
        :color="
          displayAsBadges && option.swatch_color
            ? option.swatch_color
            : undefined
        "
      >
        {{ getOptionLabel(option) }}
      </component>
    </component>
  </div>

  <span v-else>&mdash;</span>
</template>

<script setup>
import { computed } from 'vue'
import castArray from 'lodash/castArray'

import { IBadge } from '../components/UI/Badge'
import ILink from '../components/UI/ILink.vue'
import { useFloatingResourceModal } from '../composables/useFloatingResourceModal'

const props = defineProps([
  'value',
  'displayAsBadges',
  'eachOnNewLine',
  'onOptionClick',
  'list',
  'labelKey',
  'valueKey',
])

const { floatResource } = useFloatingResourceModal()

const options = computed(() => (props.value ? castArray(props.value) : []))
const totalOptions = computed(() => options.value.length)

function getOptionLabel(option) {
  if (typeof option === 'string') {
    if (!props.list) {
      return option
    }

    let selectedOptionObject = props.list.find(
      o => o[props.valueKey] === option
    )

    return selectedOptionObject ? selectedOptionObject[props.labelKey] : option
  }

  return option[props.labelKey]
}
</script>

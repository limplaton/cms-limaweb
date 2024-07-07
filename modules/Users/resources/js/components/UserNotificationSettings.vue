<template>
  <ITable grid>
    <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
      <ITableRow>
        <ITableHeader
          v-for="channel in allAvailableChannels"
          :key="'heading-' + channel"
          v-i-tooltip="$t('core::notifications.channels.' + channel)"
          class="text-center"
          width="8%"
        >
          <Icon class="inline size-5" :icon="iconMaps[channel]" />
        </ITableHeader>

        <ITableHeader class="text-left" width="92%">
          {{ $t('core::notifications.notification') }}
        </ITableHeader>
      </ITableRow>
    </ITableHead>

    <ITableBody>
      <ITableRow v-for="notification in settings" :key="notification.key">
        <ITableCell
          v-for="channel in allAvailableChannels"
          :key="channel"
          class="text-center"
        >
          <IFormCheckbox
            v-if="notification.channels.indexOf(channel) > -1"
            :checked="modelValue[notification.key][channel]"
            @update:checked="
              $emit('update:modelValue', {
                ...modelValue,
                ...{
                  [notification.key]: {
                    ...modelValue[notification.key],
                    [channel]: $event,
                  },
                },
              })
            "
          />

          <Icon v-else class="inline size-5 text-neutral-500" icon="XSolid" />
        </ITableCell>

        <ITableCell class="space-y-0.5">
          <p v-text="notification.name" />

          <IText
            v-show="notification.description"
            :text="notification.description"
          />
        </ITableCell>
      </ITableRow>
    </ITableBody>
  </ITable>
</template>

<script setup>
import flatten from 'lodash/flatten'
import map from 'lodash/map'
import uniq from 'lodash/uniq'

import { useApp } from '@/Core/composables/useApp'

defineProps({
  modelValue: { required: true, type: Object },
})

defineEmits(['update:modelValue'])

const { scriptConfig } = useApp()

const settings = scriptConfig('notifications_settings')

const iconMaps = {
  mail: 'Mail',
  database: 'Bell',
}

const allAvailableChannels = uniq(flatten(map(settings, 'channels')))
</script>

<template>
  <ICardHeader>
    <ICardHeading :text="$t('core::settings.tools.tools')" />
  </ICardHeader>

  <ICard>
    <ul class="divide-y divide-neutral-200 dark:divide-neutral-500/30">
      <li v-for="(data, tool) in tools" :key="tool" class="px-4 py-4 sm:px-6">
        <div
          class="flex flex-col space-y-2 sm:flex-row sm:items-center sm:space-y-0"
        >
          <div class="grow">
            <ITextDark class="font-medium" :text="tool" />

            <IText :text="data.description" />
          </div>

          <div class="shrink-0">
            <IButton
              variant="secondary"
              :loading="toolBeingExecuted === tool"
              :disabled="toolBeingExecuted !== null"
              :text="$t('core::settings.tools.run')"
              small
              @click="run(tool)"
            />
          </div>
        </div>
      </li>
    </ul>
  </ICard>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useApp } from '@/Core/composables/useApp'

const { t } = useI18n()
const { scriptConfig } = useApp()

const tools = scriptConfig('tools')

const toolBeingExecuted = ref(null)

async function run(tool) {
  toolBeingExecuted.value = tool

  try {
    await Innoclapps.request().post(`/tools/${tool}`)
    Innoclapps.success(t('core::settings.tools.executed'))
    setTimeout(() => window.location.reload(true), 1000)
  } finally {
    toolBeingExecuted.value = null
  }
}
</script>

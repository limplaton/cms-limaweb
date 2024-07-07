<template>
  <div class="space-y-8">
    <div>
      <ICardHeader>
        <ICardHeading :text="$t('documents::document.documents')" />
      </ICardHeader>

      <ICard :overlay="!componentReady">
        <ul class="divide-y divide-neutral-200 dark:divide-neutral-700">
          <li class="px-4 py-4 sm:px-6">
            <IFormGroup
              class="mb-0"
              label-for="default_document_type"
              :label="$t('documents::document.type.default_type')"
            >
              <ICustomSelect
                v-model="defaultType"
                input-id="default_document_type"
                class="xl:w-1/3"
                label="name"
                :clearable="false"
                :options="documentTypes"
                @option-selected="handleDocumentTypeInputEvent"
              >
              </ICustomSelect>
            </IFormGroup>
          </li>
        </ul>
      </ICard>
    </div>

    <div>
      <DocumentsTypeIndex />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { watchOnce } from '@vueuse/core'

import { useApp } from '@/Core/composables/useApp'
import { useSettings } from '@/Core/composables/useSettings'

import { useDocumentTypes } from '../composables/useDocumentTypes'
import DocumentsTypeIndex from '../views/DocumentsTypeIndex.vue'

const { resetStoreState } = useApp()
const { form, submit, isReady: componentReady } = useSettings()

const defaultType = ref(null)

const { typesByName: documentTypes } = useDocumentTypes()

function handleDocumentTypeInputEvent(e) {
  form.default_document_type = e.id
  submit(resetStoreState)
}

watchOnce(componentReady, () => {
  defaultType.value = documentTypes.value.find(
    type => type.id == form.default_document_type
  )
})
</script>

<template>
  <ICardHeader>
    <ICardHeading :text="$t('core::app.system_info')" />

    <ICardActions>
      <IButton
        icon="DocumentDownload"
        variant="secondary"
        small
        @click="download"
      />
    </ICardActions>
  </ICardHeader>

  <ICard>
    <div class="px-6">
      <ITable class="[--gutter:theme(spacing.6)]" bleed grid condensed>
        <ITableBody>
          <ITableRow v-for="(value, variableName) in info" :key="variableName">
            <ITableCell class="font-medium">
              {{ variableName }}
            </ITableCell>

            <ITableCell class="whitespace-break-spaces">
              <code>
                {{ value }}
              </code>
            </ITableCell>
          </ITableRow>
        </ITableBody>
      </ITable>
    </div>
  </ICard>
</template>

<script setup>
import { ref } from 'vue'
import FileDownload from 'js-file-download'

const info = ref({})

function retrieve() {
  Innoclapps.request('/system/info').then(({ data }) => (info.value = data))
}

function download() {
  Innoclapps.request()
    .post(
      '/system/info',
      {},
      {
        responseType: 'blob',
      }
    )
    .then(response => {
      FileDownload(response.data, 'system-info.xlsx')
    })
}

retrieve()
</script>

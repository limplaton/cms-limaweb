<template>
  <IAlert class="mb-6">
    <IAlertBody>
      {{
        $t('core::app.soft_deletes.trashed_pruning_info', {
          total: $scriptConfig('soft_deletes.prune_after'),
        })
      }}
    </IAlertBody>
  </IAlert>

  <ResourceTable
    :url-path="`/${resourceName}/table`"
    :table-id="tableId"
    :data-request-query-string="{ trashed: 1 }"
    :action-request-params="{ trashed: 1 }"
    :resource-name="resourceName"
  >
    <template #header="{ isPreEmpty }">
      <form
        v-show="!isPreEmpty"
        class="flex-1 text-right"
        @submit.prevent="emptyTrash"
      >
        <IButton
          type="submit"
          variant="danger"
          icon="Trash"
          :disabled="trashBeingEmptied"
          :loading="trashBeingEmptied"
          :text="$t('core::app.soft_deletes.empty_trash')"
          soft
        />
      </form>
    </template>
  </ResourceTable>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useTable } from '@/Core/composables/useTable'

const props = defineProps({
  resourceName: { required: true, type: String },
})

const { t } = useI18n()

const tableId = ref(`${props.resourceName}-trashed`)
const trashBeingEmptied = ref(false)

const { reloadTable } = useTable(tableId)

async function emptyTrash() {
  await Innoclapps.confirm()

  trashBeingEmptied.value = true

  // First, we will make a get request to the trashed index query
  // to retrieve the total available records the user is able to see
  // so we can properly calculate how much requests we need to make.
  const { data } = await Innoclapps.request(`/trashed/${props.resourceName}`)

  let totalDeleted = 0
  const totalRecords = data.meta.total
  const recordsPerRequest = 500
  const totalRequests = Math.ceil(totalRecords / recordsPerRequest)

  const performEmptyTrash = async () => {
    // While development encountered server errors, it's good to have it here.
    let serverError = false

    try {
      for (let i = 1; i <= totalRequests; i++) {
        try {
          const { data } = await Innoclapps.request().delete(
            `/trashed/${props.resourceName}?limit=${recordsPerRequest}`
          )

          totalDeleted = totalDeleted + data.deleted
        } catch {
          serverError = true
          break
        }
      }

      if (totalDeleted === 0 && !serverError) {
        Innoclapps.error(t('core::app.action_not_authorized'))
      } else {
        reloadTable()
      }
    } finally {
      trashBeingEmptied.value = false
    }
  }

  performEmptyTrash()
}
</script>

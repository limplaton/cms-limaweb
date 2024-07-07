<template>
  <ICardHeader>
    <ICardHeading :text="$t('deals::deal.pipeline.pipelines')" />

    <ICardActions>
      <IButton
        variant="primary"
        icon="PlusSolid"
        :to="{ name: 'create-pipeline' }"
        :text="$t('deals::deal.pipeline.create')"
      />
    </ICardActions>
  </ICardHeader>

  <ICard>
    <div class="px-6">
      <ITable class="[--gutter:theme(spacing.6)]" bleed>
        <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
          <ITableRow>
            <ITableHeader class="text-center" width="5%">
              {{ $t('core::app.id') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('deals::deal.pipeline.pipeline') }}
            </ITableHeader>

            <ITableHeader width="8%" />
          </ITableRow>
        </ITableHead>

        <SortableDraggable
          v-bind="$draggable.common"
          v-model="pipelines"
          item-key="id"
          tag="tbody"
        >
          <template #item="{ element: pipeline }">
            <ITableRow>
              <ITableCell class="text-center">
                {{ pipeline.id }}
              </ITableCell>

              <ITableCell>
                <ILink
                  class="font-medium"
                  :to="{ name: 'edit-pipeline', params: { id: pipeline.id } }"
                  :text="pipeline.name"
                />
              </ITableCell>

              <ITableCell>
                <ITableRowActions>
                  <ITableRowAction
                    icon="PencilAlt"
                    :to="{
                      name: 'edit-pipeline',
                      params: { id: pipeline.id },
                    }"
                    :text="$t('core::app.edit')"
                  />

                  <ITableRowAction
                    icon="Trash"
                    :text="$t('core::app.delete')"
                    @click="$confirm(() => destroy(pipeline.id))"
                  />
                </ITableRowActions>
              </ITableCell>
            </ITableRow>
          </template>
        </SortableDraggable>

        <tbody></tbody>
      </ITable>
    </div>
  </ICard>

  <RouterView />
</template>

<script setup>
import { useI18n } from 'vue-i18n'

import { usePipelines } from '../composables/usePipelines'

const { t } = useI18n()

const { orderedPipelinesForDraggable: pipelines, deletePipeline } =
  usePipelines()

async function destroy(id) {
  await deletePipeline(id)

  Innoclapps.success(t('deals::deal.pipeline.deleted'))
}
</script>

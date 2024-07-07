<template>
  <ICardHeader>
    <ICardHeading :text="$t('core::role.roles')" />

    <ICardActions>
      <IButton
        v-show="hasRoles"
        variant="primary"
        icon="PlusSolid"
        :to="{ name: 'create-role' }"
        :text="$t('core::role.create')"
      />
    </ICardActions>
  </ICardHeader>

  <ICard :overlay="rolesAreBeingFetched">
    <div v-if="hasRoles" class="px-6">
      <ITable class="[--gutter:theme(spacing.6)]" bleed>
        <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
          <ITableRow>
            <ITableHeader width="5%">
              {{ $t('core::app.id') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('core::role.name') }}
            </ITableHeader>

            <ITableHeader width="8%" />
          </ITableRow>
        </ITableHead>

        <ITableBody>
          <ITableRow v-for="role in rolesByName" :key="role.id">
            <ITableCell>
              {{ role.id }}
            </ITableCell>

            <ITableCell>
              <ILink
                class="font-medium"
                :to="{ name: 'edit-role', params: { id: role.id } }"
                :text="role.name"
              />
            </ITableCell>

            <ITableCell>
              <ITableRowActions>
                <ITableRowAction
                  :to="{ name: 'edit-role', params: { id: role.id } }"
                  :text="$t('core::app.edit')"
                />

                <ITableRowAction
                  :text="$t('core::app.delete')"
                  @click="$confirm(() => destroy(role.id))"
                />
              </ITableRowActions>
            </ITableCell>
          </ITableRow>
        </ITableBody>
      </ITable>
    </div>

    <ICardBody v-else-if="!rolesAreBeingFetched">
      <IEmptyState
        :to="{ name: 'create-role' }"
        :button-text="$t('core::role.create')"
        :title="$t('core::role.empty_state.title')"
        :description="$t('core::role.empty_state.description')"
      />
    </ICardBody>
  </ICard>

  <RouterView />
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { useRoles } from '../../composables/useRoles'

const { t } = useI18n()

const { rolesByName, rolesAreBeingFetched, deleteRole } = useRoles()

const hasRoles = computed(() => rolesByName.value.length > 0)

async function destroy(id) {
  await deleteRole(id)

  Innoclapps.success(t('core::role.deleted'))
}
</script>

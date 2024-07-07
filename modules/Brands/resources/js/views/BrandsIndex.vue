<template>
  <ICardHeader>
    <ICardHeading :text="$t('brands::brand.brands')" />

    <ICardActions>
      <IButton
        variant="primary"
        icon="PlusSolid"
        :to="{ name: 'create-brand' }"
        :text="$t('brands::brand.create')"
      />
    </ICardActions>
  </ICardHeader>

  <ICard :overlay="brandsAreBeingFetched">
    <div class="px-6">
      <ITable class="[--gutter:theme(spacing.6)]" bleed>
        <ITableHead class="bg-neutral-50 dark:bg-neutral-500/10">
          <ITableRow>
            <ITableHeader width="5%">
              {{ $t('core::app.id') }}
            </ITableHeader>

            <ITableHeader>
              {{ $t('brands::brand.brand') }}
            </ITableHeader>

            <ITableHeader width="8%" />
          </ITableRow>
        </ITableHead>

        <ITableBody>
          <ITableRow v-for="brand in brands" :key="brand.id">
            <ITableCell>
              {{ brand.id }}
            </ITableCell>

            <ITableCell>
              <ILink
                class="font-medium"
                :to="{ name: 'edit-brand', params: { id: brand.id } }"
                :text="brand.name"
              />

              <IBadge
                v-if="brand.is_default"
                class="ml-2"
                variant="primary"
                :text="$t('core::app.is_default')"
              />
            </ITableCell>

            <ITableCell>
              <ITableRowActions>
                <ITableRowAction
                  icon="PencilAlt"
                  :to="{ name: 'edit-brand', params: { id: brand.id } }"
                  :text="$t('core::app.edit')"
                />

                <ITableRowAction
                  icon="Trash"
                  :text="$t('core::app.delete')"
                  @click="$confirm(() => deleteBrand(brand.id))"
                />
              </ITableRowActions>
            </ITableCell>
          </ITableRow>
        </ITableBody>
      </ITable>
    </div>
  </ICard>
</template>

<script setup>
import { useBrands } from '../composables/useBrands'

const {
  orderedBrands: brands,
  brandsAreBeingFetched,
  deleteBrand,
} = useBrands()
</script>

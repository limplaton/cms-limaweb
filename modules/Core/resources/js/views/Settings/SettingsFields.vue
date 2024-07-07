<template>
  <div>
    <CustomFieldsCreate
      v-model:visible="customFieldBeingCreated"
      :resource-name="resourceName"
      @created="refreshFieldsData"
    />

    <CustomFieldsEdit
      v-if="customFieldBeingUpdated !== null"
      :visible="customFieldBeingUpdated !== null"
      :custom-field-id="customFieldBeingUpdated.id"
      :label="customFieldBeingUpdated.label"
      :field-type="customFieldBeingUpdated.field_type"
      :field-id="customFieldBeingUpdated.field_id"
      :resource-name="customFieldBeingUpdated.resource_name"
      :options="customFieldBeingUpdated.options"
      :is-unique="customFieldBeingUpdated.is_unique"
      @updated="refreshFieldsData"
      @hidden="customFieldBeingUpdated = null"
    />

    <div
      v-show="!editingRelatedFieldResource"
      class="mb-3 flex items-center justify-between"
    >
      <ITextDisplay :text="title" />

      <IButton
        variant="primary"
        icon="PlusSolid"
        class="ml-3"
        :text="$t('core::fields.add')"
        @click="customFieldBeingCreated = true"
      />
    </div>

    <div v-show="!editingRelatedFieldResource">
      <SettingsFieldsCustomize
        v-if="resource.hasDetailView"
        ref="detailRef"
        class="mb-3"
        :group="resourceName"
        :view="$scriptConfig('fields.views.detail')"
        :heading="$t('core::fields.settings.detail')"
        :sub-heading="$t('core::fields.settings.detail_info')"
        @delete-requested="deleteCustomField"
        @update-requested="handleUpdateRequestedEvent"
        @saved="refreshFieldsData"
      />

      <SettingsFieldsCustomize
        ref="createRef"
        class="mb-3"
        :group="resourceName"
        :view="$scriptConfig('fields.views.create')"
        :heading="$t('core::fields.settings.create')"
        :sub-heading="$t('core::fields.settings.create_info')"
        :collapse-option="false"
        @delete-requested="deleteCustomField"
        @update-requested="handleUpdateRequestedEvent"
        @saved="refreshFieldsData"
      />

      <SettingsFieldsCustomize
        ref="updateRef"
        class="mb-3"
        :group="resourceName"
        :view="$scriptConfig('fields.views.update')"
        :heading="$t('core::fields.settings.update')"
        :sub-heading="$t('core::fields.settings.update_info')"
        @delete-requested="deleteCustomField"
        @update-requested="handleUpdateRequestedEvent"
        @saved="refreshFieldsData"
      />

      <ICard>
        <ICardHeader>
          <ICardHeading :text="$t('core::fields.settings.list')" />
        </ICardHeader>

        <ICardBody>
          <ITextBlock>
            <I18nT scope="global" keypath="core::fields.settings.list_info">
              <template #icon>
                <Icon icon="DotsHorizontal" class="mx-1 inline size-5" />
              </template>

              <template #resourceName>
                {{ resource.label }}
              </template>
            </I18nT>
          </ITextBlock>
        </ICardBody>
      </ICard>
    </div>

    <SimpleResourceCRUD
      v-if="editingRelatedFieldResource"
      :resource-name="editingRelatedFieldResource"
      @created="refreshFieldsData"
      @updated="refreshFieldsData"
      @deleted="refreshFieldsData"
      @cancel="editingRelatedFieldResource = null"
    />
  </div>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useStore } from 'vuex'

import CustomFieldsCreate from '@/Core/components/CustomFields/CustomFieldsCreate.vue'
import CustomFieldsEdit from '@/Core/components/CustomFields/CustomFieldsEdit.vue'
import SimpleResourceCRUD from '@/Core/components/SimpleResourceCRUD.vue'
import { useApp } from '@/Core/composables/useApp'
import { usePageTitle } from '@/Core/composables/usePageTitle'

import SettingsFieldsCustomize from './SettingsFieldsCustomize.vue'

const store = useStore()
const route = useRoute()
const { t } = useI18n()
const { scriptConfig } = useApp()

const customFieldBeingCreated = ref(false)
const customFieldBeingUpdated = ref(null)

const detailRef = ref(null)
const createRef = ref(null)
const updateRef = ref(null)

const editingRelatedFieldResource = ref(null)

const resourceName = computed(() => route.params.resourceName)

const resource = computed(() => scriptConfig(`resources.${resourceName.value}`))

const title = computed(() => {
  return resource.value
    ? t('core::resource.settings.fields', {
        resourceName: resource.value.singularLabel,
      })
    : null
})

usePageTitle(title)

// In case the user is editing some field data
// to show the fields again, we need to set the editingRelatedFieldResource to null
watch(resourceName, () => {
  editingRelatedFieldResource.value = null
})

function handleUpdateRequestedEvent(field) {
  if (!field.customField) {
    editingRelatedFieldResource.value = field.optionsViaResource
  } else {
    customFieldBeingUpdated.value = field.customField
  }
}

async function deleteCustomField(id) {
  await Innoclapps.confirm()

  Innoclapps.request()
    .delete(`/custom-fields/${id}`)
    .then(() => {
      refreshFieldsData()
      Innoclapps.success(t('core::fields.custom.deleted'))
    })
}

function refreshFieldsData() {
  store.commit('table/RESET_SETTINGS')

  store.dispatch('filters/clearActive', {
    identifier: resourceName.value,
    view: resourceName.value,
  })

  const fieldsRefs = [createRef, updateRef, detailRef].filter(
    ref => ref.value !== null
  )

  Promise.all(fieldsRefs.map(ref => ref.value.fetch())).then(() => {
    nextTick(() => fieldsRefs.forEach(ref => ref.value.submit()))
  })
}
</script>

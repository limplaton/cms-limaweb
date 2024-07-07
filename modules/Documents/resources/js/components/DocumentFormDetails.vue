<template>
  <div v-show="visible" class="mx-auto max-w-3xl">
    <div class="relative">
      <ITabGroup>
        <ITabList>
          <ITab class="sm:-ml-3.5">
            <Icon icon="AdjustmentsVertical" />
            {{ $t('documents::document.document_details') }}
          </ITab>

          <ITab :disabled="Object.keys(document).length === 0">
            <Icon icon="DocumentText" />

            {{ $t('documents::document.document_activity') }}

            <IBadge
              v-show="changelog.length"
              variant="neutral"
              :text="changelog.length"
              pill
            />
          </ITab>
        </ITabList>

        <ITabPanels>
          <ITabPanel>
            <IAlert v-if="document.status === 'accepted'" class="mb-6">
              <IAlertBody>
                {{ $t('documents::document.limited_editing') }}
              </IAlertBody>
            </IAlert>

            <div
              class="mb-4 mt-3 flex items-center md:absolute md:right-1 md:top-3.5 md:my-0 md:space-x-2.5"
            >
              <div
                class="order-last ml-auto mr-0 place-self-end md:-order-none md:mr-2.5"
              >
                <slot name="actions" />
              </div>

              <IBadge
                v-if="selectedDocumentType"
                class="mr-2.5 md:mr-0"
                :color="selectedDocumentType.swatch_color"
                :icon="selectedDocumentType.icon"
              >
                <span
                  class="max-w-[90px] truncate"
                  v-text="selectedDocumentType.name"
                />
              </IBadge>

              <IBadge
                class="mr-2.5 md:mr-0"
                :color="
                  document.status
                    ? statuses[document.status].color
                    : statuses.draft.color
                "
                :text="
                  document.status
                    ? statuses[document.status].display_name
                    : statuses.draft.display_name
                "
              />
            </div>

            <slot name="top" />

            <div class="sm:grid sm:grid-cols-12 sm:gap-x-4">
              <IFormGroup
                class="sm:col-span-6"
                label-for="brand_id"
                :label="$t('brands::brand.brand')"
                required
              >
                <ICustomSelect
                  v-model="selectedBrand"
                  input-id="brand_id"
                  label="name"
                  :options="brands"
                  :clearable="false"
                  :disabled="document.status === 'accepted'"
                  @update:model-value="form.brand_id = $event.id"
                />

                <IFormError :error="form.getError('brand_id')" />
              </IFormGroup>

              <IFormGroup
                class="sm:col-span-6"
                label-for="document_type_id"
                :label="$t('documents::document.type.type')"
                required
              >
                <ICustomSelect
                  v-model="selectedDocumentType"
                  input-id="document_type_id"
                  label="name"
                  :options="documentTypes"
                  :clearable="false"
                  @update:model-value="form.document_type_id = $event.id"
                />

                <IFormError :error="form.getError('document_type_id')" />
              </IFormGroup>
            </div>

            <IFormGroup
              label-for="user_id"
              :label="$t('documents::fields.documents.user.name')"
              required
            >
              <ICustomSelect
                v-model="selectedUser"
                label="name"
                input-id="user_id"
                :clearable="false"
                :options="users"
                :disabled="document.status === 'accepted'"
                @update:model-value="form.user_id = $event ? $event.id : null"
              />

              <IFormError :error="form.getError('user_id')" />
            </IFormGroup>

            <IFormGroup
              label-for="title"
              :label="$t('documents::document.title')"
              required
            >
              <IFormInput
                id="title"
                v-model="form.title"
                :disabled="document.status === 'accepted'"
              />

              <IFormError :error="form.getError('title')" />
            </IFormGroup>

            <IFormGroup
              label-for="locale"
              :label="$t('core::app.locale')"
              required
            >
              <ICustomSelect
                v-model="form.locale"
                input-id="locale"
                :clearable="false"
                :options="locales"
              />

              <IFormError :error="form.getError('locale')" />
            </IFormGroup>

            <IFormLabel
              as="p"
              class="my-2"
              :label="$t('documents::document.view_type.html_view_type')"
            />

            <FormViewTypes v-model="form.view_type" />

            <IFormError :error="form.getError('view_type')" />
          </ITabPanel>

          <ITabPanel>
            <ul role="list" class="space-y-6">
              <li
                v-for="(log, idx) in changelog"
                :key="log.id"
                class="relative flex gap-x-4"
              >
                <div
                  :class="[
                    idx === changelog.length - 1 ? 'h-6' : '-bottom-6',
                    'absolute left-0 top-0 flex w-6 justify-center',
                  ]"
                >
                  <div class="w-px bg-neutral-200 dark:bg-neutral-500" />
                </div>

                <div
                  class="relative flex size-6 flex-none items-center justify-center bg-white dark:bg-neutral-900"
                >
                  <div
                    class="h-1.5 w-1.5 rounded-full ring-1"
                    :class="
                      log.properties.type === 'success'
                        ? 'bg-success-200 ring-success-500 dark:bg-success-400'
                        : 'bg-neutral-100 ring-neutral-300 dark:bg-neutral-300'
                    "
                  />
                </div>

                <template v-if="log.properties.section">
                  <div
                    class="flex-auto rounded-md p-3 ring-1 ring-inset ring-neutral-200 dark:ring-neutral-700"
                  >
                    <div class="flex justify-between gap-x-4">
                      <IText
                        class="py-0.5"
                        :text="
                          $t(log.properties.lang.key, log.properties.lang.attrs)
                        "
                      />

                      <ITextSmall
                        as="time"
                        class="flex-none py-0.5"
                        :datetime="log.dateTime"
                        :text="localizedDateTime(log.created_at)"
                      />
                    </div>

                    <div class="mt-1.5 py-0.5">
                      <ITextDark class="font-medium">
                        {{
                          $t(
                            log.properties.section.lang.key,
                            log.properties.section.lang.attrs || {}
                          )
                        }}
                      </ITextDark>

                      <ul class="flex-none">
                        <li
                          v-for="(data, sIdx) in log.properties.section.list"
                          :key="sIdx"
                        >
                          <IText>
                            {{ $t(data.lang.key, data.lang.attrs || {}) }}
                          </IText>
                        </li>
                      </ul>
                    </div>
                  </div>
                </template>

                <template v-else>
                  <IText
                    class="flex-auto py-0.5"
                    :text="
                      $t(log.properties.lang.key, log.properties.lang.attrs)
                    "
                  />

                  <ITextSmall
                    class="flex-none py-0.5"
                    as="time"
                    :datetime="log.dateTime"
                    :text="localizedDateTime(log.created_at)"
                  />
                </template>
              </li>
            </ul>
          </ITabPanel>
        </ITabPanels>
      </ITabGroup>
    </div>
  </div>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import find from 'lodash/find'

import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'

import { useDocumentTypes } from '../composables/useDocumentTypes'

import FormViewTypes from './DocumentFormViewTypes.vue'
import propsDefinition from './formSectionProps'

const props = defineProps(propsDefinition)

const { localizedDateTime } = useDates()
const { scriptConfig, users, currentUser, locales } = useApp()
const { typesByName: documentTypes } = useDocumentTypes()

const statuses = scriptConfig('documents.statuses')
const selectedUser = ref(null)
const selectedDocumentType = ref(null)
const selectedBrand = ref(null)

const brands = inject('brands')

const changelog = computed(() => {
  if (!props.document.changelog) {
    return []
  }

  return props.document.changelog.slice().reverse()
})

function prepareComponent() {
  if (Object.keys(props.document).length === 0) {
    selectedBrand.value = find(brands.value, brand => brand.is_default)

    if (selectedBrand.value) {
      props.form.set('brand_id', selectedBrand.value.id)
    }

    selectedDocumentType.value = find(documentTypes.value, [
      'id',
      parseInt(scriptConfig('documents.default_document_type')),
    ])

    if (selectedDocumentType.value) {
      props.form.set('document_type_id', selectedDocumentType.value.id)
    }

    selectedUser.value = currentUser.value
    props.form.set('user_id', selectedUser.value.id)
  } else {
    selectedBrand.value = props.document.brand
    selectedDocumentType.value = props.document.type
    selectedUser.value = props.document.user
  }
}

prepareComponent()
</script>

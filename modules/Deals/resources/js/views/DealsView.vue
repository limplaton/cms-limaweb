<template>
  <MainLayout :overlay="!componentReady">
    <div class="mx-auto max-w-7xl">
      <IAlert
        v-if="componentReady && !resource.authorizations.view"
        class="mb-6"
        variant="warning"
      >
        <IAlertBody>
          {{ $t('core::role.view_non_authorized_after_record_create') }}
        </IAlertBody>
      </IAlert>

      <div v-if="componentReady" class="relative">
        <ICard>
          <div class="px-3 py-4 sm:p-6">
            <div class="flex grow flex-col lg:flex-row lg:items-center">
              <div
                class="overflow-hidden text-center lg:flex lg:grow lg:items-center lg:space-x-4 lg:text-left"
              >
                <div class="overflow-hidden">
                  <div
                    class="flex flex-col items-center space-y-1 lg:flex-row lg:space-x-4 lg:space-y-0"
                  >
                    <IPopover
                      v-slot="{ hide }"
                      @show="nameForm.name = resource.name"
                      @hide="nameForm.errors.clear()"
                    >
                      <IPopoverButton
                        as="button"
                        class="relative rounded-md text-2xl font-bold text-neutral-900 hover:bg-neutral-100 focus:outline-none dark:text-white dark:hover:bg-neutral-800 lg:truncate"
                        :text="resource.display_name"
                        :disabled="!resource.authorizations.update"
                      />

                      <IPopoverPanel class="w-80">
                        <form @submit.prevent="updateName">
                          <IPopoverBody>
                            <IFormGroup
                              label-for="editDealName"
                              :label="$t('deals::fields.deals.name')"
                              required
                            >
                              <component
                                :is="
                                  resource.name.length <= 60
                                    ? 'IFormInput'
                                    : 'IFormTextarea'
                                "
                                id="editDealName"
                                v-model="nameForm.name"
                              />

                              <IFormError :error="nameForm.getError('name')" />
                            </IFormGroup>
                          </IPopoverBody>

                          <IPopoverFooter class="flex justify-end space-x-1">
                            <IButton
                              :disabled="nameForm.busy"
                              :text="$t('core::app.cancel')"
                              basic
                              @click="hide"
                            />

                            <IButton
                              variant="primary"
                              :loading="nameForm.busy"
                              :disabled="nameForm.busy || !nameForm.name"
                              :text="$t('core::app.save')"
                              @click="updateName().then(hide)"
                            />
                          </IPopoverFooter>
                        </form>
                      </IPopoverPanel>
                    </IPopover>

                    <div class="shrink-0">
                      <TagsSelectInput
                        :disabled="!resource.authorizations.update"
                        :type="$scriptConfig('deals.tags_type')"
                        :model-value="resource.tags"
                        simple
                        @update:model-value="updateResource({ tags: $event })"
                      />
                    </div>
                  </div>

                  <BillableFormProductsModal
                    v-if="resource.authorizations.update"
                    :resource-name="resourceName"
                    :resource-id="resource.id"
                    :visible="manageProducts"
                    prefetch
                    @hidden="manageProducts = false"
                    @saved="
                      synchronizeResource({
                        _sync_timestamp: new Date(),
                        billable: $event,
                        amount: $event.total,
                        products_count: $event.products.length,
                      })
                    "
                  />

                  <div
                    class="my-1 flex flex-col items-center justify-center space-y-2 lg:mb-1 lg:flex-row lg:justify-start lg:space-x-3 lg:space-y-0"
                  >
                    <ITextBlock class="order-2 shrink-0 space-x-1 sm:order-1">
                      <ILink
                        v-if="resource.authorizations.update"
                        :text="
                          $t('billable::product.count', {
                            count: resource.products_count,
                          })
                        "
                        @click="manageProducts = true"
                      />

                      <span
                        v-else
                        v-t="{
                          path: 'billable::product.count',
                          args: { count: resource.products_count },
                        }"
                      />

                      <span
                        v-if="Object.hasOwn(resource, 'amount')"
                        class="font-medium text-neutral-700 dark:text-neutral-100"
                        :class="
                          resource.authorizations.update &&
                          resource.products_count
                            ? 'cursor-pointer'
                            : ''
                        "
                        @click="
                          resource.authorizations.update &&
                          resource.products_count
                            ? (manageProducts = true)
                            : undefined
                        "
                        v-text="formatMoney(resource.amount)"
                      />
                    </ITextBlock>

                    <div
                      class="order-1 shrink-0 justify-center font-medium text-neutral-800 hover:text-neutral-600 dark:text-neutral-200 dark:hover:text-neutral-400 sm:order-2 sm:text-sm/5 lg:justify-start"
                    >
                      <DealStagePopover
                        :deal-id="resource.id"
                        :pipeline="resource.pipeline"
                        :stage-id="resource.stage_id"
                        :status="resource.status"
                        :authorized-to-update="resource.authorizations.update"
                        @updated="synchronizeResource($event, true)"
                      />
                    </div>
                  </div>

                  <p
                    v-once
                    class="text-base/6 text-neutral-600 dark:text-neutral-300 sm:text-sm/6"
                  >
                    {{ $t('core::app.created_at') }}
                    {{ localizedDateTime(resource.created_at) }}
                  </p>
                </div>
              </div>

              <div
                class="flex shrink-0 flex-col items-center lg:ml-6 lg:flex-row lg:space-x-2 lg:self-start"
              >
                <DealStatusChange
                  v-if="resource.authorizations.update"
                  class="mr-2 mt-5 lg:mt-0 lg:shrink-0"
                  :deal-id="resource.id"
                  :deal-status="resource.status"
                  @updated="synchronizeResource($event, true)"
                />

                <div
                  class="mt-5 flex shrink-0 justify-center space-x-0.5 lg:mt-0 lg:items-center lg:justify-normal"
                >
                  <UserOwnerDropdown
                    :owner="resource.user"
                    :authorize-update="resource.authorizations.update"
                    @change="updateResource({ user_id: $event?.id || null })"
                  />

                  <ActionSelector
                    type="dropdown"
                    :ids="resource.id || []"
                    :actions="resource.actions || []"
                    :resource-name="resourceName"
                    @run="handleActionExecuted"
                  />
                </div>
              </div>
            </div>

            <DealMiniPipeline
              class="mt-5"
              :stages="stagesForMiniPipeline"
              :time-in-stages="resource.time_in_stages"
              :deal-status="resource.status"
              :deal-stage-id="resource.stage_id"
              :deal-id="resource.id"
              @stage-updated="synchronizeResource($event, true)"
            />
          </div>
        </ICard>

        <IDropdown v-if="$gate.isSuperAdmin()" v-once placement="bottom-end">
          <IDropdownButton
            as="button"
            class="absolute -top-7 right-2 rotate-90 p-1 text-neutral-500 dark:text-neutral-300 lg:-right-7 lg:top-1.5 lg:rotate-0"
            no-caret
          >
            <Icon icon="EllipsisVerticalSolid" class="size-5" />
          </IDropdownButton>

          <IDropdownMenu>
            <IDropdownItem
              :text="$t('core::app.record_view.manage_sidebar')"
              @click="sidebarBeingManaged = true"
            />
          </IDropdownMenu>
        </IDropdown>
      </div>

      <div v-if="componentReady" class="mt-8">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
          <div class="col-span-4 space-y-3">
            <div
              v-for="section in enabledSections"
              v-show="!sidebarBeingManaged"
              :key="section.id"
            >
              <component
                :is="sectionComponents[section.component] || section.component"
                :resource-name="resourceName"
                :resource-id="resource.id"
                :resource="resource"
                @updated="synchronizeResource($event, true)"
              />
            </div>

            <ManageViewSections
              v-model:sections="template.sections"
              v-model:show="sidebarBeingManaged"
              class="-mt-3 inline"
              :identifier="resourceSingularName"
              @saved="sidebarBeingManaged = false"
            />
          </div>

          <div class="col-span-8 mt-4 lg:mt-0">
            <ITabGroup :default-index="defaultTabIndex">
              <ICard
                class="has-[[data-headlessui-state=selected]:not(:first-child)]:rounded-b-none"
              >
                <ITabList
                  class="has-[[data-headlessui-state=selected]:not(:first-child)]:pb-2.5 has-[[data-headlessui-state=selected]:not(:first-child)]:sm:pb-0"
                  centered
                >
                  <component
                    :is="tabComponents[tab.component] || tab.component"
                    v-for="tab in template.tabs"
                    :key="tab.id"
                    :resource-name="resourceName"
                    :resource-id="resource.id"
                    :resource="resource"
                  />
                </ITabList>
              </ICard>

              <ITabPanels class="[&_[data-slot=panel]]:-mt-[18px]">
                <component
                  :is="tabComponents[tab.panelComponent] || tab.panelComponent"
                  v-for="tab in template.tabs"
                  :id="'tabPanel-' + tab.id"
                  :key="tab.id"
                  scroll-element="#main"
                  :resource-name="resourceName"
                  :resource-id="resource.id"
                  :resource="resource"
                />
              </ITabPanels>
            </ITabGroup>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact, Company Create -->
    <RouterView
      v-if="componentReady"
      :via-resource="resourceName"
      :parent-resource="resource"
      :go-to-list="false"
      @associated="fetchResource(), $router.back()"
      @created="
        ({ isRegularAction }) => (
          isRegularAction ? $router.back() : '', fetchResource()
        )
      "
      @hidden="$router.back"
    />
  </MainLayout>
</template>

<script setup>
import { computed, provide, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import ActionSelector from '@/Core/components/Actions/ActionSelector.vue'
import ManageViewSections from '@/Core/components/ManageViewSections.vue'
import TagsSelectInput from '@/Core/components/TagsSelectInput.vue'
import { useAccounting } from '@/Core/composables/useAccounting'
import { usePrivateChannel } from '@/Core/composables/useBroadcast'
import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'
import { useGlobalEventListener } from '@/Core/composables/useGlobalEventListener'
import { usePageTitle } from '@/Core/composables/usePageTitle'
import { useResource } from '@/Core/composables/useResource'
import TimelineTab from '@/Core/views/Timeline/RecordTabTimeline.vue'
import TimelineTabPanel from '@/Core/views/Timeline/RecordTabTimelinePanel.vue'

import BillableFormProductsModal from '@/Billable/components/BillableFormProductsModal.vue'
import UserOwnerDropdown from '@/Users/components/UserOwnerDropdown.vue'

import DealMiniPipeline from '../components/DealMiniPipeline.vue'
import DealStagePopover from '../components/DealStagePopover.vue'
import DealStatusChange from '../components/DealStatusChange.vue'
import CompaniesSection from '../components/DealsViewCompanies.vue'
import ContactsSection from '../components/DealsViewContacts.vue'
import DetailsSection from '../components/DealsViewDetails.vue'
import MediaSection from '../components/DealsViewMedia.vue'

const tabComponents = {
  'timeline-tab': TimelineTab,
  'timeline-tab-panel': TimelineTabPanel,
}

const sectionComponents = {
  'details-section': DetailsSection,
  'contacts-section': ContactsSection,
  'companies-section': CompaniesSection,
  'media-section': MediaSection,
}

const resourceName = Innoclapps.resourceName('deals')

const router = useRouter()
const route = useRoute()
const { formatMoney } = useAccounting()
const { localizedDateTime } = useDates()
const { form: nameForm } = useForm({ name: null })

const sidebarBeingManaged = ref(false)
const manageProducts = ref(false)
const template = ref(Innoclapps.resource('deals').pages.detail)

const defaultTabIndex = route.query.section
  ? template.value.tabs.findIndex(tab => tab.id === route.query.section)
  : 0

let dealId = computed(() => route.params.id)

const {
  resource,
  resourceSingularName,
  synchronizeResource,
  detachResourceAssociations,
  incrementResourceCount,
  decrementResourceCount,
  fetchResource,
  updateResource,
  resourceReady: componentReady,
} = useResource(resourceName, dealId)

const broadcastChannel = computed(() =>
  resource.value?.authorizations?.view
    ? `Modules.Deals.App.Models.Deal.${dealId.value}`
    : null
)

useGlobalEventListener('refresh-details-view', () => fetchResource())

useGlobalEventListener('floating-resource-updated', () => fetchResource())

const enabledSections = computed(() =>
  template.value.sections.filter(section => section.enabled === true)
)

const stagesForMiniPipeline = computed(() =>
  resource.value.pipeline.stages.map(({ id, name }) => ({ id, name }))
)

provide('synchronizeResource', synchronizeResource)
provide('detachResourceAssociations', detachResourceAssociations)
provide('incrementResourceCount', incrementResourceCount)
provide('decrementResourceCount', decrementResourceCount)

usePageTitle(computed(() => resource.value.display_name))
usePrivateChannel(broadcastChannel, '.DealUpdated', () => fetchResource())

function handleActionExecuted(action) {
  if (!action.destroyable) {
    fetchResource()
  } else {
    router.push({ name: 'deal-index' })
  }
}

function updateName() {
  return updateResource(nameForm)
}

fetchResource()
</script>

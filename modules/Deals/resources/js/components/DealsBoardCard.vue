<template>
  <div
    v-if="swatchColor"
    class="bottom-hidden p-2"
    :style="{ background: swatchColor }"
  />

  <div v-bind="wrapperAttributes">
    <div class="flex">
      <div class="grow truncate">
        <ILink
          class="font-medium"
          :href="path"
          :text="displayName"
          basic
          @click="showDetailFloatModal"
        />

        <div class="-mt-0.5 flex text-sm">
          <IDropdown :portal="dropdownsAndPopoversInPortal" :offset="5">
            <IDropdownButton
              as="button"
              :class="{
                'text-warning-500': incompleteActivitiesCount > 0,
                'text-success-500': incompleteActivitiesCount === 0,
              }"
              no-caret
            >
              {{
                $t('activities::activity.count', incompleteActivitiesCount, {
                  count: incompleteActivitiesCount,
                })
              }}
            </IDropdownButton>

            <IDropdownMenu>
              <template v-if="nextActivityDate">
                <div class="px-4 py-2 text-sm sm:text-xs">
                  <p
                    v-t="'activities.activity.next_activity_date'"
                    class="text-neutral-500 dark:text-neutral-300"
                  />

                  <p
                    class="font-medium text-neutral-700 dark:text-neutral-100"
                    v-text="formattedNextActivityDate"
                  />
                </div>

                <IDropdownSeparator />
              </template>

              <IDropdownItem
                icon="Clock"
                :text="$t('activities::activity.create')"
                @click="$emit('createActivityRequested', dealId)"
              />
            </IDropdownMenu>
          </IDropdown>

          <BillableFormProductsModal
            v-if="manageProducts"
            :resource-name="resourceName"
            :resource-id="dealId"
            visible
            prefetch
            @saved="handleBillableModelSavedEvent"
            @hidden="manageProducts = false"
          />

          <IPopover v-slot="{ hide }" :portal="dropdownsAndPopoversInPortal">
            <IPopoverButton
              class="ml-2"
              :text="formattedAmount"
              link
              basic
              @click="handleDealAmountEdit"
            />

            <IPopoverPanel class="w-80">
              <IPopoverBody>
                <form @submit.prevent="updateDeal().then(hide)">
                  <IFormGroup
                    label-for="editDealAmount"
                    :label="$t('deals::fields.deals.amount')"
                    required
                  >
                    <IFormNumericInput
                      id="editDealAmount"
                      v-model="updateForm.amount"
                    />

                    <IFormError :error="updateForm.getError('amount')" />
                  </IFormGroup>

                  <ILink
                    class="inline-flex items-center"
                    @click="showManageProductsModal(hide)"
                  >
                    {{ $t('billable::product.manage') }}

                    <Icon icon="Window" class="ml-2 mt-px size-4" />
                  </ILink>
                </form>
              </IPopoverBody>

              <IPopoverFooter class="flex justify-end space-x-2">
                <IButton
                  :disabled="updateForm.busy"
                  :text="$t('core::app.cancel')"
                  basic
                  @click="hide"
                />

                <IButton
                  variant="primary"
                  :loading="updateForm.busy"
                  :disabled="updateForm.busy"
                  :text="$t('core::app.save')"
                  @click="updateDeal().then(hide)"
                />
              </IPopoverFooter>
            </IPopoverPanel>
          </IPopover>
        </div>

        <p
          v-if="expectedCloseDate"
          :class="[
            '-mt-1 text-sm/6 sm:text-xs/6',
            fallsBehindExpectedCloseDate
              ? 'animate-[pulse_4s_cubic-bezier(0.4,_0,_0.6,_1)_infinite] text-warning-600 dark:text-warning-300'
              : 'text-neutral-500 dark:text-neutral-300',
          ]"
          v-text="formattedExpectedCloseDate"
        />
      </div>

      <div class="flex flex-col items-center space-y-1">
        <IDropdownMinimal
          items-class="min-w-[180px]"
          placement="bottom-end"
          :portal="dropdownsAndPopoversInPortal"
          small
        >
          <div class="px-3 py-2 text-xs">
            <p
              v-t="'core::app.created_at'"
              class="text-neutral-500 dark:text-neutral-300"
            />

            <p
              class="font-medium text-neutral-700 dark:text-neutral-100"
              v-text="formattedCreatedAtDate"
            />

            <div v-if="updatedAt && updatedAt !== createdAt" class="mt-1">
              <p
                v-t="'core::app.updated_at'"
                class="text-neutral-500 dark:text-neutral-300"
              />

              <p
                class="font-medium text-neutral-700 dark:text-neutral-100"
                v-text="formattedUpdatedAtDate"
              />
            </div>
          </div>

          <IDropdownSeparator />

          <IDropdownItem
            icon="Clock"
            :text="$t('activities::activity.create')"
            @click="$emit('createActivityRequested', dealId)"
          />

          <IDropdownItem
            icon="Eye"
            :text="$t('core::app.view_record')"
            :to="path"
          />

          <IDropdownItem
            icon="PencilAlt"
            :text="$t('core::app.edit') + ' ' + $t('deals::deal.deal')"
            @click="showEditFloatModal"
          />
        </IDropdownMinimal>

        <IPopover
          placement="bottom-end"
          :portal="dropdownsAndPopoversInPortal"
          :offset="0"
        >
          <IPopoverButton
            v-once
            class="opacity-100 md:opacity-0 md:group-hover:opacity-100"
            icon="ColorSwatch"
            basic
            small
          />

          <IPopoverPanel class="w-56">
            <IPopoverBody>
              <IColorSwatch
                :model-value="swatchColor"
                :swatches="$scriptConfig('favourite_colors')"
                :customable="false"
                @input="$emit('update:swatchColor', $event)"
              />
            </IPopoverBody>
          </IPopoverPanel>
        </IPopover>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

import { useAccounting } from '@/Core/composables/useAccounting'
import { useDates } from '@/Core/composables/useDates'
import { useFloatingResourceModal } from '@/Core/composables/useFloatingResourceModal'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'

import BillableFormProductsModal from '@/Billable/components/BillableFormProductsModal.vue'

const props = defineProps({
  dealId: { required: true, type: Number },
  displayName: { required: true, type: String },
  amount: { required: true, type: [String, Number] },
  path: { required: true, type: String },
  status: { required: true, type: String },
  incompleteActivitiesCount: { required: true, type: Number },
  productsCount: { required: true, type: Number },
  expectedCloseDate: String,
  nextActivityDate: String,
  swatchColor: String,
  fallsBehindExpectedCloseDate: Boolean,
  updatedAt: String,
  createdAt: String,
})

const emit = defineEmits([
  'createActivityRequested',
  'update:amount',
  'update:productsCount',
  'update:swatchColor',
])

/**
 * For performance reasons, do not use portal for the card dropdowns and popovers.
 * It created additional dom nodes and needs more processing power.
 * When the board has a lot of cards, it may become laggy.
 */
const dropdownsAndPopoversInPortal = false
const resourceName = Innoclapps.resourceName('deals')

const manageProducts = ref(false)

const { localizedDate, localizedDateTime, isMidnight } = useDates()
const { formatMoney } = useAccounting()

const { floatResourceInDetailMode, floatResourceInEditMode } =
  useFloatingResourceModal()

const { form: updateForm } = useForm({
  amount: props.amount,
})

const { updateResource } = useResourceable(resourceName)

const wrapperAttributes = computed(() => {
  const classList = ['bottom-hidden group px-4 py-2']

  if (props.fallsBehindExpectedCloseDate) {
    classList.push('bg-warning-200/20', 'dark:bg-warning-400/30')
  }

  if (props.status === 'lost') {
    classList.push('bg-danger-50', 'dark:bg-danger-500/30')
  } else if (props.status === 'won') {
    classList.push('bg-success-50/70', 'dark:bg-success-500/30')
  }

  return { class: classList }
})

const formattedAmount = computed(() => formatMoney(props.amount))

const formattedExpectedCloseDate = computed(() =>
  localizedDate(props.expectedCloseDate)
)

const formattedCreatedAtDate = computed(() =>
  localizedDateTime(props.createdAt)
)

const formattedUpdatedAtDate = computed(() =>
  localizedDateTime(props.updatedAt)
)

const formattedNextActivityDate = computed(() =>
  isMidnight(props.nextActivityDate)
    ? localizedDate(props.nextActivityDate)
    : localizedDateTime(props.nextActivityDate)
)

function showDetailFloatModal() {
  floatResourceInDetailMode({
    resourceName,
    resourceId: props.dealId,
  })
}

function showManageProductsModal(hidePopover) {
  manageProducts.value = true
  hidePopover()
}

function showEditFloatModal() {
  floatResourceInEditMode({
    resourceName,
    resourceId: props.dealId,
  })
}

function updateDeal() {
  return updateResource(updateForm, props.dealId).then(deal => {
    emit('update:amount', deal.amount)
    updateForm.set('amount', deal.amount)
  })
}

function handleDealAmountEdit(e) {
  if (props.productsCount > 0) {
    e.preventDefault() // stop popover from opening
    showProductsModal()
  }
}

async function handleBillableModelSavedEvent(billable) {
  emit('update:amount', billable.total)
  updateForm.set('amount', billable.total)
  emit('update:productsCount', billable.products.length)
}

function showProductsModal() {
  manageProducts.value = true
}
</script>

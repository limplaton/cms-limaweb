<template>
  <ICardHeader>
    <ICardHeading :text="$t('webforms::form.forms')" />

    <ICardActions>
      <IButton
        v-show="hasForms"
        variant="primary"
        icon="PlusSolid"
        :text="$t('webforms::form.create')"
        @click="redirectToCreate"
      />
    </ICardActions>
  </ICardHeader>

  <ICard :overlay="formsAreBeingFetched">
    <template v-if="!formsAreBeingFetched">
      <TransitionGroup
        v-if="hasForms"
        name="flip-list"
        tag="ul"
        class="divide-y divide-neutral-200 dark:divide-neutral-500/30"
      >
        <li v-for="form in listForms" :key="form.id">
          <div :class="{ 'opacity-70': form.status === 'inactive' }">
            <div class="flex items-center px-4 py-5 sm:px-6">
              <div
                class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between"
              >
                <div class="truncate">
                  <div class="flex flex-col sm:flex-row">
                    <ILink
                      class="font-medium sm:flex sm:items-center"
                      @click="redirectToEdit(form.id)"
                    >
                      <span class="whitespace-normal sm:max-w-md sm:truncate">
                        {{ form.title }}
                      </span>

                      <Icon
                        icon="ArrowRight"
                        class="ml-1 hidden size-4 sm:block"
                      />
                    </ILink>

                    <IBadge
                      v-if="findPipelineById(form.submit_data.pipeline_id)"
                      class="sm:ml-2"
                      variant="primary"
                    >
                      {{ findPipelineById(form.submit_data.pipeline_id).name }}
                      <Icon icon="ChevronRight" class="mx-0.5 size-3" />
                      {{
                        findPipelineStageById(
                          form.submit_data.pipeline_id,
                          form.submit_data.stage_id
                        ).name
                      }}
                    </IBadge>
                  </div>

                  <div class="mt-2 sm:flex sm:items-center sm:space-x-4">
                    <ILink
                      :text="$t('core::app.preview')"
                      :href="form.public_url"
                    />

                    <ITextDark
                      class="font-medium"
                      :text="
                        $t('webforms::form.total_submissions', {
                          total: form.total_submissions || 0,
                        })
                      "
                    />
                  </div>
                </div>

                <div class="mt-2 shrink-0 sm:ml-5 sm:mt-0">
                  <IFormSwitchField>
                    <IFormSwitchLabel :text="$t('webforms::form.active')" />

                    <IFormSwitch
                      value="active"
                      unchecked-value="inactive"
                      :model-value="form.status"
                      @change="toggleStatus(form)"
                    />
                  </IFormSwitchField>
                </div>
              </div>

              <IDropdownMinimal class="ml-2 shrink-0 self-start sm:self-auto">
                <IDropdownItem
                  icon="PencilAlt"
                  :text="$t('core::app.edit')"
                  @click="redirectToEdit(form.id)"
                />

                <IDropdownItem
                  icon="Trash"
                  :text="$t('core::app.delete')"
                  @click="$confirm(() => destroy(form.id))"
                />
              </IDropdownMinimal>
            </div>
          </div>
        </li>
      </TransitionGroup>

      <ICardBody v-else>
        <IEmptyState
          :button-text="$t('webforms::form.create')"
          :description="$t('webforms::form.info')"
          @click="redirectToCreate"
        />
      </ICardBody>
    </template>
  </ICard>
  <!-- Create -->
  <RouterView />
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import { useForm } from '@/Core/composables/useForm'

import { usePipelines } from '@/Deals/composables/usePipelines'

import { useWebForms } from '../composables/useWebForms'

const { t } = useI18n()
const router = useRouter()

const {
  webFormsOrderedByNameAndStatus: listForms,
  formsAreBeingFetched,
  setWebForm,
  deleteWebForm,
} = useWebForms()

const { findPipelineById, findPipelineStageById } = usePipelines()

const { form: toggleStatusForm } = useForm({ status: null })

const hasForms = computed(() => listForms.value.length > 0)

async function destroy(id) {
  await deleteWebForm(id)

  Innoclapps.success(t('webforms::form.deleted'))
}

function redirectToCreate() {
  router.push({
    name: 'web-form-create',
  })
}

function redirectToEdit(id) {
  router.push({
    name: 'web-form-edit',
    params: {
      id: id,
    },
  })
}

function toggleStatus(form) {
  toggleStatusForm.fill(
    'status',
    form.status === 'active' ? 'inactive' : 'active'
  )

  toggleStatusForm.put(`/forms/${form.id}`).then(data => {
    setWebForm(data.id, data)
  })
}
</script>

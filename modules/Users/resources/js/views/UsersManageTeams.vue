<template>
  <ICardHeader>
    <ICardHeading :text="$t('users::team.teams')" />

    <ICardActions>
      <IButton
        v-show="hasTeams"
        variant="primary"
        icon="PlusSolid"
        :text="$t('users::team.add')"
        @click="teamIsBeingCreated = true"
      />
    </ICardActions>
  </ICardHeader>

  <ICard :overlay="teamsAreBeingFetched">
    <ul
      v-if="hasTeams"
      role="list"
      class="divide-y divide-neutral-200 dark:divide-neutral-500/30"
    >
      <li v-for="team in teamsByName" :key="team.id">
        <ILink
          class="group block hover:bg-neutral-50 dark:hover:bg-neutral-800/60"
          plain
          @click="
            teamContentIsVisible[team.id] = !teamContentIsVisible[team.id]
          "
        >
          <div class="flex items-center px-4 py-4 sm:px-6">
            <div
              class="min-w-0 flex-1 sm:flex sm:items-center sm:justify-between"
            >
              <div class="truncate">
                <div class="flex items-center text-base/6 sm:text-sm/6">
                  <p
                    class="truncate font-medium text-primary-600 dark:text-primary-100"
                    v-text="team.name"
                  />

                  <ILink
                    class="ml-2 md:hidden md:group-hover:block"
                    :text="$t('core::app.edit')"
                    @click.stop="prepareEdit(team)"
                  />

                  <ILink
                    class="ml-2 md:hidden md:group-hover:block"
                    variant="danger"
                    :text="$t('core::app.delete')"
                    @click.stop="$confirm(() => destroy(team.id))"
                  />
                </div>

                <ITextBlock class="mt-2 flex items-center space-x-1.5">
                  <Icon icon="Calendar" class="size-5 shrink-0" />

                  <p>
                    {{ $t('core::app.created_at') }}
                    {{ ' ' }}
                    <time :datetime="team.created_at">
                      {{ localizedDateTime(team.created_at) }}
                    </time>
                  </p>
                </ITextBlock>
              </div>

              <div class="mt-4 shrink-0 sm:ml-5 sm:mt-0">
                <div class="flex -space-x-1 overflow-hidden">
                  <IAvatar
                    v-for="member in team.members"
                    :key="member.email"
                    v-i-tooltip="member.name"
                    class="ring-2 ring-white dark:ring-neutral-900"
                    :alt="member.name"
                    :src="member.avatar_url"
                  />
                </div>
              </div>
            </div>

            <div class="ml-5 shrink-0">
              <Icon
                icon="ChevronRight"
                class="size-5 text-neutral-400 sm:size-4"
              />
            </div>
          </div>
        </ILink>

        <div v-show="teamContentIsVisible[team.id]" class="px-4 py-4 sm:px-6">
          <ITextDark
            class="mb-1 font-medium"
            :text="$t('users::team.manager')"
          />

          <IText class="mb-3" :text="team.manager.name" />

          <ITextDark
            class="mb-1 font-medium"
            :text="$t('users::team.members')"
          />

          <div
            v-for="member in team.members"
            :key="'info-' + member.email"
            class="mb-1 flex items-center space-x-1.5 last:mb-0"
          >
            <IAvatar :alt="member.name" :src="member.avatar_url" />

            <IText :text="member.name" />
          </div>

          <ITextDark
            v-show="team.description"
            class="mb-1 mt-3 font-medium"
            :text="$t('users::team.description')"
          />

          <IText :text="team.description" />
        </div>
      </li>
    </ul>

    <ICardBody v-else-if="!teamsAreBeingFetched">
      <IEmptyState
        :button-text="$t('users::team.add')"
        :title="$t('users::team.empty_state.title')"
        :description="$t('users::team.empty_state.description')"
        @click="teamIsBeingCreated = true"
      />
    </ICardBody>
  </ICard>

  <IModal
    :title="$t('users::team.create')"
    :visible="teamIsBeingCreated"
    :ok-text="$t('core::app.create')"
    :ok-disabled="formCreate.busy"
    form
    @hidden="teamIsBeingCreated = false"
    @submit="create"
    @shown="() => $refs.nameInputCreateRef.focus()"
  >
    <IFormGroup
      label-for="nameInputCreate"
      :label="$t('users::team.name')"
      required
    >
      <IFormInput
        id="nameInputCreate"
        ref="nameInputCreateRef"
        v-model="formCreate.name"
      />

      <IFormError :error="formCreate.getError('name')" />
    </IFormGroup>

    <IFormGroup label-for="user_id" :label="$t('users::team.manager')" required>
      <ICustomSelect
        v-model="formCreate.user_id"
        label="name"
        input-id="user_id"
        :options="users"
        :clearable="false"
        :reduce="user => user.id"
      />

      <IFormError :error="formCreate.getError('user_id')" />
    </IFormGroup>

    <IFormGroup
      label-for="membersInputCreate"
      :label="$t('users::team.members')"
    >
      <ICustomSelect
        v-model="formCreate.members"
        input-id="membersInputCreate"
        label="name"
        :options="users"
        :reduce="option => option.id"
        multiple
      />
    </IFormGroup>

    <IFormGroup
      label-for="descriptionInputCreate"
      :label="$t('users::team.description')"
    >
      <IFormTextarea
        id="descriptionInputCreate"
        v-model="formCreate.description"
      />

      <IFormError :error="formCreate.getError('description')" />
    </IFormGroup>
  </IModal>

  <IModal
    :visible="teamIsBeingEdited !== null"
    :ok-text="$t('core::app.save')"
    :ok-disabled="formUpdate.busy"
    :title="$t('users::team.edit')"
    :sub-title="teamIsBeingEdited?.name"
    form
    @hidden=";(teamIsBeingEdited = null), formUpdate.reset()"
    @submit="update"
  >
    <IFormGroup
      label-for="nameInputEdit"
      :label="$t('users::team.name')"
      required
    >
      <IFormInput
        id="nameInputEdit"
        ref="nameInputEdit"
        v-model="formUpdate.name"
      />

      <IFormError :error="formUpdate.getError('name')" />
    </IFormGroup>

    <IFormGroup label-for="user_id" :label="$t('users::team.manager')" required>
      <ICustomSelect
        v-model="formUpdate.user_id"
        label="name"
        input-id="user_id"
        :options="users"
        :clearable="false"
        :reduce="user => user.id"
      />

      <IFormError :error="formUpdate.getError('user_id')" />
    </IFormGroup>

    <IFormGroup label-for="membersInputEdit" :label="$t('users::team.members')">
      <ICustomSelect
        v-model="formUpdate.members"
        input-id="membersInputEdit"
        label="name"
        :options="users"
        :reduce="option => option.id"
        multiple
      />
    </IFormGroup>

    <IFormGroup
      label-for="descriptionInputEdit"
      :label="$t('users::team.description')"
    >
      <IFormTextarea
        id="descriptionInputEdit"
        v-model="formUpdate.description"
      />

      <IFormError :error="formUpdate.getError('description')" />
    </IFormGroup>
  </IModal>
</template>

<script setup>
import { computed, onBeforeMount, ref } from 'vue'
import { useStore } from 'vuex'

import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { useForm } from '@/Core/composables/useForm'

import { useTeams } from '../composables/useTeams'

const store = useStore()

const { users } = useApp()

const { localizedDateTime } = useDates()

const { teamsByName, teamsAreBeingFetched, addTeam, deleteTeam, setTeam } =
  useTeams()

const teamIsBeingCreated = ref(false)
const teamIsBeingEdited = ref(null)
const modificationsPerformed = ref(false)
const teamContentIsVisible = ref({})

const { form: formCreate } = useForm(
  {
    name: null,
    description: null,
    user_id: null,
    members: [],
  },
  { resetOnSuccess: true }
)

const { form: formUpdate } = useForm(
  {
    name: null,
    description: null,
    user_id: null,
    members: [],
  },
  { resetOnSuccess: true }
)

const hasTeams = computed(() => teamsByName.value.length > 0)

function create() {
  formCreate.post('/teams').then(team => {
    addTeam(team)
    teamIsBeingCreated.value = false
    modificationsPerformed.value = true
  })
}

function update() {
  formUpdate.put(`/teams/${teamIsBeingEdited.value.id}`).then(team => {
    setTeam(team.id, team)
    teamIsBeingEdited.value = null
    modificationsPerformed.value = true
  })
}

async function destroy(id) {
  await deleteTeam(id)

  modificationsPerformed.value = true
}

function prepareEdit(team) {
  teamIsBeingEdited.value = team
  formUpdate.fill('name', team.name)
  formUpdate.fill('user_id', team.user_id)

  formUpdate.fill(
    'members',
    team.members.map(member => member.id)
  )
  formUpdate.fill('description', team.description)
}

onBeforeMount(() => {
  if (modificationsPerformed.value) {
    store.commit('table/RESET_SETTINGS')
  }
})
</script>

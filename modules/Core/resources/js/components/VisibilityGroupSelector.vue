<template>
  <div>
    <IFormLabel as="p">
      {{ $t('core::app.visibility_group.visible_to') }}
    </IFormLabel>

    <fieldset class="mt-1">
      <legend class="sr-only">Visibility group</legend>

      <div class="space-y-3 sm:flex sm:items-center sm:space-x-4 sm:space-y-0">
        <IFormRadioField>
          <IFormRadio
            value="all"
            name="visible_to"
            :model-value="type"
            :disabled="disabled"
            @update:model-value="$emit('update:type', $event)"
            @change="$emit('update:dependsOn', [])"
          />

          <IFormRadioLabel :text="$t('core::app.visibility_group.all')" />
        </IFormRadioField>

        <IFormRadioField>
          <IFormRadio
            value="teams"
            name="visible_to"
            :model-value="type"
            :disabled="disabled"
            @update:model-value="$emit('update:type', $event)"
            @change="$emit('update:dependsOn', [])"
          />

          <IFormRadioLabel :text="$t('users::team.teams')" />
        </IFormRadioField>

        <IFormRadioField>
          <IFormRadio
            value="users"
            name="visible_to"
            :model-value="type"
            :disabled="disabled"
            @update:model-value="$emit('update:type', $event)"
            @change="$emit('update:dependsOn', [])"
          />

          <IFormRadioLabel :text="$t('users::user.users')" />
        </IFormRadioField>
      </div>
    </fieldset>

    <div v-show="type === 'users'" class="mt-4">
      <ICustomSelect
        label="name"
        :model-value="dependsOn"
        :options="usersListAdminsExcluded"
        :placeholder="$t('users::user.select')"
        :reduce="option => option.id"
        multiple
        @update:model-value="$emit('update:dependsOn', $event)"
      />

      <span
        class="mt-0.5 block text-right text-xs italic text-neutral-500 dark:text-neutral-300"
      >
        * {{ $t('users::user.admin_users_excluded') }}
      </span>
    </div>

    <div v-show="type === 'teams'" class="mt-4">
      <ICustomSelect
        label="name"
        :model-value="dependsOn"
        :options="teams"
        :placeholder="$t('users::team.select')"
        :reduce="option => option.id"
        multiple
        @update:model-value="$emit('update:dependsOn', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

import { useApp } from '@/Core/composables/useApp'

import { useTeams } from '@/Users/composables/useTeams'

defineProps({ disabled: Boolean, dependsOn: Array, type: String })

defineEmits(['update:type', 'update:dependsOn'])

const { users } = useApp()

const { teamsByName: teams } = useTeams()

const usersListAdminsExcluded = computed(() =>
  users.value.filter(user => !user.super_admin)
)
</script>

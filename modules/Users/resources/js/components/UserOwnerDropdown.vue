<template>
  <IDropdown>
    <IDropdownButton :disabled="!authorizeUpdate" basic>
      <span
        v-i-tooltip.top="$t('deals::fields.deals.user.name')"
        class="max-w-36 truncate sm:max-w-48"
      >
        <IAvatar
          v-if="owner"
          size="xs"
          class="mr-1.5"
          :src="owner.avatar_url"
        />
        {{ owner ? owner.name : $t('core::app.no_owner') }}
      </span>
    </IDropdownButton>

    <IDropdownMenu class="max-h-80">
      <IDropdownItem
        v-for="user in users"
        :key="user.id"
        :text="user.name"
        :active="owner && user.id === owner.id"
        @click="$emit('change', user)"
      />

      <IDropdownSeparator v-if="owner" />

      <IDropdownItem
        v-if="owner"
        icon="XSolid"
        :text="$t('core::app.no_owner')"
        @click="$emit('change', null)"
      />
    </IDropdownMenu>
  </IDropdown>
</template>

<script setup>
import { useApp } from '@/Core/composables/useApp'

defineProps(['owner', 'authorizeUpdate'])

defineEmits(['change'])

const { users } = useApp()
</script>

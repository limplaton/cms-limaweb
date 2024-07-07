<template>
  <IFormGroup label-for="name" :label="$t('core::role.name')" required>
    <IFormInput
      id="name"
      ref="nameRef"
      name="name"
      type="text"
      :model-value="name"
      @update:model-value="$emit('update:name', $event)"
    />

    <IFormError :error="form.getError('name')" />
  </IFormGroup>

  <IOverlay class="mt-5" :show="isLoading">
    <div v-show="availablePermissions.all.length > 0">
      <ITextDisplay class="my-4" :text="$t('core::role.permissions')" />

      <div
        v-for="(group, index) in availablePermissions.grouped"
        :key="index"
        class="mb-4"
      >
        <ITextDark class="mb-1 font-medium">
          {{ group.as }}
        </ITextDark>

        <div v-for="view in group.views" :key="view.group">
          <div class="flex items-center justify-between">
            <IText>
              {{
                view.as
                  ? view.as
                  : view.single
                    ? view.permissions[view.keys[0]]
                    : ''
              }}
            </IText>

            <IDropdown placement="bottom-end">
              <IDropdownButton
                :text="getSelectedPermissionTextByView(view)"
                basic
              />

              <IDropdownMenu>
                <IDropdownItem
                  v-show="view.revokeable"
                  :text="$t('core::role.revoked')"
                  condensed
                  @click="revokePermission(view)"
                />

                <IDropdownItem
                  v-if="view.single"
                  :text="$t('core::role.granted')"
                  condensed
                  @click="setSelectedPermission(view, view.keys[0])"
                />

                <IDropdownItem
                  v-for="(permission, key) in view.permissions"
                  v-else
                  :key="key"
                  :disabled="permissions.indexOf(key) > -1"
                  :text="permission"
                  condensed
                  @click="setSelectedPermission(view, key)"
                />
              </IDropdownMenu>
            </IDropdown>
          </div>
        </div>
      </div>
    </div>
  </IOverlay>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { useLoader } from '@/Core/composables/useLoader'

const props = defineProps({
  inCreateMode: Boolean,
  name: { required: true, type: String },
  permissions: { required: true, type: Array },
  form: { required: true, type: Object },
})

const emit = defineEmits(['update:name', 'update:permissions'])

const { t } = useI18n()
const { setLoading, isLoading } = useLoader()

const nameRef = ref(null)
const availablePermissions = ref({ all: [], grouped: {} })

function getSelectedPermissionTextByView(view) {
  // For single view, check the first key's presence in permissions
  if (view.single) {
    return props.permissions.includes(view.keys[0])
      ? t('core::role.granted')
      : t('core::role.revoked')
  }

  // For non-single view, find the first matching permission and return its text
  const selectedPermission = view.keys.find(key =>
    props.permissions.includes(key)
  )

  // For non-single view, find the first matching permission and return its text
  return selectedPermission
    ? view.permissions[selectedPermission]
    : t('core::role.revoked')
}

function setSelectedPermission(view, permissionKey) {
  // Revoke any previously view permissions
  let permissions = revokePermission(view, false)

  emit('update:permissions', [...permissions, permissionKey])
}

function revokePermission(view, emitEvent = true) {
  const newPermissions = props.permissions.filter(
    permission => !view.keys.includes(permission)
  )

  if (emitEvent) {
    emit('update:permissions', newPermissions)
  }

  return newPermissions
}

function setDefaultSelectedPermissions(permissions) {
  const defaultPermissions = Object.values(permissions.grouped).flatMap(group =>
    group.views
      .filter(view => !view.single) // when it's not a single permission, set the first as selected.
      .map(view => Object.keys(view.permissions)[0])
  )

  emit('update:permissions', defaultPermissions)
}

async function fetchAndSetPermissions() {
  setLoading(true)

  try {
    const { data } = await Innoclapps.request('/permissions')
    availablePermissions.value = data

    if (props.inCreateMode) {
      setDefaultSelectedPermissions(data)
    }
  } finally {
    setLoading(false)
  }
}

fetchAndSetPermissions()

defineExpose({ nameRef })
</script>

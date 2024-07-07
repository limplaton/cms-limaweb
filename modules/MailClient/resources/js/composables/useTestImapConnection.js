
import { ref } from 'vue'

import { useForm } from '@/Core/composables/useForm'

export function useTestImapConnection(state = false) {
  const { form: testConnectionForm } = useForm()
  const isSuccessful = ref(state)

  function testConnection(form) {
    testConnectionForm.set({
      id: form.id || null,
      connection_type: form.connection_type,
      email: form.email,
      password: form.password,
      username: form.username,
      imap_server: form.imap_server,
      imap_port: form.imap_port,
      imap_encryption: form.imap_encryption,
      smtp_server: form.smtp_server,
      smtp_port: form.smtp_port,
      smtp_encryption: form.smtp_encryption,
      validate_cert: form.validate_cert,
    })

    return new Promise((resolve, reject) => {
      testConnectionForm
        .post('/mail/accounts/connection')
        .then(data => {
          isSuccessful.value = true
          resolve(data)
        })
        .catch(error => {
          isSuccessful.value = false
          reject(error)
        })
    })
  }

  return {
    testConnection,
    isSuccessful,
    testConnectionForm,
  }
}

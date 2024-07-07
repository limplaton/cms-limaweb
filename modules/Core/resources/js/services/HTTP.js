
import axios from 'axios'

import { emitGlobal } from '../composables/useGlobalEventListener'

const instance = axios.create({
  transformRequest: [
    (data, headers) => {
      // axios v1.1.5 no longer set the Content-Type header to "multipart/form-data" automatically
      // As a temporary solution (if fixed from axios), we will manually check if the data has files
      // and set the Content-Type header to 'multipart/form-data'.
      if (data instanceof FormData && formDataContainsFiles(data)) {
        headers['Content-Type'] = 'multipart/form-data'
      }

      return data
    },
    ...axios.defaults.transformRequest,
  ],
})

instance.defaults.withCredentials = true
instance.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
instance.defaults.headers.common['Content-Type'] = 'application/json'

instance.interceptors.request.use(config => {
  // https://stackoverflow.com/questions/49435859/broadcast-to-others-and-dont-broadcast-to-current-user-are-not-working
  if (window.Echo) {
    let socketId = window.Echo.socketId()

    if (socketId) {
      config.headers['X-Socket-ID'] = window.Echo.socketId()
    }
  }

  return config
})

instance.interceptors.response.use(
  response => {
    return response
  },
  error => {
    if (axios.isCancel(error)) {
      return error
    }

    error.isValidationError = () => false

    const status = error.response.status

    if (status === 404) {
      // 404 not found
      emitGlobal('error-404')
    } else if (status === 403) {
      // Forbidden
      emitGlobal('error-403', error)
    } else if (status === 401) {
      // Session timeout / Logged out
      window.location.href = Innoclapps.scriptConfig('url') + '/login'
    } else if (status === 409) {
      // Conflicts
      emitGlobal('conflict', error.response.data.message)
    } else if (status === 419) {
      // Handle expired CSRF token
      emitGlobal('token-expired', error)
    } else if (status === 422) {
      error.isValidationError = () => true
      // Emit form validation errors event
      emitGlobal('form-validation-errors', error.response.data.errors)
    } else if (status === 429) {
      // Handle throttle errors
      emitGlobal('too-many-requests', error)
    } else if (status === 503) {
      emitGlobal('maintenance-mode', error.response.data.message)
    } else if (status >= 500) {
      // 500 errors
      emitGlobal('error', error.response.data.message)
    }

    return Promise.reject(error)
  }
)

function formDataContainsFiles(formData) {
  function containsFilesRecursive(value) {
    if (value instanceof File) {
      return true
    } else if (value instanceof Blob) {
      return false
    } else if (Array.isArray(value)) {
      for (const item of value) {
        if (containsFilesRecursive(item)) {
          return true
        }
      }
    } else if (typeof value === 'object' && value !== null) {
      for (const subValue of Object.values(value)) {
        if (containsFilesRecursive(subValue)) {
          return true
        }
      }
    }

    return false
  }

  // eslint-disable-next-line no-unused-vars
  for (const [key, value] of formData.entries()) {
    if (containsFilesRecursive(value)) {
      return true
    }
  }

  return false
}

export default instance
export const CancelToken = axios.CancelToken


import { ref, shallowRef, toValue, watchEffect } from 'vue'
import FileDownload from 'js-file-download'

import { throwConfetti } from '@/Core/utils'

import { emitGlobal } from './useGlobalEventListener'

export function handleActionResponse(response) {
  let data = response.data
  let headers = response.headers

  if (data instanceof Blob) {
    FileDownload(
      data,
      headers['content-disposition'].split('filename=')[1] || 'unknown'
    )
  } else if (data.error) {
    Innoclapps.error(data.error)
  } else if (data.success) {
    Innoclapps.success(data.success)
  } else if (data.info) {
    Innoclapps.info(data.info)
  } else if (data.confetti) {
    throwConfetti()
  }
}

export function useAction(ids, options, callback) {
  const action = shallowRef(null)
  const actionIsRunning = ref(false)

  let resourceName = null

  watchEffect(() => {
    resourceName = toValue(options.resourceName)
  })

  function getEndpoint() {
    return `${resourceName}/actions/${action.value.uriKey}/run`
  }

  function run() {
    if (!action.value) {
      return
    }

    if (!action.value.withoutConfirmation) {
      return showDialog()
    }

    actionIsRunning.value = true

    Innoclapps.request({
      method: 'post',
      data: {
        ids: ids.value,
        ...toValue(options.requestParams),
      },
      responseType: action.value.responseType,
      url: getEndpoint(),
    })
      .then(handleExecutedAction)
      .finally(() => (actionIsRunning.value = false))
  }

  function handleExecutedAction(response) {
    let data = response.data

    if (data.openInNewTab) {
      window.open(data.openInNewTab, '_blank')
    } else {
      handleActionResponse(response)

      let params = Object.assign({}, action.value, {
        ids: ids.value,
        response: data,
        resourceName,
      })

      emitGlobal('action-executed', params)

      callback(params)
    }

    action.value = null
  }

  function showDialog() {
    Innoclapps.confirm({
      component: action.value.component,
      title: action.value.name,
      message: action.value.message,
      size: action.value.size,
      ids: ids.value,
      endpoint: getEndpoint(),
      action: action.value,
      queryString: toValue(options.requestParams),
      resourceName,
      fields: action.value.fields || [],
    })
      .then(dialog => handleExecutedAction(dialog.response))
      // If canceled, set action to null because when not setting the action to null will
      // not trigger change if the user click again on the same action
      .catch(() => (action.value = null))
  }

  return { run, action, actionIsRunning, ids }
}

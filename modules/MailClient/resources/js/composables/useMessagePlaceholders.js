
import { computed } from 'vue'
import { computedAsync } from '@vueuse/core'

let _placeholders = null

export function insertPlaceholder(placeholder, groupLabel) {
  let html = ''

  if (!placeholder.newlineable) {
    html = `<input type="text"
                          class="_placeholder"
                          data-tag="${placeholder.tag}"
                          placeholder="${groupLabel} ${placeholder.description}" /> `
  } else {
    html = `<textarea class="_placeholder"
                          rows="1"
                          data-tag="${placeholder.tag}"
                          placeholder="${groupLabel} ${placeholder.description}"></textarea> `
  }

  tinymce.activeEditor.execCommand('mceInsertContent', false, html)
}

export function useMessagePlaceholders() {
  const placeholders = computedAsync(
    async () => {
      if (_placeholders !== null) {
        return _placeholders
      }

      const { data } = await Innoclapps.request('/placeholders', {
        params: { resources: ['contacts', 'companies', 'deals'] },
      })

      data.contacts.icon = 'Users'
      data.companies.icon = 'OfficeBuilding'
      data.deals.icon = 'CurrencyDollar'

      _placeholders = data

      return _placeholders
    },
    [],
    { lazy: true }
  )

  const allPlaceholders = computed(() => {
    let list = []

    Object.keys(placeholders.value).forEach(group => {
      placeholders.value[group].placeholders.forEach(placeholder => {
        list.push(placeholder)
      })
    })

    return list
  })

  const allPlaceholdersInterpolations = computed(() => {
    return allPlaceholders.value.reduce((tags, p) => {
      const tag = [p.interpolation_start, p.interpolation_end]

      if (tags.length === 0) {
        tags.push(tag)
      } else {
        tags.forEach(i => {
          if (i[0] !== tag[0] && i[1] !== tag[1]) {
            tags.push(tag)
          }
        })
      }

      return tags
    }, [])
  })

  async function makeParsePlaceholdersRequest(resources, content, type) {
    if (!content) {
      return content
    }

    let { data } = await Innoclapps.request().post(`/placeholders/${type}`, {
      resources: resources,
      content: content,
    })

    return data
  }

  return {
    placeholders,
    allPlaceholders,
    allPlaceholdersInterpolations,
    makeParsePlaceholdersRequest,
  }
}

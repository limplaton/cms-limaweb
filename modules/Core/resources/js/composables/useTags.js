
import { computed, ref, watchEffect } from 'vue'
import orderBy from 'lodash/orderBy'

import { useApp } from './useApp'

/**
 * @type {import('vue').Ref<Array<{id: number, name: string, swatch_color: string | null, type: string, display_order: number}>>}
 */
const tags = ref([])

/**
 * Composable function for managing tags.
 *
 * @returns {{
 *   tags: import('vue').Ref<Array<{ id: number, name: string, swatch_color: string | null, type: string, display_order: number }>>,
 *   tagsByDisplayOrder: import('vue').ComputedRef<Array<{ id: number, name: string, swatch_color: string | null, type: string, display_order: number }>>,
 *   findTagById: (id: number) => { id: number, name: string, swatch_color: string | null, type: string, display_order: number } | undefined,
 *   findTagsByType: (type: string) => Array<{ id: number, name: string, swatch_color: string | null, type: string, display_order: number }>,
 *   setTags: (list: Array<{ id: number, name: string, swatch_color: string | null, type: string, display_order: number }>) => void,
 *   addTag: (tag: { id: number, name: string, swatch_color: string | null, type: string, display_order: number }) => void,
 *   setTag: (id: number, tag: { id: number, name: string, swatch_color: string | null, type: string, display_order: number }) => void,
 *   removeTag: (id: number) => void
 * }}
 */
export const useTags = () => {
  const { scriptConfig } = useApp()

  tags.value = [...(scriptConfig('tags') || [])]

  watchEffect(() => {
    scriptConfig('tags', [...tags.value])
  })

  /**
   * @type {import('vue').ComputedRef<Array<{id: number, name: string, swatch_color: string | null, type: string, display_order: number}>>}
   */
  const tagsByDisplayOrder = computed(() =>
    orderBy(tags.value, 'display_order')
  )

  /**
   * Finds index of a tag by its ID.
   *
   * @param {number|string} id - The tag ID.
   * @returns {number} - Index of the tag.
   */
  function idx(id) {
    return tags.value.findIndex(tag => tag.id == id)
  }

  function findTagById(id) {
    return tagsByDisplayOrder.value.find(t => t.id == id)
  }

  function findTagsByType(type) {
    return tagsByDisplayOrder.value.filter(t => t.type == type)
  }

  function setTags(list) {
    tags.value = list
  }

  function addTag(tag) {
    tags.value.push(tag)
  }

  function setTag(id, tag) {
    tags.value[idx(id)] = tag
  }

  function removeTag(id) {
    tags.value.splice(idx(id), 1)
  }

  return {
    tags,
    tagsByDisplayOrder,

    findTagById,
    findTagsByType,
    setTags,
    setTag,
    addTag,
    removeTag,
  }
}

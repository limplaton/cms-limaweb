
import { computed, ref } from 'vue'
import { createGlobalState } from '@vueuse/core'
import orderBy from 'lodash/orderBy'

import { useLoader } from '@/Core/composables/useLoader'

export const useBrands = createGlobalState(() => {
  const { setLoading, isLoading: brandsAreBeingFetched } = useLoader()

  const brands = ref([])

  const brandsByName = computed(() => orderBy(brands.value, 'name'))

  const orderedBrands = computed(() =>
    orderBy(brands.value, ['is_default', 'name'], ['desc', 'asc'])
  )

  // Only excuted once
  fetchBrands()

  function idx(id) {
    return brands.value.findIndex(brand => brand.id == id)
  }

  function removeBrand(id) {
    brands.value.splice(idx(id), 1)
  }

  function addBrand(brand) {
    if (brand.is_default) {
      unmarkAllAsDefault()
    }

    brands.value.push(brand)
  }

  function setBrand(id, brand) {
    if (brand.is_default) {
      unmarkAllAsDefault()
    }

    brands.value[idx(id)] = brand
  }

  function patchBrand(id, brand) {
    if (brand.is_default) {
      unmarkAllAsDefault()
    }

    const brandIndex = idx(id)

    brands.value[brandIndex] = Object.assign(brands.value[brandIndex], brand)
  }

  async function fetchBrand(id, config) {
    const { data } = await Innoclapps.request(`/brands/${id}`, config)

    return data
  }

  async function deleteBrand(id) {
    await Innoclapps.request().delete(`/brands/${id}`)
    removeBrand(id)
  }

  function unmarkAllAsDefault() {
    brands.value.forEach(brand => {
      brand.is_default = false
    })
  }

  function fetchBrands(config) {
    setLoading(true)

    Innoclapps.request('/brands', config)
      .then(({ data }) => (brands.value = data))
      .finally(() => setLoading(false))
  }

  return {
    brands,
    brandsByName,
    orderedBrands,
    brandsAreBeingFetched,

    addBrand,
    removeBrand,
    setBrand,
    patchBrand,

    fetchBrands,
    fetchBrand,
    deleteBrand,
    unmarkAllAsDefault,
  }
})

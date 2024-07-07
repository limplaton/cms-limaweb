
import { ref } from 'vue'
import { createGlobalState } from '@vueuse/core'

import { useLoader } from '@/Core/composables/useLoader'

export const useProducts = createGlobalState(() => {
  const {
    setLoading: setLimitedLoading,
    isLoading: limitedNumberOfProductsLoading,
  } = useLoader()

  const limitedNumberOfActiveProducts = ref([])
  const limitedNumberOfActiveProductsRetrieved = ref(false)

  async function fetchProduct(id, config) {
    const { data } = await Innoclapps.request(`/products/${id}`, config)

    return data
  }

  async function fetchProductByName(name) {
    const { data } = await Innoclapps.request('/products/search', {
      params: {
        q: name,
        search_fields: 'name:=',
      },
    })

    return data.length > 0 ? data[0] : null
  }

  async function retrieveLimitedNumberOfActiveProducts(limit = 100) {
    if (limitedNumberOfActiveProductsRetrieved.value) {
      return limitedNumberOfActiveProducts.value
    }

    setLimitedLoading(true)

    try {
      const { data } = await fetchActiveProducts({ params: { take: limit } })

      limitedNumberOfActiveProducts.value = data

      return data
    } finally {
      setLimitedLoading(false)
      limitedNumberOfActiveProductsRetrieved.value = true
    }
  }

  function fetchActiveProducts(config) {
    return Innoclapps.request('/products/active', config)
  }

  return {
    limitedNumberOfActiveProducts,
    limitedNumberOfActiveProductsRetrieved,
    limitedNumberOfProductsLoading,

    fetchProduct,
    fetchProductByName,
    retrieveLimitedNumberOfActiveProducts,
    fetchActiveProducts,
  }
})

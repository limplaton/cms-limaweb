
import { createRouter, createWebHistory } from 'vue-router'

/**
 * Scroll behavior
 *
 * @param  {Object} to
 * @param  {Object} from
 * @param  {Object|undefined} savedPosition
 *
 * @return {Object}
 */
function scrollBehavior(to, from, savedPosition) {
  if (savedPosition) {
    return savedPosition
  }

  if (to.hash) {
    return { el: to.hash }
  }

  if (to.meta && to.meta.scrollToTop === false) {
    return {}
  }

  return { left: 0, top: 0 }
}

const router = createRouter({
  scrollBehavior,
  history: createWebHistory(),
  routes: [],
})

export default router

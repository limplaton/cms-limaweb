
import IVerticalNavigationComponent from './IVerticalNavigation.vue'
import IVerticalNavigationCollapsibleComponent from './IVerticalNavigationCollapsible.vue'
import IVerticalNavigationItemComponent from './IVerticalNavigationItem.vue'

// Components
export const IVerticalNavigation = IVerticalNavigationComponent
export const IVerticalNavigationCollapsible =
  IVerticalNavigationCollapsibleComponent
export const IVerticalNavigationItem = IVerticalNavigationItemComponent

// Plugin
export const IVerticalNavigationPlugin = {
  install(app) {
    app.component('IVerticalNavigation', IVerticalNavigationComponent)

    app.component(
      'IVerticalNavigationCollapsible',
      IVerticalNavigationCollapsibleComponent
    )
    app.component('IVerticalNavigationItem', IVerticalNavigationItemComponent)
  },
}

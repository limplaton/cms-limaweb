
import ITabComponent from './ITab.vue'
import ITabGroupComponent from './ITabGroup.vue'
import ITabListComponent from './ITabList.vue'
import ITabPanelComponent from './ITabPanel.vue'
import ITabPanelsComponent from './ITabPanels.vue'

// Components
export const ITabGroup = ITabGroupComponent
export const ITabList = ITabListComponent
export const ITab = ITabComponent
export const ITabPanels = ITabPanelsComponent
export const ITabPanel = ITabPanelComponent

// Plugin
export const ITabsPlugin = {
  install(app) {
    app.component('ITabGroup', ITabGroupComponent)
    app.component('ITabList', ITabListComponent)
    app.component('ITab', ITabComponent)
    app.component('ITabPanels', ITabPanelsComponent)
    app.component('ITabPanel', ITabPanelComponent)
  },
}

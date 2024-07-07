
import IButtonComponent from './IButton.vue'
import IButtonCopyComponent from './IButtonCopy.vue'
import IButtonLinkComponent from './IButtonLink.vue'

// Components
export const IButton = IButtonComponent
export const IButtonLink = IButtonLinkComponent
export const IButtonCopy = IButtonCopyComponent

// Plugin
export const IButtonPlugin = {
  install(app) {
    app.component('IButton', IButtonComponent)
    app.component('IButtonLink', IButtonLinkComponent)
    app.component('IButtonCopy', IButtonCopyComponent)
  },
}


import RenderComponent from './utils/renderComponent'
import TooltipComponent from './ITooltip.vue'
import tooltipDirective from './iTooltipDirective'

// Component
export const ITooltip = TooltipComponent

// Directive
export const Tooltip = tooltipDirective

// Plugin
export const ITooltipPlugin = {
  install(app, options = {}) {
    app.component('ITooltip', TooltipComponent)
    app.directive('i-tooltip', tooltipDirective)

    const tooltipComponent = new RenderComponent({
      el: document.body,
      rootComponent: TooltipComponent,
      props: options,
      appContext: app._context,
    })

    tooltipComponent.mount()
  },
}

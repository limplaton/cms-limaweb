
import IStepCircleComponent from './IStepCircle.vue'
import IStepsCircleComponent from './IStepsCircle.vue'

// Components
export const IStepCircle = IStepCircleComponent
export const IStepsCircle = IStepsCircleComponent

// Plugin
export const IStepsPlugin = {
  install(app) {
    app.component('IStepCircle', IStepCircleComponent)
    app.component('IStepsCircle', IStepsCircleComponent)
  },
}

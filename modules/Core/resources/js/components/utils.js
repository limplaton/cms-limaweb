
import { Comment, Fragment, h, isVNode, Text } from 'vue'

export function slotNodesOr(fallback, slot) {
  if (!slot) return fallback
  let slotNode = slot()
  if (isVNodeEmpty(slotNode)) return fallback

  return slotNode
}

export function isVNodeEmpty(vnode) {
  return (
    !vnode ||
    asArray(vnode).every(
      vnode =>
        vnode.type === Comment ||
        (vnode.type === Text && !vnode.children?.length) ||
        (vnode.type === Fragment && !vnode.children?.length)
    )
  )
}

export function asArray(arg) {
  return Array.isArray(arg) ? arg : arg != null ? [arg] : []
}

// Not used at this time, using the "isVNodeEmpty", probably this one works too.
export function ensureValidVNode(vnodes) {
  return vnodes.some(child => {
    if (!isVNode(child)) return true
    if (child.type === Comment) return false
    if (child.type === Fragment && !ensureValidVNode(child.children))
      return false

    return true
  })
    ? vnodes
    : null
}

// Not used at this timem useful for rendering components
// and to pass props to the component being rendered.
export function render(component, props, children, mainProps) {
  const filteredProps = {}

  if (typeof component !== 'string' && component.props) {
    const propsIsArray = Array.isArray(component.props)

    // Iterate over mainProps and include only those defined in the component being rendered props.
    Object.keys(mainProps).forEach(propName => {
      if (
        (propsIsArray && component.props.includes(propName)) ||
        (!propsIsArray && propName in component.props)
      ) {
        filteredProps[propName] = mainProps[propName]
      }
    })
  }

  const mergedProps = { ...(props || {}), ...filteredProps }

  return h(component, mergedProps, children)
}

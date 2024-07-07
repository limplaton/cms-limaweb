
export function useGate() {
  const gate = Innoclapps.app.config.globalProperties.$gate

  return { gate }
}


import Call from '../../Call'

class TwilioCall extends Call {
  /**
   * Initialize new Call instance
   *
   * @param Twilio.Call {Call}
   *
   * @return {Void}
   */
  constructor(call) {
    super(call)

    // eslint-disable-next-line no-unused-vars
    call.on('accept', call => this.emitter.emit(this.events.Accept, this))

    // eslint-disable-next-line no-unused-vars
    call.on('disconnect', call =>
      this.emitter.emit(this.events.Disconnect, this)
    )
    call.on('reject', () => this.emitter.emit(this.events.Reject))
    call.on('cancel', () => this.emitter.emit(this.events.Cancel))

    // eslint-disable-next-line no-unused-vars
    call.on('mute', (isMuted, Call) =>
      this.emitter.emit(this.events.Mute, { isMuted: isMuted, Call: this })
    )
    call.on('Error', error => this.emitter.emit(this.events.Error, error))
  }

  /**
   * Accepts an incoming voice call
   *
   * @return {Void}
   */
  accept() {
    this.instance.accept()
  }

  /**
   * Reject an incoming call
   *
   * @return {Void}
   */
  reject() {
    this.instance.reject()
  }

  /**
   * Disconnect the current call
   *
   * @return {Void}
   */
  disconnect() {
    this.instance.disconnect()
  }

  /**
   * Mutes or unmutes the local user's input audio based on the Boolean shouldMute argument you provide.
   *
   * @return {Void}
   */
  mute(shouldMute = true) {
    this.instance.mute(shouldMute)
  }

  /**
   * Returns a Boolean indicating whether the input audio of the local Device instance is muted.
   *
   * @return {Boolean}
   */
  get isMuted() {
    return this.instance.isMuted()
  }
}

export default TwilioCall

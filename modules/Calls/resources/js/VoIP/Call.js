
/* eslint-disable no-unused-vars */
/* eslint-disable getter-return */
import mitt from 'mitt'

class Call {
  /**
   * Initialize new Call instance
   *
   * @param call {Call}
   *
   * @return {Void}
   */
  constructor(call) {
    // Call instance
    this.instance = call
    // Emitter
    this.emitter = mitt()

    // Overwrite events object via the client device call instance if needed to change the names, see the "on" method
    this.events = {
      // Emitted when the Call is accepted
      Accept: 'accept',
      // Emitted when the Call is disconnected
      Disconnect: 'disconnect',
      // Emitted when the Incoming call is rejected
      Reject: 'reject',
      // Emitted when the Call instance has been canceled (caller cancelled, nobody picked up).
      Cancel: 'Cancel',
      // Emitted when the input audio associated with the Call instance is muted or unmuted
      Mute: 'mute',
      // Emitted when the Call instance receives an error.
      Error: 'error',
    }
  }

  /**
   * @abstract
   *
   * Accepts an incoming voice call
   *
   * @return {Void}
   */
  accept() { }

  /**
   * @abstract
   *
   * Reject an incoming call
   *
   * @return {Void}
   */
  reject() { }

  /**
   * @abstract
   *
   * Disconnect the current call
   *
   * @return {Void}
   */
  disconnect() { }

  /**
   * @abstract
   *
   * Mutes or unmutes the local user's input audio based on the Boolean shouldMute argument you provide.
   *
   * @return {Void}
   */
  mute(shouldMute = true) { }

  /**
   * @abstract
   *
   * Returns a Boolean indicating whether the input audio of the local Device instance is muted.
   *
   * @return {Boolean}
   */
  get isMuted() { }

  /**
   * Add event listener to the Call instance
   *
   * @param  {String}   eventName
   * @param  {Function} callback
   *
   * @return {Call}
   */
  on(eventName, callback) {
    if (!Object.hasOwn(this.events, eventName)) {
      console.error(`${eventName} event listener does not exists for the call.`)

      return this
    }

    this.emitter.on(this.events[eventName], callback)

    return this
  }
}

export default Call

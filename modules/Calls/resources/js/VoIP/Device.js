
import mitt from 'mitt'

class Device {
  /**
   * Initialize new Device instance
   *
   * @return {Void}
   */
  constructor() {
    // Device instance
    this.instance = null
    // Emitter
    this.emitter = mitt()

    // Overwrite events object via the client device instance if needed to change the names, see the "on" method
    this.events = {
      // Emitted when the Device instance is registering to receive incoming calls.
      Registering: 'registering',
      // Emitted when the Device instance is registered and able to receive incoming calls.
      Registered: 'registered',
      // Emitted when the Device instance receives an error
      Error: 'error',
    }
  }

  /**
   * @abstract
   *
   *
   * Create new call
   *
   * This method will return a Call object. You should keep track of this Call object to monitor/modify the active call.
   *
   * To end the call, you can use the .disconnect() method on the Call object or use the device.disconnectAll() method.
   *
   * @param  {Object|String} options
   *
   * @return {Promise.Call}
   */
  // eslint-disable-next-line no-unused-vars
  async createCall(options) { }

  /**
   * @abstract
   *
   * Connect the device
   *
   * @return {Promise.Device}
   */
  async connect() { }

  /**
   * @abstract
   *
   * Register incoming call event
   *
   * @param {Function} callback
   * @return {Void}
   */
  // eslint-disable-next-line no-unused-vars
  incoming(callback) { }

  /**
   * @abstract
   *
   * Unregister the Device instance. This will prevent the Device instance from receiving incoming calls.
   */
  unregister() { }

  /**
   * Add event listener to the Device instance
   *
   * @param  {String}   eventName
   * @param  {Function} callback
   *
   * @return {Device}
   */
  on(eventName, callback) {
    if (!Object.hasOwn(this.events, eventName)) {
      console.error(
        `${eventName} event listener does not exists for this device.`
      )

      return this
    }

    this.emitter.on(this.events[eventName], callback)

    return this
  }
}

export default Device

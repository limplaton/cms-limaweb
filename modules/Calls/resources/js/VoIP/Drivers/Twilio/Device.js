
import isString from 'lodash/isString'

import Device from '../../Device'

import Call from './Call'

let TwilioSDKDevice
  // eslint-disable-next-line padding-line-between-statements
  ; (async () => {
    await import('@twilio/voice-sdk/dist/twilio.min.js')
    TwilioSDKDevice = window['Twilio'].Device
  })()

class Twilio extends Device {
  /**
   * Initialize new Twilio instance
   *
   * @return {Void}
   */
  constructor() {
    super()
    this.incomingCallbacks = []
    this.instance = null
  }

  /**
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
  async createCall(options) {
    let params = {
      ...(isString(options)
        ? {
          To: options,
        }
        : options),
      ...{ viaApp: true },
    }

    const TwilioCall = await this.instance.connect({
      params: params,
    })

    return new Call(TwilioCall)
  }

  /**
   * Register incoming call event
   *
   * @param {Function} callback
   * @return {Void}
   */
  incoming(callback) {
    this.incomingCallbacks.push(callback)
  }

  /**
   * @abstract
   *
   * Unregister the Device instance. This will prevent the Device instance from receiving incoming calls.
   */
  unregister() {
    this.instance.unregister()
  }

  /**
   * Get the token access token
   *
   * @param  {Number} ttl
   *
   * @return {Promise.String}
   */
  async getAccessToken(ttl) {
    let { data } = await Innoclapps.request('/voip/token', {
      params: { ttl: ttl },
    })

    return data.token
  }

  /**
   * Connect to Twilio Device
   *
   * @return {Promise.Device}
   */
  async connect() {
    // https://www.twilio.com/docs/voice/sdks/javascript/migrating-to-js-voice-sdk-20#make-sure-the-access-token-is-kept-up-to-date
    const ttl = 600000 // 10 minutes
    const refreshBuffer = 30000 // 30 seconds

    const token = await this.getAccessToken(ttl)
    const device = this.createTwilioDevice(token)

    this.instance = device

    this.instance.on('error', (twilioError, TwilioCall) =>
      this.emitter.emit(this.events.Error, {
        Call: new Call(TwilioCall),
        error: twilioError,
      })
    )

    // eslint-disable-next-line no-unused-vars
    this.instance.on('registering', TwilioDevice =>
      this.emitter.emit(this.events.Registering, this)
    )

    // eslint-disable-next-line no-unused-vars
    this.instance.on('registered', TwilioDevice =>
      this.emitter.emit(this.events.Registered, this)
    )

    this.instance.on('incoming', TwilioCall => {
      this.incomingCallbacks.forEach(callback => callback(new Call(TwilioCall)))
    })

    setInterval(async () => {
      const newToken = await this.getAccessToken(ttl)
      this.instance.updateToken(newToken)
    }, ttl - refreshBuffer) // Gives us a generous 30-second buffer

    return this
  }

  /**
   * Create Twilio device instance
   *
   * @param  {string} token
   *
   * @return {Object}
   */
  createTwilioDevice(token) {
    return new TwilioSDKDevice(token, {
      logLevel: process.env.NODE_ENV !== 'production' ? 'debug' : 'silent',
    })
  }
}

export default Twilio

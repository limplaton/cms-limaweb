
import Twilio from './Drivers/Twilio'

class VoIP {
  /**
   * Initialize new VoIP instance
   *
   * @param  {String} client
   *
   * @return {Void}
   */
  constructor(client) {
    this.client = client
    this.device = null
    this.readyCallbacks = []
    this.callCallbacks = []
  }

  /**
   * Register on call callbacks
   *
   * @param  {Function} callback
   *
   * @return {Void}
   */
  onCall(callback) {
    this.callCallbacks.push(callback)
  }

  /**
   * Register callbacks when the device is ready
   *
   * @param  {Function} callback
   *
   * @return {Void}
   */
  ready(callback) {
    this.readyCallbacks.push(callback)
  }

  /**
   * Make new call
   *
   * @param  {Object|String} options
   *
   * @return {Promise.Call}
   */
  async makeCall(options) {
    const call = await this.device.createCall(options)

    this.callCallbacks.forEach(callback =>
      callback({ Call: call, isIncoming: false })
    )

    return call
  }

  /**
   * Initialize new device ready to make and receive calls
   *
   * @return {Void}
   */
  connect() {
    if (this.client == 'twilio') {
      this.device = new Twilio.Device()
    } else {
      console.error('VoIP client not found.')

      return
    }

    this.device.connect().then(device => {
      device.incoming(Call => {
        this.callCallbacks.forEach(callback =>
          callback({ Call: Call, isIncoming: true })
        )
      })

      this.readyCallbacks.forEach(callback => callback(device))
      this.readyCallbacks = []
    })
  }

  /**
   * Get the Vue component responsible for handling the phone call
   *
   * @return {String}
   */
  get callComponent() {
    if (this.client == 'twilio') {
      return Twilio.CallComponent
    } else {
      console.error('VoIP call component not found.')

      return ''
    }
  }
}

export default VoIP

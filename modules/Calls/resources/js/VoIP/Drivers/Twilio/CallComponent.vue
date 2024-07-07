<template>
  <IModal
    id="callingActivation"
    cancel-variant="success"
    :cancel-text="$t('calls::call.activate_voip')"
    :title="$t('calls::call.activation_required')"
    @hidden="callingModalHiddenEvent"
  >
    <!-- Removes the ok button by providing an empty slot -->
    <template #modal-ok>&nbsp;</template>

    <ITextDark>
      {{
        $t('calls::call.activation_gesture_required', {
          askForActivationIn: askForActivationIn,
        })
      }}
    </ITextDark>
  </IModal>

  <div
    v-show="showCallComponent"
    class="sticky top-0 -mx-4 border-b border-neutral-300 bg-white/50 px-7 py-3 dark:border-neutral-700 dark:bg-neutral-700/30"
  >
    <IAlert
      class="mb-4"
      variant="danger"
      :show="error !== null"
      dismissible
      @dismissed="error = null"
    >
      <IAlertBody>{{ error }}</IAlertBody>
    </IAlert>

    <div class="flex items-center px-4">
      <div class="grow">
        <div class="flex items-center space-x-4">
          <p
            class="text-base font-medium"
            :class="
              isCallInProgress || (isIncoming && !isCallInProgress)
                ? 'text-success-500'
                : 'text-neutral-800 dark:text-neutral-100'
            "
            v-text="cardHeader"
          />

          <IBadge
            v-show="isCallInProgress || (lastConnectedNumber && duration)"
            variant="info"
          >
            <Icon icon="Clock" class="mr-1 size-4" />
            {{ isCallInProgress || lastConnectedNumber ? duration : null }}
          </IBadge>

          <div>
            <div class="ml-0 mt-2 space-x-2 sm:ml-2 sm:mt-0">
              <IButton
                v-if="isCallInProgress"
                v-show="isMuted"
                v-i-tooltip="$t('calls::call.unmute')"
                variant="secondary"
                icon="VolumeUp"
                pill
                @click="$options.call.mute(false)"
              />

              <IButton
                v-if="isCallInProgress"
                v-show="!isMuted"
                v-i-tooltip="$t('calls::call.mute')"
                variant="secondary"
                icon="VolumeOff"
                pill
                @click="$options.call.mute(true)"
              />

              <IButton
                v-show="isIncoming && !isCallInProgress"
                v-i-tooltip="$t('calls::call.answer')"
                variant="success"
                icon="Phone"
                pill
                @click="$options.call.accept()"
              />

              <IButton
                v-show="isCallInProgress"
                v-i-tooltip="$t('calls::call.hangup')"
                variant="danger"
                icon="XSolid"
                pill
                @click="$options.call.disconnect()"
              />

              <IButton
                v-show="isIncoming && !isCallInProgress"
                v-i-tooltip="$t('calls::call.reject')"
                variant="danger"
                icon="PhoneXMark"
                pill
                @click="$options.call.reject()"
              />
            </div>
          </div>
        </div>
      </div>

      <div>
        <IButton
          v-show="visible === true"
          v-i-tooltip.left="$t('calls::call.hide_bar')"
          icon="XSolid"
          basic
          @click="hideCallHandler"
        />
      </div>
    </div>

    <div class="mt-4 flex flex-col items-center md:flex-row">
      <div
        class="flex flex-col items-center space-y-2 sm:flex-row md:space-x-2 md:space-y-0"
      >
        <IDropdown>
          <IDropdownButton icon="Megaphone" basic>
            <span
              class="max-w-[13rem] truncate"
              v-text="audioDevices.speaker.selected?.label"
            />
          </IDropdownButton>

          <IDropdownMenu>
            <IDropdownItem
              v-for="speaker in audioDevices.speaker.items"
              :key="speaker.id"
              :text="speaker.label"
              @click="
                getDevice().instance.audio.speakerDevices.set([speaker.id])
              "
            />
          </IDropdownMenu>
        </IDropdown>

        <IDropdown>
          <IDropdownButton icon="Microphone" basic>
            <span
              class="max-w-[13rem] truncate"
              v-text="audioDevices.ringtone.selected?.label"
            />
          </IDropdownButton>

          <IDropdownMenu>
            <IDropdownItem
              v-for="ringtone in audioDevices.ringtone.items"
              :key="ringtone.id"
              :text="ringtone.label"
              @click="
                getDevice().instance.audio.ringtoneDevices.set([ringtone.id])
              "
            />
          </IDropdownMenu>
        </IDropdown>

        <ILink
          class="hidden lg:block"
          :text="$t('calls::call.unknown_devices')"
          @click="getMediaDevices"
        />
      </div>

      <div
        v-show="isCallInProgress"
        class="ml-5 flex space-x-3 pl-5 sm:border-l sm:border-neutral-300"
      >
        <div class="flex items-center">
          <Icon
            v-i-tooltip.bottom="$t('calls::call.speaker_volume')"
            icon="VolumeUp"
            class="size-5 text-current dark:text-neutral-200"
          />

          <div ref="outputVolumeBar" class="ml-1 h-4 rounded-md"></div>
        </div>

        <div class="flex items-center">
          <Icon
            v-i-tooltip.bottom="$t('calls::call.mic_volume')"
            icon="Microphone"
            class="h-4 w-5 text-current dark:text-neutral-200"
          />

          <div ref="inputVolumeBar" class="ml-1 h-4 rounded-md"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import throttle from 'lodash/throttle'
import { DateTime } from 'luxon'

import { strTruncate } from '@/Core/utils'

export default {
  data: () => ({
    error: null,
    isCallInProgress: false,
    callStartedDate: null,
    callEndedDate: null,
    isIncoming: false,
    duration: null,
    durationInterval: null,
    visible: false,
    isMuted: false,
    lastConnectedNumber: null,
    personDisplayName: null,
    // Minutes
    askForActivationIn: 1,
    audioDevices: {
      speaker: {
        items: [],
        selected: null,
      },

      ringtone: {
        items: [],
        selected: null,
      },
    },
  }),

  computed: {
    /**
     * Indicates whether the call component should be shown
     *
     * @return {Boolean}
     */
    showCallComponent() {
      return this.visible === true || this.isCallInProgress
    },

    /**
     * Get the card headerr
     *
     * @return {String}
     */
    cardHeader() {
      // Incoming call
      if (this.isIncoming && !this.isCallInProgress) {
        return this.$t('calls::call.new_from', {
          number: this.personDisplayName || this.lastConnectedNumber,
        })
      }
      // Already connected call
      else if (this.isCallInProgress) {
        return this.$t('calls::call.connected_with', {
          number: this.personDisplayName || this.lastConnectedNumber,
        })
      }
      // Ended call, shows the last connected number
      else if (this.lastConnectedNumber) {
        return this.$t('calls::call.ended', {
          number: this.personDisplayName || this.lastConnectedNumber,
        })
      }

      return ''
    },
  },

  watch: {
    lastConnectedNumber: function (newVal) {
      if (newVal) {
        this.findDisplayNameForCall(newVal)
      }
    },
  },

  created() {
    document.documentElement.addEventListener(
      'mousedown',
      throttle(this.bootVoIPDevice, 500)
    )

    window.addEventListener('beforeunload', () => {
      this.getDevice() && this.getDevice().unregister()
    })

    // If the user did not clicked anything in 1 minute
    // We will show a modal to activate the calling functionality
    // As most browsers requires user gesture to enable audio/mic
    setTimeout(
      () => {
        if (!this.getDevice()) {
          this.$dialog.show('callingActivation')
        }
      },
      this.askForActivationIn * 60 * 1000
    )
  },

  methods: {
    strTruncate,

    /**
     * Find the display name for the call
     *
     * @param {String} phoneNumber
     *
     * @return {Void}
     */
    findDisplayNameForCall(phoneNumber) {
      let queryString = {
        search_fields: 'phones.number:=',
        q: phoneNumber,
      }

      Promise.all([
        Innoclapps.request('/contacts/search', { params: queryString }),
        Innoclapps.request('/companies/search', { params: queryString }),
      ]).then(values => {
        let contacts = values[0].data
        let companies = values[1].data

        if (contacts.length > 0) {
          this.personDisplayName = contacts[0].display_name
        } else if (companies.length > 0) {
          this.personDisplayName = companies[0].display_name
        } else {
          this.personDisplayName = null
        }
      })
    },

    /**
     * Manually hide the call handler cards
     *
     * @return {Void}
     */
    hideCallHandler() {
      this.visible = false
      // Clear the duration so the next time call is connected
      // to not see the previous durection during the call initialization
      this.duration = null
    },

    /**
     * Handle the calling modal hidden event
     *
     * @return {Void}
     */
    callingModalHiddenEvent() {
      Innoclapps.success(this.$t('calls::call.voip_activated'))
    },

    /**
     * Get the duration of the current call
     *
     * @return {Void}
     */
    updateDuration() {
      let endCallDate = this.callEndedDate || DateTime.now()
      let duration = endCallDate.diff(this.callStartedDate)

      let minutes = duration.as('minutes')
      let seconds = duration.as('seconds') % 60

      this.duration =
        (Math.floor(minutes) < 10
          ? '0' + Math.floor(minutes)
          : Math.floor(minutes)) +
        ':' +
        (Math.floor(seconds) < 10
          ? '0' + Math.floor(seconds)
          : Math.floor(seconds))
    },

    /**
     * Prepare the call component
     *
     * @return {Void}
     */
    prepareComponent(device) {
      try {
        // eslint-disable-next-line no-unused-vars
        device.on('Registering', device => {
          // console.log('Registering')
        })

        device.on('Registered', device => {
          // console.log('Registered')
          device.instance.audio.on('deviceChange', this.updateAllDevices)
        })

        // eslint-disable-next-line no-unused-vars
        device.on('Error', ({ error, Call }) => (this.error = error.message))

        this.setDevice(device)

        this.$voip.onCall(({ Call, isIncoming }) => {
          this.callEndedDate = null
          this.callStartedDate = null
          this.duration = null
          this.$options.call = Call
          this.isIncoming = isIncoming
          this.visible = true

          if (isIncoming) {
            this.lastConnectedNumber = Call.instance.parameters.From
          } else {
            this.lastConnectedNumber = Call.instance.customParameters.get('To')
          }

          // eslint-disable-next-line no-unused-vars
          Call.on('Mute', ({ isMuted, Call }) => (this.isMuted = isMuted))

          Call.on('Error', error => {
            this.visible = true
            this.call = null
            this.isIncoming = false
            this.error = error.message
          })

          Call.on('Accept', Call => {
            // console.log('Accept')
            this.isCallInProgress = true
            this.callStartedDate = DateTime.now()

            this.bindVolumeIndicators(Call.instance)
            this.durationInterval = setInterval(this.updateDuration, 1000)
          })

          Call.on('Cancel', () => {
            // console.log('Cancel')
            this.isCallInProgress = false
            this.visibility = true
            this.isIncoming = false
            this.$options.call = null
          })

          Call.on('Reject', () => {
            // console.log('Reject')
            this.duration = null
            this.callStartedDate = null
            this.isIncoming = false
            this.isCallInProgress = false
            this.$options.call = null
          })

          // eslint-disable-next-line no-unused-vars
          Call.on('Disconnect', Call => {
            // console.log('Disconnect')
            // When disconnected, set visibility to true so the user can see
            // the call data, then he can decide whether to close the call handler bar or not
            this.visible = true
            this.isCallInProgress = false
            this.$options.call = null
            this.isMuted = false
            this.isIncoming = false
            this.callEndedDate = DateTime.now()
            clearInterval(this.durationInterval)
          })
        })
      } catch (error) {
        // Catch not supported error and any other critical errors
        // twilio.js wasn't able to find WebRTC browser support. This is most likely because this page is served over http rather than https, which does not support WebRTC in many browsers. Please load this page over https and try again.
        this.error = error.message
        this.visible = true
      }
    },

    /**
     * Set the VoIP device
     */
    setDevice(device) {
      this.$options.device = device
    },

    /**
     * Get the VoIP device
     */
    getDevice() {
      return this.$options.device
    },

    /**
     * Boot the VoIP device (on user gesture)
     *
     * @return {Void}
     */
    bootVoIPDevice() {
      if (this.getDevice()) {
        document.documentElement.removeEventListener(
          'mousedown',
          this.bootVoIPDevice
        )

        return
      }

      this.$voip.ready(this.prepareComponent)
      this.$voip.ready(this.getMediaDevices)
      this.$voip.ready(device => device.instance.register())
      this.$voip.connect()
    },

    /**
     * Update all devices
     *
     * @return {Void}
     */
    updateAllDevices() {
      this.updateDevices(
        this.getDevice().instance.audio.speakerDevices.get(),
        'speaker'
      )

      this.updateDevices(
        this.getDevice().instance.audio.ringtoneDevices.get(),
        'ringtone'
      )
    },

    /**
     * Update the available audio devices
     *
     * @param  {Array} selected Selected devices
     * @param  {String} type
     *
     * @return {Void}
     */
    updateDevices(selected, type) {
      this.audioDevices[type].items = []
      this.audioDevices[type].selected = null

      let available = this.getDevice().instance.audio.availableOutputDevices

      available.forEach((device, id) => {
        let isActive = selected.size === 0 && id === 'default'

        selected.forEach(function (device) {
          if (device.deviceId === id) {
            isActive = true
          }
        })

        let item = {
          label: device.label,
          id: id,
        }

        this.audioDevices[type].items.push(item)

        if (isActive) {
          this.audioDevices[type].selected = item
        }
      })
    },

    /**
     * Get the available audio devices from navigator
     *
     * @return {Array}
     */
    getMediaDevices() {
      // https://stackoverflow.com/questions/52479734/domexception-requested-device-not-found-getusermedia
      navigator.mediaDevices
        .getUserMedia({
          audio: true,
        })
        .then(this.updateAllDevices)
        .catch(error => (this.error = error))
    },

    /**
     * Bind the volume indicators
     *
     * @param  {Object} Twilio.Call
     *
     * @return {Void}
     */
    bindVolumeIndicators(TwilioCall) {
      TwilioCall.on('volume', (inputVolume, outputVolume) => {
        let inputColor = 'red'

        if (inputVolume < 0.5) {
          inputColor = 'green'
        } else if (inputVolume < 0.75) {
          inputColor = 'yellow'
        }

        this.$refs.inputVolumeBar.style.width =
          Math.floor(inputVolume * 300) + 'px'
        this.$refs.inputVolumeBar.style.background = inputColor

        let outputColor = 'red'

        if (outputVolume < 0.5) {
          outputColor = 'green'
        } else if (outputVolume < 0.75) {
          outputColor = 'yellow'
        }

        this.$refs.outputVolumeBar.style.width =
          Math.floor(outputVolume * 300) + 'px'
        this.$refs.outputVolumeBar.style.background = outputColor
      })
    },
  },
}
</script>

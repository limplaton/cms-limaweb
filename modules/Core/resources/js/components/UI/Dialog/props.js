
import { randomString } from '@/Core/utils'

export default {
  form: Boolean,
  overlay: { type: Boolean, default: true },
  visible: Boolean, // v-model
  title: String,
  subTitle: String,
  busy: Boolean,

  okText: [String, Number],
  okVariant: { type: String, default: 'primary' },
  okDisabled: Boolean,
  okLoading: Boolean,

  cancelText: [String, Number],
  cancelDisabled: Boolean,

  hideFooter: Boolean,
  hideHeader: Boolean,
  hideHeaderClose: Boolean,
  initialFocus: { type: Object, default: null },
  staticBackdrop: Boolean, // prevent dialog close on esc and backdrop click

  size: {
    type: String,
    default: 'md',
    validator: value => ['xs', 'sm', 'md', 'lg', 'xl', 'xxl'].includes(value),
  },

  id: {
    type: String,
    default() {
      return randomString()
    },
  },
}

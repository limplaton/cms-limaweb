
export default {
  operand: { required: true },
  isNullable: { required: true, type: Boolean },
  index: { required: true, type: Number },
  query: { type: Object, required: false },
  rule: { type: Object, required: true },
  labels: { required: true },
  operator: { required: true },
  isBetween: Boolean,
  readOnly: Boolean,
}

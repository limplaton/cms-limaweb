
export default {
  /**
   * Represents the currently selected value, akin to the `value` attribute
   * on an `<input>` element. This property can be used to track the selected
   * value programmatically. Changes to the model value can be listened to
   * via the 'change' event using `v-on`.
   *
   * @type {object|string|null} - The selected value, which can be an object,
   * a string, or `null` if no value is selected.
   */
  modelValue: {},

  /**
   * Specifies the CSS class(es) to be applied to the dropdown list. Accepts a
   * string, an array of strings, or an object specifying class bindings.
   *
   * @type {String|Array|Object}
   */
  listClass: [String, Array, Object],

  /**
   * Specifies the CSS class(es) to be applied to the wrapper div of the dropdown list.
   * This property can accept a single class name as a string, an array of class names,
   * or an object for conditional class binding
   *
   * @type {String|Array|Object}
   */
  listWrapperClass: [String, Array, Object],

  /**
   * Indicates whether the options within the select dropdown can be reordered.
   * If set to true, it enables reordering functionality.
   *
   * @type {Boolean}
   */
  reorderable: Boolean,

  /**
   * Determines if the selected option's text should be truncated. This is
   * particularly useful in a single select scenario where long text might
   * need to be abbreviated.
   *
   * @type {Boolean|String}
   */
  truncate: [Boolean, String],

  /**
   * Indicates if the select component is rendered in a simple style without
   * additional styles or complex behaviors.
   *
   * @type {Boolean}
   */
  simple: Boolean,

  /**
   * Defines the icon used for toggling the dropdown menu. This property accepts
   * a string that represents the icon to be used, allowing for customization
   * of the dropdown's toggle icon. The default value is 'Selector', which can
   * be overridden with any other string that corresponds to a valid icon name.
   *
   * @type {String}
   * @default 'Selector'
   */
  toggleIcon: { type: String, default: 'Selector' },

  /**
   * Toggles the 'loading' state of the select component. When set to true,
   * a 'loading' class is added to the wrapper, allowing for custom UI
   * indicators during asynchronous operations like data fetching.
   *
   * @type {Boolean}
   */
  loading: Boolean,

  /**
   * Specifies the unique identifier (ID) for the input element. This ID can
   * be used for associating the input with a label for accessibility purposes
   * or for targeting the element with JavaScript and CSS.
   *
   * @type {String}
   */
  inputId: String,

  /**
   * Sets the name attribute for the input element. This is used to identify
   * the form data after the form is submitted, or to reference the element
   * in JavaScript when building complex forms.
   *
   * @type {String}
   */
  inputName: String,

  /**
   * Sets the tabindex attribute for the input field, controlling its position
   * in the tabbing order of the page. A higher tabindex value makes the element
   * focusable later in the sequence. Negative values can be used to remove the
   * element from the default navigation flow.
   *
   * @type {Number}
   */
  tabindex: Number,

  /**
   * Controls the autocomplete behavior of the input field. When set to 'off',
   * it instructs browsers not to predict the value for this field.
   * The default value is 'off', but it can be overridden as needed.
   *
   * @type {String}
   * @default 'off'
   */
  autocomplete: { type: String, default: 'off' },

  /**
   * Sets the placeholder text for the input field, similar to the `placeholder`
   * attribute on a standard HTML `<input>`. It displays a hint or instruction
   * within the input when it is empty.
   *
   * @type {String}
   * @default ''
   */
  placeholder: { type: String, default: '' },

  /**
   * Determines whether the dropdown list automatically scrolls to show the
   * currently selected option when opened. When set to true, the dropdown
   * will auto-scroll to the selected item, improving user experience.
   *
   * @type {Boolean}
   * @default true
   */
  autoscroll: { type: Boolean, default: true },

  /**
   * If set to true, disables the entire select component, preventing user
   * interaction. This is useful for conditionally enabling form inputs.
   *
   * @type {Boolean}
   */
  disabled: Boolean,

  /**
   * Determines whether the user can clear the selected option(s). When set to true,
   * it enables a clear control, allowing users to remove their selection easily.
   *
   * @type {Boolean}
   * @default true
   */
  clearable: { type: Boolean, default: true },

  /**
   * Equivalent to the `multiple` attribute on a standard HTML `<select>` element.
   * When true, it allows multiple options to be selected.
   *
   * @type {Boolean}
   */
  multiple: Boolean,

  /**
   * Determines if the search text in the dropdown should be cleared when an option is
   * selected. This is particularly useful for maintaining or resetting the search
   * state after selection.
   *
   * @type {Boolean}
   * @default true
   */
  clearSearchOnSelect: { type: Boolean, default: true },

  /**
   * Decides if the dropdown should automatically close after an option is selected.
   * Setting it to false keeps the dropdown open, which can be useful for multi-select
   * scenarios where users might want to make multiple selections without reopening the dropdown.
   *
   * @type {Boolean}
   * @default true
   */
  closeOnSelect: { type: Boolean, default: true },

  /**
   * Specifies the key that component should use to generate option labels when each
   * option is an object. This allows for customization of which object property
   * is used for display purposes in the dropdown.
   *
   * @type {String}
   * @default 'label'
   */
  label: { type: String, default: 'label' },

  /**
   * Generate a unique identifier for each option. If `option`
   * is an object and `Object.hasOwn(option, props.optionKey)` exists,
   * `option[props.optionKey]` is used by default, otherwise the option will be serialized to JSON.
   *
   * If you are supplying a lot of options, you should provide your own keys,
   * as JSON.stringify can be slow with lots of objects.
   *
   * The result of this function *must* be unique.
   *
   * @type {String, Function}
   * @default 'id'
   */
  optionKey: { type: [String, Function], default: 'id' },

  /**
   * Enables the creation of new options through the search input. Useful for
   * scenarios where users need to add items that are not in the pre-defined
   * options list.
   *
   * @type {Boolean}
   */
  taggable: Boolean,

  /**
   * When true, newly created tags will be added to the options list.
   */
  pushTags: Boolean,

  /**
   * A user-defined function that allows for adding new options to the select component.
   * This function is used when the component needs to handle options that are not
   * originally provided in the options list, typically used in conjunction with 'taggable'.
   *
   * @type {Function}
   */
  createOptionProvider: Function,

  /**
   * A user-defined function that provides custom comparison logic for options.
   *
   * @type {Function}
   */
  optionComparatorProvider: Function,

  /**
   * Determines whether newly added options (such as those added through 'taggable')
   * should be displayed at the end of the options list. When set to true, new options
   * are appended to the end of the existing options.
   *
   * @type {Boolean}
   */
  displayNewOptionsLast: Boolean,

  /**
   * Defines the array of options for the dropdown menu. This array can consist
   * of either strings or objects. When using an array of objects, each object should
   * contain a `label` key for display (e.g., [{label: 'This is Foo', value: 'foo'}]).
   * A custom label key can be specified using the `label` prop. If no options are
   * provided, the dropdown will be initialized with an empty array.
   *
   * @property {Array<object|string>} options - An array of strings or objects to be used as dropdown choices.
   * @property {Function} type - Constructor type for the options, which is an Array.
   * @property {Function} default - Function returning the default value, which is an empty array.
   */
  options: {
    type: Array,
    default() {
      return []
    },
  },

  /**
   * Controls whether the existing options should be filtered based on the search text.
   * When set to true, it allows dynamic filtering of options as the user types. This
   * property should not be used in conjunction with 'taggable', as they serve similar
   * but distinct purposes in handling options.
   *
   * @type {Boolean}
   * @default true
   */
  filterable: { type: Boolean, default: true },

  /**
   * Add debounce for search. Useful when using it with combination of AJAX requests.
   *
   * @type {Boolean|Number|String}
   * @default false
   */
  debounce: { type: [Boolean, Number, String], default: false },

  /**
   * Callback function used to determine if a given option should match the
   * current search text. This function is utilized to decide if the option
   * should be displayed in the dropdown based on the user's search input.
   * It takes an option (which can be an object or a string), the label associated
   * with that option, and the current search text as parameters.
   *
   * @param {object|string} option - The option to be evaluated.
   * @param {string} label - The label of the option.
   * @param {string} search - The current search text.
   * @return {boolean} - Returns true if the option matches the search criteria, false otherwise.
   * @property {Function} type - Constructor type for the filterBy function.
   * @property {Function} default - The default filtering function, which performs a case-insensitive search.
   */
  filterBy: {
    type: Function,
    default(option, label, search) {
      return (label || '').toLowerCase().indexOf(search.toLowerCase()) > -1
    },
  },

  /**
   * Callback to generate the label text. If {option}
   * is an object, returns option[props.label] by default.
   *
   * Label text is used for filtering comparison and displaying. If you only need to adjust the
   * display, you should use the `option` and `selected-option` slots.
   *
   * @param  {object|string} option
   * @return {string}
   */
  optionLabel: Function,

  /**
   * The `reduce` prop is used to transform a given object to a specific format
   * that you want to pass to a v-model binding or @input event. This is particularly
   * useful when working with complex objects and you need to extract or transform
   * only certain properties for the v-model or @input event.
   *
   * @property {Function} type - Constructor type for the reduce function.
   * @property {Function} default - The default function, which returns the option as is.
   * @param {object} option - The option object that is to be transformed.
   * @return {*} - The transformed option that will be used for v-model binding or @input event.
   */
  reduce: { type: Function, default: option => option },

  /**
   * Decides whether an option is selectable or not. Options that are not selectable
   * are displayed but disabled and cannot be selected. This function can take either
   * an object or a string as an option.
   *
   * @property {Function} type - Constructor type for the selectable function.
   * @property {Function} default - The default function that always returns true.
   * @param {object|string} option - The option to check for selectability.
   * @return {boolean} - Returns true if the option is selectable, false otherwise.
   */
  // eslint-disable-next-line no-unused-vars
  selectable: { type: Function, default: option => true },
}

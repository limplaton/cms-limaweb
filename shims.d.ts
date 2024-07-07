import Gate from './modules/Core/resources/js/gate'
import { Axios, AxiosRequestConfig, AxiosResponse } from 'axios';
import { App, Component } from 'vue';
import { Composer } from 'vue-i18n';
import { Router } from 'vue-router'
import { Store } from 'vuex'
export {}

interface VueDialogCustomProperty {
    show: (id: string) => void;
    hide: (id: string) => void;
}

interface VueDraggableCustomProperty {
    common: object;
    scrollable: object
}

interface ConfirmationDialogOptions {
    title: false | string;
    message?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'xxl';
    html?: boolean;
    component?: string | Component;
    confirmText?: string;
    cancelText?: string;
    cancelVariant?: string;
    confirmVariant?: string;
    icon?: string;
    iconColorClass?: string;
    iconWrapperColorClass?: string;
}

declare class Innoclapps {
    booting(callback: (this: Innoclapps, app: App, router: Router, store: Store) => void | Array<(this: Innoclapps, app: App, router: Router, store: Store) => void>): Innoclapps;
    resources(): object[];
    resource(name: string): object;
    resourceName(name: string): string;
    scriptConfig(key: string, value?: any): string | number | boolean | object | null | undefined;
    request(urlOrAxiosConfig: string | AxiosRequestConfig, getRequestConfig?: AxiosRequestConfig): Axios | Promise<AxiosResponse>;
    $on(event: string, callback: Function): void;
    $off(event: string, callback?: Function): void;
    $emit(event: string, params: any): void;
    success(message: string, duration?: number, options?: object): void;
    info(message: string, duration?: number, options?: object): void;
    error(message: string, duration?: number, options?: object): void;
    addShortcut(keys: string, callback: Function): void;
    disableShortcut(keys: string): void;
    confirm(options: string | ConfirmationDialogOptions | Function): Promise<any>;
    dialog(): VueDialogCustomProperty;
    timezones(timezones?: Array): Promise<array>
}

declare global {
    declare const Innoclapps: Innoclapps;

    // function useFetch(urlOrAxiosConfig: string | AxiosRequestConfig, getRequestConfig?: AxiosRequestConfig): Axios | Promise<AxiosResponse>;
    // function requireConfirmation(options: string | ConfirmationDialogOptions | Function): Promise<any>;
    // function useI18n(): Composer;
    // function showAlert(type: 'success' | 'error' | 'info', message: string, duration?: number, options?: object): void;
    // function scriptConfig(key: string, value?: any): string | number | boolean | object | null | undefined;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $store: Store
        $gate: Gate
        $dialog: VueDialogCustomProperty
        $draggable: VueDraggableCustomProperty
        $csrfToken: string
        $confirm: (options: string | ConfirmationDialogOptions | Function) => Promise<any>
        $scriptConfig: (key: string, value?: any) => string | number | boolean | object | null | undefined;
    }
}

declare module '@vue/runtime-dom' {
    export interface GlobalComponents {
        RouterLink: typeof import('vue-router')['RouterLink']
        RouterView: typeof import('vue-router')['RouterView']
        SortableDraggable: typeof import('vuedraggable')['default']

        MainLayout: typeof import('./modules/Core/resources/js/components/MainLayout.vue')['default']

        IText: typeof import('./modules/Core/resources/js/components/UI/Text')['IText']
        ITextDark: typeof import('./modules/Core/resources/js/components/UI/Text')['ITextDark']
        ITextBlock: typeof import('./modules/Core/resources/js/components/UI/Text')['ITextBlock']
        ITextBlockDark: typeof import('./modules/Core/resources/js/components/UI/Text')['ITextBlockDark']
        ITextSmall: typeof import('./modules/Core/resources/js/components/UI/Text')['ITextSmall']
        ITextDisplay: typeof import('./modules/Core/resources/js/components/UI/Text')['ITextDisplay']

        IButton: typeof import('./modules/Core/resources/js/components/UI/Button/IButton.vue')['default']
        IButtonLink: typeof import('./modules/Core/resources/js/components/UI/Button/IButtonLink.vue')['default']
        IButtonCopy: typeof import('./modules/Core/resources/js/components/UI/Button/IButtonCopy.vue')['default']

        IDropdown: typeof import('./modules/Core/resources/js/components/UI/Dropdown')['IDropdown']
        IDropdownButton: typeof import('./modules/Core/resources/js/components/UI/Dropdown')['IDropdownButton']
        IDropdownItemLabel: typeof import('./modules/Core/resources/js/components/UI/Dropdown')['IDropdownItemLabel']
        IDropdownMenu: typeof import('./modules/Core/resources/js/components/UI/Dropdown')['IDropdownMenu']
        IDropdownItem: typeof import('./modules/Core/resources/js/components/UI/Dropdown')['IDropdownItem']
        IDropdownItemDescription: typeof import('./modules/Core/resources/js/components/UI/Dropdown')['IDropdownItemDescription']
        IDropdownSeparator: typeof import('./modules/Core/resources/js/components/UI/Dropdown')['IDropdownSeparator']

        IDropdownMinimal: typeof import('./modules/Core/resources/js/components/UI/Dropdown/IDropdownMinimal.vue')['default']
        IExtendedDropdown: typeof import('./modules/Core/resources/js/components/UI/Dropdown/IExtendedDropdown.vue')['default']

        IFormError: typeof import('./modules/Core/resources/js/components/UI/Form/IFormError.vue')['default']
        IFormGroup: typeof import('./modules/Core/resources/js/components/UI/Form/IFormGroup.vue')['default']
        IFormInput: typeof import('./modules/Core/resources/js/components/UI/Form/IFormInput.vue')['default']
        IFormInputDropdown: typeof import('./modules/Core/resources/js/components/UI/Form/IFormInputDropdown.vue')['default']
        IFormLabel: typeof import('./modules/Core/resources/js/components/UI/Form/IFormLabel.vue')['default']
        IFormNumericInput: typeof import('./modules/Core/resources/js/components/UI/Form/IFormNumericInput.vue')['default']
        IFormSelect: typeof import('./modules/Core/resources/js/components/UI/Form/IFormSelect.vue')['default']
        IFormTextarea: typeof import('./modules/Core/resources/js/components/UI/Form/IFormTextarea.vue')['default']

        IFormCheckboxGroup: typeof import('./modules/Core/resources/js/components/UI/Form/Checkbox')['IFormCheckboxGroup']
        IFormCheckboxField: typeof import('./modules/Core/resources/js/components/UI/Form/Checkbox')['IFormCheckboxField']
        IFormCheckboxLabel: typeof import('./modules/Core/resources/js/components/UI/Form/Checkbox')['IFormCheckboxLabel']
        IFormCheckboxDescription: typeof import('./modules/Core/resources/js/components/UI/Form/Checkbox')['IFormCheckboxDescription']
        IFormCheckbox: typeof import('./modules/Core/resources/js/components/UI/Form/Checkbox')['IFormCheckbox']

        IFormRadioGroup: typeof import('./modules/Core/resources/js/components/UI/Form/Radio')['IFormRadioGroup']
        IFormRadioField: typeof import('./modules/Core/resources/js/components/UI/Form/Radio')['IFormRadioField']
        IFormRadioLabel: typeof import('./modules/Core/resources/js/components/UI/Form/Radio')['IFormRadioLabel']
        IFormRadioDescription: typeof import('./modules/Core/resources/js/components/UI/Form/Radio')['IFormRadioDescription']
        IFormRadio: typeof import('./modules/Core/resources/js/components/UI/Form/Radio')['IFormRadio']

        IFormSwitchGroup: typeof import('./modules/Core/resources/js/components/UI/Form/Switch')['IFormSwitchGroup']
        IFormSwitchField: typeof import('./modules/Core/resources/js/components/UI/Form/Switch')['IFormSwitchField']
        IFormSwitchLabel: typeof import('./modules/Core/resources/js/components/UI/Form/Switch')['IFormSwitchLabel']
        IFormSwitchDescription: typeof import('./modules/Core/resources/js/components/UI/Form/Switch')['IFormSwitchDescription']
        IFormSwitch: typeof import('./modules/Core/resources/js/components/UI/Form/Switch')['IFormSwitch']

        ICustomSelect: typeof import('./modules/Core/resources/js/components/UI/CustomSelect/ISelect.vue')['default']

        IConfirmationDialog: typeof import('./modules/Core/resources/js/components/UI/Dialog/IConfirmationDialog.vue')['default']
        IModal: typeof import('./modules/Core/resources/js/components/UI/Dialog/IModal.vue')['default']
        ISlideover: typeof import('./modules/Core/resources/js/components/UI/Dialog/ISlideover.vue')['default']

        DatePicker: typeof import('./modules/Core/resources/js/components/DatePicker/DatePicker.vue')['default']
        DateRangePicker: typeof import('./modules/Core/resources/js/components/DatePicker/DateRangePicker.vue')['default']

        IPopover: typeof import('./modules/Core/resources/js/components/UI/Popover')['IPopover']
        IPopoverButton: typeof import('./modules/Core/resources/js/components/UI/Popover')['IPopoverButton']
        IPopoverPanel: typeof import('./modules/Core/resources/js/components/UI/Popover')['IPopoverPanel']
        IPopoverHeader: typeof import('./modules/Core/resources/js/components/UI/Popover')['IPopoverHeader']
        IPopoverHeading: typeof import('./modules/Core/resources/js/components/UI/Popover')['IPopoverHeading']
        IPopoverBody: typeof import('./modules/Core/resources/js/components/UI/Popover')['IPopoverBody']
        IPopoverFooter: typeof import('./modules/Core/resources/js/components/UI/Popover')['IPopoverFooter']

        ITableOuter: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableOuter']
        ITable: typeof import('./modules/Core/resources/js/components/UI/Table')['ITable']
        ITableHead: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableHead']
        ITableHeader: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableHeader']
        ITableBody: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableBody']
        ITableRow: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableRow']
        ITableCell: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableCell']
        ITableRowAction: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableRowAction']
        ITableRowActions: typeof import('./modules/Core/resources/js/components/UI/Table')['ITableRowActions']

        IAlert: typeof import('./modules/Core/resources/js/components/UI/Alert')['IAlert']
        IAlerHeading: typeof import('./modules/Core/resources/js/components/UI/Alert')['IAlerHeading']
        IAlertBody: typeof import('./modules/Core/resources/js/components/UI/Alert')['IAlertBody']
        IAlertActions: typeof import('./modules/Core/resources/js/components/UI/Alert')['IAlertActions']

        IBadge: typeof import('./modules/Core/resources/js/components/UI/Badge')['IBadge']
        IBadgeButton: typeof import('./modules/Core/resources/js/components/UI/Badge')['IBadgeButton']

        IStepCircle: typeof import('./modules/Core/resources/js/components/UI/IStepCircle.vue')['default']
        IStepsCircle: typeof import('./modules/Core/resources/js/components/UI/IStepsCircle.vue')['default']

        ITab: typeof import('./modules/Core/resources/js/components/UI/Tab/ITab.vue')['default']
        ITabGroup: typeof import('./modules/Core/resources/js/components/UI/Tab/ITabGroup.vue')['default']
        ITabList: typeof import('./modules/Core/resources/js/components/UI/Tab/ITabList.vue')['default']
        ITabPanel: typeof import('./modules/Core/resources/js/components/UI/Tab/ITabPanel.vue')['default']
        ITabPanels: typeof import('./modules/Core/resources/js/components/UI/Tab/ITabPanels.vue')['default']

        ICard: typeof import('./modules/Core/resources/js/components/UI/Card')['ICard']
        ICardActions: typeof import('./modules/Core/resources/js/components/UI/Card')['ICardActions']
        ICardBody: typeof import('./modules/Core/resources/js/components/UI/Card')['ICardBody']
        ICardFooter: typeof import('./modules/Core/resources/js/components/UI/Card')['ICardFooter']
        ICardHeader: typeof import('./modules/Core/resources/js/components/UI/Card')['ICardHeader']
        ICardHeading: typeof import('./modules/Core/resources/js/components/UI/Card')['ICardHeading']

        IVerticalNavigation: typeof import('./modules/Core/resources/js/components/UI/VerticalNavigation/IVerticalNavigation.vue')['default']
        IVerticalNavigationItem: typeof import('./modules/Core/resources/js/components/UI/VerticalNavigation/IVerticalNavigationItem.vue')['default']

        ILink: typeof import('./modules/Core/resources/js/components/UI/ILink.vue')['default']
        ILinkBase: typeof import('./modules/Core/resources/js/components/UI/ILinkBase.vue')['default']

        ISpinner: typeof import('./modules/Core/resources/js/components/UI/ISpinner.vue')['default']
        IColorSwatch: typeof import('./modules/Core/resources/js/components/UI/IColorSwatch.vue')['default']
        IAvatar: typeof import('./modules/Core/resources/js/components/UI/IAvatar.vue')['default']
        IActionMessage: typeof import('./modules/Core/resources/js/components/UI/IActionMessage.vue')['default']
        IEmptyState: typeof import('./modules/Core/resources/js/components/UI/IEmptyState.vue')['default']
        IIconPicker: typeof import('./modules/Core/resources/js/components/UI/IIconPicker.vue')['default']
        Editor: typeof import('./modules/Core/resources/js/components/Editor/Editor.vue')['default']
        TextCollapse: typeof import('./modules/Core/resources/js/components/TextCollapse.vue')['default']
        ResourceTable: typeof import('./modules/Core/resources/js/components/ResourceTable/ResourceTable.vue')['default']
        Icon: typeof import('./modules/Core/resources/js/components/UI/Icon.vue')['default']
        IOverlay: typeof import('./modules/Core/resources/js/components/UI/IOverlay.vue')['default']

        FormFields: typeof import('./modules/Core/resources/js/fields/FormFields.vue')['default']
        DetailFields: typeof import('./modules/Core/resources/js/fields/DetailFields.vue')['default']
        BaseDetailField: typeof import('./modules/Core/resources/js/fields/BaseDetailField.vue')['default']
        BaseIndexField: typeof import('./modules/Core/resources/js/fields/BaseIndexField.vue')['default']
        FieldsButtonCollapse: typeof import('./modules/Core/resources/js/fields/FieldsButtonCollapse.vue')['default']
        FieldInlineEdit: typeof import('./modules/Core/resources/js/fields/FieldInlineEdit.vue')['default']
        BaseFormField: typeof import('./modules/Core/resources/js/fields/BaseFormField.vue')['default']
        BaseSelectField: typeof import('./modules/Core/resources/js/fields/BaseSelectField.vue')['default']
        FieldsPlaceholder: typeof import('./modules/Core/resources/js/fields/FieldsPlaceholder.vue')['default']
    }
}

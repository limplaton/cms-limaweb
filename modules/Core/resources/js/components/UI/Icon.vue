<template>
  <component :is="`${icon}Icon`" v-if="isHeroIcon" aria-hidden="true" />
</template>

<script>
// For solid icons, it's recommended to use the 16x16 size.
import {
  ArrowDownIcon as ArrowDownSolidIcon,
  ArrowDownTrayIcon as DownloadSolidIcon,
  ArrowUpIcon as ArrowUpSolidIcon,
  CheckCircleIcon as CheckCircleSolidIcon,
  ChevronDownIcon as ChevronDownSolidIcon,
  ChevronLeftIcon as ChevronLeftSolidIcon,
  ChevronRightIcon as ChevronRightSolidIcon,
  ChevronUpIcon as ChevronUpSolidIcon,
  EllipsisHorizontalIcon as EllipsisHorizontalSolidIcon,
  EllipsisVerticalIcon as EllipsisVerticalSolidIcon,
  MagnifyingGlassIcon as MagnifyingGlassSolidIcon,
  MinusIcon as MinusSolidIcon,
  PlusIcon as PlusSolidIcon,
  XCircleIcon as XCircleSolidIcon,
  XMarkIcon as XSolidIcon,
} from '@heroicons/vue/16/solid'
import {
  AcademicCapIcon,
  AdjustmentsVerticalIcon,
  ArchiveBoxIcon,
  ArrowLeftIcon,
  ArrowLeftStartOnRectangleIcon,
  ArrowPathIcon as RefreshIcon,
  ArrowRightIcon,
  ArrowsPointingOutIcon,
  ArrowTopRightOnSquareIcon as ExternalLinkIcon,
  ArrowUturnLeftIcon as ReplyIcon,
  AtSymbolIcon,
  BanknotesIcon,
  Bars3BottomLeftIcon,
  Bars3BottomRightIcon as MenuAlt3Icon,
  Bars3CenterLeftIcon,
  Bars3Icon,
  BarsArrowUpIcon as SortAscendingIcon,
  BellIcon,
  BookmarkIcon,
  BookOpenIcon,
  BriefcaseIcon,
  BugAntIcon,
  BuildingOfficeIcon as OfficeBuildingIcon,
  CalendarIcon,
  CameraIcon,
  ChartBarSquareIcon as ChartSquareBarIcon,
  ChatBubbleBottomCenterTextIcon,
  ChatBubbleLeftEllipsisIcon as ChatAltIcon,
  ChatBubbleOvalLeftEllipsisIcon as ChatIcon,
  CheckBadgeIcon,
  CheckCircleIcon,
  CheckIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronUpDownIcon as SelectorIcon,
  ClipboardIcon,
  ClockIcon,
  CloudArrowUpIcon,
  CodeBracketIcon,
  Cog6ToothIcon as CogIcon,
  CreditCardIcon,
  CurrencyDollarIcon,
  CursorArrowRaysIcon as CursorClickIcon,
  DevicePhoneMobileIcon as DeviceMobileIcon,
  DocumentArrowDownIcon as DocumentDownloadIcon,
  DocumentDuplicateIcon as DuplicateIcon,
  DocumentIcon,
  DocumentPlusIcon as DocumentAddIcon,
  DocumentTextIcon,
  EnvelopeIcon as MailIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FaceFrownIcon as EmojiSadIcon,
  FaceSmileIcon as EmojiHappyIcon,
  FilmIcon,
  FolderIcon,
  FunnelIcon as FilterIcon,
  GiftIcon,
  GlobeAsiaAustraliaIcon as GlobeIcon,
  HomeIcon,
  InboxIcon,
  InboxStackIcon,
  InformationCircleIcon,
  KeyIcon,
  LightBulbIcon,
  LinkIcon,
  ListBulletIcon,
  LockClosedIcon,
  MegaphoneIcon,
  MicrophoneIcon,
  MoonIcon,
  PaintBrushIcon,
  PaperClipIcon,
  PencilIcon,
  PencilSquareIcon as PencilAltIcon,
  PhoneIcon,
  PhoneXMarkIcon,
  PhotoIcon as PhotographIcon,
  PlusIcon,
  QuestionMarkCircleIcon,
  RectangleStackIcon as CollectionIcon,
  RocketLaunchIcon,
  ScaleIcon,
  ShareIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
  ShoppingBagIcon,
  SparklesIcon,
  SpeakerWaveIcon as VolumeUpIcon,
  SpeakerXMarkIcon as VolumeOffIcon,
  Squares2X2Icon as ViewGridIcon,
  SquaresPlusIcon,
  StarIcon,
  SunIcon,
  SwatchIcon as ColorSwatchIcon,
  TableCellsIcon,
  TagIcon,
  TrashIcon,
  TruckIcon,
  UserGroupIcon,
  UserIcon,
  UsersIcon,
  ViewColumnsIcon,
  ViewfinderCircleIcon,
  WindowIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'

export default {
  components: {
    EyeIcon,
    SparklesIcon,
    CheckBadgeIcon,
    BanknotesIcon,
    LockClosedIcon,
    ClockIcon,
    CodeBracketIcon,
    AcademicCapIcon,
    RocketLaunchIcon,
    ChatBubbleBottomCenterTextIcon,
    ViewfinderCircleIcon,
    SunIcon,
    MoonIcon,
    MegaphoneIcon,
    VolumeOffIcon,
    VolumeUpIcon,
    EmojiHappyIcon,
    EmojiSadIcon,
    ArrowRightIcon,
    ArrowLeftIcon,
    ReplyIcon,
    CurrencyDollarIcon,
    CursorClickIcon,
    FilterIcon,
    ShareIcon,
    ListBulletIcon,
    CogIcon,
    CollectionIcon,
    ColorSwatchIcon,
    HomeIcon,
    LightBulbIcon,
    Bars3CenterLeftIcon,
    Bars3BottomLeftIcon,
    MenuAlt3Icon,
    PhotographIcon,
    PlusSolidIcon,
    MinusSolidIcon,
    PlusIcon,
    MicrophoneIcon,
    UserGroupIcon,
    ViewGridIcon,
    XSolidIcon,
    ArchiveBoxIcon,
    BugAntIcon,
    MagnifyingGlassSolidIcon,
    ChartSquareBarIcon,
    MailIcon,
    CalendarIcon,
    TableCellsIcon,
    UsersIcon,
    UserIcon,
    OfficeBuildingIcon,
    InboxIcon,
    InboxStackIcon,
    GiftIcon,
    KeyIcon,
    FilmIcon,
    FolderIcon,
    CheckCircleIcon,
    CheckCircleSolidIcon,
    ArrowUpSolidIcon,
    ArrowDownSolidIcon,
    CheckIcon,
    XCircleSolidIcon,
    InformationCircleIcon,
    ChevronUpSolidIcon,
    ChevronDownSolidIcon,
    ChevronRightSolidIcon,
    ChevronLeftSolidIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ExclamationTriangleIcon,
    ShieldExclamationIcon,
    ShieldCheckIcon,
    SquaresPlusIcon,
    QuestionMarkCircleIcon,
    SelectorIcon,
    BellIcon,
    ArrowsPointingOutIcon,
    RefreshIcon,
    DuplicateIcon,
    GlobeIcon,
    DeviceMobileIcon,
    PhoneIcon,
    PhoneXMarkIcon,
    AdjustmentsVerticalIcon,
    ArrowLeftStartOnRectangleIcon,
    WindowIcon,
    DocumentDownloadIcon,
    EllipsisHorizontalSolidIcon,
    EllipsisVerticalSolidIcon,
    DocumentAddIcon,
    DocumentIcon,
    DocumentTextIcon,
    DownloadSolidIcon,
    ExternalLinkIcon,
    LinkIcon,
    TrashIcon,
    PencilAltIcon,
    PencilIcon,
    StarIcon,
    Bars3Icon,
    TagIcon,
    ViewColumnsIcon,
    SortAscendingIcon,
    AtSymbolIcon,
    ShoppingBagIcon,
    BriefcaseIcon,
    ChatIcon,
    ChatAltIcon,
    BookOpenIcon,
    BookmarkIcon,
    CameraIcon,
    TruckIcon,
    CloudArrowUpIcon,
    PaperClipIcon,
    PaintBrushIcon,
    ClipboardIcon,
    CreditCardIcon,
    WrenchScrewdriverIcon,
    ScaleIcon,
  },

  props: {
    type: {
      type: String,
      default: 'hero',
    },

    icon: {
      required: true,
    },
  },

  setup(props) {
    function isHeroIcon() {
      return props.type === 'hero'
    }

    return {
      isHeroIcon,
    }
  },
}
</script>

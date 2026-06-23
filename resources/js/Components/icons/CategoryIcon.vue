<script>
import { h } from 'vue'
import {
  PuzzlePieceIcon, ComputerDesktopIcon, DevicePhoneMobileIcon, DeviceTabletIcon,
  TvIcon, CameraIcon, SpeakerWaveIcon, MusicalNoteIcon, GiftIcon, HomeModernIcon,
  HeartIcon, BookOpenIcon, ShoppingCartIcon, CakeIcon, WrenchScrewdriverIcon,
  PaintBrushIcon, TrophyIcon, TruckIcon, ClockIcon, GlobeAltIcon, SunIcon,
  CpuChipIcon, BuildingStorefrontIcon, CubeIcon, KeyIcon, TicketIcon, PencilIcon,
} from '@heroicons/vue/24/outline'

// ── Custom line icons (things Heroicons lacks). 24×24, currentColor stroke. ──
const CUSTOM = {
  bag: [ // backpack
    'M6 10a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v8a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2v-8Z',
    'M9 6V5.5a3 3 0 0 1 6 0V6',
    'M9 13h6v3a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-3Z',
  ],
  trolley: [ // wheeled suitcase
    'M7 8h10a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z',
    'M10 8V6a2 2 0 0 1 4 0v2',
    'M9 18v1.6M15 18v1.6',
  ],
  bottle: [ // water bottle
    'M10 3.5h4v2.4l1.1 1.5a2 2 0 0 1 .4 1.2V19a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2V8.6a2 2 0 0 1 .4-1.2L10 5.9V3.5Z',
    'M9.3 13h5.4',
  ],
  flask: [ // thermos / flask
    'M9.5 3.5h5',
    'M10.5 3.5v3.3L7.5 17.6A2 2 0 0 0 9.4 20.3h5.2a2 2 0 0 0 1.9-2.7L13.5 6.8V3.5',
    'M8.8 14.3h6.4',
  ],
  coffee: [ // coffee mug
    'M5 8h11v6a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4V8Z',
    'M16 9.5h2a2.5 2.5 0 0 1 0 5h-2',
    'M8 3.2c-.6 1 .6 1.6 0 2.6M11.5 3.2c-.6 1 .6 1.6 0 2.6',
  ],
  cap: [ // graduation cap
    'M12 4 3 8l9 4 9-4-9-4Z',
    'M7 10.4V14c0 1 2.2 2.2 5 2.2s5-1.2 5-2.2v-3.6',
    'M21 8v4',
  ],
  apparel: [ // t-shirt
    'M8.5 4 4.5 6.2 6.2 9.2 8.5 8v12h7V8l2.3 1.2L19.5 6.2 15.5 4a3 3 0 0 1-7 0Z',
  ],
  gem: [ // jewelry / gem
    'M6 4h12l3 5-9 11L3 9l3-5Z',
    'M3 9h18',
    'M9 4 7.5 9 12 20M15 4l1.5 5L12 20',
  ],
  sparkle: [ // beauty / new
    'M12 3l1.8 4.9L18.7 9.7 13.8 11.5 12 16.4 10.2 11.5 5.3 9.7 10.2 7.9 12 3Z',
  ],
  paw: [ // pets
    'M6.6 8.4a1.3 1.3 0 1 0 0-2.6 1.3 1.3 0 0 0 0 2.6Z',
    'M10 6.4a1.3 1.3 0 1 0 0-2.6 1.3 1.3 0 0 0 0 2.6Z',
    'M14 6.4a1.3 1.3 0 1 0 0-2.6 1.3 1.3 0 0 0 0 2.6Z',
    'M17.4 8.4a1.3 1.3 0 1 0 0-2.6 1.3 1.3 0 0 0 0 2.6Z',
    'M12 10c-2.5 0-4.6 1.9-4.6 4.1 0 1.6 1.3 2.5 2.9 2.5.9 0 1.1-.4 1.7-.4s.8.4 1.7.4c1.6 0 2.9-.9 2.9-2.5C16.5 11.9 14.5 10 12 10Z',
  ],
  tag: [ // accessories / sale
    'M4 12.6 11.4 5.2a2 2 0 0 1 1.4-.6H18a1.4 1.4 0 0 1 1.4 1.4v5.2a2 2 0 0 1-.6 1.4l-7.4 7.4a2 2 0 0 1-2.8 0L4 15.4a2 2 0 0 1 0-2.8Z',
    'M15.5 8.5h.01',
  ],
  default: [ // generic shopping bag
    'M6.5 8h11l-1 11.2a2 2 0 0 1-2 1.8H9.5a2 2 0 0 1-2-1.8L6.5 8Z',
    'M9.5 8V6.2a2.5 2.5 0 0 1 5 0V8',
  ],
}

// ── Heroicon-backed icons ──
const HERO = {
  toy: PuzzlePieceIcon,
  computer: ComputerDesktopIcon,
  phone: DevicePhoneMobileIcon,
  tablet: DeviceTabletIcon,
  tv: TvIcon,
  camera: CameraIcon,
  audio: SpeakerWaveIcon,
  music: MusicalNoteIcon,
  gift: GiftIcon,
  home: HomeModernIcon,
  health: HeartIcon,
  book: BookOpenIcon,
  grocery: ShoppingCartIcon,
  food: CakeIcon,
  tools: WrenchScrewdriverIcon,
  art: PaintBrushIcon,
  sports: TrophyIcon,
  auto: TruckIcon,
  watch: ClockIcon,
  travel: GlobeAltIcon,
  outdoor: SunIcon,
  electronics: CpuChipIcon,
  store: BuildingStorefrontIcon,
  box: CubeIcon,
  key: KeyIcon,
  ticket: TicketIcon,
  pen: PencilIcon,
}

// ── Category slug → icon key (powers "Auto (by name)") ──
const ALIAS = {
  bags: 'bag', backpacks: 'bag', 'corporate-bags': 'bag', 'laptop-backpacks': 'computer',
  'school-bags': 'cap', school: 'cap', education: 'cap',
  'kids-trolley-bags': 'trolley', trolley: 'trolley', luggage: 'trolley', suitcase: 'trolley',
  drinkware: 'bottle', 'water-bottles': 'bottle', bottles: 'bottle', 'kids-bottles': 'bottle', 'adult-bottles': 'bottle', bottle: 'bottle',
  'thermos-flasks': 'flask', thermos: 'flask', flask: 'flask',
  coffee: 'coffee', mug: 'coffee', mugs: 'coffee', 'coffee-mug': 'coffee', 'coffee-mugs': 'coffee',
  food: 'food', bakery: 'food', snacks: 'food', sweets: 'food',
  grocery: 'grocery', groceries: 'grocery', supermarket: 'grocery',
  clothing: 'apparel', apparel: 'apparel', fashion: 'apparel', 't-shirts': 'apparel', tshirts: 'apparel', men: 'apparel', women: 'apparel',
  jewelry: 'gem', jewellery: 'gem', rings: 'gem', gems: 'gem',
  beauty: 'sparkle', cosmetics: 'sparkle', makeup: 'sparkle', skincare: 'sparkle',
  books: 'book', book: 'book', stationery: 'pen', office: 'pen', pens: 'pen',
  computers: 'computer', laptops: 'computer', pc: 'computer', computer: 'computer',
  phones: 'phone', mobiles: 'phone', smartphones: 'phone', mobile: 'phone',
  tablets: 'tablet', ipad: 'tablet',
  tv: 'tv', television: 'tv', tvs: 'tv',
  cameras: 'camera', camera: 'camera', photography: 'camera',
  audio: 'audio', speakers: 'audio', headphones: 'audio', earphones: 'audio',
  music: 'music', instruments: 'music',
  electronics: 'electronics', gadgets: 'electronics', devices: 'electronics',
  toys: 'toy', games: 'toy', toy: 'toy', kids: 'toy', baby: 'toy',
  art: 'art', craft: 'art', crafts: 'art', paint: 'art',
  sports: 'sports', sport: 'sports', fitness: 'sports', gym: 'sports',
  home: 'home', furniture: 'home', decor: 'home', 'home-decor': 'home', kitchen: 'home',
  tools: 'tools', hardware: 'tools', diy: 'tools',
  keys: 'key', security: 'key', locks: 'key',
  gifts: 'gift', gift: 'gift', hampers: 'gift',
  health: 'health', wellness: 'health', medical: 'health', 'personal-care': 'health',
  watches: 'watch', watch: 'watch', clocks: 'watch',
  automotive: 'auto', auto: 'auto', car: 'auto', vehicles: 'auto', bike: 'auto',
  outdoor: 'outdoor', garden: 'outdoor', camping: 'outdoor', summer: 'outdoor',
  store: 'store', general: 'store', wholesale: 'store',
  tickets: 'ticket', events: 'ticket',
  accessories: 'tag', sale: 'tag', tag: 'tag',
  pets: 'paw', pet: 'paw',
  travel: 'travel',
}

// Admin icon picker options (grouped sensibly).
export const CATEGORY_ICON_OPTIONS = [
  { value: 'bag',     label: 'Backpack' },
  { value: 'trolley', label: 'Luggage / trolley' },
  { value: 'travel',  label: 'Travel / globe' },
  { value: 'bottle',  label: 'Water bottle' },
  { value: 'flask',   label: 'Flask / thermos' },
  { value: 'coffee',  label: 'Coffee mug' },
  { value: 'food',    label: 'Food / bakery' },
  { value: 'grocery', label: 'Grocery cart' },
  { value: 'apparel', label: 'Apparel / t-shirt' },
  { value: 'gem',     label: 'Jewelry / gem' },
  { value: 'sparkle', label: 'Beauty / sparkle' },
  { value: 'cap',     label: 'School / education' },
  { value: 'book',    label: 'Books' },
  { value: 'pen',     label: 'Stationery / office' },
  { value: 'toy',     label: 'Toys / games' },
  { value: 'computer',label: 'Computer / laptop' },
  { value: 'phone',   label: 'Mobile phone' },
  { value: 'tablet',  label: 'Tablet' },
  { value: 'tv',      label: 'TV' },
  { value: 'camera',  label: 'Camera' },
  { value: 'audio',   label: 'Audio / speaker' },
  { value: 'music',   label: 'Music' },
  { value: 'electronics', label: 'Electronics / chip' },
  { value: 'home',    label: 'Home / furniture' },
  { value: 'tools',   label: 'Tools / hardware' },
  { value: 'art',     label: 'Art / craft' },
  { value: 'sports',  label: 'Sports / fitness' },
  { value: 'health',  label: 'Health / wellness' },
  { value: 'watch',   label: 'Watches / clock' },
  { value: 'auto',    label: 'Automotive' },
  { value: 'outdoor', label: 'Outdoor / garden' },
  { value: 'gift',    label: 'Gifts' },
  { value: 'key',     label: 'Keys / security' },
  { value: 'ticket',  label: 'Tickets / events' },
  { value: 'store',   label: 'Storefront' },
  { value: 'pet',     label: 'Pets' },
  { value: 'tag',     label: 'Tag / sale' },
  { value: 'box',     label: 'Box / other' },
  { value: 'default', label: 'Shopping bag' },
]

function svg(paths) {
  return h('svg', {
    viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor',
    'stroke-width': 1.6, 'stroke-linecap': 'round', 'stroke-linejoin': 'round',
  }, paths.map((d) => h('path', { d })))
}

export default {
  name: 'CategoryIcon',
  props: {
    icon: { type: String, default: '' }, // named key or legacy emoji
    slug: { type: String, default: '' },
  },
  render() {
    // 1) explicit named icon
    if (this.icon && CUSTOM[this.icon]) return svg(CUSTOM[this.icon])
    if (this.icon && HERO[this.icon]) return h(HERO[this.icon])
    // 2) inferred from slug
    const k = ALIAS[this.slug]
    if (k && CUSTOM[k]) return svg(CUSTOM[k])
    if (k && HERO[k]) return h(HERO[k])
    // 3) legacy emoji fallback
    if (this.icon) return h('span', { class: 'inline-flex items-center justify-center leading-none' }, this.icon)
    // 4) default
    return svg(CUSTOM.default)
  },
}
</script>

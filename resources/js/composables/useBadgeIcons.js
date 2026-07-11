// Shared icon set for Trust badges (home page) and Assurance badges (product page).
// Add a new entry here and it becomes available in BOTH admin editors + frontends.
// Keep the option `value` list in config/content.php (trust.icon + product.assurances.icon)
// in sync with the keys below.
import {
  TruckIcon, ShieldCheckIcon, ArrowPathIcon, CreditCardIcon, BanknotesIcon,
  CurrencyBangladeshiIcon, LifebuoyIcon, PhoneIcon, ChatBubbleLeftRightIcon,
  LockClosedIcon, CheckBadgeIcon, CheckCircleIcon, StarIcon, HeartIcon, GiftIcon,
  ClockIcon, BoltIcon, SparklesIcon, GlobeAltIcon, RocketLaunchIcon, TagIcon,
  WrenchScrewdriverIcon, CubeIcon, HandThumbUpIcon, HandRaisedIcon, FireIcon,
  KeyIcon, MapPinIcon, AcademicCapIcon,
} from '@heroicons/vue/24/outline'

// key (stored in content) → heroicon component
export const badgeIcons = {
  truck: TruckIcon,          // delivery / shipping
  rocket: RocketLaunchIcon,  // express / fast shipping
  bolt: BoltIcon,            // fast
  clock: ClockIcon,          // 24/7 / hours
  globe: GlobeAltIcon,       // nationwide / worldwide
  map: MapPinIcon,           // store pickup / location
  shield: ShieldCheckIcon,   // warranty / protection
  lock: LockClosedIcon,      // secure checkout
  check: CheckBadgeIcon,     // genuine / verified
  checkcircle: CheckCircleIcon,
  returns: ArrowPathIcon,    // easy returns / exchange
  card: CreditCardIcon,      // card payment
  cash: BanknotesIcon,       // cash on delivery
  taka: CurrencyBangladeshiIcon, // money-back / price (৳)
  tag: TagIcon,              // best price / discount
  gift: GiftIcon,            // gift / offers
  fire: FireIcon,            // hot deals / trending
  star: StarIcon,            // top rated / premium
  heart: HeartIcon,          // loved by customers
  spark: SparklesIcon,       // premium quality
  thumb: HandThumbUpIcon,    // satisfaction guarantee
  hand: HandRaisedIcon,      // hand-picked / promise
  support: LifebuoyIcon,     // dedicated support
  phone: PhoneIcon,          // call us
  chat: ChatBubbleLeftRightIcon, // live chat support
  wrench: WrenchScrewdriverIcon, // installation / service
  cube: CubeIcon,            // quality packaging
  key: KeyIcon,              // exclusive / access
  academic: AcademicCapIcon, // expert / guidance
}

// The list of selectable keys (for admin <select> options / fallbacks).
export const badgeIconKeys = Object.keys(badgeIcons)

// Resolve a stored key to its component, falling back to a sensible default.
export const resolveBadgeIcon = (key) => badgeIcons[key] || badgeIcons.check

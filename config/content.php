<?php

/*
|--------------------------------------------------------------------------
| Editable page content schema
|--------------------------------------------------------------------------
| Single source of truth for the JSON-file CMS. Each group maps to
| data/{group}.json. The schema drives the admin editor (rendering +
| validation) and provides defaults so a missing key never breaks a page.
|
| Field types: text | textarea | number | url | toggle | select | list
| A `list` field has an `item` array of sub-fields (repeatable rows).
*/

// Shared icon choices for Trust badges (home) + Assurance badges (product).
// Keys MUST match resources/js/composables/useBadgeIcons.js.
$badgeIconOptions = [
    ['value' => 'truck', 'label' => 'Delivery / shipping'],
    ['value' => 'rocket', 'label' => 'Express / fast shipping'],
    ['value' => 'bolt', 'label' => 'Fast / instant'],
    ['value' => 'clock', 'label' => '24/7 / hours'],
    ['value' => 'globe', 'label' => 'Nationwide / worldwide'],
    ['value' => 'map', 'label' => 'Store pickup / location'],
    ['value' => 'shield', 'label' => 'Warranty / protection'],
    ['value' => 'lock', 'label' => 'Secure checkout'],
    ['value' => 'check', 'label' => 'Genuine / verified'],
    ['value' => 'checkcircle', 'label' => 'Check circle'],
    ['value' => 'returns', 'label' => 'Easy returns / exchange'],
    ['value' => 'card', 'label' => 'Card payment'],
    ['value' => 'cash', 'label' => 'Cash on delivery'],
    ['value' => 'taka', 'label' => 'Money-back / price (৳)'],
    ['value' => 'tag', 'label' => 'Best price / discount'],
    ['value' => 'gift', 'label' => 'Gift / offers'],
    ['value' => 'fire', 'label' => 'Hot deals / trending'],
    ['value' => 'star', 'label' => 'Top rated / premium'],
    ['value' => 'heart', 'label' => 'Loved by customers'],
    ['value' => 'spark', 'label' => 'Premium quality'],
    ['value' => 'thumb', 'label' => 'Satisfaction guarantee'],
    ['value' => 'hand', 'label' => 'Hand-picked / promise'],
    ['value' => 'support', 'label' => 'Dedicated support'],
    ['value' => 'phone', 'label' => 'Call us'],
    ['value' => 'chat', 'label' => 'Live chat support'],
    ['value' => 'wrench', 'label' => 'Installation / service'],
    ['value' => 'cube', 'label' => 'Quality packaging'],
    ['value' => 'key', 'label' => 'Exclusive / access'],
    ['value' => 'academic', 'label' => 'Expert / guidance'],
];

return [

    'settings' => [
        'label' => 'Settings',
        'desc'  => 'Logo, favicon, store name, contact details, socials & currency.',
        'icon'  => 'cog',
        'fields' => [
            ['key' => 'logo', 'label' => 'Logo — for light backgrounds (header)', 'type' => 'image', 'default' => ''],
            ['key' => 'logo_light', 'label' => 'Logo — for dark backgrounds (footer; use a white/light version)', 'type' => 'image', 'default' => ''],
            ['key' => 'favicon', 'label' => 'Favicon (square image, e.g. 64×64 PNG)', 'type' => 'image', 'default' => ''],
            ['key' => 'site_name', 'label' => 'Site name', 'type' => 'text', 'default' => 'Kizora'],
            ['key' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'Carry your world.'],
            ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'default' => 'hello@kizora.com.bd'],
            ['key' => 'phone_1', 'label' => 'Phone (primary)', 'type' => 'text', 'default' => '+880 1730-728954'],
            ['key' => 'phone_2', 'label' => 'Phone (secondary)', 'type' => 'text', 'default' => '+880 1730-728905'],
            ['key' => 'address', 'label' => 'Address', 'type' => 'textarea', 'default' => 'House 7, Road 12, Banani, Dhaka 1213, Bangladesh'],
            ['key' => 'opening_hours', 'label' => 'Opening hours', 'type' => 'text', 'default' => 'Sat – Thu: 10:00 AM – 9:00 PM'],
            ['key' => 'map_url', 'label' => 'Google Maps URL', 'type' => 'url', 'default' => 'https://maps.google.com'],
            ['key' => 'social_links', 'label' => 'Social media links', 'type' => 'list',
                'help' => 'Add as many as you like — pick a platform and paste its URL. They appear in the footer in this order.',
                'item' => [
                    ['key' => 'platform', 'label' => 'Platform', 'type' => 'select', 'options' => [
                        ['value' => 'facebook',  'label' => 'Facebook'],
                        ['value' => 'instagram', 'label' => 'Instagram'],
                        ['value' => 'twitter',   'label' => 'X (Twitter)'],
                        ['value' => 'youtube',   'label' => 'YouTube'],
                        ['value' => 'tiktok',    'label' => 'TikTok'],
                        ['value' => 'linkedin',  'label' => 'LinkedIn'],
                        ['value' => 'whatsapp',  'label' => 'WhatsApp'],
                        ['value' => 'pinterest', 'label' => 'Pinterest'],
                        ['value' => 'telegram',  'label' => 'Telegram'],
                        ['value' => 'snapchat',  'label' => 'Snapchat'],
                        ['value' => 'threads',   'label' => 'Threads'],
                        ['value' => 'github',    'label' => 'GitHub'],
                        ['value' => 'website',   'label' => 'Website / Other'],
                    ]],
                    ['key' => 'url', 'label' => 'URL', 'type' => 'url'],
                ],
                'default' => [
                    ['platform' => 'facebook',  'url' => 'https://facebook.com/kizora'],
                    ['platform' => 'instagram', 'url' => 'https://instagram.com/kizora'],
                ],
            ],
            ['key' => 'currency_symbol', 'label' => 'Currency symbol', 'type' => 'text', 'default' => '৳'],

            ['key' => 'low_stock_threshold', 'label' => 'Low-stock alert threshold', 'type' => 'number', 'default' => 10, 'help' => 'Products at or below this stock level are flagged as "low stock" in Inventory and the sidebar alert badge.'],

            ['key' => 'head_scripts', 'label' => 'Tracking scripts — inside <head>', 'type' => 'textarea', 'default' => '', 'help' => 'Paste Google Analytics 4 / Tag Manager / Meta Pixel snippets here (full <script> tags). Printed as-is into every page\'s <head>.'],
            ['key' => 'body_scripts', 'label' => 'Tracking scripts — end of <body>', 'type' => 'textarea', 'default' => '', 'help' => 'For snippets that ask to be placed before </body> (e.g. GTM noscript, chat widgets).'],

            ['key' => 'maintenance_mode', 'label' => 'Maintenance mode', 'type' => 'toggle', 'default' => false, 'help' => 'When ON, visitors see a maintenance page (HTTP 503). You and other admins can still sign in, use the admin panel, and browse the live site — so you can update things safely.'],
            ['key' => 'maintenance_title', 'label' => 'Maintenance — heading', 'type' => 'text', 'default' => 'We’ll be right back', 'show_if' => ['key' => 'maintenance_mode', 'value' => true]],
            ['key' => 'maintenance_message', 'label' => 'Maintenance — message', 'type' => 'textarea', 'default' => 'We’re making things better. Our store is briefly down for scheduled maintenance and will be back online shortly. Thanks for your patience.', 'show_if' => ['key' => 'maintenance_mode', 'value' => true]],
        ],
    ],

    'delivery' => [
        'label' => 'Delivery Settings',
        'desc'  => 'Free delivery toggle and location-based delivery charges.',
        'icon'  => 'truck',
        'fields' => [
            ['key' => 'free_delivery_enabled', 'label' => 'Free delivery', 'type' => 'toggle', 'default' => false],
            ['key' => 'free_delivery_type', 'label' => 'Free delivery type', 'type' => 'segmented', 'default' => 'all',
                'options' => [
                    ['value' => 'all', 'label' => 'Free delivery for all orders'],
                    ['value' => 'minimum', 'label' => 'Above minimum purchase'],
                ],
                'show_if' => ['key' => 'free_delivery_enabled', 'value' => true]],
            ['key' => 'free_delivery_minimum_amount', 'label' => 'Minimum purchase amount (৳)', 'type' => 'number', 'default' => 1000,
                'show_if' => [
                    ['key' => 'free_delivery_enabled', 'value' => true],
                    ['key' => 'free_delivery_type', 'value' => 'minimum'],
                ]],
            ['key' => 'delivery_charges', 'label' => 'Delivery charges (per city)', 'type' => 'delivery_charges', 'help' => 'Set a delivery charge for each city. Click "Add Delivery Charge", pick one or more cities, and enter the charge — checkout uses the charge of the customer\'s selected city.'],
            ['key' => 'no_charge_message', 'label' => 'Message when a city has no delivery charge', 'type' => 'textarea', 'default' => 'Delivery is not available for the selected city yet. Please choose another city or contact us.', 'help' => 'Shown at checkout when the customer picks a city that has no delivery charge set.'],
        ],
    ],

    'invoice' => [
        'label' => 'Invoice Settings',
        'desc'  => 'Branding and content for generated invoices (website + POS).',
        'icon'  => 'doc',
        'fields' => [
            ['key' => 'company_logo', 'label' => 'Company logo', 'type' => 'image', 'default' => ''],
            ['key' => 'company_name', 'label' => 'Company name', 'type' => 'text', 'default' => 'Kizora'],
            ['key' => 'company_address', 'label' => 'Company address', 'type' => 'textarea', 'default' => 'House 7, Road 12, Banani, Dhaka 1213, Bangladesh'],
            ['key' => 'company_phone', 'label' => 'Company phone', 'type' => 'text', 'default' => '+880 1730-728954'],
            ['key' => 'company_email', 'label' => 'Company email', 'type' => 'text', 'default' => 'hello@kizora.com.bd'],
            ['key' => 'company_website', 'label' => 'Company website', 'type' => 'text', 'default' => 'www.kizora.com.bd'],
            ['key' => 'invoice_title', 'label' => 'Invoice title', 'type' => 'text', 'default' => 'INVOICE'],
            ['key' => 'header_text', 'label' => 'Header text (optional)', 'type' => 'text', 'default' => ''],
            ['key' => 'footer_message', 'label' => 'Footer message', 'type' => 'textarea', 'default' => 'Thank you for shopping with us!'],
            ['key' => 'notes', 'label' => 'Additional notes (optional)', 'type' => 'textarea', 'default' => ''],
            ['key' => 'terms', 'label' => 'Terms & conditions (optional)', 'type' => 'textarea', 'default' => ''],
            ['key' => 'primary_color', 'label' => 'Primary brand colour', 'type' => 'color', 'default' => '#17181B'],
            ['key' => 'secondary_color', 'label' => 'Secondary colour', 'type' => 'color', 'default' => '#ED1C24'],
        ],
    ],

    'chatbot' => [
        'label' => 'Chatbot Settings',
        'desc'  => 'AI sales assistant — provider, identity, design & behaviour.',
        'icon'  => 'chat',
        'fields' => [
            ['key' => 'enabled', 'label' => 'Enable chatbot', 'type' => 'toggle', 'default' => true],
            ['key' => 'provider', 'label' => 'AI provider', 'type' => 'select', 'options' => ['groq', 'openrouter'], 'default' => 'groq'],
            ['key' => 'groq_api_key', 'label' => 'Groq API key', 'type' => 'text', 'default' => ''],
            ['key' => 'openrouter_api_key', 'label' => 'OpenRouter API key', 'type' => 'text', 'default' => ''],
            ['key' => 'model', 'label' => 'Model', 'type' => 'text', 'default' => 'llama-3.3-70b-versatile'],
            ['key' => 'name', 'label' => 'Assistant name', 'type' => 'text', 'default' => 'Kizora Assistant'],
            ['key' => 'welcome_message', 'label' => 'Welcome message', 'type' => 'textarea', 'default' => 'Hi! 👋 I can help you find the perfect bag, answer questions, and even place your order. What are you looking for?'],
            ['key' => 'avatar', 'label' => 'Assistant avatar', 'type' => 'image', 'default' => ''],
            ['key' => 'widget_icon', 'label' => 'Chat widget icon', 'type' => 'image', 'default' => ''],
            ['key' => 'primary_color', 'label' => 'Primary colour', 'type' => 'color', 'default' => '#17181B'],
            ['key' => 'position', 'label' => 'Widget position', 'type' => 'select', 'options' => ['bottom-right', 'bottom-left'], 'default' => 'bottom-right'],
            ['key' => 'theme', 'label' => 'Theme', 'type' => 'select', 'options' => ['light', 'dark'], 'default' => 'light'],
            ['key' => 'rounded', 'label' => 'Rounded / modern UI', 'type' => 'toggle', 'default' => true],
        ],
    ],

    'header' => [
        'label' => 'Header & Menu',
        'desc'  => 'Main menu and utility links in the top bar. (The mega menu has its own section.)',
        'icon'  => 'layout',
        'fields' => [
            ['key' => 'nav', 'label' => 'Main menu', 'type' => 'list', 'item' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                ['key' => 'url', 'label' => 'Link (e.g. /shop)', 'type' => 'text'],
                ['key' => 'mega', 'label' => 'Open mega menu on hover', 'type' => 'toggle'],
            ], 'default' => [
                ['label' => 'Home', 'url' => '/', 'mega' => false],
                ['label' => 'Shop', 'url' => '/shop', 'mega' => true],
                ['label' => 'About', 'url' => '/about', 'mega' => false],
                ['label' => 'Contact', 'url' => '/contact', 'mega' => false],
            ]],
            ['key' => 'utility', 'label' => 'Utility links (right of menu)', 'type' => 'list', 'item' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                ['key' => 'url', 'label' => 'Link', 'type' => 'text'],
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => ['truck', 'help', 'none']],
            ], 'default' => [
                ['label' => 'Track order', 'url' => '/track-order', 'icon' => 'truck'],
                ['label' => 'Help', 'url' => '/faq', 'icon' => 'help'],
            ]],
        ],
    ],

    'megamenu' => [
        'label' => 'Mega Menu',
        'desc'  => 'The "Shop" dropdown — which categories show as columns and the promo tile.',
        'icon'  => 'layout',
        'fields' => [
            ['key' => 'categories', 'label' => 'Category columns', 'type' => 'categories',
                'help' => 'Choose which categories appear as columns in the mega menu (their sub-categories show automatically). Leave all unchecked to auto-show the first few. Order follows the Categories sort order.'],
            ['key' => 'mega_columns', 'label' => 'Max category columns (used only when none are chosen above)', 'type' => 'number', 'default' => 3],
            ['key' => 'promo_eyebrow', 'label' => 'Promo tile — eyebrow', 'type' => 'text', 'default' => 'New in'],
            ['key' => 'promo_title', 'label' => 'Promo tile — title', 'type' => 'text', 'default' => 'The 2026 carry collection'],
            ['key' => 'promo_cta', 'label' => 'Promo tile — button text', 'type' => 'text', 'default' => 'Shop new arrivals'],
            ['key' => 'promo_link', 'label' => 'Promo tile — link', 'type' => 'text', 'default' => '/shop?sort=latest'],
        ],
    ],

    'product' => [
        'label' => 'Product page',
        'desc'  => 'Product detail page labels, section titles and on/off toggles.',
        'icon'  => 'bag',
        'fields' => [
            ['key' => 'description_title',  'label' => 'Description — heading', 'type' => 'text', 'default' => 'Description'],
            ['key' => 'reviews_title',      'label' => 'Reviews — heading', 'type' => 'text', 'default' => 'Customer reviews'],
            ['key' => 'write_review_label', 'label' => 'Write-review button', 'type' => 'text', 'default' => 'Write a review'],
            ['key' => 'related_title',      'label' => 'Related products — heading', 'type' => 'text', 'default' => 'You may also like'],
            ['key' => 'assurances', 'label' => 'Assurance badges', 'type' => 'list', 'item' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => $badgeIconOptions],
            ], 'default' => [
                ['label' => 'Fast delivery', 'icon' => 'truck'],
                ['label' => '1-year warranty', 'icon' => 'shield'],
                ['label' => '7-day returns', 'icon' => 'returns'],
            ]],
            ['key' => 'show_highlights', 'label' => 'Show highlight cards (USP)', 'type' => 'toggle', 'default' => true],
            ['key' => 'show_description', 'label' => 'Show description section', 'type' => 'toggle', 'default' => true],
            ['key' => 'show_reviews', 'label' => 'Show reviews & ratings', 'type' => 'toggle', 'default' => true],
            ['key' => 'show_related', 'label' => 'Show "You may also like"', 'type' => 'toggle', 'default' => true],
        ],
    ],

    'home' => [
        'label' => 'Home page',
        'desc'  => 'Hero slider, trust badges and product rail headings.',
        'icon'  => 'home',
        'fields' => [
            ['key' => 'hero_enabled', 'label' => 'Hero slider — show on home page', 'type' => 'toggle', 'default' => true],
            ['key' => 'hero', 'label' => 'Hero banners', 'type' => 'list', 'item' => [
                ['key' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea'],
                ['key' => 'cta_label', 'label' => 'Button label', 'type' => 'text'],
                ['key' => 'cta_link', 'label' => 'Button link (e.g. /shop)', 'type' => 'text'],
                ['key' => 'tone', 'label' => 'Tone', 'type' => 'select', 'options' => ['ink', 'slate', 'accent']],
                ['key' => 'eyebrow_color', 'label' => 'Eyebrow text color (optional — empty = default)', 'type' => 'color'],
                ['key' => 'title_color', 'label' => 'Title text color (optional — empty = white)', 'type' => 'color'],
                ['key' => 'subtitle_color', 'label' => 'Subtitle text color (optional — empty = white)', 'type' => 'color'],

                // Typography — one control with Mobile/Desktop tabs (title & description
                // font size + weight, and content width). Empty = responsive design default.
                ['key' => 'typography', 'label' => 'Text sizing (title & description)', 'type' => 'hero_typography'],

                ['key' => 'image', 'label' => 'Background image — desktop (optional)', 'type' => 'image'],
                ['key' => 'mobile_image', 'label' => 'Background image — mobile (optional, falls back to desktop)', 'type' => 'image'],
                ['key' => 'overlay_type', 'label' => 'Image overlay style', 'type' => 'select', 'options' => ['solid', 'gradient', 'none']],
                ['key' => 'overlay_color', 'label' => 'Overlay color', 'type' => 'color'],
                ['key' => 'overlay_color2', 'label' => 'Overlay color 2 (gradient end)', 'type' => 'color'],
                ['key' => 'overlay_opacity', 'label' => 'Overlay opacity % (0–100)', 'type' => 'number'],
                ['key' => 'overlay_angle', 'label' => 'Gradient angle (degrees, e.g. 135)', 'type' => 'number'],
                ['key' => 'overlay_stop1', 'label' => 'Gradient — color 1 position %', 'type' => 'number'],
                ['key' => 'overlay_stop2', 'label' => 'Gradient — color 2 position %', 'type' => 'number'],
            ], 'default' => [
                ['eyebrow' => 'New in · 2026', 'title' => 'Carry your world', 'subtitle' => 'Premium backpacks, bottles & flasks built to move with you.', 'cta_label' => 'Shop new arrivals', 'cta_link' => '/shop?sort=latest', 'tone' => 'ink'],
                ['eyebrow' => 'Work & study', 'title' => 'Laptop backpacks that keep up', 'subtitle' => 'Padded sleeves, USB ports, water-resistant builds.', 'cta_label' => 'Shop laptop backpacks', 'cta_link' => '/shop?category=laptop-backpacks', 'tone' => 'slate'],
                ['eyebrow' => 'Limited time', 'title' => 'Up to 30% off drinkware', 'subtitle' => 'Insulated bottles & flasks — hot or cold for hours.', 'cta_label' => 'Shop the sale', 'cta_link' => '/shop?sale=1', 'tone' => 'accent'],
            ]],
            ['key' => 'trust_enabled', 'label' => 'Trust badges — show on home page', 'type' => 'toggle', 'default' => true],
            ['key' => 'trust', 'label' => 'Trust badges', 'type' => 'list', 'item' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'desc', 'label' => 'Description', 'type' => 'text'],
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => $badgeIconOptions],
            ], 'default' => [
                ['title' => 'Free delivery', 'desc' => 'On orders over ৳3,000', 'icon' => 'truck'],
                ['title' => '1-year warranty', 'desc' => 'On every product', 'icon' => 'shield'],
                ['title' => '7-day returns', 'desc' => 'Easy & hassle-free', 'icon' => 'returns'],
                ['title' => 'Cash on delivery', 'desc' => 'Pay when it arrives', 'icon' => 'card'],
            ]],
            ['key' => 'category_enabled', 'label' => 'Shop by Category — show on home page', 'type' => 'toggle', 'default' => true],
            ['key' => 'category_title', 'label' => 'Shop by Category — heading', 'type' => 'text', 'default' => 'Shop by category'],
            ['key' => 'category_subtitle', 'label' => 'Shop by Category — subtitle', 'type' => 'text', 'default' => 'Find the right carry for school, work, sport & travel.'],
            ['key' => 'sections', 'label' => 'Product sections', 'type' => 'list', 'add_label' => 'Add another section',
                'help' => 'Each section pulls products live from the catalogue — pick a category (or all), a sorting and how many products to show. Reorder with the arrows; inactive sections are hidden from the home page.',
                'item' => [
                    ['key' => 'title', 'label' => 'Section title', 'type' => 'text'],
                    ['key' => 'subtitle', 'label' => 'Short description', 'type' => 'text'],
                    ['key' => 'badge', 'label' => 'Badge next to the title (optional, e.g. New / Save)', 'type' => 'text'],
                    ['key' => 'category', 'label' => 'Product category', 'type' => 'category'],
                    ['key' => 'sort', 'label' => 'Product source / sorting', 'type' => 'select', 'default' => 'latest', 'options' => [
                        ['value' => 'latest', 'label' => 'Latest'],
                        ['value' => 'new_arrival', 'label' => 'New Arrival (products flagged New Arrival)'],
                        ['value' => 'featured', 'label' => 'Featured products'],
                        ['value' => 'best_selling', 'label' => 'Best selling'],
                        ['value' => 'on_sale', 'label' => 'On sale'],
                        ['value' => 'top_rated', 'label' => 'Top rated'],
                        ['value' => 'price_asc', 'label' => 'Price — low to high'],
                        ['value' => 'price_desc', 'label' => 'Price — high to low'],
                        ['value' => 'random', 'label' => 'Random'],
                    ]],
                    ['key' => 'limit', 'label' => 'Number of products to display', 'type' => 'number', 'default' => 10],
                    ['key' => 'layout', 'label' => 'Layout', 'type' => 'select', 'default' => 'rail', 'options' => [
                        ['value' => 'rail', 'label' => 'Carousel rail'],
                        ['value' => 'grid', 'label' => 'Grid'],
                    ]],
                    ['key' => 'active', 'label' => 'Active (visible on the home page)', 'type' => 'toggle', 'default' => true],
                ], 'default' => [
                    ['title' => 'New arrivals', 'subtitle' => 'Fresh drops across bags & drinkware.', 'badge' => 'New', 'category' => '', 'sort' => 'latest', 'limit' => 10, 'layout' => 'rail', 'active' => true],
                    ['title' => 'Bestsellers', 'subtitle' => 'The carry our customers reach for again and again.', 'badge' => '', 'category' => '', 'sort' => 'featured', 'limit' => 8, 'layout' => 'grid', 'active' => true],
                    ['title' => 'On sale', 'subtitle' => 'Limited-time prices on customer favourites.', 'badge' => 'Save', 'category' => '', 'sort' => 'on_sale', 'limit' => 10, 'layout' => 'rail', 'active' => true],
                    ['title' => 'More to explore', 'subtitle' => 'Fresh picks across the collection.', 'badge' => '', 'category' => '', 'sort' => 'random', 'limit' => 10, 'layout' => 'rail', 'active' => true],
                ]],

            // Blog slider — shown at the bottom of the home page, just above the footer.
            ['key' => 'blog_enabled', 'label' => 'Blog slider — show on home page', 'type' => 'toggle', 'default' => true, 'help' => 'A carousel of your latest blog posts, displayed just above the footer.'],
            ['key' => 'blog_title', 'label' => 'Blog slider — heading', 'type' => 'text', 'default' => 'From the blog', 'show_if' => ['key' => 'blog_enabled', 'value' => true]],
            ['key' => 'blog_subtitle', 'label' => 'Blog slider — subtitle', 'type' => 'text', 'default' => 'Guides, tips and stories from the Kizora team.', 'show_if' => ['key' => 'blog_enabled', 'value' => true]],
            ['key' => 'blog_limit', 'label' => 'Blog slider — number of posts', 'type' => 'number', 'default' => 8, 'show_if' => ['key' => 'blog_enabled', 'value' => true]],
        ],
    ],

    'shop' => [
        'label' => 'Shop page',
        'desc'  => 'Listing page header.',
        'icon'  => 'bag',
        'fields' => [
            ['key' => 'title', 'label' => 'Heading', 'type' => 'text', 'default' => 'Shop all'],
            ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'default' => 'Backpacks, bottles, flasks & accessories — delivered to your door.'],
            ['key' => 'header_image', 'label' => 'Header background (optional)', 'type' => 'image', 'default' => ''],
        ],
    ],

    'about' => [
        'label' => 'About page',
        'desc'  => 'Brand story, craftsmanship, mission & vision.',
        'icon'  => 'info',
        'fields' => [
            ['key' => 'hero_title', 'label' => 'Hero title', 'type' => 'text', 'default' => 'More than gear.'],
            ['key' => 'hero_highlight', 'label' => 'Hero highlight line', 'type' => 'text', 'default' => 'Built to move with you.'],
            ['key' => 'hero_subtitle', 'label' => 'Hero subtitle', 'type' => 'textarea', 'default' => 'Kizora makes premium backpacks, water bottles and flasks — engineered for everyday life, priced for everyone, and built to go the distance.'],
            ['key' => 'hero_image', 'label' => 'Hero background (optional)', 'type' => 'image', 'default' => ''],
            ['key' => 'story_badge', 'label' => 'Story — badge label', 'type' => 'text', 'default' => 'Our Story'],
            ['key' => 'story_title', 'label' => 'Story heading', 'type' => 'text', 'default' => 'How Kizora began'],
            ['key' => 'story', 'label' => 'Story paragraphs', 'type' => 'list', 'item' => [
                ['key' => 'text', 'label' => 'Paragraph', 'type' => 'textarea'],
            ], 'default' => [
                ['text' => 'Kizora started with a frustration we all know: carry gear that either looks great but falls apart, or lasts forever but looks dull — and always costs too much.'],
                ['text' => 'So we set out to build the opposite. Backpacks, bottles and flasks that are tough, comfortable and genuinely good-looking, at a price that makes sense for students, professionals and families.'],
                ['text' => 'Every product is obsessively tested — zippers, straps, seams and insulation — so your gear keeps up with school runs, commutes, gym sessions and weekend adventures.'],
            ]],
            ['key' => 'est', 'label' => 'Established label', 'type' => 'text', 'default' => 'Est. 2026'],
            ['key' => 'location', 'label' => 'Location label (shown when no story image)', 'type' => 'text', 'default' => 'Dhaka, Bangladesh'],
            ['key' => 'story_image', 'label' => 'Story image (optional)', 'type' => 'image', 'default' => ''],
            ['key' => 'craft_badge', 'label' => 'Craft — badge label', 'type' => 'text', 'default' => 'Our Craft'],
            ['key' => 'craft_title', 'label' => 'Craft heading', 'type' => 'text', 'default' => 'Engineered to last'],
            ['key' => 'craft_image', 'label' => 'Craftsmanship image (optional)', 'type' => 'image', 'default' => ''],
            ['key' => 'craft_body', 'label' => 'Craft body', 'type' => 'textarea', 'default' => "We don't cut corners where it counts. Kizora bags use water-resistant fabrics, reinforced stitching and heavy-duty zippers. Our bottles and flasks are made from food-grade stainless steel with double-wall vacuum insulation."],
            ['key' => 'mv_eyebrow', 'label' => 'Mission & Vision — eyebrow', 'type' => 'text', 'default' => 'Why we exist'],
            ['key' => 'mv_title', 'label' => 'Mission & Vision — heading', 'type' => 'text', 'default' => 'What drives us'],
            ['key' => 'mission_title', 'label' => 'Mission — card title', 'type' => 'text', 'default' => 'Our Mission'],
            ['key' => 'mission', 'label' => 'Mission', 'type' => 'textarea', 'default' => 'To make durable, well-designed everyday carry accessible to everyone — without the premium price tag.'],
            ['key' => 'vision_title', 'label' => 'Vision — card title', 'type' => 'text', 'default' => 'Our Vision'],
            ['key' => 'vision', 'label' => 'Vision', 'type' => 'textarea', 'default' => "To become Bangladesh's most trusted lifestyle gear brand — known for quality, value and service people rely on."],
            ['key' => 'offerings_title', 'label' => 'What we make — heading', 'type' => 'text', 'default' => 'What we make'],
            ['key' => 'offerings_subtitle', 'label' => 'What we make — subtitle', 'type' => 'text', 'default' => 'Everyday-carry essentials for school, work, sport and travel.'],
            ['key' => 'offerings', 'label' => 'What we make', 'type' => 'list', 'item' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'desc', 'label' => 'Description', 'type' => 'text'],
            ], 'default' => [
                ['title' => 'Backpacks', 'desc' => 'Everyday packs built for comfort and capacity'],
                ['title' => 'Laptop Backpacks', 'desc' => 'Padded sleeves and USB ports for work & study'],
                ['title' => 'School & Trolley Bags', 'desc' => 'Lightweight, sturdy bags kids love'],
                ['title' => 'Water Bottles', 'desc' => 'Leak-proof bottles for gym, office and travel'],
                ['title' => 'Thermos & Flasks', 'desc' => 'Double-wall insulation, hot or cold for hours'],
                ['title' => 'Accessories', 'desc' => 'Pouches, straps and add-ons to complete your carry'],
            ]],
            ['key' => 'cta_title', 'label' => 'Bottom CTA — heading', 'type' => 'text', 'default' => 'Find your everyday carry'],
            ['key' => 'cta_subtitle', 'label' => 'Bottom CTA — subtitle', 'type' => 'text', 'default' => "Explore the collection and gear up for wherever you're headed."],
            ['key' => 'cta_primary', 'label' => 'Bottom CTA — primary button', 'type' => 'text', 'default' => 'Shop now'],
            ['key' => 'cta_secondary', 'label' => 'Bottom CTA — secondary button', 'type' => 'text', 'default' => 'Get in touch'],
        ],
    ],

    'contact' => [
        'label' => 'Contact page',
        'desc'  => 'Heading, intro, which contact cards to show, and the embedded map. Card details come from Brand & Contact details.',
        'icon'  => 'mail',
        'fields' => [
            ['key' => 'title', 'label' => 'Heading', 'type' => 'text', 'default' => 'Get in touch'],
            ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Questions about a product, an order, or bulk pricing? We\'d love to help.'],

            ['key' => 'show_visit', 'label' => 'Show “Visit us” card', 'type' => 'toggle', 'default' => true, 'help' => 'Address comes from Brand & Contact details. Card is hidden if the address is empty.'],
            ['key' => 'show_call',  'label' => 'Show “Call us” card',  'type' => 'toggle', 'default' => true, 'help' => 'Phone comes from Brand & Contact details. Card is hidden if the phone is empty.'],
            ['key' => 'show_email', 'label' => 'Show “Email us” card', 'type' => 'toggle', 'default' => true, 'help' => 'Email comes from Brand & Contact details. Card is hidden if the email is empty.'],
            ['key' => 'show_hours', 'label' => 'Show “Opening hours” card', 'type' => 'toggle', 'default' => true, 'help' => 'Opening hours come from Brand & Contact details. Card is hidden if hours are empty.'],

            ['key' => 'show_map',  'label' => 'Show map', 'type' => 'toggle', 'default' => true],
            ['key' => 'map_embed', 'label' => 'Google Maps embed link', 'type' => 'textarea', 'default' => '', 'help' => 'In Google Maps: Share → Embed a map → copy the link (or the whole <iframe> code) and paste it here. Leave empty to use the image below as a fallback.'],
            ['key' => 'map_image', 'label' => 'Map image (fallback when no embed link)', 'type' => 'image', 'default' => ''],
        ],
    ],

    'faqs' => [
        'label' => 'FAQ',
        'desc'  => 'Frequently asked questions.',
        'icon'  => 'help',
        'fields' => [
            ['key' => 'items', 'label' => 'Questions', 'type' => 'list', 'item' => [
                ['key' => 'question', 'label' => 'Question', 'type' => 'text'],
                ['key' => 'answer', 'label' => 'Answer', 'type' => 'textarea'],
            ], 'default' => [
                ['question' => 'What does Kizora sell?', 'answer' => 'Kizora specialises in everyday-carry gear: backpacks, laptop backpacks, school and kids’ trolley bags, water bottles, thermos flasks, and accessories.'],
                ['question' => 'Do you offer delivery?', 'answer' => 'Yes, we deliver nationwide across Bangladesh. A standard delivery charge applies, and orders above ৳3,000 ship free.'],
                ['question' => 'What payment methods do you accept?', 'answer' => 'We accept Cash on Delivery (COD) as well as bKash, Nagad and card payments via SSLCommerz at checkout.'],
                ['question' => 'Is there a warranty on Kizora products?', 'answer' => 'Yes — every Kizora product comes with a 1-year warranty against manufacturing defects in materials and workmanship.'],
                ['question' => 'What is your return policy?', 'answer' => 'Unused items in original condition and packaging can be returned within 7 days of delivery. See our Terms for full details.'],
                ['question' => 'Are your water bottles and flasks leak-proof?', 'answer' => 'Our bottles are designed to be leak-proof, and our flasks use double-wall vacuum insulation that keeps drinks hot or cold for hours.'],
                ['question' => 'Do you offer bulk or wholesale orders?', 'answer' => 'Yes. We offer bulk pricing and optional custom branding for offices, schools and events — reach out via our Contact page.'],
                ['question' => 'How do I track my order?', 'answer' => 'After placing your order you’ll receive an order number. Use our Track Order page any time to check its current status.'],
            ]],
        ],
    ],

    'privacy' => [
        'label' => 'Privacy Policy',
        'desc'  => 'Privacy policy content.',
        'icon'  => 'doc',
        'fields' => [
            ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Privacy Policy'],
            ['key' => 'last_updated', 'label' => 'Last updated', 'type' => 'text', 'default' => 'June 2026'],
            ['key' => 'sections', 'label' => 'Sections', 'type' => 'list',
                'help' => 'Formatting: leave a blank line between paragraphs, and start a line with ● or - for a bullet point. Leave the heading empty for an intro block with no title.',
                'item' => [
                ['key' => 'heading', 'label' => 'Heading (optional)', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body', 'type' => 'textarea'],
            ], 'default' => [
                ['heading' => '1. Information We Collect', 'body' => 'When you use the Kizora website, we collect information including your name, email address, phone number, and delivery address when you place an order or create an account.'],
                ['heading' => '2. How We Use Your Information', 'body' => 'We use your information to process orders, provide customer support, send order updates, and improve our services. We do not sell your personal information to third parties.'],
                ['heading' => '3. Data Security', 'body' => 'We implement appropriate security measures to protect your personal information. All sensitive data is encrypted and stored securely.'],
                ['heading' => '4. Contact Us', 'body' => 'If you have any questions about our Privacy Policy, please contact us at hello@kizora.com.bd.'],
            ]],
        ],
    ],

    'terms' => [
        'label' => 'Terms & Conditions',
        'desc'  => 'Terms of service content.',
        'icon'  => 'doc',
        'fields' => [
            ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Terms & Conditions'],
            ['key' => 'last_updated', 'label' => 'Last updated', 'type' => 'text', 'default' => 'June 2026'],
            ['key' => 'sections', 'label' => 'Sections', 'type' => 'list',
                'help' => 'Formatting: leave a blank line between paragraphs, and start a line with ● or - for a bullet point. Leave the heading empty for an intro block with no title.',
                'item' => [
                ['key' => 'heading', 'label' => 'Heading (optional)', 'type' => 'text'],
                ['key' => 'body', 'label' => 'Body', 'type' => 'textarea'],
            ], 'default' => [
                ['heading' => '1. Acceptance of Terms', 'body' => 'By using the Kizora website and services, you agree to these Terms & Conditions. If you do not agree, please do not use our services.'],
                ['heading' => '2. Orders & Payment', 'body' => 'All orders are subject to availability. We reserve the right to cancel or refuse any order. For online payment methods, payment must be completed before your order is dispatched.'],
                ['heading' => '3. Delivery', 'body' => 'Delivery times are estimates and may vary based on location and demand. Orders above ৳3,000 qualify for free delivery; otherwise a standard delivery charge applies.'],
                ['heading' => '4. Returns & Warranty', 'body' => 'Unused products in original condition and packaging may be returned within 7 days of delivery. Every Kizora product is covered by a 1-year warranty against manufacturing defects. Normal wear and tear is not covered.'],
                ['heading' => '5. Contact', 'body' => 'For any queries regarding these terms, please contact us at hello@kizora.com.bd or +880 1730-728954.'],
            ]],
        ],
    ],

    'footer' => [
        'label' => 'Footer',
        'desc'  => 'Footer about text, tagline, quick links and which categories appear.',
        'icon'  => 'layout',
        'fields' => [
            ['key' => 'about', 'label' => 'About text', 'type' => 'textarea', 'default' => 'Kizora designs premium backpacks, water bottles, flasks and everyday‑carry essentials built to move with you — for school, work and the road ahead.'],
            ['key' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'Carry your world.'],
            ['key' => 'links', 'label' => 'Quick links column', 'type' => 'list', 'item' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                ['key' => 'url', 'label' => 'Link', 'type' => 'text'],
            ], 'default' => [
                ['label' => 'Home', 'url' => '/'],
                ['label' => 'Shop', 'url' => '/shop'],
                ['label' => 'About Us', 'url' => '/about'],
                ['label' => 'Contact', 'url' => '/contact'],
                ['label' => 'Track Order', 'url' => '/track-order'],
            ]],
            ['key' => 'footer_categories', 'label' => 'Categories column', 'type' => 'categories',
                'help' => 'Choose which categories appear in the footer’s Categories column. Leave all unchecked to show every category. Order follows the Categories sort order.'],
            ['key' => 'copyright', 'label' => 'Copyright text (bottom bar)', 'type' => 'text',
                'default' => '© {year} Kizora. All rights reserved.',
                'help' => 'Use {year} to auto-insert the current year.'],
            ['key' => 'legal_links', 'label' => 'Bottom-bar links (right side)', 'type' => 'list', 'item' => [
                ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                ['key' => 'url', 'label' => 'Link', 'type' => 'text'],
            ], 'default' => [
                ['label' => 'Privacy', 'url' => '/privacy'],
                ['label' => 'Terms', 'url' => '/terms'],
                ['label' => 'FAQ', 'url' => '/faq'],
            ]],
        ],
    ],

    'auth' => [
        'label' => 'Login & Register',
        'desc'  => 'Headings and marketing copy on the sign-in and create-account pages.',
        'icon'  => 'layout',
        'fields' => [
            ['key' => 'login_heading',    'label' => 'Login — side panel heading',  'type' => 'text',     'default' => 'Carry your world'],
            ['key' => 'login_subtext',    'label' => 'Login — side panel text',     'type' => 'textarea', 'default' => 'Premium backpacks, bottles & flasks designed to move with you — built to last, priced for everyone.'],
            ['key' => 'login_title',      'label' => 'Login — form heading',         'type' => 'text',     'default' => 'Welcome Back'],
            ['key' => 'login_subtitle',   'label' => 'Login — form subheading',      'type' => 'text',     'default' => 'Sign in to your account to continue'],
            ['key' => 'register_heading',  'label' => 'Register — side panel heading', 'type' => 'text',     'default' => 'Join Kizora'],
            ['key' => 'register_subtext',  'label' => 'Register — side panel text',    'type' => 'textarea', 'default' => 'Create your account to unlock exclusive deals, faster checkout, order tracking and a personalised experience.'],
            ['key' => 'register_title',     'label' => 'Register — form heading',       'type' => 'text',     'default' => 'Create Account'],
            ['key' => 'register_subtitle',  'label' => 'Register — form subheading',    'type' => 'text',     'default' => 'Join Kizora to enjoy exclusive benefits'],
            ['key' => 'register_perks', 'label' => 'Register — benefits list', 'type' => 'list', 'item' => [
                ['key' => 'text', 'label' => 'Benefit', 'type' => 'text'],
            ], 'default' => [
                ['text' => 'Track all your orders easily'],
                ['text' => 'Exclusive discounts & offers'],
                ['text' => 'Save your favourite items'],
            ]],
            ['key' => 'forgot_heading',  'label' => 'Forgot password — side panel heading', 'type' => 'text',     'default' => 'Recover Your Account'],
            ['key' => 'forgot_subtext',  'label' => 'Forgot password — side panel text',    'type' => 'textarea', 'default' => "No worries — it happens to everyone. Enter your email and we'll send you a secure reset link."],
            ['key' => 'forgot_title',     'label' => 'Forgot password — form heading',       'type' => 'text',     'default' => 'Forgot Password?'],
            ['key' => 'forgot_subtitle',  'label' => 'Forgot password — form subheading',    'type' => 'text',     'default' => "Enter your email and we'll send you a reset link."],
            ['key' => 'reset_heading',   'label' => 'Reset password — side panel heading',  'type' => 'text',     'default' => 'Set a New Password'],
            ['key' => 'reset_subtext',   'label' => 'Reset password — side panel text',     'type' => 'textarea', 'default' => "Choose a strong password you don't use anywhere else."],
            ['key' => 'reset_title',      'label' => 'Reset password — form heading',         'type' => 'text',     'default' => 'Reset Password'],
            ['key' => 'reset_subtitle',   'label' => 'Reset password — form subheading',      'type' => 'text',     'default' => 'Enter a new password for your account.'],
        ],
    ],

    'order_success' => [
        'label' => 'Order Success Page',
        'desc'  => 'The confirmation message shown after a customer places an order.',
        'icon'  => 'layout',
        'fields' => [
            ['key' => 'heading', 'label' => 'Heading', 'type' => 'text',     'default' => 'Order Placed!'],
            ['key' => 'message', 'label' => 'Message', 'type' => 'textarea', 'default' => "Thank you for your order. We'll start preparing it right away!"],
        ],
    ],

];

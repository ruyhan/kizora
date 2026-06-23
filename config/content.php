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
            ['key' => 'facebook', 'label' => 'Facebook URL', 'type' => 'url', 'default' => 'https://facebook.com/kizora'],
            ['key' => 'instagram', 'label' => 'Instagram URL', 'type' => 'url', 'default' => 'https://instagram.com/kizora'],
            ['key' => 'currency_symbol', 'label' => 'Currency symbol', 'type' => 'text', 'default' => '৳'],
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
            ['key' => 'inside_dhaka_delivery_charge', 'label' => 'Inside Dhaka delivery charge (৳)', 'type' => 'number', 'default' => 80],
            ['key' => 'outside_dhaka_delivery_charge', 'label' => 'Outside Dhaka delivery charge (৳)', 'type' => 'number', 'default' => 150],
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
        'desc'  => 'Main menu, utility links and the mega-menu promo tile.',
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
            ['key' => 'promo_eyebrow', 'label' => 'Mega menu promo — eyebrow', 'type' => 'text', 'default' => 'New in'],
            ['key' => 'promo_title', 'label' => 'Mega menu promo — title', 'type' => 'text', 'default' => 'The 2026 carry collection'],
            ['key' => 'promo_cta', 'label' => 'Mega menu promo — button text', 'type' => 'text', 'default' => 'Shop new arrivals'],
            ['key' => 'promo_link', 'label' => 'Mega menu promo — link', 'type' => 'text', 'default' => '/shop?sort=latest'],
            ['key' => 'mega_columns', 'label' => 'Mega menu — number of category columns (order follows Categories sort)', 'type' => 'number', 'default' => 3],
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
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => ['truck', 'shield', 'returns', 'card', 'spark', 'bolt', 'star', 'heart', 'check', 'clock', 'gift', 'lock']],
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
            ['key' => 'hero', 'label' => 'Hero banners', 'type' => 'list', 'item' => [
                ['key' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea'],
                ['key' => 'cta_label', 'label' => 'Button label', 'type' => 'text'],
                ['key' => 'cta_link', 'label' => 'Button link (e.g. /shop)', 'type' => 'text'],
                ['key' => 'tone', 'label' => 'Tone', 'type' => 'select', 'options' => ['ink', 'slate', 'accent']],
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
            ['key' => 'trust', 'label' => 'Trust badges', 'type' => 'list', 'item' => [
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'desc', 'label' => 'Description', 'type' => 'text'],
                ['key' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => ['truck', 'shield', 'returns', 'card', 'spark']],
            ], 'default' => [
                ['title' => 'Free delivery', 'desc' => 'On orders over ৳3,000', 'icon' => 'truck'],
                ['title' => '1-year warranty', 'desc' => 'On every product', 'icon' => 'shield'],
                ['title' => '7-day returns', 'desc' => 'Easy & hassle-free', 'icon' => 'returns'],
                ['title' => 'Cash on delivery', 'desc' => 'Pay when it arrives', 'icon' => 'card'],
            ]],
            ['key' => 'new_title', 'label' => 'New arrivals — heading', 'type' => 'text', 'default' => 'New arrivals'],
            ['key' => 'new_subtitle', 'label' => 'New arrivals — subtitle', 'type' => 'text', 'default' => 'Fresh drops across bags & drinkware.'],
            ['key' => 'best_title', 'label' => 'Bestsellers — heading', 'type' => 'text', 'default' => 'Bestsellers'],
            ['key' => 'best_subtitle', 'label' => 'Bestsellers — subtitle', 'type' => 'text', 'default' => 'The carry our customers reach for again and again.'],
            ['key' => 'sale_title', 'label' => 'On sale — heading', 'type' => 'text', 'default' => 'On sale'],
            ['key' => 'sale_subtitle', 'label' => 'On sale — subtitle', 'type' => 'text', 'default' => 'Limited-time prices on customer favourites.'],
            ['key' => 'more_title', 'label' => 'More — heading', 'type' => 'text', 'default' => 'More to explore'],
            ['key' => 'more_subtitle', 'label' => 'More — subtitle', 'type' => 'text', 'default' => 'Fresh picks across the collection.'],
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
            ['key' => 'story_title', 'label' => 'Story heading', 'type' => 'text', 'default' => 'How Kizora began'],
            ['key' => 'story', 'label' => 'Story paragraphs', 'type' => 'list', 'item' => [
                ['key' => 'text', 'label' => 'Paragraph', 'type' => 'textarea'],
            ], 'default' => [
                ['text' => 'Kizora started with a frustration we all know: carry gear that either looks great but falls apart, or lasts forever but looks dull — and always costs too much.'],
                ['text' => 'So we set out to build the opposite. Backpacks, bottles and flasks that are tough, comfortable and genuinely good-looking, at a price that makes sense for students, professionals and families.'],
                ['text' => 'Every product is obsessively tested — zippers, straps, seams and insulation — so your gear keeps up with school runs, commutes, gym sessions and weekend adventures.'],
            ]],
            ['key' => 'est', 'label' => 'Established label', 'type' => 'text', 'default' => 'Est. 2026'],
            ['key' => 'story_image', 'label' => 'Story image (optional)', 'type' => 'image', 'default' => ''],
            ['key' => 'craft_title', 'label' => 'Craft heading', 'type' => 'text', 'default' => 'Engineered to last'],
            ['key' => 'craft_image', 'label' => 'Craftsmanship image (optional)', 'type' => 'image', 'default' => ''],
            ['key' => 'craft_body', 'label' => 'Craft body', 'type' => 'textarea', 'default' => "We don't cut corners where it counts. Kizora bags use water-resistant fabrics, reinforced stitching and heavy-duty zippers. Our bottles and flasks are made from food-grade stainless steel with double-wall vacuum insulation."],
            ['key' => 'mission', 'label' => 'Mission', 'type' => 'textarea', 'default' => 'To make durable, well-designed everyday carry accessible to everyone — without the premium price tag.'],
            ['key' => 'vision', 'label' => 'Vision', 'type' => 'textarea', 'default' => "To become Bangladesh's most trusted lifestyle gear brand — known for quality, value and service people rely on."],
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
        ],
    ],

    'contact' => [
        'label' => 'Contact page',
        'desc'  => 'Contact page heading & intro (cards use Brand & Contact details).',
        'icon'  => 'mail',
        'fields' => [
            ['key' => 'title', 'label' => 'Heading', 'type' => 'text', 'default' => 'Get in touch'],
            ['key' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Questions about a product, an order, or bulk pricing? We\'d love to help.'],
            ['key' => 'map_image', 'label' => 'Location / map image (optional)', 'type' => 'image', 'default' => ''],
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
            ['key' => 'sections', 'label' => 'Sections', 'type' => 'list', 'item' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text'],
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
            ['key' => 'sections', 'label' => 'Sections', 'type' => 'list', 'item' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text'],
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
        'desc'  => 'Footer about text, tagline and quick links (the Categories column is automatic).',
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
        ],
    ],

];

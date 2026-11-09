-- Use the requested search themes in relevant category metadata and useful buying advice.
-- Leave any titles, descriptions, or page text already written by the shop owner unchanged.
UPDATE product_categories
SET
  seo_title = COALESCE(NULLIF(seo_title, ''), CASE slug
    WHEN 'laptops' THEN 'Laptops in Nairobi | Prices & Deals | Shop ICT Gadgets'
    WHEN 'brand-new-laptops' THEN 'Brand New Laptops in Kenya | Shop ICT Gadgets'
    WHEN 'gaming-laptops' THEN 'Gaming Laptops for Sale in Kenya | Shop ICT Gadgets'
    WHEN 'macbooks' THEN 'MacBook Air & Pro Prices in Kenya | Shop ICT Gadgets'
    WHEN 'ex-uk-laptops' THEN 'Refurbished Laptops in Nairobi | Shop ICT Gadgets'
    WHEN 'business-laptops' THEN 'Business Laptops in Nairobi | Shop ICT Gadgets'
    WHEN 'student-laptops' THEN 'Student Laptops in Kenya | Shop ICT Gadgets'
    WHEN 'workstation-laptops' THEN 'Workstation Laptops in Kenya | Shop ICT Gadgets'
    WHEN 'monitors' THEN 'Computer Monitors in Nairobi | Prices | Shop ICT Gadgets'
    WHEN 'gaming-monitors' THEN 'Gaming Monitors in Nairobi | Shop ICT Gadgets'
    WHEN 'curved-monitors' THEN 'Curved Monitors in Kenya | Shop ICT Gadgets'
    WHEN '4k-uhd-monitors' THEN '4K Monitors in Kenya | Shop ICT Gadgets'
    WHEN 'office-monitors' THEN 'Office Monitors in Kenya | Shop ICT Gadgets'
    WHEN 'ultrawide-monitors' THEN 'Ultrawide Monitors in Kenya | Shop ICT Gadgets'
    WHEN 'accessories' THEN 'Computer Accessories in Nairobi | Shop ICT Gadgets'
    WHEN 'laptop-bags' THEN 'Laptop Bags in Nairobi | Shop ICT Gadgets'
    WHEN 'keyboards' THEN 'Computer Keyboards in Nairobi | Shop ICT Gadgets'
    WHEN 'wireless-mice' THEN 'Wireless Mouse in Kenya | Shop ICT Gadgets'
    WHEN 'usb-flash-drives' THEN 'USB Flash Drives in Nairobi | Shop ICT Gadgets'
    WHEN 'external-hard-drives' THEN 'External Hard Drives in Nairobi | Shop ICT Gadgets'
  END),
  meta_description = COALESCE(NULLIF(meta_description, ''), CASE slug
    WHEN 'laptops' THEN 'Browse laptops for sale in Nairobi and buy laptops online in Kenya. Compare laptop prices, brands and specifications, with pickup or countrywide delivery.'
    WHEN 'brand-new-laptops' THEN 'Explore brand new laptops in Kenya at Shop ICT Gadgets. Compare current models, specifications and prices, with Nairobi pickup and delivery across Kenya.'
    WHEN 'gaming-laptops' THEN 'Compare gaming laptops for sale in Kenya by processor, graphics, memory and price. Check current stock at Shop ICT Gadgets in Nairobi.'
    WHEN 'macbooks' THEN 'Compare available MacBook Air and MacBook Pro models in Kenya. Check specifications, current prices, Nairobi pickup and countrywide delivery.'
    WHEN 'ex-uk-laptops' THEN 'Browse refurbished and Ex-UK laptops in Nairobi. Check each laptop’s condition, specifications, price and warranty details before ordering.'
    WHEN 'business-laptops' THEN 'Find business laptops in Nairobi for office work, travel and professional use. Compare available models, specifications and current prices.'
    WHEN 'student-laptops' THEN 'Compare student laptops in Kenya for campus, university and college. Check specifications and current prices, with pickup in Nairobi or delivery.'
    WHEN 'workstation-laptops' THEN 'Compare workstation laptops in Kenya for demanding professional workloads. Review each model’s processor, memory, graphics and current price.'
    WHEN 'monitors' THEN 'Browse monitors for sale in Nairobi and compare computer monitor prices in Kenya. Check screen size, resolution and features before ordering.'
    WHEN 'gaming-monitors' THEN 'Compare gaming monitors in Nairobi by resolution, refresh rate and connectivity. See current models and prices at Shop ICT Gadgets.'
    WHEN 'curved-monitors' THEN 'Explore curved monitors in Kenya for work, entertainment and gaming. Compare available screen sizes, specifications and current prices.'
    WHEN '4k-uhd-monitors' THEN 'Browse 4K UHD monitors in Kenya. Compare resolution, screen features and current prices, with Nairobi pickup and delivery options.'
    WHEN 'office-monitors' THEN 'Compare office monitors in Kenya for spreadsheets, documents and everyday work. Review screen details and current prices before buying.'
    WHEN 'ultrawide-monitors' THEN 'Explore ultrawide monitors in Kenya for expanded workspace and multitasking. Check available models, specifications and prices.'
    WHEN 'accessories' THEN 'Shop computer accessories in Nairobi, including laptop and desktop essentials. Compare current products and prices, with delivery across Kenya.'
    WHEN 'laptop-bags' THEN 'Browse laptop bags in Nairobi and choose a size that fits your device. Check product details, current prices and delivery options.'
    WHEN 'keyboards' THEN 'Explore computer keyboards in Nairobi, including wired and wireless options where available. Compare features and current prices.'
    WHEN 'wireless-mice' THEN 'Browse wireless mice in Kenya for laptops and desktop computers. Compare available models, features and current prices.'
    WHEN 'usb-flash-drives' THEN 'Compare USB flash drives in Nairobi by capacity and product details. Check current prices and delivery options at Shop ICT Gadgets.'
    WHEN 'external-hard-drives' THEN 'Browse external hard drives in Nairobi for portable storage. Compare available capacities, specifications and current prices.'
  END),
  intro = COALESCE(NULLIF(intro, ''), CASE slug
    WHEN 'laptops' THEN 'Shop laptops in Nairobi at Shop ICT Gadgets. Compare current laptop prices in Kenya, available brands and specifications, then order online for pickup or countrywide delivery.'
    WHEN 'gaming-laptops' THEN 'Compare gaming laptops by the specifications that matter for the games and creative applications you use. Check each listing for its actual processor, graphics, memory, storage, price and availability.'
    WHEN 'macbooks' THEN 'Explore available Apple MacBook models, including MacBook Air and MacBook Pro listings. Compare the exact model, chip, memory, storage, condition and price shown on each product page.'
    WHEN 'student-laptops' THEN 'Students choosing a laptop for university or college can compare portability, battery, memory, storage and the software required for their course. Check each product page for current specifications and price.'
    WHEN 'business-laptops' THEN 'Compare business laptops for office work, professional applications and travel. Consider the software you use, screen and keyboard comfort, portability, memory and warranty details.'
    WHEN 'monitors' THEN 'Shop computer monitors in Nairobi and compare current monitor prices in Kenya. Check screen size, resolution, panel, refresh rate and connections against your workspace and intended use.'
    WHEN 'gaming-monitors' THEN 'Choose a gaming monitor by checking resolution, refresh rate, response time and the graphics hardware you use. Confirm the specifications on each listing before ordering.'
    WHEN 'accessories' THEN 'Find computer and laptop accessories in Nairobi, from bags and input devices to storage and adapters. Check compatibility, product details and current prices before purchase.'
    WHEN 'desktop-computers' THEN 'Compare desktop computers and all-in-one PCs for home, office and professional work. Review the processor, memory, storage, display and included accessories on each listing.'
  END),
  guide = COALESCE(NULLIF(guide, ''), CASE slug
    WHEN 'laptops' THEN '## Choose a laptop for your work\n\nFor university and college, compare student laptops by weight, battery life, durability and the requirements of your course. For programming, software development, office work and business, check the exact processor, memory, keyboard, ports and operating-system requirements of the applications you use.\n\nFor graphic design, Photoshop, Illustrator, video editing, Premiere Pro, animation or 3D rendering, compare display quality, memory, storage and graphics requirements for your software. Architecture and engineering students should check the minimum specifications for their specific CAD and design tools. Traders using forex, day-trading, online-trading or stock-trading platforms may value a responsive computer and external-display support. Verify the platform requirements and ports before choosing.\n\nThere is no single best laptop for every student or professional. Compare the actual specifications and condition on each product page, and ask the shop if you need help matching a model to your workload.\n\n## Compare specifications and prices\n\nCompare laptop prices in Kenya alongside the exact model, processor, RAM, SSD capacity, screen and warranty. Check whether a listing is brand new or refurbished, and confirm what is included. Available models and specifications can change as stock changes.'
    WHEN 'monitors' THEN '## Choose a monitor for your setup\n\nFor office work, programming, graphic design, video editing or trading, compare screen size, resolution, panel type, brightness, stand adjustments and available connections. A dual-monitor setup can help with multitasking when the computer supports the required displays and ports.\n\nFor gaming, check that a monitor resolution and refresh rate suit both your graphics hardware and the games you play. Verify the advertised specifications on the exact product listing. Screen size, refresh rate and panel features vary by model.\n\n## Compare monitor prices\n\nCompare current computer monitor prices in Kenya alongside resolution, panel, connectivity, warranty and intended use. Product availability and prices may change.'
    WHEN 'accessories' THEN '## Check compatibility before buying\n\nFor laptop chargers, batteries, RAM upgrades, SSD drives, docking stations, cooling pads, stands and hubs, confirm the exact device model and supported specifications before ordering. Check connector type, voltage, capacity and compatibility information on the listing or ask the shop.\n\nFor keyboards, wireless mice, gaming headsets, webcams, speakers, cables and adapters, compare the connection type, operating-system support and included accessories. For storage, compare capacity and interface as well as price.'
  END)
WHERE slug IN (
  'laptops', 'brand-new-laptops', 'gaming-laptops', 'macbooks', 'ex-uk-laptops',
  'business-laptops', 'student-laptops', 'workstation-laptops', 'monitors',
  'gaming-monitors', 'curved-monitors', '4k-uhd-monitors', 'office-monitors',
  'ultrawide-monitors', 'accessories', 'laptop-bags', 'keyboards', 'wireless-mice',
  'usb-flash-drives', 'external-hard-drives', 'desktop-computers'
);

-- Brand landing pages use focused copy only for brands that have active matching products.
UPDATE brands b
SET
  seo_title = COALESCE(NULLIF(b.seo_title, ''), CASE LOWER(b.name)
    WHEN 'hp' THEN 'HP Laptops in Nairobi | Prices & Deals | Shop ICT Gadgets'
    WHEN 'dell' THEN 'Dell Laptops in Nairobi | Prices & Deals | Shop ICT Gadgets'
    WHEN 'lenovo' THEN 'Lenovo Laptops in Kenya | Prices & Deals | Shop ICT Gadgets'
    WHEN 'apple' THEN 'Apple MacBook Prices in Kenya | Shop ICT Gadgets'
  END),
  meta_description = COALESCE(NULLIF(b.meta_description, ''), CASE LOWER(b.name)
    WHEN 'hp' THEN 'Browse HP laptops and computers in Nairobi. Compare available models, specifications and current HP laptop prices in Kenya at Shop ICT Gadgets.'
    WHEN 'dell' THEN 'Explore Dell laptops and computers in Kenya. Check available models, specifications and current Dell laptop prices at Shop ICT Gadgets in Nairobi.'
    WHEN 'lenovo' THEN 'Compare Lenovo laptops in Kenya for study, work and business. Review available models, specifications and current prices at Shop ICT Gadgets.'
    WHEN 'apple' THEN 'Compare available Apple MacBook models in Kenya, including MacBook Air and MacBook Pro listings. Check specifications and current prices.'
  END)
WHERE LOWER(b.name) IN ('hp', 'dell', 'lenovo', 'apple')
  AND EXISTS (
    SELECT 1 FROM products p
    WHERE p.is_active = 1
      AND (LOWER(p.brand) = LOWER(b.name) OR LOWER(p.title) LIKE CONCAT(LOWER(b.name), ' %'))
  );

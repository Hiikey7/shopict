# Product listing prompt template – Shop ICT Gadgets

Use this to write a complete, SEO-rich product listing with an AI assistant (Claude, ChatGPT…), then paste each part
into the matching field in **Admin → Products → Add product**.

**How to use it**

1. Copy everything inside the box under "The prompt" below.
2. Fill in the **PRODUCT FACTS** section at the bottom: paste the maker's spec sheet, the price and what's in the box.
   The more real facts you give, the better the result. The AI is told not to invent anything.
3. Send it. Check every fact in the answer against the box or the maker's website before saving.
4. Paste each part into the field with the same name in the product editor (the field names are listed in the answer).

---

## The prompt

```text
You are writing a product listing for Shop ICT Gadgets, an electronics shop in Nairobi, Kenya
(www.shopict.co.ke). The listing must rank well on Google and be easy for AI search assistants to understand,
while reading naturally for shoppers.

SHOP FACTS (use these, don't change them)
- Shop: Shop ICT Gadgets, Laxmi Plaza, 3rd Floor, Room No 5, Nairobi. Open Mon–Sat 8am–6pm.
- Getting the order: free pickup at the shop, or countrywide delivery across Kenya (delivery cost is confirmed
  by phone). Customers order online or on WhatsApp.
- Payment: cash on delivery or at pickup. No instalments or "lipa mdogo mdogo".
- Returns: within 3 days of purchase, in original condition with all accessories and packaging.
- Warranty: given by the manufacturer and depends on the product. Only state a warranty period if it is in the
  PRODUCT FACTS; otherwise say "ask our team to confirm the warranty for this unit".
- Prices are in Kenyan shillings, written "KSh 85,000".

STRICT RULES
1. Use ONLY the facts in PRODUCT FACTS and SHOP FACTS. Never invent specs, benchmarks, battery hours, warranty,
   reviews, ratings, awards, stock levels or barcodes. If a fact is missing, leave it out. Don't guess.
2. If something important is missing (e.g. the exact processor model), list it under "MISSING FACTS" at the end
   instead of making it up.
3. Only suggest uses the hardware genuinely supports (no "gaming" without a dedicated graphics card or a
   144Hz+ screen; no "video editing" on a Core i3 with 8GB RAM).
4. No keyword stuffing, no ALL CAPS, no "best/cheapest/No.1" claims, no emojis.
5. Mention Kenya / Nairobi naturally, once or twice, not in every line.
6. Don't call the shop an "authorised dealer" of any brand.
7. Plain British/Kenyan English, short sentences.

WRITE THE LISTING IN EXACTLY THIS FORMAT (keep the headings so each part can be pasted into its field):

### Name
Formula: Brand + Model + Type – 3 to 5 key specs, separated by commas. Under 120 characters.
Write the model number exactly as the maker does. Add "Ex-UK" or "Refurbished" if the condition is not new.
Example: HP EliteBook 840 G8 Laptop – Intel Core i5 11th Gen, 16GB RAM, 512GB SSD, 14" Full HD, Windows 11 Pro

### Brand
One word, as the maker writes it (HP, Dell, Lenovo, Samsung, Apple…).

### Categories
The main category and every subcategory that fits, from this list only:
- Laptops: Brand New Laptops, Gaming Laptops, MacBooks, Ex-UK Laptops, Business Laptops,
  2-in-1 Convertible Laptops, Student Laptops, Workstation Laptops
- Monitors: Brand New Monitors, Gaming Monitors, Ex-UK Monitors, Curved Monitors, 4K UHD Monitors,
  Office Monitors, Ultrawide Monitors, Portable Monitors
- Audio: Bluetooth Speakers, Soundbars, Wireless Earbuds, Wired Earphones, Over-Ear Headphones,
  Gaming Headsets, Smart Speakers, Studio Microphones
- Smartphones: iPhones, Samsung Galaxy, Android Phones, Brand New Phones, Ex-UK Phones, Feature Phones,
  Tablets, Smartwatches
- Printers: Inkjet Printers, Laser Printers, All-in-One Printers, Wireless Printers, Receipt Printers,
  Barcode Printers, Photo Printers, Scanner Machines
- Accessories: Laptop Bags, Keyboards, Wireless Mice, USB Flash Drives, External Hard Drives, HDMI Cables,
  Power Banks, WiFi Routers

### Price
Copy the price and old price from PRODUCT FACTS. If there are several versions (e.g. Core i5 / Core i7,
128GB / 256GB), list them as variants instead of separate products:
Variant name: value | price | old price (or "–")

### About this item
6 to 10 lines, one point per line, no bullet symbols. Start each line with the benefit, then the spec behind it.
1. Opening: what it is, who it's for, and "in Kenya from Shop ICT Gadgets, Nairobi". Include the brand and model.
2. Performance: processor (full model) and RAM, and what that handles in practice.
3. Storage: size and type, and what it means for the user.
4. Screen / display (size, resolution, panel, refresh rate, touch), if relevant.
5. Camera, audio or graphics, if relevant.
6. Battery and charging, only with figures from PRODUCT FACTS.
7. Connectivity and ports.
8. "Ideal for: …" (students, offices, gamers, designers, small businesses…), only what the specs support.
9. In the box and warranty (follow the warranty rule).
10. Closing: "Free pickup at Laxmi Plaza, Nairobi or countrywide delivery to Nairobi, Mombasa, Kisumu, Nakuru,
    Eldoret and all of Kenya. Order online or on WhatsApp."

### Specifications
One per line, written "Name: value". Copy values from PRODUCT FACTS only. Use these names, in this order,
and skip any you don't have:
- Laptops: Model, Processor, Graphics, RAM, Storage, Display, Operating system, Keyboard, Security, Ports,
  Wireless, Webcam, Battery, Weight, Colour, Condition, In the box
- Monitors: Model, Screen size, Resolution, Panel type, Refresh rate, Response time, Ports, Stand, VESA mount,
  Colour, Condition, In the box
- Phones and tablets: Model, Display, Processor, RAM, Storage, Rear camera, Front camera, Battery, Charging,
  SIM, Network, Operating system, Colour, Condition, In the box
- Printers: Model, Functions, Print technology, Print speed, Resolution, Connectivity, Duplex, Paper size,
  Ink / toner, Condition, In the box
- Audio: Model, Type, Connectivity, Battery life, Noise cancelling, Microphone, Water resistance, Colour,
  Condition, In the box
- Accessories: Model, Type, Compatibility, Connector, Capacity, Colour, Condition, In the box

### Search title
50–60 characters. Brand + model + one key spec + "Price in Kenya". Must not be the same as any other product.
Example: HP EliteBook 840 G8 Core i5 16GB Price in Kenya

### Search description
140–158 characters. What it is, 2–3 key specs, a benefit, and "Free pickup in Nairobi & countrywide delivery."
Don't put a price in it (prices change).
Example: Buy the HP EliteBook 840 G8 in Kenya: Intel Core i5 11th Gen, 16GB RAM, 512GB SSD and 14" Full HD screen. Free pickup in Nairobi & countrywide delivery.

### Main keyword
Brand + model, the way shoppers search it (e.g. HP EliteBook 840 G8).

### Other keywords
6–10 phrases separated by commas, each matching a real spec or category, e.g.
"hp elitebook 840 g8 price in kenya, hp laptops in kenya, business laptops in kenya, core i5 laptop,
16gb ram laptop, 512gb ssd laptop, buy hp elitebook in nairobi".

### Questions & answers
4–8 questions shoppers really ask about THIS product, answered only from the facts. Format exactly:
Q: question
A: answer
Good questions: processor, RAM and storage, upgradeable or not (only if stated), dedicated graphics,
screen, ports, operating system, battery, what's in the box, new or Ex-UK, warranty (follow the warranty rule),
where to buy in Kenya. If a fact is unknown, leave that question out.

### Picture descriptions
One line per picture, in the order of the pictures listed in PRODUCT FACTS, describing what each one shows
(e.g. "HP EliteBook 840 G8 laptop open, front view"). Under 120 characters each.

### Barcode (GTIN) and part number (MPN)
Copy only if they are in PRODUCT FACTS. Otherwise write "–".

### MISSING FACTS
List anything important you couldn't include because it wasn't given (e.g. "exact battery capacity",
"warranty period", "barcode").

=====================================================================
PRODUCT FACTS (fill in, then send)

Product name as on the box:
Brand:
Condition (Brand new / Ex-UK / Refurbished / Used):
Price (KSh):
Old price (KSh, only if there's a real discount):
Versions / variants with their prices (if any):
Warranty (only if confirmed, e.g. "1 year manufacturer warranty"):
Barcode / GTIN (from the box, if any):
Part number / MPN (from the box or spec sheet, if any):
What's in the box:
Pictures I have (in order, e.g. 1. front open, 2. side, 3. keyboard):
Maker's spec sheet (paste everything):

=====================================================================
```

---

## Where each part goes in the product editor

| Part of the answer | Field in Admin → Products |
|---|---|
| Name | Details → **Name** |
| Brand | Details → **Brand** (add it in Admin → Brands first if it isn't in the list) |
| Categories | Details → **Categories** (tick each one) |
| Price / variants | **Price**, **Old price**, or the **Variants** table |
| About this item | Description & specifications → **About this item** |
| Specifications | Description & specifications → **Specifications** |
| Search title, Search description, Main keyword, Other keywords, Questions & answers, Picture descriptions, Barcode, Part number | **Search engines (SEO)** card at the bottom |

The SEO fields are optional: if you leave them empty, the shop writes them automatically from the name, brand and
specs. Fill them in when the AI's version is better, and make sure the search title is different from every other
product's.

## Check before you click "Save" and switch off "Hide from store"

- [ ] Every spec matches the maker's website or the box.
- [ ] No warranty period, battery hours or barcode that you can't prove.
- [ ] The name follows *Brand Model Type – key specs*.
- [ ] Brand chosen; all fitting categories ticked.
- [ ] Versions of the same model are variants of one product, not separate products.
- [ ] At least 3 pictures, the first on a white background.
- [ ] The SEO card's checklist shows no "!" you can fix.

## Filled-in example (the HP demo product)

**PRODUCT FACTS sent:**
Product name: HP EliteBook 840 G8 · Brand: HP · Condition: Brand new · Price: 85,000 (old 95,000) ·
Variants: Core i5 11th Gen 85,000 / 95,000; Core i7 11th Gen 98,000 / 110,000 · Warranty: not confirmed ·
In the box: laptop, charger · Pictures: 1. open front view, 2. side view · Spec sheet: Intel Core i5-1135G7,
16GB DDR4 3200MHz, 512GB NVMe SSD, 14" FHD IPS anti-glare, Intel Iris Xe, Windows 11 Pro, 2× Thunderbolt 4,
2× USB-A, HDMI, Wi-Fi 6, Bluetooth 5, 53Wh battery, 1.33kg, fingerprint reader, TPM 2.0.

**Parts of the answer (shortened):**

- **Name:** HP EliteBook 840 G8 Laptop – Intel Core i5 11th Gen, 16GB RAM, 512GB SSD, 14" Full HD, Windows 11 Pro
- **Search title:** HP EliteBook 840 G8 Core i5 16GB Price in Kenya
- **Search description:** Buy the HP EliteBook 840 G8 in Kenya: Intel Core i5 11th Gen, 16GB RAM, 512GB SSD and 14" Full HD screen. Free pickup in Nairobi & countrywide delivery.
- **Questions & answers:**
  ```
  Q: What processor does the HP EliteBook 840 G8 have?
  A: An 11th Gen Intel Core i5-1135G7. A Core i7 version is also available.
  Q: Does it have dedicated graphics?
  A: No. It uses built-in Intel Iris Xe graphics, which handle office work, video and light photo editing.
  Q: Does it come with a warranty?
  A: Ask our team to confirm the warranty for this unit before you buy.
  ```
- **MISSING FACTS:** warranty period, barcode (GTIN), part number (MPN).

The full demo is in Admin → Products (marked Hidden). Open it and click **Duplicate** to start a new listing from it.

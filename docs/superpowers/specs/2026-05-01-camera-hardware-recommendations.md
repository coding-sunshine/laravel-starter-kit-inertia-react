# Camera Hardware Recommendations
## AI Rake Vision — India, April 2026

Companion document to the AI Camera Rake Identification proposal. Customer references this when procuring cameras for Pakur / Dumka / Kurwa sidings.

---

## 1. Critical Regulatory Note (April 2026)

**From 1 April 2026, only ER-compliant CCTV cameras can be legally sold and installed in India** (MeitY Office Memorandum, 16 January 2026).

- Compliance verified via **STQC certification** under the IoT System Certification Scheme (IoTSCS) and **BIS registration** under the Compulsory Registration Order.
- Requirements: unique-password setup, video encryption, digitally-signed firmware, tamper-resistant ports.
- Already-installed non-compliant cameras may continue operating, but **new installations must be ER-compliant**.
- Customer **must** confirm the camera model carries valid STQC certification before purchase. Ask the dealer for the STQC certificate number.

This rules out most legacy Chinese-origin SKUs unless the importer has obtained STQC clearance for the specific model.

---

## 2. Application Profile

Rake-wagon serial OCR shares the same engineering profile as **ANPR** (Automatic Number Plate Recognition), with these adjustments:

| Factor | Wagon serial | Standard ANPR |
|---|---|---|
| Character size | Larger (paint, ~10-15 cm) | Smaller (number plate) |
| Speed | Slow (10-30 km/h passing) | Fast (up to 80 km/h) |
| Ambient | Dust, coal, heat, monsoon | Mostly road dust |
| Distance | 8-25 m typical | 3-15 m typical |
| Lighting | Daylight + IR night | Daylight + IR night |

**Bottom line:** an ANPR-class camera is over-engineered for wagon serials, and a general 4 MP varifocal bullet is under-engineered if mounted >15 m away or facing into glare. Pick mid-range based on mounting distance.

---

## 3. Recommended Models

### Tier 1 — Purpose-built ANPR (recommended primary)

| Model | Resolution / Lens | India price (April 2026) | Notes |
|---|---|---|---|
| **Dahua DHI-ITC413-PW4D-IZ1** | 4 MP, 2.7–12 mm motorised varifocal, IP67/IK10 | **~₹48,500** | Built for character OCR up to 8 m, WDR, integrated IR. Strong value. |
| **Hikvision iDS-TCM403-BI** | 4 MP ANPR, IP67 | **~₹59,500** | Premium ANPR processing onboard. |
| **Hikvision iDS-2CD7A46G0/P-IZHS(Y)** | 4 MP DeepinView ANPR, 2.8–12 mm motorised varifocal | quote on request (₹70K–1L est.) | DeepinView AI on-camera; overkill if AI runs cloud-side. |

### Tier 2 — General 4 MP varifocal bullet (budget option)

| Model | Resolution / Lens | India price (April 2026) | Notes |
|---|---|---|---|
| **CP Plus CP-UNC-TC41ZL6C-VMD-LQ** | 4 MP, 2.7–13.5 mm motorised varifocal, IP67, 60 m IR | **~₹11,950** | STQC compliant, decent for short-distance mount (<12 m). |
| **CP Plus CP-UNC-TA41ZPL5-MD** | 4 MP, 2.8–12 mm motorised varifocal, IP67, 50 m IR | **~₹7,950** | Entry-level. Use only with good ambient lighting and short mount distance. |

### Tier 3 — Indian-make (procurement-preference)

| Model | Resolution / Lens | India price (April 2026) | Notes |
|---|---|---|---|
| **Matrix SATATYA Project Series Bullet (5 MP)** | 5 MP Sony STARVIS, varifocal, IP67/IK10 | quote on request (₹25K–40K est.) | Indian OEM, ER-compliant by design, preferred under "Make in India" / GeM procurement. |

---

## 4. Recommendation by Siding Profile

| Mount distance to rake | Recommended | Backup |
|---|---|---|
| 8–12 m, controlled lighting | CP Plus CP-UNC-TC41ZL6C (₹12K) | Matrix SATATYA 4 MP |
| 12–20 m, mixed lighting | **Dahua ITC413-PW4D-IZ1 (₹48.5K)** | Hikvision iDS-TCM403-BI |
| 20+ m or harsh glare/IR-night-critical | Hikvision iDS-2CD7A46G0/P-IZHS | Dahua ITC431-RW1F-IRL8 (10–40 mm varifocal) |

**Default recommendation for Pakur/Dumka/Kurwa: Dahua DHI-ITC413-PW4D-IZ1** — best balance of OCR-grade optics, ER-compliance availability through Indian channel partners, and reasonable price.

---

## 5. Total Per-Siding Hardware Cost (April 2026)

Assumes recommended Dahua tier-1 camera + accessories:

| Item | ₹ |
|---|---|
| Dahua DHI-ITC413-PW4D-IZ1 (4 MP ANPR varifocal, ER-compliant) | 48,500 |
| Industrial IP67 enclosure with sun-shade (coal-dust rated) | 15,000 – 25,000 |
| PoE injector / PoE switch port + Cat6e outdoor cable (50 m) | 4,000 – 8,000 |
| Mounting pole / wall bracket | 3,000 – 8,000 |
| Surge protector + UPS (1 kVA) for camera and edge power | 10,000 – 15,000 |
| Installation, alignment, RTSP commissioning (1 engineer-day) | 25,000 – 40,000 |
| **Total per siding** | **₹1.05 – 1.45 L** |

For three sidings (Pakur, Dumka, Kurwa): **~₹3.2 – 4.4 L total CAPEX**.

Internet connectivity (≥10 Mbps wired or 4G failover) is in addition. Recurring connectivity ~₹1.5–3 K/month/siding.

---

## 6. Procurement Checklist for Customer

Before placing the PO, verify with the dealer:

1. STQC certificate number for the specific camera model
2. BIS registration number under CRO
3. Warranty: 2 years standard, ask for 3-year extended for industrial siding deployment
4. AMC quote for annual cleaning + alignment (recommend quarterly visits at coal sidings)
5. Spare-parts SLA (lens, housing) — coal sites kill cameras faster than office installations
6. RTSP credentials and mainstream/substream URL format documented up front (the AI integration team needs these)
7. Public IP / VPN access path agreed with the customer's IT team

---

## 7. Sources

- [Dahua DHI-ITC413-PW4D-IZ1 — NiceDeal (India)](https://www.nicedealonline.com/product-page/dahua-ip-4mp-anpr-cctv-camera-dhi-itc413-pw4d-iz1)
- [Hikvision iDS-TCM403-BI — NiceDeal (India)](https://www.nicedealonline.com/product-page/hikvision-pro-ip-4mp-anpr-smart-monitoring-camera-ids-tcm403-bi)
- [Hikvision iDS-2CD7A46G0/P-IZHS(Y) — Hikvision Global](https://www.hikvision.com/en/products/IP-Products/Network-Cameras/DeepinView-Series/ids-2cd7a46g0-p-izhs-y-/)
- [CP Plus 4 MP Varifocal Bullet (CP-UNC-TC41ZL6C-VMD-LQ) — NiceDeal](https://www.nicedealonline.com/product-page/cp-plus-ip-4mp-wdr-varifocal-bullet-cctv-cp-unc-tb41zl6-vmds)
- [CP Plus 4 MP Varifocal Bullet (CP-UNC-TA41ZPL5-MD) — NiceDeal](https://www.nicedealonline.com/product-page/cp-plus-ip-4mp-wdr-vari-focal-bullet-2-8-12mm-50mtrs-cctv-cp-unc-ta41zl5-md)
- [Matrix Project Series Bullet IP Cameras](https://www.matrixcomsec.com/product/project-series-bullet-ip-cameras/)
- [STQC / ER Compliance Mandate Explainer — Matrix Comsec](https://www.matrixcomsec.com/stqc-certification-er-compliance-for-cctv-cameras/)
- [CCTV New Rule 2026 India — Velvu](https://www.velvu.in/blog/cctv-new-rule-2026-india-stqc-certification)
- [Industrial Safety Review — April 1 2026 Compliance Deadline](https://www.isrmag.com/indias-cctv-industry-faces-a-compliance-deadline/)

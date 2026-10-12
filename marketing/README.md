# Marketing & sales kit

| File | What it's for |
|---|---|
| `config.json` | **Edit first:** your phone, WhatsApp, email, domain and prices. Everything below reads from it. |
| `build.ps1` | Rebuilds the website and all PDFs after you edit `config.json` (Windows; uses Microsoft Edge for correct Tamil). |
| `dist/Scholar-ERP-Brochure.pdf` | 2-page A4 brochure: English front, Tamil back, WhatsApp QR code. Print double-sided. |
| `dist/Scholar-ERP-Price-Sheet.pdf` | 1-page A4 price sheet: all three pricing options. |
| `dist/quotation.html` | Quotation maker: open in a browser, fill in the school, then Print → Save as PDF. Calculates the discount, GST and amount in words. |
| `dist/website/index.html` | Product website (English / தமிழ்). `setup.sh` installs it as your domain's home page; **Login** opens the app. |
| `SALES-PLAYBOOK.md` | Visit checklist, discovery questions, 15-minute demo script, objection handling and WhatsApp messages (EN + TA), follow-up rhythm, onboarding checklist. |
| `GO-TO-MARKET.md` | 90-day plan: Karur visits first, then online. Funnel targets, budget, weekly rhythm, social post ideas, guardrails. |
| `SELLING-CHANNELS.md` | Where to sell: direct SaaS + Razorpay now; Gumroad, CodeCanyon and other stores later (fees, fit, what to build first). |
| `pipeline-tracker.csv` | Open in Excel; one row per school you approach. |
| `src/` | Sources for the website and printables (edit text and design here). |

**Demo school:** run `windows\reset-demo.ps1` before each visit. School ID `demo`; the logins are in `C:\ScholarERP\DEMO-LOGINS.txt`.

**Pitch deck:** kept as a Claude artifact (10 slides, with speaker notes). It can be presented in the browser or downloaded as PowerPoint or PDF.

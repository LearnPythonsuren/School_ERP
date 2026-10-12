# Where to sell Scholar ERP

There are two very different products here. Match each one to the right channel:

| | **Hosted service (SaaS)** | **Self-hosted edition (source code)** |
|---|---|---|
| What the buyer gets | A login for their school on *your* server | The code + installer, run on *their* server |
| Who buys | Schools (correspondents, principals) | Developers, IT vendors, schools with their own IT staff, buyers worldwide |
| How you earn | Yearly fee per school, renews every year | One-time sale (+ optional support) |
| Where it sells | Direct: visits, referrals, your website, ads | Marketplaces: Gumroad, CodeCanyon, your own site |

**Recommendation: sell SaaS directly for now; add the self-hosted edition
later.** See "When to add Gumroad" below.

## 1. Direct sales to schools (now)

Customers find you through visits, referrals, your website and WhatsApp
(see `GO-TO-MARKET.md`). To take payment:

- **UPI / bank transfer + a GST invoice.** Simplest at the start. Zoho
  Invoice has a free plan for proper invoices.
- **Razorpay** (Payment Links, Payment Pages, or Subscriptions for yearly
  renewals): Indian cards, UPI and netbanking. It's the usual choice in
  India; check its current fees and KYC requirements before you sign up.
- Talk to your CA about **GST registration** before you start invoicing.

**Lead directories** don't take payments, but schools find vendors there:
JustDial, IndiaMART, Sulekha, SoftwareSuggest, Capterra, G2 and GoodFirms
(free listings). Later, government and aided institutions buy through the
**GeM portal** (Government e-Marketplace), which needs seller registration.

## 2. Gumroad (self-hosted edition, later)

- **What it is:** a simple store for digital products. You upload the zip,
  set a price, and share the link.
- **Fees (2026, per third-party summaries):** about **10% + $0.50** per
  direct sale; **30%** for sales that come through Gumroad's own Discover
  marketplace; payment processing on top. Gumroad acts as the
  *merchant of record*, so it handles VAT and sales tax for overseas buyers.
- **India:** payouts to India go through PayPal or bank transfer; PayPal
  adds conversion fees. Check that your account can be verified for India
  and what the payout charges are, on Gumroad's help pages, before relying on it.
- **Good for:** selling the code worldwide from your own audience (YouTube
  demo, LinkedIn, your website). Gumroad does **not** bring buyers on its own
  unless you're featured on Discover.

## 3. CodeCanyon (Envato), the big code marketplace

- The largest marketplace for PHP scripts, with many "school management system"
  listings, so there's real demand, but also strong competition.
- **Fees:** from July 2026, third-party reports say Envato takes a flat
  **50%** and authors may also sell the same item elsewhere. Confirm on
  Envato's Author Hub.
- **Strict review:** expects a **web installer**, full documentation, a
  live demo, regular updates and buyer support. Many items are rejected on
  the first try.
- Good for volume once the product is polished; you trade half the
  revenue for traffic.

## 4. Other stores

| Store | Notes |
|---|---|
| **Your own website + Razorpay** | Keeps the full price; you bring the traffic. Best long-term. |
| **Payhip** | Gumroad-like; lower fees on paid plans. |
| **Lemon Squeezy / Paddle** | Merchant-of-record platforms, about 5% + $0.50; acceptance of Indian sellers is unclear, so check eligibility first. |
| **Dodo Payments** | An India-based merchant of record aimed at Indian software sellers selling abroad. |
| **AppSumo** | Lifetime deals for a global audience; not a fit for Indian school SaaS. |

Fees and eligibility change often. Confirm on each platform's own pricing
page before deciding.

## When to add Gumroad / CodeCanyon

Wait until these are true:

1. **3–5 schools are live on your hosted service** and stable. You need
   proof and testimonials, and every bug fixed for them makes the code
   product better.
2. **The product can be rebranded:** today "Chenthur Info Tech" is built into
   the app and receipts. A self-hosted buyer needs their own name and logo
   (a white-label setting).
3. **A web installer exists:** marketplace buyers expect to upload the files and
   follow a setup page in the browser, not run `setup.sh`.
4. **You have decided the risk:** anyone who buys the code could resell it
   or host it for Karur schools themselves. Selling the code competes with
   your own SaaS. A common approach: a **regular license** (one school / one
   site) and an **extended license** (allowed to host for others) at a much
   higher price.

These are the next development tasks before a code launch. Each is a few
days of work.

## Draft listing (for when you're ready)

**Title:** Scholar ERP: Multi-school management system (Laravel, self-hosted)

**Summary:** Complete school ERP: students, admissions, attendance, fees with
part-payments and receipts, exams with auto-grades, timetable, staff, library,
transport GPS, hostel, announcements, reports with CSV export, and role-based
access (admin, accountant, teacher, driver, parent). Multi-tenant: one install
serves many schools, each with its own database, plus an owner console with
license keys. Windows (IIS) and Linux installers, 72 automated API tests.

**Suggested pricing (to test):** regular license $79–149 · extended license
$399–699 · optional yearly support. In India, ₹6,999–14,999 on your own site.

# Scholar ERP — 90-day go-to-market plan (Karur first, then online)

**Goal:** 10 paying schools in 90 days. That's the launch-offer cap, and it
gives you references, testimonials and real feedback before you spend money
online.

**Positioning (one sentence):** *Simple school software for Tamil Nadu
schools: fees, attendance, results and the school bus in one place, set up and
supported locally from Karur, at a price small schools can afford.*

Your edge over big national ERPs: **local, in-person setup and training,
Tamil-speaking support, simple pricing, a private database per school.**
Lead with that in every conversation.

## Timing: work with the school calendar

| When | What schools are doing | What you sell |
|---|---|---|
| Oct–Dec (now, Term 2) | Fee instalments, half-yearly exams | **Fees + attendance now**, full rollout from June. Free 30-day trials. |
| Jan–Mar | Admissions season, budget for next year | **Admissions module**; close deals for the new year. |
| Apr–May | Planning, staff changes | Data import, training, setup before reopening. |
| June | New academic year | Go live. Ask happy schools for referrals. |

Decisions for next year are mostly made **Feb–May**, so use Oct–Jan to build
pipeline, run trials and collect testimonials.

## Funnel math (your estimates; track the real numbers)

| Stage | Target in 90 days |
|---|---|
| Schools on your list (Karur + neighbouring districts) | 150 |
| Meaningful contact (call, visit or WhatsApp reply) | 60 |
| Demos done | 30 (about 3 a week) |
| Free trials started | 12 |
| **Paying schools** | **8–10** |

If demos-to-trial is weak, fix the demo. If trial-to-paid is weak, fix
onboarding and follow-up. Use `pipeline-tracker.csv` (opens in Excel) to see
which.

## Phase 1: Weeks 1–4, foundation and first 3 schools

1. **Fill in `marketing/config.json`** (phone, WhatsApp, email, domain) and run
   `marketing\build.ps1`. Print 100 brochures and 30 price sheets.
2. **Domain + website live:** Cloudflare Tunnel (`windows\connect-domain.ps1`).
   Your domain's home page now shows the website, with **Login** for schools.
3. **Google Business Profile** for "Chenthur Info Tech": category *Software
   company*, Karur address, photos, website link. It's free, and it's how
   "school software Karur" searches find you.
4. **WhatsApp Business** on the sales number: profile, greeting message, quick
   replies (use the templates in `SALES-PLAYBOOK.md`), brochure in the catalog.
5. **Build the list:** private Matric and CBSE schools in Karur, Trichy,
   Namakkal, Erode and Dindigul from [UDISE+](https://udiseplus.gov.in/),
   Google Maps ("matriculation school near Karur") and the district
   education office directory. Enter them in `pipeline-tracker.csv`.
6. **Warm network first:** relatives, friends and teachers who know a
   correspondent or principal. Intros convert far better than cold visits.
7. **Win a "pilot" school before chasing revenue.** With no customers yet,
   the first school is about proof, not money: offer one well-connected
   school a free first term in exchange for honest feedback, a short
   testimonial (a quote plus a photo or a 30-second phone video) and
   permission to mention them. Make that school a showcase; every later
   pitch gets easier with "Ask [School] how it works for them."
8. Target: **12 demos, 5 trials, 3 paying schools.**

## Phase 2: Weeks 5–8, scale locally

1. **Referral programme:** one month free for every school a customer refers
   that joins. Mention it at go-live and again at the 30-day review.
2. **School networks:** local private-school associations, CBSE *Sahodaya*
   clusters and matric school correspondents' meetings. Ask for a 10-minute
   slot to demo, and bring the brochure.
3. **Case study:** one page, "How [School] cut fee follow-up time" (before
   and after, a quote, and a screenshot). Add it to the website and the
   WhatsApp catalog.
4. **Office staff workshop:** a free 1-hour "Fee & attendance in 10 minutes"
   session for office staff of 5–8 schools at one venue. Staff become your
   champions inside each school.
5. Target: **10 more demos, 4 trials, 3 more paying schools.**

## Phase 3: Weeks 9–12, go online

1. **Demo videos (Tamil + English, 2–3 min each):** screen-record a
   fee collection, the attendance register and the dashboard. Post them to
   YouTube and add them to the website.
2. **Listings:** JustDial, IndiaMART, Sulekha, SoftwareSuggest, Capterra and
   GoodFirms (free tiers). Use the same name, phone and address everywhere.
3. **Google Ads (search only, Tamil Nadu):** for example "school management
   software", "school ERP", "school fee software", "school software Tamil".
   Start at about ₹500/day, send clicks to the website, and measure WhatsApp
   demo requests.
4. **Facebook / Instagram:** 2 posts a week (ideas below). Use small boosts
   (₹200–300) on the best post, targeted at Tamil Nadu, ages 30–60, interests
   *education / school administration*.
5. **LinkedIn:** connect with principals of CBSE schools in Tamil Nadu and
   share the case study.
6. Target: **8 more demos from online, 3 trials, 2–4 paying schools.**

## Suggested budget (90 days, adjust to suit)

| Item | ₹ |
|---|---|
| Printing (brochures, price sheets, visiting cards, 1 standee) | 6,000 |
| Domain + Cloudflare (free plan) | 1,000 |
| Travel for visits | 12,000 |
| Office-staff workshop (venue, tea) | 6,000 |
| Google Ads (6 weeks) | 21,000 |
| Facebook / Instagram boosts | 6,000 |
| Demo video editing (or DIY) | 0–5,000 |
| **Total** | **≈ 52,000–57,000** |

At ₹24,000–48,000 per school per year, **two schools pay for the whole plan.**

## Weekly rhythm

- **Mon:** update the pipeline tracker; plan 3–4 visits; send follow-ups due.
- **Tue–Thu:** visits and demos (mornings suit principals; afternoons suit the office).
- **Fri:** trials: import data, train, check in with live schools.
- **Sat:** content: 1 social post, 1 testimonial request, reply to reviews.

**Numbers to watch every Monday:** demos booked, demos done, trials
started, trials converted, revenue closed, and the reasons for every "no".

## Social post ideas (alternate Tamil and English)

1. "How long does your office take to find who hasn't paid fees? We do it in 1 click." (fee-dues screen)
2. "வருகைப் பதிவு 1 நிமிடத்தில். ஆசிரியர் கைபேசியிலேயே." (attendance register)
3. A fee receipt with a school letterhead: "Your school's receipt, printed in seconds."
4. A customer quote and photo (with permission).
5. "Bus location from the driver's phone; no extra GPS device needed."
6. "பள்ளி அலுவலக Excel தாள்களுக்கு விடை கொடுங்கள்." (Excel to Scholar ERP)
7. A 30-second video of the dashboard on a phone.
8. "Launch offer: 25% off for the first 10 Karur schools; [n] places left."
9. "Admissions season is coming: applications to enrolled students in one click."
10. "Your data stays yours: a private database for every school, backed up nightly."

## Guardrails

- **Discounts:** the launch offer (25%) or the referral month; anything more
  needs a reason (multi-year prepayment, a group of schools).
- **Trials:** 30 days with their real data. At the end, they convert, or you
  export their data to them and delete the trial school.
- **Payment:** yearly plans in advance; one-time licenses 50/50.
- **GST & invoicing:** quotations show 18% GST. Check with your CA about
  GST registration and proper tax invoices before you collect payments.
- **Student data is minors' personal data** (DPDP Act, 2023). Add a privacy
  policy page to the website, and have the license / service agreement (see
  `LICENSE` template) reviewed by a lawyer.
- **Promise only what exists.** Today: in-app announcements (no SMS), staff
  record payments (no online payment gateway), parents see school-wide
  information. These are the most likely requests, so price them as add-ons
  when you build them.

## Product requests to expect (and when to build them)

| Request | Why schools ask | Suggestion |
|---|---|---|
| SMS / WhatsApp to parents | Attendance alerts, fee reminders | Build first; sell as a paid add-on (SMS cost + margin) |
| Tamil interface in the app | Office staff comfort | High value locally; translate the app UI |
| Online fee payment (UPI / Razorpay) | Parents pay from phone | Build when 5+ schools ask |
| Parent view per child | Marks, attendance, fees for own child | Next major feature |
| Report cards (PDF) | Term-end | Quick win after the first exams |

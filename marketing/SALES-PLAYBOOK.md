# Scholar ERP — Sales playbook

Everything you need for a school visit. Printables are in `marketing/dist/`:
brochure, price sheet, and `quotation.html` (fill it in and print to PDF).

> Tamil text here is written for clarity; have a native speaker glance over
> it once before you print in bulk.

## Who decides, and what they care about

| Person | Cares about | Lead with |
|---|---|---|
| **Correspondent / owner** (decides, pays) | Fee collection, cost, control, reputation with parents | Fee dues report, receipts, "see your school from your phone", price |
| **Principal** (influences, uses daily) | Attendance, results, discipline, less paperwork | Attendance register, results with auto-grades, timetable |
| **Office / accountant** (makes or breaks adoption) | Fewer registers, no calculation mistakes | Collecting a fee in 3 clicks, printed receipt, Excel export |
| **Teachers** | Time | Attendance in under a minute, marks entry |

Always try to get the **correspondent and the office staff in the same
room**. The correspondent decides; the office decides whether it actually gets used.

## Before the visit (10 minutes)

- [ ] Run `windows\reset-demo.ps1` (fresh demo data, this week's attendance).
- [ ] Check `C:\ScholarERP\DEMO-LOGINS.txt`; open the app, School ID `demo`.
- [ ] Charge the laptop and phone; have mobile data as backup.
- [ ] 5 printed brochures + 2 price sheets; visiting cards.
- [ ] Know the school: board (State/Matric/CBSE), approx. students, buses?
      hostel? (Google Maps, their website, or ask the person who referred you.)

## Discovery: ask before you show (5 minutes)

1. "How do you collect fees today? How do you know who hasn't paid?"
2. "How is attendance taken, and how do parents find out?"
3. "How long does it take to prepare results / progress cards?"
4. "Do you run school buses? Do parents call asking where the bus is?"
5. "What software have you tried before? What didn't you like?"
6. "If this worked perfectly, what would change for you?"

Write their answers down; demo **only** the parts that match their pain.

## The 15-minute demo

Login as **admin** (`admin@greenfield.test`, School ID `demo`).

| Minute | Show | Say |
|---|---|---|
| 0–2 | **Dashboard** | "This is your whole school at one glance: students, today's attendance, fees collected and pending. Open it on your phone from anywhere." |
| 2–5 | **Fees** → *Collect* a part-payment → *Receipt* | "Parent pays ₹5,000 by UPI. Two clicks, and the receipt prints with your school's name. The balance is tracked automatically." Then filter **Due**: "Here's everyone who still owes. No more checking registers." |
| 5–7 | **Attendance** → *Mark all present* → mark 2 absent → *Save* | "A teacher does this in under a minute from their phone. The attendance % updates by itself." |
| 7–9 | **Exams** → add a result | "Grades are calculated automatically. Download the whole class to Excel." |
| 9–11 | **Admissions** → *Approve* → *Enroll* | "From application to student record in one click. No retyping." |
| 11–12 | **Transport** (if they have buses) | "The driver taps once on his phone; you see where the bus is on the map." |
| 12–13 | Log in as **teacher** | "A teacher only sees their screens. Fees and settings are hidden. Everyone gets the right access." |
| 13–15 | **Reports** → download fees CSV | "Every report your auditor or management asks for, in Excel." |

Close with: **"What would you want to see working for your school first?"**

## Handling objections

**"It's too expensive."**
EN: "For 400 students it's ₹100 a year per student, about ₹8 a month per
child. One missed fee instalment a month costs you more than that. And for the
first 10 Karur schools there's 25% off the first year."
TA: "400 மாணவர்களுக்கு, ஒரு மாணவருக்கு ஆண்டுக்கு ₹100, அதாவது மாதம் சுமார் ₹8 மட்டுமே.
ஒரு மாதம் தவறும் கட்டண வசூலே இதைவிட அதிகம். கரூரின் முதல் 10 பள்ளிகளுக்கு முதல் ஆண்டு 25% தள்ளுபடி."

**"Our staff can't handle computers."**
EN: "Each person sees only a few screens. We train them at your school, and
if they can use WhatsApp, they can use this."
TA: "ஒவ்வொருவருக்கும் சில திரைகள் மட்டுமே. உங்கள் பள்ளியிலேயே பயிற்சி தருகிறோம். WhatsApp பயன்படுத்தத் தெரிந்தால் இதையும் பயன்படுத்தலாம்."

**"What if the internet goes down?"**
EN: "Staff can use mobile data on their phone. Or choose the one-time
license, which runs on your own office computer."
TA: "கைபேசி இணையத்திலும் பயன்படுத்தலாம். அல்லது ஒருமுறை உரிமத்தில் உங்கள் அலுவலகக் கணினியிலேயே இயங்கும்."

**"Is our data safe? Will other schools see it?"**
EN: "Every school has its own separate database. Each person sees only what
their role allows, and everything is backed up nightly. The data is yours:
export it to Excel any time."
TA: "ஒவ்வொரு பள்ளிக்கும் தனி தரவுத்தளம். தினமும் காப்புப்பிரதி. தரவு உங்களுடையது, எப்போது வேண்டுமானாலும் Excel-ஆகப் பெறலாம்."

**"We already use another software."**
EN: "What do you like and dislike about it?" (listen) "We'll import your
student list from Excel, so switching takes days, not months. Many schools
switch at the start of the academic year."
TA: "இப்போதுள்ள மென்பொருளில் எது பிடிக்கும், எது பிடிக்கவில்லை?" … "உங்கள் மாணவர் பட்டியலை Excel-இலிருந்து பதிவேற்றுவோம்; மாற சில நாட்களே ஆகும்."

**"We'll think about it / next year."**
EN: "Of course. How about a free 30-day trial with your real classes this
term, so you can decide with your own data? The launch offer is only for the
first 10 schools."
TA: "நிச்சயமாக. இந்தப் பருவத்தில் உங்கள் உண்மையான வகுப்புகளுடன் 30 நாள் இலவசமாகப் பயன்படுத்திப் பாருங்கள். அறிமுகச் சலுகை முதல் 10 பள்ளிகளுக்கு மட்டுமே."

**"Do parents get SMS?"**
Be honest: "Announcements appear in the app today. SMS / WhatsApp delivery
can be added for your school as an add-on." (Only promise what you can deliver.)

## WhatsApp messages (copy and paste)

**First contact (after a referral)**
> Vanakkam Sir/Madam, I'm [Name] from Chenthur Info Tech, Karur. [Referrer] suggested I contact you. We've built Scholar ERP, simple school software for fees, attendance, results and the school bus, with local support from Karur. Can I show you a 20-minute demo at your school this week? 🙏

> வணக்கம் ஐயா/அம்மா, நான் கரூர் Chenthur Info Tech-இலிருந்து [பெயர்]. [பரிந்துரைத்தவர்] உங்களைத் தொடர்பு கொள்ளச் சொன்னார். கட்டணம், வருகைப் பதிவு, தேர்வு முடிவுகள், பள்ளி வாகனம். இவை அனைத்திற்கும் Scholar ERP என்ற எளிய மென்பொருளை உருவாக்கியுள்ளோம். இந்த வாரம் உங்கள் பள்ளியில் 20 நிமிட டெமோ காட்டலாமா? 🙏

**After the demo (same day)**
> Thank you for your time today, Sir/Madam. As discussed, I've attached the brochure and the quotation for [School]. The launch offer (25% off the first year) is open for the first 10 Karur schools. Shall I set up a free trial with your real classes this week?

> இன்று நேரம் ஒதுக்கியதற்கு நன்றி. பேசியபடி [பள்ளி]க்கான விவரக் கையேடு மற்றும் விலைப்புள்ளியை இணைத்துள்ளேன். முதல் 10 கரூர் பள்ளிகளுக்கு முதல் ஆண்டு 25% தள்ளுபடி உள்ளது. இந்த வாரமே உங்கள் வகுப்புகளுடன் இலவச சோதனையைத் தொடங்கலாமா?

**Follow-up (day 4, no reply)**
> Sir/Madam, just checking if you had a chance to look at the quotation. Happy to answer any questions or show the fee module to your office staff.

> ஐயா/அம்மா, விலைப்புள்ளியைப் பார்த்தீர்களா? ஏதேனும் சந்தேகம் இருந்தால் கேளுங்கள். உங்கள் அலுவலகப் பணியாளர்களுக்கும் கட்டணப் பகுதியைக் காட்டத் தயார்.

**Referral ask (after a school goes live)**
> Thank you for choosing Scholar ERP! If you know another school that would benefit, please share my number. For every school you refer that joins, we'll give you one month free.

> Scholar ERP-ஐத் தேர்ந்தெடுத்ததற்கு நன்றி! பயன்பெறக்கூடிய வேறு பள்ளி தெரிந்தால் என் எண்ணைப் பகிருங்கள். நீங்கள் பரிந்துரைக்கும் ஒவ்வொரு பள்ளி சேரும்போதும் உங்களுக்கு ஒரு மாதம் இலவசம்.

## Follow-up rhythm

| Day | Action |
|---|---|
| 0 | Demo. Same-day WhatsApp with brochure PDF + quotation PDF. |
| 2 | Call: "Any questions from the office staff?" |
| 4 | WhatsApp follow-up message. |
| 7 | Offer a free 30-day trial with their real classes. |
| 14 | Visit again with a filled-in trial (their own student list imported). |
| 30 | Decision call. If "no", ask why, note it, and ask permission to check back before the next academic year. |

## Closing and onboarding checklist

- [ ] Signed quotation (or written WhatsApp confirmation) + advance payment received.
- [ ] Owner console → **Issue license** (plan per quotation, expiry = paid period).
- [ ] **Provision school** → send the hand-over note to the admin.
- [ ] Get their student list in Excel → import (Students screen / CSV).
- [ ] Settings: school name, address, phone (these print on receipts).
- [ ] Create staff logins (Users & Roles): accountant, teachers, drivers.
- [ ] Training session: office (fees, receipts), teachers (attendance, marks).
- [ ] Day 7 check-in visit; day 30 review + **ask for a referral and a testimonial**.
- [ ] Set a renewal reminder 30 days before the license expires.

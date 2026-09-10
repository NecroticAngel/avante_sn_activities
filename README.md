# Avante Stock Network search & activities

PHP site for Avante: Stock Network accommodation search/booking, Plett activities, and a small admin.

Local: `php -S localhost:8080` from this folder. Copy `config.example.php` to `config.php` (gitignored) for the SN token and admin password.

- Search: http://localhost:8080/index.php
- Property map: http://localhost:8080/map.php
- Holiday builder: http://localhost:8080/holiday.php
- Activities: http://localhost:8080/activities.php
- Manage booking: http://localhost:8080/manage.php
- Admin: http://localhost:8080/admin/
- Bookings: http://localhost:8080/admin/bookings.php
- Settings (catch-all emails): http://localhost:8080/admin/settings.php

Accommodation booking requests and Stock Network responses are stored in
`data/accommodation-bookings.jsonl`. The payment handoff and booking API audit trail
are stored separately in `data/booking-audit.jsonl`; sensitive credential and card
field names are redacted. Because payment happens on Stock Network's domain, Avante
can record the portal handoff but requires a Stock Network callback or status API to
record the gateway's final payment response automatically.

## Test booking (Dunes) — keep for later chats

Use this when we need to search, book, or look up a stay again.

| Field | Value |
| --- | --- |
| Resort | **The Dunes Hotel & Resort!** (exclamation mark — there are other Dunes hotels) |
| Location | Bitou, Plettenberg Bay, Garden Route |
| Search as | `Dunes Hotel` |
| Date window | 4–18 December 2026 (single-night stays are fine) |
| Dates used | **4 Dec 2026 → 5 Dec 2026** (1 night) |
| Typical unit | FV10, 2 sleeper, ~R 2,573 |
| Guest | Jo Test |
| Email | test.bookings@avantetravel.co.za |
| Phone | +27821234567 |
| SN reference | **111526** |
| Status | Request (not paid) |
| Look up | http://localhost:8080/manage.php — ref `111526` + that email |

Earlier SN test requests (cancel in Stock Network if they are still sitting there):

- **111520** — Boshoff (The), Corner King, 20–23 Sep 2026
- **111521** — The Dunes Hotel & Resort!, FV10, 4–5 Dec 2026 (Avante Test Guest)
- **111522** — The Dunes Hotel & Resort!, FV10, 4–5 Dec 2026 (Jo Test)
- **111525** — The Dunes Hotel & Resort!, FV10, 4–5 Dec 2026 (Jo Test)

Stock Network has no documented API to edit or cancel a reservation. Pay / details use the SN portal links on the booking record.

Catch-all copies go to the addresses in admin Settings (currently `liqqquid.ideas@gmail.com`).

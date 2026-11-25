# Changelog

## 1.2.0 — 25 November 2026 (version 2026112600)
CC and image delivery fixes, per-email delivery log. Regression-tested against 1.1.5 on Moodle 4.5
(same emails, same order, same content; differences listed below are intentional).

### Fixed
- **CC refused by the mail server** (e.g. relay that blocks external recipients): the employee's copy
  was delivered but the whole email was counted as failed and the missing CC was invisible. Now the
  employee is counted as sent and the Reports page shows "Sent, CC refused" with the server message.
- **No DKIM signature / Moodle Message-ID** on plugin emails (the plugin builds the mail itself);
  now signed exactly like core `email_to_user()` when DKIM is configured.
- **Images pasted as `data:` URIs** are embedded as inline attachments (Gmail blocks `data:` images).
- `data-src="..."` was mistaken for `src="..."`; the same image used twice was attached twice.
- Reply-To name showed as "[[noreply]]" (missing core string); now "Do not reply to this email".
- `{{IMAGE}}` (any case) no longer makes the uploaded image appear twice.
- Upgrade steps 2026101601/2026101602 were in the wrong order (downgrade error when upgrading
  from 2026101600 or older).
- CC addresses are dropped when "divert all emails" is on, so test sites never copy real mailboxes.

### Changed
- **CC per email type.** New switches; **Thought of the Day is not CC'd by default** (agreed). Birthday
  and anniversary emails keep the CC.
- **Several CC addresses** (comma / semicolon / new line, max 10) with validation that names bad addresses.
- HTML emails use quoted-printable encoding, like core Moodle email.
- Employee/family birthday split uses a fixed key instead of comparing translated text.

### Added
- **Per-email delivery log** (`local_birthdayflyer_log`): status and reason for every email.
  A same-day re-run retries only failed emails; sent emails are never sent twice.
- Reports page: "Delivery issues (last 30 days)" panel and card.
- `{{image_url}}` placeholder (public URL of the uploaded template image, for backgrounds).
- Optional "Email-safe background" Thought of the Day layout (default stays the original overlay).
- Template save warnings for image references mail clients cannot show (never blocks saving).
- `cli/send_test.php`: send one employee's emails for any day to a test address, without logging.
- Optional pause between emails; SMTP connection reuse via Moodle's SMTP session limit.
- Privacy metadata for the new table and the 18 Nov family date fields.

### Performance
- Strings, templates, images and the Thought of the Day HTML are built once per run.
  Test with 307 employees / 342 emails: 1,717 → 1,351 database queries, including the new log writes.

### Unchanged on purpose
- Suspended, deleted and "No login" accounts, bounce-limit and blocked-domain addresses are still
  skipped (and their CC with them). Employees who never logged in still receive email.
- Schedule (07:00), email order, subjects and bodies.

## 1.1.0 — 18 November 2026
- Added husband date of birth (`husband_dob`, `husband_dob_stamp`).
- Added second daughter date of birth (`daughter2_dob`, `daughter2_dob_stamp`).
- Added second son date of birth (`son2_dob`, `son2_dob_stamp`).
- Added the three date pairs to manual employee setup/edit forms and CSV import.
- Added new family birthdays to the daily scheduled email task; they use the existing Family Birthday enable/disable setting.
- Added the new birthdays to the upcoming celebrations report.
- Updated the employee sample CSV with the three new columns.
- Added a Moodle upgrade step that creates the nullable date fields without altering existing employee data.


Birthday Flyer local plugin
=================================

Component: local_birthdayflyer
Directory: moodle/local/birthdayflyer

Features
--------
- Employee details in an XMLDB-managed table.
- Dated Thought of the Day records are date-based and stored in a separate table shared by all employees.
- Daily scheduled task sends the Thought of the Day and any applicable birthday/anniversary notifications to each eligible employee.
- Birthday checks cover employee, father, mother, wife, son and daughter.
- Per-day send log prevents duplicate emails if the task is run more than once.
- Moodle Form API, database API, capability checks, language strings, and privacy metadata.
- Theme-aware responsive dashboard styles.

Install / upgrade
-----------------
1. Extract the folder `birthdayflyer` into the Moodle site's `local` directory.
2. Visit Site administration > Notifications to install or upgrade the plugin.
3. Grant `local/birthdayflyer:view` and `local/birthdayflyer:manage` to the appropriate trusted role.
4. Open /local/birthdayflyer/index.php.

Daily thought
-------------
- In the dashboard, a manager can save a Thought of the Day for a chosen date.
- The dashboard displays today's thought.
- The scheduled task sends the thought for the current site date. If no thought is set for today, birthday notifications are still sent.
- If there is neither a thought nor a birthday for an employee, no email is sent to that employee that day.
- The Thought of the Day is a separate message from birthday and anniversary messages; the daily delivery log prevents accidental duplicate processing.
- Employee email addresses are used as recipients. A matching Moodle account is used when found; otherwise the email is sent to the stored employee address.

Scheduled task
--------------
The task is registered in db/tasks.php and has a default schedule of 07:00 server/site task time. Administrators can adjust it in:
Site administration > Server > Tasks > Scheduled tasks.
Moodle cron must be configured and running for scheduled tasks to execute.

Testing
-------
1. Add a test employee with a deliverable email address.
2. Set one date field (for example employee DOB or father's DOB) to today's month/day and any year.
3. In the Thought of the Day section, save a thought dated today.
4. Visit Site administration > Server > Tasks > Scheduled tasks.
5. Find "Send daily employee Thought of the Day and birthday notifications" and click Run now.
6. Check the task log / CLI output and the test mailbox, including spam/junk.
7. Run it again. The delivery log should prevent a duplicate email for that employee on the same day.
8. To test only birthday messages, temporarily remove/choose no thought for today and keep a birthday matching today's month/day.
9. To test next-day scheduling, add a thought for tomorrow and configure an employee/family date with tomorrow's month/day, then run the task on that date. Do not change production employee data for testing.

CLI test (run from Moodle root)
-------------------------------
php admin/cli/scheduled_task.php --execute='\local_birthdayflyer\task\daily_employee_digest' --showdebugging

Run all due tasks:
php admin/cli/cron.php

Important
---------
- Test on staging before production. Back up the database first.
- Employee/family birthdays and email addresses are personal data; restrict access to appropriate staff.
- The daily task uses Moodle's email API. SMTP / outgoing mail must be configured in Moodle.
- The delivery log records successful email API handoff, not guaranteed arrival in the recipient's inbox.
- The scheduled time is a default and can be changed by a site administrator.


Fixes in the latest package
---------------------------
- The employee form carries the current database record ID in a hidden Moodle form field.
- Duplicate Employee ID validation excludes the record currently being edited, so saving unchanged IDs does not trigger a false duplicate error.
- Thought date keys, birthday checks and daily delivery-log dates use Moodle's configured server timezone consistently.
- A dated thought is sent to every employee on its scheduled day. Birthday reminders are added to the same email when one or more employee/family birthdays match that day.


New in 0.2.0 - birthday templates and Excel import
-------------------------------------------------
- Employee records now include Employee full name and marriage date in addition to the existing employee/family dates.
- Birthday email templates can be created at /local/birthdayflyer/templates.php. Each template stores a name, subject, HTML body, active status, and one uploaded banner image.
- Template placeholders supported: {{username}}, {{user fullname}}, {{user_fullname}}, {{employee_fullname}}, {{employeeid}}, {{employeeemail}}, {{date}}, {{thought_of_the_day}}, {{birthday_person}}, {{relationship}}, and {{image}}.
- Template images are served by a token-protected endpoint so external email clients can load the banner without needing to log in to Moodle. Treat the image key as a public link and upload only approved company images.
- Import dated Thought of the Day records at /local/birthdayflyer/import.php. Excel workbooks should have the first row headers Date and Thought of the Day. CSV is also supported. XLS/XLSX requires PhpSpreadsheet to be available in the Moodle PHP environment.
- The scheduled task sends the daily Thought of the Day to employees when today's thought exists, and applies the active birthday template when one or more employee/family birthdays match. If no thought is configured, birthday emails can still be sent; non-birthday emails are skipped and the task logs a warning. Configure a thought for every day to meet the daily broadcast requirement.
- Existing installations are upgraded through Moodle Site administration > Notifications; the migration adds the new fields and template table.


Dashboard and navigation update (0.2.1)
---------------------------------------
- Added Moodle breadcrumb entries to the dashboard, Thought of the Day import, and birthday-template manager.
- Added consistent cross-navigation between the dashboard, Excel import, and template screens.
- Added an Upcoming Thoughts of the Day table to the dashboard. It lists up to ten future-dated entries in date order, while today's thought remains highlighted separately.
- Added shared responsive styling for the navigation, subpage headers, thought schedule, and template list. Colors inherit from Moodle/Bootstrap theme variables.
- Added comments around key navigation, query, and template-action logic and kept user-facing labels in the language pack.
- Plugin version: 2026101501 (release 0.2.1).


Version 1.2.0 (25 November 2026) - CC, images, delivery log
-----------------------------------------------------------
Why: CC copies were sometimes missing, background images were sometimes missing
in some mailboxes, and a failed email could not be retried without duplicates.
Full list in CHANGELOG.md; every changed line in the code is marked "v1.2.0".

What changes for administrators
- CC per email type (Site administration > Plugins > Local plugins > Birthday Flyer):
  "CC Thought of the Day" is OFF after the upgrade (it used to put one copy per
  employee in the CC mailbox every morning). Birthday and anniversary CC stay ON.
- Several CC addresses: separate them with commas, semicolons or new lines (max 10).
  An invalid address is named in an error instead of silently clearing the setting.
  External addresses (for example Gmail) are allowed.
- If the mail server refuses a CC address (typical when the company relay does
  not allow external recipients) the employee still gets the email; the Reports
  page shows "Sent, CC refused" with the server's message. Before 1.2.0 this
  was hidden and the employee was wrongly counted as failed.
- Reports > "Delivery issues (last 30 days)": every skipped or failed email and why
  (suspended account, "No login" account, mail server error, CC refused...).
- A failed email can be retried the same day with Scheduled tasks > Run now.
  Emails that already went out are never sent twice.
- Suspended accounts and accounts using the "No login" authentication method are
  still skipped (unchanged). Employees who never logged in still get their email.
- Emails are DKIM-signed when DKIM is configured in Moodle (Outgoing mail > DKIM selector),
  like every other Moodle email.

Background images in templates
- {{image}} inserts the uploaded template image as a picture (embedded in the email).
- NEW {{image_url}} gives only the public address of the uploaded image, for use as a
  background. Backgrounds stay a public link on purpose: Gmail does not show embedded
  (cid:) or data: images as backgrounds. The site must be reachable from the internet.
- Outlook-safe background example (600px wide, 400px high artwork):

  <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr>
  <td width="600" height="400" valign="top" background="{{image_url}}"
      style="background-image:url('{{image_url}}');background-size:600px 400px;background-repeat:no-repeat;">
  <!--[if gte mso 9]><v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:450pt;height:300pt;">
  <v:fill type="frame" src="{{image_url}}" /><v:textbox inset="0,0,0,0"><![endif]-->
      <p style="margin:120px 24px 0;font-family:Arial;font-size:22px;">Happy birthday, {{employee_fullname}}!</p>
  <!--[if gte mso 9]></v:textbox></v:rect><![endif]-->
  </td></tr></table>

- When a template is saved, the page warns about image references that mail clients
  cannot show (data: backgrounds, draftfile.php / pluginfile.php links, localhost).
  Saving is never blocked.
- Images pasted into the HTML as data:image/... are now embedded as real attachments
  (Gmail blocks data: images).

Thought of the Day layout
- "Original overlay" (default, unchanged) or "Email-safe background" (600px table with
  the artwork as background, plus an Outlook fallback). In Gmail/Outlook the original
  layout shows the quote below the picture; the email-safe layout keeps it on the
  artwork. Test with the CLI script below before switching.

Test emails without affecting the morning run
  php local/birthdayflyer/cli/send_test.php --empid=E1001 --to=you@example.com [--date=2026-12-01] [--withcc]
Builds the exact emails the task would send for that employee and day and sends them
only to --to (subject prefixed [TEST]). Nothing is logged.

Optional delivery settings
- "Pause between emails (milliseconds)", default 0 (no pause).
- To reuse one SMTP connection for many emails, raise Moodle's
  Outgoing mail > "SMTP session limit" (smtpmaxbulk), e.g. to 50. Default 1 keeps the old behaviour.

Upgrade notes
- New table local_birthdayflyer_log; existing tables and data are untouched.
- The upgrade writes the new settings with the defaults above; existing values are kept.
- The order of two old upgrade steps was corrected (only matters when upgrading from a
  2026101600-or-older copy).

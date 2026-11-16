Birthday Flyer local plugin
=================================

Component: local_birthdayflyer
Directory: moodle/local/birthdayflyer

Features
--------
- Employee details in an XMLDB-managed table.
- Dated Thought of the Day records are date-based and stored in a separate table shared by all employees.
- Daily scheduled task sends one email per employee with today's thought and any matching birthdays.
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
- One combined email is sent per employee per day; duplicate sends are prevented by a delivery log.
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

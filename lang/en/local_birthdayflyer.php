<?php
$string['pluginname'] = 'Employee Birthdate & Flyer Setup';
$string['birthdayflyer:manage'] = 'Manage employee date and flyer setup records and settings.';
$string['birthdayflyer:view'] = 'View employee date and flyer setup records.';
$string['employeemgmt'] = 'Employee Birthdate & Flyer Setup';
$string['employees'] = 'Employee Dates';
$string['addemployee'] = 'Set Up Employee Dates';
$string['editemployee'] = 'Edit Employee Dates';
$string['saveemployee'] = 'Save Employee Dates';
$string['updateemployee'] = 'Update Employee Dates';
$string['employeeid'] = 'Employee ID';
$string['employeeemail'] = 'Employee email';
$string['importemployees'] = 'Import Employee Dates from CSV';
$string['downloademployeecsvsample'] = 'Download sample CSV';
$string['reports'] = 'Reports';
$string['reportspageintro'] = 'Review employee records, scheduled thoughts, upcoming celebrations, templates, and recent email delivery activity in one place.';
$string['reportsoverview'] = 'Overview';
$string['reporttotalemployees'] = 'Total employees';
$string['reportthoughts'] = 'Scheduled thoughts';
$string['reportactivetemplates'] = 'Active templates';
$string['reportdelivery30days'] = 'Delivery log entries (30 days)';
$string['reportupcomingevents'] = 'Upcoming celebrations';
$string['reportupcomingeventsintro'] = 'Birthdays and anniversaries scheduled for the next 30 days.';
$string['reportrecentdelivery'] = 'Recent delivery activity';
$string['reportrecentdeliveryintro'] = 'Employees successfully processed by the daily scheduled task.';
$string['reportthoughtschedule'] = 'Thought schedule';
$string['reportthoughtscheduleintro'] = 'Next scheduled Thoughts of the Day.';
$string['reporttemplates'] = 'Template status';
$string['reporttemplatesintro'] = 'Configured birthday and anniversary email templates.';
$string['reportevent'] = 'Event';
$string['reportemployee'] = 'Employee';
$string['reportdate'] = 'Date';
$string['reportyears'] = 'Years';
$string['reportstatus'] = 'Status';
$string['reportactive'] = 'Active';
$string['reportinactive'] = 'Inactive';
$string['reportdeliverydate'] = 'Processed date';
$string['reportnoevents'] = 'No celebrations are scheduled in the next 30 days.';
$string['reportnodelivery'] = 'No delivery activity has been logged in the last 30 days.';
$string['reportnothoughts'] = 'No scheduled Thoughts of the Day found.';
$string['reportnotemplates'] = 'No email templates have been configured.';
$string['reportbirthdayemployee'] = 'Employee birthday';
$string['reportbirthdayfather'] = 'Father birthday';
$string['reportbirthdaymother'] = 'Mother birthday';
$string['reportbirthdaywife'] = 'Wife birthday';
$string['reportbirthdayson'] = 'Son birthday';
$string['reportbirthdaydaughter'] = 'Daughter birthday';
$string['reportworkanniversary'] = 'Work anniversary';
$string['reportmarriageanniversary'] = 'Marriage anniversary';
$string['employeecsvfile'] = 'Employee CSV file';
$string['employeecsvfile_help'] = 'Upload a UTF-8 CSV using the sample headers: empid, empfullname, empemail, date_of_birth, date_of_joining, marriage_date, father_dob, mother_dob, wife_dob, son_dob, daughter_dob. Dates can be DD-MM-YYYY, DD/MM/YYYY, YYYY-MM-DD, or common spreadsheet date formats.';
$string['employeecsvfilerequired'] = 'Please upload an employee CSV file.';
$string['updateexistingemployees'] = 'Update existing employees when Employee ID already exists';
$string['employeecsvformat'] = '<strong>CSV columns:</strong> empid, empfullname, empemail, date_of_birth, date_of_joining, marriage_date, father_dob, mother_dob, wife_dob, son_dob, daughter_dob. Required columns are empid, empfullname, and empemail. Dates are converted automatically into the paired readable-date and timestamp fields.';
$string['employeecsvintro'] = 'Bulk set up or update employee date records without changing the existing form. Employee ID is used to match existing records, and imported dates are stored in the same readable-date and timestamp fields used by the form.';
$string['employeecsvrequiredfields'] = 'Employee ID, employee full name, and employee email are required.';
$string['employeecsvinvalidemail'] = 'Invalid email address for Employee ID {$a}.';
$string['employeecsvinvaliddate'] = 'Invalid date in {$a}. Use DD-MM-YYYY, DD/MM/YYYY, or YYYY-MM-DD.';
$string['employeecsvempty'] = 'The uploaded CSV contains no data.';
$string['employeecsvbadheaders'] = 'The CSV headers are missing required columns. Required columns are empid, empfullname, and empemail.';
$string['employeecsvrowerror'] = 'Row {$a->row}: {$a->error}';
$string['employeecsvsummary'] = 'Employee CSV import complete. New: {$a->inserted}; updated: {$a->updated}; skipped: {$a->skipped}; failed: {$a->failed}.';
$string['employeecsvmoreerrors'] = '{$a} additional row errors were omitted from the notification.';
$string['employeecsvimportfailed'] = 'Employee CSV import failed: {$a}';
$string['dob'] = 'Date of birth';
$string['dateofjoining'] = 'Date of joining';
$string['fatherdob'] = "Father's date of birth";
$string['motherdob'] = "Mother's date of birth";
$string['wifedob'] = "Wife's date of birth";
$string['sondob'] = "Son's date of birth";
$string['daughterdob'] = "Daughter's date of birth";
$string['eventworkanniversary'] = 'Work anniversary';
$string['eventmarriageanniversary'] = 'Marriage anniversary';
$string['years'] = 'years';
$string['date_of_birth'] = 'Date of birth';
$string['thoughtoftheday'] = 'Thought of the Day';
$string['thoughtimagealt'] = 'Thought of the Day';
$string['thoughtsharedimagetitle'] = 'Shared Thought of the Day image';
$string['thoughtsharedimageintro'] = 'Set one shared artwork for all Thought of the Day emails. Your quote will be placed directly inside this image automatically.';
$string['thoughtsharedimage'] = 'Thought of the Day image';
$string['savethoughtimage'] = 'Set / Replace shared image';
$string['thoughtsharedimagesaved'] = 'The shared Thought of the Day image has been updated.';
$string['thoughtsharedimagenotfound'] = 'Please select an image before saving.';
$string['thoughtimageactive'] = 'Shared artwork is active';
$string['thoughtimagenotconfigured'] = 'No shared artwork configured yet';
$string['thoughtimageactiveintro'] = 'Current artwork: {$a}. Every scheduled thought will use this image with the quote composited inside it.';
$string['thoughtimageactivebadge'] = 'ACTIVE FOR ALL THOUGHTS';
$string['thoughtimagenotconfiguredintro'] = 'Upload an image once and every Thought of the Day will use it automatically.';
$string['thoughtimageaddbadge'] = 'READY TO SET';
$string['thoughtcurrentimage'] = 'Current shared image';
$string['thoughtcurrentimagehint'] = 'This is the image currently used for every Thought of the Day. Upload a new image below to replace it.';

$string['thoughtplaceholder'] = 'Enter the Thought of the day here';
$string['thoughtoftheday_help'] = 'Enter only the quote. The plugin automatically places the quote inside the shared Thought of the Day image. No HTML is required.';
$string['actions'] = 'Actions';
$string['edit'] = 'Edit';
$string['cancel'] = 'Cancel';
$string['noemployees'] = 'No employee records have been added yet.';
$string['employeeidrequired'] = 'Employee ID is required.';
$string['employeeidexists'] = 'This Employee ID belongs to another employee record. You can keep the same ID when editing this record, but each employee must have a unique ID.';
$string['recordsaved'] = 'Employee dates saved.';
$string['recordupdated'] = 'Employee dates updated.';
$string['invalidrecord'] = 'The requested employee record was not found.';
$string['employeemgmt:manage'] = 'Manage employee date and flyer setup records and settings.';
$string['employeemgmt:view'] = 'View employee date and flyer setup records.';
$string['privacy:metadata:local_birthdayflyer'] = 'Stores employee profile and date information entered in the Employee Birthdate & Flyer Setup plugin.';
$string['privacy:metadata:local_birthdayflyer:empid'] = 'Employee ID.';
$string['privacy:metadata:local_birthdayflyer:empfullname'] = 'Employee full name.';
$string['privacy:metadata:local_birthdayflyer:empemail'] = 'Employee email address.';
$string['privacy:metadata:local_birthdayflyer:date_of_birth'] = 'Employee date of birth.';
$string['privacy:metadata:local_birthdayflyer:date_of_birth_stamp'] = 'Employee date of birth timestamp.';
$string['privacy:metadata:local_birthdayflyer:date_of_joining'] = 'Employee date of joining.';
$string['privacy:metadata:local_birthdayflyer:date_of_joining_stamp'] = 'Employee date of joining timestamp.';
$string['privacy:metadata:local_birthdayflyer:marriage_date'] = 'Employee marriage date.';
$string['privacy:metadata:local_birthdayflyer:marriage_date_stamp'] = 'Employee marriage date timestamp.';
$string['privacy:metadata:local_birthdayflyer:father_dob'] = "Father's date of birth.";
$string['privacy:metadata:local_birthdayflyer:father_dob_stamp'] = "Father's date of birth timestamp.";
$string['privacy:metadata:local_birthdayflyer:mother_dob'] = "Mother's date of birth.";
$string['privacy:metadata:local_birthdayflyer:mother_dob_stamp'] = "Mother's date of birth timestamp.";
$string['privacy:metadata:local_birthdayflyer:wife_dob'] = "Wife's date of birth.";
$string['privacy:metadata:local_birthdayflyer:wife_dob_stamp'] = "Wife's date of birth timestamp.";
$string['privacy:metadata:local_birthdayflyer:son_dob'] = "Son's date of birth.";
$string['privacy:metadata:local_birthdayflyer:son_dob_stamp'] = "Son's date of birth timestamp.";
$string['privacy:metadata:local_birthdayflyer:daughter_dob'] = "Daughter's date of birth.";
$string['privacy:metadata:local_birthdayflyer:daughter_dob_stamp'] = "Daughter's date of birth timestamp.";
$string['privacy:metadata:local_birthdayflyer:usermodified'] = 'Moodle user who last modified the record.';

$string['dashboardtitle'] = 'Employee Birthdate & Flyer Setup';
$string['dashboardintro'] = 'Set up employee dates used for birthday and anniversary greetings, and manage the related greeting flyers.';
$string['formintro'] = 'Set up the employee dates and details below. These dates are used to schedule birthday and anniversary greetings.';
$string['employeesintro'] = 'Review employee date records and update them when needed.';
$string['totalemployees'] = 'Employee date records';
$string['recordsupdatedlabel'] = 'Most recent update';
$string['recordslabel'] = 'records';
$string['employee'] = 'Employee';
$string['emptyintro'] = 'Set up the first employee\'s dates using the form above. Employee date records will appear here.';


$string['thoughtformtitle'] = 'Thought of the Day schedule';
$string['thoughtformintro'] = 'Save a quote for a specific date. The daily email uses the same shared Thought of the Day image for every scheduled quote.';
$string['thoughtdate'] = 'Date';
$string['savethought'] = 'Save thought';
$string['thoughtsaved'] = 'Thought of the Day saved.';
$string['thoughtfortoday'] = 'Thought of the Day';
$string['nothoughtfortoday'] = 'No thought has been configured for today.';
$string['taskdailydigest'] = 'Send daily employee Thought of the Day and birthday notifications';
$string['taskstarted'] = 'Employee daily digest started at {$a}.';
$string['tasknoemployees'] = 'No employee records were found. No emails were sent.';
$string['taskinvalidemail'] = 'Skipped employee {$a}: invalid email address.';
$string['thoughtmailtitle'] = 'Thought of the Day';
$string['birthdaymailtitle'] = 'Birthday reminder';
$string['emailgreeting'] = 'Hello {$a},';
$string['birthdaysectiontitle'] = 'Birthdays today';
$string['thoughtsectiontitle'] = 'Thought of the Day';
$string['emailfooter'] = 'Have a great day.';
$string['birthdayemployee'] = 'Your birthday';
$string['birthdayfather'] = "Your father's birthday";
$string['birthdaymother'] = "Your mother's birthday";
$string['birthdaywife'] = "Your wife's birthday";
$string['birthdayson'] = "Your son's birthday";
$string['birthdaydaughter'] = "Your daughter's birthday";
$string['tasksentemployee'] = 'Sent daily digest to employee {$a}.';
$string['taskduplicate'] = 'Daily digest for employee {$a} was already recorded by another task run.';
$string['taskemailfailed'] = 'Email delivery failed for employee {$a}.';
$string['taskfinished'] = 'Employee daily digest finished. Sent: {$a->sent}; skipped: {$a->skipped}; failed: {$a->failed}.';


$string['privacy:metadata:local_birthdayflyer_thought'] = 'Stores dated Thought of the Day messages and the user who last modified them.';
$string['privacy:metadata:local_birthdayflyer_thought:thoughtdate'] = 'Date the thought is intended to be sent.';
$string['privacy:metadata:local_birthdayflyer_thought:thought'] = 'Thought of the Day text.';
$string['privacy:metadata:local_birthdayflyer_thought:usermodified'] = 'Moodle user who last modified the thought.';
$string['privacy:metadata:local_birthdayflyer_sent'] = 'Stores the employee and date for which the daily email was successfully handed to Moodle mail.';
$string['privacy:metadata:local_birthdayflyer_sent:employeeid'] = 'Employee record receiving the daily email.';
$string['privacy:metadata:local_birthdayflyer_sent:senddate'] = 'Date the daily email was sent.';

$string['plugininitials'] = 'BF';


// Birthday email templates and Thought of the Day import.
$string['employeefullname'] = 'Employee full name';
$string['marriagedate'] = 'Marriage date';
$string['importthoughts'] = 'Import Thought of the Day';
$string['managetemplates'] = 'Birthday Wishes';
$string['backtoemployees'] = 'Back to Employee Dates';
$string['excelworkbook'] = 'Excel or CSV file';
$string['updateexistingthoughts'] = 'Update thoughts when the date already exists';
$string['excelimporthelp'] = 'Upload an Excel (.xlsx or .xls) or CSV file. The first row must contain columns named Date and Thought of the Day. Each following row must contain a date and the thought for that date.';
$string['importfile'] = 'Import file';
$string['importnofile'] = 'Please upload a workbook first.';
$string['phpspreadsheetmissing'] = 'This Moodle PHP environment does not have PhpSpreadsheet available. Ask your Moodle administrator to enable/install the supported spreadsheet library, or upload a CSV file instead.';
$string['importempty'] = 'The file contains no data rows.';
$string['importbadheaders'] = 'Could not find the required headers. The first row must contain Date and Thought of the Day.';
$string['importsummary'] = 'Import complete. New thoughts: {$a->imported}; updated thoughts: {$a->updated}; skipped rows: {$a->skipped}.';
$string['importfailed'] = 'Import failed: {$a}';
$string['templateeditor'] = 'Birthday Wishes email template editor';
$string['templatename'] = 'Name of the template';
$string['templatesubject'] = 'Email subject';
$string['templatetext'] = 'HTML email body (paste raw HTML)';
$string['templateimage'] = 'Upload image / birthday banner';
$string['templateactive'] = 'Active template';
$string['savetemplate'] = 'Save template';
$string['templatevariables'] = 'Supported placeholders: {{username}}, {{user fullname}}, {{user_fullname}}, {{employee_fullname}}, {{employeeid}}, {{employeeemail}}, {{date}}, {{thought_of_the_day}}, {{birthday_person}}, {{relationship}}, {{work_anniversary_years}}, {{marriage_anniversary_years}}, {{years}}, {{image}} and {{image_url}}. The uploaded image is inserted where {{image}} appears; if neither {{image}} nor {{image_url}} is used, it is added at the top of the email. {{image_url}} gives only the image address, for a background, e.g. &lt;td background="{{image_url}}" style="background-image:url(\'{{image_url}}\')"&gt;. See README.md for an Outlook-safe background example.';
$string['templatesaved'] = 'Birthday email template saved.';
$string['templatedeleted'] = 'Birthday email template deleted.';
$string['confirmdeletetemplate'] = 'Are you sure you want to delete the template "{$a}"?';
$string['savedtemplates'] = 'Saved templates';
$string['notemplatesyet'] = 'No birthday templates have been created yet.';
$string['taskmissingthought'] = 'WARNING: No Thought of the Day is configured for {$a}. Non-birthday emails will not be sent until a thought is added.';
$string['birthdayimagealt'] = 'Birthday wishes';
$string['birthdaypersonemployee'] = 'you';
$string['birthdaypersonfather'] = 'your father';
$string['birthdaypersonmother'] = 'your mother';
$string['birthdaypersonwife'] = 'your wife';
$string['birthdaypersonson'] = 'your son';
$string['birthdaypersondaughter'] = 'your daughter';
$string['relationshipemployee'] = 'employee';
$string['relationshipfather'] = 'father';
$string['relationshipmother'] = 'mother';
$string['relationshipwife'] = 'wife';
$string['relationshipson'] = 'son';
$string['relationshipdaughter'] = 'daughter';
$string['privacy:metadata:local_birthdayflyer_template'] = 'Stores administrator-created birthday email templates and uploaded banner image references.';
$string['privacy:metadata:local_birthdayflyer_template:name'] = 'Template name.';
$string['privacy:metadata:local_birthdayflyer_template:body'] = 'HTML email template body.';


// Dashboard navigation and upcoming Thought of the Day schedule.
$string['dashboard'] = 'Dashboard';
$string['pluginareanavigation'] = 'Employee date and flyer setup navigation';
$string['upcomingthoughts'] = 'Upcoming Thoughts of the Day';
$string['upcomingthoughtsintro'] = 'Review the next scheduled messages in date order. The dashboard shows up to ten upcoming entries.';
$string['scheduledlabel'] = 'scheduled';
$string['noupcomingthoughts'] = 'No upcoming thoughts scheduled';
$string['noupcomingthoughtsintro'] = 'Import a dated Excel or CSV file, or add a quote using the form above.';
$string['thoughtdatecolumn'] = 'Scheduled date';
$string['thoughttextcolumn'] = 'Thought of the Day';
$string['importpageintro'] = 'Upload a dated workbook to schedule Thought of the Day messages. Moodle uses each date to select the message for the daily email.';
$string['templatespageintro'] = 'Create professional Birthday Wishes, work anniversary, and marriage anniversary emails with personalized placeholders and uploaded images.';
$string['savedtemplatesintro'] = 'Review existing templates and confirm which template is active for each celebration type.';

$string['importformintro'] = 'The first row should contain Date and Thought of the Day columns. Enter only the quote text; the plugin automatically applies the shared image and email layout. Existing dates can be updated when you select the update option.';
$string['downloadthoughtcsvsample'] = 'Download sample CSV';
$string['editemailtemplate'] = 'Edit Birthday Wishes email template';
$string['templateformintro'] = 'Paste the complete HTML email body here, insert supported placeholders, and upload one professional banner image. An optional .eml file can be attached separately.';

$string['confirmdeleteemployee'] = 'Are you sure you want to delete the employee date setup for "{$a}" and the related send history?';
$string['employeedeleted'] = 'Employee date setup deleted successfully.';
$string['editthought'] = 'Edit scheduled thought';
$string['thoughtdeleted'] = 'Thought deleted successfully.';
$string['confirmdeletethought'] = 'Are you sure you want to delete this scheduled thought?';

// Template event categories.
$string['templateeventtype'] = 'Template type';
$string['eventtypebirthday'] = 'Birthday wishes (legacy)';
$string['eventtypebirthdayemployee'] = 'Birthday wishes - Employee';
$string['eventtypebirthdayrelationship'] = 'Birthday wishes - Family / Relationship';
$string['eventtypeworkanniversary'] = 'Work anniversary';
$string['eventtypemarriageanniversary'] = 'Marriage anniversary';

$string['templateattachment'] = 'Optional .eml email attachment';
$string['privacy:metadata:local_birthdayflyer_thought:imagekey'] = 'Legacy secure key retained for previously stored Thought of the Day image references.';


$string['settings'] = 'Birthday Flyer settings';
$string['emailcontrolsheading'] = 'Email delivery controls';
$string['emailcontrolsintro'] = 'Enable or disable each type of scheduled email. Disabled categories are skipped by the daily scheduled task. Existing settings default to enabled so current email behaviour is preserved after upgrading.';
$string['settingthought'] = 'Send Thought of the Day emails';
$string['settingthought_desc'] = 'When disabled, no Thought of the Day emails are sent. Other enabled email types continue to work.';
$string['settingemployeebirthday'] = 'Send employee birthday emails';
$string['settingemployeebirthday_desc'] = 'When disabled, employee birthday emails are skipped. Family birthday emails are controlled separately.';
$string['settingfamilybirthday'] = 'Send family birthday emails';
$string['settingfamilybirthday_desc'] = 'When disabled, birthday emails for father, mother, wife, son, and daughter are skipped. Employee birthday emails are controlled separately.';
$string['settingworkanniversary'] = 'Send work anniversary emails';
$string['settingworkanniversary_desc'] = 'When disabled, work anniversary emails are skipped.';
$string['settingmarriageanniversary'] = 'Send marriage anniversary emails';
$string['settingmarriageanniversary_desc'] = 'When disabled, marriage anniversary emails are skipped.';

$string['pluginlinktitle'] = 'Birthday Flyer';
$string['openplugin'] = 'Open Birthday Flyer';

$string['employeesearchplaceholder'] = 'Search employees by ID, name, email, or any date...';
$string['clearsearch'] = 'Clear search';
// Added 16 November 2026: optional global CC settings for scheduled emails.
$string['ccsettingsheading'] = 'Email CC settings';
$string['ccsettingsintro'] = 'Optionally send a copy of Birthday Flyer emails to one or more additional email addresses. The employee remains the primary recipient. Choose below which email types are copied.';
$string['enableemailcc'] = 'Enable CC';
$string['enableemailcc_desc'] = 'When enabled, the configured CC addresses receive a copy of each email type ticked below.';
$string['emailccaddress'] = 'CC email addresses';
$string['emailccaddress_desc'] = 'One or more email addresses, separated by commas, semicolons or new lines (maximum 10). External addresses such as Gmail are allowed. If an employee is skipped (for example a suspended account), no copy is sent for that employee either.';

// Added 18 November 2026: labels for additional family date fields and events.
$string['husbanddob'] = "Husband's date of birth";
$string['daughter2dob'] = "Second daughter's date of birth";
$string['son2dob'] = "Second son's date of birth";
$string['birthdaypersonhusband'] = "Husband";
$string['relationshiphusband'] = "Husband";
$string['birthdaypersondaughter2'] = "Second daughter";
$string['relationshipdaughter2'] = "Second daughter";
$string['birthdaypersonson2'] = "Second son";
$string['relationshipson2'] = "Second son";
$string['reportbirthdayhusband'] = "Husband birthday";
$string['reportbirthdaydaughter2'] = "Second daughter birthday";
$string['reportbirthdayson2'] = "Second son birthday";

// v1.2.0 (25 November 2026): CC per email type, several CC addresses, delivery log, layout and checks.
$string['emailccaddressinvalid'] = 'These are not valid email addresses: {$a}';
$string['emailccaddresstoomany'] = 'Please enter at most {$a} CC addresses.';
$string['cctype_desc'] = 'Add the CC addresses to this type of email.';
$string['cctype_thought'] = 'CC Thought of the Day';
$string['cctype_birthdayemployee'] = 'CC employee birthday emails';
$string['cctype_birthdayrelationship'] = 'CC family birthday emails';
$string['cctype_workanniversary'] = 'CC work anniversary emails';
$string['cctype_marriageanniversary'] = 'CC marriage anniversary emails';
$string['deliveryheading'] = 'Delivery and layout';
$string['deliveryheading_desc'] = 'Optional settings. The defaults keep the original behaviour.';
$string['thoughtlayout'] = 'Thought of the Day layout';
$string['thoughtlayout_desc'] = 'Original overlay: the quote is placed over the artwork with CSS positioning; Gmail and Outlook remove that positioning, so there the quote appears below the picture. Email-safe: a 600px table with the artwork as background (plus an Outlook fallback), so the quote stays on the artwork in Gmail and Outlook too. The email-safe layout loads the artwork from this site, so the site must be reachable from the internet. Send yourself a test with cli/send_test.php before switching.';
$string['thoughtlayout_overlay'] = 'Original overlay (default)';
$string['thoughtlayout_emailsafe'] = 'Email-safe background';
$string['senddelayms'] = 'Pause between emails (milliseconds)';
$string['senddelayms_desc'] = 'Optional pause after each email, for mail providers that limit how many messages can be sent per minute. 0 = no pause. Example: 500 sends about 120 emails per minute.';
$string['taskalreadysent'] = 'Email "{$a->type}" for employee {$a->empid} was already sent today; not sending again.';
$string['taskemailfailedtype'] = 'Email "{$a->type}" for employee {$a->empid} not sent: {$a->reason}';
$string['taskccinvalid'] = 'Ignoring invalid CC addresses: {$a}';
$string['reportdeliveryissues'] = 'Delivery issues (last 30 days)';
$string['reportdeliveryissuesintro'] = 'Emails that were skipped or failed, with the reason. A failed email is retried automatically if the scheduled task runs again on the same day (use "Run now" under Scheduled tasks); emails already sent are never sent twice.';
$string['reportnodeliveryissues'] = 'No skipped or failed emails in the last 30 days.';
$string['reportdeliveryissuescount'] = 'Delivery issues (30 days)';
$string['reportemailtype'] = 'Email type';
$string['reportreason'] = 'Reason';
$string['reportstatus_sent'] = 'Sent';
$string['reportstatus_skipped'] = 'Skipped';
$string['reportstatus_failed'] = 'Failed';
$string['messagetype_thought'] = 'Thought of the Day';
$string['messagetype_birthdayemployee'] = 'Employee birthday';
$string['messagetype_birthdayrelationship'] = 'Family birthday';
$string['messagetype_workanniversary'] = 'Work anniversary';
$string['messagetype_marriageanniversary'] = 'Marriage anniversary';
$string['messagetype_recipient'] = 'All emails';
$string['reason_invalidemail'] = 'The employee email address is empty or invalid.';
$string['reason_suspended'] = 'The matching Moodle account is suspended.';
$string['reason_deleted'] = 'The matching Moodle account is deleted.';
$string['reason_nologin'] = 'The matching Moodle account uses the "No login" authentication method.';
$string['reason_bounce'] = 'The address is over Moodle\'s email bounce limit.';
$string['reason_notallowed'] = 'The address is blocked by Moodle\'s allowed/denied email domain settings.';
$string['reason_noemailever'] = 'Not sent: email is disabled on this site ($CFG->noemailever).';
$string['reason_mailerror'] = 'Mail server error: {$a}';
$string['templatewarnbackgrounddata'] = 'This template uses an inline data: image as a background. Gmail and Outlook do not show those. Upload the image and use {{image_url}} instead.';
$string['templatewarnbackgroundcid'] = 'This template uses a cid: image as a background. Gmail does not show those. Use {{image_url}} instead.';
$string['templatewarndraftfile'] = 'This template links to a draft file (draftfile.php). Draft files are temporary and need a login, so the image will disappear. Upload the image with the template image field and use {{image}} or {{image_url}}.';
$string['templatewarnpluginfile'] = 'This template links to an image through pluginfile.php, which usually needs a Moodle login. Recipients reading email outside Moodle may not see it. Prefer the template image field with {{image}} or {{image_url}}.';
$string['templatewarnlocalhost'] = 'This template links to localhost / 127.0.0.1. Recipients cannot load images from that address.';
$string['privacy:metadata:local_birthdayflyer_log'] = 'Per-email delivery log of the daily scheduled task.';
$string['privacy:metadata:local_birthdayflyer_log:employeeid'] = 'The employee record the email was for.';
$string['privacy:metadata:local_birthdayflyer_log:senddate'] = 'The day the email was processed.';
$string['privacy:metadata:local_birthdayflyer_log:messagetype'] = 'The type of email.';
$string['privacy:metadata:local_birthdayflyer_log:status'] = 'Whether the email was sent, skipped or failed.';
$string['privacy:metadata:local_birthdayflyer_log:reason'] = 'Why the email was skipped or failed.';
$string['privacy:metadata:local_birthdayflyer_log:ccaddresses'] = 'The CC addresses the email was copied to.';
$string['privacy:metadata:local_birthdayflyer:husband_dob'] = 'Husband date of birth.';
$string['privacy:metadata:local_birthdayflyer:daughter2_dob'] = 'Second daughter date of birth.';
$string['privacy:metadata:local_birthdayflyer:son2_dob'] = 'Second son date of birth.';
$string['clisendtest_help'] = 'Send one employee\'s Birthday Flyer emails to a test address. Nothing is logged, so the real morning email is not affected.

Options:
--empid=ID        Employee ID (as uploaded), required.
--to=EMAIL        Address that receives the test emails, required.
--date=YYYY-MM-DD Day to simulate (default: today, server time).
--withcc          Also copy the configured CC addresses (per-type CC switches apply).
-h, --help        Show this help.

Example:
php local/birthdayflyer/cli/send_test.php --empid=E1001 --to=me@example.com --date=2026-12-01
';
$string['taskccrejected'] = 'Email "{$a->type}" for employee {$a->empid} was sent, but the mail server refused a CC address: {$a->reason}';
$string['reportstatus_ccrejected'] = 'Sent, CC refused';
$string['reason_ccrejected'] = 'The employee received the email, but the mail server refused the CC address (often a relay that does not allow external recipients): {$a}';

=== Resume Manager ===
Contributors: mikejolley, kraftbj, tripflex, danjjohnson, aheckler, bryceadams, fitoussi, jakeom, alexsanford1
Requires at least: 4.7
Tested up to: 5.2
Stable tag: 1.17.3
License: GNU General Public License v3.0

Manage candidate resumes from the WordPress admin panel, and allow candidates to post their resumes directly to your site.

= Documentation =

Usage instructions for this plugin can be found here: [https://wpjobmanager.com/documentation/add-ons/resume-manager/](https://wpjobmanager.com/documentation/add-ons/resume-manager/).

= Support Policy =

For support, please visit [https://wpjobmanager.com/support/](https://wpjobmanager.com/support/).

We will not offer support for:

1. Customisations of this plugin or any plugins it relies upon
2. Conflicts with "premium" themes from ThemeForest and similar marketplaces (due to bad practice and not being readily available to test)
3. CSS Styling (this is customisation work)

If you need help with customisation you will need to find and hire a developer capable of making the changes.

== Installation ==

To install this plugin, please refer to the guide here: [http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

== Changelog ==

= 1.17.3 =
* Compatibility fix with WP Job Manager 1.33.0 that shows values of resume fields in WP admin's resume editor.

= 1.17.2 = 
* Feature - Add password fields for registration. 
* Change - Remove LinkedIn Import functionality that is no longer supported by LinkedIn.
* Fix - Lower time resume listing results are cached to 24 hours.
* Fix - Clear resume listing cache on post deletion.

= 1.17.1 = 
* Change - Move to Select2 for enhanced select fields and support with WP Job Manager 1.32.0 asset management.
* Change - Delete resume files when permanently deleting resumes from trash.
* Fix - Escapes attachment URLs on resumes.

= 1.17.0 =
* Feature - Adds user data export using WordPress core's "Export Personal Data" tool.
* Feature - Adds optional deletion of resumes when using WordPress core's "Erase Personal Data" tool.
* Feature - Adds the option to use WP Job Manager core's reCAPTCHA field functionality on resume submission form.
* Change - Resumes are now deleted when deleting a user without merging content.
* Fix - Fixes an error that would show up with PHP 7.2.
* Fix - Resume notifications are sent when using WC Paid Listings.
* Fix - Resume notification isn't sent again if users refresh resume submission form result.

= 1.16.1 =
* Feature - Add option to prevent search engines from crawling resumes
* Change - Default `orderby` is now `date` and not `title`
* Fix - Console errors when importing information from LinkedIn
* Fix - Random ordering now works on resume listing. `orderby=rand_featured` is now also supported to keep featured resumes at top.
* Fix - Issue where guests were getting stuck in preview when submitting a new resume.

= 1.16.0 =
* Change - Adds compatibility with WP Job Manager 1.29.0 and requires it for future updates.
* Change - Show job title in resume selector when applying for job. 
* Fix - Prevent people from applying for jobs with unapproved resumes.

= 1.15.4 =
* Feature - Allow searching of post meta.
* Fix - Prevent editing of resumes by other logged in users.
* Fix - Properly calling 'before_get_resumes' hook.
* Fix - Call the_candidate_video_embed action when necessary.
* Fix - Issue with redirect after preview on WC Paid Listing plugin workflow when package selection is first.
* Fix - Issue with license notice.
* Fix - Issue with resume_manager_user_can_view_resume filter.
* Dev - Adds 'get_resume_attachments()' function to fetch URLs and file paths.

= 1.15.3 =
* Fix - Add jpeg for candidate photos. Allows iOS Camera Roll uploads.

= 1.15.2 =
* Fix - Only load widget files once.
* Fix - Pass Job and Resume ID to login page.
* Fix - Meta retrieval with paid listings.

= 1.15.1 =
* Fix - Resume download link when previewing.
* Dev - Moved resume_manager_resume_submitted to match WPJM core.

= 1.15.0 =
* Feature - Force apply with resume setting will now force before applications plugin can be used as well.
* Tweak - Deeper integration with applications.
* Tweak - Improved 'apply' step after resume submission. Now uses job_apply shortcode to keep things DRY.
* Tweak - Split apply with resume settings. "Force Resume Creation" to make users submit resumes before they can see apply forms, and "Force Apply with Resume" to force the resume manager apply form to be used regardless of other installed plugins.
* Tweak - Attachments.

= 1.14.0 =
* Feature - UI to allow notifications to different addresses.
* Fix - Improved resume expiry setting and calculation.
* Tweak - resume_manager_default_candidate_photo filter.

= 1.13.2 =
* Fix - Relist compatibility with paid listings.
* Tweak - resume_manager_resume_filters_before and after hooks.

= 1.13.1 =
* Fix - Add text links.
* Fix - Correctly validate email addresses.
* Fix - Load user fields if posting a resume from job page links.
* Fix - anonymize default post_name/permalink. e.g. mike-randomstring-web-developer-london-uk

= 1.13.0 =
* Feature - From address for application set to candidate email.
* Feature - Option to hide the full resume name based on a new permission.
* Fix - File handling in repeated fields.
* Fix - Only show linkedin when resume_manager_user_can_post_resume.
* Fix - Only link to resume when published.
* Tweak - Use repeated-field.php template for links, education and experience.
* Tweak - Made candidate dashboard columns customisable.
* Tweak - Preserve case in new tags.
* Tweak - Don't attach images to resumes. Enabled via filter. resume_manager_attach_uploaded_files. False by default.

= 1.12.0 =
* Feature - Make keyword search also search term names.
* Tweak - Query improvements from Job Manager.
* Tweak - Filter apply mail recipient and subject.
* Tweak - Resume display template/styling.
* Tweak - Handle attachments.

= 1.11.4 =
* Fix - Support local videos.
* Dev - More repeated field enhancements.
* Tweak - Hide contact button on preview.

= 1.11.3 =
* Dev - Made repeated_rows_html public.
* Dev - save_repeated_row method.
* Fix - New row HTML.

= 1.11.2 =
* Fix - Prevent blank data being imported from LinkedIn.

= 1.11.1 =
* Fix - It's 2015, but some people are still running PHP 5.2. Compatibility fix.
* Tweak - Better checking to see if JM exists.

= 1.11.0 =
* Feature - Backend sorting of repeated rows.
* Feature - Backend search meta data when searching resumes.
* Feature - Added separate option to enable apply with resume for URL based jobs (when also using applications).
* Tweak - Added candidate-dashboard-login.php file for logged out users.
* Tweak - Refactored form classes to be instance based rather than static. Reduction in code base. Requires Job Manager 1.22.0.
* Tweak - Improved handling and filters for repeated fields (links, edu, exp).
* Tweak - Improved admin columns display.
* Tweak - Cursor:move for frontend repeated fields.

= 1.10.3 =
* Fix widget class check.
* Fix skill count check.

= 1.10.2 =
* Fix - Typo in upload method.

= 1.10.1 =
* Fix - Author edit.
* Fix - File upload field key.

= 1.10.0 =
* Feature - Added setup screen for new installs.
* Feature - Option to limit the number of resumes a user can post.
* Feature - Added recent and featured resume widgets.
* Feature - Option to limit the number if skills a user can input.
* Feature - Limit the number of skills which can be input.
* Tweak - Added no results template.
* Tweak - Improved settings page.

= 1.9.3 =
* Fix - Application last step.
* Fix - Correct post name for guests.

= 1.9.2 =
* Prevent navigation warnings in some cases.
* Import linkedin photo.
* Attach resume file when applying.

= 1.9.1 =
* Feature - Automatically Generate Username from Email Address option (disable to show a username field). Requires Job Manager 1.20+

= 1.9.0 =
* Feature - Allow role/cap checks to support CSV list of caps.
* Feature - Option to email resume details for new submissions to the admin.
* Tweak - Moved application related options to own setting tab.
* Tweak - Improved default resume list styling.
* Dev - Abiltiy to pass shortcode args to submit_resume_form shortcode.

= 1.8.2 =
* Check summary exists during import.
* Allow apply with hidden resumes.
* Fixed get_posted_multiselect_field

= 1.8.1 =
* Fix - Skill input.

= 1.8.0 =
* Added show_more and show_pagination arguments to the main shortcode.
* Added multi-select funtionality for categories for resume submission + resume filtering.
* Added filter for required/optional labels.
* Added ability for guests to submit resumes (but they cannot edit them!).
* Added tighter integration with the Job Applications plugin (so applications through resume manager can be saved in the database). Requires Applications 1.5.0.
* Added confirmation when removing education and experience.
* Fix - tinymce type checking.
* Tweak - Filter to disable chosen: job_manager_chosen_enabled (same as job manager core)
* Tweak - submit_resume_form_submit_button_text filter.
* Tweak - Pick up search_category from querystring to set default/selected category.
* Tweak - Added step input to submission form.

= 1.7.8 =
* the_candidate_video HTTPS fix.
* Add remove link to existing education/links.
* Improved uninstall script.

= 1.7.7 =
* Added dropdown to select resume submission page instead of slug option.
* Added 'add resume' link to candidate dashboard.

= 1.7.6 =
* Support skills for other field types.
* When creating a resume, copy candidate name to WP Profile (if not yet set).

= 1.7.5 =
* Fix access checks for guest posted resumes.
* Use ICL_LANGUAGE_CODE.

= 1.7.4 =
* Fix - Use triggerHandler() instead of trigger() in ajax-filters to prevent events bubbling up.
* Fix - Append current 'lang' to AJAX calls for WPML.
* Fix - When specifying categories on the jobs shortcode, don't clear those categories on reset.

= 1.7.3 =
* Fix resume file loop.

= 1.7.2 =
* Fix - Revised resume skills to work when slugs match. e.g. C++ C#, C
* resume_manager_user_can_download_resume_file filter

= 1.7.1 =
* Fix LinkedIn jquery.

= 1.7.0 =
* Mirroring WP Job Manager, added listing duration to resumes to allow them to expire/be relisted. Works in tandem with WC Paid Listings for charging submission and relisting.
* Added expirey field to backend.
* Improved post status display for resumes.
* Support html5 multiple files like WP Job Manager 1.14.
* Added video field for resumes.
* Added support for new field type in WP Job Manager 1.14.

= 1.6.4 =
* Fix category name display when using slugs.
* Fix text domains.

= 1.6.3 =
* Option to choose the role users get during registration.

= 1.6.2 =
* _candidate_title change to Professional to match frontend.
* Fix notice in update_resume_data.
* Fix resume_file notice.

= 1.6.1 =
* Fix updater.
* Job manager compat update.

= 1.6.0 =
* Confirm navigation when leaving the resume submission form.
* Added a new option to allow users to import their resume data from LinkedIn during submission.
* Added ability for users to make resumes hidden from their candidate dashboard (or publish them again).
* Added setting to automatically hide resumes after X days. Candidates can re-publish hidden resumes from their dashboard.
* Allow admin to 'feature' resumes, making them queryable and sticky.
* Fire updated_results hook after loading results.
* Fix submit_resume_form_fields_get_resume_data hook.

= 1.5.2 =
* Fix closing tag in view links.

= 1.5.1 =
* Show link to submit new resume to logged out users

= 1.5.0 =
* Additonal hooks in single template
* Extra args for submit_resume_form_save_resume_data
* Option to force users to apply via their online resume
* Built apply process into resume submission form

= 1.4.4 =
* Text domain fixes

= 1.4.3 =
* Added new updater - This requires a licence key which should be emailed to you after purchase. Past customers (via Gumroad) will also be emailed a key - if you don't recieve one, email me.

= 1.4.2 =
* Add posted by (author) setting in backend.
* Fix email URLs

= 1.4.1 =
* Jobify + WP SEO compatibility
* strtolower on capabilities

= 1.4.0 =
* Added the ability for logged in users to apply to a job with an on-file resume + include a custom message (requires Job Manager 1.9 and compatible template files)
* Added a way to have private share links for resumes (used in the apply feature). get_resume_share_link appends a key to the permalink and when present, any user can view the resume (even if standard permissions deny access).
* Drag drop sorting for education and experience fields on the resume submission form
* Template file for contact details.

= 1.3.0 =
* Improved search by including custom fields and comma separated keywords
* Get geolocation data for resumes
* Support for languages in the WP_LANG dir (subfolder wp-job-manager-resumes)

= 1.2.2 =
* Template files and functions for resume links

= 1.2.1 =
* New dir for resume files so protection does not affect old images

= 1.2.0 =
* Use GET vars to search resumes
* Added grunt
* Updated all text domains to wp-job-manager-resumes
* Fix wp-editor field
* Include POT file
* Added 'x' to remove education/exp/links
* Secure downloading of resumes and protected resumes directory with htaccess

= 1.1.2 =
* Fix remove link for uploaded files
* Fix path to fonts
* add education, experience, and links filters

= 1.1.1 =
* Fix class exists check for WP_Job_Manager_Writepanels

= 1.1.0 =
* Added resume file input. Enabled in settings. Requires Job Manager 1.7.3.
* Added download link for resume file to single resume page
* the_candidate_location_map_link filter

= 1.0.1 =
* PHP 5.2 compat

= 1.0.0 =
* First release.

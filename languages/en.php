<?php
@define('__transLocale', 'en_GB');

return array(
// [1] Global -- General 27
'Welcome' => 'Welcome',
'Shoutbox' => 'Shoutbox',
'UserCP'	=> 'User Panel',
'AdminCP'	=> 'Admin Panel',
'yes'		=>	'yes',
'Yes'		=>	'Yes',
'no'		=>	'no',
'No'		=>	'No',
'Cancel'	=>	'Cancel',
'Return'	=>	'Return',
'Date'		=>	'Date',
'Message'	=>	'Message',
'UnknowError'	=> 'There was an unknown error',
'MaintenanceMode'		=> 'Archive is in maintenance mode',
'monthly'		=> 'monthly',
'weekly'		=> 'weekly',
'daily'			=> 'daily',
'disabled'		=> 'disabled',
'enabled'		=> 'enabled',
'ClickCont'		=> 'Click to continue',
'Error'			=> 'Error',
'Success'		=> 'Success',
'Search'		=> 'Search',
'Reset'			=> 'Reset',
'Edit'			=> 'Edit',
'Title' 		=>	'Title',
'Author' 		=>	'Author',
'Privacy'		=> 	'Privacy',

// [2] Global -- Login related 20
'Login'			=> 'Login',
'LoginName'		=> 'Login',
'VisibleName'	=> 'Visible name',
'Logout'		=> 'Logout',
'Username'		=> 'User name',
'Password'		=> 'Password',
'Email'			=> 'e-Mail',
'Registered'	=> 'Registered',
'Guest'			=> 'Guest',
'ForgotPW'		=> 'Forgot you password',
'ResetPW'		=> 'Reset my password',
'ResetPWMail'	=> 'If a user with this name or e-mail exists, a mail containing instructions on how to reset the password has been sent',
'ChangePW'		=> 'Change password',
'PWRecovery'	=> 'Password recovery',
'PWChanged'		=> 'Password has been changed',
'Login_NoMatch'	=> 'Failed to log in, invalid user name or password!',
'Login_NoData'	=> 'Failed to log in, data error!',
'oldPW'			=> 'old password',
'newPW'			=> 'new password',
'newPWrepeat'	=> 'repeat new password',

// [3] Global -- Sort 13
'Sort_Date'			=> 'Date',
'Sort_ID'			=> 'ID',
'Sort_Title'		=> 'Title',
'Sort_Label'		=> 'Label',
'Sort_Headline'		=> 'Headline',
'Sort_Name'			=> 'Name',
'Sort_Text'			=> 'Text',
'Sort_Updated'		=> 'Updated',
'Sort_Count'		=> 'Count',
'Sort_COpen'		=> 'Opens',
'Sort_CClose'		=> 'Closes',
'Sort_Category' 	=> 'Category',
'Sort_TagGroup'		=> 'Tag group',
'Sort_Visibility' 	=> 'Visibility',
'Sort_Comment'		=> 'Comment',

// [4] Global -- User registration 15
'Registration'			=> 'Registration',
'RegisterNow'			=> 'Register now',
'Reg_AcceptTOS'			=> 'I accept these terms!',
'Reg_AcceptTOS_Error'	=> 'You have to accept the terms of use',
'Reg_UsernameEmpty'		=> 'No login name provided!',
'Reg_UsernameTaken'		=> 'Login name already in use.',
'Reg_EmailEmpty'		=> 'No e-mail address provided!',
'Reg_EmailTaken'		=> 'E-mail address already in use.',
'Reg_AlreadyMember'		=> 'A user with this data already exists.',
'Reg_PasswordRepeat'	=> 'Repeat password',
'Reg_PasswordTwice'		=> 'Enter your password twice',
'Reg_PasswordMismatch'	=> 'Your passwords do not match',
'Reg_PasswordCriteria'	=> 'Your desired password does not match the requirements',
'Reg_Failed'			=> 'Registration failed!',
'Reg_FailedSorry'		=> 'Sorry, can\'t complete registration at this point.',
	'Reg_Moderation'		=> 'Registration completed, however it requires moderation',
	'Reg_Done'				=> 'Registration completed',
	'Reg_Mail'				=> 'A mail has been sent to you, ',
	'Reg_MailFail'			=> 'The server tried to send you a mail but encountered a problem, you registration cannot be completed at this point',

// [5] Global -- System 5
'CookieText'	=> 'This website uses cookies to ensure you get the best experience on our website.',
'CookieInfo'	=> 'More info on cookies',
'CookieAccept'	=> 'Accept cookies',
'Month_Calendar' => '{0,date,custom,%B %Y}',	// using php strftime
'Weekday' => '{0,date,custom,%A}',

// [6] Global -- Forms 8
'CaptchaMismatch'	=> 'Captcha did not match',
'GuestURL'			=> 'Guests are not allowed to post URLs',
'GuestNameEmpty'	=> 'Name field cannot be empty',
'MessageEmpty'		=> 'No message entered',
'CommentEmpty'		=> 'No comment entered',
'CannotSave'		=> 'Could not save the data, please try again',
'FormErrors'		=> '{0, plural,zero	{No errors},one	{There was an error while processing this form},other {There were errors while processing this form} }',
'Submit'			=> 'Submit',

// [7] Global -- Search 1
'NoEntries'			=> 'No entries',

// [8] Global -- Archiv Stats 10
'Stats_Archive' => 'Archive stats',
'Stats_Members' => 'Members',
'Stats_Authors' => 'Authors',
'Stats_Stories' => 'Stories',
'Stats_Chapters' => 'Chapters',
'Stats_Reviews' => 'Reviews',
'Stats_Online' => 'Who\'s online',
'Stats_Guests' => 'Guests',
'Stats_Users' => 'Users',
'Stats_LatestMember' => 'Latest Member',

// [9] Story -- Search 21
'AdvancedSearch'	=> 'Advanced Search',
'Token_InputHint'	=> 'Enter search term',
'Token_NoMatches'	=> 'No matches',
'Token_StartTyping' => 'Start typing to search',
'SearchContest'		=> 'Search contest',
'SearchCharacter'	=> 'Search character',
'SearchStory'		=> 'Search story',
'SearchTag'			=> 'Search tag',
'LibraryStories'	=> 'Limit to stories from my library',
'Search_FavBM'		=> 'Favourites & bookmarks',
'Search_Fav'		=> 'Favourites',
'Search_BM'			=> 'Bookmarks',
'StoryExtent'		=> 'Extent of story',
'Size_SingleChapter'=> 'Single chapter',
'Size_MultiChapter'	=> 'Multiple chapters',
'Size_All'			=> 'Show all',
'Search_ShowAll'	=> 'Show all',
'SearchCompletedOnly'	=> 'Only show completed stories',
'ShowSearchForm'	=> 'Show search form',
'HideSearchForm'	=> 'Hide search form',
'TagsInclude'	=>	'Tags included',
'TagsExclude'	=>	'Tags excluded',

// [10] Story -- Fields 8
'Categories' =>	'Categories',
'Characters' =>	'Characters',
'Rating' =>	'Rating',
'Status' =>	'Status',
'Reviews' =>	'Reviews',
'Foreword' =>	'Foreword',
'Summary' =>	'Summary',
'Tags' =>	'Tags',

// [11] Story -- Info

// [12] Story -- Links 8
'TitleReadReviews'	=> 'Read reviews for \'{0}\'',
'BookmarkAdd'		=> '\'{0}\' has no bookmark, click to add.',
'BookmarkRemove'	=> '\'{0}\' has a bookmark, click to edit or remove.',
'FavouriteAdd'		=> '\'{0}\' is not a favourite, click to make it one.',
'FavouriteRemove'	=> '\'{0}\' is a favourite. Click to edit or remove.',
'BrowseStories'		=> '{0, plural, one	{Browse # story}, other	{Browse # stories} }',
'Review_Link' 		=> '{0, plural, zero {None yet! Be the first to write one ...}, one	{One review}, other	{# reviews} }',
'Review_Link_TOC'	=> '{0, plural, zero {No reviews}, one {One review}, other {# reviews} }',

// [13] Story -- Edit 10
'MainAuthor'	=> 'Main author',
'MainAuthors'	=> 'Main authors',
'MainAuthorNotEmpty' => 'Main author list cannot be empty',
'SupAuthor'		=> 'Supporting author',
'SupAuthors'	=> 'Supporting authors',
'StoryNotes'			=> 'Notes',
'StoryNotesExplained'	=> 'Notes and dedications go here',
'StorySummary'			=> 'Summary',
'StorySummaryExplained'	=> 'Your story summary goes here',
'ChapterText'			=> 'Chapter text',
'ChapterTextExplained'	=> 'Your chapter\'s content goes here',
///DB
'ReturnToOverview'		=> 'Return to overview',
'StoryAddSimilar'		=> 'A similar story already exist, <a href="{0}">edit story instead</a>',
'StoryConfirmSimilar'	=> 'Insert nevertheless',
'StoryTitle'	=>	'Story title',
'ChapterTitle'	=>	'Chapter title',
'ChapterText'	=>	'Chapter text',
'AddChapter'	=> 'Add chapter',
'EditHeader'	=> 'Edit header',
'DragdropSort'	=> 'Drag and drop to sort',
'SwitchPlainHTML'	=> 'Switch to HTML mode',
'SwitchVisual'		=> 'Switch to visual editor',

// [14] Authors
'NoAuthorsListed'	=> 'Currently, there are no authors to be listed',
'Overview'			=> 'Overview',

// User panel - PM
'PM_Inbox'				=> 'Inbox',
'PM_Outbox'				=> 'Outbox',
'PM_Write'				=> 'Write',
'PM_Outbox_Items'		=> '{0, plural,'.
							'zero	{Your Outbox is empty!},'.
							'one	{One message:},'.
							'other	{# messages:} }',
'PM_Inbox_Items' 		=> '{0, plural,'.
							'zero	{Your Inbox is empty!},'.
							'one	{One message:},'.
							'other	{# messages:} }',
'PM_Subject'			=> 'Subject',
'PM_Sender'				=> 'Sender',
'PM_Sent'				=> 'Sent',
'PM_Received'			=> 'Received',
'PM_Recipient'			=> 'Recipient',
'PM_question_Delete'	=> 'Delete message',
'PM_confirm_Delete'		=> 'Message will be deleted, sure?',
'PM_unread'				=> 'unread',
'PM_ReplySubject'		=> 'Re:',
'PM_ReplyMessageHeader'	=> 'On {0}, {1} wrote:',
'PM_WritePM'			=> 'Writing message',
'PM_ReadPM'				=> 'Reading message',

// User elements
'UserField_Type1'	=> 'URL',			// _FIELDURL
'UserField_Type2'	=> 'Select box',	// _FIELDSELECT
'UserField_Type3'	=> 'Yes/No',		// _FIELDYESNO
'UserField_Type4'	=> 'ID with URL',	// _FIELDIDURL
'UserField_Type5'	=> 'Custom Code',	// _FIELDCUSTOM
'UserField_Type6'	=> 'Text',			// _TEXT
'Nickname'			=> 'Nickname',
'Realname'			=> 'Realname',
'UserGroup'			=> 'User group',

'Author'	=> 'Author',
'Authors'	=> 'Authors',
'noAuthors'	=> 'no Authors yet',
'Recomm'	=> 'Recommendation',
'Recomms'	=> 'Recommendations',
'RecommBy'	=> 'Recommended by',
'Series'	=> 'Series',
'Story'		=> 'Story',
'Stories'	=> 'Stories',

// UserCP elements
'ChangeTo'	=> 'Change to',
'UserMenu_Profile' => 'Profile',
'UserMenu_Message' => 'Messaging',
'UserMenu_PMInbox' => '{0, plural,'.
							'zero	{Inbox},'.
							'other	{Inbox (# new)} }',
'UserMenu_PMOutbox' => 'Outbox',
'UserMenu_PMWrite' => 'Write',
	'MSG_deletedSuccess'	=> 'Message deleted',
	'MSG_deleteRead'	=> 'Unable to delete, this message has been read',
	'MSG_deleteNotFound' => 'Unable to delete, Message not found',
	'MSG_deleteNoAccess' => 'You have no access to this message',
'UserMenu_Shoutbox'			=> 'Shoutbox ({0})', 
	
'UserMenu_Authoring' => 'Authoring',
'UserMenu_AddStory' => 'Add Story',
	'Authoring_Finished'	=> 'Finished ({0})',
	'Authoring_Unfinished'	=> 'Unfinished ({0})',
	'Authoring_Drafts'		=> 'Drafts ({0})',
'UserMenu_MyLibrary' => 'My Library',
	'Library_Favourites'	=> 'Favourites ({0})',
	'AddFavourite'			=> 'Add favourite',
	'Library_Bookmarks'		=> 'Bookmarks ({0})',
	'AddBookmark'			=> 'Add bookmark',
	'Library_Recommendations' => 'Recommendations ({0})',

'UserMenu_Feedback' => 'Feedback',
'UserMenu_Reviews' => 'Reviews',
	'UserMenu_ReviewsWritten'	=> 'Reviews written ({0})',
	'UserMenu_ReviewsReceived'	=> 'Reviews received ({0})',
	'UserMenu_CommentsWritten'	=> 'Comments written ({0})',
	'UserMenu_CommentsReceived'	=> 'Comments received ({0})',
'UserMenu_Settings' => 'Settings',
	'UserMenu_Preferences' => 'Preferences',
	
'UCP_Pref_Alerts'			=>	'Notifications',
	'UCP_Pref_AlertOn'			=> 'Notify me when ...',
	'UCP_Pref_AlertFeedback'	=> '... I receive feedback',
	'UCP_Pref_AlertComment'		=> '... I receive a comment',
	'UCP_Pref_AlertFavourite'	=> '... a favourite has an activity',
'UCP_Pref_View'		=>	'View and interface',
	'UCP_Pref_TOC'		=> 'Show table of contents as default for stories with more than one chapter.',
	'UCP_Pref_Sort'		=> 'Default order of stories.',
	'UCP_Pref_sortAZ'	=> 'alphabetically',
	'UCP_Pref_sortNew'	=> 'by date (new first)',
	'UCP_Pref_Language'	=> 'Language',
	'UCP_Pref_Layout'	=> 'Layout/Theme',
	'UCP_Pref_Editor'	=> 'Use visual editor for stories.',
	'UCP_Pref_Age'		=> '***Age consent***',
	'UCP_Pref_hideTags'	=> '***Hide Tags***',

'UCP_StoryStatus' => 'Story status',
'UCP_ChapterStatus' => 'Chapter status',
'UCP_statusValidated' => 'Validation status',
	'UCP_statusValidated_'  => 'No data',
	'UCP_statusValidated_0'	=> 'Closed',
	'UCP_statusValidated_1'	=> 'Pending, author working',
	'UCP_statusValidated_2'	=> 'Pending, author done',
	'UCP_statusValidated_3'	=> 		   'Validated',
'UCP_statusValidatedReason' => 'Validation details',
	'UCP_statusValReason_0'	=> 'No reason provided',
	'UCP_statusValReason_1'	=> 'Set by user',
	'UCP_statusValReason_2'	=> 'Set by moderator',
	'UCP_statusValReason_3'	=> 'Set by admin',
	'UCP_statusValReason_4'	=> 'Forced rework',
	'UCP_statusValReason_5'	=> 'Minor rework in progress',
	'UCP_statusValReason_6'	=> 'Major rework in progress',
	'UCP_statusValReason_7'	=> 'Minor rework done',
	'UCP_statusValReason_8'	=> 'Major rework done',
	'UCP_statusValReason_9'	=> 'Locked/orphaned',
'UCP_statusCompleted'	=> 'Completion status',
	'UCP_statusCompleted_0'	=> 'Deleted',
	'UCP_statusCompleted_1'	=> 'Draft',
	'UCP_statusCompleted_2'	=> 'W.i.P.',
	'UCP_statusCompleted_3'	=> 'Completed',
'UserMenu_Curator' => 'Curator',
'UCP_ExplainMainAuthor'	=> 'All main authors can edit the story, unlike supporting authors',
'UCP_ExplainSupAuthor'	=> 'Supporting authors are mentioned along the other authors, but cannot edit the story',

'FB_Stats'					=> 'Feedback statistics',
'FB_ReviewRatio'			=> 'Review ratio',
'FB_ReviewsPerStory' => '{0, plural,'.
					'one	{This is one review per story},'.
					'other	{These are # reviews per story}
					}',
'FB_orReviewsPerChapter' => '{0, plural,'.
					'one	{or one review per chapter},'.
					'other	{or # reviews per chapter}
					}',
'FB_ReviewsPerChapter' => '{0, plural,'.
					'one	{This is one review per chapter},'.
					'other	{These are # reviews per chapter}
					}',

// AdminCP Home elements
'AdminMenu_General' => 'General settings',

'AdminMenu_Home' => 'Home',
'AdminMenu_Manual' => 'Manual',
'AdminMenu_CustomPages' => 'Custom Pages',
'AdminMenu_News' => 'News',
'AdminMenu_Modules' => 'Modules',
'AdminMenu_Logs' => 'Logs',
	'AdminMenu_Logs_AM' => 'Admin Maintenance',
	'AdminMenu_Logs_DL' => 'Deletions',
	'AdminMenu_Logs_EB' => 'Edit Member',
	'AdminMenu_Logs_ED' => 'Edit Story',
	'AdminMenu_Logs_LP' => 'Lost Password',
	'AdminMenu_Logs_RE' => 'Reviews',
	'AdminMenu_Logs_RG' => 'Registration',
	'AdminMenu_Logs_RF' => 'Registration failure',
	'AdminMenu_Logs_VS' => 'Validations',
'AdminMenu_Shoutbox' => 'Shoutbox',

'AdminMenu_Settings' => 'Settings',
'AdminMenu_Server' => 'Server',
	'AdminMenu_DateTime'	=> 'Date and time',
	'AdminMenu_Mail'		=> 'Mail and mail server',
	'AdminMenu_Maintenance'	=> 'Maintenance',
	'AdminMenu_Report'	=> 'Report',
'AdminMenu_Registration' => 'Registration',
	'AdminMenu_AntiSpam'	=> 'Spam protection',
'AdminMenu_Security'	=> 'Security',
'AdminMenu_Screening'	=> 'Screening',
	'AdminMenu_BadBevaviour'	=> 'Basic Settings',
	'AdminMenu_BadBevaviour_Ext'	=> 'Extended Settings',
	'AdminMenu_BadBevaviour_Rev'	=> 'Reverse Proxy',
'AdminMenu_Layout' => 'Layout',
'AdminMenu_Themes' => 'Themes',
'AdminMenu_Icons' => 'Icons',
'AdminMenu_Language' => 'Language',

'AdminMenu_Members' => 'Members',
	'AdminMenu_Search' => 'Search',
	'AdminMenu_Pending' => 'Pending',
	'AdminMenu_Groups' => 'Groups',
	'AdminMenu_Profile' => 'Profile',
	'AdminMenu_Team' => 'Team',
		'AdminMenu_Team_Group4' => 'Lector',
		'AdminMenu_Team_Group5' => 'Moderator',
		'AdminMenu_Team_Group6' => 'Super Moderator',
		'AdminMenu_Team_Group7' => 'Administrator',

'AdminMenu_Archive' => 'Archive',
	'AdminMenu_Intro' => 'Intro',
'AdminMenu_Submission' => 'Submissions',
	'AdminMenu_Stories' => 'Stories',
	'AdminMenu_Images' => 'Cover art',
	'AdminMenu_Reviews' => 'Reviews',
'AdminMenu_Characters' => 'Characters',
'AdminMenu_Tags' => 'Tags',
	'AdminMenu_Edit' => 'Edit',
	'AdminMenu_Taggroups' => 'Groups',
	'AdminMenu_Tagcloud' => 'Cloud',
'AdminMenu_Categories' => 'Categories',
'ACP_Categories_Success_Deleted'	=> 'Category "{0}" successfully deleted!',
'ACP_Categories_Error_notEmpty'		=> 'Could not delete category "{0}", because it is not empty!',
'ACP_Categories_Error_DBError'		=> 'Could not delete category "{0}", database error occured!',
'ACP_Categories_Error_badID'		=> 'Could not delete category, ID not found in database!',

'ACP_Tags'				=> 'Tags',
'ACP_Character'			=> 'Character',
'ACP_Biography'			=> 'Biography',
'ACP_Contest'			=> 'Contest',
'ACP_Category'			=> 'Category',
'ACP_TagName'			=> 'Tag name',
'ACP_TagLabel'			=> 'Tag label',
'ACP_TagDescription'	=> 'Tag description',
'ACP_TagLabel_Advice'	=> 'Only change when required',
'ACP_TagGroupLabel'		=> 'Tag group label',
'ACP_TagGroup'			=> 'Tag group',
'ACP_TagGroups'			=> 'Tag groups',

// Admin - Archive - Featured
'AdminMenu_Featured' => 'Featured',
	'AdminMenu_Future' => 'Future',
	'AdminMenu_Current' => 'Current',
	'AdminMenu_Past' => 'Past',
'Feat_Current'			=> 'Currently featured',
'Feat_Past'				=> 'Past feature',
'Feat_Future'			=> 'Future feature (not active yet, merely a note)',
'SelectedBy'			=> 'Selected by',

// Admin - Archive - Contest
'AdminMenu_Contests'	=> 'Contests',
'ContestDescription'	=> 'Contest description',

'AdminMenu_Stories' => 'Stories',
'AdminMenu_Pending' => 'Pending',
'AdminMenu_Edit' => 'Edit',
'AdminMenu_Add' => 'Add',

'CannotDeleteUsedElement'	=> 'Cannot delete an element that is currently being used.',
'ElementDeleted'	=> 'Element deleted',
'FailedDelete'		=> 'Failed to delete element',


// Config explain
// archive_general
	'CFG_stories_per_page'		=> 'Stories per page in the Archive.',
	'CFG_stories_recent'		=> 'Days for recent stories',
	'CFG_stories_default_order'	=> 'Default sorting for stories',
	'CFG_story_toc_default'		=> 'Show to table of contents by default for stories with multiple chapters.',
	'CFG_epub_domain'			=> 'ePub Domain@SMALL@Used to calculate your epub UUID v5. Leave blank for default (Archive URL)',
// archive_images
	'CFG_images_allowed' 		=> 'Allow posting of story images (cover art)',
	'CFG_images_height'			=> 'Allowed image height.',
	'CFG_images_width'			=> 'Allowed image width.',
// archive_intro
	'CFG_story_intro_items' 	=> 'Stories to show on the archive entry page.',
	'CFG_story_intro_order' 	=> 'Order in which stories appear on the archive entry page.',
// archive_reviews
	'CFG_allow_reviews'			=> 'Allow reviews',
	'CFG_allow_guest_reviews'	=> 'Allow guests to write reviews',
	'CFG_allow_review_delete'	=> 'Authors can delete reviews',
	'CFG_allow_rateonly'		=> 'Allow ratings without review (including kudos)',
// archive_submit
	'CFG_author_self' 			=> 'Every member can post stories@SMALL@If set to no, members must be added to group Authors to allow them to post stories',
	'CFG_story_validation'	 	=> 'Stories require validation@SMALL@This does not apply to trusted authors.',
	'CFG_stories_min_words'		=> 'Minimum amount of words for a chapter',
	'CFG_stories_max_words'		=> 'Maximum amount of words for a chapter@SMALL@(0 = unlimited)',
	'CFG_advanced_editor' 		=> 'Allow use of graphical editor',
	'CFG_allow_co_author' 		=> 'Allow addition of other authors to stories',
	'CFG_stories_min_tags'		=> 'Minimum amount of tags required',
	'CFG_allow_series'	 		=> 'Allow authors to create series@SMALL@Member series are now collections',
	'CFG_allow_roundrobin'	 	=> 'Allow round robins',
// archive_tags_cloud
	'CFG_tagcloud_basesize'		=> 'Base size in percent relative to normal font size.',
	'CFG_tagcloud_elements'		=> 'Maximum number of elements in the tag cloud@SMALL@Elements are ordered by count.',
	'CFG_tagcloud_minimum_elements'	=>  'Minimum amount of elements required to show tag cloud@SMALL@0 = always show',
	'CFG_tagcloud_spread'		=> 'Maximum size spread:@SMALL@spread*100 is the maximum percentage for the most used tag.<br>2.5 would convert to 250%.<br>(Realistic values are somewhere between 3 and 5)',
// bad_behaviour
	'CFG_bb2_enabled' 			=> 'Screen access<br/><a href="http://bad-behavior.ioerror.us/support/configuration/" target="_blank">Bad Behaviour manual</a>@SMALL@(default <b>"{{@LN__yes}}"</b>)',
	'CFG_bb2__display_stats' 	=> 'Display Statistics@SMALL@(default <b>"{{@LN__yes}}"</b>) (this causes extra load, turn off to save power)',
	'CFG_bb2__logging' 			=> 'Logging@SMALL@(default <b>"{{@LN__yes}}"</b>)',
	'CFG_bb2__strict'	 		=> 'Strict Mode@SMALL@(default <b>"{{@LN__no}}"</b>)',
// bad_behaviour_ext
	'CFG_bb2__verbose' 			=> 'Verbose Logging@SMALL@(default <b>"{{@LN__no}}"</b>)',
	'CFG_bb2__offsite_forms' 	=> 'Allow off-site Forms@SMALL@(default <b>"{{@LN__no}}"</b>)',
	'CFG_bb2__eu_cookie' 		=> 'EU Cookie@SMALL@(default <b>"{{@LN__no}}"</b>)',
	'CFG_bb2__httpbl_key' 		=> 'http:BL API Key@SMALL@Screen requests through Project Honey Pot.<br/>Leave empty to disable.',
	'CFG_bb2__httpbl_threat' 	=> 'http:BL Threat Level@SMALL@(default <b>"25"</b>)',
	'CFG_bb2__httpbl_maxage' 	=> 'http:BL Maximum Age@SMALL@(default <b>"30"</b>)',
	'CFG_bb2__reverse_proxy' 	=> 'Reverse Proxy@SMALL@(default <b>"{{@LN__no}}"</b>)',
	'CFG_bb2__reverse_proxy_header' 	=> 'Reverse Proxy Header@SMALL@(default "X-Forwarded-For")<br/>Only required when using reverse proxy!',
	'CFG_bb2__reverse_proxy_addresses'	=> 'Reverse Proxy Addresses@SMALL@(no default)<br/>Only required when using reverse proxy!',
// members_general
	'CFG_agestatement'		=>	'Have members set their age to show rating warnings',
// settings_datetime
	'CFG_date_format_short'		=> 'Default short date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)',
	'CFG_date_format_long'		=> 'Default long date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)',
	'CFG_time_format'			=> 'Time format presets.',
	'CFG_date_format'			=> 'Date format presets.',
	'CFG_monday_first_day'		=> 'Weeks in calendar start with ...',
// settings_language
	'CFG_language_forced'		=> 'Disable custom language selection:@SMALL@Default is <b>no</b>',
	'CFG_language_available'	=> 'List all languages that are available to common members.',
// settings_layout
	'CFG_layout_forced'			=> 'Disable custom layout selection:@SMALL@Default is <b>no</b>',
	'CFG_layout_available'		=> '',
// settings_general
	'CFG_page_title'			=> 'Website title',
	'CFG_page_mail'				=> 'Webmaster e-mail address',
	'CFG_page_slogan'			=> 'Site slogan',
	'CFG_page_title_add'		=> 'Show page path or slogan in title',
	'CFG_page_title_reverse'	=> 'Reverse sort order of page title elements.@SMALL@(Default is <b>no</b>)',
	'CFG_page_title_separator'	=> 'Separator for page title elements',
	'CFG_adjacent_paginations' 	=> 'Contiguous page links to display@SMALL@"1" to display: 1 ... 4 [5] 6 ... 9<br/>"2" to display: 1 ... 3 4 [5] 6 7 ... 9<br>"0" to display all links',
	'CFG_shoutbox_entries'		=> 'Number of shoutbox items to display',
	'CFG_shoutbox_guest'		=> 'Allow guest posts in shoutbox',
	'CFG_allow_comment_news'	=> 'Allow news comments',
	'CFG_allow_guest_comment_news'	=> 'Allow guest news comments',
// settings_report
	'CFG_server_report'				=> 'Server reports environment stats.@SMALL@Support development by keeping this enabled!',
	'CFG_server_report_anon'		=> 'Anonymize stat report.@SMALL@Enable to anonymize the data.',
	'CFG_server_report_publish'		=> 'List server (not available yet)@SMALL@Just a placeholder yet.',
// settings_registration
	'CFG_allow_registration'		=> 'Allow registration?',
	'CFG_reg_require_email'			=> 'User must activate their account via eMail link.',
	'CFG_reg_require_mod'			=> 'User registrations require moderation.',
	'CFG_reg_min_username'			=> 'Minimum characters for usernames',
	'CFG_reg_min_password'			=> 'Minimum characters for passwords',
	'CFG_reg_password_complexity'	=> 'Password complexity:@SMALL@see wiki',
	'CFG_reg_use_captcha'			=> 'Select CAPTCHA to be used@SMALL@Configure under <a href="{{@BASE}}/adminCP/settings/security">Settings - Security</a>',
// settings_registration_sfs
	'CFG_reg_sfs_usage'				=> 'Use the "Stop Forum Spam" Service.@SMALL@<a href="http://www.stopforumspam.com/faq" target="_blank">FAQ @ http://www.stopforumspam.com</a>',
	'CFG_reg_sfs_check_ip'			=> 'Check IP',
	'CFG_reg_sfs_check_mail'		=> 'Check mail address',
	'CFG_reg_sfs_check_username'	=> 'Check user name',
	'CFG_reg_sfs_check_advice'		=> 'You may turn off user name checking if you encounter false positives.<br>Turning off IP and mail check is not advised, however.',
	'CFG_reg_sfs_failsafe'			=> 'How to behave if the SFS Service cannot be reached upon registration@SMALL@Default is to hold.',
	'CFG_reg_sfs_explain_api'		=> '__AdminRegExplainSFSApi',
	'CFG_reg_sfs_api_key'			=> 'Your API key (optional)',
// settings_mail
	'CFG_mail_notifications'		=> 'Members can opt-in to receive mail notifications.',
	'CFG_smtp_advice'				=> 'Leave SMTP server fields empty to send through PHP and sendmail.@SMALL@<a href="http://efiction.org/wiki/Server#Working_settings_for_common_mail_providers" target="_blank">Documentation in the wiki. {ICON:external-link}</a>',
	'CFG_smtp_server'				=> 'SMTP server@SMALL@See WIKI for GMail!',
	'CFG_smtp_scheme' 				=> 'SMTP security scheme',
	'CFG_smtp_port'					=> 'Port number (if not using default)',
	'CFG_smtp_username'				=> 'SMTP user name',
	'CFG_smtp_password'				=> 'SMTP password',
// settings_maintenance
	'CFG_chapter_data_location' 	=> 'Chapter storage (Database Server or local file storage)@SMALL@Read-only - Local file is being handled by SQLite',
	'CFG_debug'						=> 'Debug level',
	'CFG_maintenance'				=> 'Archive closed for maintenance',
	'CFG_logging'					=> 'Log actions',

// Story view'
'AuthorCounted' => '{0, plural,'.
	'one	{Author},'.
	'other	{Authors}
}',
'by' => 'by',
'translatedBy'	=> 'translated by',
'Stories'				=> 'Stories',
'NewStories'			=> 'New Stories',
'noNewStory'			=> 'No new Stories',
'RandomStory' => '{0, plural,'.
	'one	{Random Story},'.
	'other	{Random Stories} }',
'noRandomStory'	=> 'No random stories yet',
'RandomSpotlight' => 'Random Spotlight',
'FeaturedStory' => '{0, plural,'.
	'one	{Featured Story},'.
	'other	{Featured Stories} }',
'noFeaturedStory'	=> 'No featured stories yet',
'RecommendedStory'	=> '{0, plural,'.
	'one	{Recommended Story},'.
	'other	{Recommended Stories} }',
'noRecommendedStory'	=> 'No recommended stories yet',
'Notification'	=> 'Notification',
'Visibility'	=> 'Visibility',
'VisPrivate'	=> 'Private',
'VisFriends'	=> 'Friends',
'VisMembers'	=> 'Members',
'VisEverybody'	=> 'Everybody',

'NoElements'	=> 'There are no elements in this view',

'CheckToDelete'	=> 'Check this box to delele',

'TOC'	=> 'Table of content',
'NoTags' => 'No tags defined',
'Published' => 'Published',
'Updated'	=> 'Last update',
'Chapter' => 'Chapter',
'Chapters' => 'Chapters',
'Words' => 'Words',
'WIP' => 'Work in progress',
'Completed' => 'Completed',
'Characters' => 'Characters',
'Clicks' => 'Clicks',
'Author_Notes' => 'Author notes',

'SearchUsername'=> 'Search in usernames',
'SearchUserAll' => 'Search in all user fields',
'Tagcloud' => 'Tagcloud',
'noTagcloud' => 'No tag cloud',


// Feedback
'Feedback_Not_Logged_In' => 'You need to be logged in to write a review or comment',
'Button_reviewStory'	=> 'Write review for the story',
'Button_reviewChapter'	=> 'Write review for this chapter',
'Button_writeComment'	=> 'Write comment',

// Archive News
'News_Headline'	=> 'Headline',
'News_DateTimeExplain' => 'News entries will become visible at the time above',
'News_Article'	=> 'Article',
'News_Box' => 'Archive News',
'News_Archive' => 'News Archive',
'News_writtenby' => 'written by',
'Reply' => 'Reply',
'Comment' => 'Comment',
'CommentsC' => '{0, plural,'.
	'zero	{No comments yet},'.
	'one	{One comment},'.
'other	{# comments} }',


'Status_Changes' => '{0, plural,'.
	'zero	{No changes.},'.
	'one	{One element changed.},'.
	'other	{# elements changed.}
}',
'Status_Errors' => '{0, plural,'.
	'zero	{No errors.},'.
	'one	{An error occurred while saving data.},'.
	'other	{# errors occurred while saving data.}
}',

'ReviewHeadline' => 'On {1} at {2}, {0} wrote a review for chapter {3}',
/*
'ReviewHeadline' => '{3, plural,'.
	'zero  {On {1} at {2}, {0} wrote a review},'.
	'other {On {1} at {2}, {0} wrote a review for chapter {3}}
}',
*/
'ReplyHeadline_noDate' => '{0} replied:',
'ReplyHeadline' => 'On {1} at {2}, {0} replied:',
'ReviewCommentsLink' => '{0, plural,'.
	'one	{One comment.},'.
	'other	{# comments.}
}',


);

?>

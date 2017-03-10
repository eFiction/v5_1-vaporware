<?php
@define('__transLocale', 'de_DE');

return array(
// Allgemeines
'UserCP'	=> 'User Panel',
'AdminCP'	=> 'Admin Panel',
'yes'		=>	'ja',
'Yes'		=>	'Ja',
'no'		=>	'nein',
'No'		=>	'Nein',
'Cancel'	=>	'Abbrechen',
'Submit'	=>	'Absenden',
'Return'	=>	'Zurück',
'CaptchaMismatch'	=>	'Captcha stimmt nicht überein',
'GuestURL'		=>	'Gäste dürfen keine URLs posten',
'GuestNameEmpty' => 'Gäste müssen einen Namen angeben',
'MessageEmpty'	=>	'Keine Nachricht eingegeben',
'CommentEmpty'	=>	'Kein Kommentar eingegeben',
'CannotSave'	=>	'Daten konnten nicht gespeichert werden, bitte erneut versuchen',
'FormErrors'	=> '{0, plural,'.
							'zero	{Keine Fehler},'.
							'one	{Beim Verarbeiten des Formulars ist ein Fehler aufgetreten},'.
							'other	{Beim Verarbeiten des Formulars sind Fehler aufgetreten} }',
'UnknowError'	=> 'There was an unknown error',
'MainAuthor'	=> 'Autor',
'MainAuthors'	=> 'Autoren',
'SupAuthor'		=> 'Nebenautor',
'SupAuthors'	=> 'Nebenautoren',
'TokenInputHint' => 'Suchwort eingeben',
// 'StoryNotes'			=>
'StoryNotesExplained'	=>	'Platz für Widmungen und Anmerkungen',
// 'StorySummary'			=>
// 'StorySummaryExplained'	=>
// 'ChapterText'			=>
// 'ChapterTextExplained' =>

// Login
'Login'			=> 'Anmelden',
'Logout'		=> 'Abmelden',
'Username'		=> 'Benutzername',
'Password'		=> 'Passwort',
'Email'			=> 'E-Mail',
'Guest'			=> 'Gast',
'ForgotPW'		=> 'Passwort vergessen',
'ResetPW'		=> 'Passwort zurücksetzen',
'ResetPWMail'	=> 'Falls ein Benutzer mit diesem Namen oder dieser E-Mail Adresse existiert, wurde eine E-Mail mit weiteren Anweisungen verschickt',
'ChangePW'		=> 'Passwort ändern',
'PWRecovery'	=> 'Passwort Wiederherstellung',
'Login_NoMatch'	=> 'Anmeldung nicht möglich, Benutzername oder Passwort ungültig!',
'Login_NoData'	=> 'Anmeldung nicht möglich, keine Daten!',

// Registrierung
'Registration'					=> 'Registrierung',
'RegisterNow'					=> 'Jetzt registrieren',
'Registration_AcceptTOS'		=> 'I accept these terms!',
'Registration_AcceptTOS_Error'	=> 'You have to accept the terms of use',
'Registration_UsernameEmpty'	=> 'No login name provided!',
'Registration_UsernameTaken'	=> 'Benutzername wird bereits verwendet.',
'Registration_EmailEmpty'		=> 'No E-Mail adress provided!',
'Registration_EmailTaken'		=> 'E-Mail wird bereits verwendet.',
'Registration_AlreadyMember'	=> 'Ein Benutzer mit diesen Daten existiert bereits.',
'Registration_PasswordRepeat'	=> 'Passwort wiederholen',
'Registration_PasswordTwice'	=> 'Passwort bitte zwei mal eingeben',
'Registration_PasswordMismatch'	=> 'Die eingegebenen Passwörter stimmen nicht miteinander überein',
'Registration_PasswordCriteria'	=> 'Dein gewünschtes Passwort erfüllt nicht die Voraussetzungen',


'Sort_Date'	=> 'Datum',
'Sort_ID'	=> 'ID',


'CookieText'	=> 'Diese Website benutzt Cookies, um das Krümelmonster zu füttern.',
'CookieInfo'	=> 'Mehr über die echten Gründe.',
'CookieAccept'	=> 'Akzeptiert!',

'PM_Inbox'				=> 'Posteingang',
'PM_Outbox'				=> 'Postausgang',
'PM_Write'				=> 'Verfassen',
'PM_Outbox_Items'		=> '{0, plural,'.
							'zero	{Your Outbox is empty!},'.
							'one	{One message:},'.
							'other	{# messages:} }',
'PM_Outbox_empty'		=> 'Dein Postausgang ist leer',
'PM_Inbox_Items' 		=> '{0, plural,'.
							'zero	{Your Inbox is empty!},'.
							'one	{One message:},'.
							'other	{# messages:} }',
'PM_Inbox_empty' 		=> 'Dein Posteingang ist leer',
'PM_Subject'			=> 'Betreff',
'PM_Sender'				=> 'Absender',
'PM_Sent'				=> 'Gesendet',
'PM_Received'			=> 'Empfangen',
'PM_Recipient'			=> 'Empfänger',
'PM_question_Delete'	=> 'Nachricht löschen?',
'PM_confirm_Delete'		=> 'Nachricht wird gelöscht, sicher?',
'PM_unread'				=> 'ungelesen',
'PM_ReplySubject'		=> 'Aw:',
'PM_ReplyMessageHeader'	=> 'Am {0} schrieb {1}:',
'PM_WritePM'			=> 'Nachricht verfassen',
'PM_ReadPM'				=> 'Nachricht lesen',

// using php strftime
'Month_Calendar' => '{0,date,custom,%B %Y}',
'Weekday' => '{0,date,custom,%A}',

// User elements
'UserField_Type1'	=> 'URL',			// _FIELDURL
'UserField_Type2'	=> 'Auswahlfeld',	// _FIELDSELECT
'UserField_Type3'	=> 'Ja/Nein',		// _FIELDYESNO
'UserField_Type4'	=> 'ID mit URL',	// _FIELDIDURL
'UserField_Type5'	=> 'Custom Code',	// _FIELDCUSTOM
'UserField_Type6'	=> 'Text',			// _TEXT

'Author'	=> 'Author',
'Authors'	=> 'Authors',
'Recomm'	=> 'Recommendation',
'Recomms'	=> 'Recommendations',
'RecommBy'	=> 'Empfohlen von',
'Series'	=> 'Serie',
'Story'		=> 'Geschichte',
'Stories'	=> 'Geschichten',

// UserCP elements
'ChangeTo'	=> 'Ändern in',
'UserMenu_Profile' => 'Profil',
'UserMenu_Message' => 'Nachrichten',
'UserMenu_PMInbox' => 'Posteingang',
'UserMenu_PMOutbox' => 'Postausgang',
'UserMenu_PMWrite' => 'Verfassen',
	'MSG_deletedSuccess'	=> 'Nachricht gelöscht',
	'MSG_deleteRead'	=> 'Löschen nicht möglich, Nachricht wurde bereits gelesen',
	'MSG_deleteNotFound' => 'Löschen nicht möglich, Nachricht wurde nicht gefunden',
	'MSG_deleteNoAccess' => 'Du kannst auf diese Nachricht nicht zugreifen',

'UserMenu_Authoring' => 'Authoring',
	'Authoring_Finished' => '{0, plural,'.
		'other	{Abgeschlossen (#)}}',
	'Authoring_Unfinished' => '{0, plural,'.
		'other	{In Arbeit (#)}}',
	'Authoring_Drafts' => '{0, plural,'.
		'other	{Entwürfe (#)}}',
'UserMenu_MyLibrary' => 'My Library',
	'Library_Favourites' => '{0, plural,'.
		'other	{Favoriten (#)}
	}',
	'AddFavourite'	=>	'Add favourite',
	'Library_Bookmarks' => '{0, plural,'.
		'other	{Lesezeichen (#)}
	}',
	'AddBookmark'	=> 'Add bookmark',
	'Library_Recommendations' => '{0, plural,'.
		'other	{Empfehlungen (#)}
	}',
'UserMenu_Feedback' => 'Feedback',
'UserMenu_Reviews' => 'Bewertungen',
	'UserMenu_ReviewsWritten'	=> '{0, plural,'. 'other {Bewertungen geschrieben (#)} }',
	'UserMenu_ReviewsReceived'	=> '{0, plural,'. 'other {Bewertungen erhalten (#)} }', 
	'UserMenu_CommentsWritten'	=> '{0, plural,'. 'other {Kommentare geschrieben (#)} }',
	'UserMenu_CommentsReceived'	=> '{0, plural,'. 'other {Kommentare erhalten (#)} }', 
	'UserMenu_Shoutbox'			=> '{0, plural,'. 'other {Shoutbox (#)} }', 
'UserMenu_Settings' => 'Einstellungen',
//	'UserMenu_Preferences' => 

'UserMenu_AddStory' => 'Hinzufügen',
'StoryTitle'	=>	'Titel der Geschichte',
'ChapterTitle'	=>	'Titel des Kapitels',
'AddChapter'	=> 'Kapitel hinzufügen',
'EditHeader'	=> 'Kopfdaten bearbeiten',
'DragdropSort'	=> 'Ziehen um zu sortieren',
'SwitchPlainHTML'	=> 'Zum einfachen Modus wechseln',
'SwitchVisual'		=> 'Zum Editor wechseln',
'UCP_statusValidated' => 'Freigabe',
	'UCP_statusValidated_closed'	=> 'Geschlossen',
	'UCP_statusValidated_moderationStatic'	=> 'Offen, Autor arbeitet',
	'UCP_statusValidated_moderationPending'	=> 'Offen, Autor fertig',
	'UCP_statusValidated_validated'	=> 		   'Bestätigt',
'UCP_statusValidatedReason' => 'Details zur Freigabe',
	'UCP_statusValidated_none'	=> 'Kein Grund angegeben',
	'UCP_statusValidated_user'		=> 'Durch Autor',
	'UCP_statusValidated_moderator'	=> 'Durch Moderator',
	'UCP_statusValidated_admin'		=> 'Durch Admin',
'UCP_statusCompleted'	=> 'Status',
	'UCP_statusCompleted_deleted'	=> 'Gelöscht',
	'UCP_statusCompleted_draft'		=> 'Entwurf',
	'UCP_statusCompleted_wip'		=> 'Unvollendet',
	'UCP_statusCompleted_completed'	=> 'Abgeschlossen',
'UserMenu_Curator' => 'Betreuer',
'UCP_ExplainMainAuthor'	=> 'Alle (Haupt)Autoren können die Geschichte bearbeiten',
'UCP_ExplainSupAuthor'	=> 'Nebenautoren werden in der Geschichte aufgeführt, können sie aber nicht bearbeiten',

// AdminCP Home elements
'AdminMenu_General' => 'Allgemeine Einstellungen',

'AdminMenu_Home' => 'Home',
'AdminMenu_Manual' => 'Handbuch',
'AdminMenu_CustomPages' => 'Custom Pages',
'AdminMenu_News' => 'News',
'AdminMenu_Modules' => 'Module',
//'AdminMenu_Logs' => 'Logs',
	// 'AdminMenu_Logs_AM' => 'Admin Maintenance',
	// 'AdminMenu_Logs_DL' => 'Deletions',
	// 'AdminMenu_Logs_EB' => 'Edit Member',
	// 'AdminMenu_Logs_ED' => 'Edit Story',
	// 'AdminMenu_Logs_LP' => 'Lost Password',
	// 'AdminMenu_Logs_RE' => 'Reviews',
	// 'AdminMenu_Logs_RG' => 'Registration',
	// 'AdminMenu_Logs_RF' => 'Registration failure',
	// 'AdminMenu_Logs_VS' => 'Validations',
'AdminMenu_Shoutbox' => 'Shoutbox',

'AdminMenu_Settings' => 'Einstellungen',
'AdminMenu_Server' => 'Server',
	'AdminMenu_DateTime'	=> 'Datum und Uhrzeit',
	// 'AdminMenu_Mail'		=> 'Mail and mail server',
	// 'AdminMenu_Maintenance'	=> 'Maintenance',
'AdminMenu_Registration' => 'Registrierung',
	'AdminMenu_AntiSpam'	=> 'Spam-Schutz',
'AdminMenu_Security'	=> 'Sicherheit',
'AdminMenu_Screening'	=> 'Screening',
	'AdminMenu_BadBevaviour'	=> 'Grundeinstellungen',
	'AdminMenu_BadBevaviour_Ext'	=> 'Erweiterte Einstellungen',
	'AdminMenu_BadBevaviour_Rev'	=> 'Reverse Proxy',
'AdminMenu_Layout' => 'Layout',
'AdminMenu_Themes' => 'Themes',
'AdminMenu_Icons' => 'Icons',
'AdminMenu_Language' => 'Sprachen',

'AdminMenu_Members' => 'Members',
	'AdminMenu_Search' => 'Suchen',
	'AdminMenu_Pending' => 'Pending',
	'AdminMenu_Groups' => 'Gruppen',
	'AdminMenu_Profile' => 'Profil',
	'AdminMenu_Team' => 'Team',

'AdminMenu_Archive' => 'Archive',
	'AdminMenu_Intro' => 'Intro',
// 'AdminMenu_Submission' => 'Submissions',
	// 'AdminMenu_Stories' => 'Stories',
	// 'AdminMenu_Images' => 'Cover art',
	// 'AdminMenu_Reviews' => 'Reviews',
'AdminMenu_Featured' => 'Featured',
	'AdminMenu_Future' => 'Zukünftige',
	'AdminMenu_Current' => 'Current',
	'AdminMenu_Past' => 'Past',
'AdminMenu_Characters' => 'Characters',
'AdminMenu_Tags' => 'Tags',
	'AdminMenu_Edit' => 'Edit',
	'AdminMenu_Taggroups' => 'Groups',
	'AdminMenu_Tagcloud' => 'Cloud',
'AdminMenu_Categories' => 'Categories',
'ACP_Categories_Success_Deleted' => 'Kategories "{0}" wurde erfolgreich gelöscht!',
'ACP_Categories_Error_notEmpty' => 'Kann Kategories "{0}" nicht löschen, da sie nicht leer ist!',
'ACP_Categories_Error_DBError' => 'Konnte Kategories "{0}" wegen Datenbankfehler nicht löschen!',
'ACP_Categories_Error_badID' => 'Kann die Kategories nicht löschen, keine gültige ID gefunden!',

// 'ACP_Tags'				=> 'Tags',
// 'ACP_TagName'			=> 'Tag name',
// 'ACP_TagLabel'			=> 'Tag label',
// 'ACP_TagDescription'	=> 'Tag description',
// 'ACP_TagLabel_Advice'	=> 'Only change when required',
// 'ACP_TagGroupLabel'		=> 'Tag group label',
// 'ACP_TagGroup'			=> 'Tag group',
// 'ACP_TagGroups'			=> 'Tag groups',

'AdminMenu_Stories' => 'Stories',
'AdminMenu_Pending' => 'Pending',
'AdminMenu_Edit' => 'Edit',
'AdminMenu_Add' => 'Add',


'Welcome' => 'Willkommen',
'Shoutbox' => 'Shoutbox',

// Config explain
// archive_general
	'CFG_stories_per_page'		=> 'Stories je Seite im Archiv.',
	// 'CFG_stories_recent'		=> 'Days for recent stories',
	'CFG_stories_default_order'	=> 'Standardsortierung für Stories',
	// 'CFG_story_toc_default'		=> 'Show to table of contents by default for stories with multiple chapters.',
	// 'CFG_epub_domain'			=> 'ePub Domain@SMALL@Used to calculate your epub UUID v5. Leave blank for default (Archive URL)',
// archive_images
	// 'CFG_images_allowed' 		=> 'Allow posting of story images (cover art)',
	// 'CFG_images_height'			=> 'Allowed image height.',
	// 'CFG_images_width'			=> 'Allowed image width.',
// archive_intro
	'CFG_story_intro_items' => 'Anzahl der Stories auf der Archiv Startseite.',
	'CFG_story_intro_order' => 'Sortierung der Stories auf der Archiv Startseite.',
// archive_reviews
	// 'CFG_allow_reviews'			=> 'Allow reviews',
	// 'CFG_allow_guest_reviews'	=> 'Allow guests to write reviews',
	// 'CFG_allow_review_delete'	=> 'Authors can delete reviews',
	// 'CFG_allow_rateonly'		=> 'Allow ratings without review (including kudos)',
// archive_submit
	// 'CFG_author_self' 			=> 'Every member can post stories@SMALL@If set to no, members must be added to group Authors to allow them to post stories',
	// 'CFG_story_validation'	 	=> 'Stories require validation@SMALL@This does not apply to trusted authors.',
	// 'CFG_stories_min_words'		=> 'Minimum amount of words for a chapter',
	// 'CFG_stories_max_words'		=> 'Maximum amount of words for a chapter@SMALL@(0 = unlimited)',
	// 'CFG_advanced_editor' 		=> 'Allow use of graphical editor',
	// 'CFG_allow_co_author' 		=> 'Allow addition of other authors to stories',
	// 'CFG_stories_min_tags'		=> 'Minimum amount of tags required',
	// 'CFG_allow_series'	 		=> 'Allow authors to create series@SMALL@Member series are now collections',
	// 'CFG_allow_roundrobin'	 	=> 'Allow roundrobins',
// archive_tags_cloud
	// 'CFG_tagcloud_basesize'		=> 'Base size in percent relative to normal font size.',
	// 'CFG_tagcloud_elements'		=> 'Maximum number of elements in the tag cloud@SMALL@Elements are ordered by count.',
	// 'CFG_tagcloud_minimum_elements'	=>  'Minimum amount of elements required to show tag cloud@SMALL@0 = always show',
	// 'CFG_tagcloud_spread'		=> 'Maximum size spread:@SMALL@spread*100 is the maximum percentage for the most used tag.<br>2.5 would convert to 250%.<br>(Realistic values are somewhere between 3 and 5)',
// bad_behaviour
	// 'CFG_bb2_enabled' 			=> 'Screen access\n<a href="http://bad-behavior.ioerror.us/support/configuration/" target="_blank">Bad Behaviour manual</a>@SMALL@(default <b>"{{@LN__yes}}"</b>)',
	// 'CFG_bb2__display_stats' 	=> 'Display Statistics@SMALL@(default <b>"{{@LN__yes}}"</b>) (this causes extra load, turn off to save power)',
	// 'CFG_bb2__logging' 			=> 'Logging@SMALL@(default <b>"{{@LN__yes}}"</b>)',
	// 'CFG_bb2__strict'	 		=> 'Strict Mode@SMALL@(default <b>"{{@LN__no}}"</b>)',
// bad_behaviour_ext
	// 'CFG_bb2__verbose' 			=> 'Verbose Logging@SMALL@(default <b>"{{@LN__no}}"</b>)',
	// 'CFG_bb2__offsite_forms' 	=> 'Allow Offsite Forms@SMALL@(default <b>"{{@LN__no}}"</b>)',
	// 'CFG_bb2__eu_cookie' 		=> 'EU Cookie@SMALL@(default <b>"{{@LN__no}}"</b>)',
	// 'CFG_bb2__httpbl_key' 		=> 'http:BL API Key@SMALL@Screen requests through Project Honey Pot.\r\nLeave empty to disable.',
	// 'CFG_bb2__httpbl_threat' 	=> 'http:BL Threat Level@SMALL@(default <b>"25"</b>)',
	// 'CFG_bb2__httpbl_maxage' 	=> 'http:BL Maximum Age@SMALL@(default <b>"30"</b>)',
	// 'CFG_bb2__reverse_proxy' 	=> 'Reverse Proxy@SMALL@(default <b>"{{@LN__no}}"</b>)',
	// 'CFG_bb2__reverse_proxy_header' 	=> 'Reverse Proxy Header@SMALL@(default "X-Forwarded-For")\r\nOnly required when using reverse proxy!',
	// 'CFG_bb2__reverse_proxy_addresses'	=> 'Reverse Proxy Addresses@SMALL@(no default)\r\nOnly required when using reverse proxy!',
// members_general
	// 'CFG_agestatement'		=>	'Have members set their age to show rating warnings',
// settings_datetime
	// 'CFG_date_format_short'		=> 'Default short date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)',
	// 'CFG_date_format_long'		=> 'Default long date.@SMALL@(See <a href="http://php.net/manual/en/function.date.php" target="_blank">php.net documentation</a> for syntax)',
	// 'CFG_time_format'			=> 'Default time format.',
	// 'CFG_monday_first_day'		=> 'Weeks in calendar start with ...',
// settings_language
	// 'CFG_language_forced'		=> 'Disable custom language selection:@SMALL@Default is <b>no</b>',
	// 'CFG_language_available'	=> 'List all languages that are available to common members.',
// settings_layout
	// 'CFG_layout_forced'			=> 'Disable custom layout selection:@SMALL@Default is <b>no</b>',
	// 'CFG_layout_available'		=> '',
// settings_general
	// 'CFG_page_title'			=> 'Website title',
	// 'CFG_page_mail'				=> 'Webmaster e-mail address',
	// 'CFG_page_slogan'			=> 'Site slogan',
	// 'CFG_page_title_add'		=> 'Show page path or slogan in title',
	// 'CFG_page_title_reverse'	=> 'Reverse sort order of page title elements.@SMALL@(Default is <b>no</b>)',
	// 'CFG_page_title_separator'	=> 'Separator for page title elements',
	// 'CFG_adjacent_paginations' 	=> 'Contiguous page links to display@SMALL@"1" to display: 1 ... 4 [5] 6 ... 9<br>\n"2" to display: 1 ... 3 4 [5] 6 7 ... 9<br>"0" to display all links',
	// 'CFG_shoutbox_entries'		=> 'Number of shoutbox items to display',
	// 'CFG_shoutbox_guest'		=> 'Allow guest posts in shoutbox',
	// 'CFG_allow_comment_news'	=> 'Allow news comments',
	// 'CFG_allow_guest_comment_news'	=> 'Allow guest news comments',
// settings_registration
	// 'CFG_allow_registration'		=> 'Allow registration?',
	// 'CFG_reg_require_email'			=> 'User must activate their account via eMail link.',
	// 'CFG_reg_require_mod'			=> 'User registrations require moderation.',
	// 'CFG_reg_min_username'			=> 'Minimum characters for usernames',
	// 'CFG_reg_min_password'			=> 'Minimum characters for passwords',
	// 'CFG_reg_password_complexity'	=> 'Password complexity:@SMALL@see wiki',
	// 'CFG_reg_use_captcha'			=> 'Select CAPTCHA to be used@SMALL@Configure under <a href=''{{@BASE}}/adminCP/settings/security''>Settings - Security</a>',
// settings_registration_sfs
	// 'CFG_reg_sfs_usage'				=> 'Use the "Stop Forumspam" Service.@SMALL@<a href="http://www.stopforumspam.com/faq" target="_blank">FAQ @ http://www.stopforumspam.com</a>',
	// 'CFG_reg_sfs_check_ip'			=> 'Check IP',
	// 'CFG_reg_sfs_check_mail'		=> 'Check mail address',
	// 'CFG_reg_sfs_check_username'	=> 'Check username',
	// 'CFG_reg_sfs_check_advice'		=> 'You may turn off username checking if you encounter false positives.<br>Turning off IP and mail check is not advised, however.',
	// 'CFG_reg_sfs_failsafe'			=> 'How to behave if the SFS Service cannot be reached upon registration@SMALL@Default is to hold.',
	// 'CFG_reg_sfs_explain_api'		=> '__AdminRegExplainSFSApi',
	// 'CFG_reg_sfs_api_key'			=> 'Your API key (optional)',
// settings_mail
	// 'CFG_mail_notifications'		=> 'Members can opt-in to receive mail notifications.',
	// 'CFG_smtp_advice'				=> 'Leave SMTP server fields empty to send through PHP and sendmail.@SMALL@<a href="http://efiction.org/wiki/Server#Working_settings_for_common_mail_providers" target="_blank">Documentation in the wiki. {ICON:external-link}</a>',
	// 'CFG_smtp_server'				=> 'SMTP server@SMALL@See WIKI for GMail!',
	// 'CFG_smtp_scheme' 				=> 'SMTP security scheme',
	// 'CFG_smtp_port'					=> 'Port number (if not using default)',
	// 'CFG_smtp_username'				=> 'SMTP username',
	// 'CFG_smtp_password'				=> 'SMTP password',
// settings_maintenance
	// 'CFG_chapter_data_location' 	=> 'Chapter storage (Database Server or local file storage)@SMALL@Read-only - Local file is being handled by SQLite',
	// 'CFG_debug'						=> 'Debug level',
	'CFG_maintenance'				=> 'Archiv wegen Wartungsarbeiten geschlossen',
	// 'CFG_logging'					=> 'Log actions',

// Story view'
'Title' =>	'Titel',
'Author' =>	'Autor',
'AuthorCounted' => '{0, plural,'.
	'one	{Author},'.
	'other	{Authors}
}',
'Categories' =>	'Kategorien',
'Characters' =>	'Charaktere',
'Rating' =>	'Einstufung',
'TagsInclude'	=>	'Tags miteinbeziehen',
'TagsExclude'	=>	'Tags nicht miteinbeziehen',
'Status' =>	'Status',
'Reviews' =>	'Reviews',
'Foreword' =>	'Vorwort',
'Summary' =>	'Klappentext',
'Tags' =>	'Tags',
'by' => 'von',
'Stories' => 'Geschichten',
'NewStories' => 'Neue Geschichten',
'RandomStory' => '{0, plural,'.
	'one	{Zufällige Geschichte},'.
	'other	{Zufällige Geschichten} }',
'FeaturedStory' => '{0, plural,'.
	'one	{Empfohlene Geschichte},'.
	'other	{Empfohlene Geschichten} }',
'RecommendedStory'	=> '{0, plural,'.
	'one	{Externe Empfehlung},'.
	'other	{Externe Empfehlungen} }',

'BookmarkAdd'		=> 'Diese Geschichte hat kein Lesezeichen.
Hier klicken um eines zu setzen.',
'BookmarkRemove'		=> 'Diese Geschichte hat ein Lesezeichen.
Hier klicken um es zu entfernen.',
'FavouriteAdd'		=> 'Diese Geschichte gehört nicht zu den Favoriten.
Hier klicken um hinzuzufügen.',
'FavouriteRemove'	=> 'Diese Geschichte gehört zu den Favoriten.
Hier klicken um zu entfernen.',

'TOC'	=> 'Inhaltsverzeichnis',
'NoTags' => 'Keine Tags gesetzt',
'Published' => 'Veröffentlicht',
'Updated'	=> 'Zuletzt überarbeitet',
'Chapters' => 'Kapitel',
'Words' => 'Wörter',
'WIP' => 'Work in progress',
'Completed' => 'Abgeschlossen',
'Characters' => 'Charaktere',
'Clicks' => 'Klicks',
'Author_Notes' => 'Anmerkungen des Autors',
'BrowseStories' => '{0, plural,'.
	'one	{Eine Geschichte anzeigen},'.
	'other	{# Geschichten durschstöbern}
}',

'Review_Link' => '{0, plural,'.
	'zero	{Noch keine ... schreibe die erste!},'.
	'one	{Eine Review},'.
	'other	{# Reviews}
}',
'Review_Link_TOC' => '{0, plural,'.
	'zero	{Keine Reviews},'.
	'one	{Eine Review},'.
	'other	{# Reviews}
}',

'Search' => 'Suche',
'Tagcloud' => 'Tagcloud',

// Feedback
'Feedback_Not_Logged_In' => 'Du musst angemeldet sein, um eine Review oder einen Kommentar zu verfassen.',

// Archiv News'
'News_Box' => 'Archiv News',
'News_Archive' => 'Alle News lesen',
'News_writtenby' => 'geschrieben von',
'CommentsC' => '{0, plural,'.
	'zero	{Noch keine Kommentare},'.
	'one	{Ein Kommentar},'.
'other	{# Kommentare} }',

// Archiv Stats'
'AS_ArchiveStats' => 'Archiv Statistiken',
'AS_Members' => 'Mitglieder',
'AS_Authors' => 'Autoren',
'AS_Stories' => 'Stories',
'AS_Chapters' => 'Kapitel',
'AS_Reviews' => 'Reviews',
'AS_Online' => 'Wer online ist',
'AS_Guests' => 'Gäste',
'AS_Users' => 'Mitglieder',
'AS_LatestMember' => 'Neuestes Mitglied',

'Status_Changes' => '{0, plural,'.
	'zero	{Keine Änderungen.},'.
	'one	{Ein Element geändert.},'.
	'other	{# Elemente geändert.}
}',
// 'Status_Errors' => '{0, plural,'.
	// 'zero	{No errors.},'.
	// 'one	{An error occurred while saving data.},'.
	// 'other	{# errors occurred while saving data.}
// }',

);

?>
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
'Date'		=>	'Datum',
'Message'	=>	'Nachricht',
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
'MaintenanceMode'		=> 'Archiv befindet sich in der Wartung',
'Token_NoMatches'	=> 'Keine Treffer',

// Login
'Login'			=> 'Anmelden',
'LoginName'		=> 'Anmeldename',
'VisibleName'	=> 'Sichtbarer Name',
'Logout'		=> 'Abmelden',
'Username'		=> 'Benutzername',
'Password'		=> 'Passwort',
'Email'			=> 'E-Mail',
'Registered'	=> 'Registriert',
'Guest'			=> 'Gast',
'ForgotPW'		=> 'Passwort vergessen',
'ResetPW'		=> 'Passwort zurücksetzen',
'ResetPWMail'	=> 'Falls ein Benutzer mit diesem Namen oder dieser E-Mail Adresse existiert, wurde eine E-Mail mit weiteren Anweisungen verschickt.',
'ChangePW'		=> 'Passwort ändern',
'PWRecovery'	=> 'Passwort Wiederherstellung',
'Login_NoMatch'	=> 'Anmeldung nicht möglich, Benutzername oder Passwort ungültig!',
'Login_NoData'	=> 'Anmeldung nicht möglich, keine Daten!',

// Registrierung
'Registration'					=> 'Registrierung',
'RegisterNow'					=> 'Jetzt registrieren',
'Registration_AcceptTOS'		=> 'Ich akzeptiere die Nutzungsbedingungen!',
'Registration_AcceptTOS_Error'	=> 'Du musst den Nutzungsbedingungen zustimmen.',
'Registration_UsernameEmpty'	=> 'Benutzername fehlt!',
'Registration_UsernameTaken'	=> 'Benutzername wird bereits verwendet.',
'Registration_EmailEmpty'		=> 'E-Mail Adresse fehlt!',
'Registration_EmailTaken'		=> 'E-Mail wird bereits verwendet.',
'Registration_AlreadyMember'	=> 'Ein Benutzer mit diesen Daten existiert bereits.',
'Registration_PasswordRepeat'	=> 'Passwort wiederholen',
'Registration_PasswordTwice'	=> 'Passwort bitte zwei mal eingeben',
'Registration_PasswordMismatch'	=> 'Die eingegebenen Passwörter stimmen nicht miteinander überein.',
'Registration_PasswordCriteria'	=> 'Dein gewünschtes Passwort erfüllt nicht die Voraussetzungen.',


'Sort_Date'	=> 'Datum',
'Sort_ID'	=> 'ID',


'CookieText'	=> 'Diese Website benutzt Cookies, um das Krümelmonster zu füttern.',
'CookieInfo'	=> 'Mehr über die echten Gründe.',
'CookieAccept'	=> 'Akzeptiert!',

'PM_Inbox'				=> 'Posteingang',
'PM_Outbox'				=> 'Postausgang',
'PM_Write'				=> 'Verfassen',
'PM_Outbox_Items'		=> '{0, plural,'.
							'zero	{Dein Postausgang ist leer!},'.
							'one	{Eine Nachtricht:},'.
							'other	{# Nachrichten:} }',
'PM_Inbox_Items' 		=> '{0, plural,'.
							'zero	{Dein Posteingang ist leer!},'.
							'one	{Eine Nachtricht:},'.
							'other	{# Nachrichten:} }',
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

'Author'	=> 'Autor',
'Authors'	=> 'Autoren',
'noAuthors'	=> 'Noch keine Autoren',
'Recomm'	=> 'Empfehlung',
'Recomms'	=> 'Empfehlungen',
'RecommBy'	=> 'Empfohlen von',
'Series'	=> 'Serie',
'Story'		=> 'Geschichte',
'Stories'	=> 'Geschichten',

// UserCP elements
'ChangeTo'	=> 'Ändern in',
'UserMenu_Profile' => 'Profil',
'UserMenu_Message' => 'Nachrichten',
'UserMenu_PMInbox' => '{0, plural,'.
							'zero	{Posteingang},'.
							'other	{Posteingang (# neu)} }',
'UserMenu_PMOutbox' => 'Postausgang',
'UserMenu_PMWrite' => 'Verfassen',
	'MSG_deletedSuccess'	=> 'Nachricht gelöscht!',
	'MSG_deleteRead'	=> 'Löschen nicht möglich, Nachricht wurde bereits gelesen.',
	'MSG_deleteNotFound' => 'Löschen nicht möglich, Nachricht wurde nicht gefunden.',
	'MSG_deleteNoAccess' => 'Du kannst auf diese Nachricht nicht zugreifen.',
'UserMenu_Shoutbox'			=> '{0, plural,'. 'other {Shoutbox (#)} }', 

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
'UserMenu_Settings' => 'Einstellungen',
//	'UserMenu_Preferences' => 'Preferences',

'UCP_Pref_Alerts'			=>	'Benachrichtigungen',
	'UCP_Pref_AlertOn'			=> 'Benachrichtige mich, falls ...',
	'UCP_Pref_AlertFeedback'	=> '... ich Feedback erhalte.',
	'UCP_Pref_AlertComment'		=> '... ich einen Kommentar erhalte.',
	'UCP_Pref_AlertFavourite'	=> '... ein Favorit aktiv ist.',
'UCP_Pref_View'		=>	'Ansicht und Bedienung',
	'UCP_Pref_TOC'		=> 'Bei Geschichten mit mehreren Kapiteln standardmäßig das Inhaltsverzeichnis zeigen.',
	'UCP_Pref_Sort'		=> 'Geschichten sortieren nach ...',
	'UCP_Pref_sortAZ'	=> 'alphabetisch',
	'UCP_Pref_sortNew'	=> 'nach Datum',
	'UCP_Pref_Language'	=> 'Sprache (Language)',
//	'UCP_Pref_Layout'	=> 'Layout/Theme',
	'UCP_Pref_Editor'	=> 'Erweiterten Editor benutzen.',
//	'UCP_Pref_Age'		=> '***Age consent***',
//	'UCP_Pref_hideTags'	=> '***Hide Tags***',

'UserMenu_AddStory' => 'Hinzufügen',
'StoryTitle'	=>	'Titel der Geschichte',
'ChapterTitle'	=>	'Titel des Kapitels',
'ChapterText'	=>	'Text des Kapitels',
'AddChapter'	=> 'Kapitel hinzufügen',
'EditHeader'	=> 'Kopfdaten bearbeiten',
'DragdropSort'	=> 'Ziehen um zu sortieren',
'SwitchPlainHTML'	=> 'Zum einfachen Modus wechseln',
'SwitchVisual'		=> 'Zum Editor wechseln',
'UCP_statusValidated' => 'Freigabe',
	'UCP_statusValidated_0'	=> 'Geschlossen',
	'UCP_statusValidated_1'	=> 'Offen, Autor arbeitet',
	'UCP_statusValidated_2'	=> 'Offen, Autor fertig',
	'UCP_statusValidated_3'	=> 'Bestätigt',
'UCP_statusValidatedReason' => 'Details zur Freigabe',
	'UCP_statusValReason_0'	=> 'Kein Grund angegeben',
	'UCP_statusValReason_1'	=> 'Durch Autor',
	'UCP_statusValReason_2'	=> 'Durch Moderator',
	'UCP_statusValReason_3'	=> 'Durch Administrator',
	'UCP_statusValReason_4'	=> 'Muss überarbeitet werden',
	'UCP_statusValReason_5'	=> 'Wird in geringem Umfang überarbeitet',
	'UCP_statusValReason_6'	=> 'Wird in großem Umfang überarbeitet',
	'UCP_statusValReason_7'	=> 'Geringfügige Überarbeitung abgeschlossen',
	'UCP_statusValReason_8'	=> 'Umfangreiche Überarbeitung abgeschlossen',
	'UCP_statusValReason_9'	=> 'Gesperrt/verwaist',
'UCP_statusCompleted'	=> 'Status',
	'UCP_statusCompleted_0'	=> 'Gelöscht',
	'UCP_statusCompleted_1'	=> 'Entwurf',
	'UCP_statusCompleted_2'	=> 'Unvollendet',
	'UCP_statusCompleted_3'	=> 'Abgeschlossen',
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
'AdminMenu_Logs' => 'Logs',
	'AdminMenu_Logs_AM' => 'Wartung durch Admin',
	'AdminMenu_Logs_DL' => 'Löschungen',
	'AdminMenu_Logs_EB' => 'Mitglieder bearbeitet',
	'AdminMenu_Logs_ED' => 'Geschichten bearbeitet',
	'AdminMenu_Logs_LP' => 'Passwort verloren',
	'AdminMenu_Logs_RE' => 'Reviews',
	'AdminMenu_Logs_RG' => 'Registrierungen',
	'AdminMenu_Logs_RF' => 'Fehler bei der Registrierung',
	'AdminMenu_Logs_VS' => 'Bestätigungen',
'AdminMenu_Shoutbox' => 'Shoutbox',

'AdminMenu_Settings' => 'Einstellungen',
'AdminMenu_Server' => 'Server',
	'AdminMenu_DateTime'	=> 'Datum und Uhrzeit',
	'AdminMenu_Mail'		=> 'Mail und Mail-Server',
	'AdminMenu_Maintenance'	=> 'Wartung',
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
		'AdminMenu_Team_Group4' => 'Lektor',
		'AdminMenu_Team_Group5' => 'Moderator',
		'AdminMenu_Team_Group6' => 'Super Moderator',
		'AdminMenu_Team_Group7' => 'Administrator',

'AdminMenu_Archive' => 'Archive',
	'AdminMenu_Intro' => 'Intro',
'AdminMenu_Submission' => 'Einsendungen',
	'AdminMenu_Stories' => 'Geschichten',
	'AdminMenu_Images' => 'Cover Grafik',
	'AdminMenu_Reviews' => 'Reviews',
'AdminMenu_Featured' => 'Featured',
	'AdminMenu_Future' => 'Zukünftige',
	'AdminMenu_Current' => 'Aktuell',
	'AdminMenu_Past' => 'Vergangenheit',
'AdminMenu_Characters' => 'Charaktere',
'AdminMenu_Tags' => 'Tags',
	'AdminMenu_Edit' => 'Editieren',
	'AdminMenu_Taggroups' => 'Tag-Gruppen',
	'AdminMenu_Tagcloud' => 'Tag-Cloud',
'AdminMenu_Categories' => 'Kategorien',
'ACP_Categories_Success_Deleted' => 'Kategorien "{0}" wurde erfolgreich gelöscht!',
'ACP_Categories_Error_notEmpty' => 'Kann Kategorien "{0}" nicht löschen, da sie nicht leer ist!',
'ACP_Categories_Error_DBError' => 'Konnte Kategorien "{0}" wegen Datenbankfehler nicht löschen!',
'ACP_Categories_Error_badID' => 'Kann die Kategorien nicht löschen, keine gültige ID gefunden!',

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
'AdminMenu_Edit' => 'Editieren',
'AdminMenu_Add' => 'Hinzufügen',


'Welcome' => 'Willkommen',
'Shoutbox' => 'Shoutbox',

// Config explain
// archive_general
	'CFG_stories_per_page'		=> 'Angezeigte Geschichten pro Seite im Archiv.',
	'CFG_stories_recent'		=> 'Tage für zuletzt erschienene Geschichten.',
	'CFG_stories_default_order'	=> 'Standardsortierung für Geschichten.',
	'CFG_story_toc_default'		=> 'Zeige immer das Inhaltsverzeichnis bei Geschichten mit mehreren Kapiteln.',
	// 'CFG_epub_domain'			=> 'ePub Domain@SMALL@Used to calculate your epub UUID v5. Leave blank for default (Archive URL)',
// archive_images
	// 'CFG_images_allowed' 		=> 'Allow posting of story images (cover art)',
	'CFG_images_height'			=> 'Maximale Höhe für Bilder',
	'CFG_images_width'			=> 'Maximale Breite für Bilder',
// archive_intro
	'CFG_story_intro_items' => 'Anzahl der Stories auf der Startseite des Archivs.',
	'CFG_story_intro_order' => 'Sortierung der Stories auf der Startseite des Archivs.',
// archive_reviews
	'CFG_allow_reviews'			=> 'Reviews erlauben',
	'CFG_allow_guest_reviews'	=> 'Gäste können Reviews schreiben',
	'CFG_allow_review_delete'	=> 'Autoren können Reviews ihrer Geschichten löschen',
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
	'CFG_monday_first_day'		=> 'Erster Wochentag im Kalender',
// settings_language
	'CFG_language_forced'		=> 'Deaktiviere persönliche Sprachwahl: @SMALL@Standard ist <b>nein</b>',
	// 'CFG_language_available'	=> 'List all languages that are available to common members.',
// settings_layout
	// 'CFG_layout_forced'			=> 'Disable custom layout selection:@SMALL@Standard ist <b>nein</b>',
	// 'CFG_layout_available'		=> '',
// settings_general
	'CFG_page_title'			=> 'Website-Titel',
	'CFG_page_mail'				=> 'Webmaster E-Mail Adresse',
	'CFG_page_slogan'			=> 'Website-Slogan',
	'CFG_page_title_add'		=> 'Zeige Seitenpfad oder Slogan im Titel',
	'CFG_page_title_reverse'	=> 'Kehre Sortierung der Seiten-Titel Elemente um @SMALL@(Standard ist <b>nein</b>)',
	'CFG_page_title_separator'	=> 'Trenner für Seiten-Titel Elemente',
	'CFG_adjacent_paginations' 	=> 'Anzeige zusammenhängender Page-Links @SMALL@"1" für: 1 ... 4 [5] 6 ... 9<br />"2" für: 1 ... 3 4 [5] 6 7 ... 9<br>"0" um alle Links anzuzeigen',
	'CFG_shoutbox_entries'		=> 'Anzahl der anzuzeigenden Shoutbox-Einträge',
	'CFG_shoutbox_guest'		=> 'Erlaube Gästen die Shoutbox zu benutzen',
	'CFG_allow_comment_news'	=> 'Erlaube News-Kommentare',
	'CFG_allow_guest_comment_news'	=> 'Erlaube Gästen News-Kommentare',
// settings_registration
	'CFG_allow_registration'		=> 'Registrierung erlaubt?',
	'CFG_reg_require_email'			=> 'Benutzer müssen ihren Account via E-Mail Link aktivieren.',
	'CFG_reg_require_mod'			=> 'Registrierungen werden moderiert.',
	'CFG_reg_min_username'			=> 'Mindestzeichen für Benutzernamen.',
	'CFG_reg_min_password'			=> 'Mindestzeichen für Passwörter.',
	'CFG_reg_password_complexity'	=> 'Passwort Komplexität: @SMALL@see wiki',
	// 'CFG_reg_use_captcha'			=> 'Select CAPTCHA to be used@SMALL@Configure under <a href=''{{@BASE}}/adminCP/settings/security''>Settings - Security</a>',
// settings_registration_sfs
	'CFG_reg_sfs_usage'				=> 'Nutze den "Stop Forumspam" Service. @SMALL@<a href="http://www.stopforumspam.com/faq" target="_blank">FAQ @ http://www.stopforumspam.com</a>',
	'CFG_reg_sfs_check_ip'			=> 'IP prüfen.',
	'CFG_reg_sfs_check_mail'		=> 'E-Mail Adresse prüfen.',
	'CFG_reg_sfs_check_username'	=> 'Benutzername prüfen.',
	// 'CFG_reg_sfs_check_advice'		=> 'You may turn off username checking if you encounter false positives.<br />Turning off IP and mail check is not advised, however.',
	// 'CFG_reg_sfs_failsafe'			=> 'How to behave if the SFS Service cannot be reached upon registration@SMALL@Default is to hold.',
	// 'CFG_reg_sfs_explain_api'		=> '__AdminRegExplainSFSApi',
	// 'CFG_reg_sfs_api_key'			=> 'Your API key (optional)',
// settings_mail
	// 'CFG_mail_notifications'		=> 'Members can opt-in to receive mail notifications.',
	// 'CFG_smtp_advice'				=> 'Leave SMTP server fields empty to send through PHP and sendmail.@SMALL@<a href="http://efiction.org/wiki/Server#Working_settings_for_common_mail_providers" target="_blank">Documentation in the wiki. {ICON:external-link}</a>',
	// 'CFG_smtp_server'				=> 'SMTP server@SMALL@See WIKI for GMail!',
	// 'CFG_smtp_scheme' 				=> 'SMTP security scheme',
	// 'CFG_smtp_port'					=> 'Port Nummer (if not using default)',
	'CFG_smtp_username'				=> 'SMTP Benutzername',
	'CFG_smtp_password'				=> 'SMTP Passwort',
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
'translatedBy'	=> 'übersetzt von',
'Stories' => 'Geschichten',
'NewStories' => 'Neue Geschichten',
//'noNewStory'			=> 'No new Stories',
'RandomStory' => '{0, plural,'.
	'one	{Zufällige Geschichte},'.
	'other	{Zufällige Geschichten} }',
//'noRandomStory'	=> 'No random stories yet',
//'RandomSpotlight' => 'Random Spotlight',
'FeaturedStory' => '{0, plural,'.
	'one	{Empfohlene Geschichte},'.
	'other	{Empfohlene Geschichten} }',
//'noFeaturedStory'	=> 'No featured stories yet',
'RecommendedStory'	=> '{0, plural,'.
	'one	{Externe Empfehlung},'.
	'other	{Externe Empfehlungen} }',
//'noRecommendedStory'	=> 'No recommended stories yet',
'TitleReadReviews' => '{0, plural, other {Read reviews for \'#\'} }',

'BookmarkAdd'		=> '{0, plural, other {\'#\' hat kein Lesezeichen, hier klicken um eines zu setzen.} }',
'BookmarkRemove'		=> '{0, plural, other {\'#\' hat ein Lesezeichen, hier klicken um es zu entfernen.} }',
'FavouriteAdd'		=> '{0, plural, other {\'#\' gehört nicht zu deinen Favoriten, hier klicken um sie hinzuzufügen.} }',
'FavouriteRemove'	=> 'Diese Geschichte gehört zu den Favoriten.
Hier klicken um zu entfernen.',

'TOC'	=> 'Inhaltsverzeichnis',
'NoTags' => 'Keine Tags gesetzt',
'Published' => 'Veröffentlicht',
'Updated'	=> 'Aktualisiert',
'Chapter' => 'Kapitel',
'Chapters' => 'Kapitel',
'Words' => 'Wörter',
'WIP' => 'In Arbeit',
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
//'SearchUsername'=> 'Search in usernames',
//'SearchUserAll' => 'Search in all user fields',
'Tagcloud' => 'Tagcloud',
'noTagcloud' => 'No tag cloud',
'Edit'		=> 'Bearbeiten',

// Feedback
'Feedback_Not_Logged_In' => 'Du musst angemeldet sein, um eine Review oder einen Kommentar zu verfassen.',
'Button_reviewStory'	=> 'Review zu dieser Geschichte schreiben',
'Button_reviewChapter'	=> 'Review zu diesem Kapitel schreiben',
'Button_writeComment'	=> 'Kommentar schreiben',

// Archiv News'
'News_Box' => 'Archiv News',
'News_Archive' => 'Alle News lesen',
'News_writtenby' => 'geschrieben von',
'Reply' => 'Antworten',
'Comment' => 'Kommentar',
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

'ReviewHeadline' => 'Am {1} um {2} Uhr schrieb {0} eine Review zu Kapitel {3}',
/*
'ReviewHeadline' => '{3, plural,'.
	'zero  {On {1} at {2}, {0} wrote a review},'.
	'other {On {1} at {2}, {0} wrote a review for chapter {3}}
}',
*/
'ReplyHeadline_noDate' => '{0} antwortete:',
'ReplyHeadline' => 'Am {1} um {2} Uhr antwortete {0}:',
'ReviewCommentsLink' => '{0, plural,'.
	'one	{One comment.},'.
	'other	{# comments.}
}',


);

?>
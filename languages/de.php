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
//'CFG_stories_recent'		=> 'Days for recent stories',
'CFG_stories_default_order'	=> 'Standardsortierung für Stories',
//'CFG_story_toc_default'		=> 'Show to table of contents by default for stories with multiple chapters.',
//'CFG_epub_domain'			=> 'ePub Domain@SMALL@Used to calculate your epub UUID v5. Leave blank for default (Archive URL)',
	// archive_intro
'CFG_story_intro_items' => 'Anzahl der Stories auf der Archiv Startseite.',
'CFG_story_intro_order' => 'Sortierung der Stories auf der Archiv Startseite.',
	// members_general
//'CFG_agestatement'		=>	'Have members set their age to show rating warnings',

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
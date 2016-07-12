<?php
@define('__transLocale', 'en_EN');

return array(
'UserCP' => 'User Panel',
'AdminCP' => 'Admin Panel',

'Sort_Date'	=> 'Date',
'Sort_ID'	=> 'ID',

'PM_Inbox' => 'Inbox',
'PM_Outbox' => 'Outbox',
'PM_Write' => 'Write',
'PM_Outbox_Items' => '{0, plural,'.
	'zero	{Your Outbox is empty!},'.
	'one	{One message:},'.
	'other	{# messages:}
}',
'PM_Outbox_empty' => 'Your Outbox is empty',

'PM_Inbox_empty' => 'Your Inbox is empty',
'PM_Inbox_Items' => '{0, plural,'.
	'zero	{Your Inbox is empty!},'.
	'one	{One message:},'.
	'other	{# messages:}
}',

'PM_Subject' => 'Subject',
'PM_Sender' => 'Sender',
'PM_Sent' => 'Sent',
'PM_Received' => 'Received',
'PM_Recipient' => 'Recipient',
'PM_question_Delete' => 'Delete message',
'PM_confirm_Delete' => 'Message will be deleted, sure?',
'PM_unread' => 'unread',
'PM_ReplySubject' => 'Re:',
'PM_ReplyMessageHeader' => 'On {0}, {1} wrote:',
'PM_WritePM' => 'Writing message',
'PM_Messaging' => 'Messaging',

'Month_Calendar' => '{0,date,custom,%B %Y}',

'Menu_Profile' => 'Profile',
'Menu_Messaging' => 'Messaging',
'Menu_Authoring' => 'Authoring',
'Menu_MyLibrary' => 'My Library',
'Menu_Reviews' => 'Reviews',
'Menu_Preferences' => 'Preferences',

// User elements
'UserField_Type1'	=> 'URL',			// _FIELDURL
'UserField_Type2'	=> 'Select box',	// _FIELDSELECT
'UserField_Type3'	=> 'Yes/No',		// _FIELDYESNO
'UserField_Type4'	=> 'ID with URL',	// _FIELDIDURL
'UserField_Type5'	=> 'Custom Code',	// _FIELDCUSTOM
'UserField_Type6'	=> 'Text',			// _TEXT

// UserCP elements
'UserMenu_Profile' => 'Profile',
'UserMenu_Message' => 'Messaging',
'UserMenu_PMInbox' => 'Inbox',
'UserMenu_PMOutbox' => 'Outbox',
'UserMenu_PMWrite' => 'Write',
'UserMenu_Authoring' => 'Authoring',
'UserMenu_MyLibrary' => 'My Library',
'UserMenu_Reviews' => 'Reviews',
'UserMenu_Preferences' => 'Preferences',

'UserMenu_AddStory' => 'Add Story',
'UserMenu_Curator' => 'Curator',

// AdminCP Home elements	
'AdminMenu_Home' => 'Home',
'AdminMenu_Manual' => 'Manual',
'AdminMenu_CustomPages' => 'Custom Pages',
'AdminMenu_News' => 'News',
'AdminMenu_Modules' => 'Modules',
'AdminMenu_Shoutbox' => 'Shoutbox',

'AdminMenu_Settings' => 'Settings',
'AdminMenu_Server' => 'Server',
'AdminMenu_Registration' => 'Registration',
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

'AdminMenu_Archive' => 'Archive',
'AdminMenu_Featured' => 'Featured',
	'AdminMenu_Future' => 'Future',
	'AdminMenu_Current' => 'Current',
	'AdminMenu_Past' => 'Past',
'AdminMenu_Characters' => 'Characters',
'AdminMenu_Tags' => 'Tags',
	'AdminMenu_Edit' => 'Edit',
	'AdminMenu_Taggroups' => 'Groups',
	'AdminMenu_Tagcloud' => 'Cloud',
'AdminMenu_Categories' => 'Categories',
'ACP_Categories_Success_Deleted' => 'Category "{0}" successfully deleted!',
'ACP_Categories_Error_notEmpty' => 'Could not delete category "{0}", because it is not empty!',
'ACP_Categories_Error_DBError' => 'Could not delete category "{0}", database error occured!',
'ACP_Categories_Error_badID' => 'Could not delete category, ID not found in database!',

'AdminMenu_Stories' => 'Stories',
'AdminMenu_Pending' => 'Pending',
'AdminMenu_Edit' => 'Edit',
'AdminMenu_Add' => 'Add',


'Welcome' => 'Welcome',
'Shoutbox' => 'Shoutbox',

// Story view'
'Stories' => 'Stories',
'St_NewStories' => 'New Stories',
'St_RandomStory' => 'Random Story',
'St_RandomStories' => 'Random Stories',
'St_FeaturedStory' => 'Featured Story',
'St_FeaturedStories' => 'Featured Stories',

'St_NoTags' => 'No tags defined',
'St_Published' => 'Published',
'St_Updated'	=> 'Last update',
'St_Chapters' => 'Chapters',
'St_Words' => 'Words',
'St_Status' => 'Status',
'St_WIP' => 'Work in progress',
'St_Completed' => 'Completed',
'Characters' => 'Characters',
'Author_Notes' => 'Author notes',
'Summary'	=> 'Summary',

'Review_Link' => '{0, plural,'.
	'zero	{None yet! Be the first to write one ...},'.
	'one	{One review},'.
	'other	{# reviews}
}',
'Review_Link_TOC' => '{0, plural,'.
	'zero	{No reviews},'.
	'one	{One review},'.
	'other	{# reviews}
}',

'Search' => 'Search',
'Tagcloud' => 'Tagcloud',

// Feedback
'Feedback_Not_Logged_In' => 'You need to be logged in to write a review or comment',

// Archive News
'News_Box' => 'Archive News',
'News_Archive' => 'News Archive',
'News_writtenby' => 'written by',

// Archiv Stats
'AS_ArchiveStats' => 'Archive Stats',
'AS_Members' => 'Members',
'AS_Authors' => 'Authors',
'AS_Stories' => 'Stories',
'AS_Chapters' => 'Chapters',
'AS_Reviews' => 'Reviews',
'AS_Online' => 'Who\'s online',
'AS_Guests' => 'Guests',
'AS_Users' => 'Users',
'AS_LatestMember' => 'Latest Member',

'Status_Changes' => '{0, plural,'.
	'zero	{No changes.},'.
	'one	{One element changed.},'.
	'other	{# elements changed.}
}',
);

?>
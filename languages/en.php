<?php
@define('__transLocale', 'en_GB');

return array(
// General stuff
'UserCP'	=> 'User Panel',
'AdminCP'	=> 'Admin Panel',
'yes'		=>	'yes',
'Yes'		=>	'Yes',
'no'		=>	'no',
'No'		=>	'No',
'Cancel'	=>	'Cancel',
'Submit'	=>	'Submit',
'CaptchaMismatch'	=>	'Captcha did not match',
'GuestURL'		=>	'Guests are not allowed to post URLs',
'GuestNameEmpty' => 'Name field cannot be empty',
'MessageEmpty'	=>	'No message entered',
'CommentEmpty'	=>	'No comment entered',
'CannotSave'	=>	'Could not save the data, please try again',
'FormErrors'	=> '{0, plural,'.
							'zero	{No errors},'.
							'one	{There was an error while processing this form},'.
							'other	{There were errors while processing this form} }',
'UnknowError'	=> 'There was an unknown error',

// Login related
'Login'			=> 'Login',
'Logout'		=> 'Logout',
'Username'		=> 'Username',
'Password'		=> 'Password',
'Email'			=> 'e-Mail',
'Guest'			=> 'Guest',
'ForgotPW'		=> 'Forgot you password',
'ResetPW'		=> 'Reset my password',
'ResetPWMail'	=> 'If a user with this name or e-mail exists, a mail containing instructions on how to reset the password has been sent',
'ChangePW'		=> 'Change password',
'PWRecovery'	=> 'Password recovery',
'Login_NoMatch'	=> 'Failed to log in, invalid username or password!',
'Login_NoData'	=> 'Failed to log in, data error!',

// User registration
'Registration'					=> 'Registration',
'RegisterNow'					=> 'Register now',
'Registration_AcceptTOS'		=> 'I accept these terms!',
'Registration_AcceptTOS_Error'	=> 'You have to accept the terms of use',
'Registration_UsernameEmpty'	=> 'No login name provided!',
'Registration_UsernameTaken'	=> 'Login name already in use.',
'Registration_EmailEmpty'		=> 'No e-mail adress provided!',
'Registration_EmailTaken'		=> 'Login name adress in use.',
'Registration_AlreadyMember'	=> 'A user with this data already exists.',
'Registration_PasswordRepeat'	=> 'Repeat password',
'Registration_PasswordTwice'	=> 'Enter your password twice',
'Registration_PasswordMismatch'	=> 'Your passwords do not match',
'Registration_PasswordCriteria'	=> 'Your desired password does not match the requirements',


'Sort_Date'	=> 'Date',
'Sort_ID'	=> 'ID',


'CookieText'	=> 'This website uses cookies to ensure you get the best experience on our website.',
'CookieInfo'	=> 'More info on cookies',
'CookieAccept'	=> 'Accept cookies',

'PM_Inbox'				=> 'Inbox',
'PM_Outbox'				=> 'Outbox',
'PM_Write'				=> 'Write',
'PM_Outbox_Items'		=> '{0, plural,'.
							'zero	{Your Outbox is empty!},'.
							'one	{One message:},'.
							'other	{# messages:} }',
'PM_Outbox_empty' 		=> 'Your Outbox is empty',
'PM_Inbox_Items' 		=> '{0, plural,'.
							'zero	{Your Inbox is empty!},'.
							'one	{One message:},'.
							'other	{# messages:} }',
'PM_Inbox_empty' 		=> 'Your Inbox is empty',
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

// using php strftime
'Month_Calendar' => '{0,date,custom,%B %Y}',
'Weekday' => '{0,date,custom,%A}',

/*
'Menu_Profile' => 'Profile',
'Menu_Messaging' => 'Messaging',
'Menu_Authoring' => 'Authoring',
'Menu_MyLibrary' => 'My Library',
'Menu_Reviews' => 'Reviews',
'Menu_Preferences' => 'Preferences',
*/

// User elements
'UserField_Type1'	=> 'URL',			// _FIELDURL
'UserField_Type2'	=> 'Select box',	// _FIELDSELECT
'UserField_Type3'	=> 'Yes/No',		// _FIELDYESNO
'UserField_Type4'	=> 'ID with URL',	// _FIELDIDURL
'UserField_Type5'	=> 'Custom Code',	// _FIELDCUSTOM
'UserField_Type6'	=> 'Text',			// _TEXT

'Author'	=> 'Author',
'Authors'	=> 'Authors',
'Recomm'	=> 'Recommendation',
'Recomms'	=> 'Recommendations',
'RecommBy'	=> 'Recommended by',
'Series'	=> 'Series',
'Story'		=> 'Story',
'Stories'	=> 'Stories',

// UserCP elements
'UserMenu_Profile' => 'Profile',
'UserMenu_Message' => 'Messaging',
'UserMenu_PMInbox' => 'Inbox',
'UserMenu_PMOutbox' => 'Outbox',
'UserMenu_PMWrite' => 'Write',
	'MSG_deletedSuccess'	=> 'Message deleted',
	'MSG_deleteRead'	=> 'Unable to delete, this message has been read',
	'MSG_deleteNotFound' => 'Unable to delete, Message not found',
	'MSG_deleteNoAccess' => 'You have no access to this message',
	
'UserMenu_Authoring' => 'Authoring',
	'Authoring_Finished' => '{0, plural,'.
		'other	{Finished (#)}}',
	'Authoring_Unfinished' => '{0, plural,'.
		'other	{Unfinished (#)}}',
	'Authoring_Drafts' => '{0, plural,'.
		'other	{Drafts (#)}}',
'UserMenu_MyLibrary' => 'My Library',
	'Library_Favourites' => '{0, plural,'.
		'other	{Favourites (#)}
	}',
	'AddFavourite'	=>	'Add favourite',
	'Library_Bookmarks' => '{0, plural,'.
		'other	{Bookmarks (#)}
	}',
	'AddBookmark'	=> 'Add bookmark',
	'Library_Recommendations' => '{0, plural,'.
		'other	{Recommendations (#)}
	}',
'UserMenu_Feedback' => 'Feedback',
'UserMenu_Reviews' => 'Reviews',
	'UserMenu_ReviewsWritten'	=> '{0, plural,'. 'other {Reviews written (#)} }',
	'UserMenu_ReviewsReceived'	=> '{0, plural,'. 'other {Reviews received (#)} }', 
	'UserMenu_CommentsWritten'	=> '{0, plural,'. 'other {Comments written (#)} }',
	'UserMenu_CommentsReceived'	=> '{0, plural,'. 'other {Comments received (#)} }', 
	'UserMenu_Shoutbox'			=> '{0, plural,'. 'other {Shoutbox (#)} }', 
'UserMenu_Settings' => 'Settings',
	'UserMenu_Preferences' => 'Preferences',

'UserMenu_AddStory' => 'Add Story',
'UserMenu_Curator' => 'Curator',

// AdminCP Home elements
'AdminMenu_General' => 'General settings',

'AdminMenu_Home' => 'Home',
'AdminMenu_Manual' => 'Manual',
'AdminMenu_CustomPages' => 'Custom Pages',
'AdminMenu_News' => 'News',
'AdminMenu_Modules' => 'Modules',
'AdminMenu_Logs' => 'Logs',
'AdminMenu_Shoutbox' => 'Shoutbox',

'AdminMenu_Settings' => 'Settings',
'AdminMenu_Server' => 'Server',
	'AdminMenu_DateTime'	=> 'Date and time',
	'AdminMenu_Mail'		=> 'Mail and mail server',
	'AdminMenu_Maintenance'	=> 'Maintenance',
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

'AdminMenu_Archive' => 'Archive',
	'AdminMenu_Intro' => 'Intro',
'AdminMenu_Submission' => 'Submissions',
	'AdminMenu_Stories' => 'Stories',
	'AdminMenu_Reviews' => 'Reviews',
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
'ACP_Categories_Success_Deleted'	=> 'Category "{0}" successfully deleted!',
'ACP_Categories_Error_notEmpty'		=> 'Could not delete category "{0}", because it is not empty!',
'ACP_Categories_Error_DBError'		=> 'Could not delete category "{0}", database error occured!',
'ACP_Categories_Error_badID'		=> 'Could not delete category, ID not found in database!',

'ACP_Tags'				=> 'Tags',
'ACP_TagName'			=> 'Tag name',
'ACP_TagLabel'			=> 'Tag label',
'ACP_TagDescription'	=> 'Tag description',
'ACP_TagLabel_Advice'	=> 'Only change when required',
'ACP_TagGroupLabel'		=> 'Tag group label',
'ACP_TagGroup'			=> 'Tag group',
'ACP_TagGroups'			=> 'Tag groups',

'AdminMenu_Stories' => 'Stories',
'AdminMenu_Pending' => 'Pending',
'AdminMenu_Edit' => 'Edit',
'AdminMenu_Add' => 'Add',


'Welcome' => 'Welcome',
'Shoutbox' => 'Shoutbox',

// Story view'
'Title' =>	'Title',
'Author' =>	'Author',
'AuthorCounted' => '{0, plural,'.
	'one	{Author},'.
	'other	{Authors}
}',
'Categories' =>	'Categories',
'Characters' =>	'Characters',
'Rating' =>	'Rating',
'TagsInclude'	=>	'Tags included',
'TagsExclude'	=>	'Tags excluded',
'Status' =>	'Status',
'Reviews' =>	'Reviews',
'Foreword' =>	'Foreword',
'Summary' =>	'Summary',
'Tags' =>	'Tags',
'by' => 'by',
'Stories'				=> 'Stories',
'NewStories'			=> 'New Stories',
'RandomStory' => '{0, plural,'.
	'one	{Random Story},'.
	'other	{Random Stories} }',
'FeaturedStory' => '{0, plural,'.
	'one	{Featured Story},'.
	'other	{Featured Stories} }',
'RecommendedStory'	=> '{0, plural,'.
	'one	{Recommended Story},'.
	'other	{Recommended Stories} }',

'BookmarkAdd'		=> 'This story has no bookmark.
Click here to add one now.',
'BookmarkRemove'		=> 'This story has a bookmark.
Click here to remove it.',
'FavouriteAdd'		=> 'This story is not a favourite.
Click here to set.',
'FavouriteRemove'	=> 'This story is a favourite.
Click here to unset.',

'TOC'	=> 'Table of content',
'NoTags' => 'No tags defined',
'Published' => 'Published',
'Updated'	=> 'Last update',
'Chapters' => 'Chapters',
'Words' => 'Words',
'WIP' => 'Work in progress',
'Completed' => 'Completed',
'Characters' => 'Characters',
'Clicks' => 'Clicks',
'Author_Notes' => 'Author notes',
'BrowseStories' => '{0, plural,'.
	'one	{Browse # story},'.
	'other	{Browse # stories}
}',

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
'CommentsC' => '{0, plural,'.
	'zero	{No comments yet},'.
	'one	{One comment},'.
'other	{# comments} }',

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
'Status_Errors' => '{0, plural,'.
	'zero	{No errors.},'.
	'one	{An error occurred while saving data.},'.
	'other	{# errors occurred while saving data.}
}',
);

?>
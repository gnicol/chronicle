package tests;

// This is a externalized class for a set of constants that are used throughout the Chronicle codebase for various classes

public final class CMSConstants {

	// GET SYSTEM INFORMATION - NEEDED FOR CODELINE, SERVEROS, SERVERCHANGE, CLIENTCODELINE, CLIENTCHANGE
	public static final String 	GET_SERVER_SYSTEM_INFO_TEXT = "//div[4]/div/div/div/div[3]/div[2]/div/div[13]/div[2]/p";	
	public static final String 	GET_CLIENT_SYSTEM_INFO_TEXT = "//div[4]/div/div/div/div[3]/div/div/div/div[2]/p";	
	public static final String  GET_CLIENT_SYSTEM_INFO_TEXT_NOLOGIN = "//div[5]/div/div/div/div/div/span[2]";
	
	// GET HOMEPAGE CODELINE - LOGGED IN
	public static final String  GET_HOMEPAGE_CODELINE = "//div[5]/div/div/div/div/div/span[2]";
	// GET HOMEPAGE CODELINE - NOT LOGGED
	public static final String  GET_HOMEPAGE_CODELINE_NO_LOGIN = "//div[4]/div/div/div/div/div/span[2]";

	
	// MANAGE MENU CONSTANTS
	public static final String 	MANAGE_MENU_PAGES_VERIFY 		= "link=Menus";
	public static final String 	MANAGE_MODULES_ANALYTICS 		= "link=Modules";
	public static final String 	MANAGE_MODULES_COMMENTS 		= "link=Modules";
	public static final String 	MANAGE_MODULES_ELEMENTS 		= "link=Modules";
	public static final String 	MANAGE_MODULES_PAGES_VERIFY 	= "link=Modules";
	public static final String 	MANAGE_PERMISSIONS_PAGE_VERIFY 	= "link=Permissions";
	public static final String 	MANAGE_ROLES_PAGE_VERIFY 		= "link=Roles";
	public static final String 	MANAGE_ROLES 					= "link=Roles";
	public static final String 	MANAGE_SEARCH_SETTINGS_VERIFY 	= "link=Search Settings";
	public static final String 	MANAGE_SYSTEM_INFO_VERIFY 		= "link=System Information";
	public static final String 	MANAGE_SITE_SETTINGS_VERIFY 	= "link=General Settings";
	public static final String 	MANAGE_THEMES_PAGE_VERIFY 		= "link=Themes";
	public static final String 	MANAGE_WORKFLOWS_PAGE_VERIFY 	= "link=Workflows";
	public static final String 	MANAGE_MODULES 					= "link=Modules";
	public static final String 	MANAGE_SYSTEM_INFO 				= "link=System Information";
	public static final String 	MANAGE_CATEGORIES 				= "link=Categories";	
	public static final String  MANAGE_CONTENTGENERATION 		= "link=Content Generation";
	public static final String 	MANAGE_CONTENT 					= "link=Content";
	public static final String 	MANAGE_CONTENT_TYPES	     	= "link=Content Types";
	public static final String  MANAGE_IDE						= "link=IDE";
	public static final String	MANAGE_WORDPRESS				= "link=WordPress Import";
	public static final String  SITE_BRANCHING	      			= "link=Sites and Branches";
	public static final String 	LIVE_LINK						= "//div/div/div/div/ul/span/li/span"; 
	//public static final String  LIVE_LINK  						= "css=.index-action .p4cms-dock .manage-toolbar .manage-toolbar-container .navigation .left-group .menu-node .menu-button .type-heading";
	public static final String 	ADD_SITE   						= "link=Add Site";
	

	// MANAGE USER CONSTANTS
	public static final String 	MANAGE_USERS_ANON_ROLE_CHECKED 			= "link=Users";
	public static final String 	MANAGE_USERS_PAGE_VERIFY 				= "link=Users";
	public static final String 	MANAGE_USERS_ADD_USER_BUTTON_VERIFY 	= "link=Users";
	public static final String 	MANAGE_USERS_ADD_USER 					= "link=Users";
	public static final String 	MANAGE_USERS 							= "link=Users";
	public static final String 	MANAGE_USERS_DELETE_USER 				= "link=Users";
	public static final String 	MANAGE_USERS_ADD_USER_DIALOG_VERIFY 	= "link=Users";
	public static final String 	MANAGE_USERS_ADMIN_CHECKED_VERIFY 		= "link=Users";
	public static final String 	MANAGE_USERS_DELETE_USER_DIALOG_VERIFY 	= "link=Users";
	public static final String 	MANAGE_USERS_DOJO_GRID_VERIFY 			= "link=Users";
	public static final String 	MANAGE_USERS_ENTRIES_VERIFY 			= "link=Users";
	public static final String 	MANAGE_USERS_EDIT_DIALOG_VERIFY 		= "link=Users";
	
	public static final String  WORD_PASTE1 = " 1) - install the Perforce plugin and make a P4 connection inside the IDE 2) Sample code to write results to a file: 3) Results.txt 4) Quart";
	public static final String  WORD_PASTE2 = "ui: Mozilla 5.0 product:p4cms clientcodeline: 2012.1.PREP-TEST_ONLY servercodeline: 2012.1.BETA clientchange: 410798  serverchange: 411339  clientos: MACOSX105X86 serveros: LINUX26X86 suite:smoke";
	
	
	// PAGE TIMEOUT
	public static final String PAGE_TIMEOUT = "30000";
	
	// URL 
	public static final String chron_srv_lin2a_qa_perforce_com_URL = "localhost";
	 
	// MISC
	public static final String PAGE_SLEEP = "Thread.sleep(3000)";
	//public static final String PAGE_WAIT = "selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT)";
	
	// SITE MAP
	public static final String SITE_MAP_TITLE = "Sitemap - Perforce Chronicle";
	public static final String SITE_MAP_LINK_XPATH = "//div[@class='wppHeaderStroke']/h3/a";

}

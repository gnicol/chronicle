package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


// This code logs in and clicks on the Manage --> Manage content and verifies that the Manage content title appears

public class ManageMenusAllElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageMenusAllElementsVerify";

	@BeforeClass
	@Parameters({ "baseurl", "redirecturl", "usergroup" })
	public void storeBaseURL(String baseurl, String redirecturl,
			String usergroup) {
		this.baseurl = baseurl;
		this.redirecturl = redirecturl;
		this.usergroup = usergroup;
	}

	@DataProvider(name = "Users")
	public Object[][] createData() throws Exception {
		Object[][] retObjArr = getDataArray("data/TestData.xls", "Users", usergroup);
		return (retObjArr);
	}

	@Test(dataProvider = "Users")
	public void validate(String username, String password)
			throws Exception {

		// Login to Chronicle
      		chronicleLogin(username, password);
	      waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		
		// Verify Chronicle home page elements 
		ManageMenusAllElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void ManageMenusAllElementsVerify() throws Exception {
		
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
		
		// click add menu button
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		selenium.click("//div[4]/div/div[2]/div[3]/div/span/span/span/span[3]");
		selenium.click("//input[@value='Add Menu']");
		
		 String quart_detailid   = "7039";
		  String quart_testname   = "AddMenuButtonText";
		  String quart_description= "verify add menu button";
		
		// Write to file for checking manage content type page
		  if (selenium.isTextPresent(("Add Menu")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		

		  
		// click menu item button
		  selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
		  waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		  selenium.click("//div[4]/div/div[2]/div[3]/div/span[2]/span/span/span[3]");
		  //selenium.click("css=input.dijitOffScreen");	
			selenium.click("//input[@value='Add Menu Item']");
			Thread.sleep(2000);
			
			 quart_detailid   = "7040";
			  quart_testname   = "AddMenuItemButtonText";
			  quart_description= "verify add menu item button";
			
			// Write to file for checking manage content type page
			  if (selenium.isTextPresent(("Add Menu Item")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		  
		  
		// click reset to defaults button
			  selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			  waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			  selenium.click("id=dijit_form_Button_2_label");
				selenium.click("//input[@value='Reset to Defaults']");
			Thread.sleep(3000);
			
			 quart_detailid   = "6595";
			  quart_testname   = "ResetMenuButton";
			  quart_description= "verify reset menu button";
			
			// Write to file for checking manage content type page
			  if (selenium.isTextPresent(("reset all menus")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		    
		  
			// search for footer
			  selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			  waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.type("id=search-query", "footer");
			Thread.sleep(2000);
			
			 quart_detailid   = "6901";
			  quart_testname   = "FooterText";
			  quart_description= "verify footer text in grid";
			
			// Write to file for checking manage content type page
			  if (selenium.isTextPresent(("Footer")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		    
			  quart_detailid   = "6901";
			  quart_testname   = "ManagementFooter";
			  quart_description= "verify management footer";
			
			// Write to file for checking manage content type page
			  if (selenium.isTextPresent(("Management Footer")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				   
		  
			  
			// search for nonexistent entry
			  selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			  waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.type("id=search-query", "blah");
			Thread.sleep(2000);
			
			 quart_detailid   = "6900";
			  quart_testname   = "0entries";
			  quart_description= "verify 0 entries";
			
			// Write to file for checking manage content type page
			  if (selenium.isTextPresent(("0")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }  
			  
			  
				// check primary
			  selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			  waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			  selenium.click("id=menu-display-primary");
			  Thread.sleep(2000);
			
			  
			   quart_detailid   = "6596";
				quart_testname   = "entries"; 
			    quart_description= "verify entries";
				
			    String getEntriesText = selenium.getText("//div[4]/div/div[2]/div[3]/span/span[2]");
			  
				boolean getEntriesTextTrue = getEntriesText.equals("entries");
			
				if (getEntriesTextTrue)  
				// Write to file for checking manage content type page
				//if (selenium.isTextPresent(("entries")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			  
			  
			 quart_detailid   = "6580";
			  quart_testname   = "PrimaryText";
			  quart_description= "verify primary";
			
			// Write to file for checking manage content type page
				if (selenium.isTextPresent("Primary"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }  
		    
				
			quart_detailid   = "6580";
			  quart_testname   = "HomeText";
			  quart_description= "verify Home";
			
			// Write to file for checking manage content type page
				if (selenium.isTextPresent("Home"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }   
		  
		  
			quart_detailid   = "6580";
			  quart_testname   = "CategoriesText";
			  quart_description= "verify Categories";
			
			// Write to file for checking manage content type page
				if (selenium.isTextPresent("Categories"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }   
			  
			  
			  
			quart_detailid   = "6580";
			  quart_testname   = "Login/logoutText";
			  quart_description= "verify login/logout";
			
			// Write to file for checking manage content type page
				if (selenium.isTextPresent("Login/Logout"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			// uncheck primary
			selenium.click("id=menu-display-primary");
			Thread.sleep(2000);
		  
			
			
			
		  // click sidebar
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.click("id=menu-display-sidebar");
			Thread.sleep(2000);
			
			quart_detailid   = "6582";
			  quart_testname   = "SidebarText";
			  quart_description= "verify sidebar";
			
			// Write to file for checking manage content type page
				if (selenium.isTextPresent("Sidebar"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		 // uncheck sidebar
			selenium.click("id=menu-display-sidebar");
			Thread.sleep(1000);
				
				
			
			
			// click sitemap 
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.click("id=menu-display-sitemap");
			Thread.sleep(2000);
			
			quart_detailid = "6590";
			quart_testname = "SitemapText";
			quart_description = "verify sitemap";
			
			// Write to file
			if (selenium.isTextPresent("Sitemap"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description ); }
				
			// uncheck sitemap
			selenium.click("id=menu-display-sitemap");
			Thread.sleep(1000);
			
			
			
		
			// click footer checkbox
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.click("id=menu-display-footer");
			Thread.sleep(2000);
			
			quart_detailid = "6583";
			quart_testname = "FooterGrid";
			quart_description = "verify footer"; 
			
			// Write to file
			if (selenium.isTextPresent("Footer"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
			
			quart_detailid = "6583";
			quart_testname = "HomeGrid";
			quart_description = "verify home"; 
			
			if (selenium.isTextPresent("Home")) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6583";
			quart_testname = "SitemapGrid";
			quart_description = "verify sitemap"; 
			
			if (selenium.isTextPresent("Sitemap")) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
				
			quart_detailid = "6583";
			quart_testname = "Login/logoutGrid";
			quart_description = "verify login/logout on grid"; 
			
			if (selenium.isTextPresent("Login/Logout")) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			// uncheck footer
			selenium.click("id=menu-display-footer");
			Thread.sleep(1000);
			
			
			
			
			
			/*// check management toolbar
				selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
				Thread.sleep(2000);
				selenium.click("id=menu-display-manage-toolbar");
				Thread.sleep(2000);
				
				quart_detailid = "0";
				quart_testname = "management toolbar";
				quart_description = "verify mgmt toolbar"; 
				
				if (selenium.isTextPresent("Management Toolbar")) 
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
				
				
				// check management footer
				quart_detailid = "0";
				quart_testname = "site configuration";
				quart_description = "verify site configuration"; 
				
				if (selenium.isTextPresent("Site Configuration")) 
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
				
				
				// check management footer
				quart_detailid = "0";
				quart_testname = "grid user management";
				quart_description = "verify user management"; 
				
				if (selenium.isTextPresent("User Management")) 
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
				
				
				// uncheck management footer
				selenium.click("id=menu-display-manage-toolbar");
				Thread.sleep(2000);
						*/
			
			
			// check add menu button
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
			quart_detailid = "6594";
			quart_testname = "AddMenuButton";
			quart_description = "verify add menu button"; 
			
			if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_0_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			
			// check add menu item button
				selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
							
				quart_detailid = "7545";
				quart_testname = "AddMenuItemButton";
				quart_description = "verify add menu item button"; 
				
				if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_1_label')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
	
			
			
			
				// check add menu item button
				selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
							
				quart_detailid = "7545";
				quart_testname = "AddMenuItemButton1";
				quart_description = "verify add menu item button"; 
				
				if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_1_label')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 	
			
			
			// check management footer
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.click("id=menu-display-manage-footer");
			Thread.sleep(2000);
			
			quart_detailid = "6591";
			quart_testname = "GridMgmtFooter";
			quart_description = "verify mgmt footer"; 
			
			if (selenium.isTextPresent("Management Footer")) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			// check management footer
			quart_detailid = "6591";
			quart_testname = "Helplink";
			quart_description = "verify help link"; 
			
			if (selenium.isTextPresent("Help Link")) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			// check management footer
			quart_detailid = "6591";
			quart_testname = "Contactus";
			quart_description = "verify contact us"; 
			
			if (selenium.isTextPresent("Contact Us")) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			// uncheck management footer
			selenium.click("id=menu-display-manage-footer");
			Thread.sleep(2000);
			
			
	
			
			// click action
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.click("id=type-display-P4Cms_Navigation_Page_Mvc");
			Thread.sleep(3000);
			
			quart_detailid = "10075";
			quart_testname = "home text";
			quart_description = "verify home text";
			
			//Write file
			if (selenium.isTextPresent("Home"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			quart_detailid = "10012";
			quart_testname = "users text";
			quart_description = "verify users text";
			
			//Write file
			if (selenium.isTextPresent("Users"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "10007";
			quart_testname = "roles text";
			quart_description = "verify roles";
			
			//Write file
			if (selenium.isTextPresent("Roles"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "10011";
			quart_testname = "permissions text";
			quart_description = "verify permissions text";
			
			//Write file
			if (selenium.isTextPresent("Permissions"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			// uncheck action
			selenium.click("id=type-display-P4Cms_Navigation_Page_Mvc");
			Thread.sleep(2000);
			
			
			
			
			// click action
				selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				selenium.click("id=type-display-P4Cms_Navigation_Page_Content");
				Thread.sleep(2000);

				quart_detailid = "6581";
				quart_testname = "Grid0Entries";
				quart_description = "verify 0 entries";
				
				if (selenium.isTextPresent("0"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
				
				// uncheck action
				selenium.click("id=type-display-P4Cms_Navigation_Page_Content");
				Thread.sleep(2000);
				
		
			
			// check Heading
				selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			selenium.click("id=type-display-P4Cms_Navigation_Page_Heading");
			Thread.sleep(3000);
			
			quart_detailid = "6588";
			quart_testname = "12entries";
			quart_description = "verify 12 entries";
			
			//Write file
			if (selenium.isTextPresent("12"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6588";
			quart_testname = "ManageText";
			quart_description = "verify manage text";
			
			//Write file
			if (selenium.isTextPresent("Manage"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6588";
			quart_testname = "ContentMgmt.";
			quart_description = "verify content mgmt.";
			
			//Write file
			if (selenium.isTextPresent("Content Management"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6588";
			quart_testname = "GridSiteConfig";
			quart_description = "verify site config";
			
			//Write file
			if (selenium.isTextPresent("Site Configuration"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			
			quart_detailid = "6588";
			quart_testname = "GridUserMgmt";
			quart_description = "verify user mgmt";
			
			//Write file
			if (selenium.isTextPresent("User Management"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			
			quart_detailid = "6588";
			quart_testname = "SystemText";
			quart_description = "verify system";

			//Write file
			if (selenium.isTextPresent("System"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			
			quart_detailid = "6588";
			quart_testname = "EditText";
			quart_description = "verify edit";
			
			//Write file
			if (selenium.isTextPresent("Edit"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			
			quart_detailid = "6588";
			quart_testname = "AddText";
			quart_description = "verify add text";
			
			//Write file
			if (selenium.isTextPresent("Add"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			
			quart_detailid = "6588";
			quart_testname = "HistoryText";
			quart_description = "verify history";
			
			//Write file
			if (selenium.isTextPresent("History"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			
			quart_detailid = "6588";
			quart_testname = "WidgetsText";
			quart_description = "verify widgets";
			
			//Write file
			if (selenium.isTextPresent("Widgets"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			selenium.click("id=type-display-P4Cms_Navigation_Page_Heading");
			Thread.sleep(2000);
			
			
				
			
			// click link
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			quart_detailid = "10009";
			quart_testname = "0EntriesUrl";
			quart_description = "verify 0 entries for url";
			
			selenium.click("id=type-display-Zend_Navigation_Page_Uri");
			Thread.sleep(2000);
			
			//Write file
			if (selenium.isTextPresent("0"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			selenium.click("id=type-display-Zend_Navigation_Page_Uri"); 
			Thread.sleep(2000);
			
			
			
			
			
			
			// click menu
			selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			quart_detailid = "6592";
			quart_testname = "PrimaryMenuCheckbox";
			quart_description = "verify primary menu";
			
			selenium.click("id=type-display-P4Cms_Menu");
			Thread.sleep(2000);
			
			//Write file
			if (selenium.isTextPresent("Primary"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6592";
			quart_testname = "SidebarTextForPrimary";
			quart_description = "verify sidebar";
			
			//Write file
			if (selenium.isTextPresent("Sidebar"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6592";
			quart_testname = "SitemapForPrimary";
			quart_description = "verify sitemap";
			
			//Write file
			if (selenium.isTextPresent("Sitemap"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6592";
			quart_testname = "FooterForPrimary";
			quart_description = "verify footer";
			
			//Write file
			if (selenium.isTextPresent("Footer"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6592";
			quart_testname = "MgmtToolbarForPrimary";
			quart_description = "verify mgmt toolbar";
			
			//Write file
			if (selenium.isTextPresent("Management Toolbar"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 
			
			
			quart_detailid = "6592";
			quart_testname = "MgmtFooterForPrimary";
			quart_description = "verify mgmt footer";
			
			//Write file
			if (selenium.isTextPresent("Management Footer"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
			
			selenium.click("id=type-display-P4Cms_Menu"); 
			Thread.sleep(1000);
		
			/*// check active users profile
			selenium.click("id=type-display-P4Cms_Navigation_Page_Dynamicuserprofile");
			Thread.sleep(2000); 
			
			quart_detailid = "6593";
			quart_testname = "grid elements";
			quart_description = "verify user profile";
			
			//Write file
			if (selenium.isTextPresent("Active User's Profile"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	*/
			
			
		//Back to Website
		backToHome();
	}
}


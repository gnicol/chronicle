package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> system info and verifies the title

public class ManageSiteSettingsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageSiteSettingsVerify";

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
		ManageSiteSettingsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void ManageSiteSettingsVerify() throws Exception {
		 
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		//writeFile1("\nskipped: 7780", "", "");
		
		// **** verify description **** // 
		String quart_detailid   = "7778";
		 String quart_testname   = "DescriptionVerify";
		 String quart_description= "site settings description verify";
		// Write to file for checking manage content type page
				if (selenium.isTextPresent("Perforce Chronicle Website"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }

		
				
		// **** verify growl **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		Thread.sleep(2000);	
			// Write to file for checking manage content type page
			 quart_detailid   = "7773";
			 quart_testname   = "GrowlVerify";
			 quart_description= "site settings growl verify";
			 // click save button
				selenium.click("id=save_label");
				Thread.sleep(2000);
			if (selenium.isVisible("xpath=//*[@id='p4cms-ui-notices']"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

			 quart_detailid   = "7773";
			  quart_testname   = "GrowlMessage";
			  quart_description= "site settings growl verify";
				selenium.click("id=save_label");
				Thread.sleep(2000);
			if (selenium.isVisible("xpath=//*[@class='message']"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		
		//**** verify site settings name **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		Thread.sleep(2000);
		// check name is required
		selenium.type("id=title", "");
		selenium.click("name=save");
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		//writeFile1("\nskipped: 7775", "", "");
		 quart_detailid   = "7775";
		  quart_testname   = "TitleText";
		  quart_description= "site settings error verify";
		if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		selenium.type("id=description", "http://www.chroniclerocks.com");
		selenium.click("name=save");
		selenium.click("id=save_label");
		
		Thread.sleep(3000);
		 quart_detailid   = "7775";
		  quart_testname   = "URL";
		  quart_description= "site settings url verify";
		if (selenium.isTextPresent("http://www.chroniclerocks.com"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		 quart_detailid   = "7775";
		  quart_testname   = "TitleElement";
		  quart_description= "site settings title verify";
		// Write to file for checking manage content type page
		if (selenium.isElementPresent("xpath=//input[@id='title']"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		
		
		
		
		// **** verify site settings page **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		assertTrue(selenium.isTextPresent("General Settings"));
		
		//writeFile1("\nskipped: 9000", "", "");
		 quart_detailid   = "7769";
		  quart_testname   = "PageVerify";
		  quart_description= "site settings page verify";
		// Write to file for checking manage content type page
		if (selenium.isTextPresent("General Settings"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		
		
		
		// **** verify robots help **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		 quart_detailid   = "7979";
		  quart_testname   = "RobotsHelpVerify";
		  quart_description= "site settings robots help verify";
		// Write to file for checking manage content type page
		if (selenium.isTextPresent("robots.txt"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	


		
		
		// **** vierfy robots value **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// enter info for robots and save
		selenium.type("id=robots", "User-agent: *\nDisallow:");
		
		selenium.click("name=save");
		selenium.click("id=save_label");
		Thread.sleep(4000);
		
		//writeFile1("\nskipped: 7853", "", "");

		 quart_detailid   = "7853";
		  quart_testname   = "RobotsValueVerify";
		  quart_description= "site settings robots value verify";
		// Write to file for checking manage content type page
		 if (selenium.isTextPresent("User-agent: *"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
         else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		
		
		 // verify short summary
		// Click on Manage --> Manage content types
			manageMenu();
			selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			
			 quart_detailid   = "8958";
			  quart_testname   = "SummaryText";
			  quart_description= "site settings summary verify";
			
			// Write to file for checking manage content type page
			if (selenium.isTextPresent("Enter a short summary of your site."))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

			quart_detailid   = "8958";
			  quart_testname   = "SummaryMoreText";
			  quart_description= "site settings summary verify";
			
			// Write to file for checking manage content type page
			if (selenium.isTextPresent("This summary will appear in meta description tags for non-content pages."))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		 
		 
	
		// **** verify robots **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		 quart_detailid   = "7854";
		  quart_testname   = "RobotsVerify";
		  quart_description= "site settings robots verify";
		
		// Write to file for checking manage content type page
		if (selenium.isTextPresent("Provide the contents for the site's robots.txt file."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	

		

		
		// **** verify server help **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// click on server help and verify
		selenium.click("link=exact:(?)");
		Thread.sleep(2000);
					
		//writeFile1("\nskipped: 7777", "", "");

		
		// get url string and match to baseurl	
		String siteSettingsURL = baseurl;
		String siteSettings = "/docs/manual/sites.management.html#robots";
		String delims_for_url1    = "[http://baseurl]";
		
		String matchURL[] = siteSettingsURL.split(delims_for_url1);
		siteSettingsURL = matchURL[0] + matchURL[1] + siteSettings;	
 					
		//writeFile(siteSettings,siteSettingsURL, delims_for_url1,"","");

	   quart_detailid   = "6097";
	   quart_testname   = "LearnMoreInManagingChronicle";
	   quart_description= "Check Homepage elements - verify learn more managing chronicle";
			//if (selenium.isVisible("//a[@name='adminguide']"))
	   //assertTrue(selenium.isElementPresent(("//a[contains(@name, 'adminguide')]"))); 

		 quart_detailid   = "7777";
		  quart_testname   = "ServerHelpVerify";
		 quart_description= "site settings server help verify";
		// Write to file for checking manage content type page
		 
		 if (siteSettings.equalsIgnoreCase(siteSettingsURL))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
		
		
		
		// **** verify site address required **** //
		
		/*// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		Thread.sleep(2000);
		assertTrue(selenium.isElementPresent(("//textarea[contains(@id, 'urls')]")));  
		
	 	// clear address field and place a space to verify it's required when saving
		selenium.type("id=urls", "");
		selenium.click("name=save");
		selenium.click("id=save_label");
		Thread.sleep(3000);
		
		//writeFile1("\nskipped: 7776", "", "");
		  quart_detailid   = "7776";
		  quart_testname   = "SiteAddressRequiredVerify";
		  quart_description= "site settings site address required";
		// Write to file for checking manage content type page
		  if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
		
		// enter info
		selenium.type("id=urls", baseurl);
		selenium.click("name=save");
		selenium.click("id=save_label");
		Thread.sleep(3000);
		  quart_detailid   = "7776";
		  quart_testname   = "SiteAddressRequiredVerify";
		  quart_description= "site settings site address required";
		// Write to file for checking manage content type page
		 if (selenium.isTextPresent(baseurl))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	*/
			
		 
		 
		
	/*	// **** verify site address **** //
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		Thread.sleep(2000);
		
		  quart_detailid   = "7780";
		  quart_testname   = "SiteAddressVerify";
		  quart_description= "site settings site address";
		
		// Write to file for checking manage content type page
		if (selenium.isTextPresent("Provide a list of urls for which this site will be served."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		*/
	
		//Back to Website
		backToHome();
	}
}


package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;

//This code clicks on manage --> modules and verifies the analytics title

public class ManageModulesVerify extends shared.BaseTest {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="ManageModulesVerify";
	
	public static String clientCodeline = "";

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
		manageModulesVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");

		waitForElements("link=Login");  

	}
	
	public void manageModulesVerify() throws Exception {
		// go to manage modules
		
		String versionString = getClientCodeline(clientCodeline);
		
		manageMenu();
		selenium.click(CMSConstants.MANAGE_MODULES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
		
		String quart_detailid   = "9842";
		String  quart_testname   = "ManageModulesTypetext"; 
		String  quart_description= "verify type text";
		// verify delete user dialog
		// check to see if user selected is checked and write to file
		if (selenium.isTextPresent("Type"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		
		
		 quart_detailid   = "2299";
		  quart_testname   = "ManageModulesAnyTypetext";
		  quart_description= "verify any type text";
		if (selenium.isTextPresent("Any Type"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		 quart_detailid   = "2325";
		  quart_testname   = "ManageModulesOnlyCoreText";
		  quart_description= "verify only core text";
		if (selenium.isTextPresent(("Only Core")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

	
		 quart_detailid   = "2324";
		  quart_testname   = "ManageModulesOnlyOptionaltext";  
		  quart_description= "verify only optional text";
		if (selenium.isTextPresent("Only Optional"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		 quart_detailid   = "9841";
		  quart_testname   = "ManageModulesSearchtext";
		  quart_description= "verify search text";
		if (selenium.isTextPresent("Search"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	
		 quart_detailid   = "2327";
		  quart_testname   = "ManageModulesSearchForm";
		  quart_description= "verify search form";
			if (selenium.isElementPresent(("//input[contains(@id, 'search-query')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
			 quart_detailid   = "2326";
			  quart_testname   = "ManageModulesAnyStatustext";
			  quart_description= "verify any status text";
				if (selenium.isTextPresent("Any Status"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			 quart_detailid   = "6849";
			  quart_testname   = "ManageModulesOnlyEnabledtext";
			  quart_description= "verify only enabled text";
				if (selenium.isTextPresent("Only Enabled"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			
		
			 quart_detailid   = "2297";
			  quart_testname   = "ManageModulesOnlyDisabledtext";
			  quart_description= "verify only disabled text";
				if (selenium.isTextPresent("Only Disabled"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "9844";
				  quart_testname   = "ManageModulesTagstext";
				  quart_description= "verify tags text";
					if (selenium.isTextPresent("Tags"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				

					
		
		// filter analytics module
		selenium.click("id=tagFilter-display-analytics");
		Thread.sleep(2000);
		
		// verify elements
		//writeFile1("\nskipped: 6868", "", "");
		
		 quart_detailid   = "9845";
		  quart_testname   = "ManageModulesAnalyticstext";
		  quart_description= "verify analytics text";
		// verify delete user dialog
		// check to see if user selected is checked and write to file
		if (selenium.isTextPresent("Analytics"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		
		
		 quart_detailid   = "9846";
		  quart_testname   = "ManageModulesAnalyticsGoogletext";
		  quart_description= "verify analytics Google text";
		if (selenium.isTextPresent("Allows a user to embed Google Analytics code in each page."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		 quart_detailid   = "6868";
		  quart_testname   = "ManageModulesAnalyticsIcon";
		  quart_description= "verify analytics icon";
		if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/analytics/resources/images/icon.png')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		 quart_detailid   = "9848";
		  quart_testname   = "ManageModulesAnalyticsPerforcetext";
		  quart_description= "verify perforce software text";
		if (selenium.isTextPresent("Perforce Software"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		 quart_detailid   = "9850";
		  quart_testname   = "ManageModulesAnalyticsStatusDisabled";
		  quart_description= "verify status disabled";
		if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		 quart_detailid   = "9839";
		  quart_testname   = "ManageModulesAnalyticsStatusText";
		  quart_description= "verify status text";
		if (selenium.isTextPresent(("Status")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		 quart_detailid   = "9847";
		  quart_testname   = "AnalyticsVersion";
		  quart_description= "verify analytics version";
		if (selenium.isTextPresent((versionString)))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		 quart_detailid   = "6864";
		  quart_testname   = "ManageModulesAnalyticSupport";
		  quart_description= "verify support link";
		if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "6866";
		  quart_testname   = "ManageModulesAnalyticWWW";
		  quart_description= "verify WWW link";
		if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		selenium.click("css=div.row-id-analytics span.dijitDropDownButton");
		Thread.sleep(4000);
		
//			quart_detailid   = "9849";
//		  quart_testname   = "ManageModulesAnalyticsEnable";
//		  quart_description= "verify enable link";
//		if (selenium.isTextPresent(("enable the Analytics module?")))
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//		
//		quart_detailid   = "7069";
//		  quart_testname   = "ManageModulesAnalyticsEnableText";
//		  quart_description= "verify enable text";
//		if (selenium.isTextPresent(("enable the Analytics module?")))
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//		
//     	 
//		quart_detailid   = "6857";
//		  quart_testname   = "ManageModulesAnalyticsEnableLink";
//		  quart_description= "verify enable link";
//		if (selenium.isTextPresent(("enable the Analytics module?")))
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_0-button-action_label')]");  
		Thread.sleep(4000);
		
		
		selenium.clickAt("css=div.row-id-analytics span.dijitButtonContents","");
		Thread.sleep(2000);
		
		quart_detailid   = "7060";
		  quart_testname   = "ManageModulesAnalyticsConfigureLink";
		  quart_description= "verify configure link";
		if (selenium.isTextPresent(("Analytics Configuration")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		// verify the analytics configuration
		
		quart_detailid   = "7074";
		  quart_testname   = "ManageModulesAnalyticsConfigurText";
		  quart_description= "verify configure text";
		if (selenium.isTextPresent(("Analytics Configuration")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "9981";
		  quart_testname   = "ManageModulesAnalyticsSiteProfile";
		  quart_description= "verify configure link";
		if (selenium.isTextPresent(("Site Profile Id")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7077";
		  quart_testname   = "ManageModulesAnalyticsTracking";
		  quart_description= "verify tracking link";
		if (selenium.isTextPresent(("Tracking Variables")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "9983";
		  quart_testname   = "ManageModulesAnalyticsText";
		  quart_description= "verify text";
		if (selenium.isTextPresent(("Your Google Analytics site profile identifier has the format UA-XXXXX-X.")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "9984";
		  quart_testname   = "ManageModulesAnalyticsActiveUser";
		  quart_description= "verify active user text";
		if (selenium.isTextPresent(("Include Active User")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "9986";
		  quart_testname   = "ManageModulesAnalyticsActiveRole";
		  quart_description= "verify active role text";
		if (selenium.isTextPresent(("Include Active Role")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	
		quart_detailid   = "9988";
		  quart_testname   = "ManageModulesAnalyticsActiveContentId";
		  quart_description= "verify content id text";
		if (selenium.isTextPresent(("Include Content Id")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "9989";
		  quart_testname   = "ManageModulesAnalyticsActiveContentType";
		  quart_description= "verify content type text";
		if (selenium.isTextPresent(("Include Content Type")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "9982";
		  quart_testname   = "ManageModulesAnalyticsSearchForm";
		  quart_description= "verify search form";
			if (selenium.isElementPresent(("//input[contains(@id, 'accountNumber')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			quart_detailid   = "7083";
			  quart_testname   = "ManageModulesAnalyticsCheckbox1";
			  quart_description= "verify checkbox1";
				if (selenium.isElementPresent(("//input[contains(@id, 'customVars-userId')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
			quart_detailid   = "9985";
			  quart_testname   = "ManageModulesAnalyticsCheckbox2";
			  quart_description= "verify checkbox2";
				if (selenium.isElementPresent(("//input[contains(@id, 'customVars-userRole')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
			quart_detailid   = "9987";
			  quart_testname   = "ManageModulesAnalyticsCheckbox3";
			  quart_description= "verify checkbox3";
				if (selenium.isElementPresent(("//input[contains(@id, 'customVars-contentId')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			quart_detailid   = "7084";
			  quart_testname   = "ManageModulesAnalyticsCheckbox4";
			  quart_description= "verify checkbox4";
				if (selenium.isElementPresent(("//input[contains(@id, 'customVars-contentType')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "7081";
				  quart_testname   = "ManageModulesAnalyticsSaveButton";
				  quart_description= "verify save button";
					if (selenium.isElementPresent(("//span[contains(@id, 'save')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
		manageMenu();
		selenium.click(CMSConstants.MANAGE_MODULES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// filter analytics module
		selenium.click("id=tagFilter-display-analytics");
		Thread.sleep(4000);
		
		
		selenium.click("css=div.row-id-analytics span.dijitDropDownButton");
		Thread.sleep(4000);	
		
		selenium.click(("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_10-button-action_label')]")); 
		Thread.sleep(4000);
		
		
		
		
		// filter navigation module
		selenium.click("id=tagFilter-display-navigation");
		Thread.sleep(2000);
		
		
		 quart_detailid   = "9871";
		  quart_testname   = "ManageModulesCategorytext";
		  quart_description= "verify category text";
		// verify delete user dialog
		//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
		// check to see if user selected is checked and write to file
		if (selenium.isTextPresent("Category"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		
		
		 quart_detailid   = "9870";
		  quart_testname   = "ManageModulesCategorytext";
		  quart_description= "verify Category text";
		if (selenium.isTextPresent("Provides data categorization in flat or nested hierarchies."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		 quart_detailid   = "6116";
		  quart_testname   = "ManageModulesCategoryIcon";
		  quart_description= "verify Category icon";
		if (selenium.isElementPresent(("//img[contains(@src, '/application/category/resources/images/icon.png')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		 quart_detailid   = "9873";
		  quart_testname   = "ManageModulesCategoryPerforcetext";
		  quart_description= "verify perforce software text";
		if (selenium.isTextPresent("Perforce Software"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
		
		
		 quart_detailid   = "9872";
		  quart_testname   = "CategoryVersion";
		  quart_description= "verify Category version";
		if (selenium.isTextPresent((versionString)))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		 quart_detailid   = "2332";
		  quart_testname   = "ManageModulesCategoryupport";
		  quart_description= "verify support link";
		if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "2331";
		  quart_testname   = "ManageModulesCategoryWWW";
		  quart_description= "verify WWW link";
		if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			quart_detailid   = "9874";
		  quart_testname   = "ManageModulesCategoryEnable";
		  quart_description= "verify enable link";
		if (selenium.isTextPresent(("Enabled")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
		
		selenium.click("id=tagFilter-display-navigation");
		Thread.sleep(2000);
		
		
		
		// click on social for comment module
		manageMenu();
		selenium.click(CMSConstants.MANAGE_MODULES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		
		selenium.type("id=search-query", "comment");
		Thread.sleep(3000);
		
		
		 quart_detailid   = "9892";
		  quart_testname   = "ManageModulesCommentstext";
		  quart_description= "verify comments text";
		// verify delete user dialog
		//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
		// check to see if user selected is checked and write to file
		if (selenium.isTextPresent("Comment"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		
		
		 quart_detailid   = "9893";
		  quart_testname   = "ManageModulesCommentstext";
		  quart_description= "verify Comments text";
		if (selenium.isTextPresent("Provides facility for user comments on content."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		 quart_detailid   = "6867";
		  quart_testname   = "ManageModulesCommentsIcon";
		  quart_description= "verify Comments icon";
		if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/comment/resources/images/icon.png')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		 quart_detailid   = "9895";
		  quart_testname   = "ManageModulesCommentsPerforcetext";
		  quart_description= "verify Comments perforce software text";
		if (selenium.isTextPresent("Perforce Software"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
		
		
		 quart_detailid   = "9894";
		  quart_testname   = "CommentsVersion";
		  quart_description= "verify Comments version";
		if (selenium.isTextPresent((versionString)))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		 quart_detailid   = "6863";
		  quart_testname   = "ManageModulesCommentsupport";
		  quart_description= "verify Comments support link";
		if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "6865";
		  quart_testname   = "ManageModulesCommentsWWW";
		  quart_description= "verify Comments WWW link";
		if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		 quart_detailid   = "6859";
		  quart_testname   = "ManageModulesCommentsStatusDisabled";
		  quart_description= "verify Comments status disabled";
		if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		
		selenium.click("css=div.row-id-comment span.dijitDropDownButton");
		Thread.sleep(3000);
		
		manageMenu();
		selenium.click(CMSConstants.MANAGE_MODULES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// enable comments module
		selenium.clickAt("css=div.row-id-comment span.dijitDropDownButton","");
		Thread.sleep(3000);
		
		selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_1-button-action')]");  
     	Thread.sleep(3000);
		

		selenium.type("id=search-query", "comment");
		Thread.sleep(3000);
		
		if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
		{ System.out.println("Comment module already enabled"); }
	
			else { // comment the IDE module
				
			selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			
			// Comment IDE
			selenium.clickAt("css=div.row-id-comment span.dijitDropDownButton","");
			Thread.sleep(3000);
			selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_1-button-action')]");  
			Thread.sleep(3000);
		}
		
 
     	selenium.type("id=search-query", "comment");
		Thread.sleep(3000);
		
		
		quart_detailid   = "7056";
		  quart_testname   = "ManageModulesCommentsStatusEnabled";
		  quart_description= "verify Comments status enabled";
		if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		// disable comments module
		selenium.click("css=div.row-id-comment span.dijitDropDownButton");
		Thread.sleep(3000);		
		
		
		// click on social for comment module
		selenium.type("id=search-query", "content");
		Thread.sleep(3000);
				
				
				 quart_detailid   = "9879";
				  quart_testname   = "ManageModulesContenttext";
				  quart_description= "verify content text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Content"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9876";
				  quart_testname   = "ManageModulesContenttext";
				  quart_description= "verify Content text";
				if (selenium.isTextPresent("Provides content presentation and management facilities."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6117";
				  quart_testname   = "ManageModulesContentsIcon";
				  quart_description= "verify Contents icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/content/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9878";
				  quart_testname   = "ManageModulesContentsPerforcetext";
				  quart_description= "verify Contents perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9877";
				  quart_testname   = "ManageModulesContentsVersion";
				  quart_description= "verify Contents version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2334";
				  quart_testname   = "ManageModulesContentsupport";
				  quart_description= "verify Contents support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2333";
				  quart_testname   = "ManageModulesContentsWWW";
				  quart_description= "verify Contents WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					quart_detailid   = "9875";
				  quart_testname   = "ManageModulesContentsEnable";
				  quart_description= "verify Contents enable link";
				if (selenium.isTextPresent(("Enabled")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
				
				
				// cron module
			
				selenium.type("id=search-query", "cron");
				Thread.sleep(2000);
				
				
				 quart_detailid   = "9926";
				  quart_testname   = "ManageModulesCrontext";
				  quart_description= "verify cron text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Cron"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9927";
				  quart_testname   = "ManageModulesCrontext";
				  quart_description= "verify Cron text";
				if (selenium.isTextPresent("Provides facility to run periodic tasks."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "7772";
				  quart_testname   = "ManageModulesCronIcon";
				  quart_description= "verify Cron icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/cron/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9929";
				  quart_testname   = "ManageModulesCronPerforcetext";
				  quart_description= "verify Cron perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9928";
				  quart_testname   = "ManageModulesCronVersion";
				  quart_description= "verify Cron version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "7770";
				  quart_testname   = "ManageModulesCronsupport";
				  quart_description= "verify Cron support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "7771";
				  quart_testname   = "ManageModulesCronWWW";
				  quart_description= "verify Cron WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					quart_detailid   = "9930";
				  quart_testname   = "ManageModulesContentsEnable";
				  quart_description= "verify Cron enable link";
				if (selenium.isTextPresent(("Enabled")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
			
				
				// diff
				
				selenium.type("id=search-query", "diff");
				Thread.sleep(2000);
				
				 quart_detailid   = "9880";
				  quart_testname   = "ManageModulesDifftext";
				  quart_description= "verify Diff text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Diff"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9881";
				  quart_testname   = "ManageModulesDifftext";
				  quart_description= "verify Diff text";
				if (selenium.isTextPresent("Provides content diff capabilities."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6118";
				  quart_testname   = "ManageModulesDiffIcon";
				  quart_description= "verify Diff icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/diff/resources/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9883";
				  quart_testname   = "ManageModulesDiffPerforcetext";
				  quart_description= "verify Diff perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9882";
				  quart_testname   = "ManageModulesDiffVersion";
				  quart_description= "verify Diff version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2341";
				  quart_testname   = "ManageModulesDiffsupport";
				  quart_description= "verify Diff support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2335";
				  quart_testname   = "ManageModulesDiffWWW";
				  quart_description= "verify Diff WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					quart_detailid   = "9884";
				  quart_testname   = "ManageModulesDiffEnable";
				  quart_description= "verify Diff enable link";
				if (selenium.isTextPresent(("Enabled")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
				
				
				// dojo
				
				selenium.type("id=search-query", "dojo");
				Thread.sleep(2000);
				
				 quart_detailid   = "9860";
				  quart_testname   = "ManageModulesDojotext";
				  quart_description= "verify Dojo text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Dojo"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9862";
				  quart_testname   = "ManageModulesDojotext";
				  quart_description= "verify Dojo text";
				if (selenium.isTextPresent("Provides access to the Dojo Toolkit."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6119";
				  quart_testname   = "ManageModulesDojoIcon";
				  quart_description= "verify Dojo icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/dojo/resources/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9866";
				  quart_testname   = "ManageModulesDojoPerforcetext";
				  quart_description= "verify Dojo perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9864";
				  quart_testname   = "ManageModulesDojoVersion";
				  quart_description= "verify Dojo version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2347";
				  quart_testname   = "ManageModulesDojosupport";
				  quart_description= "verify Dojo support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2346";
				  quart_testname   = "ManageModulesDojoWWW";
				  quart_description= "verify Dojo WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					quart_detailid   = "9868";
				  quart_testname   = "ManageModulesDojoEnable";
				  quart_description= "verify Dojo enable link";
				if (selenium.isTextPresent(("Enabled")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);


				// easy cron module
				
				selenium.type("id=search-query","easycron");
				Thread.sleep(2000);
				
				 quart_detailid   = "9936";
				  quart_testname   = "ManageModulesEasyCrontext";
				  quart_description= "verify EasyCron text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Easy Cron"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9937";
				  quart_testname   = "ManageModulesEasyCrontext";
				  quart_description= "verify EasyCron text";
				if (selenium.isTextPresent("Allows running periodic tasks without setting up crontab."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "8259";
				  quart_testname   = "ManageModulesEasyCronIcon";
				  quart_description= "verify EasyCron icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/easycron/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9939";
				  quart_testname   = "ManageModulesEasyCronPerforcetext";
				  quart_description= "verify EasyCron perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9938";
				  quart_testname   = "ManageModulesEasyCronVersion";
				  quart_description= "verify EasyCron version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "8257";
				  quart_testname   = "ManageModulesEasyCronsupport";
				  quart_description= "verify EasyCron support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "8258";
				  quart_testname   = "ManageModulesEasyCronWWW";
				  quart_description= "verify EasyCron WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					quart_detailid   = "9941";
				  quart_testname   = "ManageModulesEasyCronEnable";
				  quart_description= "verify EasyCron enable link";
				if (selenium.isTextPresent(("Enabled")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.clickAt("css=div.row-id-easycron span.dijitDropDownButton","");
				Thread.sleep(3000);
			
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_3-button-action_label')]");  
		     	Thread.sleep(4000);
		     	
		     	selenium.type("id=search-query", "easycron");
		     	Thread.sleep(3000);
		     	
				quart_detailid   = "8252";
				  quart_testname   = "ManageModulesEasyCronStatusDisabled";
				  quart_description= "verify easy cron status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.clickAt("css=div.row-id-easycron span.dijitDropDownButton","");
				Thread.sleep(3000);
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_3-button-action_label')]");  
		     	Thread.sleep(5000);
		  
		     
				
		     	selenium.type("id=search-query", "easycron");
		     	Thread.sleep(3000);
				
		     	quart_detailid   = "6856";
				  quart_testname   = "ManageModulesEasyCronStatusEnabled";
				  quart_description= "verify easy cron status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
				
				// Flickr module
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

					
				selenium.type("id=search-query", "flickr");
				Thread.sleep(3000);
				
				 quart_detailid   = "9967";
				  quart_testname   = "ManageModulesFlickrtext";
				  quart_description= "verify Flickr text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Flickr"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9968";
				  quart_testname   = "ManageModulesFlickrtext";
				  quart_description= "verify Flickr text";
				if (selenium.isTextPresent("Allows a user to configure a flickr photostream for use in widgets."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 
				 quart_detailid   = "6121";
				  quart_testname   = "ManageModulesFlickrIcon";
				  quart_description= "verify Flickr icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/flickr/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9970";
				  quart_testname   = "ManageModulesFlickrPerforcetext";
				  quart_description= "verify Flickr perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9969";
				  quart_testname   = "ManageModulesFlickrVersion";
				  quart_description= "verify Flickr version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2368";
				  quart_testname   = "ManageModulesFlickrsupport";
				  quart_description= "verify Flickr support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2369";
				  quart_testname   = "ManageModulesFlickrWWW";
				  quart_description= "verify Flickr WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9971";
				  quart_testname   = "ManageModulesFlickrStatusDisabled";
				  quart_description= "verify flickr status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				selenium.clickAt("css=div.row-id-flickr span.dijitDropDownButton","");
				Thread.sleep(3000);			
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				Thread.sleep(4000);
				
				// enable flickr module
				selenium.clickAt("css=div.row-id-flickr span.dijitDropDownButton","");
				Thread.sleep(3000);
				
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_5-button-action_label')]");  
		     	Thread.sleep(4000);
			
		     	selenium.type("id=search-query", "flickr");
				Thread.sleep(4000);  
				
				if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
				{ System.out.println("Flickr module already enabled"); }
			
					else { // enable the flickr module
						
					selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
					
					// enable flickr
					selenium.clickAt("css=div.row-id-flickr span.dijitDropDownButton","");
					Thread.sleep(3000);
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_5-button-action_label')]");  
					Thread.sleep(3000);
				}
				
				selenium.type("id=search-query", "flickr");
				Thread.sleep(3000);
				
		     // enable flickr module
					quart_detailid   = "2378";
				  quart_testname   = "ManageModulesFlickrEnabledStatus";
				  quart_description= "verify flickr enable status";
					if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					quart_detailid   = "2364";
					  quart_testname   = "ManageModulesFlickrDisabledButton";
					  quart_description= "verify flickr enable status";
						if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				

				// configure flickr module 
				selenium.clickAt("css=div.row-id-flickr span.dijitButtonContents","");
				Thread.sleep(4000); 
				
					quart_detailid   = "7061";
				  quart_testname   = "ManageModulesFlickrConfigure";
				  quart_description= "verify flickr configure link";
				if (selenium.isTextPresent(("Flickr Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "9991";
				  quart_testname   = "ManageModulesFlickrAPIKey";
				  quart_description= "verify flickr api key";
				if (selenium.isTextPresent(("Flickr API Key")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "7075";
				  quart_testname   = "ManageModulesFlickrConfigureText";
				  quart_description= "verify flickr configure text";
				if (selenium.isTextPresent(("Flickr Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "7078";
				  quart_testname   = "ManageModulesFlickrAPIText";
				  quart_description= "verify flickr api text";
				if (selenium.isTextPresent(("Enter your Flickr API key.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "7085";
				  quart_testname   = "ManageModulesFlickrSaveButton";
				  quart_description= "verify flickr save button";
					if (selenium.isElementPresent(("//span[contains(@id, 'save_label')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					quart_detailid   = "7087";
					  quart_testname   = "ManageModulesFlickrFormField";
					  quart_description= "verify flickr form field";
						if (selenium.isElementPresent(("//input[contains(@id, 'key')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					
				// enter random key
				selenium.type("name=key", "1234");
				// click save
				selenium.click("id=save_label");
				Thread.sleep(5000);
						
						
						
						
					manageMenu();
					selenium.click(CMSConstants.MANAGE_MODULES);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
					// disable flickr module
					selenium.clickAt("css=div.row-id-flickr span.dijitDropDownButton","");
					Thread.sleep(3000);
					
						selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_5-button-action_label')]");  
				     	Thread.sleep(3000);
				     
				     	// search flickr again 
				     	selenium.type("id=search-query", "flickr");
						Thread.sleep(3000);				
						
				    	quart_detailid   = "7057";
						  quart_testname   = "ManageModulesFlickrDisabledStatus";
						  quart_description= "verify flickr disabled status";
							if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }  	
				     	
			    	
				
				
				
				selenium.type("id=search-query", "error");
				Thread.sleep(2000);
				
				 quart_detailid   = "9931";
				  quart_testname   = "ManageModulesErrortext";
				  quart_description= "verify Error text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Error"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9932";
				  quart_testname   = "ManageModulesErrortext";
				  quart_description= "verify Error text";
				if (selenium.isTextPresent("Provides the error presentation."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6120";
				  quart_testname   = "ManageModulesErrorIcon";
				  quart_description= "verify Error icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/error/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9934";
				  quart_testname   = "ManageModulesErrorPerforcetext";
				  quart_description= "verify Error perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9933";
				  quart_testname   = "ManageModulesErrorVersion";
				  quart_description= "verify Error version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2343";
				  quart_testname   = "ManageModulesErrorsupport";
				  quart_description= "verify Error support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2342";
				  quart_testname   = "ManageModulesErrorWWW";
				  quart_description= "verify Error WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					quart_detailid   = "9935";
				  quart_testname   = "ManageModulesErrorEnable";
				  quart_description= "verify Error enable link";
				if (selenium.isTextPresent(("Enabled")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
			 
				
			
				// history
						 
				selenium.type("id=search-query", "history");
				Thread.sleep(2000);
				
				 quart_detailid   = "9985";
				  quart_testname   = "ManageModulesHistorytext";
				  quart_description= "verify History text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("History"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9986";
				  quart_testname   = "ManageModulesHistorytext";
				  quart_description= "verify History text";
				if (selenium.isTextPresent("Provides facilities for managing the history of content."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6122";
				  quart_testname   = "ManageModulesHistoryIcon";
				  quart_description= "verify History icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/history/resources/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9888";
				  quart_testname   = "ManageModulesHistoryPerforcetext";
				  quart_description= "verify History perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9886";
				  quart_testname   = "ManageModulesHistoryVersion";
				  quart_description= "verify History version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2345";
				  quart_testname   = "ManageModulesHistorysupport";
				  quart_description= "verify History support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2344";
				  quart_testname   = "ManageModulesHistoryWWW";
				  quart_description= "verify History WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9889";
				  quart_testname   = "ManageModulesHistoryStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				// IDE modules
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.type("id=search-query", "dev");
				Thread.sleep(3000);
				 
				 quart_detailid   = "9852";
				  quart_testname   = "ManageModulesIDEtext";
				  quart_description= "verify IDE text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("IDE"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9853";
				  quart_testname   = "ManageModulesIDEtext";
				  quart_description= "verify IDE text";
				if (selenium.isTextPresent("Provides a source code editor for development of themes and modules (embeds the powerful ACE editor http://ace.ajax.org/). "))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9851";
				  quart_testname   = "ManageModulesIDEIcon";
				  quart_description= "verify IDE icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/ide/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9855";
				  quart_testname   = "ManageModulesIDEPerforcetext";
				  quart_description= "verify IDE perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9854";
				  quart_testname   = "ManageModulesIDEVersion";
				  quart_description= "verify IDE version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "9856";
				  quart_testname   = "ManageModulesIDEsupport";
				  quart_description= "verify IDE support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "9857";
				  quart_testname   = "ManageModulesIDEWWW";
				  quart_description= "verify IDE WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "9859";
				  quart_testname   = "ManageModulesIDEStatusDisabled";
				  quart_description= "verify status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     	
		     	quart_detailid   = "8251";
				  quart_testname   = "ManageModulesIDEStatusDisabled";
				  quart_description= "verify ide status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				selenium.click("css=div.row-id-ide span.dijitDropDownButton");
				Thread.sleep(3000);
			
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.clickAt("css=div.row-id-ide span.dijitDropDownButton","");
				Thread.sleep(3000);
				
				// enable IDE module
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_6-button-action_label')]");  
		     	Thread.sleep(3000);
		   
		    	selenium.type("id=search-query", "dev");
				Thread.sleep(3000);
				
				if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
				{ System.out.println("IDE module already enabled"); }
			
					else { // enable the IDE module
						
					selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					
					// enable IDE
					selenium.clickAt("css=div.row-id-ide span.dijitDropDownButton","");
					Thread.sleep(3000);
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_6-button-action_label')]");  
					Thread.sleep(3000);
				}
				
		 
		     	selenium.type("id=search-query", "dev");
				Thread.sleep(3000);
				
				
		     	quart_detailid   = "6858";
				  quart_testname   = "ManageModulesIDEStatusEnabled";
				  quart_description= "verify IDE status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     	
				
				// disable ide module
				selenium.click("css=div.row-id-ide span.dijitDropDownButton");
				Thread.sleep(3000);		
				
				
				// Disqus module 
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.type("id=search-query", "disqus");
				Thread.sleep(2000);
				
				 quart_detailid   = "11006";
				  quart_testname   = "ManageModulesDisqustext";
				  quart_description= "verify Disqus text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Disqus"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "11007";
				  quart_testname   = "ManageModulesDisqustext";
				  quart_description= "verify Disqus text";
				if (selenium.isTextPresent("Provides integration with Disqus, adding real-time discussions to your site."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "11005";
				  quart_testname   = "ManageModulesDisqusIcon";
				  quart_description= "verify Disqus icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/disqus/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "11009";
				  quart_testname   = "ManageModulesDisqusPerforcetext";
				  quart_description= "verify Disqus perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "11008";
				  quart_testname   = "ManageModulesDisqusVersion";
				  quart_description= "verify Disqus version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "11010";
				  quart_testname   = "ManageModulesDisqussupport";
				  quart_description= "verify Disqus support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "11011";
				  quart_testname   = "ManageModulesDisqusWWW";
				  quart_description= "verify Disqus WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "11016";
				  quart_testname   = "ManageModulesDisqusStatusDisabled";
				  quart_description= "verify status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     	
		     	quart_detailid   = "11014";
				  quart_testname   = "ManageModulesDisqusStatusDisabled";
				  quart_description= "verify Disqus status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
				
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				
					// enable Disqus module
					selenium.clickAt("css=div.row-id-disqus span.dijitDropDownButton","");
					Thread.sleep(3000);
				
					// enable Disqus module
					selenium.clickAt("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_2-button-action_label')]","");  
			     	Thread.sleep(4000);
			   
			    	selenium.type("id=search-query", "disqus");
					Thread.sleep(3000);
					
					if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
					{ System.out.println("Disqus module already enabled"); }
				
						else { // enable the Disqus module
							
						selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
						waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
						// enable Disqus
						selenium.clickAt("css=div.row-id-disqus span.dijitDropDownButton","");
						Thread.sleep(3000);
						selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_2-button-action_label')]");  
						Thread.sleep(3000);
					}
			 
			     	selenium.type("id=search-query", "disqus");
					Thread.sleep(3000);
				 
		       	  quart_detailid   = "11017";
				  quart_testname   = "ManageModulesDisqusStatusEnabled";
				  quart_description= "verify Disqus status enabled";
				 if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     	
				 
				
				// configure disqus module
				selenium.clickAt("css=div.row-id-disqus span.dijitButtonContents","");
				Thread.sleep(5000);
				
					quart_detailid   = "11020";
				  quart_testname   = "ManageModulesDisqusConfigure";
				  quart_description= "verify Disqus configure text";
				if (selenium.isTextPresent(("Disqus Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				quart_detailid   = "11012";
				  quart_testname   = "ManageModulesDisqusConfigureButton";
				  quart_description= "verify Disqus status enabled";
				 if (selenium.isTextPresent(("Disqus Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     				
				
				quart_detailid   = "11028";
				  quart_testname   = "ManageModulesDisqusContentTypes";
				  quart_description= "verify Disqus content types text";
				if (selenium.isTextPresent(("Content Types")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "11025";
				  quart_testname   = "ManageModulesDisqusShortnameText";
				  quart_description= "verify Disqus short name text";
				if (selenium.isTextPresent(("Disqus Short Name")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid = "11027";
				quart_testname = "ManageModulesDisqusShortnameText2";
				quart_description = "verify Disqus short name text2";
				if (selenium.isTextPresent(("Your shortname is a unique identifier which references your site.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				 else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
				 quart_detailid   = "10614";
				  quart_testname   = "ManageModulesDisqusSaveButton";
				  quart_description= "verify Disqus save button";
				if (selenium.isElementPresent(("//span[contains(@id, 'save_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			    	quart_detailid   = "11026";
				  quart_testname   = "ManageModulesDisqusInputForm";
				  quart_description= "verify Disqus save button";
					if (selenium.isElementPresent(("//input[contains(@id, 'shortName')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
						
						quart_detailid = "11030";
						quart_testname = "ManageModulesDisqusBasicPageText";
						quart_description = "verify Disqus basic page text";
						if (selenium.isTextPresent(("Basic Page")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						quart_detailid = "11032";
						quart_testname = "ManageModulesDisqusBlogPostText";
						quart_description = "verify Disqus blog post text";
						if (selenium.isTextPresent(("Blog Post")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						quart_detailid = "11034";
						quart_testname = "ManageModulesDisqusFileText";
						quart_description = "verify Disqus file text";
						if (selenium.isTextPresent(("File")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						quart_detailid = "11036";
						quart_testname = "ManageModulesDisqusImageGalleryText";
						quart_description = "verify Disqus image gallery text";
						if (selenium.isTextPresent(("Image Gallery")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
				
						quart_detailid = "11038";
						quart_testname = "ManageModulesDisqusImageText";
						quart_description = "verify Disqus image text";
						if (selenium.isTextPresent(("Image")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						
						quart_detailid = "11040";
						quart_testname = "ManageModulesDisqusPressReleaseText";
						quart_description = "verify Disqus press release text";
						if (selenium.isTextPresent(("Press Release")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						
						quart_detailid = "11041";
						quart_testname = "ManageModulesDisqusSelectText";
						quart_description = "verify Disqus select text";
						if (selenium.isTextPresent(("Select the content types to show Disqus conversations on by default.")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
						
						quart_detailid   = "11029";
						quart_testname   = "ManageModulesDisqusBasicPageCheckbox";
					    quart_description= "verify Disqus basic page checkbox";
						  if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-basic-page') and contains(@value, 'basic-page') and contains(@checked, 'checked') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						  
					  quart_detailid   = "11031";
					  quart_testname   = "ManageModulesDisqusBlogPostCheckbox";
					  quart_description= "verify Disqus blog post checkbox";
					  if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-blog-post') and contains(@value, 'blog-post') and contains(@checked, 'checked') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					  
					  quart_detailid   = "11033";
					  quart_testname   = "ManageModulesDisqusFileCheckbox";
					  quart_description= "verify Disqus file checkbox";
					  if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-file') and contains(@value, 'file') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					  
					  quart_detailid   = "11035";
					  quart_testname   = "ManageModulesDisqusImageGalleryCheckbox";
					  quart_description= "verify Disqus image gallery checkbox";
					  if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-gallery') and contains(@value, 'gallery') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					  
					  quart_detailid   = "11037";
					  quart_testname   = "ManageModulesDisqusImageCheckbox";
					  quart_description= "verify Disqus image gallery checkbox";
					  if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-image') and contains(@value, 'image') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					  
					  quart_detailid   = "11039";
					  quart_testname   = "ManageModulesDisqusImageCheckbox";
					  quart_description= "verify Disqus image gallery checkbox";
					  if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-press-release') and contains(@value, 'press-release') and contains(@checked, 'checked') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					  
				
						quart_detailid   = "11021";
					    quart_testname   = "ManageModulesDisqusSaveButton";
					    quart_description= "verify Disqus save button";
							if (selenium.isElementPresent(("//span[contains(@id, 'save_label')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
						quart_detailid   = "11022";
						quart_testname   = "ManageModulesDisqusSaveButton";
					    quart_description= "verify Disqus save button";
						if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_4')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							

						
					/*	// check tooltip 
						quart_detailid = "11023";
						quart_testname = "ManageModulesDisqusTooltip";
						quart_description = "verify Disqus tooltip";
						
						String tooltip = selenium.getAttribute("//div[18]/div/span[2]/@title");
						boolean tooltipTrue = tooltip.equals("Cancel");
						
						if(tooltipTrue)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description);
						}*/
			
						// check icon 
						quart_detailid = "11024";
						quart_testname = "ManageModulesDisqusCloseIcon";
						quart_description = "verify Disqus close icon";
				
						if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description);
						}
						
							
						// create content to check disqus element
						verifyContentElements();
						Thread.sleep(1000);
						browserSpecificBlogPost();
						Thread.sleep(2000);
						addBlogPost();
						Thread.sleep(2000);
						addBlogPostPublishMode();
						
						// click edit on the basic page to verify disqus element
						selenium.click("id=toolbar-content-edit");
						Thread.sleep(1000);  
						
						// click the disqus element
						selenium.clickAt("id=edit-content-toolbar-button-Disqus","");
						Thread.sleep(3000);
						
						
						quart_detailid = "11046";
						quart_testname = "ManageModulesEditBlogPostDiqusElement";
						quart_description = "verify Disqus element for Blog Post";
						if (selenium.isElementPresent("//span[contains(@id, 'edit-content-toolbar-button-Disqus')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
						
						quart_detailid = "11047";
						quart_testname = "ManageModulesEditBlogPostDiqusElement";
						quart_description = "verify Disqus element popup";
						if (selenium.isElementPresent("//dd[contains(@id, 'disqus-showConversation-element')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
						
						quart_detailid = "11049";
						quart_testname = "ManageModulesEditBlogPostDisqusShowButtonText";
						quart_description = "verify Disqus button text for Blog Post";
						if (selenium.isTextPresent("Show Conversations"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
						
						quart_detailid = "11048";
						quart_testname = "ManageModulesEditBlogPostDisqusElement";
						quart_description = "verify Disqus element for Blog Post";
						if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'disqus-showConversation') and contains(@value, '1') and contains(@checked, 'checked')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
											
						
						// go into form mode
						// click form mode and verify all elements
				 		selenium.click("id=edit-content-toolbar-button-form_label");
				 		
				 		quart_detailid = "11042";
						quart_testname = "ManageModulesEditBlogPostDisqusTextFormMode";
						quart_description = "verify Disqus element for Blog Post Form Mode";
						if (selenium.isTextPresent("Disqus"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
						
						quart_detailid = "11045";
						quart_testname = "ManageModulesEditBlogPostDisqusTextFormMode";
						quart_description = "verify Disqus element for Blog Post Form Mode";
						if (selenium.isTextPresent("Show Conversations"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
						
						quart_detailid = "11043";
						quart_testname = "ManageModulesEditBlogPostDisqusCheckboxFormMode";
						quart_description = "verify Disqus element for Blog Post form mode";
						if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'disqus-showConversation') and contains(@value, '1') and contains(@checked, 'checked')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
						
						// disable Disqus module
						selenium.click(CMSConstants.MANAGE_MODULES);
						waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
						selenium.clickAt("css=div.row-id-disqus span.dijitDropDownButton","");
						Thread.sleep(3000);	
										
						selenium.clickAt("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_2-button-action_label')]","");  
				     	Thread.sleep(4000);	
							
				     	selenium.type("id=search-query", "disqus");
						Thread.sleep(3000);   
				     	
				     	quart_detailid   = "11015";
						  quart_testname   = "ManageModulesDisqusStatusDisabled";
						  quart_description= "verify Disqus status disabled";
						 if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
						
						 quart_detailid   = "11013";
						  quart_testname   = "ManageModulesDisqusStatusDisabled";
						  quart_description= "verify Disqus status disabled";
						 if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				     	
				     	
						 
						 
						 
						 
				// Feed module
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.type("id=search-query", "Feed");
				Thread.sleep(2000);
				
				 quart_detailid   = "10958";
				  quart_testname   = "ManageModulesFeedtext";
				  quart_description= "verify Feed text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Feed"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "10959";
				  quart_testname   = "ManageModulesFeedtext";
				  quart_description= "verify Feed text";
				if (selenium.isTextPresent("Provides a basic widget to display an RSS or Atom feed."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "10957";
				  quart_testname   = "ManageModulesFeedIcon";
				  quart_description= "verify Feed icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/feed/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "10961";
				  quart_testname   = "ManageModulesFeedPerforcetext";
				  quart_description= "verify Feed perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "10960";
				  quart_testname   = "ManageModulesFeedVersion";
				  quart_description= "verify Feed version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "10962";
				  quart_testname   = "ManageModulesFeedsupport";
				  quart_description= "verify Feed support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "10963";
				  quart_testname   = "ManageModulesFeedWWW";
				  quart_description= "verify Feed WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "10967";
				  quart_testname   = "ManageModulesFeedStatusDisabled";
				  quart_description= "verify status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     	
		     	quart_detailid   = "10965";
				  quart_testname   = "ManageModulesFeedStatusDisabled";
				  quart_description= "verify Feed status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
				
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				
					// enable Feed module
					selenium.clickAt("css=div.row-id-feed span.dijitDropDownButton","");
					Thread.sleep(3000);
								
					quart_detailid   = "10969";
					  quart_testname   = "ManageModulesFeedStatusEnabledButton";
					  quart_description= "verify Feed status enabled button";
					 if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_4-button-action_label')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				
					// enable Feed module
					selenium.clickAt("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_4-button-action_label')]","");  
			     	Thread.sleep(4000);
			     	
			   
			    	selenium.type("id=search-query", "Feed");
					Thread.sleep(4000);
					
				 	  quart_detailid   = "10968";
					  quart_testname   = "ManageModulesFeedStatusEnabled";
					  quart_description= "verify Feed status enabled";
					 if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
					if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
					{ System.out.println("Feed module already enabled"); }
				
						else { // enable the Feed module
							
						selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
						waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
						// enable Feed
						selenium.clickAt("css=div.row-id-Feed span.dijitDropDownButton","");
						Thread.sleep(3000);
						selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_4-button-action_label')]");  
						Thread.sleep(3000);
					}
			 
				  
				 // configure Feed module 
				 backToHome();
				 
				 // click on widgets
				 selenium.click("css=span.menu-icon.manage-toolbar-widgets");
				 
				 selenium.click("//span[@id='dijit_form_Button_1']/span");
				selenium.click("xpath=(//input[@value=''])[2]");
				Thread.sleep(3000);
				
				// click on Feed widget
				selenium.click("link=Feed Widget");
				Thread.sleep(2000);
				 
				quart_detailid   = "10973";
				  quart_testname   = "ManageModulesFeedWidgetConfigureText";
				  quart_description= "verify Feed widget configure text";
				 if (selenium.isTextPresent(("Configure Feed Widget")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				 
				 quart_detailid   = "10978";
				 quart_testname   = "ManageModulesFeedWidgetOptionsText";
				 quart_description= "verify Feed widget options text";
				 if (selenium.isTextPresent(("General Options")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				 quart_detailid   = "10979";
				 quart_testname   = "ManageModulesFeedWidgetTitleText";
				 quart_description= "verify Feed widget title text";
				 if (selenium.isTextPresent(("Title")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
			 	quart_detailid   = "10981";
			 	quart_testname   = "ManageModulesFeedWidgetShowTitleText";
			 	quart_description= "verify Feed widget show title text";
			 	if (selenium.isTextPresent(("Show Title")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
			 
			  	quart_detailid   = "10983";
				 quart_testname   = "ManageModulesFeedWidgetOrderText";
			    quart_description= "verify Feed widget order text";
			    if (selenium.isTextPresent(("Order")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				 
				 quart_detailid   = "10984";
				  quart_testname   = "ManageModulesFeedWidgetOrderSelector";
				  quart_description= "verify Feed widget order selector";
					if (selenium.isElementPresent("//select[contains(@id, 'config-order') and contains(@name, 'order') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					

				// place order them into a string array
				String[] currentSelection = selenium.getSelectOptions("//select[contains(@name, 'order')]");
						
				// verify if the Current Status exists in the selection list 
				boolean selectedValue = ArrayUtils.contains(currentSelection, "0");
							    
				quart_detailid   = "10984";  
				quart_testname   = "WidgetsRotatorOrderSelected";
				quart_description= "verify widgets order selection";
				// verify that order is selected
					if (selectedValue)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			

				quart_detailid   = "10986";
				quart_testname   = "ManageModulesFeedWidgetCSSText";
				quart_description= "verify Feed widget css text";
				 if (selenium.isTextPresent(("CSS Class")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				 	 
				 quart_detailid   = "10989";
				 quart_testname   = "ManageModulesFeedWidgetLoadText";
				 quart_description= "verify Feed widget load asynch text";
				 if (selenium.isTextPresent(("Load Asynchronously")))
			   	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 	 
				 quart_detailid   = "10985";
				  quart_testname   = "ManageModulesFeedWidgetPositionText";
				  quart_description= "verify Feed widget adjust position text";
				 if (selenium.isTextPresent(("Adjust the position of this widget in the region")))
			 	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				 
				 quart_detailid   = "10988";
				  quart_testname   = "ManageModulesFeedWidgetCSSClassText";
				  quart_description= "verify Feed widget css class text";
				 if (selenium.isTextPresent(("Specify a CSS class to customize the appearance of this widget.")))
			   	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				  quart_detailid   = "10991";
				  quart_testname   = "ManageModulesFeedWidgetLoadText";
				  quart_description= "verify Feed widget load text";
				 if (selenium.isTextPresent(("Load this widget after the rest of the page.")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				 quart_detailid   = "10980";
				  quart_testname   = "ManageModulesFeedWidgetTitleText1";
				  quart_description= "verify Feed widget title text";
				 if (selenium.isTextPresent(("Feed Widget")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					 
				 quart_detailid   = "10982";
				  quart_testname   = "ManageModulesFeedWidgetTitleCheckbox";
				  quart_description= "verify Feed widget title checkbox";
					if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'showTitle') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					quart_detailid   = "10987";
					  quart_testname   = "ManageModulesFeedWidgetCSSClassCheckbox";
					  quart_description= "verify Feed widget css class checkbox";
						if (selenium.isElementPresent("//input[@type='text' and contains(@value, '') and contains(@name, 'class') ]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
				 
						quart_detailid   = "10990";
						  quart_testname   = "ManageModulesFeedWidgetLoadCheckbox";
						  quart_description= "verify Feed widget load checkbox";
							if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'asynchronous') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
					 
							quart_detailid   = "10992";
							quart_testname   = "ManageModulesFeedWidgetOptionsText1";
							quart_description= "verify Feed widget options text";
							if (selenium.isTextPresent(("Feed Widget Options")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


							quart_detailid   = "10993";
							quart_testname   = "ManageModulesFeedWidgetFeedURLText";
							quart_description= "verify Feed widget feed url text";
							if (selenium.isTextPresent(("Feed URL")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


							quart_detailid   = "10995";
							quart_testname   = "ManageModulesFeedWidgetRSSText";
							quart_description= "verify Feed widget rss text";
							if (selenium.isTextPresent(("Both RSS and Atom feeds are supported.")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


							quart_detailid   = "10997";
							quart_testname   = "ManageModulesFeedWidgetShowSourceURLText";
							quart_description= "verify Feed widget show source url text";
							if (selenium.isTextPresent(("Show Source URL")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


							quart_detailid   = "10999";
							quart_testname   = "ManageModulesFeedWidgetShowDatesText";
							quart_description= "verify Feed widget show dates text";
							if (selenium.isTextPresent(("Show Dates")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


							quart_detailid   = "11001";
							quart_testname   = "ManageModulesFeedWidgetShowDescText";
							quart_description= "verify Feed widget rss text";
							if (selenium.isTextPresent(("Show Description")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
															  
							quart_detailid   = "11002";
							  quart_testname   = "ManageModulesFeedWidgetMaxItemsText";
							  quart_description= "verify Feed widget max items text";
								 if (selenium.isTextPresent(("Maximum Items")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								  
								 
							 quart_detailid   = "11003";
							  quart_testname   = "ManageModulesFeedWidgetMaxItemsSelector";
							  quart_description= "verify Feed widget max items selector";
							if (selenium.isElementPresent("//select[contains(@id, 'config-maxItems') and contains(@name, 'config[maxItems]') ]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 			
							
							// place order them into a string array
							String[] currentSelection1 = selenium.getSelectOptions("//select[contains(@name, 'config[maxItems]')]");
									
									// verify if the Current Status exists in the selection list 
							boolean selectedValue1 = ArrayUtils.contains(currentSelection1, "10");
								    
							quart_detailid   = "11003";  
							quart_testname   = "WidgetsRotatorOrderSelected";
							quart_description= "verify widgets order selection";
							// verify that the max items is selected
							if (selectedValue1)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
								 
						 quart_detailid   = "11004";
						 quart_testname   = "ManageModulesFeedWidgetMaxNumberText";
						 quart_description= "verify Feed widget max # text";
						 if (selenium.isTextPresent(("Enter the maximum number of items to display.")))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						 quart_detailid   = "10994";
						 quart_testname   = "ManageModulesFeedWidgetFeedURLInput";
						 quart_description= "verify Feed widget feed url form";
						 if (selenium.isElementPresent("//input[@type='text' and contains(@value, '') and contains(@name, 'config[feedUrl]') ]"))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						 quart_detailid   = "10996";
						 quart_testname   = "ManageModulesFeedWidgetSourceURLCheckbox";
						 quart_description= "verify Feed widget show source url checkbox";
						 if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'config[showFeedUrl]') ]"))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						 quart_detailid   = "10998";
						 quart_testname   = "ManageModulesFeedWidgetShowDatesCheckbox";
						 quart_description= "verify Feed widget show dates checkbox";
						 if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'config[showDate]') ]"))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						 quart_detailid   = "11000";
						 quart_testname   = "ManageModulesFeedWidgetShowDescCheckbox";
						 quart_description= "verify Feed widget show desc checkbox";
						 if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'config[showDescription]') ]"))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						 
						 quart_detailid   = "10977";
						 quart_testname   = "ManageModulesFeedWidgeSaveButton";
						 quart_description= "verify Feed widget save button";
						 if (selenium.isElementPresent(("//dd[contains(@id, 'save-element')]")))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						 quart_detailid   = "10975";
						 quart_testname   = "ManageModulesFeedWidgeCancelButton";
						 quart_description= "verify Feed widget cancel button";
						 if (selenium.isElementPresent(("//dd[contains(@id, 'cancel-element')]")))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						 
						 quart_detailid   = "10976";
						 quart_testname   = "ManageModulesFeedWidgetCloseIcon";
						 quart_description= "verify Feed widget close icon";
						 if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

/*						 
						// check tooltip
						quart_detailid = "10974";
						quart_testname = "ManageModulesFeedWidgetTooltip";
						quart_description = "verify Widget tooltip";
						
						String tooltip = selenium.getAttribute("//div[71]/div/span[2]/@title");
						boolean tooltipTrue = tooltip.equals("Cancel");
							
						if(tooltipTrue)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
					    else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
	*/		
						
				 // disable Feed module
				 	manageMenu();
					selenium.click(CMSConstants.MANAGE_MODULES);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					
					// disable Feed module
					selenium.clickAt("css=div.row-id-feed span.dijitDropDownButton","");
					Thread.sleep(3000);
					
					quart_detailid   = "10971";
					  quart_testname   = "ManageModulesFeedStatusDisabledButton";
					  quart_description= "verify Feed status disabled button";
					 if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_4-button-action_label')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				
					// disable Feed module
					selenium.clickAt("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_4-button-action_label')]","");  
			     	Thread.sleep(4000);
					
			     	selenium.type("id=search-query", "Feed");
					Thread.sleep(3000);
					
					
					quart_detailid   = "10964";
					  quart_testname   = "ManageModulesFeedStatusDisabled";
					  quart_description= "verify Feed status disabled button";
					 if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					 
					 
				
				 
				 	
				 
				// ShareThis module
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.type("id=search-query", "sharethis");
				Thread.sleep(2000);
				
				 quart_detailid   = "10494";
				  quart_testname   = "ManageModulesShareThistext";
				  quart_description= "verify ShareThis text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("ShareThis"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "10495";
				  quart_testname   = "ManageModulesShareThistext";
				  quart_description= "verify ShareThis text";
				if (selenium.isTextPresent("Provides facility for sharing content through popular social media services."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "10493";
				  quart_testname   = "ManageModulesShareThisIcon";
				  quart_description= "verify ShareThis icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/sharethis/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "10498";
				  quart_testname   = "ManageModulesShareThisPerforcetext";
				  quart_description= "verify ShareThis perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "10497";
				  quart_testname   = "ManageModulesShareThisVersion";
				  quart_description= "verify ShareThis version";
				if (selenium.isTextPresent((versionString)))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "10499";
				  quart_testname   = "ManageModulesShareThissupport";
				  quart_description= "verify ShareThis support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "10500";
				  quart_testname   = "ManageModulesShareThisWWW";
				  quart_description= "verify ShareThis WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "10510";
				  quart_testname   = "ManageModulesShareThisStatusDisabled";
				  quart_description= "verify status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     	
		     	quart_detailid   = "10509";
				  quart_testname   = "ManageModulesShareThisStatusDisabled";
				  quart_description= "verify ShareThis status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
				
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				
					// enable sharethis module
					selenium.clickAt("css=div.row-id-sharethis span.dijitDropDownButton","");
					Thread.sleep(3000);
					
					// enable ShareThis module
					selenium.clickAt("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_8-button-action_label')]","");  
			     	Thread.sleep(4000);
			   
			    	selenium.type("id=search-query", "sharethis");
					Thread.sleep(3000);
					
					if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
					{ System.out.println("ShareThis module already enabled"); }
				
						else { // enable the ShareThis module
							
						selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
						waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
						// enable ShareThis
						selenium.clickAt("css=div.row-id-sharethis span.dijitDropDownButton","");
						Thread.sleep(3000);
						selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_8-button-action_label')]");  
						Thread.sleep(3000);
					}
					
			 
			     	selenium.type("id=search-query", "sharethis");
					Thread.sleep(3000);
					
				 
		     	quart_detailid   = "10506";
				  quart_testname   = "ManageModulesShareThisStatusEnabled";
				  quart_description= "verify ShareThis status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		     	
				
			
				
				// configure share this module
				selenium.clickAt("css=div.row-id-sharethis span.dijitButtonContents","");
				Thread.sleep(5000);
				
					quart_detailid   = "10537";
				  quart_testname   = "ManageModulesShareThisConfigure";
				  quart_description= "verify ShareThis configure link";
				if (selenium.isTextPresent(("ShareThis Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "10610";
				  quart_testname   = "ManageModulesShareThisPublisherKey";
				  quart_description= "verify ShareThis publisher key";
				if (selenium.isTextPresent(("Publisher Key")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "10565";
				  quart_testname   = "ManageModulesShareThisContentTypes";
				  quart_description= "verify ShareThis content types text";
				if (selenium.isTextPresent(("Content Types")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "10547";
				  quart_testname   = "ManageModulesShareThisServicesText";
				  quart_description= "verify ShareThis services text";
				if (selenium.isTextPresent(("Services")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "10614";
				  quart_testname   = "ManageModulesShareThisSaveButton";
				  quart_description= "verify ShareThis save button";
					if (selenium.isElementPresent(("//span[contains(@id, 'save_label')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "10611";
				  quart_testname   = "ManageModulesShareThisFormField";
				  quart_description= "verify ShareThis form field";
					if (selenium.isElementPresent(("//input[contains(@id, 'publisherKey')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					quart_detailid   = "10538";
					  quart_testname   = "ManageModulesShareThisButtonStyle";
					  quart_description= "verify ShareThis button style";
						if (selenium.isTextPresent(("Button Style")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "10564";
					  quart_testname   = "ManageModulesShareThisDragDropText";
					  quart_description= "verify ShareThis drag drop text";
						if (selenium.isTextPresent(("Drag and drop to add or remove a service. You can also drag and drop to reorder the services.")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
					
				quart_detailid   = "10613";
				  quart_testname   = "ManageModulesShareThisKeyText";
				  quart_description= "verify ShareThis key text";
					if (selenium.isTextPresent(("Provide your ShareThis key for tracking purposes.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
				
									
				quart_detailid   = "10559";
				  quart_testname   = "ManageModulesShareThisAvailableServicesText";
				  quart_description= "verify ShareThis services text";
					if (selenium.isTextPresent(("Available Services")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
								
				quart_detailid   = "10548";
				  quart_testname   = "ManageModulesShareThisSelectedServicesText";
				  quart_description= "verify ShareThis services text";
					if (selenium.isTextPresent(("Selected Services")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
				quart_detailid   = "10540";
				  quart_testname   = "ManageModulesShareThisLargeText";
				  quart_description= "verify ShareThis large text";
					if (selenium.isTextPresent(("Large (32x32)")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
										
															
					quart_detailid   = "10539";
					  quart_testname   = "ManageModulesShareThisLargeRadio";
					  quart_description= "verify ShareThis large radio button checked";
						if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'buttonStyle-large') and contains(@value, 'large') and contains(@checked, 'checked') ]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
											
					quart_detailid   = "10541";
					  quart_testname   = "ManageModulesShareThisSmallRadio";
					  quart_description= "verify ShareThis large radio button checked";
						if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'buttonStyle-small') and contains(@value, 'small')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
																		
					quart_detailid   = "10542";
					  quart_testname   = "ManageModulesShareThisSmallText";
					  quart_description= "verify ShareThis small radio";
						if (selenium.isTextPresent("Small (16x16)"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
					quart_detailid   = "10543";
					  quart_testname   = "ManageModulesShareThisVerticalCounterRadio";
					  quart_description= "verify ShareThis vertical counter radio button";
						if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'buttonStyle-vcount') and contains(@value, 'vcount')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
																	
					quart_detailid   = "10544";
					  quart_testname   = "ManageModulesShareThisVerticalText";
					  quart_description= "verify ShareThis vertical radio";
						if (selenium.isTextPresent("Vertical Counters"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
					
					quart_detailid   = "10545";
					  quart_testname   = "ManageModulesShareThisHorizantalCounterRadio";
					  quart_description= "verify ShareThis horizantal counter radio button";
						if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'buttonStyle-hcount') and contains(@value, 'hcount')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
																		
						quart_detailid   = "10546";
						  quart_testname   = "ManageModulesShareThisVerticalText";
						  quart_description= "verify ShareThis vertical radio";
							if (selenium.isTextPresent("Horizontal Counters"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
		// check content types
		quart_detailid = "10567";
		quart_testname = "ManageModulesShareThisBasicPageText";
		quart_description = "verify ShareThis basic page";
		if (selenium.isTextPresent("Basic Page"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		quart_detailid = "10566";
		quart_testname = "ManageModulesShareThisBasicPageCheckbox";
		quart_description = "verify ShareThis basic page checkbox";
		if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-basic-page') and contains(@value, 'basic-page')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		// check content types
		quart_detailid = "10569";
		quart_testname = "ManageModulesShareThisBlogPostText";
		quart_description = "verify ShareThis blog post";
		if (selenium.isTextPresent("Blog Post"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		quart_detailid = "10568";
		quart_testname = "ManageModulesShareThisBlogPostCheckbox";
		quart_description = "verify ShareThis blog post checkbox";
		if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-blog-post') and contains(@value, 'blog-post') and contains(@checked, 'checked')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		quart_detailid = "10571";
		quart_testname = "ManageModulesShareThisFileText";
		quart_description = "verify ShareThis File text";
		if (selenium.isTextPresent("File"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		quart_detailid = "10570";
		quart_testname = "ManageModulesShareThisFileCheckbox";
		quart_description = "verify ShareThis file checkbox";
		if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-file') and contains(@value, 'file')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
												
							
		quart_detailid = "10575";
		quart_testname = "ManageModulesShareThisImageGalleryText";
		quart_description = "verify ShareThis image gallery text";
		if (selenium.isTextPresent("Image Gallery"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		quart_detailid = "10574";
		quart_testname = "ManageModulesShareThisImageGalleryCheckbox";
		quart_description = "verify ShareThis image gallery checkbox";
		if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-gallery') and contains(@value, 'gallery')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		// check content types
		quart_detailid = "10577";
		quart_testname = "ManageModulesShareThisPressReleaseText";
		quart_description = "verify ShareThis press release";
		if (selenium.isTextPresent("Press Release"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		quart_detailid = "10576";
		quart_testname = "ManageModulesShareThisPressReleaseCheckbox";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'contentTypes-press-release') and contains(@value, 'press-release') and contains(@checked, 'checked')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		// check content types
		quart_detailid = "10578";
		quart_testname = "ManageModulesShareThisContentTypeText";
		quart_description = "verify ShareThis content type";
		if (selenium.isTextPresent("Select the content types to show ShareThis buttons on by default."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}	
		
		
		// check selected services
		quart_detailid = "10549";
		quart_testname = "ManageModulesShareThisIcon";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//img[contains(@src, 'http://w.sharethis.com/images/sharethis_32.png')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		quart_detailid = "10551";
		quart_testname = "ManageModulesShareThisFacebookIcon";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//img[contains(@src, 'http://w.sharethis.com/images/facebook_32.png')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		quart_detailid = "10553";
		quart_testname = "ManageModulesShareThisTweetIcon";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//img[contains(@src, 'http://w.sharethis.com/images/twitter_32.png')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		
		quart_detailid = "10555";
		quart_testname = "ManageModulesShareThisLinkedInIcon";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//img[contains(@src, 'http://w.sharethis.com/images/linkedin_32.png')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		
		quart_detailid = "10557";
		quart_testname = "ManageModulesShareThisEmailIcon";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//img[contains(@src, 'http://w.sharethis.com/images/email_32.png')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
					
		
		
		quart_detailid = "10550";
		quart_testname = "ManageModulesShareThisService";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//span[contains(@data-service, 'sharethis')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		quart_detailid = "10552";
		quart_testname = "ManageModulesShareThisFacebookService";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//span[contains(@data-service, 'facebook')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		quart_detailid = "10554";
		quart_testname = "ManageModulesShareThisTweetService";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//span[contains(@data-service, 'twitter')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		
		quart_detailid = "10556";
		quart_testname = "ManageModulesShareThisLinkedInService";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//span[contains(@data-service, 'linkedin')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		
		quart_detailid = "10558";
		quart_testname = "ManageModulesShareThisEmailService";
		quart_description = "verify ShareThis press release checkbox";
		if (selenium.isElementPresent("//span[contains(@data-service, 'email')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		
		
		
		// check available services
			quart_detailid = "10560";
			quart_testname = "ManageModulesShareThisAmazonWishlistIcon";
			quart_description = "verify ShareThis Amazon Service Icon";
			if (selenium.isElementPresent("//img[contains(@src, 'http://w.sharethis.com/images/amazon_wishlist_32.png')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
			else {
				writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
			}
			
			
			quart_detailid = "10561";
			quart_testname = "ManageModulesShareThisAmazonWishlistService";
			quart_description = "verify ShareThis Amazon Wishlist Service";
			if (selenium.isElementPresent("//span[contains(@data-service, 'amazon_wishlist')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
			else {
				writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
			}		 		
												
			
			quart_detailid = "10562";
			quart_testname = "ManageModulesShareThisYammerIcon";
			quart_description = "verify ShareThis Yammer Icon";
			if (selenium.isElementPresent("//img[contains(@src, 'http://w.sharethis.com/images/yammer_32.png')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
			else {
				writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
			}
			   
			
			quart_detailid = "10563";
			quart_testname = "ManageModulesShareThisYammerService";
			quart_description = "verify ShareThis Yammer Service";
			if (selenium.isElementPresent("//span[contains(@data-service, 'yammer')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
			else {
				writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
			}		 					 		
											
			 
/*			// check tooltip
			
			quart_detailid = "10737";
			quart_testname = "ManageModulesShareThisTooltip";
			quart_description = "verify ShareThis tooltip";
			
			String tooltip = selenium.getAttribute("//div[15]/div/span[2]/@title");
			boolean tooltipTrue = tooltip.equals("Cancel");
			
			if(tooltipTrue)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else {
				writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description);
			}
				
*/			
			quart_detailid   = "10736";
			  quart_testname   = "ManageModulesShareThisCancelButton";
			  quart_description= "verify sharethis config cancel button";
				if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_3_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
			
				quart_detailid   = "10738";
				  quart_testname   = "ManageModulesShareThisCloseIcon";
				  quart_description= "verify sharethis config close icon";
					if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
									 
			
		quart_detailid = "10612";
		quart_testname = "ManageModulesShareThisGenerateButton";
		quart_description = "verify ShareThis generate button";
		if (selenium.isElementPresent(("css=.dijitDialog .dijitDialogPaneContent .scrollNode .sharethis-form-configure .zend_form_dojo .dijitButton .dijitButtonNode .dijitButtonContents")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

		// generate key
		selenium.clickAt("css=.dijitDialog .dijitDialogPaneContent .scrollNode .sharethis-form-configure .zend_form_dojo .dijitButton .dijitButtonNode .dijitButtonContents","");

		quart_detailid = "10614";
		quart_testname = "ManageModulesShareThisSaveButton";
		quart_description = "verify ShareThis save button";
		if (selenium.isElementPresent(("css=.dijitDialog .dijitDialogPaneContent .display-group .buttons .dijitButton .dijitButtonNode .dijitButtonContents")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
 
		
		
		// save sharethis
		selenium.clickAt("css=..dijitDialog .dijitDialogPaneContent .display-group .buttons .dijitButton .dijitButtonNode .dijitButtonContents","");
		Thread.sleep(5000);

		selenium.type("id=search-query", "sharethis");
		Thread.sleep(3000);

		
		
		quart_detailid = "10507";
		quart_testname = "ManageModulesShareThisEnabled";
		quart_description = "verify ShareThis enabled status";
		if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
			
		
		// disable sharethis module
		selenium.clickAt("css=div.row-id-sharethis span.dijitDropDownButton","");
		Thread.sleep(3000);
		
		quart_detailid = "10504";
		quart_testname = "ManageModulesShareThisDisabledStatus";
		quart_description = "verify ShareThis disabled status";
		if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_6-button-action_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}

			
		// create content to check sharethis element
		verifyContentElements();
		Thread.sleep(1000);
		browserSpecificBlogPost();
		Thread.sleep(2000);
		addBlogPost();
		Thread.sleep(2000);
		addBlogPostPublishMode();
		Thread.sleep(2000);
		
		// click edit on the basic page to verify sharethis element
		selenium.click("id=toolbar-content-edit");
		Thread.sleep(1000);  
		
		// click the sharethis element
		selenium.clickAt("id=edit-content-toolbar-button-ShareThis","");
		Thread.sleep(3000);
		
		
		quart_detailid = "10632";
		quart_testname = "ManageModulesEditBlogPostShareThisElement";
		quart_description = "verify ShareThis element for Blog Post";
		if (selenium.isElementPresent("//span[contains(@id, 'edit-content-toolbar-button-ShareThis')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		quart_detailid = "10635";
		quart_testname = "ManageModulesEditBlogPostShareThisShowButtonText";
		quart_description = "verify ShareThis button text for Blog Post";
		if (selenium.isTextPresent("Show ShareThis Buttons"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		quart_detailid = "10634";
		quart_testname = "ManageModulesEditBlogPostShareThisElement";
		quart_description = "verify ShareThis element for Blog Post";
		if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'sharethis-showButtons') and contains(@value, '1') and contains(@checked, 'checked')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
		else {
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
		}
		
		
		// back to WebSite
		backToHome();
	}
}

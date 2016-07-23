
package tests;

import java.io.FileWriter;
import java.util.Random;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


// This code logs in and clicks on the Manage --> Manage content and verifies that the Manage content title appears

public class SiteBranchingPullBranchFlow extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "SiteBranchingPullBranchFlow";
	private boolean moduleSET;

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
		SiteBranchingPullBranchFlow();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");  

	}
	
	public void SiteBranchingPullBranchFlow() throws Exception {
		
			
		//**** ADD BRANCH ****//
		
		// create a branch without content
		backToHome();
		Thread.sleep(2000);
		 
		// go to site branching and add a branch
		selenium.click(CMSConstants.LIVE_LINK);
		Thread.sleep(3000);  
		// go to add branch
		selenium.click("id=dijit_MenuItem_2_text");
		Thread.sleep(5000);
		
		// create branch Dev 
		selenium.type("id=branch-name", "Dev");
		selenium.click("id=branch-description");
		selenium.type("id=branch-description", "Dev branch");
		selenium.click("id=branch-save_label");
		Thread.sleep(3000);
		
		// switch to Dev branch
		// go to site branching and select dev branch
		selenium.click(CMSConstants.LIVE_LINK);
		selenium.click("id=dijit_MenuItem_0_text");
		Thread.sleep(2000);
	   	
    	// create basic page
    	addContentSiteBranchingFlow();
    	Thread.sleep(2000);
    	
    	// create a page in publish mode
    	verifyContentElements();
		Thread.sleep(2000);
		// click for basic page
		selenium.click("//a[@href='/-dev-/add/type/basic-page']"); 
		Thread.sleep(2000);		
		selenium.type("id=title", "Basic Page Published Mode for Site Branching Flow");
  		Thread.sleep(1000);
  		
  		// Save page form
  		selenium.click("id=add-content-toolbar-button-Save_label");
  		//selenium.click("id=save_label");
		waitForElements("id=workflow-state-published");

  		// click review and save
		selenium.click("id=workflow-state-published");
		waitForElements("id=save_label");
		selenium.click("id=save_label"); 
		Thread.sleep(3000);
		
    	
    	// click edit to add comments  
		selenium.type("//form/dl/dd/textarea", "Hello");
		
		selenium.click("id=post");
		Thread.sleep(4000);
	
		// click widgets
		selenium.click("css=#toolbar-widgets > span.menu-handle.type-heading");
		Thread.sleep(2000);
        		
		// search widget and save
		selenium.click("//span[contains(@class, 'dijitReset dijitInline dijitIcon plusIcon')]");
		Thread.sleep(2000);
        selenium.click("link=Search Widget");
		Thread.sleep(2000);
		selenium.click("//dd[contains(@id, 'save-element')]");		

		selenium.click("//span[contains(@class, 'dijitReset dijitInline dijitButtonText')]");		
		Thread.sleep(2000);
    	
		// create categories
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CATEGORIES);
		Thread.sleep(2000);
	
		String categoryName  = "Dev1";
		String categoryName1 = "Dev2";
		
		// click on add category
		selenium.click("//div[4]/div/div[2]/div[3]/div/span/span/span/span[3]");
		selenium.click("css=input.dijitOffScreen");
		Thread.sleep(2000);
		
		// title
		selenium.type("id=title", categoryName);

		// save
		selenium.click("id=save_label");
		Thread.sleep(2000);  
	
		// click on add category
		selenium.click("//div[4]/div/div[2]/div[3]/div/span/span/span/span[3]");
		selenium.click("css=input.dijitOffScreen");
		Thread.sleep(2000);
			
		// title
		selenium.type("id=title", categoryName1);
		
		// save
		selenium.click("id=save_label");
		Thread.sleep(3000);  
		
		
		manageMenu();
  		selenium.click(CMSConstants.MANAGE_WORKFLOWS_PAGE_VERIFY);
  		waitForText("Manage Workflows");
  		
		selenium.click("id=dijit_form_Button_0_label");
  		selenium.click("//input[@value='Add Workflow']");
  		Thread.sleep(3000);
		selenium.type("id=id", "complex");
		selenium.type("id=label", "test");
		selenium.type("id=states", "[published]\nlabel= \"Published\"\ntransitions.review.label= \"Demote to Review\"\ntransitions.draft.label= \"Demote to Draft\"");
		selenium.click("id=save_label");
		Thread.sleep(3000);
	
		
		// go to general settings
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// create a description
		selenium.type("id=description", "Hello from Dev");
		selenium.click("name=save");
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		
		// create a new role
		// click on manage -- roles
		manageMenu();
		selenium.click(CMSConstants.MANAGE_ROLES);
		waitForText("Manage Roles");
	
		// click to create a new role
		selenium.click("//span[@id='dijit_form_Button_0']/span");
		selenium.click("//input[@value='Add Role']");
		Thread.sleep(2000);
		selenium.type("id=id", "Tester");
		selenium.click("id=save_label"); 
		Thread.sleep(2000);
		
		
		// permissions
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_PERMISSIONS_PAGE_VERIFY);
		Thread.sleep(4000);
		
		// check categories for tester role
		selenium.click("//input[@roleid='Tester']");
		selenium.click("//span[contains(@id, 'dijit_form_Button_0_label')]");		
		Thread.sleep(2000);
	
		// Click on Manage --> Manage content types
		manageMenu();
		
		selenium.click(CMSConstants.MANAGE_CONTENT_TYPES);
		Thread.sleep(3000);
		
		selenium.click("id=dijit_form_Button_0_label"); 
		selenium.click("//input[@value='Add Content Type']");
		Thread.sleep(3000);
		selenium.type("id=id", "Video");
		selenium.type("id=label", "Video");
		selenium.type("id=group", "Pages");
		selenium.type("id=description", "Video");
		selenium.type("id=elements", "[checkbox]\ntype = \"checkbox\"\noptions.label = \"Hockey pool participation?\"\noptions.checkedValue = \"Yes\"\noptions.uncheckedValue = \"No\"\noptions.checked = true");
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		// go to Modules page 
			manageMenu();
			selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			
			// check to see if analytics module is enabled
			selenium.type("id=search-query", "analytics");
			Thread.sleep(3000);
			
			if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
				{ // disable analytics
				selenium.click("css=div.row-id-analytics span.dijitDropDownButton");
				Thread.sleep(3000);
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_12-button-action_label')]");  
				Thread.sleep(3000); 
			    moduleSET = false;
				}
			
					else { // enable the analytics module
						
					selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					
					selenium.type("id=search-query", "analytics");
					Thread.sleep(3000);
					
					// enable analytics
					selenium.click("css=div.row-id-analytics span.dijitDropDownButton");
					Thread.sleep(3000);
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_12-button-action_label')]");  
					Thread.sleep(3000);
				    moduleSET = true;
				}
			
		
		// go home
		backToHome();
		
		// switch back to Live
		selenium.click(CMSConstants.LIVE_LINK);
		
		verifyContentElements();
		Thread.sleep(2000);
		selenium.click("//a[@href='/add/type/basic-page']");
		Thread.sleep(2000);
		
  		selenium.type("id=title", "Basic Page After Search Tab");
  		// Click body and enter info
  		Thread.sleep(1000);
  		
  		
  		// click Menu and position this page before search
   		selenium.click("id=add-content-toolbar-button-Menus_label");
  		Thread.sleep(1000);
		selenium.click("id=menus-addMenuItem_label");
		Thread.sleep(1000);
		selenium.click("name=menus[addMenuItem]");
		Thread.sleep(1000);
  		
		// check for the action menu selections
		String quart_detailid   = "10365";
		 String quart_testname   = "MenuToolbarActionText";
		 String quart_description= "menu toolbar action text verify";
		
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
		// Write to file for checking manage content type page
	 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-contentAction')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
			
			// check drop down selection for View As options				
			// place them into a string array
			String[] viewAsValues = selenium.getSelectOptions("//select[contains(@name, 'contentAction')]");
						
			// verify if the Current Status exists in the selection list 
			boolean contentValues  = ArrayUtils.contains(viewAsValues, "Go To Page");
			boolean contentValues1 = ArrayUtils.contains(viewAsValues, "View Image");
			boolean contentValues2 = ArrayUtils.contains(viewAsValues, "Download File");

			quart_detailid   = "10366";
			quart_testname   = "MenuToolbarGoToPageSelection";
			quart_description= "verify create link go to page selection";
			if (contentValues)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			quart_detailid   = "10367";
			quart_testname   = "MenuToolbarViewAsImageSelection";
			quart_description= "verify create link view as image selection";
			if (contentValues1)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			quart_detailid   = "10368";
			quart_testname   = "MenuToolbarDownloadFileSelection";
			quart_description= "verify create link download file selection";
			if (contentValues2)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
  		
			selenium.select("id=menus-0-position", "label=After");
			selenium.select("id=menus-0-location", "label=regexp:\\s+Search");
	  		Thread.sleep(2000);
			
			
			
			
  		// Save page form
  		selenium.click("id=add-content-toolbar-button-Save_label");
 			
  		Thread.sleep(1000);
  		// click review and save
		selenium.click("id=workflow-state-published");
		Thread.sleep(1000);
		selenium.click("id=save_label");
  		Thread.sleep(2000);
  		
  		
		// create categories
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CATEGORIES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
	
		String categoryName3  = "Live1";
		String categoryName4  = "Live2";
		
		// click on add category
		selenium.click("css=.grid-footer .button .dijitButton .dijitButtonNode .dijitButtonContents");
		selenium.click("css=input.dijitOffScreen");
		Thread.sleep(2000);
		
		// title
		selenium.type("id=title", categoryName3);

		// save
		selenium.click("id=save_label");
		Thread.sleep(2000);  
	
		// click on add category
		selenium.click("css=.grid-footer .button .dijitButton .dijitButtonNode .dijitButtonContents");
		selenium.click("css=input.dijitOffScreen");
		Thread.sleep(2000);
			
		// title
		selenium.type("id=title", categoryName4);
		
		// save
		selenium.click("id=save_label");
		Thread.sleep(2000); 
		
		// go to general settings
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SITE_SETTINGS_VERIFY);
		Thread.sleep(3000);
		
		// create a description
		selenium.type("id=description", "Hello from Live");
		selenium.click("name=save");
		selenium.click("id=save_label");
		Thread.sleep(2000);
  		
		
		
		// go home
		backToHome(); 
		
		// pull from dev
		selenium.click(CMSConstants.LIVE_LINK);
		selenium.click("id=dijit_MenuItem_6_text");
		Thread.sleep(4000);
		
		// verifications
		 quart_detailid   = "9668";
		  quart_testname   = "PullFromContentCheck";
		  quart_description= "verify pull from dialog paths - content";
		
		// Write to file for checking manage content type page
			if (selenium.isElementPresent("//input[contains(@id, 'pull-paths-content')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		 // verify pull from content
		  quart_detailid   = "9683"; 
		  quart_testname   = "PullFromEntriesCheck";
		  quart_description= "verify pull from dialog paths - entries";
		
			if (selenium.isTextPresent("Entries"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
  		
  		
			// verify pull from content
			  quart_detailid   = "9684"; 
			  quart_testname   = "PullFromTypesCheck";
			  quart_description= "verify pull from dialog paths - types";
			
				if (selenium.isTextPresent("Types"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	  		
				
			
			// verify pull from content
			  quart_detailid   = "9669"; 
			  quart_testname   = "PullFromContentText";
			  quart_description= "verify pull from dialog paths - content";
			
				if (selenium.isTextPresent("Content"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  		
	
	
				// verify pull from content
				  quart_detailid   = "9672"; 
				  quart_testname   = "PullFromCategoryCheck";
				  quart_description= "verify pull from dialog paths - categories";
				
					if (selenium.isTextPresent("Categories"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			  		
	
				// verify pull from content
				  quart_detailid   = "9675"; 
				  quart_testname   = "PullFromMenusCheck";
				  quart_description= "verify pull from dialog paths - menus";
				
					if (selenium.isTextPresent("Menus"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				  		
		
			// verify pull from content
			  quart_detailid   = "9679"; 
			  quart_testname   = "PullFromWidgetsCheck";
			  quart_description= "verify pull from dialog paths - widgets";
			
				if (selenium.isTextPresent("Widgets"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			  		
	
				// verify pull from content
				  quart_detailid   = "9682"; 
				  quart_testname   = "PullFromConfigCheck";
				  quart_description= "verify pull from dialog paths - configuration";
				
					if (selenium.isTextPresent("Configuration"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						  		
				
					
				// verify pull from content
				  quart_detailid   = "9687"; 
				  quart_testname   = "PullFromSettingsCheck";
				  quart_description= "verify pull from dialog paths - general settings";
				
					if (selenium.isTextPresent("General Settings"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							  		
  		
				// verify pull from content
				  quart_detailid   = "9689"; 
				  quart_testname   = "PullFromPermissionsCheck";
				  quart_description= "verify pull from dialog paths - permissions";
				
					if (selenium.isTextPresent("Permissions"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							  		
		  		
					
			// verify pull from content
			  quart_detailid   = "9740"; 
			  quart_testname   = "PullFromSelectText";
			  quart_description= "verify pull from dialog select text";
			
				if (selenium.isTextPresent("Select What to Pull"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						  		
	  			
				// verify pull from content
				  quart_detailid   = "9741"; 
				  quart_testname   = "PullFromItemsText";
				  quart_description= "verify pull from dialog items text";
				
					if (selenium.isTextPresent("Items"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							  		
		  			
				// verify pull from content
				  quart_detailid   = "9742"; 
				  quart_testname   = "PullFromQuantityText";
				  quart_description= "verify pull from dialog quantity text";
				
					if (selenium.isTextPresent("Quantity"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							  		
					
			// verify pull from content
				quart_detailid = "9690";
				quart_testname = "PullFromWarningMessageText";
				quart_description = "verify pull from warning message text";
				
				if (selenium.isTextPresent("in the Live branch will be overwritten."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
				
				
				// verify pull from comments text
				quart_detailid ="9703";
				quart_testname = "PullFromCommentsText";
				quart_description = "verify pull from comments text";
				
				if (selenium.isTextPresent("Comments"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
				
				// verify pull from comments text
				quart_detailid ="9683";
				quart_testname = "PullFromPublishedEntriesText";
				quart_description = "verify pull from published entries text";
				
				if (selenium.isTextPresent("Published Entries"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
				
				// verify pull from comments text
				quart_detailid ="9829";
				quart_testname = "PullFromUnPublishedEntriesText";
				quart_description = "verify pull from unpublished entries text";
				
				if (selenium.isTextPresent("Unpublished Entries"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
				
			
				
				quart_detailid   = "9743";
				  quart_testname   = "PullFromPublishedEntriesCheck";
				  quart_description= "verify pull from dialog - published entries checkbox";
				
				// Write to file for checking manage content type page
					if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-contentpublished-entries') and contains(@checked, 'checked')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
			 quart_detailid   = "9828";
			  quart_testname   = "PullFromUnpublishedEntriesCheck";
			  quart_description= "verify pull from dialog - unpublished entries checkbox";
			
			// Write to file for checking manage content type page
				if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-contentunpublished-entries') and contains(@checked, 'checked')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
				 quart_detailid   = "9747";
				  quart_testname   = "PullFromTypesCheck";
				  quart_description= "verify pull from dialog - types checkbox";
				
				// Write to file for checking manage content type page
					if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-contenttypes') and contains(@checked, 'checked')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
		
					 quart_detailid   = "9671";
					  quart_testname   = "PullFromCategoriesCheck";
					  quart_description= "verify pull from dialog - categories checkbox";
					
					// Write to file for checking manage content type page
						if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-categories') and contains(@checked, 'checked')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
		

				 quart_detailid   = "9674";
				  quart_testname   = "PullFromMenusCheck";
				  quart_description= "verify pull from dialog - menus checkbox";
				
				// Write to file for checking manage content type page
					if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-menus') and contains(@checked, 'checked')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
			
		
			 quart_detailid   = "9678";
			  quart_testname   = "PullFromWidgetsCheck";
			  quart_description= "verify pull from dialog - widgets checkbox";
			
			// Write to file for checking manage content type page
				if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-widgets') and contains(@checked, 'checked')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
				 quart_detailid   = "9698";
				  quart_testname   = "PullFromPermissionsCheck";
				  quart_description= "verify pull from dialog - permissions checkbox";
				
				// Write to file for checking manage content type page
					if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-configurationpermissions')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
					 quart_detailid   = "9681";
					  quart_testname   = "PullFromConfigCheck";
					  quart_description= "verify pull from dialog - config checkbox";
					
					// Write to file for checking manage content type page
						if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-configuration')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					
					
				 quart_detailid   = "9696";
				  quart_testname   = "PullFromGeneralSettingsCheck";
				  quart_description= "verify pull from dialog - Gen. Settings checkbox";
				
				// Write to file for checking manage content type page
					if (selenium.isElementPresent("//input[contains(@id, 'pull-paths-configurationgeneral-settings')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
					
					
										
				 quart_detailid   = "9704";
				  quart_testname   = "PullFromCommentsCheck";
				  quart_description= "verify pull from dialog - Comments checkbox";
				
				// Write to file for checking manage content type page
					if (selenium.isElementPresent("//input[contains(@id, 'pull-paths-comments')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
															
				 quart_detailid   = "9688";
				  quart_testname   = "PullFromModules";
				  quart_description= "verify pull from dialog - Modules";
				
			// Write to file for checking manage content type page
				if (selenium.isTextPresent("Module Settings"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
				 quart_detailid   = "9697";
				  quart_testname   = "PullFromModulesCheck";
				  quart_description= "verify pull from dialog - Modules Check";
				
				// Write to file for checking manage content type page
					if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-configurationmodule-settings')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					
					 quart_detailid   = "9751";
					  quart_testname   = "PullFromWorkflowCheck";
					  quart_description= "verify pull from dialog - Workflow Check";
					
					// Write to file for checking manage content type page
						if (selenium.isElementPresent("//input[@type='checkbox' and contains(@id, 'pull-paths-workflows')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
					
						 quart_detailid   = "9752";
						  quart_testname   = "PullFromWorkflowText";
						  quart_description= "verify pull from dialog - Workflow Text";
						
						// Write to file for checking manage content type page
							if (selenium.isTextPresent("Workflows"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
	
							
					
			// **** Pull from Dev dialog link popup elements **** //
					
					// click on quantity for Content Entries - basic page  
					  quart_detailid   = "9735";
					  quart_testname   = "PullFromPublishedEntriesPopupPageText";
					  quart_description= "verify pull from dialog - basic page title popup";
					  
					  //selenium.click("link=1");
					  selenium.click("id=dijit__Widget_0");
					  Thread.sleep(2000);
					
		
						if (selenium.isTextPresent("Basic Page Published Mode for Site"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
					  	
						  quart_detailid   = "9735";
						  quart_testname   = "PullFromPublishedEntriesPopupPagePopup";
						  quart_description= "verify pull from dialog - basic page popup text";
											  
						if (selenium.isTextPresent("Branching Flow"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
					
					  quart_detailid   = "9735";
					  quart_testname   = "PullFromPublishedEntriesPageActionPopup";
					  quart_description= "verify pull from dialog - basic page action text";
				  
						if (selenium.isTextPresent("Action"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
						
						
					  quart_detailid   = "9735";
					  quart_testname   = "PullFromPublishedEntriesPageEditPopup";
					  quart_description= "verify pull from dialog - basic page add text";
					  
						if (selenium.isTextPresent("Add "))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
						
					
				// click on quantity for Content Entries - basic page  
				  quart_detailid   = "9831";
				  quart_testname   = "PullFromUnPublishedEntriesPopupPageText";
				  quart_description= "verify pull from dialog - basic page title popup";
				  
				  //selenium.click("link=1");
				  selenium.click("id=dijit__Widget_1");
				  Thread.sleep(2000);
				
				  
					if (selenium.isTextPresent("Title"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
				  	
					  quart_detailid   = "9831";
					  quart_testname   = "PullFromUnPublishedEntriesPopupDialog";
					  quart_description= "verify pull from dialog - basic page popup text";
										  
					if (selenium.isTextPresent("Basic Page for Site Branching Flow"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
				
				  quart_detailid   = "9831";
				  quart_testname   = "PullFromUnPublishedEntriesPageActionPopup";
				  quart_description= "verify pull from dialog - basic page action text";
			  
					if (selenium.isTextPresent("Action"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
					
					
				  quart_detailid   = "9831";
				  quart_testname   = "PullFromUnPublishedEntriesPageEditPopup";
				  quart_description= "verify pull from dialog - basic page add text";
				  
					if (selenium.isTextPresent("Add "))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
										
	
			
			// click on quantity for Content Entries - type
			  quart_detailid   = "9758";
			  quart_testname   = "PullFromTypePopupText";
			  quart_description= "verify pull from dialog - type popup";
			  
			  //selenium.click("link=1");
			  selenium.click("id=dijit__Widget_2");
			  Thread.sleep(2000);
			
			  
				if (selenium.isTextPresent("Type"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
			  	
				  quart_detailid   = "9763";
				  quart_testname   = "PullFromTypeVideoPopup";
				  quart_description= "verify pull from dialog - video popup text";
									  
				if (selenium.isTextPresent("Video"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
			
			  quart_detailid   = "9759";
			  quart_testname   = "PullFromTypeActionPopup";
			  quart_description= "verify pull from dialog - type action text";
		  
				if (selenium.isTextPresent("Action"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
				
				
			  quart_detailid   = "9764";
			  quart_testname   = "PullFromTypeEditPopup";
			  quart_description= "verify pull from dialog - type add text";
			  
				if (selenium.isTextPresent("Add"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			
				// click on quantity for Categories
				  quart_detailid   = "9760";
				  quart_testname   = "PullFromCategoryPopupText";
				  quart_description= "verify pull from dialog - category popup";
				  
				  //selenium.click("link=1");
				  selenium.click("id=dijit__Widget_3");
				  Thread.sleep(2000);
				
				  
					if (selenium.isTextPresent("Category"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
				  	
					  quart_detailid   = "9766";
					  quart_testname   = "PullFromCategoryDevPopupText";
					  quart_description= "verify pull from dialog - dev popup text";
										  
					if (selenium.isTextPresent("Dev1"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
				
				  quart_detailid   = "9765";
				  quart_testname   = "PullFromCategoryActionPopup";
				  quart_description= "verify pull from dialog - category action text";
			  
					if (selenium.isTextPresent("Action"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
					
					
				  quart_detailid   = "9767";
				  quart_testname   = "PullFromCategoryAddPopup";
				  quart_description= "verify pull from dialog - category add text";
				  
					if (selenium.isTextPresent("Add"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
						
				
				
			// click on quantity for Menus
			  quart_detailid   = "9768";
			  quart_testname   = "PullFromMenusPopupText";
			  quart_description= "verify pull from dialog - menus popup";
			  
			  //selenium.click("link=1");
			  selenium.click("id=dijit__Widget_4");
			  Thread.sleep(2000);
			
			  
				if (selenium.isTextPresent("Menu"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
			  	
				  quart_detailid   = "9770";
				  quart_testname   = "PullFromMenusPrimaryPopupText";
				  quart_description= "verify pull from dialog - menus primary popup text";
									  
				if (selenium.isTextPresent("Primary"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
			
			  quart_detailid   = "9769";
			  quart_testname   = "PullFromMenusActionPopup";
			  quart_description= "verify pull from dialog - menus action text";
		  
				if (selenium.isTextPresent("Action"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
				
				
			  quart_detailid   = "9779";
			  quart_testname   = "PullFromMenusEditPopup";
			  quart_description= "verify pull from dialog - menus edit text";
			  
				if (selenium.isTextPresent("Edit"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
					
							
		
				// click on quantity for Widgets
				  quart_detailid   = "9772";
				  quart_testname   = "PullFromWidgetsPopupText";
				  quart_description= "verify pull from dialog - widgets popup";
				  
				  //selenium.click("link=1");
				  selenium.click("id=dijit__Widget_5");
				  Thread.sleep(2000);
				
				  
					if (selenium.isTextPresent("Widget"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
				  	
					  quart_detailid   = "9774";
					  quart_testname   = "PullFromWidgetsSearchText";
					  quart_description= "verify pull from dialog - widgets search popup text";
										  
					if (selenium.isTextPresent("Header - Search Widget "))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
				
				  quart_detailid   = "9773";
				  quart_testname   = "PullFromWidgetsActionPopup";
				  quart_description= "verify pull from dialog - widgets action text";
			  
					if (selenium.isTextPresent("Action"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
					
					
				  quart_detailid   = "9775";
				  quart_testname   = "PullFromWidgetsAddPopup";
				  quart_description= "verify pull from dialog - widgets add text";
				  
					if (selenium.isTextPresent("Add"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
			
			
					
					
					
					// click on quantity for Workflows
					  quart_detailid   = "9776";
					  quart_testname   = "PullFromWorkflowPopupText";
					  quart_description= "verify pull from dialog - Workflow popup";
					  
					  //selenium.click("link=1");
					  selenium.click("id=dijit__Widget_6");
					  Thread.sleep(2000);
					
					  
						if (selenium.isTextPresent("Workflow"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
						
						
						  quart_detailid   = "9778";
						  quart_testname   = "PullFromWorkflowTestText";
						  quart_description= "verify pull from dialog - Workflow test text";					 
						  
						if (selenium.isTextPresent("test"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
						
					  quart_detailid   = "9777";
					  quart_testname   = "PullFromWorkflowActionPopup";
					  quart_description= "verify pull from dialog - Workflow action text";
				  
						if (selenium.isTextPresent("Action"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
						
						
					  quart_detailid   = "9779";
					  quart_testname   = "PullFromWorkflowAddPopup";
					  quart_description= "verify pull from dialog - Workflow add text";
					  
						if (selenium.isTextPresent("Add"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
						
					
					
					// click on quantity for Comments
					  quart_detailid   = "10016";
					  quart_testname   = "PullFromCommentsPopupText";
					  quart_description= "verify pull from dialog - Comments popup";
					  
					  selenium.click("id=dijit__Widget_7");
						Thread.sleep(2000);
					
						
						if (selenium.isTextPresent("File"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
						
						  quart_detailid   = "10013";
						  quart_testname   = "PullFromCommentsFileText";
						  quart_description= "verify pull from dialog - Comments search popup text";					 
						  

						if (selenium.isTextPresent("comments/content"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
						
					  quart_detailid   = "10014";
					  quart_testname   = "PullFromCommentsActionPopup";
					  quart_description= "verify pull from dialog - Comments action text";
				  
						if (selenium.isTextPresent("Action"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 				        
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
						
					  quart_detailid   = "10015";
					  quart_testname   = "PullFromCommentsAddPopup";
					  quart_description= "verify pull from dialog - Comments add text";
					  
						if (selenium.isTextPresent("Add"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				 

						
								
						
					// click on quantity for Analytics modules
					  quart_detailid   = "9780";
					  quart_testname   = "PullFromModulesPopupText";
					  quart_description= "verify pull from dialog - modules popup";
					  
					  //selenium.click("link=1");
					  selenium.click("id=dijit__Widget_9"); 
					  Thread.sleep(2000);
					
					  
						if (selenium.isTextPresent("Module"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
					  	
						  quart_detailid   = "9782";
						  quart_testname   = "PullFromModulesAnalyticsText";
						  quart_description= "verify pull from dialog - modules analytics popup text";
											  
						if (selenium.isTextPresent("Analytics"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
					
					  quart_detailid   = "9781";
					  quart_testname   = "PullFromModulesActionPopup";
					  quart_description= "verify pull from dialog - modules action text";
				  
						if (selenium.isTextPresent("Action"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
						
						
					  quart_detailid   = "9783";
					  quart_testname   = "PullFromModulesEnablePopup";
					  quart_description= "verify pull from dialog - modules enable text";
					  
					  
					  // check to see if module is enabled or disabled
					  	System.out.println("moduleSET =" + moduleSET);
					    if(moduleSET==true) {
						if (selenium.isTextPresent("Enable"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	}					
						
					    else { if (selenium.isTextPresent("Disable"))
					    	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	}	
					    
					    
						 quart_detailid   = "9676";
						  quart_testname   = "PullFromModulesConflictIcon";
						  quart_description= "verify pull from dialog - conflict icon";
						  
							if (selenium.isElementPresent("//input[@type='checkbox' and contains(@class, 'conflict')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
										
					
							
					// click on the clone radio button
							
					selenium.click("id=pull-mode-copy");
					Thread.sleep(4000);
					 quart_detailid   = "0";
					  quart_testname   = "PullFromModulesCloneRadioButton";
					  quart_description= "verify pull from dialog - radio button";
					  
						if (selenium.isElementPresent("//input[@id='pull-mode-copy' and contains(@checked, 'checked') and contains(@value, 'copy')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
						
										
			backToHome();
	}
}


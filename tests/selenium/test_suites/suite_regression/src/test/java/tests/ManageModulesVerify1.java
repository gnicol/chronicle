package tests;	

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;
import tests.CMSConstants;

//This code clicks on manage --> modules and verifies the analytics title

public class ManageModulesVerify1 extends shared.BaseTest {
	
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
		ManageModulesVerify1();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void ManageModulesVerify1() throws Exception {
		
		String versionString = getClientCodeline(clientCodeline);
		
		// go to manage modules
	
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
							
				// menu modules
				
				selenium.type("id=search-query", "menu");
				Thread.sleep(2000);
				
				
				 String quart_detailid   = "9942";
				 String quart_testname   = "ManageModulesMenutext";
				 String quart_description= "verify Menu text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Menu"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9943";
				  quart_testname   = "ManageModulesMenutext";
				  quart_description= "verify Menu text";
				if (selenium.isTextPresent("Provides menu facilities."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6123";
				  quart_testname   = "ManageModulesMenuIcon";
				  quart_description= "verify Menu icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/menu/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9945";
				  quart_testname   = "ManageModulesMenuPerforcetext";
				  quart_description= "verify Menu perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9944";
				  quart_testname   = "ManageModulesMenuVersion";
				  quart_description= "verify Menu version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2349";
				  quart_testname   = "ManageModulesMenusupport";
				  quart_description= "verify Menu support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2348";
				  quart_testname   = "ManageModulesMenuWWW";
				  quart_description= "verify Menu WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9946";
				  quart_testname   = "ManageModulesMenuStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
			
										
				
					
				// setup
				
				selenium.type("id=search-query", "setup");
				Thread.sleep(2000);
				
				 quart_detailid   = "6850";
				  quart_testname   = "ManageModulesSetuptext";
				  quart_description= "verify Setup text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Setup"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "2298";
				  quart_testname   = "ManageModulesSetuptext";
				  quart_description= "verify Setup text";
				if (selenium.isTextPresent("Provides site setup wizard."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6125";
				  quart_testname   = "ManageModulesSetupIcon";
				  quart_description= "verify Setup icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/setup/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9909";
				  quart_testname   = "ManageModulesSetupPerforcetext";
				  quart_description= "verify Setup perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9908";
				  quart_testname   = "ManageModulesSetupVersion";
				  quart_description= "verify Setup version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2351";
				  quart_testname   = "ManageModulesSetupsupport";
				  quart_description= "verify Setup support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2350";
				  quart_testname   = "ManageModulesSetupWWW";
				  quart_description= "verify Setup WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9910";
				  quart_testname   = "ManageModulesSetupStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
				
				
				
				// Search module
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.type("id=search-query", "search");
				Thread.sleep(2000);
				
				 quart_detailid   = "9901";
				  quart_testname   = "ManageModulesSearchtext";
				  quart_description= "verify Search text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Search"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9902";
				  quart_testname   = "ManageModulesSearchtext";
				  quart_description= "verify Search text";
				if (selenium.isTextPresent("Provides full-text content indexing and search capabilities."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				 
				
				 quart_detailid   = "6124";
				  quart_testname   = "ManageModulesSearchIcon";
				  quart_description= "verify Search icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/search/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9904";
				  quart_testname   = "ManageModulesSearchPerforcetext";
				  quart_description= "verify Search perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9903";
				  quart_testname   = "ManageModulesSearchVersion";
				  quart_description= "verify Search version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2371";
				  quart_testname   = "ManageModulesSearchsupport";
				  quart_description= "verify Search support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2370";
				  quart_testname   = "ManageModulesSearchWWW";
				  quart_description= "verify Search WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9905";
				  quart_testname   = "ManageModulesSearchStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				selenium.click("css=div.row-id-search span.dijitDropDownButton");
				Thread.sleep(4000);
				
				
				manageMenu(); 
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				// disable search
				selenium.click("css=div.row-id-search span.dijitDropDownButton");
				Thread.sleep(3000);

				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_7-button-action_label')]");  
		     	Thread.sleep(4000);
				
		     	
		     	selenium.type("id=search-query", "search");
				Thread.sleep(2000);
				
		     	// enable search
				selenium.click("css=div.row-id-search span.dijitDropDownButton");
				Thread.sleep(3000);	
				
				manageMenu(); 
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			
				selenium.click("css=div.row-id-search span.dijitDropDownButton");
				Thread.sleep(3000);
				
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_7-button-action_label')]");  
		     	Thread.sleep(4000);
			
				
				if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
				{ System.out.println("Search module already enabled"); }
			
					else { // search the IDE module
						
					selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					
					// Search IDE
					selenium.click("css=div.row-id-search span.dijitDropDownButton");
					Thread.sleep(3000);
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_7-button-action_label')]");  
					Thread.sleep(3000);
				}
			
			
				// configure search module
				selenium.clickAt("css=div.row-id-search span.dijitButtonContents","");
				Thread.sleep(2000);
				quart_detailid   = "7062";
				  quart_testname   = "ManageModulesSearchConfigure";
				  quart_description= "verify configure link";
				if (selenium.isTextPresent(("Search Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "7076";
				  quart_testname   = "ManageModulesSearchText";
				  quart_description= "verify search text";
				if (selenium.isTextPresent(("Search Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
				quart_detailid   = "9992";
				  quart_testname   = "ManageModulesSearchConfigText";
				  quart_description= "verify search config text";
				if (selenium.isTextPresent(("Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
				quart_detailid   = "9993";
				  quart_testname   = "ManageModulesSearchConfigMaxBufferedText";
				  quart_description= "verify search config max buffered text";
				if (selenium.isTextPresent(("Buffer Limit")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				quart_detailid   = "9994";
				  quart_testname   = "ManageModulesSearchConfigMaxBufferedForm";
				  quart_description= "verify search config max buffered form";
					if (selenium.isElementPresent(("//input[contains(@id, 'maxBufferedDocs')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				quart_detailid   = "8601";
				  quart_testname   = "ManageModulesSearchConfigMaxBufferedText1";
				  quart_description= "verify search config max buffered text1";
				if (selenium.isTextPresent(("The maximum number of documents buffered in memory at one time.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				quart_detailid   = "7079";
				  quart_testname   = "ManageModulesSearchConfigMaxMergeText";
				  quart_description= "verify search config max merge text";
				if (selenium.isTextPresent(("Merge Limit")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "8602";
				  quart_testname   = "ManageModulesSearchConfigMaxMergeForm";
				  quart_description= "verify search config max merge form";
					if (selenium.isElementPresent(("//input[contains(@id, 'maxMergeDocs')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
					quart_detailid   = "7088";
					  quart_testname   = "ManageModulesSearchConfigMaxMergeText1";
					  quart_description= "verify search config max merge text1";
					if (selenium.isTextPresent(("The maximum number of documents merged into an index segment by auto-optimization.")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
				
					quart_detailid   = "9995";
					  quart_testname   = "ManageModulesSearchConfigMergeFactorText";
					  quart_description= "verify search config max merge text";
					if (selenium.isTextPresent(("Merge Factor")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "9996";
				  quart_testname   = "ManageModulesSearchConfigMergeFactorForm";
				  quart_description= "verify search config merge factor form";
					if (selenium.isElementPresent(("//input[contains(@id, 'mergeFactor')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					quart_detailid   = "9997";
					  quart_testname   = "ManageModulesSearchConfigMergeFactorText1";
					  quart_description= "verify search config merge factor text1";
					if (selenium.isTextPresent(("Increasing this number decreases the frequency of auto-optimization.")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
				
					quart_detailid   = "7089";
					quart_testname   = "ManageModulesSearchConfigSaveButton";
					quart_description= "verify search config Save button";
					if (selenium.isElementPresent(("//span[contains(@id, 'Save')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
			
				quart_detailid   = "9998";
				  quart_testname   = "ManageModulesSearchConfigMaintenanceText";
				  quart_description= "verify search config maintenance text";
				if (selenium.isTextPresent(("Maintenance")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
			
		
				quart_detailid   = "9999";
				  quart_testname   = "ManageModulesSearchConfigMaintenanceText1";
				  quart_description= "verify search config maintenance text1";
				if (selenium.isTextPresent(("Optimize the search index to improve performance.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
			
				quart_detailid   = "10000";
				  quart_testname   = "ManageModulesSearchConfigOptimizeButton";
				  quart_description= "verify search config Optimize button";
					if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_3_label')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
					quart_detailid   = "10001";
					  quart_testname   = "ManageModulesSearchConfigRebuildText";
					  quart_description= "verify search config rebuild text";
					if (selenium.isTextPresent(("Rebuild the search index from existing data.")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
					
					quart_detailid   = "10002";
					  quart_testname   = "ManageModulesSearchConfigRebuildButton";
					  quart_description= "verify search config Rebuild button";
						if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_4_label')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
											
									
				// click search optimize
					//selenium.click("css=.dj_contentbox .index-action .dijitDialog .dijitDialogPaneContent .scrollNode .search-configuration .search-maintenance .dijitButton .dijitButtonNode .dijitButtonContents"); 
						//selenium.click("id=optimizeSearchButton_label");
						selenium.click("id=dijit_form_Button_3_label");
						Thread.sleep(4000);
				
					quart_detailid   = "7098";
					  quart_testname   = "ManageModulesSearchOptimizePopupDialog";
					  quart_description= "verify search optimize pop up";
						if (selenium.isTextPresent(("Search Optimize")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
						
					quart_detailid   = "7101";
					  quart_testname   = "ManageModulesSearchOptimizePopupDialogText2";
					  quart_description= "verify search optimize pop up text2";
						if (selenium.isTextPresent(("Done. Search index optimization completed.")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "10004";
					  quart_testname   = "ManageModulesSearchOptimizePopupDialogStatusBar";
					  quart_description= "verify search optimize pop up statusbar";
						if (selenium.isElementPresent(("//div[contains(@class, 'dijitProgressBarLabel')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
						
					// verify 'x' tooltip
					quart_detailid   = "7096";
					quart_testname   = "ManageModulesSearchOptimize_x_Tooltip";
					quart_description= "verify search rebuild 'x' tooltip";
					
					// get tooltip attribute
					String tooltip = selenium.getAttribute("//div[21]/div/span[2]/@title");

					boolean tooltipTrue = tooltip.equals("Cancel");
				
					if (tooltipTrue) 
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
				
						
				//selenium.click("id=p4cms_ui_ProgressBarDialog_0-button-close_label");

				quart_detailid   = "7092";
				  quart_testname   = "ManageModulesSearchOptimizePopupDialogCloseButton";
				  quart_description= "verify search optimize pop up close button";
					if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ProgressBarDialog_0-button-close')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
										
		       
				selenium.click("//div[21]/div/span[2]");	
				quart_detailid   = "7094"; 
				  quart_testname   = "ManageModulesSearchOptimizeClick_x";
				  quart_description= "verify search optimize click x";
					if (selenium.isTextPresent(("Search Optimize")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
					
			 
				  	
				// click on search rebuild button
					//selenium.click("id=rebuildSearchButton_label");
					selenium.click("id=dijit_form_Button_4_label");
					Thread.sleep(6000);
				
				quart_detailid   = "7099";
				  quart_testname   = "ManageModulesSearchRebuildPopupDialogText";
				  quart_description= "verify search rebuild pop up text";
					if (selenium.isTextPresent(("Search Rebuild")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					quart_detailid   = "10006";
					  quart_testname   = "ManageModulesSearchRebuildPopupDialogStatusBar";
					  quart_description= "verify search rebuild pop up statusbar";
						if (selenium.isElementPresent(("//div[contains(@class, 'dijitProgressBarLabel')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
										
			
				 // verify 'x' tooltip
				quart_detailid   = "7097";
				quart_testname   = "ManageModulesSearchRebuild_x_Tooltip";
				quart_description= "verify search rebuild 'x' tooltip";
				
				// get tooltip attribute
				String tooltip1 = selenium.getAttribute("//div[22]/div/span[2]/@title");

				boolean tooltipTrue1 = tooltip1.equals("Cancel");
			
				if (tooltipTrue1) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
				
				
				//selenium.click("id=p4cms_ui_ProgressBarDialog_0-button-close_label");

				quart_detailid   = "7093";
				  quart_testname   = "ManageModulesSearchRebuildPopupDialogCloseButton";
				  quart_description= "verify search rebuild pop up close button";
					if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ProgressBarDialog_1-button-close')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
		
				selenium.click("//div[22]/div/span[2]");	
				Thread.sleep(1000);
				quart_detailid   = "7095";
				  quart_testname   = "ManageModulesSearchRebuildClick_x";
				  quart_description= "verify search rebuild click x";
					if (selenium.isTextPresent(("Search Configuration")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
	
					quart_detailid   = "10733";
					  quart_testname   = "ManageModulesSearchConfigCancelButton";
					  quart_description= "verify search config cancel button";
						if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_1_label')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
										
					
						quart_detailid   = "10735";
						  quart_testname   = "ManageModulesSearchConfigCloseIcon";
						  quart_description= "verify search config close icon";
							if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
											
					// check tooltip
					
					quart_detailid = "10734";
					quart_testname = "ManageModulesSearchConfigTooltip";
					quart_description = "verify Search tooltip";
					
					String tooltip4 = selenium.getAttribute("//div[18]/div/span[2]/@title");
					boolean tooltip4True = tooltip4.equals("Cancel");
					
					if(tooltip4True)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description);
					}

					
					
					
					
				// Youtube module
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.type("id=search-query", "youtube");
				Thread.sleep(3000);
				
				  quart_detailid   = "9975";
				  quart_testname   = "ManageModulesYoutubetext";
				   quart_description= "verify Youtube text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("YouTube"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9976";
				  quart_testname   = "ManageModulesYoutubetext";
				  quart_description= "verify Youtube text";
				if (selenium.isTextPresent("Allows a user to configure a YouTube video to display in a widget."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6132";
				  quart_testname   = "ManageModulesYoutubeIcon";
				  quart_description= "verify Youtube icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/youtube/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9978";
				  quart_testname   = "ManageModulesYoutubePerforcetext";
				  quart_description= "verify Youtube perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9977";
				  quart_testname   = "ManageModulesYoutubeVersion";
				  quart_description= "verify Youtube version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2372";
				  quart_testname   = "ManageModulesYoutubeUsersupport";
				  quart_description= "verify Youtube support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2373";
				  quart_testname   = "ManageModulesYoutubeWWW";
				  quart_description= "verify Youtube WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				// youtube module initially disabled
		     	 quart_detailid   = "2365";
				  quart_testname   = "ManageModulesYoutubeStatusDisabled";
				  quart_description= "verify youtube status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
							
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				
					// enable youtube module
					selenium.clickAt("css=div.row-id-youtube span.dijitDropDownButton","");
					Thread.sleep(3000);
					
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_11-button-action_label')]");  
			     	Thread.sleep(4000);
			     	
			     	
			     	selenium.type("id=search-query", "youtube");
					Thread.sleep(3000);
					
					if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
					{   System.out.println("Youtube module already enabled"); }
				
						else { // enable the Youtube module
							
						selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
						waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

						// enable Youtube
						selenium.click("css=div.row-id-youtube span.dijitDropDownButton");
						Thread.sleep(4000);
						selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_11-button-action_label')]");  
						Thread.sleep(4000);
					}
		
				selenium.type("id=search-query", "youtube");
				Thread.sleep(4000);
				
				quart_detailid   = "6447";
				  quart_testname   = "ManageModulesYoutubeStatusEnabled";
				  quart_description= "verify youtube status enabled";
				if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
			
				 quart_detailid   = "9980";
				  quart_testname   = "ManageModulesYoutubeStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
				selenium.click("css=div.row-id-youtube span.dijitDropDownButton");
				Thread.sleep(3000);
			
				
				// site
				
				selenium.type("id=search-query", "site");
				Thread.sleep(2000);
				
				 quart_detailid   = "9911";
				  quart_testname   = "ManageModulesSitetext";
				  quart_description= "verify Site text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Site"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9912";
				  quart_testname   = "ManageModulesSitetext";
				  quart_description= "verify Site text";
				if (selenium.isTextPresent("Provides site-specific management facilities including configuration, modules and themes."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6126";
				  quart_testname   = "ManageModulesSiteIcon";
				  quart_description= "verify Site icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/site/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9914";
				  quart_testname   = "ManageModulesSitePerforcetext";
				  quart_description= "verify Site perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9913";
				  quart_testname   = "ManageModulesSiteVersion";
				  quart_description= "verify Site version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2353";
				  quart_testname   = "ManageModulesSitesupport";
				  quart_description= "verify Site support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2352";
				  quart_testname   = "ManageModulesSiteWWW";
				  quart_description= "verify Site WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9923";
				  quart_testname   = "ManageModulesSiteStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
					quart_detailid   = "9923";
				  quart_testname   = "ManageModulesSiteEnabled";
				  quart_description= "verify enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
				
				
				
				// system
				
				selenium.type("id=search-query", "system");
				Thread.sleep(2000);
				
				 quart_detailid   = "9915";
				  quart_testname   = "ManageModulesSystemtext";
				  quart_description= "verify System text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("System"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9916";
				  quart_testname   = "ManageModulesSystemtext";
				  quart_description= "verify System text";
				if (selenium.isTextPresent("Provides an overview of Perforce Chronicle, P4, and system information."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6127";
				  quart_testname   = "ManageModulesSystemIcon";
				  quart_description= "verify System icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/system/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9918";
				  quart_testname   = "ManageModulesSystemPerforcetext";
				  quart_description= "verify System perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9917";
				  quart_testname   = "ManageModulesSystemVersion";
				  quart_description= "verify System version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2355";
				  quart_testname   = "ManageModulesSystemsupport";
				  quart_description= "verify System support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2354";
				  quart_testname   = "ManageModulesSystemWWW";
				  quart_description= "verify System WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9924";
				  quart_testname   = "ManageModulesSystemStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
		
		
				
				selenium.type("id=search-query", "ui");
				Thread.sleep(2000);
				
				 quart_detailid   = "9861";
				  quart_testname   = "ManageModulesUItext";
				  quart_description= "verify UI text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("UI"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9863";
				  quart_testname   = "ManageModulesUItext";
				  quart_description= "verify UI text";
				if (selenium.isTextPresent("Provides global user interface capabililties and styling."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6128";
				  quart_testname   = "ManageModulesUIIcon";
				  quart_description= "verify UI icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/ui/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9867";
				  quart_testname   = "ManageModulesUIPerforcetext";
				  quart_description= "verify UI perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9865";
				  quart_testname   = "ManageModulesUIVersion";
				  quart_description= "verify UI version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2357";
				  quart_testname   = "ManageModulesUIsupport";
				  quart_description= "verify UI support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2356";
				  quart_testname   = "ManageModulesUIWWW";
				  quart_description= "verify UI WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9869";
				  quart_testname   = "ManageModulesUIStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				
				
				// URL modules
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.type("id=search-query", "url");
				Thread.sleep(2000);
				
				 quart_detailid   = "9963";
				  quart_testname   = "ManageModulesURLtext";
				  quart_description= "verify URL text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("URL"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9964";
				  quart_testname   = "ManageModulesURLtext";
				  quart_description= "verify URL text";
				if (selenium.isTextPresent("Provides support for assigning custom urls to content entries."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "7870";
				  quart_testname   = "ManageModulesURLIcon";
				  quart_description= "verify URL icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/url/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9966";
				  quart_testname   = "ManageModulesURLPerforcetext";
				  quart_description= "verify URL perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9965";
				  quart_testname   = "ManageModulesURLVersion";
				  quart_description= "verify URL version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "7868";
				  quart_testname   = "ManageModulesURLsupport";
				  quart_description= "verify URL support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "7869";
				  quart_testname   = "ManageModulesURLWWW";
				  quart_description= "verify URL WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9974";
				  quart_testname   = "ManageModulesURLStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				// disable url module
				selenium.clickAt("css=div.row-id-url span.dijitDropDownButton","");
				Thread.sleep(4000);
			
								
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_9-button-action_label')]");  
		     	Thread.sleep(5000);
		     	
		     	selenium.type("id=search-query", "url");
				Thread.sleep(3000);
				
		     	 quart_detailid   = "7862";
				  quart_testname   = "ManageModulesURLStatusDisabled";
				  quart_description= "verify URL status disabled";
				if (selenium.isElementPresent("//span[contains(@class, 'status disabled')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
				//enable url module
				selenium.click("css=div.row-id-url span.dijitDropDownButton");
				Thread.sleep(4000);
						
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_9-button-action_label')]");  
		     	Thread.sleep(5000);
		     	

				// confirm that URL has been re-enbaled, otherwise reattempt
				selenium.type("id=search-query", "url");
				Thread.sleep(2000);
				
				 quart_detailid   = "7866";
				  quart_testname   = "ManageModulesURLStatusEnabled";
				  quart_description= "verify URL status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					{ System.out.println("URL module is already enabled"); }
				
					else {
						
					// enable analytics
					selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					// enable analytics
					selenium.click("css=div.row-id-url span.dijitDropDownButton");
					Thread.sleep(3000);
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_9-button-action_label')]");  
					Thread.sleep(3000);
				}
				
				
				// users module
				selenium.type("id=search-query", "");
				Thread.sleep(2000);
				
				selenium.click("id=tagFilter-display-users");
				Thread.sleep(2000);
				
				
				 quart_detailid   = "9919";
				  quart_testname   = "ManageModulesUsertext";
				  quart_description= "verify User text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("User"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9920";
				  quart_testname   = "ManageModulesUsertext";
				  quart_description= "verify User text";
				if (selenium.isTextPresent("Provides user management facilities."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6129";
				  quart_testname   = "ManageModulesUserIcon";
				  quart_description= "verify User icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/user/resources/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9922";
				  quart_testname   = "ManageModulesUserPerforcetext";
				  quart_description= "verify User perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9921";
				  quart_testname   = "ManageModulesUserVersion";
				  quart_description= "verify User version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2359";
				  quart_testname   = "ManageModulesUsersupport";
				  quart_description= "verify User support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2358";
				  quart_testname   = "ManageModulesUserWWW";
				  quart_description= "verify User WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9925";
				  quart_testname   = "ManageModulesUserStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				// filter analytics module
				selenium.click("id=tagFilter-display-users");
				Thread.sleep(2000);
				
				
				 
			
				
				// PINTEREST MODULE -- COMMENTING OUT - NOT IN 12.2 RELEASE //
		     	
//				
//				manageMenu();
//				selenium.click(CMSConstants.MANAGE_MODULES);
//				Thread.sleep(2000);
//				
//				// Pinterest module
//				selenium.type("id=search-query", "pinterest");
//				Thread.sleep(2000);
//				
//				 quart_detailid   = "10045";
//				  quart_testname   = "ManageModulePinterestText";
//				  quart_description= "verify Pinterest text";
//				// verify delete user dialog
//				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
//				// check to see if user selected is checked and write to file
//				if (selenium.isTextPresent("Pinterest"))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
//				
//				
//				 quart_detailid   = "10046";
//				  quart_testname   = "ManageModulesPinterestText1";
//				  quart_description= "verify Pinterest text";
//				if (selenium.isTextPresent("Allows a user to create content with a Pinboard element."))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//
//				
//				
//				 quart_detailid   = "10044";
//				  quart_testname   = "ManageModulePinterestIcon";
//				  quart_description= "verify Pinterest icon";
//				if (selenium.isElementPresent(("//img[contains(@src, '/application/site/resources/images/module-disabled.png')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//
//				
//				
//				 quart_detailid   = "10048";
//				  quart_testname   = "ManageModulePinterestPerforcetext";
//				  quart_description= "verify Pinterest perforce software text";
//				if (selenium.isTextPresent("Perforce Software"))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				 
//				
//				
//				 quart_detailid   = "10047";
//				  quart_testname   = "ManageModulePinterestVersion";
//				  quart_description= "verify Pinterest version";
//				if (selenium.isTextPresent(versionString))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//
//				
//				 quart_detailid   = "10049";
//				  quart_testname   = "ManageModulePinterestUsersupport";
//				  quart_description= "verify Pinterest support link";
//				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//				quart_detailid   = "10050";
//				  quart_testname   = "ManageModulePinterestWWW";
//				  quart_description= "verify Pinterest WWW link";
//				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//
//				 quart_detailid   = "10052";
//				  quart_testname   = "ManageModulePinterestStatusDisabled";
//				  quart_description= "verify status disabled";
//				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//								
//				// enable pinterest module
//				selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td/div/div/div[2]/span/span/span","");
//				Thread.sleep(2000);
//				
//				 quart_detailid   = "10651";
//				  quart_testname   = "ManageModulePinterestEnableButton";
//				  quart_description= "verify Pinterest enable button";
//				if (selenium.isTextPresent(("to enable the Pinterest module?")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//			
//				quart_detailid   = "10054";
//				  quart_testname   = "ManageModulePinterestEnableText";
//				  quart_description= "verify Pinterest enable text";
//				if (selenium.isTextPresent(("to enable the Pinterest module?")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//				selenium.click(("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_10-button-action_label')]"));  
//		     	Thread.sleep(3000);
//				
//		    	quart_detailid   = "10053";
//				  quart_testname   = "ManageModulePinterestStatusEnable";
//				  quart_description= "verify Pinterest status enable";
//					if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//			// disable pinterest module
//			selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td/div/div/div[2]/span/span/span","");
//			Thread.sleep(2000);
//			
//			quart_detailid   = "10057";
//			  quart_testname   = "ManageModulePinterestDisableText";
//			  quart_description= "verify Pinterest disable text";
//			if (selenium.isTextPresent(("to disable the Pinterest module?")))
//				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//			
//			selenium.click(("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_0-button-action_label')]"));  
//	     	Thread.sleep(3000);
//					
//					
//	     	quart_detailid   = "10056";
//			  quart_testname   = "ManageModulePinterestDisableButton";
//			  quart_description= "verify Pinterest disable button";
//				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
//				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//					
//				
	
				// CONTENT GENERATION MODULE -- COMMENTING OUT - NOT IN 12.2 RELEASE //
				
//				manageMenu();
//				Thread.sleep(2000);
//				// verify content generation link not shown
//				
//				quart_detailid   = "0";
//				  quart_testname   = "ManageModuleGenerationConfigurationText";
//				  quart_description= "verify Generation config generate text";
//			 		if (!selenium.isTextPresent(("Content Generation")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//			 		
//			 		
//				selenium.click(CMSConstants.MANAGE_MODULES);
//				Thread.sleep(2000);
//				
//				// Content generation module
//				selenium.type("id=search-query", "generation");
//				Thread.sleep(2000);
//				
//				 quart_detailid   = "10190";
//				  quart_testname   = "ManageModuleGenerationText";
//				  quart_description= "verify Generation text";
//				// verify delete user dialog
//				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
//				// check to see if user selected is checked and write to file
//				if (selenium.isTextPresent("Generation"))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
//				
//				
//				 quart_detailid   = "10191";
//				  quart_testname   = "ManageModulesGenerationText1";
//				  quart_description= "verify Generation text";
//				if (selenium.isTextPresent("Provides configurable method for generating content."))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//
//				
//				
//				 quart_detailid   = "10189";
//				  quart_testname   = "ManageModuleGenerationIcon";
//				  quart_description= "verify Generation icon";
//				if (selenium.isElementPresent(("//img[contains(@src, '/application/site/resources/images/module-disabled.png')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//
//				
//				
//				 quart_detailid   = "10193";
//				  quart_testname   = "ManageModuleGenerationPerforcetext";
//				  quart_description= "verify Generation perforce software text";
//				if (selenium.isTextPresent("Perforce Software"))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				 
//				
//				
//				 quart_detailid   = "10192";
//				  quart_testname   = "ManageModuleGenerationVersion";
//				  quart_description= "verify Generation version";
//				if (selenium.isTextPresent(versionString))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//
//				
//				 quart_detailid   = "10194";
//				  quart_testname   = "ManageModuleGenerationUsersupport";
//				  quart_description= "verify Generation support link";
//				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//				quart_detailid   = "10195";
//				  quart_testname   = "ManageModuleGenerationWWW";
//				  quart_description= "verify Generation WWW link";
//				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//
//				 quart_detailid   = "10197";
//				  quart_testname   = "ManageModuleGenerationStatusDisabled";
//				  quart_description= "verify status disabled";
//				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				quart_detailid   = "10201";
//				  quart_testname   = "ManageModuleGenerationDisableButton";
//				  quart_description= "verify Generation disable button";
//					if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//					
//					
//								
//				// enable Generation module
//				selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td/div/div/div[2]/span/span/span","");
//				Thread.sleep(3000);
//				
//				 quart_detailid   = "10196";
//				  quart_testname   = "ManageModuleGenerationEnableButton";
//				  quart_description= "verify Generation enable button";
//				if (selenium.isTextPresent(("to enable the Generation module?")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				quart_detailid   = "10200";
//				  quart_testname   = "ManageModuleGenerationEnableButton1";
//				  quart_description= "verify Generation enable button";
//				if (selenium.isTextPresent(("to enable the Generation module?")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_10-button-action_label')]");  
//		     	Thread.sleep(3000);
//		     	
//		     	selenium.type("id=search-query", "generation");
//				Thread.sleep(2000);
//				 
//		    	quart_detailid   = "10196";
//				  quart_testname   = "ManageModuleGenerationStatusEnable";
//				  quart_description= "verify Generation status enable";
//					if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
//					
//				quart_detailid   = "10199";
//				  quart_testname   = "ManageModuleGenerationStatusEnable1";
//				  quart_description= "verify Generation status enable";
//					if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//										
//									
//					
//			// disable Generation module
//			selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td/div/div/div[2]/span/span/span","");
//			Thread.sleep(3000);
//			
//			
//			
//			quart_detailid   = "10201";
//			  quart_testname   = "ManageModuleGenerationDisableText1";
//			  quart_description= "verify Generation disable text";
//			if (selenium.isTextPresent(("to disable the Generation module?")))
//				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//			
//			
//			quart_detailid   = "10202";
//			  quart_testname   = "ManageModuleGenerationDisableText";
//			  quart_description= "verify Generation disable text";
//			if (selenium.isTextPresent(("to disable the Generation module?")))
//				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//			
//				
//			
//		     	
//		     	manageMenu();
//		     	Thread.sleep(2000);
//		    	selenium.click(CMSConstants.MANAGE_CONTENTGENERATION);
//		    	Thread.sleep(2000);
//		    	quart_detailid   = "10207";
//				  quart_testname   = "ManageModuleGenerationConfigurationText";
//				  quart_description= "verify Generation config text";
//					if (selenium.isTextPresent(("Configure Content Generation")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//				
//					quart_detailid   = "10208";
//					  quart_testname   = "ManageModuleGenerationConfigurationText1";
//					  quart_description= "verify Generation config text";
//						if (selenium.isTextPresent(("Content Count")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//					
//					quart_detailid   = "10209";
//					  quart_testname   = "ManageModuleGenerationConfigurationForm";
//					  quart_description= "verify Generation config form";
//				 		if (selenium.isElementPresent(("//input[contains(@name, 'count') and contains(@id, 'count')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//					
//				 		
//				 		// enter invalid text and confirm error
//				 		selenium.type("id=count", "abcde");
//				 	  // click generate
//						selenium.click("id=generate_label");
//						selenium.click("name=generate");
//						Thread.sleep(2000);
//						
//						quart_detailid   = "10210";
//						quart_testname   = "ManageModuleGenerationConfigurationFormError";
//						quart_description= "verify Generation config form error";
//					 	if (selenium.isTextPresent(("Error")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//					 		
//					   selenium.click("id=p4cms_ui_ErrorDialog_0-button-ok_label");
//					   selenium.click("id=p4cms_ui_ProgressBarDialog_0-button-close_label");
//					   Thread.sleep(1000);
//				 		
//					   
//					   
//					   // enter valid number
//						selenium.type("id=count", "10");
//						
//						// click generate
//						selenium.click("id=generate_label");
//						selenium.click("name=generate");
//						Thread.sleep(4000);
//						
//					quart_detailid   = "10211";
//					  quart_testname   = "ManageModuleGenerationConfigurationGenerateButton";
//					  quart_description= "verify Generation config generate button";
//				 		if (selenium.isTextPresent("generating content entries"))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				 		
//				 		quart_detailid   = "10216";
//						  quart_testname   = "ManageModuleGenerationConfigurationGenerateButtonText";
//						  quart_description= "verify Generation config generate button text";
//					 		if (selenium.isTextPresent("generating content entries"))
//							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//					
//			 		
//			 		quart_detailid   = "10212";
//					  quart_testname   = "ManageModuleGenerationConfigurationText";
//					  quart_description= "verify Generation config generate text";
//				 		if (selenium.isTextPresent(("Content Generation")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				 		
//				 		
//				 		
//			 		quart_detailid   = "10215";
//					  quart_testname   = "ManageModuleGenerationConfigurationText1";
//					  quart_description= "verify Generation config generate text1";
//				 		if (selenium.isTextPresent(("Generating content - for a large amount of content, this may take a while.")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				 		
//			 		quart_detailid   = "10218";
//					  quart_testname   = "ManageModuleGenerationConfigurationProgressBar";
//					  quart_description= "verify Generation config generate progress bar";
//						if (selenium.isElementPresent(("//div[contains(@class, 'dijitProgressBarFull')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//								 
//					
//					// click on view content
//					//selenium.click("//div[@id='buttons-element']/fieldset/span/input");
//					quart_detailid   = "10220";
//					  quart_testname   = "ManageModuleGenerationConfigurationViewContentButton";
//					  quart_description= "verify Generation config generate view content button";
//						if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ProgressBarDialog_0-button-view')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//						
//			
//						
//					// click close	
//					//selenium.click("id=p4cms_ui_ProgressBarDialog_0-button-close_label");
//						
//					quart_detailid   = "10219";
//					  quart_testname   = "ManageModuleGenerationConfigurationCloseButton";
//					  quart_description= "verify Generation config generate close button";
//						if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ProgressBarDialog_0-button-close_label')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//						
//						
//						
//						quart_detailid   = "10214";
//						quart_testname   = "ManageModuleGenerationConfigurationDoneText";
//						quart_description= "verify generate content done text";
//						// get tooltip attribute
//	
//			
//				 		if (selenium.isTextPresent(("Done. Content generation completed.")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//						
//						
//						quart_detailid   = "10217";
//						  quart_testname   = "ManageModuleGenerationConfigurationText1";
//						  quart_description= "verify Generation config generate text1";
//					 		if (selenium.isTextPresent(("Generating content - for a large amount of content, this may take a while.")))
//							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//						
//						
//		
//						
////						// click on x
////						selenium.click("css=span.dijitDialogCloseIcon.dijitDialogCloseIconHover");
////						
////						quart_detailid   = "10213";
////						quart_testname   = "ManageModuleGenerationConfiguration_click_x";
////						quart_description= "verify generate content click x";	
////			
////						if (selenium.isTextPresent(("Content Count")))
////						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
////						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
////							
////						Thread.sleep(2000); 
//									
//						
//				
////				// verify that the content generation link is displayed
////				// go to manage modules
////				manageMenu();
////				 quart_detailid   = "10199";
////				  quart_testname   = "ContentGenerationLinjk";
////				  quart_description= "verify content generation link";
////				// Write to file for checking manage menu header text
////				if (selenium.isTextPresent("Content Generation"))
////					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
////			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				
//				
				

				// go to manage modules
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				// filter widgets module
				selenium.type("id=search-query", "widget");
				selenium.click("id=typeFilter-display-core");
				selenium.click("id=statusFilter-display-enabled");
				Thread.sleep(2000);
				
				
				 quart_detailid   = "9947";
				  quart_testname   = "ManageModulesWidgetstext";
				  quart_description= "verify Widget text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Widget"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9948";
				  quart_testname   = "ManageModulesWidgetstext1";
				  quart_description= "verify Widget text";
				if (selenium.isTextPresent("Provides widget facilities."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6130";
				  quart_testname   = "ManageModulesWidgetsIcon";
				  quart_description= "verify Widget icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/widget/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9950";
				  quart_testname   = "ManageModulesWidgetsPerforcetext";
				  quart_description= "verify Widget perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9949";
				  quart_testname   = "ManageModulesWidgetsVersion";
				  quart_description= "verify Widget version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "2361";
				  quart_testname   = "ManageModulesWidgetsUsersupport";
				  quart_description= "verify Widget support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "2360";
				  quart_testname   = "ManageModulesWidgetsWWW";
				  quart_description= "verify Widget WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9951";
				  quart_testname   = "ManageModulesWidgetsStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				quart_detailid   = "2289";
				  quart_testname   = "ManageModulesEntriesText";
				  quart_description= "verify entries text";
				if (selenium.isTextPresent(("entry")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
				
				
				
				// filter workflow module
				selenium.type("id=search-query", "workflow");
				Thread.sleep(2000);
				
				 quart_detailid   = "9896";
				  quart_testname   = "ManageModulesWorkflowtext";
				  quart_description= "verify workflow text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("Workflow"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "9897";
				  quart_testname   = "ManageModulesWorkflowtext1";
				  quart_description= "verify Widget text";
				if (selenium.isTextPresent("Provides workflow facilities."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "6131";
				  quart_testname   = "ManageModulesWorkflowIcon";
				  quart_description= "verify workflow icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/application/workflow/resources/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "9899";
				  quart_testname   = "ManageModulesWorkflowPerforcetext";
				  quart_description= "verify workflow perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "9898";
				  quart_testname   = "ManageModulesWorkflowVersion";
				  quart_description= "verify workflow version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "6114";
				  quart_testname   = "ManageModulesWorkflowUsersupport";
				  quart_description= "verify workflow support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "6113";
				  quart_testname   = "ManageModulesWorkflowWWW";
				  quart_description= "verify workflow WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				

				 quart_detailid   = "9900";
				  quart_testname   = "ManageModulesWorkflowStatusEnabled";
				  quart_description= "verify status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
				
				
				
				
				// WordPress module
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
							
				selenium.type("id=search-query", "wordpress");
				Thread.sleep(3000);
				
				 quart_detailid   = "10921";
				  quart_testname   = "ManageModulesWordPresstext";
				  quart_description= "verify WordPress text";
				// verify delete user dialog
				//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
				// check to see if user selected is checked and write to file
				if (selenium.isTextPresent("WordPress Import"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
				
				
				 quart_detailid   = "10922";
				  quart_testname   = "ManageModulesWordPresstext1";
				  quart_description= "verify Wordpress text";
				if (selenium.isTextPresent("Imports a provided WordPress xml export document into Chronicle."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "10920";
				  quart_testname   = "ManageModulesWordPressIcon";
				  quart_description= "verify Wordpress icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/wpimport/resources/images/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				 quart_detailid   = "10924";
				  quart_testname   = "ManageModulesWordPressPerforcetext";
				  quart_description= "verify Wordpress perforce software text";
				if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
				
				
				 quart_detailid   = "10923";
				  quart_testname   = "ManageModulesWordPressVersion";
				  quart_description= "verify Wordpress version";
				if (selenium.isTextPresent(versionString))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				 quart_detailid   = "10925";
				  quart_testname   = "ManageModulesWordPressUsersupport";
				  quart_description= "verify Wordpress support link";
				if (selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "10926";
				  quart_testname   = "ManageModulesWordPressWWW";
				  quart_description= "verify Wordpress WWW link";
				if (selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
					{ // disable wordpress
					manageMenu();
					selenium.click(CMSConstants.MANAGE_MODULES);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					
					selenium.click("css=div.row-id-wpimport span.dijitDropDownButton");
					Thread.sleep(3000);
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_10-button-action_label')]");  
					Thread.sleep(3000); 
					}
						else { // do nothing...	
						System.out.println("Do nothing... WordPress is enabled");
					}
						
				selenium.type("id=search-query", "wordpress");
				Thread.sleep(3000); 

				 quart_detailid   = "10934";
				  quart_testname   = "ManageModulesWordPressStatusEnabled";
				  quart_description= "verify status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				// enable url module
				selenium.clickAt("css=div.row-id-wpimport span.dijitDropDownButton","");
				Thread.sleep(4000);		
				
								
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_10-button-action_label')]");  
		     	Thread.sleep(5000);
		     	
		     	selenium.type("id=search-query", "wordpress");
				Thread.sleep(3000);
				
		     	 quart_detailid   = "10927";
				  quart_testname   = "ManageModulesWordPressStatusEnabled";
				  quart_description= "verify WordPress status enabled";
				if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				 quart_detailid   = "10930";
				  quart_testname   = "ManageModulesWordPressStatusEnabled";
				  quart_description= "verify WordPress status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				 quart_detailid   = "10931";
				  quart_testname   = "ManageModulesWordPressStatusEnabled";
				  quart_description= "verify WordPress status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				 quart_detailid   = "10929";
				  quart_testname   = "ManageModulesWordPressStatusEnabled";
				  quart_description= "verify WordPress status enabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status enabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
				
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
				//enable url module
				selenium.click("css=div.row-id-wpimport span.dijitDropDownButton");
				Thread.sleep(4000);

				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_10-button-action_label')]");  
		     	Thread.sleep(5000);
		     	
		     	 quart_detailid   = "10933";
				  quart_testname   = "ManageModulesWordPressStatusDisabled";
				  quart_description= "verify WordPress status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				 quart_detailid   = "10932";
				  quart_testname   = "ManageModulesWordPressStatusDisabled";
				  quart_description= "verify WordPress status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			  	
				// confirm that Wordpress has been re-enbaled, otherwise reattempt
				selenium.type("id=search-query", "wordpress");
				Thread.sleep(2000);
				
				 quart_detailid   = "10928";
				  quart_testname   = "ManageModulesWordPressStatusDisabled";
				  quart_description= "verify WordPress status disabled";
				if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
				
		// back to WebSite
		backToHome();
	}
}

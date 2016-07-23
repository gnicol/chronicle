	package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;

//This code clicks on manage --> modules and verifies the analytics title

public class WordPressImport extends shared.BaseTest {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="ManageModulesVerify";

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
	      WordPressImport();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");  

	}
	
	public void WordPressImport() throws Exception {
				
				// WordPress module	
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				// check to see if analytics module is enabled
				selenium.type("id=search-query", "wordpress");
				Thread.sleep(3000);
				
				// enabled WordPress module if it's disabled 
				if (selenium.isElementPresent("//span[contains(@class, 'status disabled')]"))
					
				  { // enable wordpress
					manageMenu();
					selenium.click(CMSConstants.MANAGE_MODULES);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					
					selenium.click("css=div.row-id-wpimport span.dijitDropDownButton");
					Thread.sleep(3000);
					selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_10-button-action_label')]");  
					Thread.sleep(3000);
					
				  }
						else { // do nothing...
						System.out.println("WordPress is enabled... do nothing");
					}
				
				
				// check configuration of WordPress & attach file
				manageMenu();
				selenium.click(CMSConstants.MANAGE_WORDPRESS);
				selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);		
						
			 
				String quart_detailid   = "10935";
				String  quart_testname   = "ManageModulesWordPressText1";
				String  quart_description= "verify WordPress text";
				if (selenium.isTextPresent(("WordPress Import")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				 quart_detailid   = "10936";
				  quart_testname   = "ManageModulesWordPressText2";
				  quart_description= "verify WordPress text";
				if (selenium.isTextPresent(("WordPress XML File")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			 
				 quart_detailid   = "10940";
				  quart_testname   = "ManageModulesWordPressText3";
				  quart_description= "verify WordPress text";
				if (selenium.isTextPresent(("Select the exported WordPress XML file to import into Chronicle.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
					
				 quart_detailid   = "10940";
				  quart_testname   = "ManageModulesWordPressText3";
				  quart_description= "verify WordPress text";
				if (selenium.isTextPresent(("Select the exported WordPress XML file to import into Chronicle.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
					
				 quart_detailid   = "10941";
				  quart_testname   = "ManageModulesWordpressImportButton";
				  quart_description= "verify WordPress import button";
				if (selenium.isElementPresent(("//span[contains(@id, 'import')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				 quart_detailid   = "10939";
				  quart_testname   = "ManageModulesWordPressClearButton";
				  quart_description= "verify WordPress clear button";
				if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_0')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "10938";
				  quart_testname   = "ManageModulesWordPressBrowseButton";
				  quart_description= "verify WordPress browse button";
				if (selenium.isElementPresent(("//input[contains(@id, 'importfile')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "10937";
				  quart_testname   = "ManageModulesWordPressBrowseButton";
				  quart_description= "verify WordPress browse button";
				if (selenium.isElementPresent(("//input[contains(@id, 'importfile')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
				// import file 
				// check the Operating system to use proper file uri
				
				String operatingSystem = System.getProperty("os.name");
				 System.out.println("Operating System = " + operatingSystem);
				
				 if (operatingSystem.contains("Windows")) // check for Windows OS
				     {  
					   selenium.attachFile("id=importfile", "file:///nextprinciples.xml");
				       Thread.sleep(2000);  
				     } 
				 
				   // check for Unix OS
				   else { selenium.attachFile("id=importfile", "file:///Users/sorchanian/Downloads/nextprinciples.xml"); 
				        //selenium.attachFile("id=importfile", "C:\\Users\\Perforce\\Downloads\\nextprinciples.xml");
				     }
			   
				 
				  //click on import button 
				selenium.click("id=import_label");
				Thread.sleep(50000);
				
				
				// verify import dialog
				quart_detailid   = "10942";
				  quart_testname   = "ManageModulesWordPressImportDialogText";
				  quart_description= "verify WordPress Import dialog";
				if (selenium.isTextPresent("Import Content"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "10943";
				  quart_testname   = "ManageModulesWordPressImportDialogTEXT2";
				  quart_description= "verify WordPress Import dialog2";
				if (selenium.isTextPresent("Importing content. Item"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				

				 quart_detailid   = "10945";
				  quart_testname   = "ManageModulesWordPressImportDialogCloseButton";
				  quart_description= "verify WordPress import dialog close button";
				if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ProgressBarDialog_0-button-close')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
				// verify 'x' tooltip
				quart_detailid   = "10947";
				quart_testname   = "ManageModulesWordPressImportDialog_x_Tooltip";
				quart_description= "verify wordpress import dialog 'x' tooltip";
				
				// get tooltip attribute
				String tooltip = selenium.getAttribute("//div[6]/div/span[2]/@title");

				boolean tooltipTrue = tooltip.equals("Cancel");
			
				if (tooltipTrue) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
				quart_detailid  = "10944";
				quart_testname = "ManageModulesWordPressImportProgressLabel";
				quart_description = "verify wordpress import dialog progress bar";
				if (selenium.isElementPresent(("//div[contains(@class, 'dijitProgressBarLabel')]")))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
				quart_detailid  = "10946";
				quart_testname = "ManageModulesWordPressImportDialogCloseIcon";
				quart_description = "verify wordpress import dialog close icon";
				if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					

				
				   // check to see if final import dialog appears
				   if (selenium.isElementPresent(("//span[contains(@class, 'password')]"))) 
					   
				  {
						quart_detailid  = "10951";
						quart_testname = "ManageModulesWordPressImportFinalDialog";
						quart_description = "verify wordpress import final dialog";
						if (selenium.isTextPresent("Import Content"))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 						
						quart_detailid  = "10948";
						quart_testname = "ManageModulesWordPressImportFinalDialogText";
						quart_description = "verify wordpress import dialog progress bar";
						if (selenium.isTextPresent("Import complete. Users have been imported and their passwords reset. Please inform the users of their new passwords."))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						quart_detailid  = "10952";
						quart_testname = "ManageModulesWordPressImportFinalDialogAdminText";
						quart_description = "verify wordpress import dialog Admin text";
						if (selenium.isTextPresent("Admin"))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						quart_detailid  = "10953";
						quart_testname = "ManageModulesWordPressImportFinalDialogPassword";
						quart_description = "verify wordpress import final dialog password";
						if (selenium.isElementPresent(("//span[contains(@class, 'password')]")))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
						quart_detailid  = "10954";
						quart_testname = "ManageModulesWordPressImportFinalDialogCloseButton";
						quart_description = "verify wordpress import final dialog close button";
						if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ProgressBarDialog_0-button-close')]")))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
						quart_detailid  = "10954";
						quart_testname = "ManageModulesWordPressImportFinalDialogCloseButton";
						quart_description = "verify wordpress import final dialog close button";
						if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ProgressBarDialog_0-button-close')]")))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
						quart_detailid  = "10955";
						quart_testname = "ManageModulesWordPressImportFinalDialogCloseIcon";
						quart_description = "verify wordpress import final dialog close icon";
						if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
						// verify 'x' tooltip
						quart_detailid   = "10956";
						quart_testname   = "ManageModulesWordPressImportDialog_x_Tooltip";
						quart_description= "verify search rebuild 'x' tooltip";
						
						// get tooltip attribute
						String tooltip1 = selenium.getAttribute("//div[6]/div/span[2]/@title");
		
						boolean tooltip1True = tooltip1.equals("Cancel");
					
						if (tooltip1True) 
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
						quart_detailid  = "10955";
						quart_testname = "ManageModulesWordPressFinalDialogCloseIcon";
						quart_description = "verify wordpress final dialog close icon";
						if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
						else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
				}
				   
	   
		// back to WebSite 
		backToHome();
	}
}

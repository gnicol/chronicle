package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


public class Widgets extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "Widgets";

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
		          
		// verify widgets
		Widgets();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");
		

	}
	
	public void Widgets() throws Exception {
		
		selenium.open(baseurl);
		waitForElements("link=Home");
		
		// verify widgets gear icons
		// click on a widget
		selenium.click("css=span.menu-icon.manage-toolbar-widgets");
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitStretch dijitButtonContents')]")));  
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitIcon plusIcon')]")));  

		// click on a widget
		selenium.click("//span[@id='dijit_form_Button_1']/span");
		selenium.click("xpath=(//input[@value=''])[2]");
		waitForText("Add Widget");
		
		 String quart_detailid   = "9717";
		 String quart_testname   = "WidgetsDialog";
		 String quart_description= "verify widgets elements";
		
		// Write to file for checking manage content type page
		 if (selenium.isTextPresent("Add Widget"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
			 // Configure Content List widget
			 	selenium.open(baseurl);
				waitForElements("link=Home");
				
				// click on widgets
				selenium.click("css=span.menu-icon.manage-toolbar-widgets");
				
				selenium.click("//span[@id='dijit_form_Button_1']/span");
				selenium.click("xpath=(//input[@value=''])[2]");
				Thread.sleep(2000);
				
				 selenium.click("link=Content List");
				 Thread.sleep(2000);
			 
				 quart_detailid   = "8732";
				  quart_testname   = "WidgetsContentListTitleText";
				  quart_description= "verify widgets content list title text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Title"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				 quart_detailid   = "8742";
				  quart_testname   = "WidgetsContentListShowTitleText";
				  quart_description= "verify widgets content list show title text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Show Title"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				 
				 quart_detailid   = "8741";
				  quart_testname   = "WidgetsContentListMaxItemsText";
				  quart_description= "verify widgets content list max items text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Maximum Items"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				 
				 quart_detailid   = "8740";
				  quart_testname   = "WidgetsContentListMaxItemsText1";
				  quart_description= "verify widgets content list max items text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Enter the maximum number of content entries to display."))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
						
				 // check image widget

				 selenium.open(baseurl);
				 waitForElements("link=Home");

				 // click on widgets
				 selenium.click("css=span.menu-icon.manage-toolbar-widgets");

				 selenium.click("//span[@id='dijit_form_Button_1']/span");
				 selenium.click("xpath=(//input[@value=''])[2]");
				 Thread.sleep(2000);

				 selenium.click("link=Image Widget");
				 Thread.sleep(2000);
						
					
				 quart_detailid   = "7390";
				  quart_testname   = "WidgetsConfigureImageWidget";
				  quart_description= "verify image widget text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Configure Image Widget"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 		 
						 
						 
				// check Menu widget

				 selenium.open(baseurl);
				 waitForElements("link=Home");

				 // click on widgets
				 selenium.click("css=span.menu-icon.manage-toolbar-widgets");

				 selenium.click("//span[@id='dijit_form_Button_1']/span");
				 selenium.click("xpath=(//input[@value=''])[2]");
				 Thread.sleep(2000);

				 selenium.click("link=Menu Widget");
				 Thread.sleep(2000);
						
					
				 quart_detailid   = "7428";
				  quart_testname   = "WidgetsConfigureMenuWidget";
				  quart_description= "verify menu widget text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Configure Menu Widget"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 		 		 
						 
						 
				// check Search widget

				 selenium.open(baseurl);
				 waitForElements("link=Home");

				 // click on widgets
				 selenium.click("css=span.menu-icon.manage-toolbar-widgets");

				 selenium.click("//span[@id='dijit_form_Button_1']/span");
				 selenium.click("xpath=(//input[@value=''])[2]");
				 Thread.sleep(2000);

				 selenium.click("link=Search Widget");
				 Thread.sleep(2000);
						
					
				 quart_detailid   = "7418";
				  quart_testname   = "WidgetsConfigureSearchWidget";
				  quart_description= "verify search widget text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Configure Search Widget"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 		 		 
				
				 
			      // check Text widget
				  selenium.open(baseurl);
				  waitForElements("link=Home");
				  
				  //click on widgets
				  selenium.click("css=span.menu-icon.manage-toolbar-widgets");
				  selenium.click("//span[@id='dijit_form_Button_1']/span");
				  selenium.click("xpath=(//input[@value=''])[2]");
				  Thread.sleep(2000);
				  
				  selenium.click("link=Text Widget");
				  Thread.sleep(2000);
				  
				  
				  quart_detailid   = "9197";
				  quart_testname   = "WidgetsConfigureTextWidget";
				  quart_description= "verify text widget text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Configure Text Widget"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 		
				  
				  
				  
				
				  // check IFrame widget
				  selenium.open(baseurl);
				  waitForElements("link=Home");
				  
				  // click on widgets
				  selenium.click("css=span.menu-icon.manage-toolbar-widgets");
				  selenium.click("//span[@id='dijit_form_Button_1']/span");
				  selenium.click("xpath=(//input[@value=''])[2]");
				  Thread.sleep(2000);
				  
				  selenium.click("link=IFrame Widget");
				  Thread.sleep(2000);
				  
				
				  quart_detailid   = "9225";
				  quart_testname   = "WidgetsConfigureIFrameWidget";
				  quart_description= "verify IFrame widget text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Configure IFrame Widget"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 		
	}
}


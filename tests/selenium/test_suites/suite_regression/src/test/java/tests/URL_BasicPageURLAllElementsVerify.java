
package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class URL_BasicPageURLAllElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "URL_BasicPageURLAllElementsVerify";

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
		URL_BasicPageURLAllElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void URL_BasicPageURLAllElementsVerify() throws InterruptedException, Exception {
		
		// Verify title & close icon & content type
		verifyContentElements();
		
		// Basic page
		// click on Pages in left tab
		selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
		selenium.click("//a[@href='/add/type/basic-page']");
		Thread.sleep(2000);		
		
		// click on URL element
 		selenium.clickAt("id=add-content-toolbar-button-URL","");
		Thread.sleep(2000);
		
		 	String quart_detailid   = "8171";
			String quart_testname   = "PlaceModeCustomRadioButton";
			String quart_description= "verify url custom radio button";
				 
		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'url-auto-false') and contains(@value, 'false')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


		
		
		
		// verify custom radio button
		// click on URL element
 
		// click url custom radio bbutton
		selenium.click("id=url-auto-false");
		Thread.sleep(2000);
		
		// verify custom url is inputtable
		assertTrue(!selenium.isElementPresent("//input[@readonly='']"));

		 quart_detailid   = "8174";
		 quart_testname   = "PlaceModeCustomRadioCheck";
		 quart_description= "verify url custom radio button";
		
			if (!selenium.isElementPresent("//input[@readonly='']"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
	
			// verify input field disabled
			// click on URL element
			selenium.click("id=url-auto-true");
			Thread.sleep(2000);
	 		quart_detailid   = "8173";
			 quart_testname   = "PlaceModeInputFieldDisabled";
			 quart_description= "verify input field disabled";
				
			 if (selenium.isElementPresent("//input[@type='text' and contains(@id, 'url-path') and contains(@readonly, '')]"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			
			
			 // url path verify
			// click on URL element
	 		
	 		quart_detailid   = "8170";
			 quart_testname   = "PlaceModeURLPath";
			 quart_description= "verify url path";
					
	 		if (selenium.isTextPresent(baseurl))
	 			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		
		
	 	// radio button verify
	 	// click on URL element
	 		
	 		quart_detailid   = "8169";
			 quart_testname   = "PlaceModeRadioButton";
			 quart_description= "verify radio button";
							
	 		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'url-auto-true') and contains(@value, 'true') and contains(@checked, 'checked')]"))
	 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
	 		
	 	// url title verify
	 	// click on URL element
			
			 quart_detailid   = "8172";
			 quart_testname   = "PlaceModeURLTitle";
			 quart_description= "verify url title";
					
			  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'url-auto-true') and contains(@value, 'true') and contains(@checked, 'checked')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

	 		
		
		// URL Form mode verify

		// Verify menu elements
		backToHome();
			
		// Verify title & close icon & content type
		verifyContentElements();
		Thread.sleep(1000);
		
		// click on Pages in left tab
		//selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
		selenium.click("//a[@href='/add/type/basic-page']");
		Thread.sleep(2000);		
		// confirm url basic page for URL elements 
		// click form mode and verify all elements
				selenium.click("id=add-content-toolbar-button-form_label");
				selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
				Thread.sleep(3000);
				
		  quart_detailid   = "8178";
		  quart_testname   = "FormModeCustomRadioButton";
		  quart_description= "verify url custom radio button";
		
		 // Write to file for checking manage content type page
			if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'url-auto-false') and contains(@value, 'false')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				

			
 		// click url custom radio bbutton
			selenium.click("id=url-auto-false");
			Thread.sleep(2000);
			
			// verify custom url is inputtable
			assertTrue(!selenium.isElementPresent("//input[@readonly='']"));
	
			quart_detailid   = "8179";
			quart_testname   = "FormModeCustomRadioCheck";
			 quart_description= "verify url custom radio button";
			
			
			  if (!selenium.isElementPresent("//input[@readonly='']"))
			  	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							  	
			  	
			  	
			// verify input field disabled
			selenium.click("id=url-auto-true");
			Thread.sleep(2000);
				
			 quart_detailid   = "8177";
			 quart_testname   = "FormModeInputFieldDisabled";
			 quart_description= "verify url input field disabled";
						
			 if (selenium.isElementPresent("//input[@type='text' and contains(@id, 'url-path') and contains(@readonly, '')]"))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			  	
		  
			  	
			// url path verify
			 quart_detailid   = "8175";
			  quart_testname   = "FormModeURLPathVerify";
			 quart_description= "verify url path";
							
			 if (selenium.isTextPresent(baseurl))
			  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			  	
				  
				  
			  // verify radio button
	
				quart_detailid   = "8176";
				  quart_testname   = "FormModeURLTitleElement";
				  quart_description= "verify use title for url";
										
				  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'url-auto-true') and contains(@value, 'true') and contains(@checked, 'checked')]"))
					  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
			
				
			// url title verify
			quart_detailid   = "8180";
			  quart_testname   = "FormModeURLTitle";
			  quart_description= "verify url title";
									
			  if (selenium.isTextPresent("URL"))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }  	
		
	}
}

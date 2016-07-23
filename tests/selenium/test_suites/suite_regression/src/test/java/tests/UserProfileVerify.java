package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class UserProfileVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "UserProfileVerify";
	private static String testType;

	@BeforeClass
	@Parameters({ "baseurl", "redirecturl", "usergroup" , "testType"})
	public void storeBaseURL(String baseurl, String redirecturl, String usergroup, String testType) {
		this.baseurl = baseurl;
		this.redirecturl = redirecturl;
		this.usergroup = usergroup;
		this.testType = testType;
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
		UserProfileVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void UserProfileVerify() throws Exception {
		 
		 // click on user profile drop down
		selenium.click("css=#p4cms_ui_toolbar_DropDownMenuButton_1 > span.menu-handle.type-heading");
		
		if (testType.equalsIgnoreCase("smoke")) 
			selenium.click("id=dijit_MenuItem_4_text"); 
		else
		  { selenium.click("id=dijit_MenuItem_9_text"); }
		 
		Thread.sleep(2000);
		assertTrue(selenium.isTextPresent("Edit User"));
				 
		// verify pull from content
		  String quart_detailid   = "9718"; 
		  String quart_testname   = "UserProfile";
		  String quart_description= "verify user profile dropdown";
		  
		  //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
		  if (selenium.isTextPresent("Edit User"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		  	
		  
		  selenium.open(baseurl);
	}
}

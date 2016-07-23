package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;

// This code clicks on manage --> add site page and verifies the title

public class ManageAddSitePageVerify extends BaseTest{
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageAddSitePageVerify";

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
		manageAddSitePageVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");  

	}
	
	public void manageAddSitePageVerify() throws Exception {
		 
		// Click on Manage --> Manage content types
		manageMenu();
 		selenium.click("//a[@href='/setup/start/1']");

		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		//writeFile1("\nskipped: 1044", "", "");
		
		// Write to file for checking manage content type page
		
		 String quart_detailid   = "6446";
		 String quart_testname   = "PageVerify";
		 String quart_description= "verify manage add site page";
		 
			if (selenium.isTextPresent("Setup: Requirements"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		//Back to Website
		selenium.open(baseurl);
	}
}


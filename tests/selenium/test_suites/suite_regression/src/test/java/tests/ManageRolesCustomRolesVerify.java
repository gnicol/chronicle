package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> roles and verifies the roles title

public class ManageRolesCustomRolesVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageRolesCustomRolesVerify";

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
		ManageRolesCustomRolesVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
	}
	
	public void ManageRolesCustomRolesVerify() throws Exception {
		 
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_ROLES_PAGE_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// click on system checkbox
		selenium.click("id=type-types-custom");
		Thread.sleep(1000);
		
		assertTrue(selenium.isTextPresent("author"));
		assertTrue(selenium.isTextPresent("editor"));
		
		
		//writeFile1("\nskipped: 716", "", "");

		// Write to file for checking manage content type page
		String quart_detailid   = "716";
		 String quart_testname   = "CustomRolesVerify";
		 String quart_description= "verify manage custom roles author text";
		
			if (selenium.isTextPresent("author"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
			quart_detailid   = "716";
			  quart_testname   = "CustomRolesVerify";
			  quart_description= "verify manage custom roles editor text";
		
		if (selenium.isTextPresent("editor"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		//Back to Website
		backToHome();
	}
}


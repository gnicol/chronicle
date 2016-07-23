package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> pages and verifies the title

public class ManageMenusPageVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageMenusPageVerify";
	
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
		manageMenusPageVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		selenium.waitForPageToLoad("30000");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void manageMenusPageVerify() throws Exception {
		 
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_MENU_PAGES_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		//writeFile1("\nskipped: 1044", "", "");

		// Write to file for checking manage content type page
		
		String quart_detailid   = "6143";
		 String quart_testname   = "PageVerify";
		 String quart_description= "verify manage menus text";
		
			if (selenium.isTextPresent("Manage Menus"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		//Back to Website
		backToHome();
	}
}


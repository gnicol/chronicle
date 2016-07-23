package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the page title

public class ManageUsersPageVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageUsersPageVerify";
	
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
		manageUsersPageVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void manageUsersPageVerify() throws Exception {
		 
		// Click on Manage --> Manage users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_USERS_PAGE_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		//writeFile1("\nskipped: 1044", "", "");

		// Write to file for checking manage content type page
		
		
		String quart_detailid   = "6686";
		 String  quart_testname   = "PageVerify";
		 String  quart_description= "verify users page title";
			if (selenium.isTextPresent("Manage Users"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		//Back to Website
		backToHome();
	}
}


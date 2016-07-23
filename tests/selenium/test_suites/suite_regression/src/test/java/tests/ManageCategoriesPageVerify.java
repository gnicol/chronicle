package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> categories page and verifies the title

public class ManageCategoriesPageVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;

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
		manageCategoriesPageVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
	
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void manageCategoriesPageVerify() throws Exception {
		 

		// Click on Manage --> Manage content types
		manageMenu();
		
		selenium.click(CMSConstants.MANAGE_CATEGORIES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		//writeFile1("\nskipped: 1044", "", "");

		// Write to file for checking manage content type page
		if (selenium.isTextPresent("Manage Categories"))
			writeFile("6141", "pass", "", "ManageCategoriesPageVerify.java","Check Manage - categories page title"); 
	        else  { writeFile("6141", "fail", "", "ManageCategoriesPageVerify.java", "Check Manage - categories page title"); }
		
		//Back to Website
		backToHome();
	}
}


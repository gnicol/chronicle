package tests;

import java.util.Random;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code creates a new user with a random generator for the username

public class CreateNewUserVerifyGrowl extends shared.BaseTest  {
	
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
	public void validate(String username, String password) throws Exception {

		// Login to Chronicle with new user
		newUser();

		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-logout user-logout type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Logout"));  
		
		selenium.click("link=Logout");
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  
		
	}

}
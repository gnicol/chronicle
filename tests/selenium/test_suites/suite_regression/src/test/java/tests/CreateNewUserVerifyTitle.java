package tests;

import java.util.Random;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code logs into Chronicle, clicks on user --> add user and verifies the "add user" text

public class CreateNewUserVerifyTitle extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "CreateNewUserVerifyTitle";

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

		// Login to Chronicle
      	  chronicleLogin(username, password);
	      waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		// Login to Chronicle with new user
		createNewUserVerifyTitle();
		
		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-logout user-logout type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Logout"));  
		
		selenium.click("link=Logout");
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  
	}


	public void createNewUserVerifyTitle() throws Exception {
		
		manageMenu();
		selenium.click(CMSConstants.MANAGE_USERS);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		// click to create a new users
		selenium.click("//span[@id='dijit_form_Button_0']/span");
		selenium.click("//input[@value='Add User']");
		Thread.sleep(1000);

		String quart_detailid = "1414";
		String quart_testname   = "CreateNewUserVerifyTitle";
		String quart_description= "Create User and Verify title"; 		
		//writeFile1("\nskipped: 1043", "", "");
		// check to see if user selected is checked and write to file
		if (selenium.isTextPresent("Add User"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid ,"fail", quart_scriptname,quart_testname, quart_description); }
		
		// Back to WebSite
		backToHome();
	}
}


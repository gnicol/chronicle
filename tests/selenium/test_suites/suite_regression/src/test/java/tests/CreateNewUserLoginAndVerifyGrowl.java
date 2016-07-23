	package tests;

import java.util.Random;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code logs into Chronicle with a new user that has a random generator for the name
// It then logs in and verifies the growl message is visible 

public class CreateNewUserLoginAndVerifyGrowl extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "CreateNewUserLoginAndVerifyGrowl";

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
		createNewUserLoginAndVerifyGrowl();

		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-logout user-logout type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Logout"));  
		
		selenium.click("link=Logout");
		
		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  
					
	}


	public void createNewUserLoginAndVerifyGrowl() throws Exception {
		// Open base url
		selenium.open(baseurl);
		
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		selenium.click("link=Login");	
		selenium.waitForCondition("selenium.isElementPresent(\"//span[@id='partial-addNewUser_label']\")", "10000");
		
		// generate random numbers for username
		Random  generator = new Random();
		int newNumber = generator.nextInt(10000);
		
		String userName = "chronicle-test" + newNumber; 

		selenium.click("id=partial-addNewUser_label");
		Thread.sleep(2000);

		selenium.type("id=id", userName);
		selenium.type("id=email", "chronicle-test@perforce.com");
		selenium.type("id=fullName", "chronicle test");
		selenium.type("id=password", "Chronicle612");
		selenium.type("id=passwordConfirm", "Chronicle612");
		
		// Login to Website
		selenium.click("name=save");
		selenium.click("id=save_label");
		Thread.sleep(2000);

		String quart_detailid = "1405";
		String quart_testname   = "CreateNewUserLoginVerifyGrowl";
		String quart_description= "Create User and Verify growl"; 		
		//writeFile1("\nskipped: 1043", "", "");
		// check to see if user selected is checked and write to file
		if(selenium.isVisible(("//div[contains(@id, 'p4cms-ui-notices')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid ,"fail", quart_scriptname,quart_testname, quart_description); }
	
	}
}


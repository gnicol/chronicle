package tests;

import java.util.Random;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code logs into Chronicle, creates a new role and verifies that the growl message is visible.

public class CreateNewRoleAndVerifyGrowl extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "CreateNewRoleAndVerifyGrowl";

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
		
		// generate random numbers for role
		Random  generator = new Random();
		int newNumber = generator.nextInt(10000);
		String roleName = "test-role" + newNumber; 	
		
		// Login to Chronicle with new role name
		createNewRoleAndVerifyGrowl(roleName);
		
		selenium.click("link=Home");
		
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-logout user-logout type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Logout"));  
		
		selenium.click("link=Logout");		
		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  
					
	} 


	public void createNewRoleAndVerifyGrowl(String roleName) throws Exception {
		
		// click on manage -- roles
		manageMenu();
		selenium.click(CMSConstants.MANAGE_ROLES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
	
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-manage-toolbar-stack-controller_p4cms-manage-toolbar-page-content-add']\")", "10000");
		// verify grid for manage roles
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'grid-options role-grid-options')]")));  
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'dojoxGrid')]")));  
	
		// click to create a new role
		selenium.click("//span[@id='dijit_form_Button_0']/span");
		selenium.click("//input[@value='Add Role']");
		Thread.sleep(2000);
		selenium.type("id=id", roleName);
		selenium.click("id=users-chronicle-test");
		selenium.type("id=id", "test-role");
		selenium.click("id=save_label"); 
		Thread.sleep(2000);
		// verify growl message
		//assertTrue for growl message on creating new role
		// check to see if user selected is checked and write to fil
		
		//writeFile1("\nskipped: 1044", "", "");
		
		String quart_detailid   = "1044";
		String  quart_testname   = "CreateNewRoleVerifyGrowl";
		String   quart_description= "verify new role and growl";
		if(!selenium.isVisible(("xpath=//*[@class='message']")))
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description );  }
		
		
		// Back to Website
		backToHome();
	}

}


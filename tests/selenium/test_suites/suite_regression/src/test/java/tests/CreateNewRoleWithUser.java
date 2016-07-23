package tests;

import java.util.Random;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code logs into Chronicle, creates a new role with user, edits the role and verifies the user is still checked.

public class CreateNewRoleWithUser extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "CreateNewRoleWithUser";
	

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

		
		// generate random numbers starting with number 1 to distinguish for role
		Random  generator = new Random();
		int newNumber = generator.nextInt(10000);
		String roleName = "1test-role" + newNumber; 	
		
		// Login to Chronicle with new role name
		createNewRoleWithUser(roleName);
		
		selenium.click("link=Home");
		
		assertTrue(selenium.isElementPresent("link=Logout"));  
		
		selenium.click("link=Logout");
		
		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  
					
	}	


	public void createNewRoleWithUser(String roleName) throws Exception {
		
		// click on manage -- roles
		manageMenu();
		selenium.click(CMSConstants.MANAGE_ROLES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
	
		// verify grid for manage roles
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'grid-options role-grid-options')]")));  
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'dojoxGrid')]")));  
	
		// click to create a new role
		selenium.click("//span[@id='dijit_form_Button_0']/span");
		selenium.click("//input[@value='Add Role']");
		Thread.sleep(1000);
		selenium.type("id=id", roleName);
		// assign role to user
		selenium.click("id=users-chronicle-test0");
		selenium.click("id=users-chronicle-test");
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		// Select a role from the grid and edit a role
		selenium.click("id=type-types-custom");
		Thread.sleep(2000);
		
		// click on arrow and edit
		// verify delete branch dialog
		selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[4]/span/span/span","");
		Thread.sleep(1000);
				
		selenium.click("id=dijit_MenuItem_6_text");    
		Thread.sleep(2000);
		 
		assertTrue(selenium.isElementPresent("//input[contains(@value, 'chronicle-test') and contains(@checked, 'checked')]"));
		
		String quart_detailid = "1043";
		String quart_testname   = "CreateNewRoleWithUser";
		String quart_description= "Create role with user"; 		
		//writeFile1("\nskipped: 1043", "", "");
		// check to see if user selected is checked and write to file
		if(selenium.isElementPresent("//input[contains(@value, 'chronicle-test') and contains(@checked, 'checked')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid ,"fail", quart_scriptname,quart_testname, quart_description); }
		
		// Back to Website
		backToHome();
	}
}


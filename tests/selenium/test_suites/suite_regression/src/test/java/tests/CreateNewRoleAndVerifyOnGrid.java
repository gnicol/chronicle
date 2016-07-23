package tests;

import java.util.Random;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


// This code logs into Chronicle, checks to see if a specific role if it already exists and deletes it
// Otherwise, it creates a new role if it doesn't exist and verifies that the role appears on the dojo grid.

public class CreateNewRoleAndVerifyOnGrid extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "CreateNewRoleAndVerifyOnGrid";

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
		createNewRoleAndVerifyOnGrid(roleName);
		
		selenium.click("link=Home");
		
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-logout user-logout type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Logout"));  
		
		selenium.click("link=Logout");
		
		selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  
					
	}
 

	public void createNewRoleAndVerifyOnGrid(String roleName) throws Exception {
		
		// click on manage -- roles
		manageMenu();
		selenium.click(CMSConstants.MANAGE_ROLES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
	
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-manage-toolbar-stack-controller_p4cms-manage-toolbar-page-content-add']\")", "10000");
		// verify grid for manage roles
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'grid-options role-grid-options')]")));  
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'dojoxGrid')]")));  
		
		// delete role prior to creating a new one
		// Select the role from the grid and Delete a role
		selenium.click("id=type-types-custom");
		Thread.sleep(2000);
	 	
		// Check to see if the role exists before deleting
		if(selenium.isTextPresent("0-new-test-role")) {
			// back to Home
			backToHome();
			// Code to click on 'Delete'
			selenium.click("id=dijit_MenuItem_1_text");
		  }	
			else {
				// click to create a new role if role doesn't exist
				selenium.click("//span[@id='dijit_form_Button_0']/span");
				selenium.click("//input[@value='Add Role']");
				Thread.sleep(2000);
				selenium.type("id=id", "0-new-test-role");
				selenium.click("id=users-chronicle-test");
				selenium.click("id=save_label");
				Thread.sleep(2000);
				assertTrue(selenium.isTextPresent("0-new-test-role"));
				}
		
		
		String quart_detailid   = "1045";
		String  quart_testname   = "CreateNewRoleVerifyOnGrid";
		String   quart_description= "Verify new role on grid";
		if (selenium.isTextPresent("0-new-test-role"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		// Back to Website
		backToHome();
	}

}


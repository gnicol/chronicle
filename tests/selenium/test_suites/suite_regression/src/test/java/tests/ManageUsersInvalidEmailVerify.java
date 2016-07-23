package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageUsersInvalidEmailVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageUsersInvalidEmailVerify";

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

		// User management
		ManageUsersInvalidEmailVerify();
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	

	
public void ManageUsersInvalidEmailVerify() throws Exception {
		
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_USERS_ADD_USER);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-manage-toolbar-stack-controller_p4cms-manage-toolbar-page-content-add']\")", "10000");
		// verify grid for manage users
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'grid-options user-grid-options')]")));  
		assertTrue(selenium.isElementPresent(("//div[contains(@class, 'dojoxGrid')]"))); 
		
		// verify elements on page
		assertTrue(selenium.isElementPresent(("//input[contains(@id, 'search-query')]")));  
		assertTrue(selenium.isElementPresent(("//input[contains(@id, 'role-roles-administrator')]")));  
		assertTrue(selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_0_label')]")));  
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'num-rows')]")));  
		
		// click to add user
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add User']");
		Thread.sleep(2000);
		
		String testUser = "TestUser";
		// form for add user
		selenium.type("id=id", testUser);
		selenium.type("id=email", "test-user@perforce");
		selenium.type("id=fullName", "TestUser");
		selenium.type("id=password", "testing!612");
		selenium.type("id=passwordConfirm", "testing!612");
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		// Verify error message
		assertTrue(selenium.isTextPresent("'test-user@perforce' is not a valid email address."));
		
		//writeFile1("\nskipped 1401", "", "ManageUsersAddUser.java");
		// check to see if user selected is checked and write to file
		
		 String quart_detailid   = "1420";
		 String  quart_testname   = "InvalidEmailVerify";
		 String  quart_description= "verify invalid email";
			if(selenium.isTextPresent("'test-user@perforce' is not a valid email address."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
		// Back to Website
		backToHome();
 }
}
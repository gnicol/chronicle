package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageUsersAddUser extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageUsersAddUser";

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
		manageUsersAddUser();
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	

	
public void manageUsersAddUser() throws Exception {
		
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_USERS_ADD_USER);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
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
		selenium.type("id=email", "test-user@perforce.com");
		selenium.type("id=fullName", "TestUser");
		selenium.type("id=password", "testing!612");
		selenium.type("id=passwordConfirm", "testing!612");
		selenium.click("id=save_label");
		Thread.sleep(3000);
		
		// Verify growl message
		assertTrue(selenium.isVisible("xpath=//*[@id='p4cms-ui-notices']"));
		assertTrue(selenium.isVisible("xpath=//*[@class='message']"));		
		
		 String quart_detailid   = "1401";
		 String  quart_testname   = "AddUser";
		 String  quart_description= "verify add user";
			if(selenium.isTextPresent("User 'TestUser' has been successfuly added"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// Back to Website
		backToHome();
 }
}
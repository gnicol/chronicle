package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageUsersDeleteUsers extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="ManageUsersDeleteUsers";

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
		manageUsersDeleteUser();
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	

	
public void manageUsersDeleteUser() throws Exception {
		
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_USERS_DELETE_USER);
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
		
		// search for test user then delete
		selenium.type("id=search-query", "chronicle-test");
		
		// Code to click on 'Delete'
		Thread.sleep(3000);

		selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[5]/span/span/span","");
		Thread.sleep(2000);
		selenium.click("id=dijit_MenuItem_7_text");
		Thread.sleep(2000);
		selenium.isTextPresent("Are you sure you want to delete the user");
		
		String quart_detailid   = "1188";
		String  quart_testname   = "DeleteUsers";
		String  quart_description= "manage users delete users";
		// verify delete user dialog
		//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent("Are you sure you want to delete the user")) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }				
	
		selenium.click("id=p4cms_ui_ConfirmDialog_0-button-action_label");
		Thread.sleep(2000);
		
		// Back to Website
		backToHome();
 }
}
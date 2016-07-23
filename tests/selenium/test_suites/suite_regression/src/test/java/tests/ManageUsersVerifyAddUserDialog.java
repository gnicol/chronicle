package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifies the add user dialog popup after clicking on the add user button

public class ManageUsersVerifyAddUserDialog extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname= "ManageUsersVerifyAddUserDialog";

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
		manageUsersVerifyAddUserDialog();
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	

	
public void manageUsersVerifyAddUserDialog() throws Exception {
		
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_USERS_ADD_USER_DIALOG_VERIFY);
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
		
		// verify add user button
		// click to create a new users
		assertTrue(selenium.isElementPresent("//span[@id='dijit_form_Button_0']/span"));
		
		// click to add user
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add User']");
		Thread.sleep(2000);
		
		assertTrue(selenium.isElementPresent("//span[@class='dijitReset dijitInline dijitButtonText']"));

		String quart_detailid   = "1226";
		String  quart_testname   = "AddUserDialog";
		String  quart_description= "manage users verify add user dialog";
		// verify delete user dialog
		//writeFile1("\nskipped 1185", "", "ManageUsersVerifyDeleteUserDialog.java");
		// check to see if user selected is checked and write to file
		if(selenium.isElementPresent("//span[@class='dijitReset dijitInline dijitButtonText']"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }				
		
		// Back to Website
		backToHome();
 }
}
package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageUsersEntriesTextVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageUsersEntriesTextVerify";

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
		ManageUsersEntriesTextVerify();
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	

	
public void ManageUsersEntriesTextVerify() throws Exception {
		
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
		
		// verify entries text
		assertTrue(selenium.isTextPresent("entries"));
		
		//writeFile1("\nskipped 1401", "", "ManageUsersAddUser.java");
		// check to see if user selected is checked and write to file
		 String quart_detailid   = "1203";
		 String  quart_testname   = "EntriesTextVerify";
		 String  quart_description= "verify entries text";
			if(selenium.isTextPresent( "entries"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		// Back to Website
		backToHome();
 }
}
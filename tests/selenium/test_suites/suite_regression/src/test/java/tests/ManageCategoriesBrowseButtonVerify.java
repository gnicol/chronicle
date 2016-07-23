package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageCategoriesBrowseButtonVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;

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
		ManageCategoriesBrowseButtonVerify();
	
		// Logout and verify Login link
		selenium.click("link=Logout");

		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	

	
public void ManageCategoriesBrowseButtonVerify() throws Exception {
		
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CATEGORIES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
	
		
		// click on add category
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add Category']");
		
		// title
		selenium.type("id=title", "Test category");
		
		// click browse button
		selenium.click("id=indexContent-browse-button_label");
		selenium.click("name=indexContent-browse-button");
		
		Thread.sleep(2000);
		
		// verify selector 
		assertTrue(selenium.isTextPresent("Select Content"));	
		
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent( "Select Content" ))
			writeFile("7119", "pass", "", "ManageCategoriesBrowseButtonVerify.java", "Manage category - verify browse button"); 
        else  { writeFile("7119", "fail", "", "ManageCategoriesBrowseButtonVerify.java", "Manage category - verify browse button"); }
						
		// Back to Website
		backToHome();
 }
}
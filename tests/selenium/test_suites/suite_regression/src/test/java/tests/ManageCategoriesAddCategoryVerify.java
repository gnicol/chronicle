package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageCategoriesAddCategoryVerify extends shared.BaseTest  {
	
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
		ManageCategoriesAddCategoryVerify();
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	

	
public void ManageCategoriesAddCategoryVerify() throws Exception {
		

		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CATEGORIES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// click on add category
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add Category']");
		
		// verify add category dialog
		assertTrue(selenium.isTextPresent("Add Category"));
		
		// title
		selenium.type("id=title", "Test category");
		
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent( "Add Category" ))
			writeFile("7036", "pass", "", "ManageCategoriesAddCategoryVerify.java", "Manage category - verify add category"); 
        else  { writeFile("7036", "fail", "", "ManageCategoriesAddCategoryVerify.java", "Manage category - verify add category"); }
				
		// save
		selenium.click("id=save_lavel");
		Thread.sleep(2000);
		
		// Back to Website
		backToHome();
 }
}
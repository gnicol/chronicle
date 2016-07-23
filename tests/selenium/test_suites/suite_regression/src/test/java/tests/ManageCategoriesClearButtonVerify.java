package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageCategoriesClearButtonVerify extends shared.BaseTest  {
	
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
		ManageCategoriesClearButtonVerify();
	
		// Logout and verify Login link
		selenium.click("link=Logout");

		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	

	
public void ManageCategoriesClearButtonVerify() throws Exception {
		
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
		
		// select a file
		selenium.click("//div[@id='dojox_grid__View_5']/div/div/div/div/table/tbody/tr/td[2]");
		selenium.click("id=p4cms_content_SelectDialog_1-button-select_label");
		selenium.click("//div[@id='buttons-element']/fieldset/span/input");
		
		// verify file
		assertTrue(selenium.isTextPresent("Testing"));
		
		// click clear
		selenium.click("id=indexContent-clear-button_label");
		selenium.click("name=indexContent-clear-button");
		
		assertFalse(selenium.isTextPresent("Testing"));
		
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		
		// check to see if user selected is checked and write to file
		if(!selenium.isTextPresent( "Testing" ))
			writeFile("7120", "pass", "", "ManageCategoriesClearButtonVerify.java", "Manage category - verify clear"); 
        else  { writeFile("7120", "fail", "", "ManageCategoriesClearButtonVerify.java", "Manage category - verify clear"); }
						
		// Back to Website
		backToHome();
 }
}
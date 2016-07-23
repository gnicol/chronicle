package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;

//This code clicks on manage --> modules and verifies the modules elements 


public class ManageModulesElements extends shared.BaseTest {
	
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

		
		// Verify Chronicle home page elements 
		manageModulesElements();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void manageModulesElements() throws Exception {
		// enable comments module
		manageMenu();
		selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// verify elements
		////writeFile1("\nskipped: 6611", "", "");
	    	    	        	          	    	        
		assertTrue(selenium.isElementPresent(("//form[contains(@id, 'p4cms_ui_grid_Form_0')]")));  
		assertTrue(selenium.isElementPresent(("//dd[contains(@id, 'tagFilter-display-element')]")));  
		assertTrue(selenium.isElementPresent(("//dd[contains(@id, 'statusFilter-display-element')]")));  
		assertTrue(selenium.isElementPresent(("//dd[contains(@id, 'tagFilter-display-element')]")));  
		assertTrue(selenium.isElementPresent(("//div[contains(@id, 'p4cms_ui_grid_DataGrid_0')]")));  
		
		// verify that type - any type is checked by default
		assertTrue(selenium.isElementPresent( "//input[@type='radio' and contains(@name, 'typeFilter[display]') and contains(@checked, true) ]" ));
		// verify that status - any status is checked by default
		assertTrue(selenium.isElementPresent( "//input[@type='radio' and contains(@name, 'statusFilter[display]') and contains(@checked, true) ]" ));
		
		// back to WebSite
		backToHome();
	}
}

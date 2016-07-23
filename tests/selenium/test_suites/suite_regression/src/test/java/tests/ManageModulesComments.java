package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;

// This code enables the comments module from the Manage --> Modules screen

public class ManageModulesComments extends shared.BaseTest {
	
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
		//manageModulesComments();
				
		// Logout and verify Login link
		selenium.click("link=Logout");

		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void manageModulesComments() throws Exception {
		// go to manage modules
		/*manageMenu();
		selenium.click(CMSConstants.MANAGE_MODULES_COMMENTS);
		Thread.sleep(2000);
		
		// filter analytics module
		selenium.click("id=tagFilter-display-social");
		Thread.sleep(2000);
		
		// verify elements
		//writeFile1("\nskipped: 6867", "", "");
		
		if (selenium.isTextPresent("Comment"))
			writeFile("6867", "pass", "", "ManageModulesComments.java", "Manage modules - verify comments" ); 
	        else  { writeFile("6867", "fail", "", "ManageModulesComments.java", "Manage modules - verify comments" ); }
		
		if (selenium.isTextPresent("Provides facility for user comments on content."))
			writeFile("6867", "pass", "", "ManageModulesComments.java", "Manage modules - verify comments" ); 
        else  { writeFile("6867", "fail", "", "ManageModulesComments.java", "Manage modules - verify comments" ); }
		
		if (selenium.isElementPresent(("//span[contains(@class, 'v1.0')]")))
			writeFile("6867", "pass", "", "ManageModulesComments.java", "Manage modules - verify comments" ); 
        else  { writeFile("6867", "fail", "", "ManageModulesComments.java", "Manage modules - verify comments" ); }
		
		if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/comment/resources/images/icon.png')]")))
			writeFile("6867", "pass", "", "ManageModulesComments.java", "Manage modules - verify comments" ); 
        else  { writeFile("6867", "fail", "", "ManageModulesComments.java", "Manage modules - verify comments" ); }
		
		if (selenium.isTextPresent("Perforce Software"))
			writeFile("6867", "pass", "", "ManageModulesComments.java", "Manage modules - verify comments" ); 
        else  { writeFile("6867", "fail", "", "ManageModulesComments.java", "Manage modules - verify comments" ); }
		
		if (selenium.isElementPresent(("//span[contains(@class, 'status disabled')]")))
			writeFile("6867", "pass", "", "ManageModulesComments.java", "Manage modules - verify comments" ); 
        else  { writeFile("6867", "fail", "", "ManageModulesComments.java", "Manage modules - verify comments" ); }
		
		if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitArrowButtonInner')]")))
			writeFile("6867", "pass", "", "ManageModulesComments.java", "Manage modules - verify comments" ); 
        else  { writeFile("6867", "fail", "", "ManageModulesComments.java", "Manage modules - verify comments" ); }
		
		//selenium.click("//div[@id='buttons-element']/fieldset/span/input");
		
	    	    	        
		assertTrue(selenium.isElementPresent(("//form[contains(@id, 'p4cms_ui_grid_Form_0')]")));  
		assertTrue(selenium.isElementPresent(("//dd[contains(@id, 'typeFilter-display-element')]")));  
		assertTrue(selenium.isElementPresent(("//dd[contains(@id, 'statusFilter-display-element')]")));  
		assertTrue(selenium.isElementPresent(("//dd[contains(@id, 'tagFilter-display-element')]")));  
		assertTrue(selenium.isElementPresent(("//div[contains(@id, 'p4cms_ui_grid_DataGrid_0')]")));  
		assertTrue(selenium.isElementPresent(("//img[contains(@src, '/sites/all/modules/comment/resources/images/icon.png')]")));  

		assertTrue(selenium.isElementPresent(("//a[contains(@href, 'mailto:support@perforce.com')]")));  
		assertTrue(selenium.isElementPresent(("//a[contains(@href, 'http://www.perforce.com')]")));  
		
		// back to WebSite
		backToHome();*/
	}
}

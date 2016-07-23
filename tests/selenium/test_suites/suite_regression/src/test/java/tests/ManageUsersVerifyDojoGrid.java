	package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifies the dojo grid

public class ManageUsersVerifyDojoGrid extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageUsersVerifyDojoGrid";
	
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
		manageUsersVerifyGridTitles();
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	

	
public void manageUsersVerifyGridTitles() throws Exception {
		
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_USERS_DOJO_GRID_VERIFY);
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
	
		//writeFile1("\nskipped 1175", "", "DojoGrid.java");
		
		String quart_detailid   = "1175";
		 String  quart_testname   = "DojoGridUsername";
		 String  quart_description= "verify grid titles - username";
			if (selenium.isTextPresent(("Username")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			 quart_detailid   = "1175";
			  quart_testname   = "DojoGridName";
			  quart_description= "verify grid titles - name";	
		if (selenium.isTextPresent(("Full Name")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		quart_detailid   = "1175";
		  quart_testname   = "DojoGridEmail";
		  quart_description= "verify grid titles - email";	
		if (selenium.isTextPresent(("Email Address")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		quart_detailid   = "1175";
		  quart_testname   = "DojoGridRoles";
		  quart_description= "verify grid titles - roles";	
		
		if (selenium.isTextPresent(("Roles")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
				
		
		quart_detailid   = "1175";
		  quart_testname   = "DojoGridActions";
		  quart_description= "verify grid titles - actions";	
		if (selenium.isTextPresent(("Actions")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// Back to Website
		backToHome();
 }
}
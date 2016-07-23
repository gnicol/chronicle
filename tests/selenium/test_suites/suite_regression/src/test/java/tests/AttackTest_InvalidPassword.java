package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> pages and verifies the title

public class AttackTest_InvalidPassword extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "AttackTest_InvalidPassword";
	
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

		// Login to Chronicle using incorrect username
		//chronicleLogin("p4cms", "p4cms");
		
		selenium.open(baseurl);
		waitForElements("link=Login");
		selenium.click("link=Login");
		selenium.waitForCondition("selenium.isElementPresent(\"//input[@id='partial-user']\")", "10000");
		selenium.type("id=partial-user", "p4cms");
		selenium.waitForCondition("selenium.isElementPresent(\"//input[@id='partial-password']\")", "10000");
		selenium.type("id=partial-password", "p4cms");
		
		
		selenium.click("name=login");
		selenium.click("id=partial-login_label");
		waitForText("Login failed. Invalid user or password.");
		
		// verify same error message for invalid username & invalid password
		assertTrue(selenium.isTextPresent("Login failed. Invalid user or password."));
		
		String quart_detailid   = "8926";
		 String quart_testname   = "InvalidPassword";
		 String quart_description= "verify invalid password";
		
			if (selenium.isTextPresent("Login failed. Invalid user or password."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
 }
}


package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;



public class LoginUsingFooterLink extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "LoginUsingFooterLink";

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
	public void validate(String username, String password) throws Exception {

		// Login to Chronicle
		selenium.open(baseurl);		
		waitForElements("link=Login");
		
		// login through footer link
		selenium.click("//div[4]/div/div/div[2]/div/div/ul/li[3]/a");
		selenium.waitForCondition("selenium.isElementPresent(\"//input[@id='partial-user']\")", "10000");
		selenium.type("id=partial-user", username);
		selenium.waitForCondition("selenium.isElementPresent(\"//input[@id='partial-password']\")", "10000");
		selenium.type("id=partial-password", password);
		
		selenium.click("name=login");
		selenium.click("id=partial-login_label");
		Thread.sleep(2000);
		
		String quart_detailid = "6137";
		String quart_testname   = "LoginUsingFooterLink";
		String quart_description= "Login using footer link"; 		
		//writeFile1("\nskipped: 1043", "", "");
		// check to see if user selected is checked and write to file
		if (selenium.isElementPresent("link=Logout"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid ,"fail", quart_scriptname,quart_testname, quart_description); }

	}
}


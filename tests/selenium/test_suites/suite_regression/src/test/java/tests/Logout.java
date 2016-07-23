package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


public class Logout extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "Logout";

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
		selenium.waitForPageToLoad("30000");
		           
		assertTrue(selenium.isElementPresent("link=Logout"));  

		selenium.click("link=Logout");
		waitForElements("link=Login");
	
		 String quart_detailid   = "6047";
		 String quart_testname   = "Logout";
		 String quart_description= "Logout";
		 
		if (selenium.isElementPresent("link=Login"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
	}
}


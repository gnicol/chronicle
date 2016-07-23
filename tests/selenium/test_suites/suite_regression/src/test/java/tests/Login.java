package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;



public class Login extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String browser;
	private String quart_scriptname = "Login";

	@BeforeClass
	@Parameters({ "baseurl", "redirecturl", "usergroup", "browser"})
	public void storeBaseURL(String baseurl, String redirecturl,
			String usergroup, String browser) {
		this.baseurl = baseurl;
		this.redirecturl = redirecturl;
		this.usergroup = usergroup;
		this.browser = browser;
	} 
 
	
	@DataProvider(name = "Users")
	public Object[][] createData() throws Exception {
		Object[][] retObjArr = getDataArray("data/TestData.xls", "Users", usergroup);
		return (retObjArr);
	}


	@Test(dataProvider = "Users")
	public void validate(String username, String password) throws Exception {

		// Login to Chronicle
        chronicleLogin(username, password);
		selenium.waitForPageToLoad("30000");
			
		//writeFile1("\nskipped: 6046", "", "");
		 String quart_detailid   = "6046";
		 String quart_testname   = "Login";
		 String quart_description= "Login";
		 
		if (selenium.isElementPresent("link=Logout"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

	}
}


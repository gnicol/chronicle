package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> system info and verifies the title

public class ManageSystemInfoPageVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageSystemInfoPageVerify";

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
		manageSystemInfoPageVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void manageSystemInfoPageVerify() throws Exception {
		 
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		//writeFile1("\nskipped: 6150", "", "");

		 String quart_detailid   = "6150";
		 String  quart_testname   = "PageTitle";
		 String  quart_description= "Check Manage - system info page title";
			if (selenium.isTextPresent("System Information"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
			
			
		quart_detailid = "6543";
		quart_testname = "CheckVersion";
		quart_description = "System Info - check version";
		
			if(selenium.isTextPresent("Version"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
			
			quart_detailid = "9496";
			quart_testname = "ActiveSite";
			quart_description = "System Info - active site";
			
				if(selenium.isTextPresent("Active Site"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
		
				
			quart_detailid = "9501";
			quart_testname = "CheckZend";
			quart_description = "System Info - zend";
			
				if(selenium.isTextPresent("Zend"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
	
			quart_detailid = "9502";
			quart_testname = "CheckP4cms";
			quart_description = "System Info - p4cms";
			
				if(selenium.isTextPresent("P4Cms"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
				else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
		
		//Back to Website
		backToHome();
	}
}


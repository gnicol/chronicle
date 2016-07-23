package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


// This code clicks on Manage and verifies each of the header text exist

public class ManageMenuHeaderTextVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageMenuHeaderTextVerify";
	
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
		
		// Verify Chronicle home page elements 
		manageMenuHeaderTextVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void manageMenuHeaderTextVerify() throws Exception {
		
		// Click Manage link
		manageMenu();
		//waitForText("Content Management");
		//writeFile1("\nskipped: 7552", "", "");
		Thread.sleep(2000);
		// Write to file for checking manage menu header text
		
		String quart_detailid   = "7552";
		 String quart_testname   = "HeaderTextVerify1";
		 String quart_description= "verify menu header text";
		
		if (selenium.isTextPresent("Content Management"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		 quart_detailid   = "7552";
		  quart_testname   = "HeaderTextVerify2";
		  quart_description= "verify site config";
		// Write to file for checking manage menu header text
		if (selenium.isTextPresent("Site Configuration"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		 quart_detailid   = "7552";
		  quart_testname   = "HeaderTextVerify3";
		  quart_description= "verify user management text";
		// Write to file for checking manage menu header text
		if (selenium.isTextPresent("User Management"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		 quart_detailid   = "7552";
		  quart_testname   = "HeaderTextVerify4";
		  quart_description= "verify sytem text";
		// Write to file for checking manage menu header text
		if (selenium.isTextPresent("System"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		// Back to Website
		selenium.open(baseurl);

	}
}


package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;
import java.text.SimpleDateFormat;
import java.util.Date;

// This code is a check for the Website. If the Site is up, then we just do a login,
// otherwise, we call chronicleSetup() in BaseTest.java to go through the Chronice setup
// During the Setup, we write the results to a file.

public class A_ChronicleSetup extends shared.BaseTest {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private static String testType;

	@BeforeClass
	@Parameters({ "baseurl", "redirecturl", "usergroup", "testType" })
	public void storeBaseURL(String baseurl, String redirecturl, String usergroup, String testType) {
		this.baseurl = baseurl;
		this.redirecturl = redirecturl;
		this.usergroup = usergroup;
		// call the CodelineInfo for the testType that gets read from the xml files
		Add_CodelineInfo.testType = testType;
	}
 
	@DataProvider(name = "Users")
	public Object[][] createData() throws Exception {
		Object[][] retObjArr = getDataArray("data/TestData.xls", "Users", usergroup);
		return (retObjArr);
	}

	@Test(dataProvider = "Users")
 	public void validate(String username, String password) throws Exception {

		backToHome();
		Thread.sleep(2000);
 		
		// Logic to check if Site is up already 
		if (selenium.isElementPresent(("//img[contains(@title, 'Cover Logo')]")))
			{
			   // do nothing but login if Website already setup
			   chronicleLogin(username,password);  
			   selenium.open(baseurl);
			   Thread.sleep(2000);
			   //waitForElements("link=Logout");
			   //selenium.click("link=Logout"); 
			   
			   // change the default them back to business
			   changeDefaultTheme();
			   
			}	
		 
		else  {  // Website needs to be setup  
				 // Setup Chronicle 
				  chronicleSiteSetup();
					
				 // Login to Chronicle
				  chronicleLogin(username,password);
				  Thread.sleep(2000); 
				  
				  // get Codeline
				  Add_CodelineInfo.addCodeLineInfo();
				  	 
				  selenium.open(baseurl);
				  Thread.sleep(2000);
				  
				  // check the Tablet theme HOME & BACK buttons
				  verifyTabletFirstMainMenuButtons();
				  
				  // check the tablet page, blog, & press release
				  verifyTabletContent();
				  
				  // change theme because default is now tablet theme
				  changeDefaultTheme(); 
				  	  
				  // create new users for base state
				  createNewUserBaseState();			  
				  selenium.open(baseurl);
				  
				  //waitForElements("link=Logout");
				  //selenium.click("link=Logout");
			} 				
	}
}

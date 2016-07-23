package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;
import java.text.SimpleDateFormat;
import java.util.Date;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class SaveTransitionPressReleaseServerTimeVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "SaveTransitionPressReleaseServerTimeVerify";

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
		SaveTransitionPressReleaseServerTimeVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
	}
	
	public void SaveTransitionPressReleaseServerTimeVerify() throws InterruptedException, Exception {
		
		// Verify title & close icon & content type
		verifyContentElements();	
		Thread.sleep(2000);
		
		// press release
		// click on Pages in left tab
		selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
		// Press release
		Thread.sleep(1000);
		browserSpecificPressRelease();

		Thread.sleep(2000);
		// click form mode and verify all elements
		selenium.click("id=add-content-toolbar-button-form_label");
		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
			Thread.sleep(1000); 
			
			// Save the page info
	 		selenium.click("id=add-content-toolbar-button-Save_label");			
			Thread.sleep(2000);
						
			// click on the promote radio button
			selenium.click("id=workflow-state-review");
			Thread.sleep(2000);
			
			// save out 
			selenium.click("id=save_label");	
			Thread.sleep(3000);
	
			String quart_detailid   = "8438";
			String quart_testname   = "PressReleaseServerTimeVerify";
			String quart_description= "verify Save press release server time";
					
			if (selenium.isTextPresent("Server Time"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			// back Home
			selenium.open(baseurl);
	}
}

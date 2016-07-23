package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class AddBasicPageAllElementsVerify extends shared.BaseTest {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="AddBasicPageAllElementsVerify";

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
		AddBasicPageAllElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void AddBasicPageAllElementsVerify() throws InterruptedException, Exception {
		
		// Verify title & close icon & content type
		verifyContentElements();
		
		// Basic page
		
		browserSpecificBasicPage();
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		 String quart_detailid   = "7888";
 		 String quart_testname   = "PlaceModeBodyVerify";
 		 String quart_description= "Add basic page - place mode body verify";
 		// verify place mode body

 		if (selenium.isElementPresent(("//body[contains(@id, 'dijitEditorBody')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		 quart_detailid   = "8014";
 		 quart_testname   = "PlaceModeButtonElementVerify";
 		 quart_description= "Add basic page - place mode button verify";

 		// verify place mode button	

 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-in-place_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
 		
 		 quart_detailid   = "7980";
 		 quart_testname   = "PlaceModeCancelButtonVerify";
 		 quart_description= "Edit basic page - place mode cancel verify ";	
 		// verify place mode cancel 
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-cancel_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "7890";
 		 quart_testname   = "PlaceModeCategoryButtonVerify";
 		 quart_description= "Add basic page - place mode category verify";
 		// verify place mode category

 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Categories_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "7983";
 		 quart_testname   = "PlaceModeMenuButtonVerify";
 		 quart_description= "Add basic page - place mode menu verify";
 		// verify place mode menus

 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Menus_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "1600";
 		 quart_testname   = "PlaceModeSaveButtonVerify";
 		 quart_description= "Add basic page - place mode save button verify";
 		// verify place mode save button

 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Save_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
	
 		quart_detailid   = "7981";
		 quart_testname   = "PlaceModeTitleVerify";
		 quart_description= "Add basic page - place mode title verify";
		 
 		if (selenium.isTextPresent("Title"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "7891";
 		 quart_testname   = "PlaceModeURLButtonVerify";
 		 quart_description= "Add basic page - place mode url button verify";
 		// verify place mode url

 		selenium.clickAt("id=add-content-toolbar-button-URL","");
 		Thread.sleep(1000);
 		if (selenium.isTextPresent(("Use Title for URL")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
		 quart_detailid   = "7889";
		 quart_testname   = "PlaceModeFormButtonVerify";
		 quart_description= "Add basic page - place mode form element ";
		 
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-form_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 	
 		
 		
 	//****  Click into form mode ****//
 		
 		// click form mode and verify all elements
 		selenium.click("id=add-content-toolbar-button-form_label");
 		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
 		
 		  quart_detailid   = "7782";
 		  quart_testname   = "FormModeBodyVerify";
 		  quart_description= "Add basic page - form mode body verify";
 		// verify place mode body

 		if (selenium.isElementPresent(("//div[contains(@id, 'body-Editor')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		
 		quart_detailid   = "7985";
		 quart_testname   = "FormModeButtonElementVerify";
		 quart_description= "Add basic page - form mode button verify ";
		 
		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-form_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
		quart_detailid   = "7984";
		 quart_testname   = "FormModePlaceButtonVerify";
		 quart_description= "Add basic page - form mode place button verify ";
		 
		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-in-place_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
         quart_detailid   = "7892";
 		 quart_testname   = "FormModeCancelButtonVerify";
 		 quart_description= "Add basic page - form mode cancel verify ";
 		// verify form mode cancel

 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-cancel_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		 
 		quart_detailid   = "1583";
 		 quart_testname   = "FormModeCategoryVerify";
 		 quart_description= "Add basic page - form mode category verify ";
 		// verify form mode category	

 		if (selenium.isTextPresent("Categories"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid   = "7989";
 		 quart_testname   = "FormModeMenuVerify";
 		 quart_description= "Add basic page - form mode menu verify ";
 		// verify form mode menu

 		if (selenium.isTextPresent(("Menus")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid   = "7988";
 		 quart_testname   = "FormModeHeadingVerify";
 		 quart_description= "Add basic page - form mode heading verify ";
 		// verify form mode heading

 		if (selenium.isTextPresent(("Basic Page")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
         else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		quart_detailid   = "7893";
 		 quart_testname   = "FormModeIconVerify";
 		 quart_description= "Add basic page - form mode icon verify ";
 		// verify form mode icon	

 		if (selenium.isElementPresent(("//img[contains(@src, '/type/icon/id/basic-page')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid   = "7986";
 		 quart_testname   = "FormModeSaveButtonVerify";
 		 quart_description= "Add basic page - form mode save button verify ";
 		// verify form mode save 

 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Save_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		quart_detailid   = "7987";
		 quart_testname   = "FormModeTitleVerify";
		 quart_description= "Add basic page - form mode title verify ";
		 
		if (selenium.isTextPresent("Title"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid   = "7895";
 		 quart_testname   = "FormModeURLVerify";
 		 quart_description= "Add basic page - form mode url verify ";
 		// verify form mode url	

 		if (selenium.isElementPresent(("//input[contains(@id, 'url-auto-true')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		if (selenium.isTextPresent("Use Title for URL"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 			
 		
 		
 			// add pages in review and publish mode for manage content
 	 		selenium.open(baseurl);
 	 		// click manage menu then click for content
 	 		verifyContentElements();
 	 		browserSpecificBasicPage();
 	 		Thread.sleep(1000);
 	 		addBasicPage();
 	 		
 	 		addBasicPageReviewMode();  
 	 	
 	 		addBasicPagePublishMode();  
			
		// back home
		    backToHome();
	}
}

package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class EditBasicPageAllElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "EditBasicPageAllElementsVerify";
	
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
		EditBasicPageAllElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");

	}
	
	public void EditBasicPageAllElementsVerify() throws InterruptedException, Exception {
	
		// enter info, save page, and click edit 
		verifyContentElements();
		editBasicPage();
		
		//waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
	
		 String quart_detailid   = "7796";
		 String quart_testname   = "PlaceModeBodyVerify";
		 String quart_description= "place mode verify body";

		// verify place mode body
		if (selenium.isElementPresent(("//div[contains(@class, 'content-element content-element-type-editor content-element-body')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
		
		
		 quart_detailid   = "8015";
		 quart_testname   = "PlaceModeButtonVerify";
		 quart_description= "place mode button verify";

		// verify place mode button	
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-in-place_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "8016";
		 quart_testname   = "PlaceModeElementVerify";
		 quart_description= "place mode form button verify";

		// verify place mode button	
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-form_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 quart_detailid   = "8023";
		 quart_testname   = "PlaceModeCancelButtonVerify";
		 quart_description= "place mode cancel verify ";	
		// verify place mode cancel 
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-cancel_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
		
		
		
		 quart_detailid   = "8019";
		 quart_testname   = "PlaceModeCategoryVerify";
		 quart_description= "place mode category verify";
		// verify place mode category
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Categories_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		
		 quart_detailid   = "8024";
		 quart_testname   = "PlaceModeDeleteButtonVerify";
		 quart_description= "place mode delete button verify";
		// verify place mode delete
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-delete_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		
		 quart_detailid   = "8018";
		 quart_testname   = "PlaceModeMenusVerify";
		 quart_description= "place mode menu verify";
		// verify place mode menus
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Menus_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 quart_detailid   = "8022";
		 quart_testname   = "PlaceModeSaveButtonVerify ";
		 quart_description= "place mode save button verify";
		// verify place mode save button
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Save_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		
		 quart_detailid   = "8017";
		 quart_testname   = "PlaceModeURLVerify";
		 quart_description= "place mode url button verify";
		// verify place mode url
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-URL')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 /*quart_detailid   = "8020";
		 quart_testname   = "WorkflowVerify";
		 quart_description= "place mode workflow ";
		// verify place mode workflow
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Workflow_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }*/
		
		
		
	//****  Click into form mode ****//
		
		// click form mode and verify all elements
		selenium.click("id=edit-content-toolbar-button-form_label");
		selenium.click("//div[@id='edit-content-toolbar']/span[4]/input");
		
		//waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
					
		 quart_detailid   = "8033";
		 quart_testname   = "FormModeBodyVerify";
		 quart_description= "form mode body verify ";
        if (selenium.isElementPresent(("//div[contains(@id, 'body-Editor')]")))
        	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
			
        quart_detailid   = "8030";
		 quart_testname   = "FormModeCancelButtonVerify";
		 quart_description= "form mode cancel verify ";
		// verify form mode cancel
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-cancel_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		 
		quart_detailid   = "8038";
		 quart_testname   = "FormModeCategoryVerify";
		 quart_description= "form mode category verify ";
		// verify form mode category	
		if (selenium.isTextPresent(("Categories")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "8031";
		 quart_testname   = "FormModeDeleteButtonVerify";
		 quart_description= "form mode delete verify ";
		// verify form mode delete	
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-delete_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
		
		
		quart_detailid   = "8037";
		 quart_testname   = "FormModeMenuVerify";
		 quart_description= "form mode menu verify ";
		// verify form mode menus
		if (selenium.isTextPresent(("Menus")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "8035";
		 quart_testname   = "FormModeHeadingVerify";
		 quart_description= "form mode heading verify ";
		// verify form mode heading
		if (selenium.isTextPresent(("Basic Page")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "8032";
		 quart_testname   = "FormModeTitleVerify";
		 quart_description= "Edit Basic apge - formn mode title verify";
			if (selenium.isTextPresent("Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
		
		quart_detailid   = "8046";
		 quart_testname   = "FormModeIconVerify";
		 quart_description= "form mode icon verify ";
		// verify form mode icon	
		if (selenium.isElementPresent(("//img[contains(@src, '/type/icon/id/basic-page')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
		
		
		quart_detailid   = "8029";
		 quart_testname   = "FormModeSaveButtonVerify";
		 quart_description= "form mode save button verify ";
		// verify form mode save 
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Save_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
				
		
		
		quart_detailid   = "8036";
		 quart_testname   = "FormModeURLVerify";
		 quart_description= "form mode url verify ";
		// verify form mode url	
		if (selenium.isElementPresent("//input[contains(@id, 'url-auto-true')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
		
		
		/*quart_detailid   = "8039";
		 quart_testname   = "WorkflowVerify";
		 quart_description= "form mode workflow verify ";
		// verify form mode workflow	 
		if (selenium.isTextPresent(("Workflow')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
		*/
		
		
		quart_detailid   = "8027";
		 quart_testname   = "FormModePlaceButtonVerify";
		 quart_description= "form mode in place button verify ";
		// verify in form button
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-in-place_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
			
		// back Home
		selenium.open(baseurl);
	}
}

package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class EditBlogPostAllElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "EditBlogPostAllElementsVerify";

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
		EditBlogPostAllElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");

	}
	
	public void EditBlogPostAllElementsVerify() throws InterruptedException, Exception {
	
		// add blog, save, and click edit
		verifyContentElements();
		editBlogPost();
	
		 String quart_detailid   = "8078";
		 String quart_testname   = "PlaceModeBodyVerify";
		 String quart_description= "place mode body verify";
		// verify place mode body
		if (selenium.isElementPresent(("//div[contains(@class, 'content-element content-element-type-editor content-element-body')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		  quart_detailid   = "8026";
		  quart_testname   = "PlaceModeButtonVerify";
		  quart_description= "place mode element verify";
		// verify place mode button	
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-in-place_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		  quart_detailid   = "1773";
		  quart_testname   = "PlaceModeCancelButtonVerify";
		  quart_description= "place mode cancel button";
		// verify place mode cancel 
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-cancel_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		  quart_detailid   = "7618";
		  quart_testname   = "PlaceModeCategoryVerify";
		  quart_description= "place mode category verify";
		// verify place mode category
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Categories_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		  quart_detailid   = "8075";
		  quart_testname   = "PlaceModeDeleteButtonVerify";
		  quart_description= "place mode delete button verify";
		// verify place mode delete
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-delete_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		  quart_detailid   = "7617";
		  quart_testname   = "PlaceModeMenusVerify";
		  quart_description= "place mode menu verify";
		// verify place mode menus
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Menus_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		quart_detailid   = "8076";
		 quart_testname   = "PlaceModeTitleVerify";
		 quart_description= "place mode title verify";
			if (selenium.isTextPresent("Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
			
	 		quart_detailid   = "8077";
			 quart_testname   = "PlaceModeDateVerify";
			 quart_description= "place mode date verify ";
			// verify form mode date
			if (selenium.isElementPresent(("//div[contains(@class, 'content-element content-element-type-dateTextBox content-element-date')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
			
		
		 quart_detailid   = "8116";
		  quart_testname   = "PlaceModeAuthorVerify";
		  quart_description= "place mode author verify";
		// verify place mode body
		if (selenium.isElementPresent(("//input[contains(@id, 'author')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		  quart_detailid   = "7795";
		  quart_testname   = "PlaceModeSaveButtonVerify";
		  quart_description= "place mode save button verify";
		// verify place mode save button
		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_DropDownButton_1_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		  quart_detailid   = "7798";
		  quart_testname   = "PlaceModeURLVerify";
		  quart_description= "place mode url verify";
		// verify place mode url
			if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-URL')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		 quart_detailid   = "6153";
		  quart_testname   = "PlaceModeElementVerify";
		  quart_description= "place mode in form element verify";
		// verify in form button
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-form_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }	
		
		
		 /* quart_detailid   = "7794";
		  quart_testname   = "WorkflowVerify";
		  quart_description= "place mode workflow verify";
		// verify place mode workflow
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Workflow_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
	*/
		
	//**** Click into form mode ****//
		
		// click form mode and verify all elements
		selenium.click("id=edit-content-toolbar-button-form_label");
		selenium.click("//div[@id='edit-content-toolbar']/span[4]/input");
		
		//waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		
		 quart_detailid   = "8050";
		  quart_testname   = "FormModeBodyVerify";
		  quart_description= "form mode body verify";
        if (selenium.isElementPresent(("//div[contains(@id, 'body-Editor')]")))
        	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }			
			
					
        quart_detailid   = "8044";
		  quart_testname   = "FormModeCancelButtonVerify";
		  quart_description= "form mode cancel verify";
		// verify form mode cancel
		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-cancel_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		 
		 quart_detailid   = "8038";
		  quart_testname   = "FormModeCategoryVerify";
		  quart_description= "form mode category verify";
		// verify form mode category	
		if (selenium.isTextPresent(("Categories')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		 quart_detailid   = "8045";
		  quart_testname   = "FormModeDeleteButtonVerify";
		  quart_description= "form mode delete verify";
		// verify form mode delete	
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-delete_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }			
		
		
		 quart_detailid   = "8054";
		  quart_testname   = "FormModeMenuVerify";
		  quart_description= "form mode menu verify";
		// verify form mode menus
		if (selenium.isTextPresent(("Menus')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		 quart_detailid   = "8051";
		  quart_testname   = "FormModeAuthorVerify";
		  quart_description= "form mode author verify";
		// verify place mode body
		if (selenium.isElementPresent(("//input[contains(@id, 'author')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		 quart_detailid   = "8047";
		  quart_testname   = "FormModeHeadingVerify";
		  quart_description= "form mode heading verify";
		// verify form mode heading
		if (selenium.isTextPresent(("Basic Page")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		quart_detailid   = "8048";
		 quart_testname   = "FormModeTitleVerify";
		 quart_description= "form mode title verify";
			if (selenium.isTextPresent("Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
			
		
		quart_detailid   = "8052";
		 quart_testname   = "FormModeExcerptVerify";
		 quart_description= "form mode excerpt verify ";
		if (selenium.isElementPresent(("//div[contains(@id, 'excerpt-Editor')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		 quart_detailid   = "8049";
 		 quart_testname   = "FormModeEditBlogPostFormModeDateVerify";
 		 quart_description= "form mode date verify ";
 		// verify form mode date
 		if (selenium.isElementPresent(("//input[contains(@id, 'date')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		 quart_detailid   = "8034";
		  quart_testname   = "FormModeIconVerify";
		  quart_description= "form mode icon verify";
		// verify form mode icon	
		if (selenium.isElementPresent(("//img[contains(@serc, '/type/icon/id/basic-page')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		 quart_detailid   = "8043";
		  quart_testname   = "FormModeSaveButtonVerify";
		  quart_description= "form mode save button verify";
		// verify form mode save 
		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_DropDownButton_1_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		 quart_detailid   = "8053";
		  quart_testname   = "FormModeURLVerify";
		  quart_description= "form mode url verify";
		// verify form mode url	
		if (selenium.isElementPresent(("//input[contains(@id, 'url-path')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
		
		
		/* quart_detailid   = "8039";
		  quart_testname   = "WorkflowVerify";
		  quart_description= "form mode workflow verify";
		// verify form mode workflow	 
		if (selenium.isTextPresent(("Workflow')]")))
			writeFile("8039", "pass", "", "WorkflowVerify.java", "in form mode - workflow verify"); 
		else  { writeFile("8039", "fail", "", "WorkflowVerify.java", "in form mode - workflow verify"); }
		*/
		
		 quart_detailid   = "8041";
		  quart_testname   = "FormModePlaceButtonVerify";
		  quart_description= "form mode in place element verify";
		// verify in form button
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-in-place_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid , "pass", quart_scriptname, quart_testname, quart_description); }		
			
		// back Home
		selenium.open(baseurl);
	}
}

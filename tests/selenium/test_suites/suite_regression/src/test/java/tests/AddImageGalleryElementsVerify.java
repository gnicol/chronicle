package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class AddImageGalleryElementsVerify extends shared.BaseTest {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="AddImageGalleryElementsVerify";

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
		AddImageGalleryElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void AddImageGalleryElementsVerify() throws InterruptedException, Exception {
		
		// Verify title & close icon & content type
		verifyContentElements();
		
		// Image Gallery
		
		browserSpecificImageGallery();
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
 		
		 String quart_detailid   = "10358";
 		 String quart_testname   = "PlaceModeTitleVerify";
 		 String quart_description= "Add basic page - place mode title verify";
 		// verify place mode body
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isTextPresent(("Title")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		 quart_detailid   = "10359";
 		 quart_testname   = "PlaceModeImagesAreaVerify";
 		 quart_description= "Add basic page - place mode images area verify";

 		// verify place mode button	
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//span[contains(@class, 'value-placeholder')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
		selenium.click("css=#p4cms_content_Element_1 > span.value-placeholder");
		Thread.sleep(1000);
 		
 		 quart_detailid   = "10362";
 		 quart_testname   = "PlaceModeImagesInputFormVerify";
 		 quart_description= "Edit basic page - place mode images input form verify ";	
 		// verify place mode cancel 
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//div[contains(@id, 'content-form-images')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "10363";
 		 quart_testname   = "PlaceModeImagesBrowseButtonVerify";
 		 quart_description= "Add basic page - place mode browse button verify";
 		// verify place mode category
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_0_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "10364";
 		 quart_testname   = "PlaceModeMenuButtonVerify";
 		 quart_description= "Add basic page - place mode menu verify";
 		// verify place mode menus
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_1_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "10361";
 		 quart_testname   = "PlaceModeImagesTextVerify";
 		 quart_description= "Add basic page - place mode save button verify";
 		// verify place mode save button
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isTextPresent(("Images")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
		
		
		
 	//****  Click into form mode ****//
 		
 		// click form mode and verify all elements
 		selenium.click("id=add-content-toolbar-button-form_label");
 		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
 		
 		 quart_detailid   = "10347";
		  quart_testname   = "PlaceModeImagegalleryVerify";
		  quart_description= "Add image gallery - place mode verify";
		// verify place mode body
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

		if (selenium.isElementPresent(("//img[contains(@src, '/type/icon/id/gallery')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
 		  quart_detailid   = "10350";
 		  quart_testname   = "FormModeIconVerify";
 		  quart_description= "Add image gallery - form mode icon verify";
 		// verify place mode body
 		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//img[contains(@src, '/type/icon/id/gallery')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		
 		quart_detailid   = "10351";
		 quart_testname   = "FormModeImageGalleryTextVerify";
		 quart_description= "Add image gallery - form mode text verify ";
		 
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

		if (selenium.isTextPresent(("Image Gallery")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
		quart_detailid   = "10352";
		 quart_testname   = "FormModePlaceTitleVerify";
		 quart_description= "Add image gallery - form mode title verify ";
		 
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

		if (selenium.isTextPresent(("Title")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
         quart_detailid   = "10353";
 		 quart_testname   = "FormModeTitleFormVerify";
 		 quart_description= "Add image gallery - form mode title form verify ";
 		// verify form mode cancel
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//input[contains(@id, 'title')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		selenium.click("id=add-content-toolbar-button-Save_label");
   		selenium.click("id=save_label");			
   		Thread.sleep(2000);
   		
 		 quart_detailid   = "10353";
 		 quart_testname   = "FormModeTitleRequiredVerify";
 		 quart_description= "Add image gallery - form mode title required verify ";
 		// verify form mode cancel
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isTextPresent(("Value is required and can't be empty")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		 
 		quart_detailid   = "10354";
 		 quart_testname   = "FormModeImagesTextVerify";
 		 quart_description= "Add image gallery - form mode images verify ";
 		// verify form mode category	
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isTextPresent("Images"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		quart_detailid   = "10355";
 		 quart_testname   = "FormModeImageFormVerify";
 		 quart_description= "Add image gallery - form mode image form verify ";
 		// verify form mode menu
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

  		if (selenium.isElementPresent(("//input[contains(@name, 'images-title[]')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid   = "10356";
 		 quart_testname   = "FormModeBrowseButtonVerify";
 		 quart_description= "Add image gallery - form mode browse button verify ";
 		// verify form mode heading
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

   		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_0')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
         else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		quart_detailid   = "10357";
 		 quart_testname   = "FormModeClearButtonVerify";
 		 quart_description= "Add image gallery - form mode clear button verify ";
 		// verify form mode icon	
		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_1')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
// 		selenium.click("id=dijit_form_Button_0_label");
//		//selenium.click("xpath=(//input[@value=''])[10]");
// 		quart_detailid   = "10357";
//		 quart_testname   = "FormModeClearButtonVerify";
//		 quart_description= "Add image gallery - form mode clear button verify ";
//		// verify form mode icon	
//		 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
//
//		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_1')]")))
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
//       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
//		
 		
 			// add pages in review and publish mode for manage content
 	 		selenium.open(baseurl);
	}
}

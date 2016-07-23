package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;
import java.text.SimpleDateFormat;
import java.util.Date;

import shared.BaseTest;


//This code creates a press release - content in form mode; it clicks on add a press release, clicks on in-form mode and verifys all elements to write to a file.
//It also enters a title and body and then saves the press release

public class AddPressReleaseAllElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="AddPressReleaseAllElementsVerify";

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
		AddPressReleaseAllElementsVerify(); 

		// Logout and verify Login link
		selenium.click("link=Logout");
	}
	



public void AddPressReleaseAllElementsVerify() throws Exception {
	
	// Verify title & close icon & content type
		verifyContentElements();
		
		// Press release
		browserSpecificPressRelease();
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		 String quart_detailid   = "7885";
 		 String quart_testname   = "PlaceModeButtonVerify";
 		 String quart_description= "Add press release - place mode author verify";
 		// verify place mode body
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-form_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		quart_detailid   = "8007";
		 quart_testname   = "PlaceModeBodyVerify";
		 quart_description= "Add press release - place mode body verify ";
		
		if (selenium.isElementPresent(("//body[contains(@id, 'dijitEditorBody')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
		
 		
 		 quart_detailid   = "7791";
 		 quart_testname   = "PlaceModeCancelButtonVerify";
 		 quart_description= "Add press release - place mode cancel verify ";	
 		// verify place mode cancel 
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-cancel_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		 quart_detailid   = "8001";
 		 quart_testname   = "PlaceModeCategoryVerify";
 		 quart_description= "Add press release - place mode category verify";
 		// verify place mode category
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Categories')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		 quart_detailid   = "8000";
 		 quart_testname   = "PlaceModeMenuVerify";
 		 quart_description= "Add press release - place mode menu verify";
 		// verify place mode menus
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Menus')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		 quart_detailid   = "8003";
 		 quart_testname   = "PlaceModeSaveButtonVerify";
 		 quart_description= "Add press release - place mode save button verify";
 		// verify place mode save button
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Save_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	
 		quart_detailid   = "7884";
		 quart_testname   = "PlaceModeTitleVerify";
		 quart_description= "Add press release - place mode title verify";
			if (selenium.isTextPresent("Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		 quart_detailid   = "7887";
 		 quart_testname   = "PlaceModeURLVerify";
 		 quart_description= "Add press release - place mode url button verify";
 		// verify place mode url
 		 
  		//selenium.clickAt("//div/div/div/div/ul/span/li[5]/div/div/div/span[5]/span/span","");
  		selenium.clickAt("id=add-content-toolbar-button-URL","");
 		Thread.sleep(1000);
 		if (selenium.isTextPresent(("Use Title for URL")))
 			
 		//if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-URL')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		 quart_detailid   = "8006";
 		 quart_testname   = "PlaceModeSubTitleVerify";
 		 quart_description= "Add press release - place mode sub title verify ";
 		// verify place mode workflow
 		if (selenium.isTextPresent("Sub-Title"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		 quart_detailid   = "8005";
 		 quart_testname   = "PlaceModeDateVerify";
 		 quart_description= "Add Press Release - place mode date verify ";
 		// verify form mode date
 		if (selenium.isElementPresent(("//div[contains(@class, 'content-element content-element-type-dateTextBox content-element-date')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		quart_detailid   = "1597";
		 quart_testname   = "PlaceModeLocationVerify";
		 quart_description= "Add press release - place mode location verify ";

			if (selenium.isTextPresent("Location"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid   = "8004";
		 quart_testname   = "PlaceModeContactDetailsVerify";
		 quart_description= "Add press release - place mode contact details ";
 		if (selenium.isTextPresent("Contact Details"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }	
	
			 quart_detailid   = "7999";
			 quart_testname   = "PlaceModeVerifyElements";
			 quart_description= "Add press release - place mode place element ";
			 if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-in-place_label')]")))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		// inplace mode check
		//inplaceModeCheck();
	
 		
 		
 	//****  Click into form mode ****//
 		
 		// click form mode and verify all elements
 		selenium.click("id=add-content-toolbar-button-form_label");
 		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
 		 					
 		// form mode check
 		//formModeCheck();
			
 		
 		quart_detailid   = "7994";
		 quart_testname   = "FormModeBodyVerify";
		 quart_description= "Add press release - form mode body verify ";
		
		if (selenium.isElementPresent(("//div[contains(@id, 'body-Editor')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		quart_detailid   = "7896";
		 quart_testname   = "FormModeButtonVerify";
		 quart_description= "Add press release - form mode button verify ";
		 if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-form_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
		
 		
         quart_detailid   = "7899";
 		 quart_testname   = "FormModeCancelButtonVerify";
 		 quart_description= "Add press release - form mode cancel verify ";
 		// verify form mode cancel
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-cancel_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		 
 		quart_detailid   = "7880";
 		 quart_testname   = "FormModeCategoryVerify";
 		 quart_description= "Add press release - form mode category verify ";
 		// verify form mode category	
 		if (selenium.isTextPresent(("Categories")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7993";
		 quart_testname   = "FormModeLocationVerify";
		 quart_description= "Add press release - form mode location verify";
		// verify place mode button	
		if (selenium.isTextPresent("Location"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
		
		 quart_detailid   = "7992";
 		 quart_testname   = "FormModeSubTitleVerify";
 		 quart_description= "Add press release - form mode sub title verify ";
 		// verify place mode workflow
 		if (selenium.isTextPresent("Sub-Title"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		
 		quart_detailid   = "7906";
 		 quart_testname   = "FormModeHeadingVerify";
 		 quart_description= "Add press release - form mode heading verify ";
 		// verify form mode delete	
 		if (selenium.isTextPresent("Press Release"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7883";
 		 quart_testname   = "FormModeMenuVerify";
 		 quart_description= "Add press release - form mode menu verify ";
 		// verify form mode menu
 		if (selenium.isTextPresent("Menus"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7877";
		 quart_testname   = "FormModeIconVerify";
		 quart_description= "Add press release - form mode icon verify ";
		// verify form mode icon	
		if (selenium.isElementPresent(("//img[contains(@src, '/type/icon/id/press-release')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7897";
 		 quart_testname   = "FormModeSaveButtonVerify";
 		 quart_description= "Add press release - form mode save button verify ";
 		// verify form mode save 
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Save_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		 quart_detailid   = "7878";
 		 quart_testname   = "FormModeDateVerify";
 		 quart_description= "Add Press Release - form mode date verify ";
 		// verify form mode date
 		if (selenium.isElementPresent(("//input[contains(@id, 'date')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7987";
		 quart_testname   = "FormModeTitleVerify";
		 quart_description= "Add press release - form mode title verify ";
		if (selenium.isTextPresent("Title"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
		
		quart_detailid   = "7995";
		 quart_testname   = "FormModeContactVerify";
		 quart_description= "Add press release - form mode contact verify ";
	 		if (selenium.isElementPresent(("//div[contains(@id, 'contact-Editor')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
 		quart_detailid   = "7786";
 		 quart_testname   = "FormModeURLVerify";
 		 quart_description= "Add press release - form mode url verify ";
 		// verify form mode url	
 		if  (selenium.isElementPresent(("//input[contains(@id, 'url-auto-true')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		if (selenium.isTextPresent("Use Title for URL"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 	
 		
 		quart_detailid   = "7898";
 		 quart_testname   = "FormModePlaceButtonVerify";
 		 quart_description= "Add press release - form mode in place button verify ";
 		// verify in form button
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-in-place_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 	// add press release in review and publish mode for manage content
 		selenium.open(baseurl);
 		// click manage menu then click for content
 		verifyContentElements();
 		browserSpecificPressRelease();
 		Thread.sleep(1000);
 		addPressRelease();
 		
 		addPressReleaseReviewMode();  
 	
 		addPressReleasePublishMode();  
 		
 		verifyContentElements();
 		browserSpecificPressRelease();
 		Thread.sleep(1000);
 		addPressReleaseWithCreateLink();
 		Thread.sleep(2000);
 		
 		verifyContentElements();
 		browserSpecificPressRelease();
 		Thread.sleep(1000);
 		addPressReleaseWithInsertImage();
 		Thread.sleep(2000);
 		
 		verifyContentElements();
 		browserSpecificPressRelease();
 		Thread.sleep(1000);
 		addPressReleaseWithWordPaste();
 		Thread.sleep(2000);
	
	}


    
    
	// Code to test the press release create link in the body form popup 

  	 private void addPressReleaseWithCreateLink() throws Exception {
	  	  
   		// Click on title
   		selenium.type("id=title", "Press Release Testing for Create Link");
   		
   		// Initialize new Date object		
   		Date date = new Date();
   		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
   		System.out.println(dateEntry.format(date));				
   		selenium.click("id=date");
   		selenium.type("id=date", dateEntry.format(date));
   		Thread.sleep(1000);
   		
   	    // click on body element
  		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
  		Thread.sleep(1000);

		selenium.click("css=#p4cms_content_Element_4 > span.value-node");
		Thread.sleep(1000);
  		
		// check all elements (bold,italic, etc...) on body	
  		String quart_detailid   = "10071";
		String quart_testname   = "PressReleaseBoldButton";
		String quart_description= "Add press release - bold button verify ";

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_ToggleButton_10') and contains(@title, 'Bold')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
/* 			
 		quart_detailid   = "10072";
		quart_testname   = "PressReleaseBoldButtonTooltip";
		quart_description= "verify press release bold tooltip";
		// get tooltip attribute
		String tooltip1 = selenium.getAttribute("//div[11]/div/div/div/div/span[7]/span/span/span/@title");

		boolean tooltipTrue1 = tooltip1.equals("Bold");
	
		if (tooltipTrue1) 
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
	
	*/
 		
 		 quart_detailid   = "10073";
		 quart_testname   = "PressReleaseItalicButton";
		 quart_description= "Add press release - italic button verify ";

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_ToggleButton_11') and contains(@title, 'Italic')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		 quart_detailid   = "10076";
		 quart_testname   = "PressReleaseUnderlineButton";
		 quart_description= "Add press release - underline button verify ";

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_ToggleButton_12') and contains(@title, 'Underline')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		 quart_detailid   = "10078";
		 quart_testname   = "PressReleaseStrikethroughButton";
		 quart_description= "Add press release - strike through button verify ";

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_ToggleButton_13') and contains(@title, 'Strikethrough')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
		
 		quart_detailid   = "10080";
		 quart_testname   = "PressReleaseAlignLeftButton";
		 quart_description= "Add press release - align left button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_22') and contains(@title, 'Align Left')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10082";
		 quart_testname   = "PressReleaseAlignCenterButton";
		 quart_description= "Add press release - align center button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_23') and contains(@title, 'Align Center')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "10084";
		 quart_testname   = "PressReleaseAlignRightButton";
		 quart_description= "Add press release - align right button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_24') and contains(@title, 'Align Right')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10086";
		 quart_testname   = "PressReleaseJustifyButton";
		 quart_description= "Add press release - justify button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_25') and contains(@title, 'Justify')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		

		quart_detailid   = "10088";
		 quart_testname   = "PressReleaseBulletList";
		 quart_description= "Add press release - bullet list button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_26') and contains(@title, 'Bullet List')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "10090";
		 quart_testname   = "PressReleaseNumberedList";
		 quart_description= "Add press release - numbered list button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_27') and contains(@title, 'Numbered List')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "10092";
		 quart_testname   = "PressReleaseIndent";
		 quart_description= "Add press release - indent button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_28') and contains(@title, 'Indent')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10094";
		 quart_testname   = "PressReleaseOutdent";
		 quart_description= "Add press release - outdent button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_29') and contains(@title, 'Outdent')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		quart_detailid   = "10096";
		 quart_testname   = "PressReleaseCreateLink";
		 quart_description= "Add press release - create link button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_30') and contains(@title, 'Create Link')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10098";
		 quart_testname   = "PressReleaseInsertImage";
		 quart_description= "Add press release - insert image button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_31') and contains(@title, 'Insert Image')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10100";
		 quart_testname   = "PressReleaseViewHTMLSource";
		 quart_description= "Add press release - view html source button verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_ToggleButton_14') and contains(@title, 'View HTML Source')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10062";
		 quart_testname   = "PressReleaseBodyText";
		 quart_description= "Add press release - body text verify ";

		if (selenium.isElementPresent(("//label[contains(@for, 'body')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10063";
		 quart_testname   = "PressReleaseBodyFormatSelection";
		 quart_description= "Add press release - body format selection verify ";

		if (selenium.isElementPresent(("//div[contains(@id, 'widget_dijit__editor_plugins__FormatBlockDropDown_2_select')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10064";
		 quart_testname   = "PressReleaseBodyFontSelection";
		 quart_description= "Add press release - body font selection verify ";

		if (selenium.isElementPresent(("//div[contains(@id, 'widget_dijit__editor_plugins__FontNameDropDown_2_select')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
			
		quart_detailid   = "10065";
		 quart_testname   = "PressReleaseBodySizeSelection";
		 quart_description= "Add press release - body size selection verify ";

		if (selenium.isElementPresent(("//div[contains(@id, 'widget_dijit__editor_plugins__FontSizeDropDown_2_select')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10066";
		 quart_testname   = "PressReleaseBodyColorSelection";
		 quart_description= "Add press release - body color selection verify ";

		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_DropDownButton_4')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		quart_detailid   = "10293";
		 quart_testname   = "PressReleaseBodyPasteSelection";
		 quart_description= "Add press release - body paste selection verify ";

		 if (selenium.isElementPresent("//span[contains(@id, 'dijit_form_DropDownButton_5') and contains(@tabindex, '-1')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
//		quart_detailid   = "10293";
//		quart_testname   = "PressReleaseWordPasteTooltip";
//		quart_description= "verify word paste tooltip";
//		// get tooltip attribute
//		String pasteSelection = selenium.getText("//div[11]/div/div/div/div/span[5]/span/span/@title");
//
//		boolean pasteSelectionTrue = pasteSelection.equals("Select Paste Mode");
//	
//		if (pasteSelectionTrue) 
//		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
		
		
		// Verify create link testcases
  		// Click on body to create link button using css
	  		selenium.click("css=span.dijitEditorIconCreateLink");
  		    Thread.sleep(2000);
  		
  		
		// click the url radio button
	 		 quart_detailid   = "10296";
			 quart_testname   = "PressReleaseCreateLinkURLButton";
			 quart_description= "Add press release - create link url button ";

		 		if (selenium.isElementPresent(("//input[contains(@type, 'radio') and contains(@name, 'contentSource')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 		
		 		
		 		
		 	selenium.click("xpath=(//input[@name='contentSource'])[2]");
			Thread.sleep(1000);
			
			 quart_detailid   = "10296";
			 quart_testname   = "PressReleaseCreateLinkURLButtonActionNotVisible";
			 quart_description= "Add press release - create link url button - action not visible ";

	 		if (selenium.isVisible(("//select[contains(@name, 'contentAction')]")))
			writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); 
	 		else  { writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); } 		
	 		
		
	 		quart_detailid   = "10296";
			 quart_testname   = "PressReleaseCreateLinkURLButtonContentTitleNotVisible";
			 quart_description= "Add press release - create link url button - content title not visible ";

		 		if (selenium.isElementPresent(("//select[contains(@name, 'useTitle')]")))
				writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); 
		 		else  { writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); } 		
		 		
		 		// click content radio button
				selenium.click("name=contentSource");
				Thread.sleep(1000);
  			
  		
  		 quart_detailid   = "10103";
		 quart_testname   = "PressReleaseCreateLinkText";
		 quart_description= "Add press release - create link verify ";

		if  (selenium.isTextPresent(("Create Link")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 quart_detailid   = "10106";
		 quart_testname   = "PressReleaseCreateLinkSourceText";
		 quart_description= "Add press release - create link source text verify ";

		if  (selenium.isTextPresent(("Link Source")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
		
		 quart_detailid   = "10114";
		 quart_testname   = "PressReleaseCreateLinkPropertiesText";
		 quart_description= "Add press release - create link properties text verify ";

		if  (selenium.isTextPresent(("Link Properties")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		
		 quart_detailid   = "10115";
		 quart_testname   = "PressReleaseCreateLinkDisplayedText";
		 quart_description= "Add press release - create link source displayed verify ";

		if  (selenium.isTextPresent(("Displayed Text")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		 quart_detailid   = "10119";
		 quart_testname   = "PressReleaseCreateLinkTargetText";
		 quart_description= "Add press release - create link target text verify ";

		if  (selenium.isTextPresent(("Open In")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 quart_detailid   = "10121";
		 quart_testname   = "PressReleaseCreateLinkCSSClassText";
		 quart_description= "Add press release - create link css class text verify ";

		if  (selenium.isTextPresent(("CSS Class")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 quart_detailid   = "10118";
		 quart_testname   = "PressReleaseCreateLinkContentTitleText";
		 quart_description= "Add press release - create link content title text verify ";

		if  (selenium.isTextPresent(("Use Content Title")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 quart_detailid   = "10108";
		 quart_testname   = "PressReleaseCreateLinkContentText";
		 quart_description= "Add press release - create link content text verify ";

		if  (selenium.isTextPresent(("Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		 quart_detailid   = "10112";
		 quart_testname   = "PressReleaseCreateLinkURLText";
		 quart_description= "Add press release - create link url text verify ";

		if  (selenium.isTextPresent(("URL")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
		 quart_detailid   = "10107";
		 quart_testname   = "PressReleaseCreateLinkContentRadioButton";
		 quart_description= "Add press release - create link content radio button ";

	 		if (selenium.isElementPresent(("//input[contains(@name, 'contentSource') and contains(@value, 'content')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
		
	 		 quart_detailid   = "10109";
			 quart_testname   = "PressReleaseCreateLinkContentForm";
			 quart_description= "Add press release - create link content form ";

		 		if (selenium.isElementPresent(("//input[contains(@name, 'contentTitle')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		 quart_detailid   = "10111";
		 quart_testname   = "PressReleaseCreateLinkURLRadioButton";
		 quart_description= "Add press release - create link url radio button ";

	 		if (selenium.isElementPresent(("//input[contains(@name, 'contentSource') and contains(@value, 'external')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		

	 		 quart_detailid   = "10113";
			 quart_testname   = "PressReleaseCreateLinkURLForm";
			 quart_description= "Add press release - create link url form ";

		 		if (selenium.isElementPresent(("//input[contains(@type, 'text') and contains(@name, 'url')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
				
	 		 quart_detailid   = "10122";
			 quart_testname   = "PressReleaseCreateLinkCSSClassForm";
			 quart_description= "Add press release - create link css class form ";

		 		if (selenium.isElementPresent(("//input[contains(@type, 'text') and contains(@name, 'cssClass')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
							
		 		 quart_detailid   = "10116";
				 quart_testname   = "PressReleaseCreateLinkDescForm";
				 quart_description= "Add press release - create link desc form ";

			 		if (selenium.isElementPresent(("//input[contains(@type, 'text') and contains(@name, 'description')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
									
		 		 quart_detailid   = "10123";
				 quart_testname   = "PressReleaseCreateLinkInsertButton";
				 quart_description= "Add press release - create link insert button";

			 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_31')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
			 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
			 		
			 		 quart_detailid   = "10124";
					 quart_testname   = "PressReleaseCreateLinkCancelButton";
					 quart_description= "Add press release - create link canccel button";

				 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_32')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
				 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
							
									
		 		 quart_detailid   = "10110";
				 quart_testname   = "PressReleaseCreateLinkBrowseButton";
				 quart_description= "Add press release - create link browse button ";

				 // click on browse button using css
	 			 selenium.click("css=.dijitDialogPaneContent .linkForm .sourceContainer .dijitButton .dijitButtonNode .dijitButtonContents");
				 Thread.sleep(4000);
				 
			 		if (selenium.isTextPresent("Select Content"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
			 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
			 	//close select content
					selenium.click("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[2]");
					selenium.click("id=p4cms_content_SelectDialog_0-button-select_label");
					Thread.sleep(1000);

									
		 		// check drop down selection
		 		selenium.select("name=targetSelect", "label=Current Window");
				 
				// place them into a string array
				String[] currentSelection = selenium.getSelectOptions("//select[contains(@name, 'targetSelect')]");
						
						// verify if the Current Status exists in the selection list 
				boolean selectedValue = ArrayUtils.contains(currentSelection, "Current Window");
					    
				quart_detailid   = "10120";  
				quart_testname   = "PressReleaseCreateLinkDropDownSelection";
				quart_description= "verify create link drop down selection";
				// verify that scheduled status is selected
					if (selectedValue)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
					// place them into a string array
					String[] openinValues = selenium.getSelectOptions("//select[contains(@name, 'targetSelect')]");
								
								// verify if the Current Status exists in the selection list 
					boolean hasValues  = ArrayUtils.contains(openinValues, "Current Window");
					boolean hasValues1 = ArrayUtils.contains(openinValues, "New Window");
					boolean hasValues2 = ArrayUtils.contains(openinValues, "Top Window");
					boolean hasValues3 = ArrayUtils.contains(openinValues, "Parent Window");
	
					quart_detailid   = "10120";
					quart_testname   = "PressReleaseCreateLinkDropDownSelection1";
					quart_description= "verify create link dropdown selection";
					if (hasValues)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "10120";
					quart_testname   = "PressReleaseCreateLinkDropDownSelection1";
					quart_description= "verify create link dropdown selection";
					if (hasValues1)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "10120";
					quart_testname   = "PressReleaseCreateLinkDropDownSelection1";
					quart_description= "verify create link dropdown selection";
					if (hasValues2)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "10120";
					quart_testname   = "PressReleaseCreateLinkDropDownSelection1";
					quart_description= "verify create link dropdown selection";
					if (hasValues3)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						
					
					// check drop down selection for View As options
			 		selenium.select("name=contentAction", "label=Go To Page");
											
						// place them into a string array
						String[] viewAsValues = selenium.getSelectOptions("//select[contains(@name, 'contentAction')]");
									
									// verify if the Current Status exists in the selection list 
						boolean contentValues  = ArrayUtils.contains(viewAsValues, "Go To Page");
						boolean contentValues1 = ArrayUtils.contains(viewAsValues, "View Image");
						boolean contentValues2 = ArrayUtils.contains(viewAsValues, "Download File");
		
						quart_detailid   = "10298";
						quart_testname   = "PressReleaseCreateLinkViewAsPageDropDown";
						quart_description= "verify create link go to page selection";
						if (contentValues)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "10299";
						quart_testname   = "PressReleaseCreateLinkViewAsImageDropDown";
						quart_description= "verify create link view as image selection";
						if (contentValues1)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "10230";
						quart_testname   = "PressReleaseCreateLinkDownloadDropDown";
						quart_description= "verify create link download file selection";
						if (contentValues2)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
						quart_detailid   = "10297";
						quart_testname   = "PressReleaseCreateLinkActionText";
						quart_description= "verify create link action text";
				 		if (selenium.isTextPresent("Action"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
					
				    quart_detailid   = "10105";
				 	quart_testname   = "PressReleaseCreateLinkTooltip";
					quart_description= "verify create link tooltip";
					// get tooltip attribute
						String tooltip = selenium.getAttribute("//div[42]/div/span[2]/span/@title");
					//selenium.click("css=span.dijitDialogCloseIcon.dijitDialogCloseIconHover");

					boolean tooltipTrue = tooltip.equals("Cancel");
				
					if (tooltipTrue) 
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
				 
		
		  if (browser.equalsIgnoreCase("*googlechrome"))
		     { selenium.click("//span[contains(@class, 'dijitDialogCloseIcon')]"); 
		       Thread.sleep(2000); }
  	    
		  else // cancel create link dialog  
 		    selenium.click("//div[@id='buttons-element']/fieldset/span[2]/input");
 
		  Thread.sleep(2000);
		
		 quart_detailid   = "10104";
		 quart_testname   = "PressReleaseCreateLinkCancelButton";
		 quart_description= "Add press release - create link cancel button ";

	 		if (selenium.isElementPresent("//a[contains(@class, 'home-page type-mvc')]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); } 		
	 		
		
  		// Click on body to enter info
  		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", "Create Link test"); 
  		Thread.sleep(1000);
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		selenium.click("//div[@class='container']");			
  		Thread.sleep(1000);
   		
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		selenium.click("//div[@class='container']");			
   		waitForElements("id=add-content-toolbar-button-Save_label");
   		// save
   		selenium.click("id=add-content-toolbar-button-Save_label");
   		selenium.click("id=save_label");			
   		Thread.sleep(3000);
       }
  	 
  	
  	 
  	 
  	 
  	 
  	 	//**** INSERT IMAGE CODE ****//
  	 
 	private void addPressReleaseWithInsertImage() throws Exception {

 	// Click on title
   		selenium.type("id=title", "Press Release Testing for Insert Image");
   		
   		// Initialize new Date object		
   		Date date = new Date();
   		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
   		System.out.println(dateEntry.format(date));				
   		selenium.click("id=date");
   		selenium.type("id=date", dateEntry.format(date));
   		Thread.sleep(1000);
   		
   	    // click on body element
  		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
  		Thread.sleep(1000);

		selenium.click("css=#p4cms_content_Element_4 > span.value-node");
		Thread.sleep(2000);
  		
  		// Click on body to enter image info
  		selenium.click("css=span.dijitEditorIconInsertImage");
  		Thread.sleep(1000);
  		
  		String quart_detailid   = "10125";
		String quart_testname   = "PressReleaseInsertImageText";
		String quart_description= "Add press release - insert image text verify ";

		if  (selenium.isTextPresent(("Insert Image")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		

		quart_detailid = "10128";
		quart_testname = "PressReleaseInsertImageText1";
		quart_description = "Add press release - insert image text verify";
		
		if (selenium.isTextPresent("Image Source"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	
 	
		quart_detailid = "10137";
		quart_testname = "PressReleaseInsertImageText2";
		quart_description = "Add press release - insert image text verify";
		
		if (selenium.isTextPresent("Image Formatting"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	
 	 
		quart_detailid = "10130";
		quart_testname = "PressReleaseInsertImageContentText";
		quart_description = "Add press release - insert image content text verify";
		
		if (selenium.isTextPresent("Content"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	
 	
		quart_detailid = "10135";
		quart_testname = "PressReleaseInsertImageContentText";
		quart_description = "Add press release - insert image content text verify";
		
		if (selenium.isTextPresent("URL"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	
		
		quart_detailid = "10130";
		quart_testname = "PressReleaseInsertImageContentText";
		quart_description = "Add press release - insert image content text verify";
		
		if (selenium.isTextPresent("Content"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }

		quart_detailid = "10129";
		quart_testname = "PressReleaseInsertImageContentButton";
		quart_description = "Add press release - insert image content radio button verify";
		
 		if (selenium.isElementPresent(("//input[contains(@name, 'contentSource') and contains(@value, 'content')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	
 		quart_detailid = "10134";
		quart_testname = "PressReleaseInsertImageURLButton";
		quart_description = "Add press release - insert image url radio button verify";
		
 		if (selenium.isElementPresent(("//input[contains(@name, 'contentSource') and contains(@value, 'external')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	
 		quart_detailid = "10131";
		quart_testname = "PressReleaseInsertImageContentForm";
		quart_description = "Add press release - insert image content form verify";
		
 		if (selenium.isElementPresent(("//input[contains(@name, 'contentTitle') and contains(@value, 'No content selected.')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid = "10136";
		quart_testname = "PressReleaseInsertImageURLForm";
		quart_description = "Add press release - insert image url form verify";
		
 		if (selenium.isElementPresent(("//input[contains(@name, 'url') and contains(@type, 'text')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
 		quart_detailid = "10138";
		quart_testname = "PressReleaseInsertImageAltText";
		quart_description = "Add press release - insert image alt text verify";
		
 		if (selenium.isTextPresent(("Alt Text")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		
 		quart_detailid = "10140";
		quart_testname = "PressReleaseInsertImageSizeText";
		quart_description = "Add press release - insert image size text verify";
		
 		if (selenium.isTextPresent(("Size")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid = "10142";
 		quart_testname = "PressReleaseInsertImageWidthText";
 		quart_description = "Add press release - insert image width text verify";
 		
 		if (selenium.isTextPresent(("Width")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid = "10145";
 		quart_testname = "PressReleaseInsertImageHeightText";
 		quart_description = "Add press release - insert image height text verify";
 		
 		if (selenium.isTextPresent(("Height")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		quart_detailid = "10150";
 		quart_testname = "PressReleaseInsertImageMarginText";
 		quart_description = "Add press release - insert image margin text verify";
 		
 		if (selenium.isTextPresent(("Margin")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid = "10153";
 		quart_testname = "PressReleaseInsertImageCSSClassText";
 		quart_description = "Add press release - insert image margin text verify";
 		
 		if (selenium.isTextPresent(("CSS Class")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid = "10155";
 		quart_testname = "PressReleaseInsertImageAlignmentText";
 		quart_description = "Add press release - insert image alignment text verify";
 		
 		if (selenium.isTextPresent(("Alignment")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid = "10139";
 		quart_testname = "PressReleaseInsertImageAltTextForm";
 		quart_description = "Add press release - insert image alt text form verify";
 		
 		if (selenium.isElementPresent(("//input[contains(@name, 'altText') and contains(@type, 'text')]")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		
 		quart_detailid = "10143";
 		quart_testname = "PressReleaseInsertImageWidthForm";
 		quart_description = "Add press release - insert image width form verify";
 		
 		if (selenium.isElementPresent(("//input[contains(@name, 'width') and contains(@type, 'text')]")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid = "10146";
 		quart_testname = "PressReleaseInsertImageHeightForm";
 		quart_description = "Add press release - insert image width form verify";
 		
 		if (selenium.isElementPresent(("//input[contains(@name, 'height') and contains(@type, 'text')]")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid = "10151";
 		quart_testname = "PressReleaseInsertImageMarginForm";
 		quart_description = "Add press release - insert image margin form verify";
 		
 		if (selenium.isElementPresent(("//input[contains(@name, 'margin') and contains(@type, 'text')]")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid = "10154";
 		quart_testname = "PressReleaseInsertImageCSSClassForm";
 		quart_description = "Add press release - insert image margin form verify";
 		
 		if (selenium.isElementPresent(("//input[contains(@name, 'cssClass') and contains(@type, 'text')]")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
 		quart_detailid = "10141";
 		quart_testname = "PressReleaseInsertImageSizeSelector";
 		quart_description = "Add press release - insert image size selector verify";
 		
 		if (selenium.isElementPresent(("//input[contains(@name, 'cssClass') and contains(@type, 'text')]")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 				
/* 		
 		quart_detailid   = "10127";
		quart_testname   = "PressReleaseInsertImageTooltip";
		quart_description= "verify insert image tooltip";
		// get tooltip attribute
		String tooltip1 = selenium.getAttribute("//div[39]/div/span[2]/span/@title");
		//selenium.click("css=span.dijitDialogCloseIcon.dijitDialogCloseIconHover");

		boolean tooltip1True = tooltip1.equals("Cancel");
	
		if (tooltip1True) 
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description );  
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
*/ 		
		
 		
 	// place them into a string array
		String[] currentSelection = selenium.getSelectOptions("//select[contains(@name, 'sizeType')]");
				
				// verify if the Current Status exists in the selection list 
		boolean selectedValue = ArrayUtils.contains(currentSelection, "Full Size");
			    
		quart_detailid   = "10141";  
		quart_testname   = "PressReleaseInsertImageSizeSelector";
		quart_description= "verify insert image size selection";
		// verify that scheduled status is selected
			if (selectedValue)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
			// place them into a string array
			String[] imageSizeValues = selenium.getSelectOptions("//select[contains(@name, 'sizeType')]");
						
		    // verify if the Current Status exists in the selection list 
			boolean sizeValues  = ArrayUtils.contains(imageSizeValues, "Full Size");
			boolean sizeValues1 = ArrayUtils.contains(imageSizeValues, "Custom Size");
			

			quart_detailid   = "10141";
			quart_testname   = "PressReleaseInsertImageSizeSelector1";
			quart_description= "verify insert image size selection";
			if (sizeValues)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			quart_detailid   = "10141";
			quart_testname   = "PressReleaseInsertImageSizeSelector2";
			quart_description= "verify insert image size selection";
			if (sizeValues1)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
 		
			quart_detailid   = "10144";
			quart_testname   = "PressReleaseInsertImagePixelsText";
			quart_description= "verify insert image pixels text";
			if (selenium.isTextPresent("pixels"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			
			
			quart_detailid   = "10147";
			quart_testname   = "PressReleaseInsertImagePixelsText1";
			quart_description= "verify insert image pixels text";
			if (selenium.isTextPresent("pixels"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			
			quart_detailid   = "10149";
			quart_testname   = "PressReleaseInsertImageScaleText";
			quart_description= "verify insert image scale prop. text";
			if (selenium.isTextPresent("Scale Proportionally"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			
			
			quart_detailid   = "10152";
			quart_testname   = "PressReleaseInsertImagePixelsText2";
			quart_description= "verify insert image pixels text";
			if (selenium.isTextPresent("pixels"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
 		
			quart_detailid = "10156";
	 		quart_testname = "PressReleaseInsertImageNoneAlignmentIcon";
	 		quart_description = "Add press release - insert image none alignment icon verify";
	 		
	 		if (selenium.isElementPresent(("//img[contains(@src, '/application/content/resources/images/icon-align-none.png')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		quart_detailid = "10159";
	 		quart_testname = "PressReleaseInsertImageLeftAlignmentIcon";
	 		quart_description = "Add press release - insert image none alignment icon verify";
	 		
	 		if (selenium.isElementPresent(("//img[contains(@src, '/application/content/resources/images/icon-align-left.png')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		
	 		quart_detailid = "10162";
	 		quart_testname = "PressReleaseInsertImageCenterAlignmentIcon";
	 		quart_description = "Add press release - insert image center alignment icon verify";
	 		
	 		if (selenium.isElementPresent(("//img[contains(@src, '/application/content/resources/images/icon-align-center.png')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		
	 		quart_detailid = "10165";
	 		quart_testname = "PressReleaseInsertImageRightAlignmentIcon";
	 		quart_description = "Add press release - insert image right alignment icon verify";
	 		
	 		if (selenium.isElementPresent(("//img[contains(@src, '/application/content/resources/images/icon-align-right.png')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		quart_detailid = "10158";
	 		quart_testname = "PressReleaseInsertImageNoneAlignmentText";
	 		quart_description = "Add press release - insert image none alignment text verify";
	 		
	 		if (selenium.isTextPresent(("None")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		quart_detailid = "10161";
	 		quart_testname = "PressReleaseInsertImageLeftAlignmentText";
	 		quart_description = "Add press release - insert image left alignment text verify";
	 		
	 		if (selenium.isTextPresent(("Left")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 			
		
	 		quart_detailid = "10164";
	 		quart_testname = "PressReleaseInsertImageCenterAlignmentText";
	 		quart_description = "Add press release - insert image center alignment text verify";
	 		
	 		if (selenium.isTextPresent(("Center")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		
	 		quart_detailid = "10167";
	 		quart_testname = "PressReleaseInsertImageRightAlignmentText";
	 		quart_description = "Add press release - insert image right alignment text verify";
	 		
	 		if (selenium.isTextPresent(("Right")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		
	 		quart_detailid = "10157";
	 		quart_testname = "PressReleaseInsertImageNoneRadioButton";
	 		quart_description = "Add press release - insert image none radio button verify";
	 		
	 		if (selenium.isElementPresent(("//input[contains(@value, 'none') and contains(@type, 'radio')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 				
		 						 		
	 		quart_detailid = "10160";
	 		quart_testname = "PressReleaseInsertImageLeftRadioButton";
	 		quart_description = "Add press release - insert image left radio button verify";
	 		
	 		if (selenium.isElementPresent(("//input[contains(@value, 'left') and contains(@type, 'radio')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 				
		 									 		
							 		 		
	 		quart_detailid = "10163";
	 		quart_testname = "PressReleaseInsertImageCenterRadioButton";
	 		quart_description = "Add press release - insert image center radio button verify";
	 		
	 		if (selenium.isElementPresent(("//input[contains(@value, 'center') and contains(@type, 'radio')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 				
		 									 			 		
	 		quart_detailid = "10166";
	 		quart_testname = "PressReleaseInsertImageRightRadioButton";
	 		quart_description = "Add press release - insert image right radio button verify";
	 		
	 		if (selenium.isElementPresent(("//input[contains(@value, 'right') and contains(@type, 'radio')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 		
	 		
	 		quart_detailid = "10168";
	 		quart_testname = "PressReleaseInsertImageInsertButton";
	 		quart_description = "Add press release - insert image insert button verify";
	 		
	 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_34_label')]")))
	 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	 				
	 		
	 		
	 		
	 		// click browse button
	 		 quart_detailid   = "10132";
			 quart_testname   = "PressReleaseInsertImageBrowseButton";
			 quart_description= "Add press release - insert image browse button ";

			 // click on browse button
			 //selenium.click("//form[@id='p4cms_content_Editor_0_0linkForm']/dl/dd/fieldset/dl/dd/span/input");
			//selenium.click("id=dijit_form_Button_30_label");
			selenium.click("css=.dijitDialogPaneContent .scrollNode .imageForm .zend_form_dojo .sourceContainer .dijitButton .dijitButtonNode .dijitButtonContents");
			 //selenium.clickAt("//div[13]/div[2]/div/form/dl/dd/fieldset/dl/dd/span/span/span/span[3]","");
			 Thread.sleep(2000);
			 
		 		if (selenium.isTextPresent("Select Content"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
		 	//close select content
				//selenium.click("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[2]");
				selenium.click("id=p4cms_content_SelectDialog_0-button-cancel_label");
				Thread.sleep(1000);
				
			// click new image button 
				
				// click browse button
		 		 quart_detailid   = "10133";
				 quart_testname   = "PressReleaseInsertImageNewImageButton";
				 quart_description= "Add press release - new image button ";

				 // click on browse button
				 //selenium.click("//form[@id='p4cms_content_Editor_0_0linkForm']/dl/dd/fieldset/dl/dd/span/input");
				selenium.click("id=dijit_form_Button_33_label");
				 //selenium.clickAt("//div[13]/div[2]/div/form/dl/dd/fieldset/dl/dd/span/span/span/span[3]","");
				 Thread.sleep(2000);
				 
			 		if (selenium.isTextPresent("New Image"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
			 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
						
/*			 		
		 		quart_detailid   = "10172";
				quart_testname   = "PressReleaseNewImageTooltip";
				quart_description= "verify new image tooltip";
				// get tooltip attribute
				String tooltip2 = selenium.getAttribute("//div[41]/div/span[2]/@title");
				//selenium.click("css=span.dijitDialogCloseIcon.dijitDialogCloseIconHover");

				boolean tooltipTrue2 = tooltip2.equals("Cancel");
			
				if (tooltipTrue2) 
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
	*/				 		
		 		 quart_detailid   = "10170";
				 quart_testname   = "PressReleaseInsertImageNewImageText";
				 quart_description= "Add press release - new image text ";
				 
			 		if (selenium.isTextPresent("New Image"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
		 		
	 		
		 		 quart_detailid   = "10173";
				 quart_testname   = "PressReleaseInsertImageNewImageTitleText";
				 quart_description= "Add press release - new image title text ";
				 
			 		if (selenium.isTextPresent("Title"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
			 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
			 		
			 		 quart_detailid   = "10175";
					 quart_testname   = "PressReleaseInsertImageNewImageFileText";
					 quart_description= "Add press release - new image file text ";
					 
				 		if (selenium.isTextPresent("File"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
				 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
				 		
		 		
		 		 quart_detailid   = "10179";
				 quart_testname   = "PressReleaseInsertImageNewImageDateText";
				 quart_description= "Add press release - new image date text ";
				 
		 		if (selenium.isTextPresent("Date"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
		 		
		 		 quart_detailid   = "10181";
				 quart_testname   = "PressReleaseInsertImageNewImageCreatedByText";
				 quart_description= "Add press release - new image created by text ";
				 
			 		if (selenium.isTextPresent("Created By"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
			 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
			 		
			 		
	 		 quart_detailid   = "10183";
			 quart_testname   = "PressReleaseInsertImageNewImageDescText";
			 quart_description= "Add press release - new image desc text ";
			 
		 		if (selenium.isTextPresent("Description"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
		 		
		 		
		 		 quart_detailid   = "10185";
				 quart_testname   = "PressReleaseInsertImageNewImageAltText";
				 quart_description= "Add press release - new image title text ";
				 
			 		if (selenium.isTextPresent("Alternate Text"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
			 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }		
			 		

		 				
			 									 			 		
		 		 quart_detailid   = "10169";
		 		 quart_testname   = "PressReleaseInsertImageCancelButton";
	 		 	quart_description= "Add press release - insert image cancel button verify";	
		 		// verify place mode cancel 
		 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_33_label')]")))
		 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 		
		 		 quart_detailid   = "10168";
		 		 quart_testname   = "PressReleaseInsertImageInsertButton";
		 		 quart_description= "Add press release - insert image insert button verify";	
		 		// verify place mode cancel 
		 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_32_label')]")))
		 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
	  
			 		
		 		quart_detailid = "10174";
		 		quart_testname = "PressReleaseInsertImageTitleForm";
		 		quart_description = "Add press release - insert image title form verify";
		 		
				//if (selenium.isElementPresent("//input[@id='3-title']"))
		 		if (selenium.isElementPresent(("//div[contains(@id, 'content-form-title')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 					
			 					 		
		 		quart_detailid = "10182";
		 		quart_testname = "PressReleaseInsertImageCreatedByForm";
		 		quart_description = "Add press release - insert image created by form verify";
		 		
		 		if (selenium.isElementPresent(("//div[contains(@id, 'content-form-creator')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 						
			 		
		 		quart_detailid = "10184";
		 		quart_testname = "PressReleaseInsertImageDescForm";
		 		quart_description = "Add press release - insert image desc form verify";
		 		
		 		if (selenium.isElementPresent(("//div[contains(@id, 'content-form-description')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 						
		 		quart_detailid = "10186";
		 		quart_testname = "PressReleaseInsertImageAltForm";
		 		quart_description = "Add press release - insert image alt form verify";
		 		
		 		if (selenium.isElementPresent(("//div[contains(@id, 'content-form-alt')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 							
		 		quart_detailid = "10176";
		 		quart_testname = "PressReleaseInsertImageFileForm";
		 		quart_description = "Add press release - insert image file form verify";
		 		
		 		if (selenium.isElementPresent(("//input[contains(@id, '3-file')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 							
		 		quart_detailid = "10180";
		 		quart_testname = "PressReleaseInsertImageDateForm";
		 		quart_description = "Add press release - insert image date form verify";
		 		
		 		if (selenium.isElementPresent(("//input[contains(@id, '3-date')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 					
		 		quart_detailid = "10187";
		 		quart_testname = "PressReleaseInsertImageSaveButton";
		 		quart_description = "Add press release - insert image save button verify";
		 		
		 		if (selenium.isElementPresent(("//dd[contains(@id, 'save-element')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 				
		 		quart_detailid = "10188";
		 		quart_testname = "PressReleaseInsertImageCancelButton";
		 		quart_description = "Add press release - insert image save button verify";
		 		
		 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_35')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 				
	 		
		 		quart_detailid = "10178";
		 		quart_testname = "PressReleaseInsertImageClearButton";
		 		quart_description = "Add press release - insert image clear button verify";
		 		
		 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_34')]")))
		 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		 		else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		 				
		 		
		 	    // click to upload a new image using Firefox
		 		selenium.click("id=dijit_form_Button_31_label");
		 		Thread.sleep(2000);
		 		selenium.type("name=title", "New Image");
		 		Thread.sleep(2000);
		 		//selenium.clickAt("name=file",""); 
		 		// click on the file to upload an image
		 		//selenium.type("css=.dijitDialog .dijitDialogPaneContent .scrollNode .content-form .zend_form_dojo .display-group .content-form-elements .content-form-element[id]", "Test_image");
		 		//Thread.sleep(2000);
		 		//selenium.attachFile("class=file-upload-input", "http://cdn3.worldcarfans.co/2005/12/medium/7051223.002.1M.jpg");
		 		//selenium.attachFile("css=.dijitDialog .dijitDialogPaneContent .scrollNode .content-form .zend_form_dojo .display-group .content-form-elements .content-form-element .file-upload .file-upload-input", "http://cdn3.worldcarfans.co/2005/12/medium/7051223.002.1M.jpg");
		 		Thread.sleep(2000);
		 		
		 		
		 		
		 		 

		// Click on body to enter info
  		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", "Create Link test"); 
  		Thread.sleep(2000);
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		selenium.click("//div[@class='container']");			
  		Thread.sleep(1000);
   		
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		selenium.click("//div[@class='container']");			
   		waitForElements("id=add-content-toolbar-button-Save_label");

   		selenium.click("id=add-content-toolbar-button-Save_label");
   		selenium.click("id=save_label");			
   		Thread.sleep(2000);
}  		
   		
 	
   		
   		
   		// **** WORD PASTE **** //
  	 
 	private void addPressReleaseWithWordPaste() throws Exception {

 		// Click on title
   		selenium.type("id=title", "Press Release Testing for Word Paste");
   		
   		// Initialize new Date object		
   		Date date = new Date();
   		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
   		System.out.println(dateEntry.format(date));				
   		selenium.click("id=date");
   		selenium.type("id=date", dateEntry.format(date));
   		Thread.sleep(1000);
   		
   	    // click on body element
  		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
  		Thread.sleep(2000);

		selenium.click("css=#p4cms_content_Element_4 > span.value-node");
		Thread.sleep(2000);
  		
  		// Click on body to enter image info
		// Click on body to enter info
 		//selenium.clickAt("//span[@id='dijit_form_Button_5']/span","");
 		selenium.click("css=span.editorIconPaste");

		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", CMSConstants.WORD_PASTE1);
  		selenium.type("id=dijitEditorBody", CMSConstants.WORD_PASTE2);
		Thread.sleep(1000);
  		

  		String quart_detailid   = "10291";
		String quart_testname   = "PressReleaseWordPasteButton";
		String quart_description= "Add press release - word paste button verify ";

 		if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_DropDownButton_5') and contains(@title, 'Select Paste Mode')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 /*		
 		quart_detailid   = "10292";
		quart_testname   = "PressReleaseWordPasteTooltip";
		quart_description= "verify word paste tooltip";
		// get tooltip attribute
		String tooltip3 = selenium.getAttribute("//div[11]/div/div/div/div/span[5]/span/span/span/@title");

		boolean tooltip3True = tooltip3.equals("Select Paste Mode");
	
		if (tooltip3True) 
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
 	
 	*/ 

		// Click on body to enter info
  		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", CMSConstants.WORD_PASTE1); 
  		selenium.type("id=dijitEditorBody", CMSConstants.WORD_PASTE2);
  		Thread.sleep(2000);
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		selenium.click("//div[@class='container']");			
  		Thread.sleep(1000);
   		
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		selenium.click("//div[@class='container']");			
   		waitForElements("id=add-content-toolbar-button-Save_label");

   		selenium.click("id=add-content-toolbar-button-Save_label");
   		selenium.click("id=save_label");			
   		Thread.sleep(2000);
   		
   		backToHome();
	
 	} 
}

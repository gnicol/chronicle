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

public class SaveTransitionForPressReleaseAllTransitionsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "SaveTransitionForPressReleaseAllTransitionsVerify";

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
		SaveTransitionForPressReleaseAllTransitionsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");

	}
	
	public void SaveTransitionForPressReleaseAllTransitionsVerify() throws InterruptedException, Exception {
		  
		// Verify title & close icon & content type
		verifyContentElements();
		
		Thread.sleep(2000);
		// verify tooltip
		selenium.mouseOver("//span[contains(@class,'dijitDialogCloseIcon')]"); 
	 
		// Press release
		browserSpecificPressRelease();

		Thread.sleep(2000);
		
		// click form mode and verify all elements
		selenium.click("id=add-content-toolbar-button-form_label");
		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
		Thread.sleep(2000);
			
		// Click title and enter info
		selenium.click("id=p4cms_content_Element_0");
		selenium.click("id=p4cms_content_Element_0");
		selenium.type("id=title", "Edit Press Release for save transition");
		// Click body and enter info
		Thread.sleep(1000);
		selenium.click("css=#p4cms_content_Element_1 > span.value-node");
			
		// Initialize new Date object		
		Date date = new Date();
		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
		System.out.println(dateEntry.format(date));				
		selenium.click("id=date");
		selenium.type("id=date", dateEntry.format(date));
		Thread.sleep(2000);
		// click on body element
		 selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
		 Thread.sleep(2000);
		
		// enter location 
		selenium.click("id=location");
	
		selenium.type("id=location", "Testing");
		Thread.sleep(1000);
		
		// Click on body to enter info
 		selenium.clickAt("//span[@id='dijit_form_Button_5']/span","");
 		Thread.sleep(2000);
 		// Click on body to enter info
 		selenium.click("id=body-Editor");
 		selenium.type("id=dijitEditorBody", "Press Release testing for save transition");
 		Thread.sleep(2000);
 		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
 		selenium.click("//div[@class='container']");					
		
		
		// enter contact details
		selenium.click("id=contact");
		selenium.type("id=contact", "Testing");
		Thread.sleep(2000);
		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
		selenium.click("//div[@class='container']");			
		Thread.sleep(2000);
		
		// save drop down
		selenium.click("id=add-content-toolbar-button-Save_label");		

  		Thread.sleep(2000);

		String  quart_detailid   = "8287";
		String  quart_testname   = "DefaultRadioButton";
		String  quart_description= "verify draft default radio button";
		
		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
            else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		  quart_detailid   = "8279";
		  quart_testname   = "StatusText";
		  quart_description= "verify status text";
		
		if (selenium.isTextPresent("Status"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
            else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		  quart_detailid   = "8285";
		  quart_testname   = "VersionHistory";
		  quart_description= "verify shown in version history text";
		
		if (selenium.isTextPresent(("Shown in the version history")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "8283";
		  quart_testname   = "CommentField";
		  quart_description= "form mode - verify comment input field";
		
		  if (selenium.isElementPresent(("//textarea[contains(@id, 'comment')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		  quart_detailid   = "8281";
		  quart_testname   = "CommentText";
		  quart_description= "form mode - verify comment text";
		
		  if (selenium.isTextPresent(("Comment")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		
		
		// save out
			selenium.click("id=save_label");	
			Thread.sleep(3000);
			
			
			// go back into edit mode
			// click edit
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			Thread.sleep(2000);
			
			
			// click save drop down
			selenium.click("id=edit-content-toolbar-button-Save_label");		

			Thread.sleep(2000);
			
			
			
			 quart_detailid   = "8295";
			  quart_testname   = "EditCheckDraftOption";
			  quart_description= "form mode - edit then check draft option";
			
			  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			  quart_detailid   = "8287";
			  quart_testname   = "EditAndCheckDraftOption1";
			  quart_description= "form mode - edit then check draft option";

		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description );  
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// click on the promote radio button
		selenium.click("id=workflow-state-review");
		
		// save out
		selenium.click("id=save_label");	
		Thread.sleep(3000);
		
		
		// go back into edit mode
		// click edit
		selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
		Thread.sleep(2000);
		
		
		// click save drop down
		selenium.click("id=edit-content-toolbar-button-Save_label");		

		Thread.sleep(2000);

		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"));
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
		
		
		
		 quart_detailid   = "8289";
		  quart_testname   = "ReviewAndPromoteScheduleStatus";
		  quart_description= "form mode - verify review checked and promote and schedule status";

		  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"))
			  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		  
		  quart_detailid   = "8289";
		  quart_testname   = "ScheduleStatus";
		  quart_description= "form mode - verify schedule status";

		  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
			  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// save out
		  selenium.click("id=save_label");	
		Thread.sleep(3000);
		
		// go back into edit mode
		// click edit
		selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
		Thread.sleep(2000);
					
		
		// click save drop down
		selenium.click("id=edit-content-toolbar-button-Save_label");		

		Thread.sleep(2000);
		
		
		// click publish
		selenium.click("id=workflow-state-published");		
		Thread.sleep(2000);
		
		
		// save out
		selenium.click("id=save_label");	
		Thread.sleep(3000);
		
		// go back into edit mode
		// click edit
		selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
		Thread.sleep(2000);
		
		// click save drop down
		selenium.click("id=edit-content-toolbar-button-Save_label");		

		Thread.sleep(2000);
		
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
		
		// verify published is selected
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"));
		
		
		
		 quart_detailid   = "8303";
		  quart_testname   = "PublishMode";
		  quart_description= "form mode - verify publish mode";

		  if(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"))
			  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		  
		  quart_detailid   = "8291";
		  quart_testname   = "PublishModeAndScheduleStatusChange";
		  quart_description= "form mode - verify publish mode and schedule status change";

			if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"))
			  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
			
			quart_detailid   = "8291";
			  quart_testname   = "ScheduleStatusChange";
			  quart_description= "form mode - verify schedule status change";

			  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
		
		quart_detailid   = "8297";
		  quart_testname   = "EditPublishAndScheduling";
		  quart_description= "form mode - verify edit publish and scheduling";

			if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') and contains(@checked, 'checked') ]"))
			  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			quart_detailid   = "8297";
			  quart_testname   = "PublishModeSchedule";
			  quart_description= "form mode - verify publish mode schedule";
			
		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		

		// save out
		selenium.click("id=save_label");	
		Thread.sleep(3000);
		
		
		// go back into edit mode
		// click edit
		selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
		Thread.sleep(2000);
					
		
		// click save
		selenium.click("id=edit-content-toolbar-button-Save_label");		

		Thread.sleep(2000);
		
		// verify page is in published mode
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"));
		
		
		// click demote to review
		selenium.click("id=workflow-state-review");
		Thread.sleep(2000);
		
		// save 
		selenium.click("id=save_label");	
		Thread.sleep(3000);
		
		
		// go back into edit mode
		// click edit
		selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
		Thread.sleep(2000);
					
		
		// click save drop down
		selenium.click("id=edit-content-toolbar-button-Save_label");		

		Thread.sleep(2000);
		
		
		// verify demote to review
		
		quart_detailid   = "8301";
		  quart_testname   = "DemoteToReview";
		  quart_description= "form mode - verify demote to review";
		
			if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"))
		 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		
			quart_detailid   = "8301";
			  quart_testname   = "DemoteToReviewAndSchedule";
			  quart_description= "form mode - verify demote to review schedule";
			
			  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));

		
		// verify page in review mode
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"));
		
		// select draft mode and verify elements 
		selenium.click("id=workflow-state-draft");
		Thread.sleep(2000);
		
		// verify date shown in draft mode
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
		
		
		// save 
		selenium.click("id=save_label");	
		Thread.sleep(3000);
		
		
		// click edit 
		selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
		Thread.sleep(2000);
					
		// click save drop down
		selenium.click("id=edit-content-toolbar-button-Save_label");		

		Thread.sleep(2000);
		
		// verify press release in draft mode
		quart_detailid   = "8299";
		  quart_testname   = "DemoteToDraft";
		  quart_description= "form mode - verify demote to draft";
		
			if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
		 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
			
			quart_detailid   = "8299";
			  quart_testname   = "DemoteToDraftAndSchedule";
			  quart_description= "form mode - verify demote to draft schedule";
			  
			  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		
		
		quart_detailid   = "8293";
		  quart_testname   = "DemoteToDraftOption";
		  quart_description= "form mode - verify demote to draft option";
		
		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		quart_detailid   = "8293";
		  quart_testname   = "DemoteToDraftAndScheduleStatus";
		  quart_description= "form mode - verify demote to draft schedule status";
		
		  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "8699";
		  quart_testname   = "AllTransitionsOption";
		  quart_description= "form mode - verify all transitions option";
		
		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		// save 
		selenium.click("id=save_label");	
		Thread.sleep(2000);
		
		// back Home
		selenium.open(baseurl);
	}
}
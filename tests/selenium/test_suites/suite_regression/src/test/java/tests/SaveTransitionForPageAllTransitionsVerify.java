package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;


// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class SaveTransitionForPageAllTransitionsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "SaveTransitionForPageAllTransitionsVerify";

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
		SaveTransitionForPageAllTransitionsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");

	}
	
	public void SaveTransitionForPageAllTransitionsVerify() throws InterruptedException, Exception {
		
		
		// Verify title & close icon & content type
		verifyContentElements();
		
		// Basic page
		Thread.sleep(1000);
		// click on Pages in left tab
		selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
		Thread.sleep(1000);
		
		browserSpecificBasicPage();
		
		Thread.sleep(1000);		
		// click form mode and verify all elements
		selenium.click("id=add-content-toolbar-button-form_label");
		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
			
			Thread.sleep(1000);
			// Click title and enter info
			selenium.click("id=p4cms_content_Element_0");
			selenium.click("id=p4cms_content_Element_0");
			selenium.type("id=title", "Edit page for save transition");
			// Click body and enter info
			Thread.sleep(1000);
			selenium.click("css=#p4cms_content_Element_0 > span.value-node");
			selenium.type("id=dijitEditorBody", "Page testing for save transition");
			Thread.sleep(1000);
			selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
			selenium.click("//div[@class='container']");			
	 		// Save page form
	 		selenium.click("id=add-content-toolbar-button-Save_label");			
	
	 		Thread.sleep(2000);
			
			String  quart_detailid   = "7290";
			String  quart_testname   = "StatusVerify";
			String  quart_description= "verify status text";
			
			if (selenium.isTextPresent(("Status")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	            else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			  quart_detailid   = "8153";
			  quart_testname   = "VersionHistory";
			  quart_description= "verify shown in version history text";
			
			if (selenium.isTextPresent(("Shown in the version history")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			quart_detailid   = "7302";
			  quart_testname   = "Comments";
			  quart_description= "form mode - verify comment input field";
			
			  if (selenium.isElementPresent(("//textarea[contains(@id, 'comment')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			  quart_detailid   = "7307";
			  quart_testname   = "CommentText";
			  quart_description= "form mode - verify comment text";
			
			  if (selenium.isTextPresent(("Comment")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			

			  quart_detailid   = "7291";
			  quart_testname   = "VerifyDraftOption";
			  quart_description= "form mode - verify draft option";
		
			// verify draft option
				if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
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
			
			// verify draft selected
			
			quart_detailid   = "7297";
			  quart_testname   = "EditDraftOption";
			  quart_description= "form mode - edit then check draft option";
			
			if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			// verify schedule date not shown
			assertFalse(selenium.isVisible("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
			assertFalse(selenium.isVisible("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
			
			
			// save out
			selenium.click("id=save_label");
			Thread.sleep(3000);
			
			// go back into edit mode
			// click edit
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			Thread.sleep(2000);
			
			
			// click save drop down
			//selenium.click("//div[@id='edit-content-toolbar']/span[3]/input");
			selenium.click("id=edit-content-toolbar-button-Save_label");		

			Thread.sleep(2000);
			
			
			// click promote to review
			selenium.click("id=workflow-state-review");		
			Thread.sleep(2000);
			
			// verify schedule changes
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"));
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
		
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
		
			// verify review still selected 
			
			quart_detailid   = "7296";
			  quart_testname   = "PromoteAndSchedule";
			  quart_description= "form mode - verify review checked and promote and schedule status";

			  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			  
			  quart_detailid   = "7296";
			  quart_testname   = "PromoteAndScheduleStatus";
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
						
			
			// click save
			selenium.click("id=edit-content-toolbar-button-Save_label");		

			Thread.sleep(2000);
			
			
			// click publish
			selenium.click("id=workflow-state-published");		
			Thread.sleep(2000);
			
			  quart_detailid   = "7299";
				quart_testname   = "DemoteToReviewScheduleVerify";
				quart_description= "form mode - verify schedule status change";

				  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			  
			
			
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
			
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
			
			// verify published is selected
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"));

				//writeFile1("\nskipped:7294\n", "", "");
				 
				quart_detailid   = "7294";
				  quart_testname   = "PublishOptionVerify";
				  quart_description= "form mode - verify publish mode";

				  if(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"))
					  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				  
				  quart_detailid   = "7295";
				  quart_testname   = "PublishAndScheduleVerify";
				  quart_description= "form mode - verify publish mode and schedule status change";

				  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"))
					  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
			
				quart_detailid   = "7295";
				quart_testname   = "PublishAndScheduleStatusChange";
				quart_description= "form mode - verify schedule status change";

				  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		

				quart_detailid   = "7299";
				  quart_testname   = "PublishAndScheduleStatus";
				  quart_description= "form mode - verify edit publish and schedule status change";

				  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') and contains(@checked, 'checked') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				  
				  
				  quart_detailid   = "7299";
				  quart_testname   = "PublishAndScheduleStatusVerify";
				  quart_description= "form mode - verify edit publish and schedule status change";

				if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
						
			// save out
			selenium.click("id=save_label");
			Thread.sleep(3000);
			
			
			
			// ****  Demoting page **** //
			
			// page in published mode
			// select review mode and verify elements
			
			// go back into edit mode
			// click edit
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			Thread.sleep(2000);
						
			
			// click save drop down
			selenium.click("id=edit-content-toolbar-button-Save_label");		

			
			// verify page is in published mode
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"));

			// click promote to review
			selenium.click("id=workflow-state-review");		
			Thread.sleep(2000);
			
			// save out
			selenium.click("id=save_label");
			Thread.sleep(3000);
			
			
			// page in review mode
			// go back into edit mode
			// click edit
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			Thread.sleep(2000);
					
			// click save drop down
			selenium.click("id=edit-content-toolbar-button-Save_label");		

			// verify page in review mode
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"));
			
			// verify 
				quart_detailid   = "7301";
				quart_testname   = "DemoteToReviewVerify";
				quart_description= "form mode - verify demote to review";

				if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


				quart_detailid   = "7301";
				quart_testname   = "DemoteToReviewScheduleVerify";
				quart_description= "form mode - verify demote to review schedule";

				  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));

				
			// select draft mode and verify elements 
			selenium.click("id=workflow-state-draft");
			Thread.sleep(2000);
					
			// save out
			selenium.click("id=save_label");
			Thread.sleep(3000);
			
			
			// click edit 
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			Thread.sleep(2000);
			
			// click save drop down
			selenium.click("id=edit-content-toolbar-button-Save_label");		

			Thread.sleep(1000);
			
			
			// verify page in draft mode 
			
				quart_detailid   = "7298";
				  quart_testname   = "DemoteToDraftVerify";
				  quart_description= "form mode - verify demote to draft";
				
					if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
					
					quart_detailid   = "7298";
					  quart_testname   = "DemoteToDraftScheduleVerify";
					  quart_description= "form mode - verify demote to draft schedule";
					  
					  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
					 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "7300";
				  quart_testname   = "DemoteToDraftOptionVerify";
				  quart_description= "form mode - verify demote to draft option";
				
					if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
					 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				quart_detailid   = "7300";
				  quart_testname   = "DemoteToDraftScheduleStatus";
				  quart_description= "form mode - verify demote to draft schedule status";
				
				  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
					
					quart_detailid   = "8697";
					  quart_testname   = "DemoteToDraftOptionVerify";
					  quart_description= "form mode - verify all transitions option";
					
						if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				
				// verify date shown in draft mode
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
	
						
			// save 
			selenium.click("id=save_label");
			Thread.sleep(3000);
						
			// back Home
			selenium.open(baseurl);
	}
}

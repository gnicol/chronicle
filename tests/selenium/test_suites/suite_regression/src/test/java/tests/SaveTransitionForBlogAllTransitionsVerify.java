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

public class SaveTransitionForBlogAllTransitionsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "SaveTransitionForBlogAllTransitionsVerify";

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
		SaveTransitionForBlogAllTransitionsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void SaveTransitionForBlogAllTransitionsVerify() throws InterruptedException, Exception {
		 	
		// Verify title & close icon & content type
		verifyContentElements();
	
		// blog 
		// click on Blog in left tab
		selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
		Thread.sleep(1000);
		
		browserSpecificBlogPost();
		Thread.sleep(2000);	

		// click form mode and verify all elements
		selenium.click("id=add-content-toolbar-button-form_label");
		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
			
			Thread.sleep(1000);
			// Click on title
			selenium.click("id=p4cms_content_Element_0");
			selenium.click("id=p4cms_content_Element_0");
			selenium.type("id=title", "Edit Blog Post for save transition");
			Thread.sleep(1000);
			
			// Initialize new Date object		
			Date date = new Date();
			SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
			System.out.println(dateEntry.format(date));				
			selenium.click("id=date");
			selenium.type("id=date", dateEntry.format(date));
			Thread.sleep(2000);
			selenium.type("id=author", "Testing");
			Thread.sleep(1000);
			
			selenium.click("id=excerpt");
			selenium.type("id=excerpt-Editor", "Testing");
			Thread.sleep(1000);
			
			// Click on body to enter info
			//selenium.click("css=#p4cms_content_Element_1 > span.value-node");
			selenium.click("id=body-Editor");
			selenium.type("id=dijitEditorBody", "Blog Post testing for save transition");
			Thread.sleep(2000);
			selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
			selenium.click("//div[@class='container']");			 
			
			// Save the page info
	  		selenium.click("id=add-content-toolbar-button-Save_label");	
	  		Thread.sleep(2000);
				
			String  quart_detailid   = "8278";
			String  quart_testname   = "StatusVerify";
			String  quart_description= "verify status text";
			
			if (selenium.isTextPresent(("Status")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	            else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			  quart_detailid   = "8284";
			  quart_testname   = "VersionHistory";
			  quart_description= "verify shown in version history text";
			
			if (selenium.isTextPresent(("Shown in the version history")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			quart_detailid   = "8282";
			  quart_testname   = "CommentText";
			  quart_description= "form mode - verify comment input field";
			
			  if (selenium.isElementPresent(("//textarea[contains(@id, 'comment')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			  quart_detailid   = "8280";
			  quart_testname   = "Comments";
			  quart_description= "form mode - verify comment text";
			
			  if (selenium.isTextPresent(("Comment")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			// save out
			selenium.click("id=save_label");	
			Thread.sleep(3000);
			
			
			// go back into edit mode
			// click edit
			selenium.click("css=#toolbar-content-edit > span.menu-handle");
			Thread.sleep(2000);
			
			
			// click save drop down
			selenium.click("id=edit-content-toolbar-button-Save_label");		
			Thread.sleep(2000);
			
			// verify draft option selected
			 quart_detailid   = "8286";
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
			selenium.click("css=#toolbar-content-edit > span.menu-handle");
			Thread.sleep(2000);
			
			
			// click save drop down
			selenium.click("id=edit-content-toolbar-button-Save_label");		
			Thread.sleep(2000);
			
			
			
			// click on the promote radio button
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
			selenium.click("css=#toolbar-content-edit > span.menu-handle");
			Thread.sleep(2000);
						
			
			// click save drop down
			selenium.click("id=edit-content-toolbar-button-Save_label");		
			Thread.sleep(3000);
			
			quart_detailid   = "8288";
			  quart_testname   = "PromoteAndScheduleStatus";
			  quart_description= "form mode - verify review checked and promote and schedule status";

			  if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			  
			  quart_detailid   = "8288";
			  quart_testname   = "PromoteAndScheduleStatusChange";
			  quart_description= "form mode - verify schedule status";

			  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						  

			// save out
			    selenium.click("id=save_label");	
				Thread.sleep(3000);
				
				// go back into edit mode
				// click edit
				selenium.click("css=#toolbar-content-edit > span.menu-handle");
				Thread.sleep(2000);
							
				
				// click save drop down
				selenium.click("id=edit-content-toolbar-button-Save_label");		
				Thread.sleep(2000);
	
				
			// click publish		
				selenium.click("id=workflow-state-published");			
 
			// save 
				selenium.click("id=save_label");	
				Thread.sleep(3000);
				
				// go back into edit mode
				// click edit
				selenium.click("css=#toolbar-content-edit > span.menu-handle");
				Thread.sleep(2000);
				
				// click save drop down
				selenium.click("id=edit-content-toolbar-button-Save_label");		
				Thread.sleep(2000);
				
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
				
				// verify
				quart_detailid   = "8290";
				  quart_testname   = "PublishOptionMode";
				  quart_description= "form mode - verify publish mode";

				  if(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"))
					  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				  
				  quart_detailid   = "8302";
				  quart_testname   = "PublishAndScheduleVerify";
				  quart_description= "form mode - verify publish mode and schedule status change";

					if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"))
					  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
					//writeFile1("\nskipped:8296\n", "", "");
					quart_detailid   = "8296";
					  quart_testname   = "EditPublishAndScheduleVerify";
					  quart_description= "form mode - verify edit publish and scheduling";

						if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@checked, 'checked')]"))
						  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				  
					
					quart_detailid   = "8290";
					  quart_testname   = "PublishAndScheduleStatus";
					  quart_description= "form mode - verify schedule status change";

					  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
						  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
						  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
						
				// schedule a date time
				// Initialize new Date object		
				Date date1 = new Date();
				SimpleDateFormat dateEntry1 = new SimpleDateFormat("MMMM dd, yyyy");
				System.out.println(dateEntry1.format(date1));				
				selenium.click("id=workflow-scheduledDate");
				selenium.type("id=workflow-scheduledDate", dateEntry1.format(date1));
				Thread.sleep(2000);
			
				
				// save 
				selenium.click("id=save_label");	
				Thread.sleep(3000);
				
				
				// go back into edit mode
				// click edit
				selenium.click("css=#toolbar-content-edit > span.menu-handle");
				Thread.sleep(2000);
							
				
				// click save
				selenium.click("id=edit-content-toolbar-button-Save_label");		
				
				// verify page is in published mode
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-published') and contains(@value, 'published') ]"));
				
				// click demote to review
				selenium.click("id=workflow-state-review");

			
				// save 
				selenium.click("id=save_label");	
				Thread.sleep(3000);
				
				
				// page in review mode
				// go back into edit mode
				// click edit
				selenium.click("css=#toolbar-content-edit > span.menu-handle");
				Thread.sleep(2000);
				
				// click save
				selenium.click("id=edit-content-toolbar-button-Save_label");		
				
				
				quart_detailid   = "8300";
				  quart_testname   = "DemoteToReviewVerify";
				  quart_description= "form mode - verify demote to review";
				
					if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				
					quart_detailid   = "8300";
					  quart_testname   = "DemoteToReviewScheduleVerify";
					  quart_description= "form mode - verify demote to review schedule";
					
					  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
						  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					  
				
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));

				
				// verify page in review mode
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-review') and contains(@value, 'review') ]"));
				
				
				// click draft option
				
				// select draft mode and verify elements 
				selenium.click("id=workflow-state-draft");
				Thread.sleep(2000);
				
				quart_detailid   = "8298";
				  quart_testname   = "DemoteToDraftScheduleStatus";
				  quart_description= "form mode - verify schedule status change";
				  
				  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
				
				
				// verify date not shown in draft mode
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-true') and contains(@value, 'true') ]"));
				assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-scheduled-false') and contains(@value, 'false') ]"));
				
				
				// save 
				selenium.click("id=save_label");
				Thread.sleep(3000);
				
				
				// click edit 
				selenium.click("css=#toolbar-content-edit > span.menu-handle");
				Thread.sleep(2000);
							
				// click save drop down
				selenium.click("id=edit-content-toolbar-button-Save_label");		
				Thread.sleep(1000);
				
				
				quart_detailid   = "8294";
				  quart_testname   = "DemoteToDraftVerify";
				  quart_description= "form mode - verify demote to draft";
				
					if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
					
					quart_detailid   = "8292";
					  quart_testname   = "DemoteToDraftScheduleVerify";
					  quart_description= "form mode - verify demote to draft schedule";
					  
					  if (selenium.isElementPresent(("//label[contains(@class, 'required')]")))
					 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				
				
				quart_detailid   = "8292";
				  quart_testname   = "DemoteToDraftOptionVerify";
				  quart_description= "form mode - verify demote to draft option";
				
				if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
					 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				quart_detailid   = "8298";
				  quart_testname   = "DemoteToDraftScheduleStatus";
				  quart_description= "form mode - verify demote to draft schedule status";
				
					if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
					 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					quart_detailid   = "8698";
					  quart_testname   = "DemoteToDraftOptions";
					  quart_description= "form mode - verify all transitions option";
					
					if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'workflow-state-draft') and contains(@value, 'draft') and contains(@checked, 'checked') ]"))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					  else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
			// save 
			selenium.click("id=save_label");
			Thread.sleep(3000);
			
			// back Home
			selenium.open(baseurl);
	}
}

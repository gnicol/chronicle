package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


// This code logs in and clicks on the Manage --> Manage content and verifies that the Manage content title appears

public class ManageContentAllElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageContentAllElementsVerify";

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
		ManageContentAllElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void ManageContentAllElementsVerify() throws Exception {
		
		// add content - pages, blogs, press release for Manage content
		addManageContent();
		
		// Verify menu elements
		manageMenu();
		
		// go to manage content
		selenium.click(CMSConstants.MANAGE_CONTENT);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		//writeFile1("\nskipped: 1044", "", "");

		String quart_detailid   = "6481";
		 String quart_testname   = "GridElements";
		 String quart_description= "verify grid elements";
		
		// Write to file for checking manage content type page
		if (selenium.isTextPresent(("Type")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		if (selenium.isTextPresent(("Title")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		if (selenium.isTextPresent(("Modified")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		if (selenium.isTextPresent(("Workflow")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		if (selenium.isTextPresent(("Actions")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 quart_detailid   = "6483";
		  quart_testname   = "EntriesText";
		  quart_description= "verify footer entries text";
		
		  if (selenium.isTextPresent(("entries")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  quart_detailid   = "6482";
		  quart_testname   = "AddButton";
		  quart_description= "verify add content button";
		
		  if (selenium.isElementPresent(("//input[@value='Add Content']")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		  
		  quart_detailid   = "8929";
		  quart_testname   = "WorkflowButton";
		  quart_description= "verify workflow button";
		
			if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_2_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
		  quart_detailid   = "1743";
		  quart_testname   = "PageTitle";
		  quart_description= "verify page title";
		
		  if (selenium.isTextPresent(("Manage Content")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
		  quart_detailid   = "8269";
		  quart_testname   = "SearchButton";
		  quart_description= "verify search button";
		
		  if (selenium.isTextPresent(("Search")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
		  
		  quart_detailid   = "8270";
		  quart_testname   = "TypeText";
		  quart_description= "verify type text";
		
		  if (selenium.isTextPresent(("Type")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


		  /*quart_detailid   = "8271";
		  quart_testname   = "category text";
		  quart_description= "verify category text";
		
		  if (selenium.isTextPresent(("Category")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }*/

		  quart_detailid   = "8899";
		  quart_testname   = "WorkflowText";
		  quart_description= "verify workflow text";
		
		  if (selenium.isTextPresent(("Workflow")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		  
		  // click basic page
			selenium.click("id=type-types-Pagesbasic-page");
			Thread.sleep(1000);
			
			quart_detailid   = "41";
			quart_testname   = "BasicPageCheckbox";
			quart_description= "verify basic page checkbox";
			
		 if (selenium.isTextPresent(("Basic Page")))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
		 // uncheck basic page
		    selenium.click("id=type-types-Pagesbasic-page");
		    Thread.sleep(1000);
		    
		    // click blog post
			selenium.click("id=type-types-Pagesblog-post");
			Thread.sleep(1000);

			quart_detailid   = "1741";
			quart_testname   = "BlogPostCheckbox";
			quart_description= "verify blog post checkbox";
			
			if (selenium.isTextPresent(("Blog Post")))
			 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		 // uncheck blog
		selenium.click("id=type-types-Pagesblog-post");
		Thread.sleep(1000);  
			
		// check press release
		selenium.click("id=type-types-Pagespress-release");
		Thread.sleep(1000);
		
		quart_detailid   = "1732";
		quart_testname   = "PressReleaseCheckbox";
		quart_description= "verify press release checkbox";
		
		if (selenium.isTextPresent(("Press Release")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	  
		//uncheck press release
		selenium.click("id=type-types-Pagespress-release");
		Thread.sleep(1000);
		
		// click add content button
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("css=input.dijitOffScreen");
		Thread.sleep(1000);
		
		quart_detailid   = "1740";
		quart_testname   = "AddContentDialog";
		quart_description= "verify add content modal dialog";
		
		if (selenium.isTextPresent(("Add Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		// close dialog 
		selenium.click("//span[@class='dijitDialogCloseIcon']");
		Thread.sleep(2000);
		
		
		// click pages checkbox also checks pages,blog, and press release
		selenium.click("id=type-types-Pages");
		Thread.sleep(1000);
		
		assertTrue(selenium.isTextPresent("Basic Page Testing"));
		assertTrue(selenium.isTextPresent("Blog Post Testing"));
		assertTrue(selenium.isTextPresent("Press Release Testing"));
		assertTrue(selenium.isTextPresent("Image Gallery"));
		
		
		quart_detailid   = "1726";
		quart_testname   = "PagesCheckbox";
		quart_description= "verify pages checkbox also checks pages,blog,press release,image gallery";
		
		if (selenium.isTextPresent("Basic Page"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "1726";
		quart_testname   = "PagesCheckbox1";
		quart_description= "verify pages checkbox also checks pages,blog,press release,image gallery";
		if (selenium.isTextPresent("Blog Post"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "1726";
		quart_testname   = "PagesCheckbox2";
		quart_description= "verify pages checkbox also checks pages,blog,press release,image gallery";
		if (selenium.isTextPresent("Press Release"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "1726";
		quart_testname   = "PagesCheckbox3";
		quart_description= "verify pages checkbox also checks pages,blog,press release,image gallery";
		if (selenium.isTextPresent("Image Gallery"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "10349";
		quart_testname   = "ImageGalleryCheckbox";
		quart_description= "verify pages checkbox also checks pages,blog,press release,image gallery";
		if (selenium.isTextPresent("Image Gallery"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "10348";
		quart_testname   = "ImageGalleryCheckbox";
		quart_description= "verify pages checkbox also checks pages,blog,press release,image gallery";
		if (selenium.isElementPresent(("//input[contains(@id, 'type-types-Pagesgallery')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		
		
		// uncheck pages
		selenium.click("id=type-types-Pages");
		Thread.sleep(1000);
		
		// verify user can select schedule status for workflow
		
		selenium.select("id=workflow-targetState", "label=Scheduled Status");
		Thread.sleep(1000);
		 
		// place them into a string array
		String[] currentSelection = selenium.getSelectOptions("//select[contains(@name, 'workflow[targetState]')]");
				
				// verify if the Current Status exists in the selection list 
		boolean selectedValue = ArrayUtils.contains(currentSelection, "Scheduled Status");
			    
		quart_detailid   = "8273";  
		quart_testname   = "ScheduledStatusSelection";
		quart_description= "verify scheduled status selection";
		// verify that scheduled status is selected
			if (selectedValue)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		/*// verify delete
			quart_detailid   = "8900";  
			quart_testname   = "delete text";
			quart_description= "verify delete text";
			// verify that scheduled status is selected
				if (selenium.isTextPresent("Deleted"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }*/
			
		
		// verify current status is default for workflow dropdown
				// get selected default value				
				selenium.getSelectedValue("//select[contains(@name, 'workflow[targetState]')]");
				
				// place them into a string array
				String[] items = selenium.getSelectOptions("//select[contains(@name, 'workflow[targetState]')]");
				
				// verify if the Current Status exists in the selection list 
			    boolean contains = ArrayUtils.contains(items, "Current Status");
			    		 
			    quart_detailid   = "8272";
				quart_testname   = "WorkflowSelection";
				quart_description= "verify default workflow selection";
				
				if (contains)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				// select current status
				selenium.select("id=workflow-targetState", "label=Current Status");
				
				
		// verify workflow state radio button
				
			assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@checked, 'checked') and contains(@autoapply, '1') and contains(@name, 'workflow[workflow]')]"));
		
			quart_detailid   = "7251";  
			quart_testname   = "AnystateRadioButton";
			quart_description= "verify anystate text";
			// verify that scheduled status is selected
				if (selenium.isTextPresent("Published"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					

				if (selenium.isTextPresent("Draft"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				
		// verify workflow state 'published' selection
			
			selenium.click("id=workflow-workflow-onlyPublished");
			Thread.sleep(1000);

			quart_detailid   = "7252";  
			quart_testname   = "PublishedRadioButton";
			quart_description= "verify published text";
			// verify that scheduled status is selected
				if (selenium.isTextPresent("Published"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				if (!selenium.isTextPresent("Draft"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					

			// verify workflow state 'unpublished' selection
			
			selenium.click("id=workflow-workflow-onlyUnpublished");	
			Thread.sleep(1000);

			quart_detailid   = "7253";  
			quart_testname   = "DraftRadioButton";
			quart_description= "verify draft text";
			// verify that scheduled status is selected
				if (selenium.isTextPresent("Draft"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			

		
			// verify specific workflow states
					
			selenium.click("id=workflow-workflow-userSelected");
			Thread.sleep(1000);
			selenium.click("id=workflow-states-simpledraft");
			Thread.sleep(1000);

			quart_detailid   = "7256";  
			quart_testname   = "DraftRadioButton";
			quart_description= "verify draft text";
			// verify that scheduled status is selected
				if (selenium.isTextPresent("Draft"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
			selenium.click("id=workflow-workflow-userSelected");
			Thread.sleep(1000);
			//uncheck draft
			selenium.click("id=workflow-states-simpledraft");
			Thread.sleep(1000); 
			selenium.click("id=workflow-states-simplereview");
			Thread.sleep(1000);

			quart_detailid   = "7257";  
			quart_testname   = "SpecificReviewState";
			quart_description= "verify draft text";
			// verify that scheduled status is selected
				if (selenium.isTextPresent("Review"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				
					
			selenium.click("id=workflow-workflow-userSelected");
			Thread.sleep(1000);
			selenium.click("id=workflow-states-simplereview");
			Thread.sleep(1000);
			selenium.click("id=workflow-states-simplepublished");
			Thread.sleep(1000);

			quart_detailid   = "7258";  
			quart_testname   = "SpecificPublishState";
			quart_description= "verify publish text";
			// verify that scheduled status is selected
				if (selenium.isTextPresent("Published"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
			selenium.click("id=workflow-states-simplepublished");
			Thread.sleep(1000);
				
			
		
		// **** context click for edit in manage content **** //
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		// click on page in grid using css attribute 
			//selenium.click("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[2]");
			selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
			// context menu on page
 		selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[2]");
		Thread.sleep(2000);
		
		// click on context link to edit item
		selenium.click("id=dijit_MenuItem_8_text");
		Thread.sleep(4000);
		
		// verify edit mode
		quart_detailid   = "7264";
		quart_testname   = "ContextClickEdit";
		quart_description= "verify edit toolbar element after context click edit";
		
		//if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-in-place_label')]")))
			//if (selenium.isElementPresent(("//div[contains(@id, 'edit-content-toolbar')]")))
			if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-Save_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		// commented out to be able to pass ; being checked in other code 
		quart_detailid   = "7264";
		quart_testname   = "ContextClickEditURLPath";
		quart_description= "verify url element after context click edit";
		
		if (selenium.isElementPresent(("//input[contains(@id, 'url-path')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		
		
		// ****  context click for history in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// context menu on page
		selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[2]");
		Thread.sleep(2000);
		
		// click on context link to history item
		selenium.click("id=dijit_MenuItem_9_text");
		Thread.sleep(4000);
		
		// verify edit mode
		quart_detailid   = "1720";
		quart_testname   = "ContextClickHistory";
		quart_description= "verify context click to history";
		
		if (selenium.isElementPresent(("//div[contains(@class, 'data-grid history-grid')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
	
		
		
		// **** context click for view item in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// context menu on page
		selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[2]");
		Thread.sleep(2000);
		
		// click on context link to view item
		selenium.click("id=dijit_MenuItem_6_text");
		Thread.sleep(4000);
		
		// verify edit mode
		quart_detailid   = "7792";
		quart_testname   = "ContextClickView";
		quart_description= "verify form element context click to view";
		
		
		if (selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-content-edit')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		quart_detailid   = "7792";
		quart_testname   = "ContextClickViewURLPath";
		quart_description= "verify url element for context click to view";
		
		if (selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-content-add')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
	// **** context click for change status item in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// context menu on page
		selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[2]");
		Thread.sleep(2000);
		
		// click on context link to change status item
		selenium.click("id=dijit_MenuItem_11_text");
		Thread.sleep(4000);
		
		// verify edit mode
		quart_detailid   = "8930";
		quart_testname   = "ContextClickChangeStatus";
		quart_description= "verify workflow dialog context click to change status";
		
		// verify if workflow dialog is visible
		//if (selenium.isElementPresent(("//div[contains(@class, 'workflow-dialog form-dialog p4cms-ui dijitDialog dijitDialogFocused dijitFocused')]")))
		if (selenium.isTextPresent("Change Workflow Status"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		quart_detailid   = "8930";
		quart_testname   = "ContextClickChangeStatusWorkflowDialog";
		quart_description= "verify workflow dialog for context click to view";
		
		if (selenium.isTextPresent(("Change Status To")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// **** context click for delete item in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);

		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		Thread.sleep(1000);
		// context menu on page
		selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[2]");
		Thread.sleep(2000);
		
		// click on context link to delete item
		selenium.click("id=dijit_MenuItem_10_text");
		Thread.sleep(4000);
		
		// verify edit mode
		quart_detailid   = "28";
		quart_testname   = "ContextClickDelete";
		quart_description= "verify context click to delete";
		
		
		if (selenium.isTextPresent(("Delete Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		
		
		
		
		// **** actions menu edit in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);

		//selenium.clickAt("//span[@id='dijit_form_DropDownButton_0_label']","");
		selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
		Thread.sleep(2000);
		
		// click edit menu
		selenium.click("id=dijit_MenuItem_8_text");
		Thread.sleep(4000);
		
		// verify edit mode
		quart_detailid   = "7265";
		quart_testname   = "ActionsMenuEdit";
		quart_description= "verify form element for actions menu click to edit";
		
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-in-place_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		// commented out to pass ; being checked in other code
		quart_detailid   = "7265";
		quart_testname   = "ActionsMenuEditURLPath";
		quart_description= "verify url for actions menu click to edit";
		
		if (selenium.isElementPresent(("//input[contains(@id, 'url-path')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7265";
		quart_testname   = "ActionsMenuEditPlaceButton";
		quart_description= "verify place element for actions menu click to edit";
		
		// commented out to pass ; being checked in other code
		if (selenium.isElementPresent(("//span[contains(@id, 'edit-content-toolbar-button-in-place_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			


		
		 
		// **** actions menu history in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);

		//selenium.clickAt("//span[@id='dijit_form_DropDownButton_0_label']","");
		selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
		Thread.sleep(2000);
		
		// click history menu
		selenium.click("id=dijit_MenuItem_9_text");
		Thread.sleep(3000);
		
		// verify edit mode
		quart_detailid   = "1721";
		quart_testname   = "ActionsMenuHistory";
		quart_description= "verify actions menu click to history";
		
		if (selenium.isElementPresent(("//div[contains(@class, 'data-grid history-grid')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
			
		
		// **** actions menu view in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);

		//selenium.clickAt("//span[@id='dijit_form_DropDownButton_0_label']","");
		selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
		Thread.sleep(2000);
		
		// click view menu
		selenium.click("id=dijit_MenuItem_6_text");
		Thread.sleep(3000);
		
		// verify view mode
		quart_detailid   = "7793";
		quart_testname   = "ActionsMenuView";
		quart_description= "verify actions menu click to view";
		
		if (selenium.isElementPresent(("//div[contains(@class, 'manage-toolbar-container')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		
				
				
		// **** actions menu change status in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);

		//selenium.clickAt("//span[@id='dijit_form_DropDownButton_0_label']","");
		selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
		Thread.sleep(2000);
		
		// click view menu
		selenium.click("id=dijit_MenuItem_11_text");
		Thread.sleep(3000);
			
		quart_detailid   = "8931";
		quart_testname   = "ActionsMenuChangeStatus";
		quart_description= "verify actions menu click to change status";	
			
		if (selenium.isTextPresent(("Change Workflow Status")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
				
				
				
				
	// **** actions menu delete in manage content **** //
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);

		//selenium.clickAt("//span[@id='dijit_form_DropDownButton_0_label']","");
		selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
		Thread.sleep(2000);
		
		// click delete menu
		selenium.click("id=dijit_MenuItem_10_text");
		Thread.sleep(2000);
		
		// verify delete mode
		quart_detailid   = "1711";
		quart_testname   = "ActionsMenuDelete";
		quart_description= "verify actions menu click to delete";
		
		if (selenium.isTextPresent(("Delete Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			
	
		
		
		// check workflow button
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");

		// click on workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(2000);
		
		// verify workflow
		quart_detailid   = "1256";
		quart_testname   = "WorkflowButton";
		quart_description= "verify workflow button";
				
		if (selenium.isTextPresent(("Change Workflow Status")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	
		
		
		// verify tooltip
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");

		// click on workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(4000);
		
		 // verify 'x' tooltip
		quart_detailid   = "8724";		
		quart_testname   = "_x_Tooltip";
		quart_description= "verify 'x' tooltip";
		
		// get tooltip attribute
		String tooltip = selenium.getAttribute("//div[7]/div/span[2]/@title");
		//String tooltip = selenium.getText("css=.dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon .closeText"); 

		boolean tooltipTrue = tooltip.equals("Cancel");
	
		if (tooltipTrue) 
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
	

		
			 
		
		// check shown in version history		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");

		// click on workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(2000);
		
		//assertTrue(selenium.isTextPresent("Shown in the version history")); 

		// verify workflow
		quart_detailid   = "8717";
		quart_testname   = "ShownInVersionHistory";
		quart_description= "verify shown in version history";
				
		if (selenium.isTextPresent(("Shown in the version history.")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		// verify workflow
		quart_detailid   = "8708";
		quart_testname   = "WorkflowDialogText";
		quart_description= "verify workflow dialog";
				
		if (selenium.isTextPresent(("Change Workflow Status")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		// verify error when not selecting a workflow status nor date
		
		// click on save
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		// verify workflow
		quart_detailid   = "8722";
		quart_testname   = "SaveError";
		quart_description= "verify save error when nothing selected";
				
		if (selenium.isTextPresent(("Value is required and can't be empty")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		// verify workflow comment
		quart_detailid   = "8716";
		quart_testname   = "CommentsText";
		quart_description= "verify comments text";
				
		if (selenium.isTextPresent(("Comment")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// verify comment textarea
		
		assertTrue(selenium.isElementPresent(("//textarea[contains(@id, 'comment')]")));

		// verify workflow
		quart_detailid   = "8718";
		quart_testname   = "CommentsTextArea";
		quart_description= "verify comments text area";
				
		if (selenium.isElementPresent(("//textarea[contains(@id, 'comment')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// verify 1 entry selected text
		
		assertTrue(selenium.isTextPresent("1 selected"));

		// verify workflow
		quart_detailid   = "8995";
		quart_testname   = "1EntrySelected";
		quart_description= "verify 1 entry selected text";
				
		if (selenium.isTextPresent(("1 selected")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	
		
		
			
		
		// verify save button and status change
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");

		// click on workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(3000);
		
		// click review
		selenium.click("id=state-review");
		Thread.sleep(2000);
		// click draft
		selenium.click("id=scheduled-false");
		Thread.sleep(2000);
		// click save
		selenium.click("id=save_label");
		Thread.sleep(4000);
				
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		Thread.sleep(2000);

		
		// verify workflow
		quart_detailid   = "8728";
		quart_testname   = "ChangeStatusJustNowText";
		quart_description= "verify save workflow and change status";
				
		if (selenium.isTextPresent("just now"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				
		
		
		
		// verify status required
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");

		// click on workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(3000);
		
		// verify change status to text
		quart_detailid   = "8709";
		quart_testname   = "ChangeStatusToText";
		quart_description= "verify change status to text";
				
		if (selenium.isTextPresent("Change Status To"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		// click date
		selenium.click("id=scheduled-false");
		Thread.sleep(1000);
		
		// click save
		selenium.click("id=save_label");
		Thread.sleep(4000);
		
		// verify workflow
		quart_detailid   = "8709";
		quart_testname   = "StatusRequired";
		quart_description= "verify status required";
				
		if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// verify schedule status change text
		quart_detailid   = "8710";
		quart_testname   = "ScheduleStatusText";
		quart_description= "verify schedule status change text";
				
		if (selenium.isTextPresent("Schedule Status Change"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// click save
		selenium.click("id=save_label");
		Thread.sleep(4000);
	
		// verify workflow
		quart_detailid   = "8710";
		quart_testname   = "DateTimeRequired";
		quart_description= "verify date/time required";
				
		if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		

		// verify draft option displayed
		
		assertTrue(selenium.isTextPresent(("Draft")));
		// click draft
		selenium.click("id=state-draft");
		Thread.sleep(1000);

		// verify workflow
		quart_detailid   = "8711";
		quart_testname   = "DraftOption";
		quart_description= "verify draft option";
				
		if (selenium.isTextPresent("Draft"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
		
		// verify review option displayed
		
		assertTrue(selenium.isTextPresent(("Review")));
		// click draft
		selenium.click("id=state-review");
		Thread.sleep(1000);

		// verify workflow
		quart_detailid   = "8712";
		quart_testname   = "ReviewOption";
		quart_description= "verify review option";
				
		if (selenium.isTextPresent("Review"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
		
		

		// verify publish option displayed
		
		assertTrue(selenium.isTextPresent(("Published")));
		// click draft
		selenium.click("id=state-published");
		Thread.sleep(1000);

		// verify workflow
		quart_detailid   = "8713";
		quart_testname   = "PublishOption";
		quart_description= "verify publish option";
				
		if (selenium.isTextPresent("Published"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// verify now option displayed
		
		assertTrue(selenium.isTextPresent(("Now")));
		// click draft
		selenium.click("id=scheduled-false");
		Thread.sleep(1000);

		// verify workflow
		quart_detailid   = "8714";
		quart_testname   = "NowDateOption";
		quart_description= "verify Now (date) option";
				
		if (selenium.isTextPresent("Now"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
		
		
		// verify scheduled date option displayed
		
		assertTrue(selenium.isTextPresent(("Specify Server Date and Time")));
		// click draft
		selenium.click("id=scheduled-true");
		Thread.sleep(1000);

		// verify workflow
		quart_detailid   = "8715";
		quart_testname   = "ServerDateOption";
		quart_description= "verify Server (date) option";
				
		if (selenium.isTextPresent("Specify Server Date and Time"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				
				
		
		// verify scheduled date option displayed
		
		// click server time
		selenium.click("id=scheduled-true");
		Thread.sleep(1000);

		// click date and verify
		selenium.click("//div[@id='widget_scheduledDate']/div");
		Thread.sleep(3000);
		
		// verify workflow
		quart_detailid   = "8720";
		quart_testname   = "DateDropDown";
		quart_description= "verify date dropdown";
				
		if (selenium.isElementPresent(("//div[contains(@id, 'widget_scheduledDate_dropdown')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		
		// verify scheduled date option displayed
		
		// verify workflow
		quart_detailid   = "8721";
		quart_testname   = "TimeDropDown";
		quart_description= "verify time dropdown";
				
		if (selenium.isElementPresent(("//select[contains(@id, 'scheduledTime')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
		
		// verify array in time drop down
		
		// place them into a string array
		String[] timeValues = selenium.getSelectOptions("//select[contains(@name, 'scheduledTime')]");
					
					// verify if the Current Status exists in the selection list 
		boolean hasValues = ArrayUtils.contains(timeValues, "12 AM");
		boolean hasValues1 = ArrayUtils.contains(timeValues, "1 AM");
		boolean hasValues2 = ArrayUtils.contains(timeValues, "2 AM");
		boolean hasValues3 = ArrayUtils.contains(timeValues, "3 AM");
		boolean hasValues4 = ArrayUtils.contains(timeValues, "4 AM");
		boolean hasValues5 = ArrayUtils.contains(timeValues, "5 AM");
		boolean hasValues6 = ArrayUtils.contains(timeValues, "1 PM");
		boolean hasValues7 = ArrayUtils.contains(timeValues, "2 PM");
		boolean hasValues8 = ArrayUtils.contains(timeValues, "3 PM");
		boolean hasValues9 = ArrayUtils.contains(timeValues, "4 PM");
		boolean hasValues10 = ArrayUtils.contains(timeValues, "5 PM");
		
		assertTrue(hasValues); 
		assertTrue(hasValues1); 
		assertTrue(hasValues2); 
		assertTrue(hasValues3); 
		assertTrue(hasValues4); 
		assertTrue(hasValues5);  
		assertTrue(hasValues6); 
		assertTrue(hasValues7); 
		assertTrue(hasValues8); 
		assertTrue(hasValues9); 
		assertTrue(hasValues10);  
				
		quart_detailid   = "8721";
		quart_testname   = "time drop down";
		quart_description= "verify time dropdown";
				
		if (hasValues1)
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	 
		

		
		// verify scheduled date option displayed
		
		// click server time
		selenium.click("id=scheduled-true");

		// click date and verify
		assertTrue(selenium.isElementPresent(("//dd[contains(@class, 'current-time')]")));
		Thread.sleep(4000);
		
		// verify workflow
		quart_detailid   = "8719";
		quart_testname   = "ServerCurrentTime";
		quart_description= "verify server current time";
				
		if (selenium.isElementPresent(("//dd[contains(@class, 'current-time')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


		
		
	/*	// verify save button and status change
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");

		// click on workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(4000);
		
		// click review
		selenium.click("id=state-review");
		Thread.sleep(4000);
		// click draft
		selenium.click("id=scheduled-false");
		Thread.sleep(4000);
		// click save
		selenium.click("id=save_label");
		Thread.sleep(4000);
				
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		Thread.sleep(4000);

		// click on action history and verify there's a just now update
		selenium.clickAt("//span[@class='dijitReset dijitInline dijitArrowButtonInner']","");
		Thread.sleep(4000);
		
		// click history menu 
		selenium.click("id=dijit_MenuItem_3_text");
		Thread.sleep(4000);
		
		// verify workflow
		quart_detailid   = "8728";
		quart_testname   = "change status workflow";
		quart_description= "verify save workflow and change status";
				
		if (selenium.isTextPresent("just now"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }*/
			
				
		
				
		// verify search text query 
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// verify search text
		//assertTrue(selenium.isElementPresent("//input[@id='search-query]"));
		
		// verify workflow
		quart_detailid   = "8269";
		quart_testname   = "SearchQuery";
		quart_description= "verify lucene query";
				
		if (selenium.isElementPresent("//input[@id='lucene-query']"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		
		
		
		// verify deleted item workflow 
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// context menu on page
		selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[2]");
		Thread.sleep(2000);
				
		// click on context link to delete item
		selenium.click("id=dijit_MenuItem_10_text");
		Thread.sleep(2000);
		
		// delete the item
		//selenium.click("//input[@value='Delete']");
		//Thread.sleep(4000);
		selenium.click("id=delete_label");
		Thread.sleep(4000);
		
		// click only show deleted items
		selenium.click("id=deleted-display-only");
		Thread.sleep(3000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// click workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(2000);
		
		// verify workflow message
		
		// verify workflow
		quart_detailid   = "8947";
		quart_testname   = "DeletedItemMessage";
		quart_description= "verify workflow message for deleted item";
				
		if (selenium.isTextPresent("Workflow states cannot be assigned to deleted content."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		
		
		// verify deleted item workflow 
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click only show deleted items
		selenium.click("id=deleted-display-show");
		Thread.sleep(2000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// click workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(2000);
		
		// verify workflow message
		
		// verify workflow
		quart_detailid   = "8946";
		quart_testname   = "DeletedItemWorkflowText";
		quart_description= "verify workflow text for deleted item";
				
		if (selenium.isTextPresent("Change Workflow Status"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				
		
		
		// verify deleted item workflow 
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on hide deleted option to delete another item
		selenium.click("id=deleted-display-");
		Thread.sleep(2000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// context menu on page
		selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[2]");
		Thread.sleep(2000);
				
		// click on context link to delete item
		selenium.click("id=dijit_MenuItem_10_text");
		Thread.sleep(2000);
		
		// delete the item
		selenium.click("//input[@value='Delete']");
		Thread.sleep(2000);
		selenium.click("id=delete_label");
		Thread.sleep(2000);
		
		// click only show deleted items
		selenium.click("id=deleted-display-only");
		Thread.sleep(2000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// click workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(2000);
		
		// verify workflow
		quart_detailid   = "8950";
		quart_testname   = "OKButtonForDeletedItem";
		quart_description= "verify workflow 'OK' button for deleted item";
				
		if (selenium.isElementPresent(("//span[contains(@id, 'p4cms_ui_ConfirmDialog_0-button-action_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		
		
		// verify tooltip 
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click only show deleted items
		selenium.click("id=deleted-display-show");
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// click workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(4000);
		
		
		 // verify 'x' tooltip
		quart_detailid   = "8948";
		quart_testname   = "XTooltipForShowDeletedItem";
		quart_description= "verify 'x' tooltip for show deleted item";
		
		// get tooltip attribute
		String tooltip1 = selenium.getAttribute("//div[8]/div/span[2]/@title");
		//String tooltip1 = selenium.getText("css=.dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon[title]"); 
		
		boolean tooltip1True =	tooltip1.equals("Cancel");
 				
		if (tooltip1True)
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	

		
		// verify deleted item workflow - click 'x' 
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click only show deleted items
		selenium.click("id=deleted-display-show");
		Thread.sleep(2000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		// click workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(2000);
		
		//selenium.click("css=span.dijitDialogCloseIcon.dijitDialogCloseIconHover");
		selenium.click("//span[contains(@title, 'Cancel')]");		
		Thread.sleep(2000);
		
		// verify workflow
		quart_detailid   = "8949";
		quart_testname   = "Click_x";
		quart_description= "verify click 'x' button";
				
		if (selenium.isTextPresent(("Manage Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		
		
		// check delete button
		// go back to manage content
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		
		assertTrue(selenium.isTextPresent("Deleted")); 
		
		// verify edit mode
		quart_detailid   = "8900";
		quart_testname   = "DeleteTextLeftTab";
		quart_description= "verify delete text in left tab";
				
		if (selenium.isTextPresent(("Deleted")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		
		assertTrue(selenium.isTextPresent("Hide Deleted")); 
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'deleted-display-') and contains(@checked, 'checked') ]"));

		
		// verify delete mode
		quart_detailid   = "29";
		quart_testname   = "HideDeleted";
		quart_description= "verify hide delete text in left tab";
				
		if (selenium.isTextPresent(("Hide Deleted")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
		// verify delete mode
		quart_detailid   = "29";
		quart_testname   = "HideDeletedTextLeftTab";
		quart_description= "verify hide delete text in left tab";
				
		if (selenium.isElementPresent("//input[@type='radio' and contains(@id, 'deleted-display-') and contains(@checked, 'checked') ]"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		

		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		
		assertTrue(selenium.isTextPresent("Show Deleted")); 
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'deleted-display-') and contains(@checked, 'checked') ]"));

		// click show deleted
		selenium.click("id=deleted-display-show");
		Thread.sleep(2000);
		
		// verify delete mode
		quart_detailid   = "31";
		quart_testname   = "ShowDeleteTextLeftTab";
		quart_description= "verify show delete text in left tab";
				
		if (selenium.isTextPresent(("Show Deleted")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
	
			
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		
		assertTrue(selenium.isTextPresent("Only Show Deleted")); 
		assertTrue(selenium.isElementPresent("//input[@type='radio' and contains(@id, 'deleted-display-') and contains(@checked, 'checked') ]"));

		// click show deleted
		selenium.click("id=deleted-display-only");
		Thread.sleep(2000);
		
		// verify delete mode
		quart_detailid   = "30";
		quart_testname   = "OnlyShowDeletedTextLeftTab";
		quart_description= "verify show delete text in left tab";
				
		if (selenium.isTextPresent(("Only Show Deleted")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		

		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");
		
		
		
		
		
		
		// click delete button 
		selenium.click("id=dijit_form_Button_1_label");
		selenium.click("//input[@value='Delete']");
		Thread.sleep(2000); 
				
		// verify edit mode
		quart_detailid   = "8855";
		quart_testname   = "DeleteButtonCheck";
		quart_description= "verify delete button";
				
		if (selenium.isTextPresent(("Delete Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
		
		
		selenium.click(CMSConstants.MANAGE_CONTENT);
		Thread.sleep(4000);
		
		// click on page in grid
		selenium.click("css=.dojoxGridMasterView .dojoxGridView .dojoxGridRow .dojoxGridRowTable .dojoxGridCell.title");

		// click on workflow
		selenium.click("id=dijit_form_Button_2_label");
		selenium.click("//input[@value='Workflow']");
		Thread.sleep(2000);
		
		selenium.click("css=span.dijitDialogCloseIcon");
		Thread.sleep(2000);

		// verify workflow
		quart_detailid   = "8725";
		quart_testname   = "Click_x_button";
		quart_description= "verify clicking 'x' button";
				
		if (selenium.isTextPresent(("Manage Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		//Back to Website
		backToHome();
	}
}


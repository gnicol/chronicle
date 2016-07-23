package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


// This code logs in and clicks on the Manage --> Manage content and verifies that the Manage content title appears

public class ViewHistoryList extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ViewHistoryList";

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
		selenium.waitForPageToLoad("30000");
		
		selenium.click("link=Home");
		Thread.sleep(2000);
		
		// Verify Chronicle home page elements 
		viewHistoryList();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		Thread.sleep(2000);
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void viewHistoryList() throws Exception {
		
				
		Thread.sleep(1000);
		// Verify menu elements
		manageMenu();
		
		// add content - page
		verifyContentElements();
		browserSpecificBasicPage();
		Thread.sleep(2000);
		addBasicPage();
		Thread.sleep(2000);
		
		// click on history element
		verifyHistoryList();
		Thread.sleep(2000);
		 
		 
		 // verify toolbar elements
		  String quart_detailid   = "9488";
		  String quart_testname   = "ViewHistoryListVersion";
		  String quart_description= "verify version";
			
		  if (selenium.isElementPresent(("//span[contains(@class, 'version')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
			 
			// verify toolbar elements
			  quart_detailid   = "7193";
			  quart_testname   = "ViewHistoryListDescription";
			  quart_description= "verify description";
				
		     if (selenium.isElementPresent(("//span[contains(@class, 'description')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 
		 
		 // verify display version
		selenium.click("//div[@id='history-toolbar']/div/span[4]/input");
		Thread.sleep(3000);
			// verify toolbar elements
		  quart_detailid   = "7187";
		  quart_testname   = "ViewHistoryListVersionText";
		  quart_description= "verify version";
			
			 if (selenium.isTextPresent(("Version")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					 
			 
			 
			 
			 // verify toolbar elements
			   quart_detailid   = "7188";
			   quart_testname   = "ViewHistoryListVersionElement";
			   quart_description= "verify version";
				
			  if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_DropDownButton_2')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	 
			 
			 
			 
			 
			 
			 
//			 
//			 
//		 // verify display tooltip version
//			// verify toolbar elements
//		  quart_detailid   = "7196";
//		  quart_testname   = "ViewHistoryListVersionTooltip";
//		  quart_description= "verify tooltip display version";
//			
//			String tooltip = selenium.getAttribute("//div[6]/div/div/div/ul/span/li[6]/div/div/div/div/span[4]/span/span/@title");
//			 
//			 //xpath=//a[contains(@href,'#id')]/@class 
//			 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
//
//			boolean tooltipTrue =	tooltip.equals("All Versions");
//				
//			if (tooltipTrue)
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//			 
//				 	 
//			// verify manage chronicle tooltip
//		  quart_detailid   = "7195";
//		  quart_testname   = "ViewHistoryListChronicleTooltip";
//		  quart_description= "verify manage chronicle tooltip";
//			
//		  String tooltip1 = selenium.getAttribute("//div[6]/div/div/div/ul/span/li[2]/span/@title");
//			 
//			 //xpath=//a[contains(@href,'#id')]/@class 
//			 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
//
//			boolean tooltip1True =	tooltip1.equals("Manage Chronicle");
//			
//			if (tooltip1True)
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//			
//				
//		// verify preview tooltip
//		 quart_detailid   = "7194";
//		 quart_testname   = "ViewHistoryListPreviousVersionTooltip";
//		 quart_description= "verify previous version tooltip";
//		 	
//		 String tooltip2 = selenium.getAttribute("//div[6]/div/div/div/ul/span/li[6]/div/div/div/div/span[2]/span/span/@title");
//			 
//			 //xpath=//a[contains(@href,'#id')]/@class 
//			 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
//
//		 boolean tooltip2True =	tooltip2.equals("Previous Version");
//				
//			if (tooltip2True)
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
//				
					
	 
		 quart_detailid   = "242";
		 quart_testname   = "ViewHistoryListHistoryListButton";
		 quart_description= "verify history list button";
		
		 
		  if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_12_label')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
			
		  
		  
			// edit page and save in review mode
			verifyEditButtonDisplayed();
			Thread.sleep(4000);
			
			selenium.click("id=p4cms_content_Element_0");
 			selenium.type("id=title", "Basic Page review mode for history list");
 			// Click body and enter info
 			Thread.sleep(1000);
 			selenium.click("css=#p4cms_content_Element_0 > span.value-node");
 			selenium.type("id=dijitEditorBody", "History List testing");
 			Thread.sleep(2000);
 			selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
 			selenium.click("//div[@class='container']");			
 			Thread.sleep(2000);
 			
 			// Save page form
 			selenium.click("id=edit-content-toolbar-button-Save_label");	
			Thread.sleep(2000);
			
			// click review mode
 			selenium.click("id=workflow-state-review");
 			Thread.sleep(2000);
 			
			// save out
 			selenium.click("id=save_label");	
			Thread.sleep(3000);
	
 			
 			// edit page and save in publish mode
 			verifyEditButtonDisplayed();
			Thread.sleep(2000);
			
			selenium.click("id=p4cms_content_Element_0");
 			selenium.type("id=title", "Basic Page publish mode for history list");
 			// Click body and enter info
 			Thread.sleep(1000);
 			selenium.click("css=#p4cms_content_Element_0 > span.value-node");
 			selenium.type("id=dijitEditorBody", "History List testing");
 			Thread.sleep(2000);
 			selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
 			selenium.click("//div[@class='container']");			
 			Thread.sleep(2000);
 			
 			
 		// Save page form
 			selenium.click("id=edit-content-toolbar-button-Save_label");	
			Thread.sleep(2000);
			
			// click review mode
 			selenium.click("id=workflow-state-published");
 			Thread.sleep(2000);
 			
			// save out
 			selenium.click("id=save_label");	
			Thread.sleep(3000);

			
					 		 
			// click on history element
			verifyHistoryList();
			Thread.sleep(3000);
			
			
	/*		// check to see if button is enabled for version list
			if (selenium.isTextPresent("Version: 4 of 4")) {
				
			  quart_detailid   = "7186";
			  quart_testname   = "ViewHistoryListButtonDisabledLatestVersion";
			  quart_description= "verify button disabled if on latest";
				  if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_11') and contains(@aria-disabled, 'true')]")))
					  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	}
			
*/		
			
			
			 // click on previous version
		  //selenium.click("//div[@id='history-toolbar']/div/span[2]/input");
			selenium.clickAt("id=dijit_form_Button_10_label","");

/*		  Thread.sleep(3000);
		  quart_detailid   = "7185";
		  quart_testname   = "ViewHistoryListPreviousVersionButton";
		  quart_description= "verify previous version button";
									 
			  if (selenium.isTextPresent(("Version: 3 of 4")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
				
		  
			  // click on previous version again
			 // selenium.click("//div[@id='history-toolbar']/div/span[2]/input");
				selenium.clickAt("id=dijit_form_Button_10_label","");

			  Thread.sleep(3000);
			  quart_detailid   = "7185";
			  quart_testname   = "ViewHistoryListClickPreviousVersionButtonAgain";
			  quart_description= "verify previous version button";
										 
				  if (selenium.isTextPresent(("Version: 2 of 4")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
*/				
				  
			if (selenium.isTextPresent("Version: 2 of 4")) {
				
			  quart_detailid   = "9487";
			  quart_testname   = "ViewHistoryListButtonEnabledLatestVersion";
			  quart_description= "verify button enabled if on latest";
			  
			  selenium.clickAt("id=dijit_form_Button_11_label", "");
			  Thread.sleep(3000);
				 if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_11')]")))
				  writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	}	  
		  
		  		
				  
				  
			// click history list button
			selenium.clickAt("css=.view-action .p4cms-dock .manage-toolbar .toolbar-drawer .toolbar-pane .history-toolbar .right .dijitButton .dijitButtonNode .dijitButtonContents .dijitButtonText","");
 			//selenium.clickAt("id=dijit_form_Button_12_label","");
 			//selenium.click("//div[@id='history-toolbar']/div[3]/span[2]/input");
 			Thread.sleep(3000);
 			
 			
 			// click history list
			 quart_detailid   = "7192";
		     quart_testname   = "ViewHistoryListHistoryListButtonElement";
			 quart_description= "verify history grid";
			
			  if (selenium.isElementPresent(("//div[contains(@class, 'data-grid history-grid')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
 			
 			
 	
 			// verify history grid
 			 quart_detailid   = "6481";
 		     quart_testname   = "ViewHistoryListHistoryGrid";
 			 quart_description= "verify history grid";
 			
 			  if (selenium.isElementPresent(("//div[contains(@class, 'data-grid history-grid')]")))
 				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
 				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
 			  	  
 			  
 			
 		// verify history text
		  quart_detailid   = "2478";
		  quart_testname   = "ViewHistoryListHistoryText";
		  quart_description= "verify history text";
								 
			 if (selenium.isTextPresent(("History")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		
				
			 
		 // verify entries in grid
			 quart_detailid   = "7198";
			  quart_testname   = "ViewHistoryListEntriesText";
			  quart_description= "verify entries text";
									 
				 if (selenium.isTextPresent(("3")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
	
			 
			 
			 
 			// verify search
 			 quart_detailid   = "9490";
			 quart_testname   = "ViewHistoryListSearchText";
			  quart_description= "verify search button";
									 
			 if (selenium.isTextPresent(("Search")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
 			
 			
				// verify search
 			  quart_detailid   = "227";
			  quart_testname   = "ViewHistoryListSearchButton";
			  quart_description= "verify search button";
									 
			  if (selenium.isElementPresent(("//input[contains(@id, 'search-query')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
	 			
	 			
	 			
			// verify search
 			 quart_detailid   = "9491";
			  quart_testname   = "ViewHistoryListUserText";
			  quart_description= "verify user text";
									 
			if (selenium.isTextPresent(("User")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		 			
				 
			// verify modified
 			 quart_detailid   = "9492";
			  quart_testname   = "ViewHistoryListModifiedText";
			  quart_description= "verify modified text";
									 
			  if (selenium.isTextPresent(("Modified")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
 					
 			
			// verify anytime radio button
				quart_detailid   = "208";
				quart_testname   = "ViewHistoryListAnytimeRadioButton";
				quart_description= "verify anytime radio button"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isElementPresent("//input[@id='modified-range-' and contains(@type, 'radio') and contains(@autoapply, '1') and contains(@checked, 'checked')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
			
		 
		  
				// verify past day radio button
				quart_detailid   = "209";
				quart_testname   = "ViewHistoryListDayRadioButton";
				quart_description= "verify past day radio button"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isElementPresent("//input[@id='modified-range-1' and contains(@type, 'radio') and contains(@autoapply, '1') and contains(@value, '1')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				 
		 

				// verify past week radio button
				quart_detailid   = "211";
				quart_testname   = "ViewHistoryListWeekRadioButton";
				quart_description= "verify past week radio button"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isElementPresent("//input[@id='modified-range-7' and contains(@type, 'radio') and contains(@autoapply, '1') and contains(@value, '7')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				 
 			
 			
				// verify past month radio button
				quart_detailid   = "210";
				quart_testname   = "ViewHistoryListMonthRadioButton";
				quart_description= "verify past month radio button"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isElementPresent("//input[@id='modified-range-31' and contains(@type, 'radio') and contains(@autoapply, '1') and contains(@value, '31')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
 			
 			
				// verify past month radio button
				quart_detailid   = "212";
				quart_testname   = "ViewHistoryListYearRadioButton";
				quart_description= "verify past year radio button"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isElementPresent("//input[@id='modified-range-365' and contains(@type, 'radio') and contains(@autoapply, '1') and contains(@value, '365')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
 			
 			 
				
				// verify workflow text
				quart_detailid   = "9493";
				quart_testname   = "ViewHistoryListWorkflowText";
				quart_description= "verify workflow text"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("Workflow"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
 			
				
				
				// verify # text
				quart_detailid   = "232";
				quart_testname   = "ViewHistoryListLabelText";
				quart_description= "verify label text"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("#"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
				
				// verify workflow text
				quart_detailid   = "8265";
				quart_testname   = "ViewHistoryListDescText";
				quart_description= "verify desc text"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("Description"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
				// verify workflow text
				quart_detailid   = "8266";
				quart_testname   = "ViewHistoryListWorkflowTextOnGrid";
				quart_description= "verify workflow text"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("Workflow"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
				// verify workflow text
				quart_detailid   = "8267";
				quart_testname   = "ViewHistoryListDateText";
				quart_description= "verify date text"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("Date"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
				// verify tooltip text
				quart_detailid   = "234";
				quart_testname   = "ViewHistoryListTooltipCancel";
				quart_description= "verify tooltip cancel";
					
				String tooltip3 = selenium.getAttribute("//div[15]/div/span[2]/@title");
					 
				 //xpath=//a[contains(@href,'#id')]/@class 
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

				boolean tooltip3True = tooltip3.equals("Cancel");
					
				if (tooltip3True)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			

				
				// place them into a string array
				String[] workFlowValues = selenium.getSelectOptions("//select[contains(@name, 'workflow[targetState]')]");
							
				// verify if the Current Status exists in the selection list 
				boolean hasValues 	= ArrayUtils.contains(workFlowValues, "Current Status");
				boolean hasValues1 = ArrayUtils.contains(workFlowValues, "Scheduled Status");
				boolean hasValues2 = ArrayUtils.contains(workFlowValues, "Current or Scheduled Status");
						
				quart_detailid   = "8261";
				quart_testname   = "ViewHistoryListWorkflowSelections1";
				quart_description= "verify workflow dropdown";
				if (hasValues)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				quart_detailid   = "8262";
				quart_testname   = "ViewHistoryListWorkflowSelections2";
				quart_description= "verify workflow dropdown";
				if (hasValues1)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				quart_detailid   = "8263";
				quart_testname   = "ViewHistoryListWorkflowSelections3";
				quart_description= "verify workflow dropdown";
				if (hasValues2)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
				
	 			
	 			// click on draft checkbox
	 			selenium.click("id=workflow-validStates-draft");
	 			Thread.sleep(2000);
	 			quart_detailid   = "6870";
				quart_testname   = "ViewHistoryListDraftCheckbox";
				quart_description= "verify draft"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("Draft"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
	 			
	 			
	 			// verify actions menu for draft
				//selenium.clickAt("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input","");
				//selenium.click("css=#dijit_form_DropDownButton_6_label > span > img");
				//selenium.clickAt("//span[@id='dijit_form_DropDownButton_6']","");
				//selenium.click("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input");
				selenium.clickAt("css=.dijitDialog .dijitDialogPaneContent .scrollNode .history-grid-wrapper .history-grid .selectable .dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
				//selenium.click("css=#dijit_form_DropDownButton_6_label > span > img");
				Thread.sleep(3000);
				
				// click edit menu
				//selenium.click("id=dijit_MenuItem_2_text");
				
				// get attribute for the view, diff against latest version, rollback
				//String View = selenium.getAttribute("//div[13]/table/tbody/tr/td[2]");
				//String View = selenium.getAttribute("//*[@id='dijit_MenuItem_6_text']/@id");
		
				/*boolean ViewTrue = View.equals("View");
					
				quart_detailid   = "229";
				quart_testname   = " actions menu for view";
				quart_description= "verify actions menu for view"; 
				
				if (ViewTrue)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);*/
				 
	 			// verify view
				quart_detailid   = "229";
				quart_testname   = "ViewHistoryListViewForDraft";
				quart_description= "verify actions menu for view"; 
				if (selenium.isTextPresent("View"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				// verify Diff against latest version
				quart_detailid   = "229";
				quart_testname   = "ViewHistoryListDiffAgainstLatestVersionForDraft";
				quart_description= "verify actions menu for diff against latest version"; 
				if (selenium.isTextPresent("Diff Against Latest Version"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
				// verify Diff against prev version
				quart_detailid   = "229";
				quart_testname   = "ViewHistoryListDiffAgainstPreviousVersionForDraft";
				quart_description= "verify actions menu for diff against previous version"; 
				if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
				// verify Diff against selected version
				quart_detailid   = "229";
				quart_testname   = "ViewHistoryListDiffAgainstSelectedVersionForDraft";
				quart_description= "verify actions menu for diff against selected version"; 
				if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
				// verify rollback version
				quart_detailid   = "229";
				quart_testname   = "ViewHistoryListRollbackForDraft";
				quart_description= "verify actions menu for rollback version"; 
				if (selenium.isTextPresent("Rollback"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
				
				
			
				
	 			
	 			// click on review checkbox
	 			selenium.click("id=workflow-validStates-draft");
	 			Thread.sleep(2000);
	 			selenium.click("id=workflow-validStates-review");
	 			Thread.sleep(3000);
	 			quart_detailid   = "6871";
				quart_testname   = "ViewHistoryListReviewCheckbox";
				quart_description= "verify review"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("Review"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(3000);
	 			
	 			// verify actions menu for review
	 			// verify actions menu for draft
					//selenium.clickAt("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input","");
					//selenium.click("css=#dijit_form_DropDownButton_6_label > span > img");
				selenium.clickAt("css=.dijitDialog .dijitDialogPaneContent .scrollNode .history-grid-wrapper .history-grid .selectable .dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
				//selenium.click("css=#dijit_form_DropDownButton_6_label > span > img");
				Thread.sleep(3000);
				
					// click edit menu
					//selenium.click("id=dijit_MenuItem_2_text");
					
					// get attribute for the view, diff against latest version, rollback
					//String View = selenium.getAttribute("//div[13]/table/tbody/tr/td[2]");
					//String View = selenium.getAttribute("//*[@id='dijit_MenuItem_6_text']/@id");
			
					/*boolean ViewTrue = View.equals("View");
						
					quart_detailid   = "229";
					quart_testname   = " actions menu for view";
					quart_description= "verify actions menu for view"; 
					
					if (ViewTrue)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);*/
					 
		 			// verify view
					quart_detailid   = "228";
					quart_testname   = "ViewHistoryListViewForReview";
					quart_description= "verify actions menu for view"; 
					//if (selenium.isElementPresent(("//tr[contains(@id, 'dijitReset dijitMenuItem')]")))
					if(selenium.isTextPresent("View"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					// verify Diff against latest version
					quart_detailid   = "228";
					quart_testname   = "ViewHistoryListDiffAgainstLatestVersionForReview";
					quart_description= "verify actions menu for diff against latest version"; 
					//if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem') and contains(@aria-disabled, 'false')]")))
					if(selenium.isTextPresent("Diff Against Latest Version"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					
					// verify Diff against prev version
					quart_detailid   = "228";
					quart_testname   = "ViewHistoryListDiffAgainstPreviousVersionForReview";
					quart_description= "verify actions menu for diff against previous version"; 
					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem') and contains(@aria-disabled, 'false')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					
					// verify Diff against selected version
					quart_detailid   = "228";
					quart_testname   = "ViewHistoryListDiffAgainstSelectedVersionForReview";
					quart_description= "verify actions menu for diff against selected version"; 
					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					
					// verify rollback version
					quart_detailid   = "228";
					quart_testname   = "ViewHistoryListRollbackForReview";
					quart_description= "verify actions menu for rollback version"; 
					//if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemLabel') and contains(@aria-disabled, 'false')]")))
					if(selenium.isTextPresent("Rollback"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
			
					
					
					
	 			
	 			
	 			// click on publish checkbox
	 			selenium.click("id=workflow-validStates-review");
	 			Thread.sleep(2000);
	 			selenium.click("id=workflow-validStates-published");
	 			Thread.sleep(2000);
	 			quart_detailid   = "6872";
				quart_testname   = "ViewHistoryListPublishcheckbox";
				quart_description= "verify publish"; 
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				if (selenium.isTextPresent("Published"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(2000);
	 			
	 			// verify actions menu for published
				selenium.clickAt("css=.dijitDialog .dijitDialogPaneContent .scrollNode .history-grid-wrapper .history-grid .selectable .dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
				//selenium.click("css=#dijit_form_DropDownButton_6_label > span > img");
				Thread.sleep(3000);
					
					// click edit menu
					//selenium.click("id=dijit_MenuItem_2_text");
					
					// get attribute for the view, diff against latest version, rollback
					//String View = selenium.getAttribute("//div[13]/table/tbody/tr/td[2]");
					//String View = selenium.getAttribute("//*[@id='dijit_MenuItem_6_text']/@id");
			
					/*boolean ViewTrue = View.equals("View");
						
					quart_detailid   = "229";
					quart_testname   = " actions menu for view";
					quart_description= "verify actions menu for view"; 
					
					if (ViewTrue)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);*/
					 
		 			// verify view
					quart_detailid   = "230";
					quart_testname   = "ViewHistoryListViewForPublished";
					quart_description= "verify actions menu for view"; 
					//if (selenium.isElementPresent(("//tr[contains(@id, 'dijitReset dijitMenuItem')]")))
					if(selenium.isTextPresent("View"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					// verify Diff against latest version
					quart_detailid   = "230";
					quart_testname   = "ViewHistoryListDiffAgainstLatestVersionForPublished";
					quart_description= "verify actions menu for diff against latest version"; 
					//if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
					if(selenium.isTextPresent("Diff Against Latest Version"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					
					// verify Diff against prev version
					quart_detailid   = "230";
					quart_testname   = "ViewHistoryListDiffAgainstPreviousVersionForPublished";
					quart_description= "verify actions menu for diff against previous version"; 
					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem') and contains(@aria-disabled, 'false')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					
					// verify Diff against selected version
					quart_detailid   = "230";
					quart_testname   = "ViewHistoryListDiffAgainstSelectedVersionForPublished";
					quart_description= "verify actions menu for diff against selected version"; 
					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);
					
					
					// verify rollback version
					quart_detailid   = "230";
					quart_testname   = "ViewHistoryListRollbackForPublished";
					quart_description= "verify actions menu for rollback version"; 
					//if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemSelected dijitMenuItem') and contains(@aria-disabled, 'false')]")))
					if(selenium.isTextPresent("Rollback"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					Thread.sleep(2000);		
				
	 			selenium.click("id=workflow-validStates-published");
	 			Thread.sleep(2000);
	 			 			
	 			
//	 			
//	 		// click on draft checkbox
//	 			selenium.click("id=workflow-validStates-draft");
//	 			Thread.sleep(2000);
//
//	 			selenium.clickAt("css=.dijitDialog .dijitDialogPaneContent .scrollNode .history-grid-wrapper .history-grid .selectable .dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRow .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
//	 			Thread.sleep(2000);
//	 			// verify actions menu for draft
				//selenium.clickAt("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input","");
				//selenium.click("css=#dijit_form_DropDownButton_6_label > span > img");
				

//	 			// click on element on page
//	 			selenium.click("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input");
//	 			
//	 			// context click
//	 			selenium.contextMenu("//div[15]/div[2]/div/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[3]");
//				Thread.sleep(3000);
//				
//				// click edit menu
//				//selenium.click("id=dijit_MenuItem_2_text");
//				
//				// get attribute for the view, diff against latest version, rollback
//				//String View = selenium.getAttribute("//div[13]/table/tbody/tr/td[2]");
//				//String View = selenium.getAttribute("//*[@id='dijit_MenuItem_6_text']/@id");
//		
//				/*boolean ViewTrue = View.equals("View");
//					
//				quart_detailid   = "229";
//				quart_testname   = " actions menu for view";
//				quart_description= "verify actions menu for view"; 
//				
//				if (ViewTrue)
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);*/
//				 
//	 			// verify view
//				quart_detailid   = "236";
//				quart_testname   = "ViewHistoryListContextClickForViewDraft";
//				quart_description= "verify context click menu for view"; 
//				if (selenium.isElementPresent(("//tr[contains(@id, 'dijitReset dijitMenuItem dijitMenuItemFocused dijitFocused dijitMenuItemSelected')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//				// verify Diff against latest version
//				quart_detailid   = "236";
//				quart_testname   = "ViewHistoryListContextClickDiffAgainstLatestVersionForDraft";
//				quart_description= "verify context click menu for diff against latest version"; 
//				if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemSelected dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//				
//				// verify Diff against prev version
//				quart_detailid   = "236";
//				quart_testname   = "ViewHistoryListContextClickDiffAgainstPreviousVersionForDraft";
//				quart_description= "verify context click menu for diff against previous version"; 
//				if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//				
//				// verify Diff against selected version
//				quart_detailid   = "236";
//				quart_testname   = "ViewHistoryListContextClickDiffAgainstSelectedVersionForDraft";
//				quart_description= "verify click menu for diff against selected version"; 
//				if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//				
//				// verify rollback version
//				quart_detailid   = "236";
//				quart_testname   = "ViewHistoryListContextClickRollbackForDraft";
//				quart_description= "verify context click menu for rollback version"; 
//				if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemSelected dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//				
//			
//				
//	 			
//	 			// click on review checkbox
//	 			selenium.click("id=workflow-validStates-draft");
//	 			Thread.sleep(2000);
//	 			selenium.click("id=workflow-validStates-review");
//	 			Thread.sleep(3000);
//	 			
//	 			
//	 			// verify actions menu for review
//	 			// verify actions menu for draft
//					//selenium.clickAt("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input","");
//					//selenium.click("css=#dijit_form_DropDownButton_6_label > span > img");
//	 		// click on element on page
//	 			selenium.click("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input");
//	 			
//	 			// context click
//	 			selenium.contextMenu("//div[15]/div[2]/div/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[3]");
//				Thread.sleep(3000);
//				//selenium.clickAt("//span[@id='dijit_form_DropDownButton_6']","");
//					Thread.sleep(3000);
//					
//					// click edit menu
//					//selenium.click("id=dijit_MenuItem_2_text");
//					
//					// get attribute for the view, diff against latest version, rollback
//					//String View = selenium.getAttribute("//div[13]/table/tbody/tr/td[2]");
//					//String View = selenium.getAttribute("//*[@id='dijit_MenuItem_6_text']/@id");
//			
//					/*boolean ViewTrue = View.equals("View");
//						
//					quart_detailid   = "229";
//					quart_testname   = " actions menu for view";
//					quart_description= "verify actions menu for view"; 
//					
//					if (ViewTrue)
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);*/
//					 
//		 			// verify view
//					quart_detailid   = "235";
//					quart_testname   = "ViewHistoryListContextClickViewForReview";
//					quart_description= "verify context click menu for view"; 
//					if (selenium.isElementPresent(("//tr[contains(@id, 'dijitReset dijitMenuItemSelected dijitMenuItem dijitMenuItemFocused dijitFocused')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					// verify Diff against latest version
//					quart_detailid   = "235";
//					quart_testname   = "ViewHistoryListContextClickDiffAgainstLatestVersionForReview";
//					quart_description= "verify context click menu for diff against latest version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemSelected dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					
//					// verify Diff against prev version
//					quart_detailid   = "235";
//					quart_testname   = "ContextClickDiffAgainstPreviousVersionForReview";
//					quart_description= "verify context click menu for diff against previous version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					
//					// verify Diff against selected version
//					quart_detailid   = "235";
//					quart_testname   = "ContextClickDiffAgainstSelectedVersionForReview";
//					quart_description= "verify context click menu for diff against selected version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					
//					// verify rollback version
//					quart_detailid   = "235";
//					quart_testname   = "ContextClickRollbackForReview";
//					quart_description= "verify actions menu for rollback version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemSelected dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//			
//					
//					
//					
//	 			
//	 			
//	 			// click on publish checkbox
//	 			selenium.click("id=workflow-validStates-review");
//	 			Thread.sleep(2000);
//	 			selenium.click("id=workflow-validStates-published");
//	 			Thread.sleep(2000);
//	 			
//	 			
//	 			// verify actions menu for published
//	 		// click on element on page
//	 			selenium.click("//div[@id='dojox_grid__View_1']/div/div/div/div/table/tbody/tr/td[6]/span/input");
//	 			
//	 			// context click
//	 			selenium.contextMenu("//div[15]/div[2]/div/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td[3]");
//				Thread.sleep(3000);
//				//selenium.clickAt("//span[@id='dijit_form_DropDownButton_6']","");
//					Thread.sleep(3000);
//					
//					// click edit menu
//					//selenium.click("id=dijit_MenuItem_2_text");
//					
//					// get attribute for the view, diff against latest version, rollback
//					//String View = selenium.getAttribute("//div[13]/table/tbody/tr/td[2]");
//					//String View = selenium.getAttribute("//*[@id='dijit_MenuItem_6_text']/@id");
//			
//					/*boolean ViewTrue = View.equals("View");
//						
//					quart_detailid   = "229";
//					quart_testname   = " actions menu for view";
//					quart_description= "verify actions menu for view"; 
//					
//					if (ViewTrue)
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);*/
//					 
//		 			// verify view
//					quart_detailid   = "237";
//					quart_testname   = " context click menu for view selection";
//					quart_description= "verify context click menu for view"; 
//					if (selenium.isElementPresent(("//tr[contains(@id, 'dijitReset dijitMenuItemSelected dijitMenuItem dijitMenuItemFocused dijitFocused')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					// verify Diff against latest version
//					quart_detailid   = "237";
//					quart_testname   = " context click menu for diff against latest version selection";
//					quart_description= "verify context click menu for diff against latest version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemSelected dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					
//					// verify Diff against prev version
//					quart_detailid   = "237";
//					quart_testname   = " context click menu for diff against previous version selection";
//					quart_description= "verify context click menu for diff against previous version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					
//					// verify Diff against selected version
//					quart_detailid   = "237";
//					quart_testname   = " context click menu for diff against selected version selection";
//					quart_description= "verify actions menu for diff against selected version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItem dijitMenuItemDisabled dijitDisabled') and contains(@aria-disabled, 'true')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);
//					
//					
//					// verify rollback version
//					quart_detailid   = "237";
//					quart_testname   = " context click menu for rollback selection";
//					quart_description= "verify actions menu for rollback version"; 
//					if (selenium.isElementPresent(("//tr[contains(@class, 'dijitReset dijitMenuItemSelected dijitMenuItem') and contains(@aria-disabled, 'false')]")))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//					Thread.sleep(2000);		
//				
//	 			selenium.click("id=workflow-validStates-published");
//	 			Thread.sleep(2000);	
//	 			
//	 			// verify sorting on history list
//	 			
//	 			selenium.click("css=div.dojoxGridSortNode");
//	 			Thread.sleep(3000);
//	 			
//	 			quart_detailid   = "218";
//				quart_testname   = " ascending sort on #";
//				quart_description= "verify ascending sort on #"; 
//				if (selenium.isElementPresent(("//div[16]/div[2]/div/div/div[2]/div[2]/div/div/div/div/table/tbody/tr/th/div")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//								
//				
//				// verify sort on user
//				
//				selenium.click("css=div.dojoxGridColCaption");
//				Thread.sleep(2000);
//				
//				quart_detailid   = "216";
//				quart_testname   = " ascending sort on user";
//				quart_description= "verify ascending sort on user"; 
//				if (selenium.isElementPresent(("//div[16]/div[2]/div/div/div[2]/div[2]/div/div/div/div/table/tbody/tr/th[2]/div/div[3]")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//				
//				
//				// verify sort on description
//				
//				selenium.click("//div[16]/div[2]/div/div/div[2]/div[2]/div/div/div/div/table/tbody/tr/th[3]/div/div[3]");
//				Thread.sleep(2000);
//				
//				quart_detailid   = "231";
//				quart_testname   = " ascending sort on user";
//				quart_description= "verify ascending sort on user"; 
//				if (selenium.isElementPresent(("//div[contains(@class, 'dojoxGridSortNode dojoxGridSortUp']")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//	 			
//				
//				// verify sort on date
//				
//				selenium.click("//div[16]/div[2]/div/div/div[2]/div[2]/div/div/div/div/table/tbody/tr/th[5]/div/div[3]");
//				Thread.sleep(2000);
//				
//				quart_detailid   = "214";
//				quart_testname   = " ascending sort on user";
//				quart_description= "verify ascending sort on user"; 
//				if (selenium.isElementPresent(("//div[contains(@class, 'dojoxGridSortNode dojoxGridSortUp']")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);
//				
//	 			
//				// verify no sort on actions
//				
//				selenium.click("//div[16]/div[2]/div/div/div[2]/div[2]/div/div/div/div/table/tbody/tr/th[4]/div");
//				Thread.sleep(2000);
//				
//				quart_detailid   = "6869";
//				quart_testname   = " ascending sort on user";
//				quart_description= "verify ascending sort on user"; 
//				if (!selenium.isElementPresent(("//div[contains(@class, 'dojoxGridSortNode dojoxGridSortUp']")))
//					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(2000);		
				
	}
}
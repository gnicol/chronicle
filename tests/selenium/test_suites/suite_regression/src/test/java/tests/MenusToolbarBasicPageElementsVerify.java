package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;
import java.io.*;

import shared.BaseTest;

// This code creates a page - content in form mode; it clicks on add a page, clicks on in-form mode and verifys all elements to write to a file.
// It also enters a title and body and then saves the page

public class MenusToolbarBasicPageElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "MenusToolbarBasicPageElementsVerify";

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
	      MenusToolbarBasicPageElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
	}
	
	public void MenusToolbarBasicPageElementsVerify() throws InterruptedException, Exception {
		
		// Verify title & close icon & content type
		verifyContentElements();
		
		// Basic page
		// click on Pages in left tab
		selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
		selenium.click("//a[@href='/add/type/basic-page']");
		Thread.sleep(2000);		
		
		// click on Menu element
 		selenium.clickAt("id=add-content-toolbar-button-Menus","");
		Thread.sleep(2000);
		
		// click on new menu item
		selenium.click("id=menus-addMenuItem_label");
		Thread.sleep(1000);
		selenium.click("name=menus[addMenuItem]");
		Thread.sleep(1000);
		
	 	String quart_detailid   = "8184";
		String quart_testname   = "PlaceModeMenusLabelText";
		String quart_description= "verify menus label text";
			 
		if (selenium.isTextPresent("Label"))
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }



	 	 quart_detailid   = "8186";
		 quart_testname   = "PlaceModeMenusText";
		 quart_description= "verify menus text";
			 
		 if (selenium.isTextPresent("Use content entry's title"))
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


		// check for the action menu selections
		 quart_detailid   = "8185";
		  quart_testname   = "PlaceModeMenuToolbarCheckbox";
		  quart_description= "menu toolbar checkbox verify";
		
	 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-autoLabel')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	 	// check for the action menu selections
			 quart_detailid   = "8181";
			  quart_testname   = "PlaceModeMenuToolbarTooltip";
			  quart_description= "menu toolbar tooltip verify";
			
		 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-autoLabel')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
	 		
	 	// check for the action menu selections
			 quart_detailid   = "8183";
			  quart_testname   = "PlaceModeMenuToolbarAddMenuItemButton";
			  quart_description= "menu toolbar add menu item button verify";
			
		 		if (selenium.isElementPresent(("//span[contains(@id, 'menus-addMenuItem_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	 		
	 		
		 
		// check for the action menu selections
			 quart_detailid   = "10365";
			  quart_testname   = "PlaceModeMenuToolbarActionText";
			  quart_description= "menu toolbar action text verify";
			
		 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-contentAction')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				
				// check drop down selection for View As options				 			
				// place them into a string array
				String[] viewAsValues = selenium.getSelectOptions("//select[contains(@name, 'contentAction')]");
							
							// verify if the Current Status exists in the selection list 
				boolean contentValues  = ArrayUtils.contains(viewAsValues, "Go To Page");
				boolean contentValues1 = ArrayUtils.contains(viewAsValues, "View Image");
				boolean contentValues2 = ArrayUtils.contains(viewAsValues, "Download File");

				quart_detailid   = "10366";
				quart_testname   = "PlaceModeMenuToolbarGoToPageSelection";
				quart_description= "verify create link go to page selection";
				if (contentValues)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				quart_detailid   = "10367";
				quart_testname   = "PlaceModeMenuToolbarViewAsImageSelection";
				quart_description= "verify create link view as image selection";
				if (contentValues1)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				quart_detailid   = "10368";
				quart_testname   = "PlaceModeMenuToolbarDownloadFileSelection";
				quart_description= "verify create link download file selection";
				if (contentValues2)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			

				// check for the action menu selections
				 quart_detailid   = "8182";
				  quart_testname   = "PlaceModeMenuToolbarAddMenuItemButton";
				  quart_description= "menu toolbar add menu item button verify";
				
			 		if (selenium.isElementPresent(("//span[contains(@id, 'menus-addMenuItem_label')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 		 		
				// check for the position selections
				
				// check for the position menu selections
				 quart_detailid   = "8187";
				  quart_testname   = "PlaceModeMenuToolbarPosition";
				  quart_description= "menu toolbar position text verify";
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
				// Write to file for checking manage content type page
			 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-position')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
					
					// check drop down selection for View As options
			 		//selenium.select("name=contentAction", "label=Go To Page");
					 			
					// place them into a string array
					String[] positionValues = selenium.getSelectOptions("//select[contains(@name, 'position')]");
								
								// verify if the Current Status exists in the selection list 
					boolean menuPostionValues  = ArrayUtils.contains(positionValues, "Before");
					boolean menuPostionValues1 = ArrayUtils.contains(positionValues, "After");
					boolean menuPostionValues2 = ArrayUtils.contains(positionValues, "Under");

					quart_detailid   = "8202";
					quart_testname   = "PlaceModeMenuToolbarPositionBefore";
					quart_description= "verify menu position before selection";
					if (menuPostionValues)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "8203";
					quart_testname   = "PlaceModeMenuToolbarPositionAfter";
					quart_description= "verify menu position after selection";
					if (menuPostionValues1)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
					quart_detailid   = "8188";
					quart_testname   = "PlaceModeMenuToolbarPositionUnder";
					quart_description= "verify menu position under selection";
					if (menuPostionValues2)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
					
					// check for the position menu location
					 quart_detailid   = "8189";
					  quart_testname   = "PlaceModeMenuToolbarLocationSelection";
					  quart_description= "form mode - menu toolbar location selection verify";
					
					 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
					// Write to file for checking manage content type page
				 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-location')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 		
				 		
				 		// capture the locations selections for menu toolbar
				 		// place them into a string array
						String[] locationValues = selenium.getSelectOptions("//select[contains(@name, 'location')]");
									
									// verify if the Current Status exists in the selection list 
						boolean menuLocationValues  = ArrayUtils.contains(locationValues, "Primary");
						boolean menuLocationValues1 = ArrayUtils.contains(locationValues, "\u00a0\u00a0Home");
						boolean menuLocationValues2 = ArrayUtils.contains(locationValues, "\u00a0\u00a0Categories");
						boolean menuLocationValues3 = ArrayUtils.contains(locationValues, "\u00a0\u00a0Search");
						boolean menuLocationValues4 = ArrayUtils.contains(locationValues, "\u00a0\u00a0Login/Logout");
						boolean menuLocationValues5 = ArrayUtils.contains(locationValues, "Sidebar");
						boolean menuLocationValues6 = ArrayUtils.contains(locationValues, "Sitemap");

						quart_detailid   = "8189";
						quart_testname   = "PlaceModeMenuToolbarLocationPrimary";
						quart_description= "verify menu location primary";
						if (menuLocationValues)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "8189";
						quart_testname   = "PlaceModeMenuToolbarLocationHome";
						quart_description= "verify menu location home";
						if (menuLocationValues1)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "8189";
						quart_testname   = "PlaceModeMenuToolbarLocationCategories";
						quart_description= "verify menu location categories";
						if (menuLocationValues2)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
						quart_detailid   = "8189";
						quart_testname   = "PlaceModeMenuToolbarLocationSearch";
						quart_description= "verify menu location search";
						if (menuLocationValues3)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "8189";
						quart_testname   = "PlaceModeMenuToolbarLocationLogin-Logout";
						quart_description= "verify menu location login/logout";
						if (menuLocationValues4)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "8189";
						quart_testname   = "PlaceModeMenuToolbarLocationSidebar";
						quart_description= "verify menu location sidebar";
						if (menuLocationValues5)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
						quart_detailid   = "8189";
						quart_testname   = "PlaceModeMenuToolbarLocationSitemap";
						quart_description= "verify menu location sitemap";
						if (menuLocationValues6)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				 		
					// check for the position remove
					 quart_detailid   = "8190";
					  quart_testname   = "PlaceModeMenuToolbarRemoveLink";
					  quart_description= "menu toolbar remove link verify";
					
					 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
					// Write to file for checking manage content type page
				 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-remove')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
		 
		
				 		
		 
				 		
			
			// Menus Form mode verify
				 		
			// click form mode and verify all elements
			selenium.click("id=add-content-toolbar-button-form_label");
			selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
			Thread.sleep(3000);
		
				
		  quart_detailid   = "8191";
		  quart_testname   = "FormModeMenusText";
		  quart_description= "form mode - menus text";
		
		 // Write to file for checking manage content type page
			if (selenium.isTextPresent("Menus"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			 quart_detailid   = "8192";
			  quart_testname   = "FormModeMenusLabelText";
			  quart_description= "form mode - menus label text";
			
			 // Write to file for checking manage content type page
				if (selenium.isTextPresent("Label"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
				
				quart_detailid   = "8194";
				  quart_testname   = "FormModeMenusUseContentEntry";
				  quart_description= "form mode - menus use content entry";
				
				 // Write to file for checking manage content type page
					if (selenium.isTextPresent("Use content entry's title"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
					
					// check for the action menu selections
					 quart_detailid   = "8193";
					  quart_testname   = "FormModeMenuToolbarCheckbox";
					  quart_description= "form mode - menu toolbar checkbox verify";
					
				 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-autoLabel')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				 		
					
					// check for the action menu selections
					 quart_detailid   = "8199";
					  quart_testname   = "FormModeMenuToolbarAddMenuItemButton";
					  quart_description= "form mode - menu toolbar add menu item button verify";
					
				 		if (selenium.isElementPresent(("//span[contains(@id, 'menus-addMenuItem_label')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 		 		
				 
				// check for the action menu selections
					 quart_detailid   = "10369";
					  quart_testname   = "FormModeMenuToolbarActionText";
					  quart_description= "form mode - menu toolbar action text verify";
					
				 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-contentAction')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				 		
				 		
			 		
				 	// check drop down selection for View As options				 			
						// place them into a string array
						String[] formModeViewAsValues = selenium.getSelectOptions("//select[contains(@name, 'contentAction')]");
									
									// verify if the Current Status exists in the selection list 
						boolean formModecontentValues  = ArrayUtils.contains(formModeViewAsValues, "Go To Page");
						boolean formModecontentValues1 = ArrayUtils.contains(formModeViewAsValues, "View Image");
						boolean formModecontentValues2 = ArrayUtils.contains(formModeViewAsValues, "Download File");

						quart_detailid   = "10370";
						quart_testname   = "FormModeMenuToolbarGoToPageSelection";
						quart_description= "form mode - create link go to page selection";
						if (formModecontentValues)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "10371";
						quart_testname   = "FormModeMenuToolbarViewAsImageSelection";
						quart_description= "form mode - create link view as image selection";
						if (formModecontentValues1)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						quart_detailid   = "10372";
						quart_testname   = "FormModeMenuToolbarDownloadFileSelection";
						quart_description= "form mode - create link download file selection";
						if (formModecontentValues2)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
						
						// check for the position menu selections
						 quart_detailid   = "8195";
						  quart_testname   = "FormModeMenuToolbarPosition";
						  quart_description= "form mode - menu toolbar position text verify";
						
						 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
						// Write to file for checking manage content type page
					 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-position')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
							// place them into a string array
							String[] formModePositionValues = selenium.getSelectOptions("//select[contains(@name, 'position')]");
										
										// verify if the Current Status exists in the selection list 
							boolean formModeMenuPositionValues  = ArrayUtils.contains(formModePositionValues, "Before");
							boolean formModeMenuPositionValues1 = ArrayUtils.contains(formModePositionValues, "After");
							boolean formModeMenuPositionValues2 = ArrayUtils.contains(formModePositionValues, "Under");

							quart_detailid   = "8200";
							quart_testname   = "MenuToolbarPositionBefore";
							quart_description= "verify menu position before selection";
							if (formModeMenuPositionValues)
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
							quart_detailid   = "8201";
							quart_testname   = "MenuToolbarPositionAfter";
							quart_description= "verify menu position after selection";
							if (formModeMenuPositionValues1)
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
							quart_detailid   = "8196";
							quart_testname   = "MenuToolbarPositionUnder";
							quart_description= "verify menu position under selection";
							if (formModeMenuPositionValues2)
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
							// check for the position menu location
							 quart_detailid   = "8197";
							  quart_testname   = "FormModeMenuToolbarLocationSelection";
							  quart_description= "form mode - menu toolbar location selection verify";
							
							 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
							// Write to file for checking manage content type page
						 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-location')]")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					 		
						 		
						 		
						 	// capture the location selections for menus toolbar 
						 	// place them into a string array
								String[] formModelocationValues = selenium.getSelectOptions("//select[contains(@name, 'location')]");
											
											// verify if the Current Status exists in the selection list 
								boolean formModeMenuLocationValues  = ArrayUtils.contains(formModelocationValues, "Primary");
								boolean formModeMenuLocationValues1 = ArrayUtils.contains(formModelocationValues, "\u00a0\u00a0Home");
								boolean formModeMenuLocationValues2 = ArrayUtils.contains(formModelocationValues, "\u00a0\u00a0Categories");
								boolean formModeMenuLocationValues3 = ArrayUtils.contains(formModelocationValues, "\u00a0\u00a0Search");
								boolean formModeMenuLocationValues4 = ArrayUtils.contains(formModelocationValues, "\u00a0\u00a0Login/Logout");
								boolean formModeMenuLocationValues5 = ArrayUtils.contains(formModelocationValues, "Sidebar");
								boolean formModeMenuLocationValues6 = ArrayUtils.contains(formModelocationValues, "Sitemap");

								quart_detailid   = "8197";
								quart_testname   = "FormModeMenuToolbarLocationPrimary";
								quart_description= "verify menu location primary";
								if (formModeMenuLocationValues)
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
								else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
								quart_detailid   = "8197";
								quart_testname   = "FormModeMenuToolbarLocationHome";
								quart_description= "verify menu location home";
								if (formModeMenuLocationValues1)
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
								else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
								quart_detailid   = "8197";
								quart_testname   = "FormModeMenuToolbarLocationCategories";
								quart_description= "verify menu location categories";
								if (formModeMenuLocationValues2)
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
								else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
								quart_detailid   = "8197";
								quart_testname   = "FormModeMenuToolbarLocationSearch";
								quart_description= "verify menu location search";
								if (formModeMenuLocationValues3)
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
								else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
								quart_detailid   = "8197";
								quart_testname   = "FormModeMenuToolbarLocationLogin-Logout";
								quart_description= "verify menu location login/logout";
								if (formModeMenuLocationValues4)
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
								else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
								quart_detailid   = "8197";
								quart_testname   = "FormModeMenuToolbarLocationSidebar";
								quart_description= "verify menu location sidebar";
								if (formModeMenuLocationValues5)
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
								else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
								quart_detailid   = "8197";
								quart_testname   = "FormModeMenuToolbarLocationSitemap";
								quart_description= "form mode - verify menu location sitemap";
								if (formModeMenuLocationValues6)
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
								else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						 						 		
						 		
						// check for the position remove
						 quart_detailid   = "8198";
						  quart_testname   = "FormModeMenuToolbarRemoveLink";
						  quart_description= "form mode - menu toolbar remove link verify";
						
						 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
						// Write to file for checking manage content type page
					 		if (selenium.isElementPresent(("//label[contains(@for, 'menus-0-remove')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
						
			backToHome();
	}
}

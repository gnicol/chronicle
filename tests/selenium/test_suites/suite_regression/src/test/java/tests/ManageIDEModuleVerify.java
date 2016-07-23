	package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;

//This code clicks on manage --> modules and verifies the analytics title

public class ManageIDEModuleVerify extends shared.BaseTest {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="ManageIDEModuleVerify";

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
		ManageIDEModuleVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		selenium.waitForPageToLoad("30000");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void ManageIDEModuleVerify() throws Exception {
			
			// go to manage modules
			// IDE modules
				manageMenu();
				selenium.click(CMSConstants.MANAGE_MODULES);
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				
				selenium.clickAt("css=div.row-id-ide span.dijitDropDownButton","");
				Thread.sleep(3000);
				
				// enable IDE module
				selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_6-button-action_label')]");  
		     	Thread.sleep(3000);
		   
				// click on the Manage IDE link
				manageMenu();
				Thread.sleep(2000);
				selenium.click(CMSConstants.MANAGE_IDE);
				Thread.sleep(5000);
				
				
				
				
				
				String quart_detailid = "10224";
				String quart_testname = "ManageIDEModuleNewFile";
				String quart_description = "verify IDE New File";
				if (selenium.isTextPresent("New File"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				quart_detailid = "10226";
				quart_testname = "ManageIDEModuleNewFolder";
				quart_description = "verify IDE New Folder";
				if (selenium.isTextPresent("New Folder"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				quart_detailid = "10228";
				quart_testname = "ManageIDEModuleNewPackage";
				quart_description = "verify IDE New Package";
				if (selenium.isTextPresent("New Package"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				quart_detailid = "10230";
				quart_testname = "ManageIDEModuleOpenRecent";
				quart_description = "verify IDE Open Recent";
				if (selenium.isTextPresent("Open Recent"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				
				quart_detailid = "10233";
				quart_testname = "ManageIDEModuleSaveFile";
				quart_description = "verify IDE Save File";
				if (selenium.isTextPresent("Save File"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				quart_detailid = "10235";
				quart_testname = "ManageIDEModuleChangeTheme";
				quart_description = "verify IDE Change Theme";
				if (selenium.isTextPresent("Change Theme"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
/*				
				// expand the all section
				selenium.click("css=img.dijitTreeExpando.dijitTreeExpandoClosed");
				Thread.sleep(2000);
				
				quart_detailid = "10237";
				quart_testname = "ManageIDEModuleExpandTree";
				quart_description = "verify IDE Expand Tree";
				if (selenium.isTextPresent("analytics"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
				
				quart_detailid = "10242";
				quart_testname = "ManageIDEModuleVerifyExpandTree";
				quart_description = "verify IDE Expand Tree";
				if (selenium.isTextPresent("youtube"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
	*/			/*// expand themes
				selenium.click("css=div.dijitTreeRow.dijitTreeRowHover > img.dijitTreeExpando.dijitTreeExpandoClosed");
				quart_detailid = "10243";
				quart_testname = "ManageIDEModuleExpandThemes";
				quart_description = "verify IDE Expand Themes";
				if (selenium.isTextPresent("spring"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				*/
				
				// context click on IDE menu
		 		selenium.contextMenu("//div[2]/div[3]/div/div[2]/div[2]/div/div");
		 		Thread.sleep(1000);
		 		
		 		selenium.clickAt("id=dijit_MenuItem_30_text","");
		 		Thread.sleep(1000);
		 		
		 		quart_detailid = "10382";
				quart_testname = "ManageIDEModuleCopyDialogText";
				quart_description = "verify IDE Copy Dialog";
				if (selenium.isTextPresent("Copy"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
				quart_detailid = "10389";
				quart_testname = "ManageIDEModuleCopyFromText";
				quart_description = "verify IDE Copy Dialog";
				if (selenium.isTextPresent("From"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
				quart_detailid = "10392";
				quart_testname = "ManageIDEModuleCopytoText";
				quart_description = "verify IDE Copy Dialog";
				if (selenium.isTextPresent("To"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
				quart_detailid = "10391";
				quart_testname = "ManageIDEModuleCopyDialogText";
				quart_description = "verify IDE Copy Dialog";
				if (selenium.isTextPresent("Copy"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
				
				
				quart_detailid = "10393";
				quart_testname = "ManageIDEModuleToInput";
				quart_description = "verify IDE To Input";
				if (selenium.isElementPresent("//input[contains(@id, 'target')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
				 // verify 'x' tooltip
				quart_detailid   = "10385";
				quart_testname   = "ManageIDEModuleCopyTooltip";
				quart_description= "verify 'x' tooltip for Copy";
				
				// get tooltip attribute
				String tooltip1 = selenium.getAttribute("//div[7]/div/span[2]/@title");
				//String tooltip1 = selenium.getText("css=.dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon[title]"); 
				
				boolean tooltip1True =	tooltip1.equals("Cancel");
		 				
				if (tooltip1True)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			
			
				selenium.click("//div[7]/div/span[2]");
				Thread.sleep(1000);
				
			 
				// code for the delete dialog
				
				
				
				
				// collapse the tree
				selenium.click("//div[@id='dijit__TreeNode_1']/div/img");
				Thread.sleep(1000);
				
				quart_detailid = "10391";
				quart_testname = "ManageIDEModuleFromInput";
				quart_description = "verify IDE From Input";
				if (selenium.isElementPresent("//input[contains(@id, 'source')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				} 
				
				
				
				
				
				// click on new folder
				//selenium.click("css=.index-action .dijitLayoutContainer .dijitAlignTop .dijitToolbar .dijitButton .dijitButtonNode .dijitButtonContents");
				selenium.click("id=dijit_form_Button_1_label");
				Thread.sleep(2000);
				
				 quart_detailid = "10245";
				 quart_testname = "ManageIDEModuleNewFolderText";
				 quart_description = "verify Folder text";
				if (selenium.isTextPresent("New Folder"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				
				 quart_detailid = "10250";
				 quart_testname = "ManageIDEModuleNewFolderPath";
				 quart_description = "verify Folder path";
				if (selenium.isTextPresent("Path"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				 quart_detailid = "10248";
				 quart_testname = "ManageIDEModuleNewFolderName";
				 quart_description = "verify Folder name";
				if (selenium.isTextPresent("Name"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				
				 quart_detailid = "10249";
				 quart_testname = "ManageIDEModuleNewFolderInput";
				 quart_description = "verify Folder name input";
					if (selenium.isElementPresent("//input[contains(@id, 'name')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				
				 quart_detailid = "10251";
				 quart_testname = "ManageIDEModuleNewFolderPath";
				 quart_description = "verify Folder path input";
					if (selenium.isElementPresent("//input[contains(@id, 'path')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
				
				
					 // verify 'x' tooltip
					quart_detailid   = "10247";
					quart_testname   = "ManageIDEModuleNewFolderTooltip";
					quart_description= "verify 'x' tooltip for New Folder";
					
					// get tooltip attribute
					String tooltip2 = selenium.getAttribute("//div[10]/div/span[2]/@title");
					//String tooltip1 = selenium.getText("css=.dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon[title]"); 
					
					boolean tooltip2True =	tooltip2.equals("Cancel");
			 				
					if (tooltip2True)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				
					selenium.click("//div[10]/div/span[2]");
					Thread.sleep(1000);
					
					
					// click on new package
					//selenium.click("css=.index-action .dijitLayoutContainer .dijitAlignTop .dijitToolbar .dijitButton .dijitButtonNode .dijitButtonContents");
					selenium.click("id=dijit_form_Button_2_label");
					Thread.sleep(2000);
					
					 quart_detailid = "10255";
					 quart_testname = "ManageIDEModulePackageText";
					 quart_description = "verify Package text";
					if (selenium.isTextPresent("New Package"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
					}
					
					
					 quart_detailid = "10258";
					 quart_testname = "ManageIDEModulePackageType";
					 quart_description = "verify Package type";
					if (selenium.isTextPresent("Type"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
					}
					
					 quart_detailid = "10260";
					 quart_testname = "ManageIDEModulePackageName";
					 quart_description = "verify Package name";
					if (selenium.isTextPresent("Name"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
					}
					
					 quart_detailid = "10262";
					 quart_testname = "ManageIDEModulePackageDesc";
					 quart_description = "verify Package desc";
					if (selenium.isTextPresent("Description"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
					}
					
					 quart_detailid = "10264";
					 quart_testname = "ManageIDEModulePackageTags";
					 quart_description = "verify Package tags";
					if (selenium.isTextPresent("Tags"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
					}
					
					
				 quart_detailid = "10261";
				 quart_testname = "ManageIDEModulePackageNameInput";
				 quart_description = "verify Package name input";
					if (selenium.isElementPresent("//input[contains(@id, 'name')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
				else {
					writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
				}
					
					 quart_detailid = "10263";
					 quart_testname = "ManageIDEModulePackageDescInput";
					 quart_description = "verify Package desc input";
						if (selenium.isElementPresent("//textarea[contains(@id, 'description')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
					}
						
					 quart_detailid = "10265";
					 quart_testname = "ManageIDEModulePackageTagsInput";
					 quart_description = "verify Package tags input";
						if (selenium.isElementPresent("//input[contains(@id, 'tags')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
					else {
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
					}
						
						 quart_detailid = "10259";
						 quart_testname = "ManageIDEModulePackageModule";
						 quart_description = "verify Package module selector";
							if (selenium.isElementPresent("//select[contains(@id, 'type')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
				
				
							
						 // verify 'x' tooltip
						quart_detailid   = "10257";
						quart_testname   = "ManageIDEModulePackageTooltip";
						quart_description= "verify 'x' tooltip for New Package";
						
						// get tooltip attribute
						String tooltip3 = selenium.getAttribute("//div[12]/div/span[2]/@title");
						//String tooltip1 = selenium.getText("css=.dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon[title]"); 
						
						boolean tooltip3True =	tooltip3.equals("Cancel");
				 				
						if (tooltip3True)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
					
						selenium.click("//div[12]/div/span[2]");
						Thread.sleep(1000);
							
					
						// click new file
						selenium.click("id=dijit_form_Button_0_label");
						
						// click save file
						selenium.click("id=dijit_form_Button_3_label");
						Thread.sleep(1000);
						
						
						 quart_detailid = "10269";
						 quart_testname = "ManageIDEModuleSaveAsText";
						 quart_description = "verify Save As text";
							if (selenium.isTextPresent("Save As"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
				
						 quart_detailid = "10272";
						 quart_testname = "ManageIDEModuleSaveAsName";
						 quart_description = "verify Package module selector";
							if (selenium.isTextPresent("Name"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
				
						
						quart_detailid = "10274";
						 quart_testname = "ManageIDEModuleSaveAsPath";
						 quart_description = "verify Save As Path";
							if (selenium.isTextPresent("Path"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
						}
		
						 quart_detailid = "10273";
						 quart_testname = "ManageIDEModuleSaveAsInputName";
						 quart_description = "verify Package module selector";
							if (selenium.isElementPresent("//input[contains(@id, 'name')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
						else {
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description); 
						}
				
							 quart_detailid = "10275";
							 quart_testname = "ManageIDEModuleSaveAsInputPath";
							 quart_description = "verify Package module selector";
								if (selenium.isElementPresent("//input[contains(@id, 'path')]"))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
							else {
								writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
							}
					
					
							 // verify 'x' tooltip
							quart_detailid   = "10271";
							quart_testname   = "ManageIDEModuleSaveAsTooltip";
							quart_description= "verify 'x' tooltip for Save As";
							
							// get tooltip attribute
							String tooltip4 = selenium.getAttribute("//div[15]/div/span[2]/@title");
							//String tooltip1 = selenium.getText("css=.dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon[title]"); 
							
							boolean tooltip4True =	tooltip4.equals("Cancel");
					 				
							if (tooltip4True)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
						
							selenium.click("//div[15]/div/span[2]");
							Thread.sleep(1000);
								
/*						
							quart_detailid = "10276";
							 quart_testname = "ManageIDEModuleSaveAsCancelButton";
							 quart_description = "verify Package module selector";
								if (selenium.isElementPresent("//css[contains(@class, '.dijitDialog .dijitDialogPaneContent .display-group .buttons.cancel-element .dijitButton .dijitButtonNode')]"))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname,quart_description);
							else {
								writeFile(quart_detailid, "fail", quart_scriptname, quart_testname,quart_description);
							}
				
*/						
						manageMenu();
						selenium.click(CMSConstants.MANAGE_MODULES);
						waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
						
						selenium.clickAt("css=div.row-id-ide span.dijitDropDownButton","");
						Thread.sleep(3000);
			
						selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_6-button-action_label')]");  
				     	Thread.sleep(3000);
				 
				     	
						
					
		// back to WebSite
		backToHome();
	}
}

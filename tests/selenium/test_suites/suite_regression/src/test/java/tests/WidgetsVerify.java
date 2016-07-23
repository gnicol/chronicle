package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


public class WidgetsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "WidgetsVerify";

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
		          
		// verify widgets
		WidgetsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");
	}
	
	public void WidgetsVerify() throws Exception {
		
		selenium.open(baseurl);
		waitForElements("link=Home");
		
		// verify widgets gear icons
		// click on a widget
		selenium.click("css=span.menu-icon.manage-toolbar-widgets");
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitStretch dijitButtonContents')]")));  
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitIcon plusIcon')]")));  

		// click on a widget
		selenium.click("//span[@id='dijit_form_Button_1']/span");
		selenium.click("xpath=(//input[@value=''])[2]");
		waitForText("Add Widget");
		
		 String quart_detailid   = "9717";
		 String quart_testname   = "WidgetsDialog";
		 String quart_description= "verify widgets elements";
		
		// Write to file for checking manage content type page
		 if (selenium.isTextPresent("Add Widget"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
		 
		 Thread.sleep(2000);
		 selenium.click("link=Image Rotator Widget");
		 Thread.sleep(2000);
		 
		 quart_detailid = "11080";
		 quart_testname = "WidgetsRotatorText";
		 quart_description="verify rotator widget dialog text";
		 if (selenium.isTextPresent("Configure Image Rotator Widget"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
		 quart_detailid = "11067";
		 quart_testname = "WidgetsRotatorTitleText";
		 quart_description="verify rotator widget title text";
		 if (selenium.isTextPresent("Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11069";
		 quart_testname = "WidgetsRotatorShowTitleText";
		 quart_description="verify rotator widget show title text";
		 if (selenium.isTextPresent("Show Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11071";
		 quart_testname = "WidgetsRotatorOrderText";
		 quart_description="verify rotator widget order text";
		 if (selenium.isTextPresent("Order"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11074";
		 quart_testname = "WidgetsRotatorCSSClassText";
		 quart_description="verify rotator widget css class text";
		 if (selenium.isTextPresent("CSS Class"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11077";
		 quart_testname = "WidgetsRotatorLoadText";
		 quart_description="verify rotator widget load text";
		 if (selenium.isTextPresent("Show Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11073";
		 quart_testname = "WidgetsRotatorOrderText1";
		 quart_description="verify rotator widget order text";
		 if (selenium.isTextPresent("Adjust the position of this widget in the region."))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11076";
		 quart_testname = "WidgetsRotatorCSSText1";
		 quart_description="verify rotator widget css class text";
		 if (selenium.isTextPresent("Specify a CSS class to customize the appearance of this widget."))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		 quart_detailid = "11079";
		 quart_testname = "WidgetsRotatorLoadText1";
		 quart_description="verify rotator widget load text";
		 if (selenium.isTextPresent("Load this widget after the rest of the page."))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11086";
		 quart_testname = "WidgetsRotatorImagesText";
		 quart_description="verify rotator widget images text";
		 if (selenium.isTextPresent("Show Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11068";
		 quart_testname = "WidgetsRotatorFormText";
		 quart_description="verify rotator widget form text";
		 if (selenium.isTextPresent("Image Rotator Widget"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11090";
		 quart_testname = "WidgetsRotatorCaptionText";
		 quart_description="verify rotator widget show title text";
		 if (selenium.isTextPresent("Caption"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11092";
		 quart_testname = "WidgetsRotatorLinkText";
		 quart_description="verify rotator widget link text";
		 if (selenium.isTextPresent("Link"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11070";
		 quart_testname = "WidgetsRotatorShowTitleCheckbox";
		 quart_description="verify rotator widget show title checkbox";
		 if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'showTitle') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11072";
		 quart_testname = "WidgetsRotatorOrderSelection";
		 quart_description="verify rotator widget show title checkbox";
		 if (selenium.isElementPresent("//select[contains(@name, 'order') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		 
		 
			// place order them into a string array
			String[] currentSelection = selenium.getSelectOptions("//select[contains(@name, 'order')]");
					
					// verify if the Current Status exists in the selection list 
			boolean selectedValue = ArrayUtils.contains(currentSelection, "0");
				    
			quart_detailid   = "11072";  
			quart_testname   = "WidgetsRotatorOrderSelected";
			quart_description= "verify widgets order selection";
			// verify that order is selected
				if (selectedValue)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
				
		 quart_detailid = "11075";
		 quart_testname = "WidgetsRotatorCSSClassInput";
		 quart_description="verify rotator widget css class input";
		 if (selenium.isElementPresent("//input[@type='text' and contains(@value, '') and contains(@name, 'class') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11078";
		 quart_testname = "WidgetsRotatorLoadCheckbox";
		 quart_description="verify rotator widget load checkbox";
		 if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'asynchronous') ]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11087";
		 quart_testname = "WidgetsRotatorImagesInput";
		 quart_description="verify rotator widget images input";
		 if (selenium.isElementPresent("//input[@type='text' and contains(@readonly, 'readonly')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11091";
		 quart_testname = "WidgetsRotatorCaptionInput";
		 quart_description="verify rotator widget caption input";
		 if (selenium.isElementPresent("//input[@type='text' and contains(@name, 'config[images][1][caption]')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11093";
		 quart_testname = "WidgetsRotatorLinkInput";
		 quart_description="verify rotator widget caption input";
			 if (selenium.isElementPresent("//input[contains(@name, 'config[images][1][link]')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11088";
		 quart_testname = "WidgetsRotatorBrowseButton";
		 quart_description="verify rotator widget browse button";
		 if (selenium.isElementPresent("//span[contains(@id, 'dijit_form_Button_6_label')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11084";
		 quart_testname = "WidgetsRotatorSaveButton";
		 quart_description="verify rotator widget save button";
		 if (selenium.isElementPresent("//span[contains(@id, 'config-save')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 quart_detailid = "11082";
		 quart_testname = "WidgetsRotatorCancelButton";
		 quart_description="verify rotator widget cancel button";
		 if (selenium.isElementPresent("//span[contains(@id, 'config-cancel')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			  
		 
		 
		 quart_detailid = "11066";
		 quart_testname = "WidgetsRotatorOptionsText";
		 quart_description="verify rotator widget text";
		 if (selenium.isTextPresent("General Options"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
		 
		 quart_detailid = "11085";
		 quart_testname = "WidgetsRotatorOptions";
		 quart_description="verify rotator widget options text";
		 if (selenium.isTextPresent("Image Rotator Widget Options"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
/*		 
		  	// verify 'x' tooltip
			quart_detailid   = "11081";
			quart_testname   = "WidgetsRotator_x_tooltip";
			quart_description= "verify search rebuild 'x' tooltip";
			
			// get tooltip attribute
			String tooltip = selenium.getAttribute("//div[71]/div/span[2]/@title");

			boolean tooltipTrue = tooltip.equals("Cancel");
		
			if (tooltipTrue) 
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			
	*/	 
			 quart_detailid = "11083";
			 quart_testname = "WidgetsRotatorCloseIcon";
			 quart_description="verify rotator widget close icon";
			 if (selenium.isElementPresent("//span[contains(@class, 'dijitDialogCloseIcon')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					  
		 
			 
			 
			 // Configure Content List widget
			 
			 	selenium.open(baseurl);
				waitForElements("link=Home");
				
				// click on widgets
				selenium.click("css=span.menu-icon.manage-toolbar-widgets");
				
				selenium.click("//span[@id='dijit_form_Button_1']/span");
				selenium.click("xpath=(//input[@value=''])[2]");
				Thread.sleep(2000);
				
				 selenium.click("link=Content List");
				 Thread.sleep(2000);
			 
				 quart_detailid   = "8732";
				  quart_testname   = "WidgetsContentListTitleText";
				  quart_description= "verify widgets content list title text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Title"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				 quart_detailid   = "8742";
				  quart_testname   = "WidgetsContentListShowTitleText";
				  quart_description= "verify widgets content list show title text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Show Title"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				 
				 quart_detailid   = "8741";
				  quart_testname   = "WidgetsContentListMaxItemsText";
				  quart_description= "verify widgets content list max items text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Maximum Items"))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				 
				 quart_detailid   = "8740";
				  quart_testname   = "WidgetsContentListMaxItemsText1";
				  quart_description= "verify widgets content list max items text";
				
				// Write to file for checking manage content type page
				 if (selenium.isTextPresent("Enter the maximum number of content entries to display."))
				 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				// place order them into a string array
					String[] currentSelection1 = selenium.getSelectOptions("//select[contains(@name, 'config[count]')]");
							
							// verify if the Current Status exists in the selection list 
					boolean selectedValue1 = ArrayUtils.contains(currentSelection1, "10");
						    
					quart_detailid   = "8743";  
					quart_testname   = "WidgetsContentListMaxItemsSelector";
					quart_description= "verify widgets content list max items selection";
					// verify that order is selected
						if (selectedValue1)
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
				
						 quart_detailid   = "8745";
						  quart_testname   = "WidgetsContentListPrimarySortText";
						  quart_description= "verify widgets content list primary sort text";
						
						// Write to file for checking manage content type page
						 if (selenium.isTextPresent("Primary Sort"))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					 
						 
						 quart_detailid   = "8746";
						  quart_testname   = "WidgetsContentListSecondarySortText";
						  quart_description= "verify widgets content list secondary sort text";
						
						// Write to file for checking manage content type page
						 if (selenium.isTextPresent("Secondary Sort"))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
						 quart_detailid   = "11094";
						  quart_testname   = "WidgetsContentListShowRSSText";
						  quart_description= "verify widgets content list show rss text";
						
						// Write to file for checking manage content type page
						 if (selenium.isTextPresent("Show RSS Link"))
						 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
						 
						 quart_detailid = "11095";
						 quart_testname = "WidgetsContentListRSSCheckbox";
						 quart_description="verify widgets content list RSS checkbox";
						 if (selenium.isElementPresent("//input[@type='checkbox' and contains(@value, '1') and contains(@name, 'config[showRssLink]') ]"))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
						 
						selenium.click("//input[@name='config[showRssLink]']");
						Thread.sleep(1000);
						
						if (selenium.isTextPresent("Feed Title") && selenium.isTextPresent("Feed Description"))
						{
							 quart_detailid = "11097";
							 quart_testname = "WidgetsContentListFeedTitleInput";
							 quart_description="verify widget content list feed title input";
							 if (selenium.isElementPresent("//input[@type='text' and contains(@value, '') and contains(@name, 'config[feedTitle]') ]"))
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
							 
							 quart_detailid = "11099";
							 quart_testname = "WidgetsContentListFeedDescInput";
							 quart_description="verify widget content list feed desc input";
							 if (selenium.isElementPresent("//textarea[contains(@name, 'config[feedDescription]') ]"))
									writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
							        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							 
							 quart_detailid   = "11096";
							  quart_testname   = "WidgetsContentListFeedTitleText";
							  quart_description= "verify widgets content list feed title text";
							
							// Write to file for checking manage content type page
							 if (selenium.isTextPresent("Feed Title"))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
								
							 quart_detailid   = "11098";
							  quart_testname   = "WidgetsContentListFeedDescText";
							  quart_description= "verify widgets content list feed desc text";
							
							// Write to file for checking manage content type page
							 if (selenium.isTextPresent("Feed Description"))
							 writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
						}
						
	}
}


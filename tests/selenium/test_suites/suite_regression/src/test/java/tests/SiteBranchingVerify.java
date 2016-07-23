package tests;

import java.io.FileWriter;
import java.util.Random;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


// This code logs in and clicks on the Manage --> Manage content and verifies that the Manage content title appears

public class SiteBranchingVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "SiteBranchingVerify";

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
		SiteBranchingVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");  

	}
	
	public void SiteBranchingVerify() throws Exception {
		
			
		//**** VERIFY THE LIVE LINK --> ADD BRANCH, EDIT LIVE BRANCH, MANANGE BRANCHES, and PULL FROM links ****//
		
		// verify add branch dialog
		selenium.click(CMSConstants.LIVE_LINK);
		selenium.click("id=dijit_MenuItem_0_text");
		Thread.sleep(2000);
		
		selenium.isTextPresent("Add Branch");
		selenium.isTextPresent("Branch From");
		
		String quart_detailid   = "9629";
		 String quart_testname   = "LiveLinkAddBranchDialogVerify";
		 String quart_description= "verify live link --> add branch dialog";
		
			if (selenium.isTextPresent(("Add Branch")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			quart_detailid   = "9629";
			 quart_testname   = "LiveLinkAddBranchDialogVerify1";
			 quart_description= "verify live link --> add branch dialog";
			  
			if (selenium.isTextPresent(("Branch From")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		 
			backToHome();
			
			
			// verify edit branch dialog
			selenium.click(CMSConstants.LIVE_LINK);
			selenium.click("id=dijit_MenuItem_1_text");
			Thread.sleep(2000); 
			
			 quart_detailid   = "9629";
			  quart_testname   = "LiveLinkEditBranchDialogVerify";
			  quart_description= "verify live link --> edit branch dialog";
			
				if (selenium.isTextPresent(("Edit Branch")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				quart_detailid   = "9629";
				 quart_testname   = "LiveLinkEditBranchDialogVerify1";
				 quart_description= "verify live link --> edit branch dialog";
				  
				if (selenium.isTextPresent(("Branch Address")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			     else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
				backToHome();
				
								
				// verify manage branch page
				selenium.click(CMSConstants.LIVE_LINK);
				selenium.click("id=dijit_MenuItem_2_text");
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
				
				 quart_detailid   = "9629";
				  quart_testname   = "LiveLinkManageBranchPageVerify";
				  quart_description= "verify live link --> manage branch dialog";
				
				if (selenium.isTextPresent(("Sites and Branches")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				quart_detailid   = "9629";
			    quart_testname   = "LiveLinkManageBranchPageVerify1";
			    quart_description= "verify live link --> manage branch dialog";
				
				 backToHome();
		 
		
				// verify manage branch page
				selenium.click(CMSConstants.LIVE_LINK);
				selenium.click("id=dijit_PopupMenuItem_0_text");
				Thread.sleep(2000); 
				
				 quart_detailid   = "9629";
				  quart_testname   = "LiveLinkPullFromBranchVerify";
				  quart_description= "verify live link --> pull from branch verify";
				
					if (selenium.isTextPresent("No Branches"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					backToHome();
	
						
						
		
		
		//**** ADD BRANCH ****//
		
		// go to site branching
		selenium.click(CMSConstants.SITE_BRANCHING);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 	
		
		//writeFile1("\nskipped: 1044", "", "");
		// click on Add branch
		//selenium.click("id=dijit_MenuItem_1_text");
		selenium.click("id=dijit_form_Button_1_label");
		selenium.click("//input[@value='Add Branch']");
		Thread.sleep(2000);

		 quart_detailid   = "8959";
		  quart_testname   = "SiteBranchingAddBranch";
		  quart_description= "verify add branch dialog";
		
		// Write to file for checking manage content type page
		if (selenium.isTextPresent(("Add Branch")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		 // verify Name text field 
		  quart_detailid   = "8961"; 
		  quart_testname   = "AddBranchName";
		  quart_description= "verify name field";
		
		  if (selenium.isTextPresent(("Name")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  // click Save to verify required name field
		  selenium.click("id=branch-save_label");
		  Thread.sleep(2000);
		  
		  quart_detailid   = "8961";
		  quart_testname   = "AddBranchNameField";
		  quart_description= "verify name field required";
		

		  if (selenium.isTextPresent(("Value is required and can't be empty")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
		  
		// verify branch address with invalid name
		  selenium.type("id=branch-name", "$%*");
		  selenium.click("id=branch-save_label");
		  Thread.sleep(2000);
		  
		  quart_detailid   = "9443";
		  quart_testname   = "AddBranchInvalidName";
		  quart_description= "verify invalid chars for name";
		
			if (selenium.isTextPresent(("Name must contain at least one letter or number.")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
		  
		  // verify branch address 
		  quart_detailid   = "8962";
		  quart_testname   = "AddBranchFromField";
		  quart_description= "verify branch from";
		
			if (selenium.isElementPresent(("//select[contains(@id, 'branch-parent')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
			
			
			
		  // verify branch address
		  quart_detailid   = "8963";
		  quart_testname   = "AddBranchAddress";
		  quart_description= "verify branch address";
		
			if (selenium.isElementPresent(("//textarea[contains(@id, 'branch-urls')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
			
		 // verify description
			quart_detailid   = "8963";
			quart_testname   = "AddBranchDesc";
			quart_description= "verify desc";
			
				if (selenium.isElementPresent(("//textarea[contains(@id, 'branch-description')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
				
		 
			// verify text
			quart_detailid   = "8964";
			quart_testname   = "AddBranchText";
			quart_description= "verify text";
			
				if (selenium.isTextPresent(("Optionally provide a list of urls for which this branch will be served.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			  	
			 
			
			 // verify 'x'
			quart_detailid   = "8967";
			quart_testname   = "AddBranch_Click_x_";
			quart_description= "verify 'x' click";
			
				if (selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		  
		  
			
			 // verify save
			quart_detailid   = "8968";
			quart_testname   = "AddBranchSaveButton";
			quart_description= "verify save button";
			
				if (selenium.isElementPresent(("//span[contains(@id, 'branch-save')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				 // verify save
				quart_detailid   = "8968";
				quart_testname   = "AddBranchSaveGrowlMessage";
				quart_description= "verify save growl message";
				
				if(selenium.isVisible(("//div[contains(@id, 'p4cms-ui-notices')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		        else  { writeFile(quart_detailid ,"fail", quart_scriptname,quart_testname, quart_description); }
				
		
			// verify cancel
			quart_detailid   = "8966";
			quart_testname   = "AddBranchCancelbutton";
			quart_description= "verify cancel button";
			
				if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_0')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				 // verify 'x' tooltip
				quart_detailid   = "8960";
				quart_testname   = "AddBranchTooltip";
				quart_description= "verify 'x' tooltip";
				
					String tooltip = selenium.getAttribute("//div[7]/div/span[2]/@title");
					
					boolean tooltipTrue =	tooltip.equals("Cancel");
					
					if (tooltipTrue)
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
				

					quart_detailid   = "9784";
					quart_testname   = "AddBranchDescriptionSpaces";
					quart_description= "verify description with spaces"; 
					
					 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

					selenium.type("id=branch-description", "testing");
					Thread.sleep(1000);
					selenium.type("id=branch-description", "    ");
					Thread.sleep(1000);
					
					if (selenium.isTextPresent("Edit User"))
							writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description );  }
					

					
		
				
				
			// **** EDIT BRANCH **** //
				
				// click manage branches 
				manageMenu();	

				selenium.click("link=Sites and Branches");
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
				
				quart_detailid   = "8970";
				quart_testname   = "SitesBranchesText";
				quart_description= "verify sites and branches text";
				
				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

					if (selenium.isTextPresent(("Sites and Branches")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
					// click on the edit menu for the local site
					selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .branch .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
					Thread.sleep(4000); 							
 
			   // click edit
				selenium.click("id=dijit_MenuItem_7_text");
				Thread.sleep(4000);
			   

				quart_detailid   = "9650";
				quart_testname   = "EditBranchText";
				quart_description= "verify edit branch text";
			
				if (selenium.isTextPresent(("Edit Branch")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		   
   
				// verify branch address with invalid name
				  selenium.type("id=branch-name", "$%*");
				  selenium.click("id=branch-save_label");
				  Thread.sleep(2000);
				  
				  quart_detailid   = "9653";
				  quart_testname   = "EditBranchInvalidName";
				  quart_description= "verify invalid chars for name";
				
					if (selenium.isTextPresent(("Name must contain at least one letter or number.")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				  		
				
				
					quart_detailid   = "9652";
					quart_testname   = "EditBranchNameText";
					quart_description= "verify edit branch name text";
			
				if (selenium.isTextPresent(("Name")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			   
			  
			// verify name is required
			selenium.type("id=branch-name", "");
			selenium.click("id=branch-save_label");
			Thread.sleep(2000);
				
			 quart_detailid   = "9652";
			 quart_testname   = "EditBranchNameRequiredText";
			quart_description= "verify edit branch name required";
				
			if (selenium.isTextPresent(("Value is required and can't be empty")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 
				 
				
/*			quart_detailid   = "9654";
			quart_testname   = "EditBranchFromText";
			quart_description= "verify edit branch 'branch from' text";
			
			 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

				if (selenium.isTextPresent(("Branch From")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		   */
				   
		   
				
			quart_detailid   = "9657";
			quart_testname   = "EditBranchAddressText";
			quart_description= "verify edit branch 'branch address' text";
			
				if (selenium.isTextPresent(("Branch Address")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			   
					   	
			quart_detailid   = "9660";
			quart_testname   = "EditBranchDescText";
			quart_description= "verify edit branch description text";
			
				if (selenium.isTextPresent(("Description")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			   
							   		
			quart_detailid   = "9659";
			quart_testname   = "EditBranchAddressText1";
			quart_description= "verify edit branch address text";
			
				if (selenium.isTextPresent(("Optionally provide a list of urls for which this branch will be served.")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			   Thread.sleep(1000);
						   			
			quart_detailid   = "9659";
			quart_testname   = "EditBranchAddressText2";
			quart_description= "verify edit branch address text";
			
				if (selenium.isTextPresent(("For example: dev.domain.com, stage.domain.com")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			 Thread.sleep(1000); 		
				
				
			quart_detailid   = "9658";
			quart_testname   = "EditBranchURL";
			quart_description= "verify edit branch url";
						 

			// get url string and match to baseurl	
			String currentURL = selenium.getLocation();
			// split url to remove the trailing section
			String matchURL[]  = currentURL.split("/site/branch/manage"); 
			String branchURL = matchURL[0]; 
	 			
			// get the edit branch url & append the http://
			String editBranchURL = selenium.getText("//*[@id='branch-urls']");
			
			editBranchURL = "http://" + editBranchURL; 
			
			if (editBranchURL.equalsIgnoreCase(branchURL))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
			
			quart_detailid   = "9661";
			quart_testname   = "EditBranchDescTextarea";
			quart_description= "verify edit branch desc textarea";
			
			 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

			if (selenium.isElementPresent(("//textarea[contains(@id, 'branch-description')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			   		
			

			/*quart_detailid   = "9656";
			quart_testname   = "EditBranchDropdown";
			quart_description= "verify edit branch dropdown";
			
			 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

			if (selenium.isElementPresent(("//select[contains(@id, 'branch-parent') and contains(@name, 'parent') and contains(@disabled, '1')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			   		*/
							
				
			quart_detailid   = "9651";
			quart_testname   = "EditBranchTooltip";
			quart_description= "verify edit branch tooltip";
			
			String tooltip4 = selenium.getAttribute("//div[7]/div/span[2]/@title");
			 
			boolean tooltip4True =	tooltip4.equals("Cancel");
				
				if (tooltip4True)
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
			// save button for edit branch
				quart_detailid   = "9664";
				quart_testname   = "EditBranchSaveButton";
				quart_description= "verify edit branch save button";

				if (selenium.isElementPresent(("//span[contains(@id, 'branch-save_label')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				   		
				
				
				// cancel button for edit branch
				quart_detailid   = "9662";
				quart_testname   = "EditBranchCancelButton";
				quart_description= "verify edit branch cancel button";
				

				if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_1_label')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				   				
				
					quart_detailid   = "8972";
					quart_testname   = "EditBranchEntriesText";
					quart_description= "verify entries text";
					
						if (selenium.isTextPresent(("entries")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
				quart_detailid   = "8971";
				quart_testname   = "EditBranchColumnNameText";
				quart_description= "verify name text";
			
					if (selenium.isTextPresent(("Name")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			
			
				quart_detailid   = "8971";
				quart_testname   = "EditBranchColumnOwner";
				quart_description= "verify column owner text";
			
					if (selenium.isTextPresent(("Owner")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
			 /*	quart_detailid = "9663";
			 	quart_testname = " 'x' close";
			 	quart_description = "verfiy 'x' close";
			 	
			 	if(selenium.isElementPresent(("//span[contains(@class, 'dijitDialogCloseIcon')]")))
			 		 	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			 	else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					
				selenium.click("css=span.dijitDialogCloseIcon.dijitDialogCloseIconHover");
				
				quart_detailid = "9663";
			 	quart_testname = " 'x' close icon click";
			 	quart_description = "verfiy 'x' close icon click";
			 	
			 	if(selenium.isTextPresent(("Sites and Branches")))
			 		 	writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description);
			 	else { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
					*/
			 	
					
					
					// **** ADD SITE / SITE STORAGE & ADMINISTRATION ****//
					
					// site storage
					// go to site branching
					manageMenu();
					selenium.click(CMSConstants.ADD_SITE);
					waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
					
					selenium.click("//span[contains(@id, 'dijit_form_Button_0_label')]");		
					Thread.sleep(2000);
					
					
					// check radio buttons not clickable
					  quart_detailid   = "8888";
						quart_testname   = "SiteStorageServerType";
						quart_description= "verify site storage";
					
						if (selenium.isElementPresent("//input[@id='serverType-new' and contains(@disabled, '1')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
						

						// check radio buttons not clickable & selected
						assertTrue(selenium.isElementPresent("//input[@id='serverType-existing' and contains(@checked, 'checked') and contains(@disabled, '1')]"));
						quart_detailid   = "8889";
						quart_testname   = "SiteStorageServerTypeExists";
						quart_description= "verify site storage";


						if (selenium.isElementPresent("//input[@id='serverType-existing' and contains(@checked, 'checked') and contains(@disabled, '1')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
							
						// verify local server
						quart_detailid   = "8892";
						quart_testname   = "SiteStorageLocalServer";
						quart_description= "verify local server";
						
						if (selenium.isTextPresent("Local Server"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						

						
						// verify local server filled in
						quart_detailid   = "8890";
						quart_testname   = "SiteStorageLocalServerInfo";
						quart_description= "verify local server filled in";
						
						if (selenium.isElementPresent("//input[@type='text' and contains(@id, 'port') and contains(@value, '/latest/p4chronicle/data/perforce') and contains(@disabled, '1')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
						
						
						// verify server text
						quart_detailid   = "8891";
						quart_testname   = "SiteStorageText";
						quart_description= "verify perforce server text";
						
						if (selenium.isTextPresent("You have already configured a Perforce Server."))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

						
						// go back to main site
						backToHome();
						
							
						
						
						
					    // administrator
						// go to site branching
						manageMenu();
						selenium.click(CMSConstants.ADD_SITE);
						waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
						
						selenium.click("//span[contains(@id, 'dijit_form_Button_0_label')]");		
						Thread.sleep(2000);
						selenium.click("name=continue");
						selenium.click("id=continue_label");
						Thread.sleep(3000);
						
						quart_detailid   = "8893";
						quart_testname   = "Administration";
						quart_description= "verify administration";
						
						if (selenium.isTextPresent(("Enter server administrator username and password:")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
					
						quart_detailid   = "8894";
						quart_testname   = "AdministrationText";
						quart_description= "verify administration";
						
							if (selenium.isTextPresent(("This user must already exist and have super level privileges on the local server:")))
								writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
						
							
						// enter username & pw
							selenium.type("id=user", "p4cms");
							selenium.type("id=password", "p4cms123");
							selenium.click("name=continue");
							selenium.click("id=continue_label");
							Thread.sleep(2000);
							
							// generate random numbers for site address url
							Random generator = new Random();
							int urlInteger = generator.nextInt(9) + 2;
							
							selenium.type("id=title", "chron-srv-lin" + urlInteger + "z.qa.perforce.com");
							selenium.type("id=urls", "chron-srv-lin" +  urlInteger + "z.qa.perforce.com");
								
							// create site
							selenium.click("name=create");
							selenium.click("id=create_label");
							Thread.sleep(8000);
							
							//view site
							quart_detailid   = "10813";
							quart_testname   = "SiteBranchingSiteCreatedText";
							quart_description= "verify site created text";
							
							if (selenium.isTextPresent(("You have successfully created the site.")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
							backToHome();
					
						
								
					
			 	
			// **** ADD BRANCH FOR FIREFOX **** //
					
			// go to site branching to add branch
			
			selenium.click(CMSConstants.LIVE_LINK);
			selenium.click("id=dijit_MenuItem_0_text");
			Thread.sleep(2000); 
			
			// create a Firefox branch
			selenium.type("id=branch-name", "Firefox");
			selenium.click("id=branch-description");
			selenium.type("id=branch-description", "Firefox branch");
			
			quart_detailid   = "8965";
			quart_testname   = "AddBranchTextInputtable";
			quart_description= "verify description text area inputtable"; 
			
			if (selenium.isElementPresent("//textarea[contains(@id, 'branch-description')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			selenium.click("id=branch-save_label");	 
			Thread.sleep(3000);
		
			
			
			
			// **** GO TO SITES AND BRANCHING FOR EDIT & ADD BRANCH ON FIREFOX **** //
			
			// click manage branches 
			manageMenu(); 
			
			selenium.click("link=Sites and Branches");
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
			
			
			// **** ADD BRANCH ON SITES & BRANCHES **** //
			// click add branch
			selenium.click("id=dijit_form_Button_1_label");
			selenium.click("//input[@value='Add Branch']");
			Thread.sleep(2000);
			
			// verify add branch
			quart_detailid   = "8962";
			quart_testname   = "AddBranchSiteDropDown";
			quart_description= "verify add branch site dropdown"; 
			
			if (selenium.isElementPresent("//label[contains(@for, 'branch-site')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			// verify add branch
				quart_detailid   = "8962";
				quart_testname   = "AddBranchSiteDropDownText";
				quart_description= "verify edit branch parent text"; 
				
				if (selenium.isTextPresent("Site"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			
				// verify add branch
				quart_detailid   = "8962";
				quart_testname   = "AddBranchSiteDropDownElement";
				quart_description= "verify add branch site dropdown element"; 
				
				if (selenium.isElementPresent("//label[contains(@for, 'branch-site')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
			
			
				
				// verify add branch
				quart_detailid   = "9757";
				quart_testname   = "AddBranchFromDropDown";
				quart_description= "verify add branch from dropdown"; 
				
				if (selenium.isElementPresent("//select[contains(@id, 'branch-parent')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				// verify add branch
					quart_detailid   = "9757";
					quart_testname   = "AddBranchFromDropDownText";
					quart_description= "verify site branch from text"; 
					
					if (selenium.isTextPresent("Branch From"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
				
					// verify add branch
					quart_detailid   = "9757";
					quart_testname   = "AddBranchParentDropDownElement";
					quart_description= "verify add branch site dropdown element"; 
					
					if (selenium.isElementPresent("//select[contains(@id, 'branch-parent')]"))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
					
			
			// **** EDIT BRANCH FOR FIREFOX **** //
			backToHome();
			
			// click manage branches 
			manageMenu();
			
			selenium.click("link=Sites and Branches");
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
				
			// click edit branch
			// use xpath
			// click on p4cms filter
			selenium.click("id=user-users-p4cms"); 
			Thread.sleep(2000);
			
			//click on the Firefox branches action edit
			selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div[3]/table/tbody/tr/td[3]/span/span/span","");
			Thread.sleep(2000);
			
			selenium.click("id=dijit_MenuItem_8_text");
			Thread.sleep(2000);			
			// verify edit branch
				quart_detailid   = "9634";
				quart_testname   = "EditBranchParentDropDown";
				quart_description= "verify edit branch parent dropdown"; 
				
				if (selenium.isTextPresent("Parent"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			
				// verify edit branch
				quart_detailid   = "9634";
				quart_testname   = "EditBranchParentDropDownText";
				quart_description= "verify edit branch parent text"; 
				
				if (selenium.isElementPresent("//label[contains(@for, 'branch-parent')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
				// verify edit branch
				quart_detailid   = "9656";
				quart_testname   = "EditBranchParentDropDownSelector";
				quart_description= "verify edit branch parent selector"; 
				
				if (selenium.isElementPresent("//select[contains(@id, 'branch-parent')]"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

				
				
				quart_detailid   = "9785";
				quart_testname   = "EditBranchDescriptionSpaces";
				quart_description= "verify edit branch description with spaces"; 
				
				selenium.type("id=branch-description", "testing    ");
				Thread.sleep(1000);
				selenium.type("id=branch-description", "    ");
				Thread.sleep(1000);
				
				if (selenium.isTextPresent("Edit User"))
						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description );  }
				
				
				
				backToHome();
				// click manage branches 
				manageMenu();
				
				selenium.click("link=Sites and Branches");
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
					
				// click edit branch
				// use xpath
				// click on p4cms filter
				selenium.click("id=user-users-p4cms");
				Thread.sleep(2000);
				
				//context click menu for delete 
				selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div[3]/table/tbody/tr/td");
				Thread.sleep(2000); 
				
				selenium.click("id=dijit_MenuItem_9_text");
				Thread.sleep(2000);			
				
				// verify delete link
				quart_detailid   = "9808";
				quart_testname   = "SitesBranchesContextClickDelete";
				quart_description= "verify context click delete"; 
				
				if (selenium.isTextPresent("Delete Branch"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				
				
				// **** CREATE SUB BRANCH ON SITES & BRANCHES **** //
				backToHome();
				// click manage branches 
				manageMenu();
				
				selenium.click("link=Sites and Branches");
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
				
				// click edit branch
				// use xpath
				// click on p4cms filter
				selenium.click("id=user-users-p4cms");
				Thread.sleep(2000);
				
			    //selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .row-id-chronicle-chron-srv-lin2a-qa-perforce-com-firefox.branch .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
				selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div[3]/table/tbody/tr/td[3]/span/span/span","");
				Thread.sleep(1000);
				
				// add branch
				selenium.click("id=dijit_MenuItem_10_text");
				Thread.sleep(1000);
				
				selenium.type("id=branch-name", "Sub-branch");
				selenium.click("id=branch-save_label");
				Thread.sleep(2000);
				
				
				 // verify save
				quart_detailid   = "10010";
				quart_testname   = "AddSubBranchCheckGrowlMessage";
				quart_description= "verify add sub-branch growl message";
			
				if(selenium.isVisible(("//div[contains(@id, 'p4cms-ui-notices')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
		        else  { writeFile(quart_detailid ,"fail", quart_scriptname,quart_testname, quart_description); }
				
					
			    backToHome();
			
			
			
			
			
			// **** PULL FROM BRANCH ****//
			
			// pull from Firefox branch - no content yet
			selenium.click(CMSConstants.LIVE_LINK);
			selenium.click("id=dijit_MenuItem_5_text");
			Thread.sleep(2000);
			

			// verify update radio button
			quart_detailid   = "9412";
			quart_testname   = "PullBranchUpdateButton";
			quart_description= "verify pull branch update button"; 
			
			if (selenium.isElementPresent("//input[contains(@id, 'pull-mode-merge')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			
			// verify update radio button
			quart_detailid   = "9412";
			quart_testname   = "PullBranchUpdateButtonText";
			quart_description= "verify pull branch update button text"; 
			
			if (selenium.isTextPresent("Update"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
			// verify update text
			quart_detailid   = "9738";
			quart_testname   = "PullBranchUpdateText";
			quart_description= "verify pull branch update text"; 
			
			 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);

			if (selenium.isTextPresent("Pull items that have changed in the Firefox branch"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		
			// verify clone button
			quart_detailid   = "9413";
			quart_testname   = "PullBranchCloneButton";
			quart_description= "verify pull branch clone button"; 
			
			if (selenium.isElementPresent("//input[contains(@id, 'pull-mode-copy')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
			// verify update text
			quart_detailid   = "9413";
			quart_testname   = "PullBranchCloneButtonText";
			quart_description= "verify pull branch clone button text"; 
			
			if (selenium.isTextPresent("Clone"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
			// verify update text
			quart_detailid   = "9739";
			quart_testname   = "PullBranchCloneText";
			quart_description= "verify pull branch clone text"; 
			
			if (selenium.isTextPresent("Make items identical to the Firefox branch"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			
			
			// verify pull is not enabled
			quart_detailid   = "9442";
			quart_testname   = "PullBranchNotEnabled";
			quart_description= "verify pull not enabled"; 
			
			if (selenium.isElementPresent("//span[contains(@aria-disabled, 'true')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			
			// verify from
			quart_detailid   = "8992";
			quart_testname   = "PullBranchFromElement";
			quart_description= "verify 'From' element"; 
			
			if (selenium.isElementPresent("//dt[@id='pull-source-label']"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			quart_detailid   = "8992";
			quart_testname   = "PullBranchFromText";
			quart_description= "verify 'From' text"; 
			
				if (selenium.isElementPresent("//label[@class='required']"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
			
			// verify pull To
			quart_detailid   = "8993";
			quart_testname   = "PullBranchToElement";
			quart_description= "verify 'To' element"; 
			
			if (selenium.isElementPresent("//dt[@id='pull-target-label']"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			quart_detailid   = "8993";
			quart_testname   = "PullBranchToText";
			quart_description= "verify 'To' text"; 
			
				if (selenium.isElementPresent("//label[@class='required readonly']"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
			
			
			
			// verify pull Mode
			quart_detailid   = "9411";
			quart_testname   = "PullBranchModeElement";
			quart_description= "verify mode element"; 
			
			if (selenium.isElementPresent("//dt[@id='pull-mode-label']"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			// verify pull text
			quart_detailid   = "8988";
			quart_testname   = "PullBranchPullText";
			quart_description= "verify pull text"; 
			
			if (selenium.isTextPresent("Pull"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		
			// verify pull copy radio button
			quart_detailid   = "9408";
			quart_testname   = "PullBranchCopyButton";
			quart_description= "verify copy source button"; 
			
			if (selenium.isElementPresent("//select[@id='pull-source']"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
						
			
			// verify pull live target
			quart_detailid   = "9410";
			quart_testname   = "PullBranchTarget";
			quart_description= "verify target"; 
			
			if (selenium.isElementPresent("//input[@id='pull-target' and contains(@readonly, '1') and contains(@value, 'Live') and contains(@name, 'target')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			// verify pull update radio button
			quart_detailid   = "9412";
			quart_testname   = "PullBranchMergeRadioButton";
			quart_description= "verify update radio button"; 
			
			if (selenium.isElementPresent("//input[@id='pull-mode-merge' and contains(@type, 'radio') and contains(@value, 'merge') and contains(@checked, 'checked')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			// verify pull copy radio button
			quart_detailid   = "9413";
			quart_testname   = "PullBranchCopyRadioButton";
			quart_description= "verify copy radio button"; 
			
			if (selenium.isElementPresent("//input[@id='pull-mode-copy' and contains(@type, 'radio') and contains(@value, 'copy')]"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			// verify pull cancel button
			quart_detailid   = "8990";
			quart_testname   = "PullBranchCancelButton";
			quart_description= "verify cancel button"; 
			
				if (selenium.isElementPresent(("//span[contains(@id, 'dijit_form_Button_0_label')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

			
			// go to home, create content for Firefox
			backToHome();
			
			// go to site branching and select Firefox branch
			selenium.click(CMSConstants.LIVE_LINK);
			
			selenium.click("id=dijit_MenuItem_0_text");
			Thread.sleep(2000); 
			
			
			// create some content in Firefox branch 
			browserSpecificBasicPageSiteBranching();
			browserSpecificBlogPostSiteBranching();
			browserSpecificPressReleaseSiteBranching();
			
			backToHome();
			

			
			
			// switch back to Live branch
			// click Live link
			selenium.click(CMSConstants.LIVE_LINK);
			
			// click Pull from Firefox branch
			selenium.click("id=dijit_MenuItem_5_text");
			Thread.sleep(1000);	
			
			
			// verify pull copy radio button
			quart_detailid   = "9405";
			quart_testname   = "PullBranchPullButton";
			quart_description= "verify pull button"; 
			
			if (selenium.isElementPresent("//span[@id='pull-pull_label']"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			Thread.sleep(1000);
			
			
			
			// click on 'pull' element to pull Firefox branch
			selenium.click("id=pull-pull_label");
			Thread.sleep(4000);
			 
			
			// verify growl message
			assertTrue(selenium.isVisible("xpath=//*[@id='p4cms-ui-notices']"));
			assertTrue(selenium.isVisible("xpath=//*[@class='message']"));
			
			// verify that content is pulled over to live branch
			backToHome();
			
			
			
			
			
			//**** VERIFY SITES & BRANCHES ELEMENTS ****//
			// click manage branches 
			manageMenu();
			
			selenium.click("link=Sites and Branches");
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
			
			// click on p4cms filter
			selenium.click("id=user-users-p4cms");
			Thread.sleep(1000);
			
			
			// verify search text
				quart_detailid   = "9632";
				quart_testname   = "SitesBranchesSearchText";
				quart_description= "verify search text"; 
				
				if (selenium.isTextPresent("Search"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
				
				// verify search
				quart_detailid   = "9630";
				quart_testname   = "SitesBranchesSearchForm";
				quart_description= "verify search form"; 
				
				if (selenium.isElementPresent("//input[@id='search-query']"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				// verify owner
				quart_detailid   = "9635";
				quart_testname   = "SitesBranchesOwnerText";
				quart_description= "verify owner text"; 
				
				if (selenium.isTextPresent("Owner"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				

				// verify chronicle
				quart_detailid   = "9636";
				quart_testname   = "SitesBranchesChronicleText";
				quart_description= "verify chronicle text"; 
				
				if (selenium.isTextPresent("chronicle"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				// verify search form
				quart_detailid   = "9633";
				quart_testname   = "SitesBranchesSiteText";
				quart_description= "verify site text"; 
				
				if (selenium.isElementPresent("//div[@class='icon']"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				// verify search form
				quart_detailid   = "9638";
				quart_testname   = "SitesBranchesIcon";
				quart_description= "verify icon element"; 
				
				if (selenium.isTextPresent("Site"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				// verify search form
				quart_detailid   = "9807";
				quart_testname   = "SitesBranchesButtonDropDown";
				quart_description= "verify button drop down"; 
				
				if (selenium.isElementPresent("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div[3]/table/tbody/tr/td[3]/span/span/span"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				// verify search form
				quart_detailid   = "9804";
				quart_testname   = "SitesBranchesBranchIcon";
				quart_description= "verify button branch icon"; 
				
				if (selenium.isElementPresent("//div[@class='icon']"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				// verify search form
				quart_detailid   = "9805";
				quart_testname   = "SitesBranchesBranchName";
				quart_description= "verify button branch name"; 
				
				if (selenium.isTextPresent("Firefox"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				// verify search form
				quart_detailid   = "9809";
				quart_testname   = "SitesBranchesDropDownElements";
				quart_description= "verify button drop down elements"; 
				
				if (selenium.isElementPresent("//td[@id='dijit_MenuItem_10_text']"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				// click delete
				//selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .row-id-chronicle-chron-srv-lin2a-qa-perforce-com-firefox.branch .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
				selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div[4]/table/tbody/tr/td[3]/span/span/span","");
				Thread.sleep(1000);

				selenium.click("id=dijit_MenuItem_11_text");
				Thread.sleep(2000);			
				
				
				// verify search form
				quart_detailid   = "9809";
				quart_testname   = "SitesBranchesDropDownDeleteText";
				quart_description= "verify button drop down elements"; 
				
				if (selenium.isTextPresent("Delete Branch"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }				
								
				
				quart_detailid   = "9789";
				quart_testname   = "DeleteBranchDialogText";
				quart_description= "verify delete branch dialog text"; 
								
				if (selenium.isTextPresent("Delete Branch"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "9791";
				quart_testname   = "DeleteBranchConfirmationText";
				quart_description= "verify delete branch confirmation text"; 
								
				if (selenium.isTextPresent("Are you sure you want to delete the"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				
				quart_detailid   = "9794";
				quart_testname   = "DeleteBranchButton";
				quart_description= "verify delete branch button"; 
				
				if (selenium.isElementPresent("//span[contains(@id, 'p4cms_ui_ConfirmDialog_0-button-action_label')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				

				quart_detailid   = "9793";
				quart_testname   = "DeleteBranchCancelButton";
				quart_description= "verify delete branch cancel button"; 
				
				if (selenium.isElementPresent("//span[contains(@id, 'p4cms_ui_ConfirmDialog_0-button-cancel_label')]"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				 else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
				
		
				
//				quart_detailid   = "9790";
//				quart_testname   = "DeleteBranchTooltip";
//				quart_description= "verify delete branch tooltip"; 
//				
//				
//				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
//				
//				String tooltipDeleteBranch = selenium.getAttribute("//div[11]/div/span[2]/@title");
//			 
//				boolean tooltipForDelete =	tooltipDeleteBranch.equals("Cancel");
//				
//				if (tooltipForDelete)
//				writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description ); 
//				 else  { writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description );  }
				
				
				
//				quart_detailid   = "9792";
//				quart_testname   = "DeleteBranchClickX";
//				quart_description= "verify delete branch delete X"; 
//				
//				// click on X
//				selenium.click("//div[@id='p4cms_ui_FormDialog_0']/div/span[2]");							
//				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
//				
//				if (selenium.isTextPresent("Sites and Branches"))
//						writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description ); 
//				        else  { writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description );  }
//				
//		
							
				// click manage branches 
				backToHome();
				
				manageMenu();
				
				selenium.click("link=Sites and Branches");
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
				
				
				selenium.click("id=dijit_form_Button_1_label");
				selenium.click("//input[@value='Add Branch']");
				Thread.sleep(2000);
				
				// verify branch button
				quart_detailid   = "9637";
				quart_testname   = "SitesBranchesAddBranchButton";
				quart_description= "verify add branch button"; 
				
				if (selenium.isTextPresent("Add Branch"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
								
				
				// click manage branches 
				backToHome();
				
				manageMenu();
				
				selenium.click("link=Sites and Branches");
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 				
				
				selenium.click("id=dijit_form_Button_0_label");
				selenium.click("//input[@value='Add Site']");
				Thread.sleep(2000);
				
				// verify branch button
				quart_detailid   = "9795";
				quart_testname   = "SitesBranchesAddSiteButton";
				quart_description= "verify add branch button"; 
				
				if (selenium.isTextPresent("Setup: Requirements"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }								
				
				
				// click manage branches 
				backToHome();
				manageMenu();
				
				selenium.click("link=Sites and Branches");
				waitForPageToLoad(CMSConstants.PAGE_TIMEOUT); 
				 
				// click on p4cms filter
				selenium.click("id=user-users-p4cms");
				Thread.sleep(2000);
				
				//click on the Firefox action edit link
				//selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .row-id-chronicle-chron-srv-lin2a-qa-perforce-com-firefox.branch .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
				selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div[3]/table/tbody/tr/td[3]/span/span/span","");
				Thread.sleep(2000);
							   
			   // click edit
				selenium.click("id=dijit_MenuItem_10_text");
				Thread.sleep(1000);
				
				// verify branch button
				quart_detailid   = "9802";
				quart_testname   = "SitesBranchesContextClickEdit";
				quart_description= "verify context click edit"; 
				
				if (selenium.isTextPresent("Edit Branch"))
			    writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				Thread.sleep(1000);
						
				//click save
				selenium.click("id=branch-save_label");
				Thread.sleep(3000);
				
				
//				//context click menu for delete 
//				selenium.contextMenu("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div[5]/table/tbody/tr/td/div");
//				Thread.sleep(2000); 
//				
//				
//				// verify delete link
//				quart_detailid   = "9808";
//				quart_testname   = "SitesBranchesContextClickDelete";
//				quart_description= "verify context click delete"; 
//				
//				 //writeFile(quart_detailid, "skipped", quart_scriptname, quart_testname, quart_description);
//				if (selenium.isTextPresent("Delete Branch"))
//						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//				        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
//				Thread.sleep(1000);			

				// back to Website
				backToHome();
	}
}


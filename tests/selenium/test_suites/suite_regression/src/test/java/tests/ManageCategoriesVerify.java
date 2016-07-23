package tests;

import org.apache.commons.lang.ArrayUtils;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> users and verifys the add user button

public class ManageCategoriesVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageCategoriesVerify";

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
	      
		// User management
		ManageCategoriesVerify();
	
		// Logout and verify Login link
		selenium.click("link=Logout");

		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	

	
public void ManageCategoriesVerify() throws Exception {
		
		// click on manage -- users
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CATEGORIES);

		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		String categoryName  = "Category1";
		String categoryName1 = "Category2";
		//  **** add a category **** //
		// click on add category
		//selenium.click("//div[4]/div/div[2]/div[3]/div/span/span/span/span[3]");
		//selenium.click("css=input.dijitOffScreen");
		selenium.click("css=.grid-footer .button .dijitButton .dijitButtonNode .dijitButtonContents");
		Thread.sleep(2000);
		
		// title
		selenium.type("id=title", categoryName);
		selenium.type("id=description", "This is a test");	
		
						/*// click browse button
						selenium.click("id=indexContent-browse-button_label");
						selenium.click("id=indexContent-browse-button");
						Thread.sleep(3000);
						 
						// click on an item in grid
						selenium.click("//img[@src='/type/icon/id/press-release']");
						Thread.sleep(2000);
						
						// click select
						selenium.click(("//span[contains(@class, 'dijitReset dijitStretch dijitButtonContents')]"));
						selenium.click("//div[@id='buttons-element']/fieldset/span/input");
						//selenium.click("//div[@id='buttons-element']/fieldset/span/input");
						Thread.sleep(3000);*/
		
		// save
		selenium.click("id=save_label");
		Thread.sleep(2000);  
	
		
	//  **** add a category **** //
		// click on add category
		selenium.click("css=.grid-footer .button .dijitButton .dijitButtonNode .dijitButtonContents");

		Thread.sleep(2000);
			
		// title
		selenium.type("id=title", categoryName1);
		selenium.type("id=description", "This is a test");	
		
		String quart_detailid   = "7121";
		 String quart_testname   = "ManageCategoriesInputTitleVerify";
		String  quart_description= "verify input title";
		//if (selenium.isTextPresent("Are you sure you want to delete the" + categoryName + "category?"))
		if(selenium.isElementPresent(("//input[contains(@id, 'title')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		// save
		selenium.click("id=save_label");
		Thread.sleep(3000);  
	
		
		
		// **** delete category **** //
		selenium.clickAt("//span[@id='dijit_form_DropDownButton_1']","");
		Thread.sleep(2000);
		
		// click delete menu
		selenium.click("id=dijit_MenuItem_8_text");
		Thread.sleep(2000);
		
		 quart_detailid   = "7115";
		  quart_testname   = "DeleteTextVerify";
		  quart_description= "verify delete text";
		//if (selenium.isTextPresent("Are you sure you want to delete the" + categoryName + "category?"))
		if (selenium.isTextPresent("Delete Category"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		 quart_detailid   = "7114";
		  quart_testname   = "DeleteCategoryTextVerify";
		  quart_description= "verify delete category text";
		if (selenium.isTextPresent("Delete Category"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// **** click cancel closes delete category **** //
		//selenium.click("//fieldset[@id='fieldset-buttons']/dl/dd[2]/span/input");
		//selenium.click("id=p4cms_ui_ConfirmDialog_0-button-cancel_label");
		selenium.click("//span[@id='p4cms_ui_ConfirmDialog_0-button-cancel_label']");
		Thread.sleep(2000);

		quart_detailid   = "7149";
		  quart_testname   = "ClickCancelVerify";
		  quart_description= "verify click cancel";
		if (selenium.isTextPresent("Manage Categories"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }


		
		
		// **** grid elements **** //
		 quart_detailid   = "7168";
		  quart_testname   = "ElementsVerify";
		  quart_description= "verify elements";
		if (selenium.isTextPresent("Add Category"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		if (selenium.isTextPresent("entries"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
 
		
		
		
		// **** verify page **** //
		 quart_detailid   = "6141";
		  quart_testname   = "PageVerify";
		  quart_description= "verify page";
		// Write to file for checking manage content type page
		  if (selenium.isTextPresent("Manage Categories"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
		
		  
		// **** add category **** //
		// click on add category
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add Category']");
		Thread.sleep(2000);
		
		// verify add category dialog
		assertTrue(selenium.isTextPresent("Add Category"));
		
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		
		// check to see if user selected is checked and write to file
		 quart_detailid   = "7036";
		  quart_testname   = "AddCategoryVerify";
		  quart_description= "verify add category modal dialog";
		if(selenium.isTextPresent( "Add Category" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		 quart_detailid   = "7117";
		  quart_testname   = "ModalDialogVerify";
		  quart_description= "verify add category title on modal dialog";
		if(selenium.isTextPresent( "Add Category" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		
		// check to see if user selected is checked and write to file
		quart_detailid   = "7129";
		  quart_testname   = "CancelButtonVerify";
		  quart_description= "verify cancel button category";
		selenium.click("//fieldset[@id='fieldset-buttons']/dl/dd[2]/span/input");
		if(selenium.isTextPresent(("Manage Categories")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CATEGORIES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			
		
		
		// **** description verify **** //
		// click on add category
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add Category']");
		Thread.sleep(2000);
		
		// enter description
		assertTrue(selenium.isElementPresent(("//textarea[contains(@id, 'description')]"))); 

		selenium.type("id=description", "This is a test");	
				
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		quart_detailid   = "7123";
		  quart_testname   = "DescriptionVerify";
		  quart_description= "verify description";
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent( "Description" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		

		
		// **** select a page verify **** //
		
		assertTrue(selenium.isTextPresent("Select a page to display when a user navigates to this category. Leave blank for the default presentation."));
		
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		quart_detailid   = "7148";
		  quart_testname   = "SelectAPageTextVerify";
		  quart_description= "select a page sentence description";
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent("Select a page to display when a user navigates to this category. Leave blank for the default presentation."))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		
		// edit category context click
		// click on manage -- category
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CATEGORIES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
		Thread.sleep(2000);
		
		// click edit menu
		selenium.click("id=dijit_MenuItem_7_text");
		Thread.sleep(2000);
		
		quart_detailid   = "7154";
		  quart_testname   = "EditCategoryVerify";
		  quart_description= "edit category verify";
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent("Edit Category"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// edit description
		selenium.type("id=description", "Chronicle rules");
		selenium.type("id=title", "Bar");
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		
		// edit category context click
		//selenium.clickAt("//span[@id='dijit_form_DropDownButton_2']","");
		selenium.clickAt("css=.dojoxGridMasterView .dojoxGridView .dojoxGridScrollbox .dojoxGridContent .dojoxGridRowTable .dojoxGridCell .dijitDropDownButton .dijitButtonNode .dijitDownArrowButton","");
		Thread.sleep(3000);
		
		// click edit menu
		selenium.click("id=dijit_MenuItem_7_text");
		Thread.sleep(2000);
		
		quart_detailid   = "7157";
		  quart_testname   = "EditCategoryDescVerify";
		  quart_description= "edit category description verify";
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent("Chronicle rules"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		quart_detailid   = "7160";
		  quart_testname   = "EditCategoryTitleVerify";
		  quart_description= "edit category title verify";
		// check to see if user selected is checked and write to file
		if(selenium.isTextPresent("Bar"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	
		
		
		// **** browse category **** //
		
		// title
		selenium.type("id=title", "Test category");
		
		// click browse button
		
		selenium.clickAt("css=.dijitDialogPaneContent .scrollNode .category-form .zend_form_dojo .content-select .content-select-row .button-small.dijitButton .dijitButtonNode .dijitButtonContents .dijitButtonText","");
		Thread.sleep(4000);
		
		// verify selector 
		assertTrue(selenium.isTextPresent("Select Content"));	
		
		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
		
		// check to see if user selected is checked and write to file
		quart_detailid   = "7119";
		  quart_testname   = "ManageCategoryBrowseButtonVerify";
		  quart_description= "verify browse button category";
		if(selenium.isTextPresent( "Select Content" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7140";
		  quart_testname   = "ManageCategoryBrowseButtonSelectContentTextVerify";
		  quart_description= "verify browse button select content";
		if(selenium.isTextPresent( "Select Content" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7283";
		  quart_testname   = "ManageCategoryBrowseButtonSearchTextVerify";
		  quart_description= "verify browse button search text";
		if(selenium.isTextPresent( "Search" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7284";
		  quart_testname   = "ManageCategoryBrowseButtonSearchForm";
		  quart_description= "verify browse button search form";
			if(selenium.isElementPresent(("//input[contains(@id, 'lucene-query')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7284";
		  quart_testname   = "ManageCategoryBrowseButtonVerify";
		  quart_description= "verify browse button category";
		if(selenium.isTextPresent( "Select Content" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
		quart_detailid   = "7147";
		  quart_testname   = "ManageCategoryBrowseButtonPagesVerify";
		  quart_description= "verify select content pages";
		if(selenium.isTextPresent( "Pages" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
		quart_detailid   = "7137";
		  quart_testname   = "ManageCategoryBrowseButtonBasicPageVerify";
		  quart_description= "verify select content basic pages";
		if(selenium.isTextPresent( "Basic Page" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
		quart_detailid   = "7138";
		  quart_testname   = "ManageCategoryBrowseButtonBlogPostVerify";
		  quart_description= "verify select content blog post";
		if(selenium.isTextPresent( "Blog Post" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
		quart_detailid   = "7141";
		  quart_testname   = "ManageCategoryBrowseButtonPressReleaseVerify";
		  quart_description= "verify select content pages";
		if(selenium.isTextPresent( "Press Release" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
		quart_detailid   = "7279";
		  quart_testname   = "ManageCategoryBrowseButtonAssetsVerify";
		  quart_description= "verify select content assets";
		if(selenium.isTextPresent( "Assets" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
		quart_detailid   = "7280";
		  quart_testname   = "ManageCategoryBrowseButtonImageVerify";
		  quart_description= "verify select content image";
		if(selenium.isTextPresent( "Image" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		 
		quart_detailid   = "7281";
		  quart_testname   = "ManageCategoryBrowseButtonFileVerify";
		  quart_description= "verify select content file";
		if(selenium.isTextPresent( "File" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7278";
		  quart_testname   = "ManageCategoryBrowseButtonWorkflowVerify";
		  quart_description= "verify select content workflow";
		if(selenium.isTextPresent( "Workflow" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7142";
		  quart_testname   = "ManageCategoryBrowseButtonAnyStateVerify";
		  quart_description= "verify select content any state";
		if(selenium.isTextPresent( "Any State" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		quart_detailid   = "7273";
		  quart_testname   = "ManageCategoryBrowseButtonPublishedContentVerify";
		  quart_description= "verify select content published content";
		if(selenium.isTextPresent( "Published Content" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7144";
		  quart_testname   = "ManageCategoryBrowseButtonUnPublishedContentVerify";
		  quart_description= "verify select content unpublished content";
		if(selenium.isTextPresent( "Unpublished Content" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		quart_detailid   = "7274";
		  quart_testname   = "ManageCategoryBrowseButtonSpecificWorkflowVerify";
		  quart_description= "verify select content specific workflow state";
		if(selenium.isTextPresent( "Specific Workflow States" ))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		selenium.select("id=workflow-targetState", "label=Current Status");
		 
		// place them into a string array
		String[] currentSelection = selenium.getSelectOptions("//select[contains(@name, 'workflow[targetState]')]");
				
				// verify if the Current Status exists in the selection list 
		boolean selectedValue = ArrayUtils.contains(currentSelection, "Current Status");
			    
		quart_detailid   = "10061";  
		quart_testname   = "ManageCategoryCurrentStatusSelection";
		quart_description= "verify current status selection";
		// verify that scheduled status is selected
			if (selectedValue)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
			// verify array in browse workflow drop down
			
			// place them into a string array
			String[] workflowValues = selenium.getSelectOptions("//select[contains(@name, 'workflow[targetState]')]");
						
						// verify if the Current Status exists in the selection list 
			boolean hasValues  = ArrayUtils.contains(workflowValues, "Current Status");
			boolean hasValues1 = ArrayUtils.contains(workflowValues, "Scheduled Status");
			boolean hasValues2 = ArrayUtils.contains(workflowValues, "Current or Scheduled Status");
					
			quart_detailid   = "10061";
			quart_testname   = "ManageCategoryBrowseButtonWorkflowDropDown";
			quart_description= "verify browse workflow dropdown";
			if (hasValues)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
			quart_detailid   = "10061";
			quart_testname   = "ManageCategoryBrowseButtonWorkflowDropDown";
			quart_description= "verify browse workflow dropdown";
			if (hasValues1)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			quart_detailid   = "10061";
			quart_testname   = "ManageCategoryBrowseButtonWorkflowDropDown";
			quart_description= "verify browse workflow dropdown";
			if (hasValues2)
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
		
		
		// click on an item in grid
		selenium.click("//img[@src='/type/icon/id/press-release']");
		Thread.sleep(2000);
		
		// click select
		//selenium.click(("//span[contains(@class, 'dijitReset dijitStretch dijitButtonContents')]"));
		//selenium.click("//div[@id='buttons-element']/fieldset/span/input");
		selenium.click("id=p4cms_content_SelectDialog_0-button-select_label");
		selenium.click("//div[@id='buttons-element']/fieldset/span/input");
		Thread.sleep(2000);
		
		
	   // **** clear button **** //
		
//		// click clear
//		selenium.clickAt("//div[6]/div[2]/div/form/dl/dd[4]/div/div/span[2]/span/span/span[3]", "");
//		//selenium.click("id=indexContent-clear-button_label");
//		//selenium.click("name=indexContent-clear-button");
//		Thread.sleep(2000);
//		
//		//writeFile1("\nskipped 1202", "", "ManageUsersVerifyAddUserButton.java");
//		assertTrue(selenium.isElementPresent(("//span[contains(@id, 'indexContent-clear-button_label')]"))); 
//		// check to see if user selected is checked and write to file
//		quart_detailid   = "7120";
//		  quart_testname   = "ClearButtonVerify";
//		  quart_description= "verify clear button category";
//		if(selenium.isElementPresent(("//span[contains(@id, 'indexContent-clear-button_label')]")))
//			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
//		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// save
		selenium.click("id=save_label");
		Thread.sleep(2000);  
	
		// Back to Website
		backToHome();
 }
}
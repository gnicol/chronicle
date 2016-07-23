package tests;

import java.text.DateFormat;
import java.util.Date;
import java.text.SimpleDateFormat;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code creates a blog post - content in in place mode; it clicks on add a blog post, clicks on in-in place mode and verifys all elements to write to a file.
//It also enters a title, date, author, excerpt, and body and then saves the blog post

public class AddBlogPostAllElementsVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname="AddBlogPostAllElementsVerify";

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
		AddBlogPostAllElementsVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//assertTrue(selenium.isElementPresent("link=Login"));  

	}
	
	public void AddBlogPostAllElementsVerify() throws InterruptedException, Exception {
		
		// Verify title & close icon & content type
		verifyContentElements();
		
		browserSpecificBlogPost();
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		 String quart_detailid   = "7904";
 		 String quart_testname   = "PlaceModeAuthorVerify";
 		 String quart_description= "Add blog post - place mode author verify";
 		// verify place mode body
 		if (selenium.isElementPresent(("//input[contains(@id, 'author')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		 quart_detailid   = "8013";
 		 quart_testname   = "PlaceModeBodyVerify";
 		 quart_description= "Add Blog post - place mode body verify";

 		// verify place mode button	
 		if (selenium.isElementPresent(("//div[contains(@id, 'body-Editor')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "7876";
 		 quart_testname   = "PlaceModeCancelButtonVerify";
 		 quart_description= "Add Blog post - place mode cancel verify ";	
 		// verify place mode cancel 
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-cancel_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "7875";
 		 quart_testname   = "PlaceModeCategoryVerify";
 		 quart_description= "Add Blog post - place mode category verify";
 		// verify place mode category
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Categories')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "8012";
		 quart_testname   = "PlaceModeDateVerify";
		 quart_description= "Add Blog post - place mode date verify ";
		// verify form mode date
		if (selenium.isElementPresent(("//div[contains(@class, 'content-element content-element-type-dateTextBox content-element-date')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
       else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
 		 quart_detailid   = "7788";
 		 quart_testname   = "PlaceModeMenuVerify";
 		 quart_description= "Add Blog post - place mode menu verify";
 		// verify place mode menus
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Menus')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		 quart_detailid   = "8011";
 		 quart_testname   = "PlaceModeSaveButtonVerify";
 		 quart_description= "Add Blog post - place mode save button verify";
 		// verify place mode save button
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Save_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
	
 		quart_detailid   = "7874";
		 quart_testname   = "PlaceModeTitleVerify";
		 quart_description= "Add Blog post - place mode title verify";
			if (selenium.isTextPresent("Title"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
 		
 		
 		 quart_detailid   = "7882";
 		 quart_testname   = "PlaceModeURLVerify";
 		 quart_description= "Add Blog post - place mode url button verify";
 		// verify place mode url
 		 
  		selenium.clickAt("id=add-content-toolbar-button-URL","");
  		//selenium.clickAt("//div/div/div/div/ul/span/li[5]/div/div/div/span[5]/span/span","");
 		Thread.sleep(1000);
 		if (selenium.isTextPresent(("Use Title for URL")))
 			
 		//if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-URL')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		/* quart_detailid   = "8009";
 		 quart_testname   = "WorkflowVerify";
 		 quart_description= "Add Blog post - place mode workflow ";
 		// verify place mode workflow
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Workflow')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		*/
 		
		 quart_detailid   = "8008";
		 quart_testname   = "PlaceModeVerifyElements";
		 quart_description= "Add Blog post - place mode form element ";
			if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-form_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
	
			 quart_detailid   = "1602";
			 quart_testname   = "PlaceModePlaceButtonVerify";
			 quart_description= "Add Blog post - place mode form element ";
			if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-in-place_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
		// inplace mode check
		//inplaceModeCheck();
	
 		
 		
 	//****  Click into form mode ****//
 		
 		// click form mode and verify all elements
 		selenium.click("id=add-content-toolbar-button-form_label");
 		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
 		
 		// form mode check
 		//formModeCheck();
 					
 		 quart_detailid   = "7902";
 		 quart_testname   = "FormModeBodyVerify";
 		 quart_description= "Add Blog post - form mode body verify ";
 		
 		if (selenium.isElementPresent(("//div[contains(@id, 'body-Editor')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		quart_detailid   = "7896";
		 quart_testname   = "FormModeButtonVerify";
		 quart_description= "Add Blog post - form mode button verify ";
		 if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-form_label')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
				
		 
		 
		  quart_detailid   = "7996";
 		  quart_testname   = "FormModeAuthorVerify";
 		  quart_description= "Add blog post - form mode author verify";
 		// verify place mode body
 		if (selenium.isElementPresent(("//input[contains(@id, 'author')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
 		
 		
		 quart_detailid   = "7990";
		 quart_testname   = "FormModeTitleVerify";
		 quart_description= "Add Blog post - form mode title verify ";
		 if (selenium.isTextPresent(("Title")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
         quart_detailid   = "7899";
 		 quart_testname   = "FormModeCancelButtonVerify";
 		 quart_description= "Add Blog post - form mode cancel verify ";
 		// verify form mode cancel
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-cancel_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		 quart_detailid   = "7900";
 		 quart_testname   = "FormModeDateVerify";
 		 quart_description= "Add Blog post - form mode date verify ";
 		// verify form mode date
 		if (selenium.isElementPresent(("//input[contains(@id, 'date')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		 
 		quart_detailid   = "7880";
 		 quart_testname   = "FormModeCategoryVerify";
 		 quart_description= "Add Blog post - form mode category verify ";
 		// verify form mode category	
 		if (selenium.isTextPresent(("Categories")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7871";
		 quart_testname   = "FormModeExcerptVerify";
		 quart_description= "Add Blog post - form mode excerpt verify ";
 		if (selenium.isElementPresent(("//div[contains(@id, 'excerpt-Editor')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		quart_detailid   = "7872";
 		 quart_testname   = "FormModeHeadingVerify";
 		 quart_description= "Add Blog post - form mode heading verify ";
 		// verify form mode delete	
 		if (selenium.isTextPresent("Blog Post"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7881";
 		 quart_testname   = "FormModeMenuVerify";
 		 quart_description= "Add Blog post - form mode menu verify ";
 		// verify form mode menu
 		if (selenium.isTextPresent(("Menus")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		quart_detailid   = "7901";
 		 quart_testname   = "FormModeIconVerify";
 		 quart_description= "Add Blog post - form mode icon verify ";
 		// verify form mode icon	
 		if (selenium.isElementPresent(("//img[contains(@src, '/type/icon/id/blog-post')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7897";
 		 quart_testname   = "FormModeSaveButtonVerify";
 		 quart_description= "Add Blog post - form mode save button verify ";
 		// verify form mode save 
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-Save_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 		quart_detailid   = "7987";
		 quart_testname   = "FormModeFormModeTitleVerify";
		 quart_description= "Add Blog post - form mode title verify ";
		if (selenium.isTextPresent("Title"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		quart_detailid   = "7903";
 		 quart_testname   = "FormModeURLVerify";
 		 quart_description= "Add Blog post - form mode url verify ";
 		// verify form mode url	
 		if  (selenium.isElementPresent(("//input[contains(@id, 'url-auto-true')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		if (selenium.isTextPresent("Use Title for URL"))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			
 		
 		
 	/*	quart_detailid   = "7783";
 		 quart_testname   = "WorkflowVerify";
 		 quart_description= "Add Blog post - form mode workflow verify ";
 		// verify form mode workflow	 
 		if (selenium.isTextPresent(("Workflow")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
			*/
 		
 		
 		quart_detailid   = "7898";
 		 quart_testname   = "FormModeVerifyElements";
 		 quart_description= "Add Blog post - form mode in place button verify ";
 		// verify in form button
 		if (selenium.isElementPresent(("//span[contains(@id, 'add-content-toolbar-button-in-place_label')]")))
 			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description); }
		
 		
 	// add blog posts in review and publish mode for manage content
	 		selenium.open(baseurl);
	 		// click manage menu then click for content
	 		verifyContentElements();
	 		browserSpecificBlogPost();
	 		Thread.sleep(1000);
	 		addBlogPost();
	 		
	 		addBlogPostReviewMode();  
	 	
	 		addBlogPostPublishMode();  		
	 		
	 		backToHome(); 
    }
}

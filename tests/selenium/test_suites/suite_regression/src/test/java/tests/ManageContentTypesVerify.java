	package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> content type page and verifies the title

public class ManageContentTypesVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageContentTypesPageVerify";

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
		manageContentTypesVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void manageContentTypesVerify() throws Exception {
		 
		// Click on Manage --> Manage content types
		manageMenu();
		
		selenium.click(CMSConstants.MANAGE_CONTENT_TYPES);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		//writeFile1("\nskipped: 1044", "", "");

		// Write to file for checking manage content type page
		
		String quart_detailid   = "6142";
		 String quart_testname   = "ManageContentTypesPageVerify";
		 String quart_description= "verify manage content types pages";
		 
			if (selenium.isTextPresent("Manage Content Types"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
			 quart_detailid   = "266";
			  quart_testname   = "ManageContentTypesSearchText";
			  quart_description= "verify manage content types search text";
			 
				if (selenium.isTextPresent("Search"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				
				 quart_detailid   = "258";
				  quart_testname   = "ManageContentTypesGroupText";
				  quart_description= "verify manage content types group text";
				 
					if (selenium.isTextPresent("Group"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
					
			
			 quart_detailid   = "6896";
			  quart_testname   = "ManageContentTypesPagesText";
			  quart_description= "verify manage content types pages text";
			 
				if (selenium.isTextPresent("Pages"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
				

			 quart_detailid   = "275";
			  quart_testname   = "ManageContentTypesAssetsText";
			  quart_description= "verify manage content types assets text";
			 
				if (selenium.isTextPresent("Assets"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
									
		
			 quart_detailid   = "267";
			  quart_testname   = "ManageContentTypesSearchForm";
			  quart_description= "verify manage content types search form";
			 
			if (selenium.isElementPresent(("//input[contains(@id, 'search-query')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
											
			 quart_detailid   = "265";
			  quart_testname   = "ManageContentTypesPagesCheck";
			  quart_description= "verify manage content types pages checkbox";
			 
				if (selenium.isElementPresent(("//input[contains(@id, 'group-groups-Pages')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
							
			 quart_detailid   = "6897";
			  quart_testname   = "ManageContentTypesAssetsCheck";
			  quart_description= "verify manage content types assets checkbox";
			  
				if (selenium.isElementPresent(("//input[contains(@id, 'group-groups-Assets')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }		
			
				
			// click on pages checkbox	
			selenium.click("id=group-groups-Pages");
				
			 quart_detailid   = "10339";
			  quart_testname   = "ManageContentTypesPagesClick";
			  quart_description= "verify manage content types pages click";
			  
				if (selenium.isElementPresent(("//input[contains(@id, 'group-groups-Pages')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }					
				
				// click on pages checkbox	
				selenium.click("id=group-groups-Pages");
				// click on assets checkbox	
				selenium.click("id=group-groups-Assets");
								
				 quart_detailid   = "10340";
				  quart_testname   = "ManageContentTypesAssetsClick";
				  quart_description= "verify manage content types assets click";
				  
					if (selenium.isElementPresent(("//input[contains(@id, 'group-groups-Assets')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }					
					
		//Back to Website
		backToHome();
	}
}


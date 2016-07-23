package tests;

import java.io.FileWriter;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> modules and verifies the pages title


public class HomePageLoggedInVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "HomePageLoggedInVerify";


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
		waitForElements("link=Logout");
		selenium.click("link=Home");
		
		// Verify Chronicle home page elements 
		HomePageLoggedInVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		waitForElements("link=Login");

	}
	
	public void HomePageLoggedInVerify() throws Exception {
		 
		// **** verify gear icon **** //
		// Click on Manage --> Manage content types
		manageMenu();
		waitForElements("//span[contains(@class, 'menu-icon manage-toolbar-manage')]");
		// verify menu icon
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-manage')]")));  

		 String quart_detailid   = "9712";
		 String quart_testname   = "ManageText";
		 String quart_description= "Check Homepage elements - manage toolbar text";
		
		// Write to file for checking manage content type page
		if (selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-manage')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		// **** verify content icon **** //
		  quart_detailid   = "9713";
		  quart_testname   = "ContentText";
		  quart_description= "Check Homepage elements - content text";
		
		// verify content icon
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-content-manage')]"))); 
		
		if (selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-content-manage')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		// verify content text
		assertTrue(selenium.isTextPresent(("Content"))); 
		
		
		// **** verify content text **** //
		// verify content page
		selenium.open(baseurl);
		waitForElements("link=Logout");
		manageMenu();
		waitForText("Content Management");
		selenium.click(CMSConstants.MANAGE_CONTENT);
		waitForText("Manage Content");
		
		   quart_detailid   = "7722";
		   quart_testname   = "ContentText";
		   quart_description= "Check Homepage elements - check content text";
		if (selenium.isTextPresent(("Content")))
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		
		// verify content page
		selenium.open(baseurl);
		waitForElements("link=Logout");
		
		manageMenu();
		selenium.click(CMSConstants.MANAGE_CONTENT);
		waitForText("Manage Content");
		assertTrue(selenium.isTextPresent(("Manage Content"))); 
		
		  quart_detailid   = "7730";
		  quart_testname   = "ContentPage";
		  quart_description= "Check Homepage elements - check content page";
		if (selenium.isTextPresent(("Manage Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		selenium.open(baseurl);
		waitForElements("link=Logout");
		
		
	
		// verify add content
		selenium.open(baseurl);
		waitForElements("link=Logout");
		selenium.click("css=span.menu-icon.manage-toolbar-content-add");
		waitForElements("//img[contains(@src, '/type/icon/id/basic-page')]");
				
		   quart_detailid   = "6140";
		   quart_testname   = "AddContentDialog";
		   quart_description= "Check Homepage elements - add content dialog";
		if (selenium.isTextPresent(("Add Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		selenium.open(baseurl);
		waitForElements("link=Logout");
		
		
		
		// click on learn more button in managing chronicle
			selenium.click("//a[contains(@href, '/docs/manual/adminguide.html')]");
			//selenium.click("css=#widget-db1216a8-8217-dd99-5bf2-a0701e0b6010-content > div.button > a.button-link > span");
								
			// get url string and match to baseurl	
			String adminGuideURL = baseurl;
			String adminGuide = "/docs/manual/adminguide.html";
			String delims_for_url1    = "[http://baseurl]";
			
			String matchURL[] = adminGuideURL.split(delims_for_url1);
			adminGuideURL = matchURL[0] + matchURL[1] + adminGuide;	
	 					
			//writeFile(adminGuide,branchURL, delims_for_url1,url,"");

		   quart_detailid   = "6097";
		   quart_testname   = "LearnMoreInManagingChronicle";
		   quart_description= "Check Homepage elements - verify learn more managing chronicle";
				//if (selenium.isVisible("//a[@name='adminguide']"))
		   //assertTrue(selenium.isElementPresent(("//a[contains(@name, 'adminguide')]"))); 

		   if (adminGuide.equalsIgnoreCase(adminGuideURL))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
    		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		selenium.open(baseurl);
		waitForElements("link=Home");
				

		
		// click on learn more button in cms
		selenium.click("//a[contains(@href, '/docs/manual/devguide.html')]");
		
		String devGuideURL = baseurl;
		String devGuide = "/docs/manual/devguide.html";
		String delims_for_url2    = "[http://baseurl]";
		
		String matchURL1[] = devGuideURL.split(delims_for_url2);
		devGuideURL = matchURL1[0] + matchURL1[1] + devGuide;	
		
		
	   quart_detailid   = "6098";
	   quart_testname   = "LearnMoreInAdvancedCMS";
	   quart_description= "Check Homepage elements - verify learn more advanced cms";
	
	   if (devGuide.equalsIgnoreCase(devGuideURL))
		writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
	   selenium.open(baseurl);
	   waitForElements("link=Home");
	
	
	
		
		// verify help window
		// click on help window
		selenium.click("css=span.menu-handle.type-uri");
		waitForText("Welcome to Perforce Chronicle");
		
		   quart_detailid   = "9715";
		   quart_testname   = "HelpWindow";
		   quart_description= "Check Homepage elements - verify help window";
		   if (selenium.isTextPresent(("Welcome to Perforce Chronicle")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
    		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
	
		
		selenium.open(baseurl);
		waitForElements("link=Home");
		
		
		// verify help window
		// click on help window
		selenium.click("css=span");
		waitForText("Welcome to Perforce Chronicle");
			
		   quart_detailid   = "7731";
		   quart_testname   = "ChronicleInfoLink";
		   quart_description= "Check Homepage elements - verify chronicle info help window";
		   if (selenium.isTextPresent(("Welcome to Perforce Chronicle")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
    		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
			selenium.open(baseurl);
			waitForElements("link=Home");
		
		
		// click on learn more button in getting started
		selenium.click("css=a.button-link > span");
		waitForText("Content");
		
		   quart_detailid   = "6096";
		   quart_testname   = "LearnMoreInGettingStarted";
		   quart_description= "Check Homepage elements - verify learn more getting started";
		   if (selenium.isTextPresent(("Content")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
    		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		selenium.open(baseurl);
		waitForElements("link=Home");
		
		
		
		// verify widgets gear icons
		// click on a widget
		selenium.click("css=span.menu-icon.manage-toolbar-widgets");
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitStretch dijitButtonContents')]")));  
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitIcon plusIcon')]")));  

		// click on a widget
		   quart_detailid   = "6147";
		   quart_testname   = "WidgetsPlusIcon";
		   quart_description= "Check Homepage elements - widget plus icon";
		   if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitIcon plusIcon')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		selenium.open(baseurl);
		waitForElements("link=Home");
		
		
		// verify footer sitemap link
		assertTrue(selenium.isElementPresent(("//a[contains(@href, '/menu/sitemap')]")));
		
		   quart_detailid   = "8686";
		   quart_testname   = "SitemapLink";
		   quart_description= "Check Homepage elements - check sitemap link";
		if (selenium.isElementPresent(("//a[contains(@href, '/menu/sitemap')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		
		// verify HOME tab
		assertTrue(selenium.isElementPresent(("//a[contains(@class, 'home-page type-mvc')]")));
		
		   quart_detailid   = "8689";
		   quart_testname   = "HomeTab";
		   quart_description= "Check Homepage elements - Home tab";
		if (selenium.isElementPresent(("//a[contains(@class, 'home-page type-mvc')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
		
		// verify Categories tab
		assertTrue(selenium.isElementPresent(("//a[contains(@href, '/category')]")));
		
		   quart_detailid   = "8690";
		   quart_testname   = "CategoryTab";
		   quart_description= "Check Homepage elements - Category tab";
		if (selenium.isElementPresent(("//a[contains(@href, '/category')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		   quart_detailid   = "8691";
		   quart_testname   = "SearchTab";
		   quart_description= "Check Homepage elements - Search tab";
		if (selenium.isElementPresent(("//a[contains(@href, '/search')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		

		// verify Logout tab
		assertTrue(selenium.isElementPresent(("//a[contains(@href, '/user/logout')]")));
		
		   quart_detailid   = "8692";
		   quart_testname   = "LogoutTab";
		   quart_description= "Check Homepage elements - Logout tab";
		if (selenium.isElementPresent(("//a[contains(@href, '/user/logout')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// verify Chronicle logo
		assertTrue(selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/business/images/logo.png')]")));
		
		   quart_detailid   = "8694";
		   quart_testname   = "Logo";
		   quart_description= "Check Homepage elements - Logo";
		if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/business/images/logo.png')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// verify powered by			
		   quart_detailid   = "8688";
		   quart_testname   = "Poweredby";
		  quart_description= "Check Homepage elements - Powered by";
		if (selenium.isTextPresent(("Powered by ")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	    
		// verify powered by			
		   quart_detailid   = "8688";
		   quart_testname   = "PerforceChronicle";
		  quart_description= "Check Homepage elements - Powered by";
		if (selenium.isTextPresent(("Perforce Chronicle")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	    
		// verify powered by			
		   quart_detailid   = "8688";
		   quart_testname   = "PoweredbyLink";
		  quart_description= "Check Homepage elements - Powered by Perforce Chronicle link";
			if (selenium.isElementPresent(("//a[contains(@href, 'http://perforcechronicle.com/')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		 
		manageMenu();
		Thread.sleep(1000);
		selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY); 
		waitForElements("dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1");
		
		// get serverOS variable via xpath
		String clientCodelineAttribute = selenium.getText(CMSConstants.GET_CLIENT_SYSTEM_INFO_TEXT);
		// set delimiters for serverOS
		String delims_for_clientCodelineAttribute    = "[/-]";		  // parse out the "/" and "-"
		// split out the delimiters from serverOS
		String[] attributes  = clientCodelineAttribute.split(delims_for_clientCodelineAttribute);
		String space = " "; // the space is needed to match the home page codeline
		
		// parse serverOS info to appropriate fields 
		clientCodelineAttribute    = space + attributes [1];    // codeline - second element  	 
	 
		 selenium.open(baseurl);
		 waitForElements("link=Logout");
		 
		// get home page codeline variable via xpath
		String HomepageCodeline = selenium.getText(CMSConstants.GET_HOMEPAGE_CODELINE);
		String delims_for_homepagecodeline    = "[-]"; // parse out the "-"
		
		// split home page codeline attributes 
		String[] attributes1 = HomepageCodeline.split(delims_for_homepagecodeline); 
 		
		// parse home page codeline attributes
		int numberOfCharsInCodeline = 9 ;
		// check the length of the codeline string 
		// if the release string isn't "MAIN" nor "P line" then only get the first two elements to prevent out of bounds exception
		if (clientCodelineAttribute.length() >= numberOfCharsInCodeline )   
		   	  HomepageCodeline = attributes1 [0] + attributes1 [1];
		else {HomepageCodeline = attributes1 [0] + attributes1 [1]; }
		
		// debug writeFile code
		//writeFile("8695", clientCodelineAttribute, HomepageCodeline, "", "");
		 
		
		// match client codeline to homepage codeline
		// java string compare while ignoring case
		  quart_detailid   = "8695";
		  quart_testname   = "Codeline";
		  quart_description= "Check Homepage codeline";
		  
		  // COMMENTING OUT UNTIL RESOLVED //
		if (clientCodelineAttribute.equalsIgnoreCase(HomepageCodeline))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// verify help icon
		assertTrue(selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-help')]")));  
		
		   quart_detailid   = "9716";
		   quart_testname   = "HelpIcon";
		   quart_description= "Check Homepage elements - check help icon";
		if (selenium.isElementPresent(("//span[contains(@class, 'menu-icon manage-toolbar-help')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		// verify help window
		selenium.click("css=span.menu-icon.manage-toolbar-help");
		assertTrue(selenium.isTextPresent("Welcome to Perforce Chronicle"));;
		
		   quart_detailid   = "9715";
		   quart_testname   = "HelpContent";
		   quart_description= "Check Homepage elements - check help content";
		if (selenium.isTextPresent("Welcome to Perforce Chronicle"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		selenium.open(baseurl);
		waitForElements("link=Logout");
	
	}
}


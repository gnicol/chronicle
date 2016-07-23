package tests;

import java.io.FileWriter;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> modules and verifies the pages title


public class HomePageNoLoginVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private static String quart_scriptname = "HomePageNoLoginVerify";
	public static String clientCodeline = "";

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
		
		// get client codeline
		getLoggedInCodeline(); 
		
		// Verify Chronicle home page elements without logging in 
		HomePageNoLoginVerify();
				 
		waitForElements("link=Login");
		assertTrue(selenium.isElementPresent("link=Login"));  

	}
	

	public void  getLoggedInCodeline() throws Exception {
		
		manageMenu();
		Thread.sleep(1000);
		selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY); 
		
		waitForElements("dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1");		
		// get the client codeline while logged in and return it
		
		// get client codeline variable via xpath
		String clientCodelineAttribute = selenium.getText(CMSConstants.GET_CLIENT_SYSTEM_INFO_TEXT);
		// set delimiters for serverOS
		String delims_for_clientCodelineAttribute    = "[/-]";		  // parse out the "/" and "-"
		// split out the delimiters from serverOS
		String[] attributesLoggedIn  = clientCodelineAttribute.split(delims_for_clientCodelineAttribute);
		String space = " "; // the space is needed to match the home page codeline
		
		// parse serverOS info to appropriate fields 
		clientCodelineAttribute    = space + attributesLoggedIn [1];    // codeline - second element  	
		
		// Logout and verify Login link
		selenium.open(baseurl);
		waitForElements("link=Logout");

		selenium.click("link=Logout");
		waitForElements("link=Login");
		 
		backToHome();
		
		// get home page codeline variable via xpath
		String HomepageCodeline = selenium.getText(CMSConstants.GET_HOMEPAGE_CODELINE_NO_LOGIN);
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
		 
		
		// Debug code to verify codeline matching
		//writeFile(clientCodelineAttribute, HomepageCodeline, "","","");
		
		// match client codeline to homepage codeline
		// java string compare while ignoring case
		  String quart_detailid   = "8696";
		  String quart_testname   = "Codeline";
		  String quart_description= "Check Homepage codeline";
		 
		if (clientCodelineAttribute.equalsIgnoreCase(HomepageCodeline))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	}
	
	
	
	public void HomePageNoLoginVerify() throws Exception {
		
		// verify footer sitemap link
		assertTrue(selenium.isElementPresent(("//a[contains(@href, '/menu/sitemap')]")));
		
		String quart_detailid   = "7542";
		String quart_testname   = "SitemapLink";
		String  quart_description= "verify sitemap";
	
		if (selenium.isElementPresent(("//a[contains(@href, '/menu/sitemap')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		// verify HOME tab
		assertTrue(selenium.isElementPresent(("//a[contains(@class, 'home-page type-mvc')]")));
		
		quart_detailid   = "6456";
		  quart_testname   = "HomeTab";
		  quart_description= "verify home tab";
		
		if (selenium.isElementPresent(("//a[contains(@class, 'home-page type-mvc')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
	
		
		// verify Categories tab
		assertTrue(selenium.isElementPresent(("//a[contains(@href, '/category')]")));
		quart_detailid   = "7543";
		  quart_testname   = "CategoryTab";
		  quart_description= "verify category";
		
		if (selenium.isElementPresent(("//a[contains(@href, '/category')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		// verify Search tab
		quart_detailid   = "6457";
		  quart_testname   = "SearchTab";
		  quart_description= "verify search";
		
		if (selenium.isElementPresent(("//a[contains(@href, '/search')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		// verify Logout tab
		assertTrue(selenium.isElementPresent(("//a[contains(@href, '/user/login')]")));
				
		quart_detailid   = "6458";
		  quart_testname   = "LogoutTab";
		  quart_description= "verify logout tab";
		  
		if (selenium.isElementPresent(("//a[contains(@href, '/user/login')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		
		// verify Chronicle logo
		assertTrue(selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/business/images/logo.png')]")));
		quart_detailid   = "7544";
		  quart_testname   = "Logo";
		  quart_description= "verify logo";
		
		if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/business/images/logo.png')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		

		
		// verify powered by
		quart_detailid   = "6135";
		  quart_testname   = "Poweredby";
		  quart_description= "verify powered by";
				
		if (selenium.isTextPresent(("Powered by ")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		// verify powered by
		quart_detailid   = "6135";
		  quart_testname   = "PerforceChronicle";
		  quart_description= "verify powered by Perforce Chronicle";
				
		if (selenium.isTextPresent(("Perforce Chronicle")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
        else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }

		
		// verify powered by			
		   quart_detailid   = "6135";
		   quart_testname   = "PoweredbyLink";
		  quart_description= "Check Homepage elements - Powered by Perforce Chronicle link";
			if (selenium.isElementPresent(("//a[contains(@href, 'http://perforcechronicle.com/')]")))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
		
		
		selenium.open(baseurl);
		waitForElements("link=Login");
	}
}


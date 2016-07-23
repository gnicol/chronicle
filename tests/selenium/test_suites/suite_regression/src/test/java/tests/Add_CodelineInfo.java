package tests;

import java.io.FileWriter;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;

// This code captures the meta tag for version string and also gets the browser name and browser version
// It parses and splits the meta tag for codeline, clientchange, & serverchange.
// It parses the navigator.appVersion to for browser version
// It also calls an external method - getServerOS - in BaseTest.java for getting the serverOS string
// The codeline information is written to a file using writeFile in BaseTest.java

public class Add_CodelineInfo extends shared.BaseTest {
	
	private static String baseurl;
	private String redirecturl;
	private String usergroup;
	static String testType;
	private static String results;
	public static String serverOSAttribute = "";
	public static String serverCodeline    = "";
	public static String serverChange      = "";
	public static String clientCodeline	   = "";
	public static String clientChange 	   = "";

	@BeforeClass
	@Parameters({ "baseurl", "redirecturl", "usergroup" , "testType"})
	public void storeBaseURL(String baseurl, String redirecturl,
			String usergroup, String testType) { 
		this.baseurl = baseurl;
		this.redirecturl = redirecturl;
		this.usergroup = usergroup;
		Add_CodelineInfo.testType = testType;
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
		
		selenium.click("link=Home");
		Thread.sleep(2000);
	
		// Logout and verify Login link
		selenium.click("link=Logout");
		Thread.sleep(2000);
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		assertTrue(selenium.isElementPresent("link=Login"));  
	}
	 
	
	    
	@Parameters({"results"})
	@Test
			public static void addCodeLineInfo() throws Exception { 
			   	  			 
			try { 
		 		//String serverOSAttribute = selenium.getAttribute("xpath=//div[contains(@id, 'ServerVersion-status')]@message");
		
				// open file write & append onto the setup chronicle code results    
				FileWriter out = new FileWriter("Results.txt",true);
				//BufferedWriter out = new BufferedWriter(fstream); 
			 
				// get metatag attribute for version string 
				//String clientCodeline = selenium.getAttribute("//meta/@content");
				
					// set delimiters for versionstring into codeline, client change & server change 
				//String delims_for_codeline            = "[/]";	  // parse out the "/"
				//String delims_for_clientserver_change = "[/()]";  // parse out "/()"
				
				// split out the delimiters from codeline from metatag
				//String[] attributes  = clientCodeline.split(delims_for_codeline);
				//String[] attributes1 = clientCodeline.split(delims_for_clientserver_change); 
				
				// parse versionstring info to appropriate fields 
				//clientCodeline      = attributes [1];   // codeline - second element 
				//String clientChange = attributes1[2];   // clientchange - third element
				//String serverChange = attributes1[2];   // serverchange - third element
				
				// get browser attributes for browser name & version   
				String version     = selenium.getEval("navigator.appVersion;");   
				String browserName = selenium.getEval("navigator.appCodeName;");
				String platform	   = selenium.getEval("navigator.platform;"); 
				String userAgent   = selenium.getEval("navigator.userAgent;");
				String product     = "p4cms";
				//String testType	   = testType;
				
				// Split browser attributes to get the browser version 
				String browserVersion[] = version.split("()"); 
				version = browserVersion[0] + browserVersion[1] + browserVersion[2] + browserVersion[3];
				
				// set MacIntel to MACOSX otherwise use correct platform
				if (platform.equals("MacIntel"))
					platform = "MACOSX105X86";    
				else if (platform.equals("Linux i686"))
					platform = "LINUX26X86";    
				else if (platform.equals("Win32"))
					platform = "NTX86";    
				else if (platform.equals("Win64"))
					platform = "NTX86";
				else { platform = selenium.getEval("navigator.platform;"); } 
				
				// distinguish between browsers
				if (userAgent.contains("Chrome")) 
					{ browserName = "Chrome";
					  version = ""; }
				
				Thread.sleep(2000);
						
				// write to file all information    
			 	out.write("\n\nui: "                 + browserName+" " + version);  
				out.write("\nproduct:"               + product);                             
				//out.write("\nclientcodeline: "       + clientCodeline);
				out.write("\nclientcodeline: "       + getClientCodeline(clientCodeline));  // get from system info page using getText()
				out.write("\nservercodeline: "       + getServerCodeline(serverCodeline));  // get from system info page using getText()
				//out.write("\nclientchange: "         + clientChange);
				out.write("\nclientchange: "         + getClientChange(clientChange));      // get from system info page using getText()
				out.write("\nserverchange: "         + getServerChange(serverChange));      // get from system info page using getText()
				//out.write("\nclientos: "             + selenium.getEval("navigator.platform;"));
				out.write("\nclientos: " 			 + platform); 
				out.write("\nserveros: "             + getServerOS(serverOSAttribute));     // get from system info page using getText()	
				out.write("\nsuite:"                 + testType);                           // type of test
				//out.write("\nuserAgent:"             + userAgent);
				out.close();  
				
				//assertTrue(selenium.isElementPresent(("//meta[contains(@name, 'chronicle-version')]")));  
				 
				//selenium.open(baseurl);
				//Thread.sleep(2000);
				
			   } catch (Exception e){  //Catch exception if any 
				  System.err.println("Error: " + e.getMessage());}      
			}
		

	/*public void browserDetection()
	{
		var str;
		str='navigator.appCodeName: '+navigator.appCodeName;
		str+='navigator.appName: '+navigator.appName;
		str+='navigator.appVersion: '+navigator.appVersion;
		str+='navigator.cookieEnabled: '+navigator.cookieEnabled;
		str+='navigator.language: '+navigator.language;
		str+='navigator.mimeTypes: '+navigator.mimeTypes;
		str+='navigator.platform: '+navigator.platform;
		str+='navigator.plugins: '+navigator.plugins;
		str+='navigator.systemLanguage: '+navigator.systemLanguage;
		str+='navigator.userAgent: '+navigator.userAgent;
	 
		document.getElementById('description').innerHTML=str;
	}*/
	
	
/*	Output in Firefox:
		navigator.appCodeName: Mozilla
		navigator.appName: Netscape
		navigator.appVersion: 5.0 (Windows; en-US)
		navigator.cookieEnabled: true
		navigator.language: en-US
		navigator.mimeTypes: [object MimeTypeArray]
		navigator.platform: Win32
		navigator.plugins: [object PluginArray]
		navigator.systemLanguage: undefined
		navigator.userAgent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1

		Output in InternetExplorer:
		navigator.appCodeName: Mozilla
		navigator.appName: Microsoft Internet Explorer
		navigator.appVersion: 4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322)
		navigator.cookieEnabled: true
		navigator.language: undefined
		navigator.mimeTypes:
		navigator.platform: Win32
		navigator.plugins:
		navigator.systemLanguage: en-us
		navigator.userAgent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322)

		Output in Google Chrome:
		navigator.appCodeName: Mozilla
		navigator.appName: Netscape
		navigator.appVersion: 5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.30 Safari/525.13
		navigator.cookieEnabled: true
		navigator.language: en-US
		navigator.mimeTypes: [object MimeTypeArray]
		navigator.platform: Win32
		navigator.plugins: [object PluginArray]
		navigator.systemLanguage: undefined
		navigator.userAgent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.30 Safari/525.13*/
}

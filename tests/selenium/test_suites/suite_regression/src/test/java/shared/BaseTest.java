package shared;
import java.io.File;
import java.io.FileWriter;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Random;

import jxl.Cell;
import jxl.Sheet;
import jxl.Workbook;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.openqa.selenium.server.RemoteControlConfiguration;
import org.openqa.selenium.server.SeleniumServer;
import org.testng.ITestResult;
import org.testng.Reporter;
import org.testng.annotations.AfterClass;
import org.testng.annotations.AfterMethod;
import org.testng.annotations.AfterSuite;
import org.testng.annotations.BeforeClass;
import org.testng.annotations.BeforeMethod;
import org.testng.annotations.BeforeSuite;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Optional;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import tests.Add_CodelineInfo;
import tests.CMSConstants;

import com.thoughtworks.selenium.DefaultSelenium;
import com.thoughtworks.selenium.SeleneseTestBase;
import com.thoughtworks.selenium.Selenium;

//BaseTest Class based on SeleneseTestCase
//@author Simon Orchanian

// This class is used for all reusable methods that are called within the test framework
// It includes starting & stopping selenium server, browser sessions, login, creating/editing content, 
// data provider, Chronicle setup methods, writeFile methods for detail id's, getting codeline info...

public class BaseTest extends SeleneseTestBase {
	
	private SeleniumServer server;
	private String results;
	public static Selenium selenium;
	public static String browser;
	public static int port;
	public static String hostname;
	public static String baseurl;
	public static String datapath;
	private static boolean maximizeByDefault = true;
	public static Logger logger = Logger.getLogger(BaseTest.class.getName());
	public enum BrowserSchedule {OncePerTest,OncePerClass;}

		@BeforeSuite(alwaysRun = true)
		@Parameters( { "browser", "hostname", "baseurl", "port", "datapath", "maximizeByDefault", "firefox-profile-location" })
		public void configureSuite(String browser, String hostname, String loginurl, int port,
								   @Optional("") String datapath,
								   @Optional("TRUE") String maximizeByDefault,
		 						   @Optional("") String firefoxProfileLocation) throws Exception {
			BaseTest.browser = browser;
			BaseTest.port = port;
			BaseTest.hostname = hostname;
			BaseTest.baseurl = loginurl;
			BaseTest.datapath = datapath;
			BaseTest.maximizeByDefault = maximizeByDefault.equalsIgnoreCase("TRUE");
			
			PropertyConfigurator.configure("log4j.properties");
		 	// **** THIS IS COMMENTED IF YOU ARE GOING TO RUN COMMAND LINE (MVN VERIFY) OR CI'ed ON ELECTRIC COMMANDER **** //
			// **** PLEASE UNCOMMENT IF YOU INTEND ON RUNNING IN ECLIPSE **** //
			//startSeleniumServer(hostname, firefoxProfileLocation);
	}
	
	public BrowserSchedule getBrowserSchedule() {
		return BrowserSchedule.OncePerClass;
	}
	
	@BeforeClass(alwaysRun = true)
	public void openBrowserBeforeEachClass() {
		if(getBrowserSchedule() == BrowserSchedule.OncePerClass) {
			openBrowser(BaseTest.browser, BaseTest.hostname, BaseTest.baseurl, BaseTest.port);
		}	}
	
	@BeforeMethod(alwaysRun = true)
	public void openBrowserBeforeEachTest() {
		if(getBrowserSchedule() == BrowserSchedule.OncePerTest) {
			openBrowser(BaseTest.browser, BaseTest.hostname, BaseTest.baseurl, BaseTest.port);
		}	}
	
	@AfterMethod(alwaysRun = true)
	public void closeBrowserAfterEachTest() {
		if(getBrowserSchedule() == BrowserSchedule.OncePerTest) {
			closebrowser();
		}	}
	
	@AfterClass(alwaysRun = true)
	public void closeBrowserAfterEachClass() {
		if(getBrowserSchedule() == BrowserSchedule.OncePerClass) {
			closebrowser();
		}	}
	
	@AfterSuite(alwaysRun = true)
	public void stopSeleniumServer() {
		if(server != null) {
			server.stop();
		}	}
	
	//@param hostname  ,  @param firefoxProfileLocation
	// Starts the Selenium server, use specified firefoxProfile for localhost.
	public void startSeleniumServer(String hostname, String firefoxProfileLocation){
		try {
			if("localhost".equals(hostname)) {
				if(!"".equals(firefoxProfileLocation)) {
					RemoteControlConfiguration config = new RemoteControlConfiguration();
					config.setFirefoxProfileTemplate(new File(firefoxProfileLocation));
					config.setTrustAllSSLCertificates(true);
					config.setAvoidProxy(false);
					//config.setProxyInjectionModeArg(true);
					//config.
					server = new SeleniumServer(config);
				}
				else {
					RemoteControlConfiguration config = new RemoteControlConfiguration();
					config.setTrustAllSSLCertificates(true);
					config.setAvoidProxy(false);
					//config.setProxyInjectionModeArg(true);
					server = new SeleniumServer(config);		
				}		
				server.start();
			}
		} catch (Exception t) {
			logger.error("Unable to start Selenium server", t);
			fail("Unable to start Selenium server: " + t.getMessage());
		}
	}
	
	
	/**
	 * @param browser    @param hostname      @param baseurl
	 */
	public void openBrowser(String browser, String hostname, String loginurl, int port) {
		//int port = 4444;
		
		if(hostname.indexOf(":") != -1) {
			String[] splitURL = hostname.split(":");
			port = Integer.parseInt(splitURL[1]);
			hostname = splitURL[0];
		}
		else if (!hostname.matches("localhost")) {
			port = 4448;
		}
		
		selenium = new DefaultSelenium(hostname, port, browser, loginurl);
		
		selenium.start();
		selenium.setTimeout("350000");
		if(BaseTest.maximizeByDefault) {
			selenium.windowMaximize();
			selenium.windowFocus();
		}
	}

	public void closebrowser() {
		if (selenium != null) {
			selenium.close();
			selenium.stop();
		}
	}
	
     
    //**** @param xlFilePath -the path of the excel document  
	//@param sheetName
    //@param tableName
    //This method retrieves a section of the excel sheet as data array, just like a table within a database
    public String[][] getDataArray(String xlFilePath, String sheetName, String tableName) throws Exception{
        	String[][] dataArray=null;
            Workbook workbook = Workbook.getWorkbook(new File(xlFilePath));
            Sheet sheet = workbook.getSheet(sheetName); 
            int startRow,startCol, endRow, endCol,ci,cj;
            
            Cell tableStart=sheet.findCell(tableName);
            startRow=tableStart.getRow();
            startCol=tableStart.getColumn();

            Cell tableEnd= sheet.findCell(tableName, startCol+1,startRow+1, 100, 64000,  false);                
            endRow=tableEnd.getRow();
            endCol=tableEnd.getColumn();
            
            dataArray=new String[endRow-startRow-1][endCol-startCol-1];
            ci=0;

            for (int i=startRow+1;i<endRow;i++,ci++){
                cj=0;
                for (int j=startCol+1;j<endCol;j++,cj++){
                    dataArray[ci][cj]=sheet.getCell(j,i).getContents();
                }
            }
        return(dataArray);
    }
    
    @DataProvider(name = "DEV1Users")
    public Object[][] createData2() throws Exception{
        Object[][] retObjArr=getDataArray(datapath, "Users", "dev1_Users");
        return(retObjArr);
    }
    
    
    
//*********************** PERFORCE CHRONICLE CODE **********************************************************************************************************************************************//
    
    // Setup Chronicle after a fresh deploy
    public void chronicleSiteSetup() throws Exception {
    	    	
    	if (selenium.isTextPresent("Setup"))
			writeFile("7918", "pass", "BaseTest.java", "checkSetupReqPage", "Setup requirements page"); 
	        else  { writeFile("7918", "fail", "BaseTest.java","checkSetupReqPage", "Setup requirements page"); }
    	
    	// Setup requirements section
    	selenium.click("css=div.splash > a > img");
    	//selenium.click("//img[contains(@src,'/application/setup/resources/images/splash.png')]");
		Thread.sleep(2000);
		if (selenium.isTextPresent("Setup: Requirements"))
			writeFile("7707", "pass", "BaseTest.java","checkSetupReqPage1", "Setup requirements page"); 
	        else  { writeFile("7707", "fail", "BaseTest.java","checkSetupReqPage1", "Setup requirements page"); }
		
		if (selenium.isTextPresent("Check Requirements "))
			writeFile("1817", "pass", "BaseTest.java","checkReqs", "Check requirements"); 
	        else  { writeFile("1817", "fail", "BaseTest.java","checkReqs", "Check requirements"); }
		
		if (selenium.isTextPresent("Your environment meets all of the application requirements. "))
			writeFile("1817", "pass", "BaseTest.java","checkEnvChecklist", "Enironment checklist"); 
	        else  { writeFile("1817", "fail", "BaseTest.java","checkEnvChecklist", "Environent checklist"); }
		
		checkHeader();
		checkFooter();
		checkContactus();
		
		if (selenium.isElementPresent("//span[@id='dijit_form_Button_0_label']"))
			writeFile("7917", "pass", "BaseTest.java","checkContinue", "Setup - check continue"); 
	        else  { writeFile("7917", "fail", "BaseTest.java","checkContinue", "Setup - check continue"); }	
		
		if (selenium.isTextPresent("You have PHP version"))
			writeFile("7915", "pass", "BaseTest.java","checkPHPVersion", "Setup - php version"); 
	        else  { writeFile("7915", "fail", "BaseTest.java","checkPHPVersion", "Setup - php version"); }	
		
		if (selenium.isTextPresent("Request rewriting appears to be working correctly."))
			writeFile("7919", "pass", "BaseTest.java","checkCleanURLs", "Setup - Clean url's"); 
	        else  { writeFile("7919", "fail", "BaseTest.java","checkCleanURLs", "Setup - Clean url's"); }	
		
		if (selenium.isTextPresent("You have version"))
			writeFile("7920", "pass", "BaseTest.java","checkPerforceClient", "Setup - Perforce client"); 
	        else  { writeFile("7920", "fail", "BaseTest.java","checkPerforceClient", "Setup - Perforce client"); }	
		
		if (selenium.isTextPresent("Your data directory"))
			writeFile("7921", "pass", "BaseTest.java","checkDataDirectory", "Setup - Data directory"); 
	        else  { writeFile("7921", "fail", "BaseTest.java","checkDataDirectory", "Setup - Data directory"); }	
		
		if (selenium.isTextPresent("The APC extension is installed."))
			writeFile("7922", "pass", "BaseTest.java","checkOpCode", "Setup - Opcode"); 
	        else  { writeFile("7922", "fail", "BaseTest.java","checkOpCode", "Setup - Opcode"); }	
		
		
		// Click on continue: Setup Site storage section 
		selenium.click("//span[contains(@id, 'dijit_form_Button_0_label')]");		
		Thread.sleep(2000);
		
		if (selenium.isTextPresent("Site Storage"))
			writeFile("7917", "pass", "BaseTest.java","checkSiteStorage", "Setup - site storage"); 
	        else  { writeFile("7917", "fail","BaseTest.java", "checkSiteStorage", "Setup - site storage"); }	
		
		if (selenium.isTextPresent("Store the site's content:"))
			writeFile("7924", "pass", "BaseTest.java","checkSiteContentStorage", "Setup - site storage"); 
	        else  { writeFile("7924", "fail", "BaseTest.java","checkSiteContentStorage", "Setup - site storage"); }	
		
		if (selenium.isTextPresent("In a new Perforce Server on the same machine as Chronicle"))
			writeFile("7926", "pass", "BaseTest.java","checkPerforceServerSameMachine", "Setup - site storage radio buttons"); 
	        else  { writeFile("7926", "fail","BaseTest.java", "checkPerforceServerSameMachine", "Setup - site storage radio buttons"); }	
		
		if (selenium.isTextPresent("In a new depot on an existing Perforce Server"))
			writeFile("7926", "pass","BaseTest.java", "checkNewDepotExistingServer", "Setup - site storage radio buttons"); 
	        else  { writeFile("7926", "fail", "BaseTest.java","checkNewDepotExistingServer", "Setup - site storage radio buttons"); }	
		
		if (selenium.isElementPresent("//span[@id='continue_label']"))
			writeFile("7923", "pass", "BaseTest.java","checkSiteStorageContinue", "Setup - site storage check continue"); 
	        else  { writeFile("7923", "fail","BaseTest.java", "checkSiteStorageContinue", "Setup - site storage check continue"); }	
		
		if (selenium.isElementPresent("//span[@id='goback_label']"))
			writeFile("7923", "pass", "BaseTest.java","checkSiteStorageGoBack", "Setup - site storage check go back"); 
	        else  { writeFile("7923", "fail", "BaseTest.java","checkSiteStorageGoBack", "Setup - site storage check go back"); }	
		
		if (selenium.isTextPresent("Setup: Site Storage"))
			writeFile("7708", "pass", "BaseTest.java","checkSiteStorageText", "Setup - site storage - text"); 
	        else  { writeFile("7708", "fail", "BaseTest.java","checkSiteStorageText", "Setup - site storage - text"); }
		
		// click on new depot radio button
		selenium.click("id=serverType-existing");
		Thread.sleep(2000);
		if (selenium.isTextPresent("Enter the host name and port of your Perforce Server (e.g. localhost:1666)"))
			writeFile("7929", "pass", "BaseTest.java","checkSiteStorageNewDepot", "site storage - new depot radio button"); 
	        else  { writeFile("7929", "fail","BaseTest.java", "checkSiteStorageNewDepot", "site storage - new depot radio button"); }
		
		// click on existing Perforce server
		selenium.isTextPresent("Enter the host name and port of your Perforce Server (e.g. localhost:1666)");
		
		// click continue for existing perforce server
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		waitForText("Enter server administrator username and password");
		
		if (selenium.isTextPresent("Enter server administrator username and password:"))
			writeFile("7944", "pass", "BaseTest.java","checkSiteStorageExistingServer","site storage - existing perforce server"); 
	        else  { writeFile("7944", "fail", "BaseTest.java","checkSiteStorageExistingServer","site storage - existing perforce server"); }
		
		
		if (selenium.isTextPresent("This user must already exist and have super level privileges on the server:"))
			writeFile("7944", "pass", "BaseTest.java","checkSiteStorageExistingServer","site storage - existing perforce server text"); 
	        else  { writeFile("7944", "fail", "BaseTest.java","checkSiteStorageExistingServer","site storage - existing perforce server text"); }

		
		if (selenium.isElementPresent("//input[@id='password']"))
			writeFile("7948", "pass", "BaseTest.java","checkSiteStoragePWField","site storage - check pw field"); 
	        else  { writeFile("7948", "fail","BaseTest.java", "checkSiteStoragePWField","site storage - check pw field"); }	
		
		if (selenium.isTextPresent("Password"))
			writeFile("7949", "pass", "BaseTest.java","checkSiteStoragePWLabel","site storage - pw label"); 
	        else  { writeFile("7949", "fail", "BaseTest.java","checkSiteStoragePWLabel","site storage - pw label"); }
		
		if (selenium.isTextPresent("User Name"))
			writeFile("7947", "pass", "BaseTest.java","checkSiteStorageUsernameLabel","site storage - username label"); 
	        else  { writeFile("7947", "fail", "BaseTest.java","checkSiteStorageUsernameLabel","site storage - username label"); }
		
		// go back to previous screen and continue to setup
		selenium.click("name=goback");
		selenium.click("id=goback_label");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		
		//7925, 7930, 7928, 7932, 7931
		checkHeader();
		checkFooter();
		checkContactus();
		
		
		// Continue to next screen: Setup Admin user  [local server]
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		checkHeader();
		checkFooter();
		checkContactus();
		if (selenium.isTextPresent("Choose a username and password for the Chronicle administrator:"))
			writeFile("7934", "pass", "BaseTest.java","siteAdminChooseUsernamePW", "site admin - choose username-pw"); 
	        else  { writeFile("7934", "fail", "BaseTest.java","siteAdminChooseUsernamePW", "site admin - choose username pw"); }
		
		if (selenium.isTextPresent("The Chronicle administrator has full access"))
			writeFile("7935", "pass", "BaseTest.java","siteAdminCheckHeader","site admin - check header"); 
	        else  { writeFile("7935", "fail", "BaseTest.java","siteAdminCheckHeader","site admin - check header"); }
		
		if (selenium.isElementPresent("//span[@id='continue_label']"))
			writeFile("7933", "pass","BaseTest.java", "siteAdminCheckContinue","site admin - check continue"); 
	        else  { writeFile("7933", "fail", "BaseTest.java","siteAdminCheckContinue","site admin - check continue"); }	
		
		if (selenium.isElementPresent("//span[@id='goback_label']"))
			writeFile("7933", "pass", "BaseTest.java","siteAdminCheckGoBack","site admin - check go back"); 
	        else  { writeFile("7933", "fail", "BaseTest.java","siteAdminCheckGoBack","site admin - check go back"); }	
		
		if (selenium.isTextPresent("Setup: Administrator"))
			writeFile("1818", "pass", "BaseTest.java","setupAdministrator","site admin - check header"); 
	        else  { writeFile("1818", "fail","BaseTest.java", "setupAdministrator","site admin - check header"); }
		
		if (selenium.isElementPresent("//input[@id='password']"))
			writeFile("7941", "pass", "BaseTest.java","siteAdminCheckPWField","site admin - check pw field"); 
	        else  { writeFile("7941", "fail", "BaseTest.java","siteAdminCheckPWField","site admin - check pw field"); }	
		
		if (selenium.isElementPresent("//input[@id='user']"))
			writeFile("7943", "pass", "BaseTest.java","siteAdminCheckUsername","site admin - check username field"); 
	        else  { writeFile("7943", "fail", "BaseTest.java","siteAdminCheckUsername","site admin - check username field"); }	
		
		if (selenium.isElementPresent("//input[@id='passwordConfirm']"))
			writeFile("7940", "pass", "BaseTest.java","siteAdminCheckPWConfirm","site admin - check pw confirm field"); 
	        else  { writeFile("7940", "fail", "BaseTest.java","siteAdminCheckPWConfirm","site admin - check pw confirm field"); }	
		
		if (selenium.isElementPresent("//input[@id='email']"))
			writeFile("7942", "pass", "BaseTest.java","siteAdminCheckEmail","site admin - check email field"); 
	        else  { writeFile("7942", "fail", "BaseTest.java","siteAdminCheckEmail","site admin - check email field"); }	
		
		
		// username empty check
		selenium.type("id=user", "");
		selenium.type("id=password", "p4cms123");
		selenium.type("id=passwordConfirm", "p4cms123");
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		Thread.sleep(2000);
		if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile("7936", "pass", "BaseTest.java","siteAdminCheckUserReq", "site admin - check user required"); 
	        else  { writeFile("7936", "fail", "BaseTest.java","siteAdminCheckUserReq", "site admin - user required"); }
		
		selenium.click("name=goback");
		selenium.click("id=goback_label");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		Thread.sleep(3000);
		selenium.type("id=user", "p4cms");
		selenium.type("id=email", "");
		selenium.type("id=password", "p4cms123");
		selenium.type("id=passwordConfirm", "p4cms123");
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		Thread.sleep(2000);
		// email empty check
		if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile("7937", "pass", "BaseTest.java","siteAdminCheckEmailReq" , "site admin - check email required"); 
	        else  { writeFile("7937", "fail", "BaseTest.java", "siteAdminCheckEmailReq", "site admin - email required"); }
			
		// enter user admin info
		selenium.click("name=goback");
		selenium.click("id=goback_label");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		Thread.sleep(2000);
		selenium.type("id=user", "p4cms");
		// check required fields
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		Thread.sleep(2000);
		if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile("7938", "pass","BaseTest.java", "siteAdminCheckPWEmpty", "site admin - check pw required"); 
	        else  { writeFile("7938", "fail", "BaseTest.java","siteAdminCheckPWEmpty", "site admin - pw required"); }
		Thread.sleep(2000);
		// check required fields
		selenium.type("id=password", "p4cms123");
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		Thread.sleep(2000);
		if (selenium.isTextPresent("Value is required and can't be empty"))
			writeFile("7939", "pass", "BaseTest.java","siteAdminConfirmPWReq" , "site admin - check confirm pw required"); 
	        else  { writeFile("7939	", "fail", "BaseTest.java","siteAdminConfirmPWReq", "site admin - check confirm pw required"); }

		// enter proper info
		selenium.type("id=password", "p4cms123");
		selenium.type("id=passwordConfirm", "p4cms123");
		
	
		
		// Continue to next screen: Setup site
		selenium.click("name=continue");
		selenium.click("id=continue_label");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		checkHeader();
		checkFooter();
		checkContactus();
		assertTrue(selenium.isElementPresent("//input[@id='title']"));
		assertTrue(selenium.isElementPresent("//textarea[@id='urls']"));
		
		if (selenium.isTextPresent("Choose a title and address for the site:"))
			writeFile("7950", "pass", "BaseTest.java","checkNameandAddress", "Setup site - check header"); 
	        else  { writeFile("7950", "fail","BaseTest.java", "checkNameandAddress", "Setup site - check header"); }
		
		if (selenium.isElementPresent("//span[@id='create_label']"))
			writeFile("7958", "pass", "BaseTest.java","setupSiteCheckContinue", "Setup site - check continue"); 
	        else  { writeFile("7958", "fail", "BaseTest.java","setupSiteCheckContinue", "Setup site - check continue"); }	
		
		if (selenium.isElementPresent("//span[@id='goback_label']"))
			writeFile("7958", "pass", "BaseTest.java","setupSiteCheckGoBack", "Setup site - check go back"); 
	        else  { writeFile("7958", "fail", "BaseTest.java","setupSiteCheckGoBack", "Setup site - check go back"); }	
		
		if (selenium.isTextPresent("Setup: Site"))
			writeFile("7709", "pass","BaseTest.java", "checkTopLeftCorner", "Setup site - top left corner"); 
	        else  { writeFile("7709", "fail", "BaseTest.java","checkTopLeftCorner", "Setup site - top left corner"); }
		
		if (selenium.isTextPresent("Provide a list of urls for which this site will be served."))
			writeFile("7957", "pass", "BaseTest.java","checkSiteDomain", "Setup site - check site address"); 
	        else  { writeFile("7957", "fail","BaseTest.java", "checkSiteDomain", "Setup site - check site address"); }
		
		// enter description
		selenium.type("id=description", "Perforce Chronicle Website");
		
		if (selenium.isElementPresent("//textarea[@id='urls']"))
			writeFile("7953", "pass", "BaseTest.java","checkAddressInputtable", "Setup site - address inputtable"); 
	        else  { writeFile("7953", "fail", "BaseTest.java","checkAddressInputtable", "Setup site - address inputtable"); }	
		
		if (selenium.isElementPresent("//textarea[@id='description']"))
			writeFile("7960", "pass","BaseTest.java", "checkDescInputtable", "Setup site - desc inputable"); 
	        else  { writeFile("7960", "fail", "BaseTest.java","checkDescInputtable", "Setup site - desc inputable"); }	
		
		if (selenium.isTextPresent("Description"))
			writeFile("7959", "pass", "BaseTest.java","checkDesc", "Setup site - check desc"); 
	        else  { writeFile("7959", "fail", "BaseTest.java","checkDesc", "Setup site - check desc"); }
		
		if (selenium.isTextPresent("Enter a short summary of your site."))
			writeFile("7961", "pass", "BaseTest.java","checkSummaryOfSite", "Setup site - check desc"); 
	        else  { writeFile("7961", "fail", "BaseTest.java","checkSummaryOfSite", "Setup site - check desc"); }
		
		if (selenium.isTextPresent("Enter a recognizable title for this site."))
			writeFile("7955", "pass","BaseTest.java", "checkSiteName", "Setup site - check site name"); 
	        else  { writeFile("7955", "fail","BaseTest.java", "checkSiteName", "Setup site - check site name"); }
		
		if (selenium.isElementPresent("//input[@id='title']"))
			writeFile("7954", "pass", "BaseTest.java","checkSiteInputtable", "Setup site - site inputable"); 
	        else  { writeFile("7954", "fail", "BaseTest.java","checkSiteInputtable", "Setup site - site inputable"); }	
	
		
		// Create Site for setup 
		selenium.click("name=create");
		selenium.click("id=create_label");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		selenium.isTextPresent("You have successfully created the site");
		
		// View Site
		//selenium.click("css=input.dijitOffScreen");
		selenium.click("//span[@id='dijit_form_Button_0_label']");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// Verify Website 
		//assertTrue(selenium.isElementPresent(("//a[contains(@class, 'home-page type-mvc')]")));  
		
		// Wsrite out to file for verifying new role exists
		if (selenium.isElementPresent(("//a[contains(@href, '/user/login')]")))
			writeFile("1045", "pass","BaseTest.java", "createSiteAndVerify","Create Site and verify"); 
	        else  { writeFile("1045", "fail", "BaseTest.java","createSiteAndVerify","Create Site and verify"); }
		Thread.sleep(4000);
    } 
    
 
    
    
    //**** CODE FOR EXTERNAL AUTHENTICATION ****//
    
    public void externalAuthentication() throws Exception {
    		selenium.click("css=div.splash > a > img");
    		// Click on continue: Setup Site storage section 
    		selenium.click("//span[contains(@id, 'dijit_form_Button_0_label')]");	
    		
    		selenium.click("id=serverType-existing");
    		Thread.sleep(2000);
    		
    		// click on existing Perforce server
    		selenium.isTextPresent("Enter the host name and port of your Perforce Server (e.g. localhost:1666)");
    		
    		// click continue for existing perforce server
    		selenium.click("name=continue");
    		selenium.click("id=continue_label");
    		waitForText("Enter server administrator username and password");	
    	
    		// enter external authentication server
    		selenium.type("name=port", "play.perforce.com:1414");
    	 		
    		// click continue for existing perforce server
    		selenium.click("name=continue");
    		selenium.click("id=continue_label");
    		Thread.sleep(2000);
    		
    		//enter username/pw
    		selenium.type("name=user", "bruno");
    		selenium.type("name=password", "secret");
    		selenium.type("name=systemPassword", "secret");
    		selenium.click("name=continue");
    		selenium.click("id=continue_label");
    		Thread.sleep(2000);
    		
    		// Create Site for setup 
    		selenium.click("name=create");
    		selenium.click("id=create_label");
    		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
    		selenium.isTextPresent("You have successfully created the site");
    		
    		// View Site
    		//selenium.click("css=input.dijitOffScreen");
    		selenium.click("//span[@id='dijit_form_Button_0_label']");
    		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
    	}
    
	//@Parameters({ "results"})
    // Write results file to operating system   
    public void writeFile(String detailsID, String passfail, String scriptname, String description, String testname) {
	   
	         try {
	    		//String results = null; 
				FileWriter out = new FileWriter("Results.txt", true);
	    		//BufferedWriter out = new BufferedWriter(fstream);
	    			//out.write(selenium.getEval("BROWSER: navigator.userAgent;"));  //out.write(selenium.getEval("navigator.appCodeName; "));   //out.write(selenium.getEval("navigator.appVersion;"));
	    			//out.write("\n codeline: ");  out.write(selenium.getEval("navigator.systemLanguage;"));
	    			out.write("\n\nresult:" + detailsID + ":" + passfail + ":" + scriptname + ":" + description + ":" + testname);
	    			out.close();
	    	 
	    	}catch (Exception e){  //Catch exception if any 
	    		  System.err.println("Error: " + e.getMessage());}        
	      } 
	    
	    public static void writeFileDebug(String detailsID, String passfail, String scriptname, String description, String testname) {
	 	   
	         try {
	    		//String results = null; 
				FileWriter out = new FileWriter("Results.txt", true);
	    		//BufferedWriter out = new BufferedWriter(fstream);
	    			//out.write(selenium.getEval("BROWSER: navigator.userAgent;"));  //out.write(selenium.getEval("navigator.appCodeName; "));   //out.write(selenium.getEval("navigator.appVersion;"));
	    			//out.write("\n codeline: ");  out.write(selenium.getEval("navigator.systemLanguage;"));
				out.write("\n\noutput:" + detailsID + ":" + passfail + ":" + scriptname + ":" + description + ":" + testname);
	    			out.close(); 
	    	 
	    	}catch (Exception e){  //Catch exception if any 
	    		  System.err.println("Error: " + e.getMessage());}      
	      } 
	    
	    
	    // ** Get server os information from manage system info page **
	    public static String getServerOS(String serverOS) throws Exception {
	    	
			// Click on Manage --> Manage content types
			manageMenu();
			Thread.sleep(1000);
			selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);		
			selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
			Thread.sleep(2000);
			 
			// get serverOS variable via xpath
			String serverOSAttribute = selenium.getText(CMSConstants.GET_SERVER_SYSTEM_INFO_TEXT);
			// set delimiters for serverOS 
			String delims_for_serverOSAttribute  = "[/]";	  // parse out the '/'
			// split out the delimiters from serverOS
			String[] attributes  = serverOSAttribute.split(delims_for_serverOSAttribute);
			// parse serverOS info to appropriate fields 
			serverOSAttribute    = attributes [1];   // codeline - second element  
			
			// return value 
			return serverOSAttribute;		
}
	    // ** Get server code line info from manage system info page ** 
	    public static String getServerCodeline(String serverCodeline) throws Exception {
	    	
	    	// Click on Manage --> Manage content types
			manageMenu();
			Thread.sleep(1000);
			selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);	
			selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
			Thread.sleep(2000);
			
			// get serverOS variable via xpath
			String serverCodelineAttribute = selenium.getText(CMSConstants.GET_SERVER_SYSTEM_INFO_TEXT);
			// set delimiters for serverOS
			String delims_for_serverCodelineAttribute  = "[/(]";	  // parse out the '/'
			// split out the delimiters from serverOS
			String[] attributes  = serverCodelineAttribute.split(delims_for_serverCodelineAttribute);
			// parse serverOS info to appropriate fields 
			serverCodelineAttribute    = attributes [2];   // codeline - second element  
			
			// return value 
			return serverCodelineAttribute;		
  }
	    // ** Get server change info from manage system info page **
	    public static String getServerChange(String serverChange) throws Exception {
    	
	    	// Click on Manage --> Manage content types
			manageMenu();
			Thread.sleep(1000);
			selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);		 
			selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
			Thread.sleep(2000);
			
			// get serverOS variable via xpath
			String serverChangeAttribute = selenium.getText(CMSConstants.GET_SERVER_SYSTEM_INFO_TEXT);
			// set delimiters for serverOS
			String delims_for_serverChangeAttribute   = "[/()]";	  // parse out the "/"
			// split out the delimiters from serverOS
			String[] attributes  = serverChangeAttribute.split(delims_for_serverChangeAttribute);
			
			// parse serverOS info to appropriate fields 
			serverChangeAttribute    = attributes [3];   // codeline - second element  
			
			// return value 
			return serverChangeAttribute;	
  }
	    // ** Get client codeline info from manage system info page **
	    public static String getClientCodeline(String clientCodeline) throws Exception {
    	
	    	// Click on Manage --> Manage content types
			manageMenu();
			Thread.sleep(1000);
			selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);	
			
			// get serverOS variable via xpath
			String clientCodelineAttribute = selenium.getText(CMSConstants.GET_CLIENT_SYSTEM_INFO_TEXT);
			// set delimiters for serverOS
			String delims_for_clientCodelineAttribute   = "[/()]";	  // parse out the "/"
			// split out the delimiters from serverOS
			String[] attributes  = clientCodelineAttribute.split(delims_for_clientCodelineAttribute);
			
			// parse serverOS info to appropriate fields 
			clientCodelineAttribute    = attributes [1];   // codeline - second element  
			
			// return value 
			return clientCodelineAttribute;		 
  }    
	    // ** Get client change info from manage system info page **
	    public static String getClientChange(String clientChange) throws Exception {
    	
	    	// Click on Manage --> Manage content types
			manageMenu();
			Thread.sleep(1000);
			selenium.click(CMSConstants.MANAGE_SYSTEM_INFO_VERIFY);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);	
			
			// get serverOS variable via xpath
			String clientChangeAttribute = selenium.getText(CMSConstants.GET_CLIENT_SYSTEM_INFO_TEXT);
			// set delimiters for serverOS
			String delims_for_clientChangeAttribute   = "[/()]";	  // parse out the "/"
			// split out the delimiters from serverOS
			String[] attributes  = clientChangeAttribute.split(delims_for_clientChangeAttribute);
			
			// parse serverOS info to appropriate fields 
			clientChangeAttribute    = attributes [2];   // codeline - third element  
			
			// return value 
			return clientChangeAttribute;	 	
  }
    
	    
	    // Chronicle login method
	    public void chronicleLogin(String username, String password) throws Exception{	
	    	logger.info("Login to ENV: " + BaseTest.baseurl);
	    	logger.info("User: " + username + " Password: " + password);	
			// Open base url
			selenium.open(baseurl);
			selenium.windowFocus();
			selenium.getAllWindowIds();
			
			if(selenium.isElementPresent("Link=Continue to this website (not recommended).")){ 	
				selenium.click("Link=Continue to this website (not recommended).");
			  	selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				}
			
			// Click on login link and enter username & password  
			selenium.click("link=Login");	
			 
			selenium.waitForCondition("selenium.isElementPresent(\"//input[@id='partial-user']\")", "10000");
			selenium.type("id=partial-user", username);
			Thread.sleep(1000);
			selenium.waitForCondition("selenium.isElementPresent(\"//input[@id='partial-password']\")", "10000");
			selenium.type("id=partial-password", password);
		    Thread.sleep(1000);

			// Click to login to Website 
			selenium.click("name=login"); 
			waitForVisible("xpath=//*[@id='p4cms-ui-notices']");
			
			// Verify growl message 
			assertTrue(selenium.isVisible("xpath=//*[@id='p4cms-ui-notices']"));
			//assertTrue(selenium.isVisible("xpath=//*[@class='message']"));
	    } 
	     
	    
	  // create new user with random number generator for username
	  public void newUser() throws Exception {	 
    	// Open base url
		selenium.open(baseurl);
		selenium.windowFocus();
		selenium.getAllWindowIds();
		 if(selenium.isElementPresent("Link=Continue to this website (not recommended)."))
		   { 	
			selenium.click("Link=Continue to this website (not recommended).");
			selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
				}
		selenium.click("link=Login");	
		selenium.waitForCondition("selenium.isElementPresent(\"//span[@id='partial-addNewUser_label']\")", "10000");
		
		// generate random numbers for username
		Random  generator = new Random();
		int newNumber = generator.nextInt(10000);
		String userName = "chronicle-test" + newNumber; 

		selenium.click("id=partial-addNewUser_label");
		Thread.sleep(2000);
		selenium.type("id=id", userName);
		selenium.type("id=email", "chronicle-test@perforce.com");
		selenium.type("id=fullName", "chronicle test");
		selenium.type("id=password", "Chronicle612");
		selenium.type("id=passwordConfirm", "Chronicle612");
		// Login to Website
		selenium.click("name=save");
		selenium.click("id=save_label");
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		Thread.sleep(1000);
		assertTrue(selenium.isVisible(("//div[contains(@id,'p4cms-ui-notices')]")));
		
		// check new user growl and write to file
		if(selenium.isVisible(("//div[contains(@id, 'p4cms-ui-notices')]")))
			writeFile("1404", "pass","BaseTest.java", "createRoleVerifyGrowl", "Create Role and Verify growl"); 
        else  { writeFile("1404", "fail","BaseTest.java", "createRoleVerifyGrowl", "Create Role and Verify growl"); }				
    }
     
    
      // create new user for base state
      public void createNewUserBaseState() throws Exception {
        selenium.open(baseurl);
    	waitForElements("link=Home");
    	selenium.click("css=#p4cms_ui_toolbar_MenuButton_0 > span.menu-handle.type-heading");
    	Thread.sleep(2000);
		selenium.click(CMSConstants.MANAGE_USERS);
		Thread.sleep(2000);
		
		// click to create a new users
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add User']");
		Thread.sleep(2000);
		selenium.type("id=id", "chronicle-test");
		selenium.type("id=email", "chronicle-test@perforce.com");
		selenium.type("id=fullName", "Chronicle test");
		selenium.type("id=password", "chronicle612");
		selenium.type("id=passwordConfirm", "chronicle612");
		selenium.click("id=save_label");
		Thread.sleep(2000);
		
		selenium.click("id=dijit_form_Button_0_label");
		selenium.click("//input[@value='Add User']");
		Thread.sleep(1000);
		selenium.type("id=id", "chronicle-test0");
		selenium.type("id=email", "chronicle-test0@perforce.com");
		selenium.type("id=fullName", "Chronicle test0");
		selenium.type("id=password", "chronicle612");
		selenium.type("id=passwordConfirm", "chronicle612");
		selenium.click("id=save_label");
		Thread.sleep(2000);
    } 
    
    
    // manage menu for re-use in different classes
    public static void manageMenu() throws Exception {
    	Thread.sleep(2000);
		selenium.click("css=#p4cms_ui_toolbar_MenuButton_0 > span.menu-handle.type-heading");
		waitForText("System");
		} 
    
    // return back to Home Website
    public void backToHome() throws Exception { 
    	selenium.open(baseurl);
		selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
    }
     
    public void verifyContentElements() throws Exception {
    	selenium.click("css=span.menu-icon.manage-toolbar-content-add");
    	Thread.sleep(2000);
    	//waitForElements("//ul[contains(@class,'content-types')]");
    }
   
    public void verifyEditButtonDisplayed() throws Exception {
    	// verify edit button is displayed
		selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
    }
    
    public void verifyHistoryList() throws Exception {
    	assertTrue(selenium.isElementPresent(("//span[contains(@id, 'toolbar-content-history')]")));
    	selenium.click("id=toolbar-content-history");
    } 
    
    public void checkHeader() {
    	// check header for the setup
    	if (selenium.isElementPresent("//img[@alt='Chronicle logo']"))
			writeFile("7911", "pass", "BaseTest.java","setupSiteCheckHeader", "Setup site - check header"); 
	        else  { writeFile("7911", "fail","BaseTest.java", "setupSiteCheckHeader", "Setup site - check header"); }	
    }
    
    public void checkFooter() {
    	// check footer for the setup
    	if (selenium.isElementPresent("//div[@id='manage-footer']"))
			writeFile("7912", "pass","BaseTest.java", "setupSiteCheckFooter", "Setup site - check footer"); 
	        else  { writeFile("7912", "fail","BaseTest.java", "setupSiteCheckFooter", "Setup site - check footer"); }
    }
    
    public void checkContactus() { 
    	// check contact us for the setup
    	if (selenium.isElementPresent("//a[@href='mailto:support@perforce.com?subject=Perforce%20Chronicle%20Support%20Request']"))
			writeFile("7913", "pass","BaseTest.java", "checkContactUs", "Setup site - check contact us"); 
	        else  { writeFile("7913", "fail", "BaseTest.java","checkContactUs", "Setup site - check contact us"); }
    }
     
     
     // add content
     public void addManageContent() throws Exception {
 		// click manage menu then click for content
    	// Basic page
 		verifyContentElements();
 		browserSpecificBasicPage();
		waitForElements("id=add-content-toolbar-button-Save_label");	
 		addBasicPage();    
 		
 		// Blog post
 		verifyContentElements();
 		browserSpecificBlogPost();
		waitForElements("id=add-content-toolbar-button-Save_label");
 		addBlogPost();
 			
 		// Press release
 		verifyContentElements();  
 		browserSpecificPressRelease();
		waitForElements("id=add-content-toolbar-button-Save_label");
 		addPressRelease();
     }
       
     
     // **** SITE BRANCHING METHODS FOR SITEBRANCHINGVERIFY.java & SITEBRANCHINGPULLBRANCHFLOW.java **** //
     
     public void addContentSiteBranchingFlow() throws Exception {
  		// Basic page
  		browserSpecificBasicPageSiteBranchingFlow();
  		Thread.sleep(2000);		
     }
    

     public void browserSpecificBasicPageSiteBranchingFlow() throws Exception {
     	
    	 if (browser.equalsIgnoreCase("*iexplore"))
		  {  verifyContentElements();
    		 selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li/span/a","");
		     Thread.sleep(2000); 
		     addBasicPageSiteBranchingFlow(); 
		   }
		else   
		{   
			verifyContentElements();
			// click on add link
			waitForElements("//ul[contains(@class,'content-types')]");
			// click for basic page 
			selenium.click("//a[@href='/-dev-/add/type/basic-page']"); 
			Thread.sleep(2000);		
			selenium.type("id=title", "Basic Page for Site Branching Flow");
	  		Thread.sleep(2000);
	  		
	  		// edit page
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			waitForElements("id=edit-content-toolbar-button-Menus_label");
	  		// click Menu and position this page before search
			selenium.click("id=edit-content-toolbar-button-Menus_label");
		
			waitForElements("id=menus-addMenuItem_label");
			selenium.click("id=menus-addMenuItem_label");
			Thread.sleep(2000);
			selenium.click("name=menus[addMenuItem]");
			//selenium.click("css=input[name=\"menus[addMenuItem]\"]");
			Thread.sleep(2000);
			selenium.click("css=#menus-0-location");
			Thread.sleep(2000);
			selenium.select("css=#menus-0-position", "label=After");
			Thread.sleep(2000);
			
			selenium.select("id=menus-0-location", "label=regexp:\\s+Search");
			Thread.sleep(2000);
	  		
	  		// Save page form
	  		selenium.click("id=edit-content-toolbar-button-Save_label");
			waitForElements("id=workflow-state-review");

	  		// click review and save
			selenium.click("id=workflow-state-review");
			waitForElements("id=save_label");
			selenium.click("id=save_label");
			Thread.sleep(3000);
	 	}
 }
     
     
     public void addBasicPageSiteBranchingFlow() throws Exception {
      	
			// click on add link
			selenium.click("//div[5]/div/div/div/ul/span/li[5]/span");
			waitForElements("//a[@href='/-dev-/add/type/basic-page']"); 
			
			selenium.click("//a[@href='/-dev-/add/type/basic-page']"); 
			waitForElements("id=add-content-toolbar-button-Save_label");
			selenium.type("id=title", "Basic Page for Site Branching Flow");
	  		Thread.sleep(1000);
	  		 		
	  		// edit page
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			Thread.sleep(2000);
	  		// click Menu and position this page before search
			selenium.click("id=edit-content-toolbar-button-Menus_label");
			waitForElements("id=menus-addMenuItem_label");

			selenium.click("id=menus-addMenuItem_label");
			selenium.click("name=menus[addMenuItem]");
			selenium.select("id=menus-0-location", "label=regexp:\\s+Search");
			Thread.sleep(2000);
	  		
	  		// Save page form
	  		selenium.click("id=edit-content-toolbar-button-Save_label");
	  		waitForElements("id=workflow-state-review");
	  		// click review and save
			selenium.click("id=workflow-state-review");
			waitForElements("id=save_label");
			selenium.click("id=save_label");
			Thread.sleep(3000);
			
			// edit page and save another in published mode
			selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
			Thread.sleep(2000);
			selenium.type("id=title", "Basic Page Publish Mode for Site Branching Flow");
	  		Thread.sleep(1000);
	
			// Save page form
	  		selenium.click("id=edit-content-toolbar-button-Save_label");
	  		waitForElements("id=workflow-state-published");
	  		// click review and save
			selenium.click("id=workflow-state-published");
			waitForElements("id=save_label");
			selenium.click("id=save_label");
			Thread.sleep(3000);
	 	}
      
     
        
     // This code is different than the browserSpecificBasicPage code below
     // it is designed for SiteBranching since the url for the add content (page, blog post, press release)
     // changes when a specific branch is selected
     //****************************************************************************************************//
     
     // used for Firefox Branch basic page
     public void browserSpecificBasicPageSiteBranching() throws Exception {
        	 if (browser.equalsIgnoreCase("*iexplore"))
    		  {  verifyContentElements();
        		 selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li/span/a","");
    		     Thread.sleep(2000);
    		     addBasicPage();
    		   }
    		else   
    		{   verifyContentElements();
    		    waitForElements("//ul[contains(@class,'content-types')]");
    			selenium.click("//a[@href='/-firefox-/add/type/basic-page']"); 
    			Thread.sleep(2000);		
    	 		addBasicPage();  
    	 	}
     }
     // used for Firefox Branch - blog post
     public void browserSpecificBlogPostSiteBranching() throws Exception {
        	 if (browser.equalsIgnoreCase("*iexplore"))
    		  {  verifyContentElements();
    		     waitForElements("//ul[contains(@class,'content-types')]");
        		 selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li[2]/span/a","");
    		     Thread.sleep(2000);
    		     addBlogPost();
    		   }
    		else  
    		{   verifyContentElements();
    		    waitForElements("//ul[contains(@class,'content-types')]");
    			selenium.click("//div[@id='dijit_layout_ContentPane_0']/ul/li[2]/span/a/img");
    			Thread.sleep(2000);		
    	 		addBlogPost();  
    	 	}
     }
     // used for Firefox Branch - press release
     public void browserSpecificPressReleaseSiteBranching() throws Exception {
     
        	 if (browser.equalsIgnoreCase("*iexplore"))
    		  {  verifyContentElements();
        		 selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li[3]/span/a","");
    		     Thread.sleep(2000);
    		     addPressRelease();
    		   }
    		else
    		{   verifyContentElements();
    			selenium.click("//a[@href='/-firefox-/add/type/press-release']");  
    			Thread.sleep(2000);		
    	 		addPressRelease();  
    	 	}  
     }
     
     
     //**** Browser specific code for internet explorer for AddBasicPage, AddBlogPost, AddPressRelease code ****//
     
     public void browserSpecificBasicPage() throws Exception {
    	 if (browser.equalsIgnoreCase("*iexplore"))
		  {  selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li/span/a","");
		     Thread.sleep(2000); }
		else   
			selenium.click("//a[@href='/add/type/basic-page']"); 
    	 	Thread.sleep(1000);
     }
     
     public void browserSpecificBlogPost() throws Exception {
    	 if (browser.equalsIgnoreCase("*iexplore"))
		  {  selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li[2]/span/a","");
		     Thread.sleep(2000); }
		else   
			//selenium.click("//div[@id='dijit_layout_ContentPane_0']/ul/li[2]/span/a/img");
        	 selenium.click("//a[@href='/add/type/blog-post']");
    	 	Thread.sleep(1000);
     }
     
     public void browserSpecificPressRelease() throws Exception { 
    	 if (browser.equalsIgnoreCase("*iexplore"))
		  {  selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li[3]/span/a","");
		     Thread.sleep(2000); }
		else   
			selenium.click("//a[@href='/add/type/press-release']");
    	 	Thread.sleep(1000);
     }
     
     public void browserSpecificImageGallery() throws Exception {
    	 if (browser.equalsIgnoreCase("*iexplore"))
		  {  selenium.clickAt("//div[6]/div[2]/div/div/div/div[3]/div/ul/li[3]/span/a","");
		     Thread.sleep(2000); }
		else   
			selenium.click("//a[@href='/add/type/gallery']"); 
    	 	Thread.sleep(1000);
     }
     
     
     public void addBasicPage() throws Exception {
     	
     	// Click title and enter info
    	Thread.sleep(1000);
 		selenium.type("id=title", "Basic Page Testing");
 		// Click body and enter info
 		Thread.sleep(2000);
 		selenium.click("css=#p4cms_content_Element_0 > span.value-node");
 		Thread.sleep(1000);
 		selenium.type("id=dijitEditorBody", "Basic Page Testing");
 		Thread.sleep(2000);
 		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
 		selenium.click("//div[@class='container']");	
 		waitForElements("id=add-content-toolbar-button-Save_label");
 		selenium.click("id=add-content-toolbar-button-Save_label");
 		selenium.click("id=save_label");	
 		Thread.sleep(3000);
 		
 		// edit page
		selenium.click("id=toolbar-content-edit");
  		// click Menu and position this page before search
		Thread.sleep(2000);
  		
  		// Save page form
  		selenium.click("id=edit-content-toolbar-button-Save_label");
		Thread.sleep(1000);

  		// click review and save
		selenium.click("id=workflow-state-review");
		waitForElements("id=save_label");
		selenium.click("id=save_label");
		Thread.sleep(3000);
     }
     
     
     public void addBlogPost() throws Exception {
    	 
      	// Click on title
    	 Thread.sleep(1000);
  		selenium.click("id=p4cms_content_Element_0");
  		selenium.type("id=title", "Blog Post Testing");
  		Thread.sleep(1000);
  		
  		// Initialize new Date object		
  		Date date = new Date();
  		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
  		System.out.println(dateEntry.format(date));				
  		selenium.click("id=date");
  		selenium.type("id=date", dateEntry.format(date));
  		Thread.sleep(2000);
  		selenium.type("id=author", "Blog Post Testing");
  		Thread.sleep(1000);
  		
  		selenium.click("id=excerpt");
  		selenium.type("id=excerpt-Editor", "Blog Post Testing");
  		Thread.sleep(1000);
  		
  		// Click on body to enter info
   		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", "Blog Post Testing");
  		Thread.sleep(2000);
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		selenium.click("//div[@class='container']");			
 		waitForElements("id=add-content-toolbar-button-Save_label");
  		
  		selenium.click("id=add-content-toolbar-button-Save_label");
  		selenium.click("id=save_label");			
  		Thread.sleep(3000);
  		
  		// edit page
  		selenium.click("id=toolbar-content-edit");
  		// click Menu and position this page before search
		Thread.sleep(2000);
  		
  		// Save page form
  		selenium.click("id=edit-content-toolbar-button-Save_label");
		Thread.sleep(1000);

  		// click review and save
		selenium.click("id=workflow-state-review");
		waitForElements("id=save_label");
		selenium.click("id=save_label");
		Thread.sleep(3000);
      }
      
     
      public void addPressRelease() throws Exception {  
    	  
   		// Click on title
    	  Thread.sleep(1000);
   		selenium.type("id=title", "Press Release Testing");
   		Thread.sleep(1000);
   		selenium.type("id=subtitle", "Press Release Testing");
   		
   		// Initialize new Date object		
   		Date date = new Date();
   		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
   		System.out.println(dateEntry.format(date));				
   		selenium.click("id=date");
   		selenium.type("id=date", dateEntry.format(date));
   		Thread.sleep(1000);
   		
   	    // click on body element
  		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
  		Thread.sleep(2000);
  		
  		// enter location 
  		selenium.click("id=location");
  		selenium.type("id=location", "Testing");
  		Thread.sleep(1000);
  		
  		// Click on body to enter info
  		selenium.clickAt("//span[@id='dijit_form_Button_5']/span","");
  		Thread.sleep(2000);
  		// Click on body to enter info
  		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", "Testing");
  		Thread.sleep(2000);
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		selenium.click("//div[@class='container']");			
  		Thread.sleep(1000);
   		
   		// enter contact details
   		selenium.click("id=contact");
   		selenium.type("id=contact", "Testing");
   		Thread.sleep(1000);
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		selenium.click("//div[@class='container']");			
 		waitForElements("id=add-content-toolbar-button-Save_label");

   		selenium.click("id=add-content-toolbar-button-Save_label");
   		selenium.click("id=save_label");			
   		Thread.sleep(3000);
   		
   		// edit page
   		selenium.click("id=toolbar-content-edit");
   		
  		// click Menu and position this page before search
		waitForElements("id=edit-content-toolbar-button-Save_label");
  		
  		// Save page form
  		selenium.click("id=edit-content-toolbar-button-Save_label");
		Thread.sleep(2000);

  		// click review and save
		selenium.click("id=workflow-state-review");
		waitForElements("id=save_label");
		selenium.click("id=save_label");
		Thread.sleep(3000);
       }
         
      
     public void addBasicPageReviewMode() throws Exception {
      	
      	// Click title and enter info
    	 //click edit
    	 Thread.sleep(2000);
    	 selenium.click("id=toolbar-content-edit");
    	 Thread.sleep(2000);
  		selenium.type("id=title", "Basic Page Review Mode Testing");
  		Thread.sleep(2000);
  		// Save page form in edit mode
  		selenium.click("id=edit-content-toolbar-button-Save_label");
  		waitForElements("id=workflow-state-review");
		
  		// click review mode
  		selenium.click("id=workflow-state-review");	
  		waitForElements("id=save_label");
  		// save
  		selenium.click("id=save_label");
  		Thread.sleep(3000);
      }
     
     
     public void addBasicPagePublishMode() throws Exception {
       	
       	// Click title and enter info
    	 //click edit
    	 Thread.sleep(2000);
    	 selenium.click("id=toolbar-content-edit");
    	 Thread.sleep(2000);
   		selenium.type("id=title", "Basic Page Publish Mode Testing");
   		// Click body and enter info
   		Thread.sleep(1000);
   		selenium.click("css=#p4cms_content_Element_0 > span.value-node");
   		selenium.type("id=dijitEditorBody", "Basic Page Publish Mode Testing");
   		Thread.sleep(2000);
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		selenium.click("//div[@class='container']");			
   		Thread.sleep(3000);
   		
   		// Save page form in edit mode
  		selenium.click("id=edit-content-toolbar-button-Save_label");
  		waitForElements("id=workflow-state-published");
  		
  		// click publish mode
  		selenium.click("id=workflow-state-published");	
  		waitForElements("id=save_label");
  		// save
  		selenium.click("id=save_label");	
  		Thread.sleep(3000);
       }
     
     
     public void addBlogPostReviewMode() throws Exception {
       	
    	// Click on title
    	//click edit
    	 selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
    	 Thread.sleep(2000);
  		selenium.click("id=p4cms_content_Element_0");
  		selenium.type("id=title", "Blog Post Review Mode Testing");
  		Thread.sleep(1000);
  		
  		// Initialize new Date object		
  		Date date = new Date();
  		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
  		System.out.println(dateEntry.format(date));				
  		selenium.click("id=date");
  		selenium.type("id=date", dateEntry.format(date));
  		Thread.sleep(2000);
  		selenium.type("id=author", "Blog Post Review Mode Testing");
  		Thread.sleep(1000);
  		
  		selenium.type("id=excerpt-Editor", "Blog Post Testing");
  		Thread.sleep(1000);
  		
  		// Click on body to enter info
  		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", "Blog Post Testing");
  		Thread.sleep(2000);
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		selenium.click("//div[@class='container']");			
  		Thread.sleep(3000);
  		
  		// Save page form in edit mode
  		selenium.click("id=edit-content-toolbar-button-Save_label");
  		waitForElements("id=workflow-state-review");
  		
  		// click publish mode
  		selenium.click("id=workflow-state-review");	
  		waitForElements("id=save_label");
  		// save
  		selenium.click("id=save_label");	
  		Thread.sleep(3000);
       }
      
     
      public void addBlogPostPublishMode() throws Exception {
        	
    	// Click on title
    	  //click edit
    	  selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
    	  Thread.sleep(2000);
		selenium.click("id=p4cms_content_Element_0");
		selenium.type("id=title", "Blog Post Publish Mode Testing");
		Thread.sleep(1000);
		
		// Initialize new Date object		
		Date date = new Date();
		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
		System.out.println(dateEntry.format(date));				
		selenium.click("id=date");
		selenium.type("id=date", dateEntry.format(date));
		Thread.sleep(2000);
		selenium.type("id=author", "Blog Post Testing");
		Thread.sleep(1000);
		
		selenium.type("id=excerpt-Editor", "Blog Post Testing");
		Thread.sleep(1000);
		
		// Click on body to enter info
		selenium.click("id=body-Editor");
		selenium.type("id=dijitEditorBody", "Blog Post Publish Mode Testing");
		Thread.sleep(2000);
		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
		selenium.click("//div[@class='container']");			
		
		// Save page form in edit mode
  		selenium.click("id=edit-content-toolbar-button-Save_label");
  		waitForElements("id=workflow-state-published");
		
  		// click publish mode
  		selenium.click("id=workflow-state-published");	
  		waitForElements("id=save_label");
  		// save
  		selenium.click("id=save_label");	
  		Thread.sleep(3000);	
        }
      
      
      public void addPressReleaseReviewMode() throws Exception {
        	
    	// Click on title
    	  //click edit
    	  selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
    	  Thread.sleep(2000);
		selenium.type("id=title", "Press Release Review Mode Testing");
		Thread.sleep(1000);
		
		selenium.type("id=subtitle", "Press Release Testing");
		
		// Initialize new Date object		
		Date date = new Date();
		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
		System.out.println(dateEntry.format(date));				
		selenium.click("id=date");
		selenium.type("id=date", dateEntry.format(date));
		Thread.sleep(1000);
    		
    	// click on body element
   		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
   		Thread.sleep(2000);
   		
   		// enter location 
   		selenium.type("id=location", "Testing");
   		Thread.sleep(1000);
   		
   		// Click on body to enter info
   		selenium.clickAt("//span[@id='dijit_form_Button_5']/span","");
   		Thread.sleep(2000);
   		// Click on body to enter info
   		selenium.click("id=body-Editor");
   		selenium.type("id=dijitEditorBody", "Press Release Review Mode Testing");
   		Thread.sleep(2000);
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		selenium.click("//div[@class='container']");			
   		Thread.sleep(1000);
    		
		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
		selenium.click("//div[@class='container']");	
		
		// Save page form in edit mode
  		selenium.click("id=edit-content-toolbar-button-Save_label");
  		waitForElements("id=workflow-state-review");
		
  		// click publish mode
  		selenium.click("id=workflow-state-review");	
  		waitForElements("id=save_label");
  		// save
  		selenium.click("id=save_label");	
  		Thread.sleep(3000);		
        }
       
      
       public void addPressReleasePublishMode() throws Exception {
         	
    	 // Click on title
    	 //click edit
     	 selenium.click("css=#toolbar-content-edit > span.menu-handle.type-heading");
     	 Thread.sleep(2000);
   		selenium.type("id=title", "Press Release Publish Mode Testing");
   		Thread.sleep(1000);
   		
   		selenium.type("id=subtitle", "Press Release Testing");
   		
   		// Initialize new Date object		
   		Date date = new Date();
   		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
   		System.out.println(dateEntry.format(date));				
   		selenium.click("id=date");
   		selenium.type("id=date", dateEntry.format(date));
   		Thread.sleep(1000);
   		
   		// click on body element
  		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
  		Thread.sleep(2000);
  		
  		// enter location 
  		selenium.type("id=location", "Testing");
  		Thread.sleep(1000);
  		
  		// Click on body to enter info
  		selenium.clickAt("//span[@id='dijit_form_Button_5']/span","");
  		Thread.sleep(2000);
  		// Click on body to enter info
  		selenium.click("id=body-Editor");
  		selenium.type("id=dijitEditorBody", "Press Release Publish Mode Testing");
  		Thread.sleep(2000);
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		selenium.click("//div[@class='container']");			
   		
   		// enter contact details
   		selenium.type("id=contact", "Testing");
   		Thread.sleep(1000);
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		selenium.click("//div[@class='container']");			
   		
   		// Save page form in edit mode
  		selenium.click("id=edit-content-toolbar-button-Save");
  		waitForElements("id=workflow-state-published");
		
  		// click publish mode
  		selenium.click("id=workflow-state-published");	
  		waitForElements("id=save_label");
  		// save
  		selenium.click("id=save_label");	
  		Thread.sleep(3000);   		
         }
       
   
     public void editBasicPage() throws Exception {
 		
 		// Basic page
 		// click on Pages in left tab
 		selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
 		Thread.sleep(1000);
 		browserSpecificBasicPage();

 		waitForElements("id=add-content-toolbar-button-form_label");	
 		// click form mode and verify all elements
 		selenium.click("id=add-content-toolbar-button-form_label");
 		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
		
		Thread.sleep(1000);
		// Click title and enter info
		selenium.click("id=p4cms_content_Element_0");
		selenium.type("id=title", "Basic Page editing for testing");
		// Click body and enter info
		Thread.sleep(1000);
		selenium.click("css=#p4cms_content_Element_0 > span.value-node");
		selenium.type("id=dijitEditorBody", "Basic Page");
		Thread.sleep(2000);
		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
		selenium.click("//div[@class='container']");			
 		Thread.sleep(2000);	
		
		// Save page form
		selenium.click("id=add-content-toolbar-button-Save_label");
		Thread.sleep(1000);

		selenium.click("id=add-content-toolbar-button-Save_label");
		selenium.click("id=save_label");			
		Thread.sleep(3000);
		
		// verify edit button is displayed
		verifyEditButtonDisplayed();
		Thread.sleep(1000);
		// click into form mode
		selenium.click("id=edit-content-toolbar-button-form_label");
		selenium.click("//div[@id='edit-content-toolbar']/span[5]/input");
		Thread.sleep(2000);	
     }
     
     
     public void editBlogPost() throws Exception {
     	
 		browserSpecificBlogPost();
 		Thread.sleep(2000);
 		
 		// click form mode and verify all elements
 		selenium.click("id=add-content-toolbar-button-form_label");
 		selenium.click("//div[@id='add-content-toolbar']/span[4]/input");
 		Thread.sleep(2000);

 		// Click on title
 		selenium.click("id=p4cms_content_Element_0");
 		selenium.click("id=p4cms_content_Element_0");
 		selenium.type("id=title", "Blog Post editing for testing");
 		Thread.sleep(1000);
 		
 		// Initialize new Date object		
 		Date date = new Date();
 		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
 		System.out.println(dateEntry.format(date));				
 		selenium.click("id=date");
 		selenium.type("id=date", dateEntry.format(date));
 		Thread.sleep(2000);
 		selenium.type("id=author", "Blog Post");
 		Thread.sleep(1000);
 		
 		selenium.type("id=excerpt-Editor", "Blog Post");
 		Thread.sleep(1000);
 		
 		// Click on body to enter info
 		selenium.click("id=body-Editor");
 		selenium.type("id=dijitEditorBody", "Blog Post");
 		Thread.sleep(2000);
 		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
 		selenium.click("//div[@class='container']");			
 		Thread.sleep(2000);
 		
 		// Save the page info
 		selenium.click("id=add-content-toolbar-button-Save_label");
 		Thread.sleep(2000);

 		selenium.click("id=add-content-toolbar-button-Save_label");
 		selenium.click("id=save_label");			
 		Thread.sleep(3000);
 		
 		// verify edit button is displayed
 		verifyEditButtonDisplayed();
 		Thread.sleep(2000);
 		// click into form mode
 		selenium.click("id=edit-content-toolbar-button-form_label");
 		selenium.click("//div[@id='edit-content-toolbar']/span[5]/input");
 		Thread.sleep(2000);
     }
     
  
     public void editPressRelease() throws Exception {
	
  		// press release
  		// click on press release in left tab
  		selenium.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_1 > span.tabLabel");
  		Thread.sleep(1000);
  		// click press release
  		browserSpecificPressRelease();
  		Thread.sleep(2000);
 		
 		// Click on title
 		selenium.click("id=p4cms_content_Element_0");
 		selenium.type("id=title", "Press Release editing for testing");
 		Thread.sleep(1000);
 		
 		selenium.type("id=subtitle", "Press Release");
 		Thread.sleep(1000);
 		
 		// Initialize new Date object		
 		Date date = new Date();
 		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
 		System.out.println(dateEntry.format(date));				
 		selenium.click("id=date");
 		selenium.type("id=date", dateEntry.format(date));
 		Thread.sleep(2000);
 		// click on body element
 		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
 		Thread.sleep(2000);
 		
 		// enter location 
 		selenium.type("id=location", "Press Release");
 		Thread.sleep(1000);
 		
 		// Click on body to enter info
 		selenium.clickAt("//span[@id='dijit_form_Button_5']/span","");
 		Thread.sleep(2000);
 		// Click on body to enter info
 		selenium.click("id=body-Editor");
 		selenium.type("id=dijitEditorBody", "Press Release");
 		Thread.sleep(2000);
 		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
 		selenium.click("//div[@class='container']");			
 		
 		// enter contact details
 		selenium.type("id=contact", "Press Release");
 		Thread.sleep(2000);
 		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
 		selenium.click("//div[@class='container']");			
 		waitForElements("id=add-content-toolbar-button-form_label");	
 
 		
 		// Save the page info
 		selenium.click("id=add-content-toolbar-button-Save_label");
 		Thread.sleep(2000);
 		
 		selenium.click("id=add-content-toolbar-button-Save_label");
 		selenium.click("id=save_label");			
 		Thread.sleep(3000);
 		
 		// verify edit button is displayed
 		verifyEditButtonDisplayed();
 		Thread.sleep(2000);	
 		// click into form mode
 		selenium.click("id=edit-content-toolbar-button-form_label");
 		selenium.click("//div[@id='edit-content-toolbar']/span[5]/input");
 		Thread.sleep(2000);
     }
      
     
     public void addTabletThemeBasicPage() throws Exception {
      	
      	// Click title and enter info
     	Thread.sleep(1000);
     	selenium.click("id=p4cms_content_Element_1");
     	Thread.sleep(2000);
  		selenium.type("id=title", "Basic Page Testing");
  		
    	// verify tablet theme basic page title
  	    if (selenium.isElementPresent(("//div[contains(@id,'title')]")))
  	 	writeFile("11100", "pass", "BaseTest.java","TabletThemeBasicPageTitle", "verify the table theme basic page title"); 
  	    else  { writeFile("11100", "fail", "BaseTest.java","TabletThemeBasicPageTitle", "verify the table theme basic page title"); }	
  	 			
  		selenium.fireEvent("body", "blur");
  		
  		 // verify tablet theme basic page image 
 	    if (selenium.isElementPresent(("//div[contains(@id,'p4cms_content_Element_0')]")))
 	 	writeFile("11110", "pass", "BaseTest.java","TabletThemeBasicPageImage", "verify the table theme basic page image"); 
 	    else  { writeFile("11110", "fail", "BaseTest.java","TabletThemeBasicPageImage", "verify the table theme basic page image"); }	
 		  

  		// Click body and enter info
  		Thread.sleep(2000);
  		selenium.clickAt("css=.mobile .add-action .content-entry-type-basic-page .page-1 .p4cms-swap-view .cover .details-container .p4cms-column-last .content-element-body","");
  		
  		 // verify tablet theme basic page body
  	    if (selenium.isElementPresent(("//body[contains(@id,'dijitEditorBody')]")))
  	 	writeFile("11107", "pass", "BaseTest.java","TabletThemeBasicPageBodyElement", "verify the table theme basic page body element"); 
  	    else  { writeFile("11107", "fail", "BaseTest.java","TabletThemeBasicPageElement", "verify the table theme basic page body element"); }	
  	 	
  	    // type in body text 
  		selenium.type("id=dijitEditorBody", "Basic Page Testing");
  		Thread.sleep(2000);
  		
  		// verify body text
  		if (selenium.isTextPresent(("Body")))
  	  	 writeFile("11105", "pass", "BaseTest.java","TabletThemeBasicPageBodyText", "verify the table theme basic page body"); 
  	  	  else  { writeFile("11105", "fail", "BaseTest.java","TabletThemeBasicPageBodyText", "verify the table theme basic page body"); }	
  	  	 	
  		if (selenium.isElementPresent(("//div[contains(@id,'dijit_Toolbar_1')]")))
  	  	 writeFile("11106", "pass", "BaseTest.java","TabletThemeBasicPageBodyTextElements", "verify the table theme basic page body"); 
  	  	 else  { writeFile("11106", "fail", "BaseTest.java","TabletThemeBasicPageBodyTextElements", "verify the table theme basic page body"); }	
  	  	  	 	
  		
  	    // click on close icon to save body text
  		selenium.clickAt("css=.mobile .add-action .dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon","");
  		Thread.sleep(1000);
  		
  		 // verify tablet theme basic page close
  	    if (selenium.isElementPresent(("//span[contains(@class,'dijitDialogCloseIcon')]")))
  	 	writeFile("11103", "pass", "BaseTest.java","TabletThemeBasicPageBodyCloseIcon", "verify the table theme basic page body close icon"); 
  	    else  { writeFile("11103", "fail", "BaseTest.java","TabletThemeBasicPageCloseIcon", "verify the table theme basic page body close icon"); }	
  	 	
  		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		waitForElements("id=add-content-toolbar-button-Save_label");
  		selenium.click("id=add-content-toolbar-button-Save_label");
  		selenium.click("id=save_label");	
  		Thread.sleep(3000);
  	
  		
  		// edit page
 		selenium.click("id=toolbar-content-edit");
   		// click Menu and position this page before search
 		Thread.sleep(2000);
   		
   		// Save page form
   		selenium.click("id=edit-content-toolbar-button-Save_label");
 		Thread.sleep(1000); 

   		// click review and save
 		selenium.click("id=workflow-state-review");
 		waitForElements("id=save_label");
 		selenium.click("id=save_label");
 		Thread.sleep(3000);
      }
 
     
     public void addTabletThemeBlogPost() throws Exception {
    	 
       	// Click on title
     	 Thread.sleep(1000);
   		selenium.click("id=p4cms_content_Element_1");
   		Thread.sleep(2000);
   		selenium.type("id=title", "Blog Post Testing");
   		Thread.sleep(1000);
   		
   		// verify tablet theme blog post title
  	    if (selenium.isElementPresent(("//div[contains(@id,'title')]")))
  	 	writeFile("11101", "pass", "BaseTest.java","TabletThemeBlogPostTitle", "verify the table theme blog post title"); 
  	    else  { writeFile("11101", "fail", "BaseTest.java","TabletThemeBlogPostTitle", "verify the table theme blog post title"); }	
  
  	   // verify tablet theme blog post image 
 	    if (selenium.isElementPresent(("//div[contains(@id,'p4cms_content_Element_0')]")))
 	 	writeFile("11119", "pass", "BaseTest.java","TabletThemeBlogPostImage", "verify the table theme blog post image"); 
 	    else  { writeFile("11119", "fail", "BaseTest.java","TabletThemeBlogPostImage", "verify the table theme blog post image"); }	
 		    
  	    
   		// Initialize new Date object		
   		Date date = new Date();
   		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
   		System.out.println(dateEntry.format(date));			
   		
   		selenium.click("id=p4cms_content_Element_2");
   		
     	// verify tablet theme blog post date 
 	    if (selenium.isElementPresent(("//div[contains(@id,'p4cms_content_Element_2')]")))
 	 	writeFile("11111", "pass", "BaseTest.java","TabletThemeBlogPostDate", "verify the table theme blog post date"); 
 	    else  { writeFile("11111", "fail", "BaseTest.java","TabletThemeBlogPostDate", "verify the table theme blog post date"); }	
 	
   		selenium.type("id=date", dateEntry.format(date));
   		Thread.sleep(2000);
   		
   		// blog post author
   		// verify tablet theme blog post date 
 	    if (selenium.isElementPresent(("//div[contains(@id,'p4cms_content_Element_3')]")))
 	 	writeFile("11112", "pass", "BaseTest.java","TabletThemeBlogPostAuthor", "verify the table theme blog post author"); 
 	    else  { writeFile("11112", "fail", "BaseTest.java","TabletThemeBlogPostAuthor", "verify the table theme blog post author"); }	
 	
   		selenium.type("id=author", "Blog Post Testing");
   		Thread.sleep(1000);
   		
   	    // Click body and enter info
  		Thread.sleep(2000);
  		selenium.clickAt("css=.mobile .add-action .content-entry-type-blog-post .page-1 .p4cms-swap-view .cover .details-container .p4cms-column-last .content-element-body","");
  			
  	   // verify tablet theme blog post body
  		if (selenium.isElementPresent(("//html[contains(@class,'content-editor')]")))
  	  	writeFile("11115", "pass", "BaseTest.java","TabletThemeBlogPostBodyElement", "verify the table theme blog post body element"); 
  	  	  else  { writeFile("11115", "fail", "BaseTest.java","TabletThemeBlogPostBodyElement", "verify the table theme blog post body element"); }	
  	  	 	
   		// Click on body to enter info		
   		selenium.type("id=dijitEditorBody", "Blog Post Testing");
   		Thread.sleep(2000);
   		
   		// verify body text
  		if (selenium.isTextPresent(("Body")))
  	  	 writeFile("11105", "pass", "BaseTest.java","TabletThemeBlogPostBodyText", "verify the table theme blog post body"); 
  	  	  else  { writeFile("11105", "fail", "BaseTest.java","TabletThemeBlogPostBodyText", "verify the table theme blog post body"); }	
  	  	 	
  		if (selenium.isElementPresent(("//div[contains(@id,'dijit_Toolbar_1')]")))
  	  	 writeFile("11117", "pass", "BaseTest.java","TabletThemeBlogPostBodyTextElements", "verify the table theme blog post body"); 
  	  	 else  { writeFile("11117", "fail", "BaseTest.java","TabletThemeBlogPostBodyTextElements", "verify the table theme blog post body"); }	
  	  
  	    
  		 // click on close icon to save body text
  		selenium.clickAt("css=.mobile .add-action .dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon","");
  		Thread.sleep(1000);
  		
  		 // verify tablet theme blog post close 
  	    if (selenium.isElementPresent(("//span[contains(@class,'dijitDialogCloseIcon')]")))
  	 	writeFile("11118", "pass", "BaseTest.java","TabletThemeBlogPostBodyCloseIcon", "verify the table theme blog post body close icon"); 
  	    else  { writeFile("11118", "fail", "BaseTest.java","TabletThemeBlogPostCloseIcon", "verify the table theme blog post body close icon"); }	
  	
  			
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		waitForElements("id=add-content-toolbar-button-Save_label");
   		
   		selenium.click("id=add-content-toolbar-button-Save_label");
   		selenium.click("id=save_label");			
   		Thread.sleep(3000);
   		
   		// edit page
   		selenium.click("id=toolbar-content-edit");
   		// click Menu and position this page before search
 		Thread.sleep(2000);
   		
   		// Save page form
   		selenium.click("id=edit-content-toolbar-button-Save_label");
 		Thread.sleep(1000);

   		// click review and save
 		selenium.click("id=workflow-state-review");
 		waitForElements("id=save_label");
 		selenium.click("id=save_label");
 		Thread.sleep(3000);
       }
       
      
     
       public void addTabletThemePressRelease() throws Exception {  
     	  
    	   // Google chrome specific code for press release
    	   if (browser.equalsIgnoreCase("*googlechrome")) {
    		   
    		// Click on title
    	     	 Thread.sleep(1000);
    	     	 selenium.click("id=p4cms_content_Element_1");
    	     	 Thread.sleep(1000);
    			selenium.type("id=title", "Press Release Testing");
    			Thread.sleep(1000);

    			// verify tablet theme press release title
    	  	    if (selenium.isElementPresent(("//div[contains(@id,'title')]")))
    	  	 	writeFile("11102", "pass", "BaseTest.java","TabletThemePressReleaseTitle", "verify the table theme press release title"); 
    	  	    else  { writeFile("11102", "fail", "BaseTest.java","TabletThemePressReleaseTitle", "verify the table theme press release title"); }	
    	  
    	  	   // verify tablet theme press release image 
    	 	    if (selenium.isElementPresent(("//div[contains(@id,'p4cms_content_Element_0')]")))
    	 	 	writeFile("11130", "pass", "BaseTest.java","TabletThemePressReleaseImage", "verify the table theme press release image"); 
    	 	    else  { writeFile("11130", "fail", "BaseTest.java","TabletThemePressReleaseImage", "verify the table theme press release image"); }	
    	 	
    	 	    
    	 	  // verify tablet theme press release subtitle 
    	 	    if (selenium.isElementPresent(("//div[contains(@id,'subtitle')]")))
    	 	 	writeFile("11120", "pass", "BaseTest.java","TabletThemePressReleaseSubtitle", "verify the table theme press release subtitle"); 
    	 	    else  { writeFile("11120", "fail", "BaseTest.java","TabletThemePressReleaseSubtitle", "verify the table theme press release subtitle"); }	
    	 		
    			
    			// Initialize new Date object		
    			Date date = new Date();
    			SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
    			System.out.println(dateEntry.format(date));				
    	        
    			// click date field		
    		    selenium.click("id=p4cms_content_Element_3");
    	   		
    	     	// verify tablet theme press release date 
    	 	    if (selenium.isElementPresent(("//div[contains(@class,'content-element content-element-type-dateTextBox content-element-date')]")))
    	 	 	writeFile("11121", "pass", "BaseTest.java","TabletThemePressReleaseDate", "verify the table theme press release date"); 
    	 	    else  { writeFile("11121", "fail", "BaseTest.java","TabletThemePressReleaseDate", "verify the table theme press release date"); }	
    	 	
    			selenium.type("id=date", dateEntry.format(date));
    			Thread.sleep(2000);
    	   		
    			selenium.fireEvent("body", "blur");
    			Thread.sleep(2000);
    			
    	   		// enter location 
    	   	    // verify tablet theme press release location 
    	 	    if (selenium.isElementPresent(("//div[contains(@id,'location')]")))
    	 	 	writeFile("11122", "pass", "BaseTest.java","TabletThemePressReleaseLocation", "verify the table theme press release location"); 
    	 	    else  { writeFile("11122", "fail", "BaseTest.java","TabletThemePressReleaseLocation", "verify the table theme press release location"); }	
	   		
    	   		
    	   	   // Click body and enter info
    	  		Thread.sleep(2000);
    	  		selenium.click("css=.mobile .add-action .content-entry-type-press-release .page-1 .p4cms-swap-view .cover .details-container .p4cms-column-last .content-element-body"); 
    	  		Thread.sleep(2000);
    	  		
    	  	   // verify tablet theme press release body
    	  		if (selenium.isElementPresent(("//html[contains(@class,'content-editor')]")))
    	  	  	writeFile("11126", "pass", "BaseTest.java","TabletThemePressReleaseBodyElement", "verify the table theme press release body element"); 
    	  	  	  else  { writeFile("11126", "fail", "BaseTest.java","TabletThemePressReleaseBodyElement", "verify the table theme press release body element"); }	
    	  	  
    	   		
    	   		// verify body text
    	  		if (selenium.isTextPresent(("Body")))
    	  	  	 writeFile("11127", "pass", "BaseTest.java","TabletThemePressReleaseBodyText", "verify the table theme press release body"); 
    	  	  	  else  { writeFile("11127", "fail", "BaseTest.java","TabletThemePressReleaseBodyText", "verify the table theme press release body"); }	
    	  	  	 	
    	  		if (selenium.isElementPresent(("//div[contains(@id,'dijit_Toolbar_2')]")))
    	  	  	 writeFile("11128", "pass", "BaseTest.java","TabletThemePressReleaseBodyTextElements", "verify the table theme press release body"); 
    	  	  	 else  { writeFile("11128", "fail", "BaseTest.java","TabletThemePressReleaseBodyTextElements", "verify the table theme press release body"); }	
    	  	  
    	  		
    	  		 // verify tablet theme blog post close 
    	  	    if (selenium.isElementPresent(("//span[contains(@class,'dijitDialogCloseIcon')]")))
    	  	 	writeFile("11129", "pass", "BaseTest.java","TabletThemePressReleaseBodyCloseIcon", "verify the table theme press release body close icon"); 
    	  	    else  { writeFile("11129", "fail", "BaseTest.java","TabletThemePressReleaseCloseIcon", "verify the table theme press release body close icon"); }	
    	  	    
    	  	    // click on close icon to save body text
    	  		selenium.clickAt("css=.mobile .add-action .dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon","");
    	  		Thread.sleep(2000);
    	  	    
    	  		
    	   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
    	   		Thread.sleep(1000);
    	    		
    			// enter contact details
    	      	// verify tablet theme press release contact 
    	 	    if (selenium.isElementPresent(("//div[contains(@id,'contact')]")))
    	 	 	writeFile("11123", "pass", "BaseTest.java","TabletThemePressReleaseContact", "verify the table theme press release contact"); 
    	 	    else  { writeFile("11123", "fail", "BaseTest.java","TabletThemePressReleaseContact", "verify the table theme press release contact"); }	
    	 		
    		
    			selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
    	  		Thread.sleep(2000);

    			selenium.click("id=add-content-toolbar-button-Save_label");
    			selenium.click("id=save_label");			
    			Thread.sleep(3000);
    			
    			// edit page
    			selenium.click("id=toolbar-content-edit");
    	    		
    	   		// click Menu and position this page before search
    			Thread.sleep(2000);
    			
    	   		// Save page form
    	   		selenium.click("id=edit-content-toolbar-button-Save_label");
    	 		Thread.sleep(2000);

    	   		// click review and save
    	 		selenium.click("id=workflow-state-review");
    	 		Thread.sleep(2000);
    	 		selenium.click("id=save_label");
    	 		Thread.sleep(3000);
    	        }
    	          
    	    else {  // Code for Firefox
    	   
    	   
    	   
    	// Click on title
     	 Thread.sleep(1000);
     	 selenium.click("id=p4cms_content_Element_1");
     	 Thread.sleep(1000);
		selenium.type("id=title", "Press Release Testing");
		Thread.sleep(1000);

		// verify tablet theme press release title
  	    if (selenium.isElementPresent(("//div[contains(@id,'title')]")))
  	 	writeFile("11102", "pass", "BaseTest.java","TabletThemePressReleaseTitle", "verify the table theme press release title"); 
  	    else  { writeFile("11102", "fail", "BaseTest.java","TabletThemePressReleaseTitle", "verify the table theme press release title"); }	
  
  	   // verify tablet theme press release image 
 	    if (selenium.isElementPresent(("//div[contains(@id,'p4cms_content_Element_0')]")))
 	 	writeFile("11130", "pass", "BaseTest.java","TabletThemePressReleaseImage", "verify the table theme press release image"); 
 	    else  { writeFile("11130", "fail", "BaseTest.java","TabletThemePressReleaseImage", "verify the table theme press release image"); }	
 	
 	    
 	  // verify tablet theme press release subtitle 
 	    if (selenium.isElementPresent(("//div[contains(@id,'subtitle')]")))
 	 	writeFile("11120", "pass", "BaseTest.java","TabletThemePressReleaseSubtitle", "verify the table theme press release subtitle"); 
 	    else  { writeFile("11120", "fail", "BaseTest.java","TabletThemePressReleaseSubtitle", "verify the table theme press release subtitle"); }	
 		
		selenium.type("id=subtitle", "Press Release Testing");
		
		// Initialize new Date object		
		Date date = new Date();
		SimpleDateFormat dateEntry = new SimpleDateFormat("MMMM dd, yyyy");
		System.out.println(dateEntry.format(date));				
        
		// click date field		
	    selenium.click("id=p4cms_content_Element_2");
   		
     	// verify tablet theme press release date 
 	    if (selenium.isElementPresent(("//div[contains(@class,'content-element content-element-type-dateTextBox content-element-date')]")))
 	 	writeFile("11121", "pass", "BaseTest.java","TabletThemePressReleaseDate", "verify the table theme press release date"); 
 	    else  { writeFile("11121", "fail", "BaseTest.java","TabletThemePressReleaseDate", "verify the table theme press release date"); }	
 	
		selenium.type("id=date", dateEntry.format(date));
		Thread.sleep(1000);
    		
    	 // click on body element
   		selenium.click("//input[@id='dijit__editor_plugins__FormatBlockDropDown_0_select']");
   		Thread.sleep(2000);
   		
   		// enter location 
   	    // verify tablet theme press release location 
 	    if (selenium.isElementPresent(("//div[contains(@id,'location')]")))
 	 	writeFile("11122", "pass", "BaseTest.java","TabletThemePressReleaseLocation", "verify the table theme press release location"); 
 	    else  { writeFile("11122", "fail", "BaseTest.java","TabletThemePressReleaseLocation", "verify the table theme press release location"); }	
 		
   		selenium.click("id=location");
   		selenium.type("id=location", "Testing");
   		Thread.sleep(1000);
   		
   		
   	   // Click body and enter info
  		Thread.sleep(2000);
  		selenium.clickAt("css=.mobile .add-action .content-entry-type-press-release .page-1 .p4cms-swap-view .cover .details-container .p4cms-column-last .content-element-body","");   			
  	   // verify tablet theme press release body
  		if (selenium.isElementPresent(("//body[contains(@id,'dijitEditorBody')]")))
  	  	writeFile("11126", "pass", "BaseTest.java","TabletThemePressReleaseBodyElement", "verify the table theme press release body element"); 
  	  	  else  { writeFile("11126", "fail", "BaseTest.java","TabletThemePressReleaseBodyElement", "verify the table theme press release body element"); }	
  	  	 	
   		// Click on body to enter info		
   		selenium.type("id=dijitEditorBody", "Press Release Testing");
   		Thread.sleep(2000);
   		
   		// verify body text
  		if (selenium.isTextPresent(("Body")))
  	  	 writeFile("11127", "pass", "BaseTest.java","TabletThemePressReleaseBodyText", "verify the table theme press release body"); 
  	  	  else  { writeFile("11127", "fail", "BaseTest.java","TabletThemePressReleaseBodyText", "verify the table theme press release body"); }	
  	  	 	
  		if (selenium.isElementPresent(("//div[contains(@id,'dijit_Toolbar_2')]")))
  	  	 writeFile("11128", "pass", "BaseTest.java","TabletThemePressReleaseBodyTextElements", "verify the table theme press release body"); 
  	  	 else  { writeFile("11128", "fail", "BaseTest.java","TabletThemePressReleaseBodyTextElements", "verify the table theme press release body"); }	
  	  
  	    
  		 // click on close icon to save body text
  		selenium.clickAt("css=.mobile .add-action .dijitDialog .dijitDialogTitleBar .dijitDialogCloseIcon","");
  		Thread.sleep(1000);
  		
  		 // verify tablet theme blog post close 
  	    if (selenium.isElementPresent(("//span[contains(@class,'dijitDialogCloseIcon')]")))
  	 	writeFile("11129", "pass", "BaseTest.java","TabletThemePressReleaseBodyCloseIcon", "verify the table theme press release body close icon"); 
  	    else  { writeFile("11129", "fail", "BaseTest.java","TabletThemePressReleaseCloseIcon", "verify the table theme press release body close icon"); }	
  	
  	    
   		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
   		Thread.sleep(1000);
    		
		// enter contact details
      	// verify tablet theme press release contact 
 	    if (selenium.isElementPresent(("//div[contains(@id,'contact')]")))
 	 	writeFile("11123", "pass", "BaseTest.java","TabletThemePressReleaseContact", "verify the table theme press release contact"); 
 	    else  { writeFile("11123", "fail", "BaseTest.java","TabletThemePressReleaseContact", "verify the table theme press release contact"); }	
 		
		selenium.click("id=contact");
		selenium.type("id=contact", "Testing");
		Thread.sleep(1000);
		selenium.fireEvent("body", "blur"); // call "blur" event to remove focus from the form
  		waitForElements("id=add-content-toolbar-button-Save_label");

		selenium.click("id=add-content-toolbar-button-Save_label");
		selenium.click("id=save_label");			
		Thread.sleep(3000);
		
		// edit page
		selenium.click("id=toolbar-content-edit");
    		
   		// click Menu and position this page before search
 		waitForElements("id=edit-content-toolbar-button-Save_label");
   		
   		// Save page form
   		selenium.click("id=edit-content-toolbar-button-Save_label");
 		Thread.sleep(2000);

   		// click review and save
 		selenium.click("id=workflow-state-review");
 		waitForElements("id=save_label");
 		selenium.click("id=save_label");
 		Thread.sleep(3000);
        }
       }
       
   
     //**** CHANGE THEME --> default theme is now tablet; changing to business ****//
     public void changeDefaultTheme() throws Exception {
    	 
    	  manageMenu();
		  selenium.click(CMSConstants.MANAGE_THEMES_PAGE_VERIFY); 
		  Thread.sleep(4000);
		  
		  // verify current theme is set to tablet
		  if (selenium.isTextPresent("Current Theme"))
				writeFile("9426", "pass", "ManageThemes","checkDefaultTheme", "Check the default tablet theme"); 
		        else  { writeFile("9426", "fail", "ManageThemes","checkDefaultTheme", "Check the default tablet theme"); }	
		  
		  if (selenium.isTextPresent("Tableau (Default)"))
				writeFile("11132", "pass", "ManageThemes","checkDefaultTheme", "Check the default tablet theme"); 
		        else  { writeFile("11132", "fail", "ManageThemes","checkDefaultTheme", "Check the default tablet theme"); }	
		  
		  if (selenium.isTextPresent("A tablet-first, magazine-style theme"))
				writeFile("11133", "pass", "ManageThemes","checkDefaultTheme", "Check the default tablet theme"); 
		        else  { writeFile("11133", "fail", "ManageThemes","checkDefaultTheme", "Check the default tablet theme"); }	
		  
		  if (selenium.isTextPresent("Version: 1.0"))
				writeFile("9437", "pass", "ManageThemes","checkThemeVersion", "Check the tablet theme version"); 
		        else  { writeFile("9437", "fail", "ManageThemes","checkThemeVersion", "Check the tablet theme version"); }	
		  
		  if (selenium.isTextPresent("Perforce Software"))
				writeFile("9438", "pass", "ManageThemes","checkPerforceSoftware", "Check Perforce Software"); 
		        else  { writeFile("9438", "fail", "ManageThemes","checkPerforceSoftware", "Check Perforce Software"); }	
		  
		  if (selenium.isElementPresent("//img[@src='/sites/all/themes/default/icon.png']"))
				writeFile("11131", "pass", "ManageThemes","checkTabletIcon", "Check the icon"); 
		        else  { writeFile("11131", "fail", "ManageThemes","checkTabletIcon", "Check the icon"); }	
		  
		  if (selenium.isTextPresent("magazine"))
				writeFile("11139", "pass", "ManageThemes","checkMagazineText", "Check magazine text"); 
		        else  { writeFile("11139", "fail", "ManageThemes","checkMagazineText", "Check magazine text"); }	
		  
		  if (selenium.isTextPresent("responsive"))
				writeFile("11135", "pass", "ManageThemes","checkResponsiveText", "Check responsive text"); 
		        else  { writeFile("11135", "fail", "ManageThemes","checkResponsiveText", "Check responsive text"); }	
		  
		  if (selenium.isTextPresent("tablet"))
				writeFile("11137", "pass", "ManageThemes","checkTabletText", "Check tablet text"); 
		        else  { writeFile("1137", "fail", "ManageThemes","checkTabletText", "Check tablet text"); }	
		  
		  if (selenium.isTextPresent("support@perforce.com"))
				writeFile("9439", "pass", "ManageThemes","checkSupportText", "Check support text"); 
		        else  { writeFile("9439", "fail", "ManageThemes","checkSupportText", "Check support text"); }	
		  
		  if (selenium.isTextPresent("http://www.perforce.com"))
				writeFile("9440", "pass", "ManageThemes","checkPerforceText", "Check Perforce text"); 
		        else  { writeFile("9440", "fail", "ManageThemes","checkPerforceText", "Check Perforce text"); }	
		  
		  if (selenium.isElementPresent("//input[@id='tagFilter-display-magazine' and contains(@value, 'magazine') and contains(@name, 'tagFilter[display][]') ]"))
				writeFile("11138", "pass", "ManageThemes","checkMagazineCheckbox", "Check magazine checkbox"); 
		        else  { writeFile("11138", "fail", "ManageThemes","checkMagazineCheckbox", "Check magazine checkbox"); }
		  
		  if (selenium.isElementPresent("//input[@id='tagFilter-display-responsive' and contains(@value, 'responsive') and contains(@name, 'tagFilter[display][]') ]"))
				writeFile("11134", "pass", "ManageThemes","checkResponsiveCheckbox", "Check responsive checkbox"); 
		        else  { writeFile("11134", "fail", "ManageThemes","checkResponsiveCheckbox", "Check responsive checkbox"); }	
		
		  if (selenium.isElementPresent("//input[@id='tagFilter-display-tablet' and contains(@value, 'tablet') and contains(@name, 'tagFilter[display][]') ]"))
				writeFile("11136", "pass", "ManageThemes","checkTabletCheckbox", "Check tablet checkbox"); 
		        else  { writeFile("11136", "fail", "ManageThemes","checkTabletCheckbox", "Check tablet checkbox"); }	
		  
		  Thread.sleep(4000); 
		  
		  selenium.clickAt("css=div.row-id-business span.dijitDropDownButton","");
		  selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_4-button-action_label')]");  
		  Thread.sleep(4000);
     }
     
     
     //**** VERIFY THE TABLET THEMES MAIN MENU FOR HOME & BACK BUTTONS ****//
     public void verifyTabletFirstMainMenuButtons() throws Exception {
    	 
 		assertTrue(selenium.isElementPresent(("//div[contains(@class,'p4cms-dock-close-button')]")));
 		assertTrue(selenium.isElementPresent(("//span[contains(@class,'menu-handle type-heading')]")));
 		
 		// click close button
 		selenium.click("css=div.p4cms-dock-close-button");
 		Thread.sleep(1000);
 		
 		
 		// verify toggle button
 		selenium.click("css=div.page-header-button.toggle-site-toolbar");
 		Thread.sleep(1000);
 		
    	// verify close icon
		String quart_detailid   = "11155";
		String  quart_testname   = "TabletFirstMainMenuToolbarToggleIcon";
		String  quart_description= "verify the table theme main menu toolbars toggle icon";
		if (selenium.isElementPresent(("//span[contains(@class,'menu-handle type-heading')]")))
		writeFile(quart_detailid, "pass", "BaseTest.java",quart_testname, quart_description); 
		else  { writeFile(quart_detailid, "fail", "BaseTest.java",quart_testname, quart_description); }	
			
 		
	
 		// verify main toolbar
 		assertTrue(selenium.isElementPresent(("//span[contains(@class,'menu-handle type-heading')]")));

 		// click one of the sample pages
 		selenium.click(("//a[contains(@href,'/sample-page')]"));
 		Thread.sleep(4000);
 	    
 	   // verify close icon
 		quart_detailid   = "11154";
 	    quart_testname   = "TabletFirstMainMenuToolbarCloseIcon";
 	    quart_description= "verify the table theme main menu toolbars close icon";
 		 if (selenium.isElementPresent("css=div.p4cms-dock-close-button"))
 	    writeFile("11154", "pass", "BaseTest.java","TabletFirstMainMenuToolbarCloseIcon", "verify the table theme main menu toolbars close icon"); 
	    else  { writeFile("11154", "fail", "BaseTest.java","TabletFirstMainMenuToolbarCloseIcon", "verify the table theme main menu toolbars close icon"); }	
 			
 		// click close button
 	 	selenium.click("css=div.p4cms-dock-close-button");
 	 		
 	 		
 	 	
 	 	// verify HOME icon
		 quart_detailid   = "11156";
		 quart_testname   = "TabletFirstMainMenuHOMEIcon";
		 quart_description= "verify the table theme main menu toolbars home icon";
	     if (selenium.isElementPresent(("//div[contains(@title,'Home')]")))
		 writeFile("11156", "pass", "BaseTest.java","TabletFirstMainMenuHOMEIcon", "verify the table theme main menu toolbars home icon"); 
	     else  { writeFile("11156", "fail", "BaseTest.java","TabletFirstMainMenuHOMEIcon", "verify the table theme main menu toolbars home icon"); }	
			
	     
		 // verify BACK icon
		 quart_detailid   = "11156";
		  quart_testname   = "TabletFirstMainMenuBACKIcon";
		  quart_description= "verify the table theme main menu toolbars back icon";
		 if (selenium.isElementPresent(("//div[contains(@title,'Back')]")))
		 writeFile("11156", "pass", "BaseTest.java","TabletFirstMainMenuBACKIcon", "verify the table theme main menu toolbars back icon"); 
          else  { writeFile("11156", "fail", "BaseTest.java","TabletFirstMainMenuBACKIcon", "verify the table theme main menu toolbars back icon"); }	
    			
 		// verify HOME & BACK buttons
 		assertTrue(selenium.isElementPresent(("//div[contains(@title,'Home')]")));
 		assertTrue(selenium.isElementPresent(("//div[contains(@title,'Back')]")));
 		 
 		
 		
 		// click on HOME icon
 		selenium.click("//div[contains(@title, 'Home')]");
 		Thread.sleep(2000);
 		
 		// verify sample page after clicking HOME icon
 		quart_detailid   = "11157";
 		 quart_testname   = "TabletFirstMainMenuHOMEIconClick";
 		 quart_description= "verify the table theme main menu toolbars click Home icon";
 		  if (selenium.isElementPresent(("//a[contains(@href,'/sample-page')]")))
 		 writeFile("11157", "pass", "BaseTest.java","TabletFirstMainMenuHOMEIconClick", "verify the table theme main menu toolbars click Home icon"); 
 	    else  { writeFile("11157", "fail", "BaseTest.java","TabletFirstMainMenuHOMEIconClick", "verify the table theme main menu toolbars click Home icon"); }	
 				
 	
 		// click on the toggle button & verify the main menu toolbar
 		selenium.click("css=div.page-header-button.toggle-site-toolbar");
 		assertTrue(selenium.isElementPresent(("//span[contains(@class,'menu-handle type-heading')]")));
 		
 		backToHome();
 		Thread.sleep(2000);
    	 
     }
     
     
     //**** VERIFY THE TABLET THEMED CONTENT (PAGE, BLOG, PRESS RELEASE) ****//
     public void verifyTabletContent() throws Exception {
    	 
    	 // basic page
    	 verifyContentElements();
    	 Thread.sleep(2000);
    	 System.out.println("TABLET THEME CONTENT");
    	 selenium.click("//a[@href='/add/type/basic-page']");
    	 Thread.sleep(2000);
    	 addTabletThemeBasicPage();
    	 
    	 // blog post 
    	 verifyContentElements();
    	 Thread.sleep(2000);
    	 selenium.click("//a[@href='/add/type/blog-post']");
    	 Thread.sleep(2000);
    	 addTabletThemeBlogPost();
    	 
    	 // press release
    	 verifyContentElements();
    	 Thread.sleep(2000);
    	 selenium.click("//a[@href='/add/type/press-release']");
    	 Thread.sleep(2000);
    	 addTabletThemePressRelease(); 
     }
 
     
     
     
     //**** ENABLE MODULES --> comments, youtube ****//
     
     public void enableModules() throws Exception {
     	
     	selenium.click(CMSConstants.MANAGE_MODULES);
     	waitForText("Manage Modules");
     	Thread.sleep(2000);
     	
     	selenium.click("id=tagFilter-display-social");
		Thread.sleep(3000);
     	
     	selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td/div/div/div[2]/span/span/span","");  
     	Thread.sleep(3000);
       	selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_8-button-action_label')]");  
     	Thread.sleep(3000);	
     	
     	
        // go to Modules page
		selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		// check to see if comments module is enabled
		selenium.click("id=tagFilter-display-social");
		Thread.sleep(3000);
		
		if (selenium.isElementPresent("//span[contains(@class, 'status enabled')]"))
			{ System.out.println("Comments module is already enabled"); }
		
			else { // enable the comments module
				
			// enable comments
			selenium.click(CMSConstants.MANAGE_MODULES_ELEMENTS);
			waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
			
			selenium.type("id=search-query", "comments");
			Thread.sleep(3000);
			
			// enable comments
		   	selenium.clickAt("//div[4]/div/div[2]/div[2]/div[2]/div/div/div/div/div/table/tbody/tr/td/div/div/div[2]/span/span/span",""); 
			Thread.sleep(3000);
			selenium.click("//span[contains(@id, 'p4cms_ui_ConfirmTooltip_8-button-action_label')]");  
			Thread.sleep(3000);
		} 	
     } 
     
     
     //**** WAIT FOR METHODS (used instead of Thread.sleep(); ****//
      
     public void waitForCondition(String Element, String waitTime) throws Exception { 
     	 //Thread.sleep(waitTime);
    		selenium.waitForCondition("selenium.isElementPresent(\"Element\")", CMSConstants.PAGE_TIMEOUT);
      	}  
     
     public static void waitForPageToLoad(String waitTime) throws Exception {
    	 	selenium.waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
       } 
     
     public static void waitForElements(String Element) throws Exception {
     	for (int x = 0;; x++) 
     	 { if (x >= 250) fail("Page timeout");
     	   
     	    try { if (selenium.isElementPresent(Element)) break; }    
     	    catch (Exception e) {}
     	    Thread.sleep(1000);
     	}
     }   
     
     public static void waitForVisible(String Element) throws Exception {	
     	for (int x = 0;; x++) 
     	 { 
     		if (x >= 250) fail("Page timeout");    	   
     	    try { if (selenium.isVisible(Element)) break; }    
     	    catch (Exception e) {}
     	    Thread.sleep(1000);
     	}
     }
     
    public static void waitForText(String Text) throws Exception {
     	for (int x = 0;; x++) 
     	 {  if (x >= 250) fail("Page timeout");
     	    try { if (selenium.isTextPresent(Text)) break; }    
     	    catch (Exception e) {}
     	    Thread.sleep(1000);
     	}
     }
     
   
//*********************** PERFORCE CHRONICLE CODE **********************************************************************************************************************************************//
	
    
    public void verifyTrue(boolean condition) {
    	try {
    		assertTrue(condition);
    	} catch(Throwable e) {
    		addVerificationFailure(e);
    		logger.info("Oops! a verifyTrue() just failed");
    	}
    }
    
    
    public static void verifyTrue(String message, boolean condition) {
    	try { 
    		assertTrue(message, condition);
    	} catch(Throwable e) {
    		addVerificationFailure(e);
    		logger.info("Oops! a verifyTrue() just failed");
    	}
    }
   
    
    private static Map<ITestResult, List<Throwable>> verificationFailuresMap = new HashMap<ITestResult, List<Throwable>>();
    
	public static List<Throwable> getVerificationFailures() {
		List<Throwable> verificationFailures = verificationFailuresMap.get(Reporter.getCurrentTestResult());
		return verificationFailures == null ? new ArrayList<Throwable>() : verificationFailures;
	}
	
	
	private static void addVerificationFailure(Throwable e) {
		List<Throwable> verificationFailures = getVerificationFailures();
		verificationFailuresMap.put(Reporter.getCurrentTestResult(), verificationFailures);
		verificationFailures.add(e);
	}
    
	
	//general page opening method
	public void p4CMSVerifyElement(String locator,String attrLocator, String attr){
		assertTrue(selenium.isElementPresent(locator));  // verify that the element is present
		if(!attrLocator.equals("")){
			String attrValue = selenium.getAttribute(attrLocator); //get the value of the element attribute
			logger.info("The "+ attr + " attribute for " + locator + " = " + attrValue);
			assertEquals(attrValue , attr); //compare the attribute from excel
		}
	}
}

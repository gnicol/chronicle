package tests;

import org.testng.annotations.BeforeClass;
import org.testng.annotations.DataProvider;
import org.testng.annotations.Parameters;
import org.testng.annotations.Test;

import shared.BaseTest;


//This code clicks on manage --> themes and verifies the title

public class ManageThemesPageVerify extends shared.BaseTest  {
	
	private String baseurl;
	private String redirecturl;
	private String usergroup;
	private String quart_scriptname = "ManageThemesPageVerify";

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
	public void validate(String username, String password) throws Exception {

		// Login to Chronicle
      	  chronicleLogin(username, password);
	      waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);

		// Verify Chronicle home page elements 
		manageThemesPageVerify();
				
		// Logout and verify Login link
		selenium.click("link=Logout");
		//selenium.waitForCondition("selenium.isElementPresent(\"//a[@class='p4cms-user-login user-login type-mvc']\")", "10000");
		waitForElements("link=Login");  

	}
	
	public void manageThemesPageVerify() throws Exception {
		 
		// Click on Manage --> Manage content types
		manageMenu();
		selenium.click(CMSConstants.MANAGE_THEMES_PAGE_VERIFY);
		waitForPageToLoad(CMSConstants.PAGE_TIMEOUT);
		
		//writeFile1("\nskipped: 1044", "", "");
		
		 String quart_detailid   = "6146";
		 String  quart_testname   = "BusinessPageTitle";
		 String  quart_description= "Business themes page title";
			if (selenium.isTextPresent("Manage Themes"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
	    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }
			
			
			// click business checkbox
			selenium.click("id=tagFilter-display-business");
			Thread.sleep(2000);
			
			
			 quart_detailid   = "9473";
			 quart_testname   = "BusinessPerforceText";
			 quart_description= "verify business theme Perforce";
				if (selenium.isTextPresent("Perforce Software"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			
				
				 quart_detailid   = "9474";
				 quart_testname   = "BusinessEmail";
				 quart_description= "verify business theme email";
					if (selenium.isTextPresent("support@perforce.com"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			  		
				
			 quart_detailid   = "9475";
			 quart_testname   = "BusinessUrl";
			 quart_description= "verify business theme url";
				if (selenium.isTextPresent("http://www.perforce.com"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
						
				 quart_detailid   = "9472";
				 quart_testname   = "BusinessVersion";
				 quart_description= "verify business theme version";
					if (selenium.isTextPresent("1.0"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
				
					
				 quart_detailid   = "9470";
				 quart_testname   = "BusinessText";
				 quart_description= "verify business theme look";
					if (selenium.isTextPresent("A theme with a business-oriented look."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "9469";
				 quart_testname   = "BusinessDefault";
				 quart_description= "verify business theme default";
					if (selenium.isTextPresent("Business"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "7267";
				 quart_testname   = "BusinessIcon";
				 quart_description= "verify business icon";
					if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/business/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				    else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
									
			
			// uncheck
		selenium.click("id=tagFilter-display-business");
		Thread.sleep(2000);	
		
		
		// check gray theme
		selenium.click("id=tagFilter-display-gray");
		Thread.sleep(2000);
		
		
		
		 quart_detailid   = "9466";
		 quart_testname   = "GrayPerforce";
		 quart_description= "verify gray perforce";
			if (selenium.isTextPresent("Perforce Software"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
		
			
			 quart_detailid   = "9467";
			 quart_testname   = "GrayEmail";
			 quart_description= "verify gray email";
				if (selenium.isTextPresent("support@perforce.com"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		      else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
			
		 quart_detailid   = "9468";
		 quart_testname   = "GrayUrl";
		 quart_description= "verify gray url";
			if (selenium.isTextPresent("http://www.perforce.com"))
			writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		   else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			
					
			 quart_detailid   = "9465";
			 quart_testname   = "GrayVersion";
			 quart_description= "verify gray version";
				if (selenium.isTextPresent("v1.0"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
			
				
			 quart_detailid   = "9463";
			 quart_testname   = "GrayText";
			 quart_description= "verify gray text";
				if (selenium.isTextPresent("A generic 960-grid theme with gray tones for users to input their own content."))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
									
				
				
			 quart_detailid   = "7053";
			 quart_testname   = "Gray960";
			 quart_description= "verify gray 960";
				if (selenium.isTextPresent("960 Gray"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		    	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
		 							
				
				
			 quart_detailid   = "7047";
			 quart_testname   = "GrayIcon";
			 quart_description= "verify gray icon";
				if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/960gray/icon.png')]")))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		     	else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
								
		
				quart_detailid   = "9464";
				 quart_testname   = "GrayApplyButton";
				 quart_description= "verify gray apply button";
					if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitArrowButtonInner')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
		    		else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
				
			
			// uncheck gray theme
			selenium.click("id=tagFilter-display-gray");
			Thread.sleep(2000);
					
			
			// check nature spring theme
			selenium.click("id=tagFilter-display-nature");
			Thread.sleep(2000);
			
			 quart_detailid   = "7048";
			 quart_testname   = "SpringPerforce";
			 quart_description= "verify nature spring perforce";
				if (selenium.isTextPresent("Perforce Software"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			
				
				 quart_detailid   = "9485";
				 quart_testname   = "SpringEmail";
				 quart_description= "verify spring email";
					if (selenium.isTextPresent("support@perforce.com"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
				
			 quart_detailid   = "9456";
			 quart_testname   = "SpringUrl";
			 quart_description= "verify nature spring url";
				if (selenium.isTextPresent("http://www.perforce.com"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
						
				 quart_detailid   = "7052";
				 quart_testname   = "SpringVersion";
				 quart_description= "verify nature spring version";
					if (selenium.isTextPresent("v1.0"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
				
					
				 quart_detailid   = "6552";
				 quart_testname   = "SpringText";
				 quart_description= "verify nature spring text";
					if (selenium.isTextPresent("A theme featuring spring colours."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "9477";
				 quart_testname   = "SpringTextTheme";
				 quart_description= "verify nature spring text";
					if (selenium.isTextPresent("Spring"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "7268";
				 quart_testname   = "SpringIcon";
				 quart_description= "verify nature spring icon";
					if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/spring/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
									
			
					quart_detailid   = "7049";
					quart_testname   = "SpringApplyButton";
					quart_description= "verify spring apply button";
						if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitArrowButtonInner')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
			
				
				 quart_detailid   = "9484";
				 quart_testname   = "WinterPerforce";
				 quart_description= "verify nature winter perforce";
					if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
					
					 quart_detailid   = "9486";
					 quart_testname   = "WinterEmail";
					 quart_description= "verify nature winter email";
						if (selenium.isTextPresent("support@perforce.com"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
					
				 quart_detailid   = "9483";
				 quart_testname   = "WinterUrl";
				 quart_description= "verify nature winter url";
					if (selenium.isTextPresent("http://www.perforce.com"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
							
					 quart_detailid   = "9481";
					 quart_testname   = "WinterVersion";
					 quart_description= "verify nature winter version";
						if (selenium.isTextPresent("v1.0"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
								
					
						
					 quart_detailid   = "9479";
					 quart_testname   = "WinterText";
					 quart_description= "verify nature winter theme";
						if (selenium.isTextPresent("A theme with a winter look"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
											
						
						
					 quart_detailid   = "9478";
					 quart_testname   = "WinterTextTheme";
					 quart_description= "verify nature winter theme";
						if (selenium.isTextPresent("Winter"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
											
						
						
					 quart_detailid   = "9476";
					 quart_testname   = "WinterIcon";
					 quart_description= "verify nature winter icon";
						if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/winter/icon.png')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
				
				
						quart_detailid   = "9480";
						quart_testname   = "WinterApplyButton";
						 quart_description= "verify winter apply button";
							if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitArrowButtonInner')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
								
				
				// uncheck nature spring theme
				selenium.click("id=tagFilter-display-nature");
				Thread.sleep(2000);			
			
				
				// red theme
			selenium.click("id=tagFilter-display-red");
			Thread.sleep(2000);
			
			 quart_detailid   = "10008";
			 quart_testname   = "RedPerforce";
			 quart_description= "verify nature red perforce";
				if (selenium.isTextPresent("Perforce Software"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			
				
				 quart_detailid   = "9461";
				 quart_testname   = "RedEmail";
				 quart_description= "verify nature red email";
					if (selenium.isTextPresent("support@perforce.com"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
				
			 quart_detailid   = "9462";
			 quart_testname   = "RedUrl";
			 quart_description= "verify nature red url";
				if (selenium.isTextPresent("http://www.perforce.com"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
						
				 quart_detailid   = "9459";
				 quart_testname   = "RedVersion";
				 quart_description= "verify nature red version";
					if (selenium.isTextPresent("v1.0"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
				
					
				 quart_detailid   = "9457";
				 quart_testname   = "RedText";
				 quart_description= "verify nature red text";
					if (selenium.isTextPresent("A generic 960-grid theme with red tones for users to input their own content."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "7054";
				 quart_testname   = "Red960";
				 quart_description= "verify nature red 960";
					if (selenium.isTextPresent("960 Red"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "7050";
				 quart_testname   = "RedIcon";
				 quart_description= "verify nature red icon";
					if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/960red/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
			
			
					quart_detailid   = "9458";
					 quart_testname   = "RedApplyButton";
					 quart_description= "verify red apply button";
						if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitArrowButtonInner')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
		
			// uncheck red 
			selenium.click("id=tagFilter-display-red");
			Thread.sleep(2000);
			
			// check blue theme
			selenium.click("id=tagFilter-display-blue");
			Thread.sleep(2000);
	
			
			 quart_detailid   = "6553";
			 quart_testname   = "BluePerforce";
			 quart_description= "verify nature blue perforce";
				if (selenium.isTextPresent("Perforce Software"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
			
				
				 quart_detailid   = "9447";
				 quart_testname   = "BlueEmail";
				 quart_description= "verify nature blue email";
					if (selenium.isTextPresent("support@perforce.com"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
				
			 quart_detailid   = "7051";
			 quart_testname   = "BlueUrl";
			 quart_description= "verify nature blue url";
				if (selenium.isTextPresent("http://www.perforce.com"))
				writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
			else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
						
				 quart_detailid   = "9449";
				 quart_testname   = "BlueVersion";
				 quart_description= "verify nature blue version";
					if (selenium.isTextPresent("v1.0"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
							
				
					
				 quart_detailid   = "7266";
				 quart_testname   = "BlueText";
				 quart_description= "verify nature blue text";
					if (selenium.isTextPresent("A generic 960-grid theme with blue tones for users to input their own content."))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "9446";
				 quart_testname   = "Blue960";
				 quart_description= "verify nature blue 960";
					if (selenium.isTextPresent("960 Blue"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
										
					
					
				 quart_detailid   = "6550";
				 quart_testname   = "BlueIcon";
				 quart_description= "verify nature blue icon";
					if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/960blue/icon.png')]")))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
			
			
		
					quart_detailid   = "9448";
					quart_testname   = "BlueApplyButton";
					 quart_description= "verify blue apply button";
						if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitArrowButtonInner')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
									
					
					
					
				 quart_detailid   = "9454";
				 quart_testname   = "SoftBluePerforce";
				 quart_description= "verify soft blue perforce";
					if (selenium.isTextPresent("Perforce Software"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
				
					
					 quart_detailid   = "9455";
					 quart_testname   = "SoftBlueEmail";
					 quart_description= "verify nature soft blue email";
						if (selenium.isTextPresent("support@perforce.com"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
						
					
				 quart_detailid   = "9482";
				 quart_testname   = "SoftBlueUrl";
				 quart_description= "verify nature soft blue url";
					if (selenium.isTextPresent("http://www.perforce.com"))
					writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
				else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
					
							
					 quart_detailid   = "9453";
					 quart_testname   = "SoftBlueVersion";
					 quart_description= "verify nature soft blue version";
						if (selenium.isTextPresent("v1.0"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
								
					
						
					 quart_detailid   = "9451";
					 quart_testname   = "SoftBlueText";
					 quart_description= "verify nature soft blue text";
						if (selenium.isTextPresent("A generic 960-grid theme with soft blue tones for users to input their own content."))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
											
						
						
					 quart_detailid   = "9450";
					 quart_testname   = "SoftBlue960";
					 quart_description= "verify nature soft blue 960";
						if (selenium.isTextPresent("960 Soft Blue"))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
											
						
						
					 quart_detailid   = "6557";
					 quart_testname   = "SoftBlueIcon";
					 quart_description= "verify nature soft blue icon";
						if (selenium.isElementPresent(("//img[contains(@src, '/sites/all/themes/960softblue/icon.png')]")))
						writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
					else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }			
				
						
						quart_detailid   = "9452";
						quart_testname   = "SoftBlueApplyButton";
						 quart_description= "verify soft blue apply button";
							if (selenium.isElementPresent(("//span[contains(@class, 'dijitReset dijitInline dijitArrowButtonInner')]")))
							writeFile(quart_detailid, "pass", quart_scriptname, quart_testname, quart_description ); 
						else  { writeFile(quart_detailid, "fail", quart_scriptname, quart_testname, quart_description );  }	
								
			
		//Back to Website
			backToHome();
	}
}


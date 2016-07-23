from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Setup_Server(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_setup_server(self):
        sel = self.selenium
        sel.open("/")
        sel.click("//div[@id='layout-main']/div/div[1]/a/img")
        functions.waitForElementPresent(sel,"dijit_form_Button_0_label")
        sel.click("dijit_form_Button_0_label")
        functions.waitForElementPresent(sel,"continue_label")
        sel.click("continue_label")
        functions.waitForElementPresent(sel,"user")
        sel.type("user", "p4cms")
        sel.type("password", "p4cms123")
        sel.type("passwordConfirm", "p4cms123")
        sel.click("//input[@name='continue']")
        functions.waitForElementPresent(sel,"create_label")        
        sel.click("create_label")
        functions.waitForTextPresent(sel,"You have successfully created the site.")
        try: 
            self.failUnless(sel.is_text_present("You have successfully created the site."))
            functions.printResults("1817","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1817","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1817")
    functions.printDetailIds(detailids)
    
    unittest.main()
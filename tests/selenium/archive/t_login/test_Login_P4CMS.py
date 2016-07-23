from selenium import selenium
import unittest, time, re, sys, getopt, os
sys.path.append("shared")
import functions

class test_Login_P4CMS(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_Login_P4CMS(self):
        sel = self.selenium
        sel.open("/")
        sel.click("link=Login")
        functions.waitForElementPresent(sel,"partial-user")
        sel.type("partial-user", "p4cms")
        sel.type("partial-password", "p4cms123")
        sel.click("login")
        sel.click("partial-login_label")
        sel.click("link=Home")
        functions.waitForElementPresent(sel,"link=Logout")
        try: 
            self.failUnless(sel.is_element_present("link=Logout"))
            functions.printResults("6046","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6046","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)
        
if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]

    detailids = ("6046")
    functions.printDetailIds(detailids)
    
    unittest.main()

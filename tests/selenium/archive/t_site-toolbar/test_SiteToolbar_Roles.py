from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SiteToolbar_Roles(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_sitetoolbar_roles(self):
        sel = self.selenium
        sel.open("/")
        functions.waitForElementPresent(sel,"p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("css=ul > li:nth(2) > ul > li:nth(1) > a > span")
        functions.waitForTextPresent(sel,"Add Role")
        try:
            self.failUnless(sel.is_text_present("Add Role"))
            functions.printResults("6148","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6148","pass")
                
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6148")
    functions.printDetailIds(detailids)
        
    unittest.main()

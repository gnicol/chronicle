from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SiteToolbar_ManageContentTypes(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_sitetoolbar_managecontenttypes(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        functions.waitForElementPresent(sel,"p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li[2]/a/span")
        functions.waitForTextPresent(sel,"Manage Content Types")
        try: 
            self.failUnless(sel.is_text_present("Manage Content Types"))
            functions.printResults("6142","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6142","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6142")
    functions.printDetailIds(detailids)

    unittest.main()

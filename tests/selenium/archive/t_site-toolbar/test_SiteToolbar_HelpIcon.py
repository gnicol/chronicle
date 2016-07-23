from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SiteToolbar_HelpIcon(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_sitetoolbar_helpicon(self):
        sel = self.selenium
        sel.open("/")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_3']/span")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[8]/input")
        functions.waitForElementPresent(sel,"link=Introduction")
        try: 
            self.failUnless(sel.is_element_present("link=Introduction"))
            functions.printResults("6094","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6094","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6094")
    functions.printDetailIds(detailids)
        
    unittest.main()

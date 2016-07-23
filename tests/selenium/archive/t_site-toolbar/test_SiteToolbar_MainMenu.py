from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SiteToolbar_MainMenu(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_sitetoolbar_mainmenu(self):
        sel = self.selenium
        sel.open("/")
        try: 
            self.failUnless(sel.is_text_present("Manage"))
            functions.printResults("6688","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6688","fail")
        try: 
            self.failUnless(sel.is_text_present("Add Content"))
            functions.printResults("6689","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6689","fail")
        try: 
            self.failUnless(sel.is_text_present("Widgets"))
            functions.printResults("6690","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6690","fail")
        try: 
            self.failUnless(sel.is_element_present("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_3']/span"))
            functions.printResults("6691","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6691","fail")
        try:
            self.failUnless(sel.is_element_present("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_2']/span"))
            functions.printResults("6692","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6692","fail")
        try: 
            self.failUnless(sel.is_element_present("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_1']/span"))
            functions.printResults("6693","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6693","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6688,6689,6690,6691,6692,6693")
    functions.printDetailIds(detailids)
        
    unittest.main()

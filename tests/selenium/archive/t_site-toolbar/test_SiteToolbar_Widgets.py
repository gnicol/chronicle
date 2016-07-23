from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SiteToolbar_Widgets(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_sitetoolbar_wiget(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-widgets_label")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[5]/input")
        #functions.waitForELementPresent(sel,"//span[@id='p4cms-site-toolbar-stack-controller']/span[5]/input")
        try: 
            self.failIf(sel.is_element_present("css=dijitReset dijitInline dijitIcon configIcon dojoattachpoint=iconNode"))
            functions.printResults("6147","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6147","fail")
        try: 
            self.failIf(sel.is_element_present("css=dijitReset dijitInline dijitIcon deleteIcon dojoattachpoint=iconNode"))
            functions.printResults("6147","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6147","fail")
        try: 
            self.failIf(sel.is_element_present("css=dijitReset dijitInline dijitIcon plusIcon dojoattachpoint=iconNode"))
            functions.printResults("6147","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6147","fail")

    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6147")
    functions.printDetailIds(detailids)
        
    unittest.main()

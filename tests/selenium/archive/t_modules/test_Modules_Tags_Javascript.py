from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Modules_Tags_Javascript(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_modules_tags_javascript(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[2]/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=tagFilter-display-javascript")
        functions.waitForTextPresent(sel,"Dojo")
        try: 
           self.failUnless(sel.is_text_present("Dojo"))
           functions.printResults("2305","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2305","fail")
        try: 
           self.failUnless(sel.is_text_present("Ui"))
           functions.printResults("2305","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2305","fail")
           
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("2305")
    functions.printDetailIds(detailids)

    unittest.main()

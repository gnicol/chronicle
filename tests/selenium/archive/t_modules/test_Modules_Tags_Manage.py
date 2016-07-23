from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Modules_Tags_Manage(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_modules_tags_manage(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[2]/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=tagFilter-display-manage")
        functions.waitForTextPresent(sel,"Content")
        try: 
           self.failUnless(sel.is_text_present("Content"))
           functions.printResults("2306","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2306","fail")
        try: 
           self.failUnless(sel.is_text_present("Setup"))
           functions.printResults("2306","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2306","fail")
        try: 
           self.failUnless(sel.is_text_present("Site"))
           functions.printResults("2306","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2306","fail")
        try: 
           self.failUnless(sel.is_text_present("System"))
           functions.printResults("2306","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2306","fail")   
        try: 
           self.failUnless(sel.is_text_present("User"))
           functions.printResults("2306","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2306","fail")
        try: 
           self.failIf(sel.is_text_present("Workflow"))
           functions.printResults("2306","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2306","fail")
           
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("2306")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SystemInfo_Modules(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_systeminfo_modules(self):
        sel = self.selenium
        sel.open("/")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0']/span")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[4]/ul/li/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_6 > span.tabLabel")
        functions.waitForTextPresent(sel,"Category")
        try: 
           self.failUnless(sel.is_text_present("Category"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Content"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Diff"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Dojo"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Error"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("History"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Menu"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Setup"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Site"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("System"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Ui"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("User"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Widget"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Workflow"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Flickr"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Search"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")
        try: 
           self.failUnless(sel.is_text_present("Youtube"))
           functions.printResults("6545","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6545","fail")

    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6545")
    functions.printDetailIds(detailids)

    unittest.main()

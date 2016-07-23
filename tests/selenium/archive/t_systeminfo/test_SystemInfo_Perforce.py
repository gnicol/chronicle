from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SystemInfo_Perforce(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_systeminfo_perforce(self):
        sel = self.selenium
        sel.open("/")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0']/span")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[4]/ul/li/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_6 > span.tabLabel")
        functions.waitForTextPresent(sel,"Server Root")
        try: 
           self.failUnless(sel.is_text_present("Server Root"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("Server Date"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("Server Version"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("Case Handling"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("Client Version"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("Is Licensed"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("User Limit"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("Client Limit"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
        try: 
           self.failUnless(sel.is_text_present("File Limit"))
           functions.printResults("6544","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6544","fail")
           
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6544")
    functions.printDetailIds(detailids)

    unittest.main()

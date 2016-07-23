from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Permissions_Resources_Menus(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_permissions_resources_menus(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[3]/ul/li[3]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=resource-resources-menus")
        functions.waitForElementPresent(sel,"css=span.resourceLabel > Menus")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Menus"))
           functions.printResults("1465","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1465","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Add Menu Items"))
           functions.printResults("1465","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1465","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Menus"))
           functions.printResults("1465","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1465","fail")
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("1465")
    functions.printDetailIds(detailids)

    unittest.main()

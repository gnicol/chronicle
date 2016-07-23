from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageUsers_Role_Anonymous(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manageusers_role_anonymous(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[3]/ul/li/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=role-roles-anonymous")
        functions.waitForTextPresent(sel,"anonymous")
        try: 
           self.failUnless(sel.is_text_present("anonymous"))
           functions.printResults("1198","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1198","fail")
        try: 
           self.failIf(sel.is_element_present("css=num-rows > 0"))
           functions.printResults("1198","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1198","fail")
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("1198")
    functions.printDetailIds(detailids)

    unittest.main()

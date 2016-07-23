from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Roles_AddRole(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_roles_addrole(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[3]/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=dijit_form_Button_0_label")
        sel.click("//input[@value='Add Role']")
        functions.waitForElementPresent(sel,"id=id")
        sel.type("id=id", "contractor")
        sel.click("id=save_label")
        functions.waitForTextPresent(sel,"Add Role")
        try: 
           self.failIf(sel.is_element_present("css=form-dialog p4cms-ui dijitDialog"))
           functions.printResults("713","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("713","fail")
        try: 
           self.failUnless(sel.is_text_present("Add Role"))
           functions.printResults("1048","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1048","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_1_label"))
           functions.printResults("1041","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1041","fail")
        try: 
           self.failUnless(sel.is_element_present("id=save_label"))
           functions.printResults("6069","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6069","fail")
        try: 
           self.failIf(sel.is_text_present("contractor"))
           functions.printResults("1045","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1045","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("713,1041,1045,1048,6069")
    functions.printDetailIds(detailids)

    unittest.main()

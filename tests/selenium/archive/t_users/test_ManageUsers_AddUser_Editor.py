from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageUsers_AddUser_Editor(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manageusers_adduser_editor(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[3]/ul/li/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=dijit_form_Button_0_label")
        sel.click("//input[@value='Add User']")
        functions.waitForTextPresent(sel,"Add User")
        functions.waitForElementPresent(sel,"id=id")       
        sel.type("id=id", "curly")
        sel.type("id=email", "curly@chronicle.com")
        sel.type("id=fullName", "curly sue")
        sel.type("id=password", "p4cms123")
        sel.type("id=passwordConfirm", "p4cms123")
        sel.click("id=roles-editor")
        sel.click("id=save_label")
        try: 
           self.failUnless(sel.is_text_present("Add User"))
           functions.printResults("1414","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1414","fail")
        try: 
           self.failIf(sel.is_element_present("id=dijit_form_Button_2_label"))
           functions.printResults("1412","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1412","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.dijitDialogCloseIcon.dijitDialogCloseIconHover"))
           functions.printResults("1411","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1411","fail")
        try: 
           self.failUnless(sel.is_element_present("id=id"))
           functions.printResults("6781","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6781","fail")
        try: 
           self.failUnless(sel.is_element_present("id=email"))
           functions.printResults("6782","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6782","fail")
        try: 
           self.failUnless(sel.is_element_present("id=fullName"))
           functions.printResults("6783","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6783","fail")
        try: 
           self.failUnless(sel.is_element_present("id=password"))
           functions.printResults("6784","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6784","fail")
        try: 
           self.failUnless(sel.is_element_present("id=roles-editor"))
           functions.printResults("1418","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1418","fail")
        try: 
           self.failIf(sel.is_element_present("css=message > User 'curly' has been successfuly added."))
           functions.printResults("1401","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1401","fail")
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("1401,1411,1412,1414,1418,6781,6782,6783,6784")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageWorkflows_DeleteWorkflow(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manageworkflows_delete_workflow(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li[4]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("css=input.dijitOffScreen")
        sel.click_at("css=span > img", "")
        sel.click_at("id=dijit_MenuItem_1_text", "")
        sel.click("css=#p4cms_ui_ConfirmDialog_0-button-action_label")
        functions.waitForTextPresent(sel,"Delete Workflow")
        try:
           self.failUnless(sel.is_text_present("Delete Workflow"))
           functions.printResults("6506","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6506","fail")
        try: 
           self.failIf(sel.is_element_present("css=dijitDialogCloseIcon"))
           functions.printResults("6504","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6504","fail")
        try:
           self.failUnless(sel.is_element_present("id=p4cms_ui_ConfirmDialog_0-button-action_label"))
           functions.printResults("6052","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6052","fail")
        try:
           self.failIf(sel.is_element_present("id=p4cms_ui_ConfirmDialog_3"))
           functions.printResults("6050","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6050","fail")
        try: 
           self.failUnless(sel.is_text_present("Are you sure you want to delete the \"Draft Workflow\" workflow?"))
           functions.printResults("6505","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6505","fail")
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6506,6505,6504,6052,6050")
    functions.printDetailIds(detailids)

    unittest.main()

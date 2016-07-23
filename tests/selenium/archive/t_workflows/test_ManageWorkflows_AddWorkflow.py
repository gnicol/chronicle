from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageWorkflows_AddWorkflow(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manageworkflows_add_workflow(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li[4]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=dijit_form_Button_0_label")
        sel.click("//input[@value='Add Workflow']")
        functions.waitForTextPresent(sel,"Add Workflow")
        functions.waitForElementPresent(sel,"id=id")
        sel.type("id=id", "Draft")
        sel.type("id=label", "Draft Workflow")
        sel.type("id=description", "Hello Draft Workflow")
        sel.type("id=states", "[draft]\nlabel = \"Draft\"\ntransitions.review.label = \"Promote to Review\"\ntransitions.published.label = \"Publish\"")
        sel.click("id=save_label")
        functions.waitForElementPresent(sel,"id=save_label")
        functions.waitForTextPresent(sel,"Draft Workflow")
        try: 
           self.failUnless(sel.is_text_present("Add Workflow"))
           functions.printResults("6510","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6510","fail")
        try: 
           self.failUnless(sel.is_text_present("Draft Workflow"))
           functions.printResults("6511","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6511","fail")
        try: 
           self.failIf(sel.is_element_present("css=dijitDialogCloseIcon"))
           functions.printResults("6509","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6509","fail")
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6509,6510,6511")
    functions.printDetailIds(detailids)

    unittest.main()

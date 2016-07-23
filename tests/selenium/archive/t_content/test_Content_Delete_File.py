from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Delete_File(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_delete_file(self):
        sel = self.selenium
        sel.open("/")
        sel.click("link=Chronicle File")
        sel.wait_for_page_to_load("30000")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-edit_label")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[2]/input")
        sel.click("id=edit-content-toolbar-button-delete_label")
        sel.click("//div[@id='edit-content-toolbar']/span[3]/input")
        sel.click("//div[@id='buttons-element']/fieldset/span/input")
        sel.click("id=p4cms_ui_ConfirmDialog_0-button-action_label")
        functions.waitForElementNotPresent(sel,"id=p4cms_ui_ConfirmDialog_0-button-action_label")
        functions.waitForTextPresent(sel,"Recent Content")
        try: 
           self.failUnless(sel.is_text_present("Recent Content"))
           functions.printResults("6536","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6536","fail")
        try: 
           self.failUnless(sel.is_text_present("Chronicle File"))
           functions.printResults("6536","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6536","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6536")
    functions.printDetailIds(detailids)

    unittest.main()

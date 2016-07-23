from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Edit_BasicPage(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_content_edit_basicpage(self):
        sel = self.selenium
        sel.open("/")
        sel.click("link=Basic Page")
        sel.wait_for_page_to_load("30000")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-edit_label")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[2]/input")
        sel.click("css=span.value-node")
        functions.waitForElementPresent(sel,"title-label")
        sel.type("title", "Edit Basic Page")
        sel.click("css=#p4cms_content_Element_1 > span.value-node")
        sel.click("//div[@id='edit-content-toolbar']/span[5]/input")
        sel.click_at("dijit_form_DropDownButton_1_label", "")
        sel.click("edit-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"dijit_form_DropDownButton_1_label")
        functions.waitForTextPresent(sel,"Edit Basic Page")

        try: 
            self.failUnless(sel.is_text_present("Edit Basic Page"))
            functions.printResults("6153","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6153","fail")

    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6153")
    functions.printDetailIds(detailids)

    unittest.main()
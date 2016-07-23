from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_BlogPost(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_blogpost(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[3]/input")
        sel.click("link=Blog Post")
        functions.waitForElementPresent(sel,"p4cms_content_Element_0")
        sel.type("title", "Chronicle Blog Post")
        sel.click_at("css=#p4cms_content_Element_1 > span.value-placeholder", "")
        sel.click("//table[@id='date_popup']/tbody/tr[5]/td[4]/span")
        sel.click_at("css=span.value-placeholder", "")
        sel.type("author", "Moe")
        sel.type("body", "Chronicle Blog Post In Place Mode")
        functions.waitForElementPresent(sel,"dijit_form_DropDownButton_2_label")
        sel.click_at("dijit_form_DropDownButton_2_label", "")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"dijit_form_DropDownButton_2_label")
        functions.waitForTextPresent(sel,"Chronicle Blog Post")     
        try: 
            self.failUnless(sel.is_text_present("Chronicle Blog Post"))
            functions.printResults("1602","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1602","fail")
        try: 
            self.failUnless(sel.is_element_present("p4cms_content_Element_1"))
            functions.printResults("1602","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1602","fail")
        try: 
            self.failUnless(sel.is_text_present("Moe"))
            functions.printResults("1602","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1602","fail")

    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1602")
    functions.printDetailIds(detailids)

    unittest.main()

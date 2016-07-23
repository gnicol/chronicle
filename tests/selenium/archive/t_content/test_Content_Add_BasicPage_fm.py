from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_BasicPage_fm(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_basicpage_fm(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[3]/input")
        sel.click("css=a > img")
        functions.waitForElementPresent(sel,"add-content-toolbar-button-form_label")
        sel.click("add-content-toolbar-button-form_label")
        functions.waitForElementPresent(sel,"//div[@id='add-content-toolbar']/span[2]/input")
        sel.click("//div[@id='add-content-toolbar']/span[2]/input")
        functions.waitForElementPresent(sel,"title-label")
        sel.type("title", "Form Mode Basic Page")
        functions.waitForElementPresent(sel,"dijitEditorBody")
        sel.type("dijitEditorBody", "This is Basic Page in Form Mode")
        sel.click_at("dijit_form_DropDownButton_1", "")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"css=#dijit_form_DropDownButton_1_label")
        functions.waitForTextPresent(sel,"Form Mode Basic Page")
        try: 
           self.failUnless(sel.is_text_present("Form Mode Basic Page"))
           functions.printResults("1583","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1583","fail")
        try: 
           self.failUnless(sel.is_text_present("This is Basic Page in Form Mode"))
           functions.printResults("1583","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1583","fail")
       
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1583")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_BasicPage(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_content_add_basicpage(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[3]/input")
        functions.waitForElementPresent(sel,"css=a > img")
        sel.click("css=a > img")
        functions.waitForElementPresent(sel,"title-label")
        sel.type("title", "Basic Page")
        sel.click("css=#p4cms_content_Element_1 > span.value-node")
        functions.waitForElementPresent(sel,"dijitEditorBody")
        sel.type("dijitEditorBody", "This is a basic page")
        sel.click_at("dijit_form_DropDownButton_1", "")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"css=#dijit_form_DropDownButton_1_label")
        functions.waitForTextPresent(sel,"Basic Page")        
        try: 
            self.failUnless(sel.is_text_present("Basic Page"))
            functions.printResults("1600","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1600","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1600")
    functions.printDetailIds(detailids)

    unittest.main()

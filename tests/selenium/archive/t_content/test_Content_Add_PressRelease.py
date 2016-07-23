from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_PressRelease(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_pressrelease(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[3]/input")
        sel.click("//div[@id='dijit_layout_ContentPane_0']/ul/li[3]/span/a/img")
        sel.wait_for_page_to_load("30000")
        sel.click("p4cms_content_Element_0")
        functions.waitForElementPresent(sel,"title")
        sel.type("title", "Press Release IPM")
        sel.click("css=#p4cms_content_Element_2 > span.value-placeholder")
        sel.click("//table[@id='date_popup']/tbody/tr[5]/td[4]/span")
        sel.click("css=#p4cms_content_Element_3 > span.value-node > span.value-placeholder")
        functions.waitForElementPresent(sel,"location")
        sel.type("location", "Alameda, CA")
        sel.click("css=#p4cms_content_Element_4 > span.value-node")
        functions.waitForElementPresent(sel,"css=#p4cms_content_Element_4 > span.value-node")
        functions.waitForElementPresent(sel,"dijitEditorBody")
        sel.type("dijitEditorBody", "This is a press release IPM")
        sel.click("css=#p4cms_content_Element_5 > span.value-node")
        functions.waitForElementPresent(sel,"dijit_form_DropDownButton_2_label")
        sel.click_at("dijit_form_DropDownButton_2_label", "")
        functions.waitForElementPresent(sel,"css=form.p4cms-ui > #workflow-sub-form > #fieldset-workflow > dl")
        functions.waitForElementPresent(sel,"add-content-toolbar-button-save_label")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"dijit_form_DropDownButton_2_label")
        functions.waitForTextPresent(sel,"Press Release IPM")     
        try: 
           self.failUnless(sel.is_text_present("Press Release IPM"))
           functions.printResults("1597","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1597","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_content_Element_2"))
           functions.printResults("1597","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1597","fail")
        try: 
           self.failUnless(sel.is_text_present("Alameda, CA"))
           functions.printResults("1597","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1597","fail")
        try: 
           self.failUnless(sel.is_text_present("This is a press release IPM"))
           functions.printResults("1597","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1597","fail")

    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1597")
    functions.printDetailIds(detailids)

    unittest.main()

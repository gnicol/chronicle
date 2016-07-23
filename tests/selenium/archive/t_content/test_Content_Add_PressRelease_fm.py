from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_PressRelease_fm(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_pressrelease_fm(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[3]/input")
        sel.click("//div[@id='dijit_layout_ContentPane_0']/ul/li[3]/span/a/img")
        sel.wait_for_page_to_load("30000")
        sel.click("add-content-toolbar-button-form_label")
        sel.click("//div[@id='add-content-toolbar']/span[2]/input")
        sel.type("title", "Press Release Form Mode")
        sel.type("date", "")
        sel.click("//table[@id='date_popup']/tbody/tr[5]/td[4]/span")
        sel.type("location", "Alameda, CA")
        functions.waitForElementPresent(sel,"css=#dijitEditorBody")
        sel.type("css=#dijitEditorBody", "This is a Press Release done in Form Mode")
        sel.click("//div[@id='add-content-toolbar']/span[4]/input")
        functions.waitForElementPresent(sel,"dijit_form_DropDownButton_2_label")
        sel.click_at("dijit_form_DropDownButton_2_label", "")
        functions.waitForElementPresent(sel,"css=form.p4cms-ui > #workflow-sub-form > #fieldset-workflow > dl")
        functions.waitForElementPresent(sel,"add-content-toolbar-button-save_label")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"dijit_form_DropDownButton_2_label")
        functions.waitForTextPresent(sel,"Press Release Form Mode")
        try: 
           self.failUnless(sel.is_text_present("Press Release Form Mode"))
           functions.printResults("1581","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1581","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_content_Element_2"))
           functions.printResults("1581","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1581","fail")
        try: 
           self.failUnless(sel.is_text_present("Alameda, CA"))
           functions.printResults("1581","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1581","fail")
        try: 
           self.failUnless(sel.is_text_present("This is a Press Release done in Form Mode"))
           functions.printResults("1581","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1581","fail")
               
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1581")
    functions.printDetailIds(detailids)

    unittest.main()


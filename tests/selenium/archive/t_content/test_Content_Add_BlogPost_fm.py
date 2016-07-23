from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_BlogPost_fm(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_blogpost_fm(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[3]/input")
        sel.click("link=Blog Post")
        functions.waitForElementPresent(sel,"add-content-toolbar-button-form_label")
        sel.click("add-content-toolbar-button-form_label")
        functions.waitForElementPresent(sel,"//div[@id='add-content-toolbar']/span[2]/input")
        sel.click("//div[@id='add-content-toolbar']/span[2]/input")
        sel.type("title", "Chronicle Blog Post Form Mode")
        sel.type("date", "")
        sel.click("//table[@id='date_popup']/tbody/tr[5]/td[4]/span")
        sel.type("body", "This the Chronicle Blog Post Body")
        sel.type("author", "Moe")
        functions.waitForElementPresent(sel,"dijit_form_DropDownButton_2_label")
        sel.click_at("dijit_form_DropDownButton_2_label", "")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"dijit_form_DropDownButton_2_label")
        #sel.click("save_label")
        functions.waitForTextPresent(sel,"Chronicle Blog Post Form Mode")
        try: 
           self.failUnless(sel.is_text_present("Chronicle Blog Post Form Mode"))
           functions.printResults("1585","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1585","fail")
        try: 
           self.failUnless(sel.is_text_present("Moe"))
           functions.printResults("1585","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1585","fail")
        try: 
           self.failUnless(sel.is_text_present("This the Chronicle Blog Post Body"))
           functions.printResults("1585","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1585","fail")
           
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1585")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_Image_fm(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_image_fm(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("css=#dijit_layout_ContentPane_1 > ul.content-types > li > span.content-type-icon > a > img")
        functions.waitForElementPresent(sel,"add-content-toolbar-button-form_label")
        sel.click("add-content-toolbar-button-form_label")
        functions.waitForElementPresent(sel,"//div[@id='add-content-toolbar']/span[2]/input")
        sel.click("//div[@id='add-content-toolbar']/span[2]/input")
        sel.type("title", "Form Mode Image")
        sel.type("file", functions.getContentPath("perforce_site_logo.gif"))
        sel.click("date")
        sel.click("//table[@id='date_popup']/tbody/tr[5]/td[4]/span")
        sel.type("creator", "Moe")
        sel.type("description", "Perforce Site Logo")
        sel.type("alt", "image of Perforce site logo")
        sel.click("//div[@id='add-content-toolbar']/span[4]/input")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"//div[@id='add-content-toolbar']/span[4]/input")
        functions.waitForTextPresent(sel,"Form Mode Image")        
        try:
            self.failUnless(sel.is_text_present("Form Mode Image"))
            functions.printResults("1622","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1622","fail")
        try:
            self.failUnless(sel.is_text_present("perforce_site_logo.gif"))
            functions.printResults("1622","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1622","fail")
        functions.waitForTextPresent(sel,"Moe")        
        try:
            self.failUnless(sel.is_text_present("Moe"))
            functions.printResults("1622","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1622","fail")
        functions.waitForTextPresent(sel,"Perforce Site Logo")        
        try:
            self.failUnless(sel.is_text_present("Perforce Site Logo"))
            functions.printResults("1622","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1622","fail")
        functions.waitForTextPresent(sel,"image of Perforce site logo")        
        try:
            self.failUnless(sel.is_text_present("image of Perforce site logo"))
            functions.printResults("1622","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1622","fail")
            
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1622")
    functions.printDetailIds(detailids)

    unittest.main()

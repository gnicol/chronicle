from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_File(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_file(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("css=#dijit_layout_ContentPane_2 > ul.content-types > li > span.content-type-icon > a > img")
        functions.waitForElementPresent(sel,"title")
        sel.type("title", "Chronicle File")
        sel.type("file", functions.getContentPath("HelloChronicle.txt"))
        sel.type("description", "Chronicle File Description")
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"add-content-toolbar-button-save_label")
        #sel.click("save_label")
        functions.waitForTextPresent(sel,"Chronicle File")       
        try: 
           self.failUnless(sel.is_text_present("Chronicle File"))
           functions.printResults("1595","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1595","fail")
        functions.waitForTextPresent(sel,"HelloChronicle.txt")       
        try: 
           self.failUnless(sel.is_text_present("HelloChronicle.txt"))
           functions.printResults("1595","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1595","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1595")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Content_Add_Image(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_content_add_image(self):
        sel = self.selenium
        sel.open("/")
        sel.click("p4cms-site-toolbar-stack-controller_p4cms-site-toolbar-page-content-add_label")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller']/span[3]/input")
        functions.waitForElementPresent(sel,"css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("css=#dijit_layout_TabContainer_0_tablist_dijit_layout_ContentPane_2 > span.tabLabel")
        sel.click("css=#dijit_layout_ContentPane_1 > ul.content-types > li > span.content-type-icon > a > img")
        functions.waitForElementPresent(sel,"title")
        sel.type("title", "Perforce Site Logo IPM")
        sel.type("file", functions.getContentPath("perforce_site_logo.gif"))
        sel.click("add-content-toolbar-button-save_label")
        functions.waitForElementNotPresent(sel,"add-content-toolbar-button-save_label")
        functions.waitForTextPresent(sel,"Perforce Site Logo IPM")  
        try: 
           self.failUnless(sel.is_text_present("Perforce Site Logo IPM"))
           functions.printResults("1576","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1576","fail")
        functions.waitForTextPresent(sel,"perforce_site_logo.gif")   
        try: 
            self.failUnless(sel.is_text_present("perforce_site_logo.gif"))
            functions.printResults("1576","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("1576","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("1576")
    functions.printDetailIds(detailids)

    unittest.main()

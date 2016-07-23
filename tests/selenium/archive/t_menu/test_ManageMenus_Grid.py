from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageMenu_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_managemenu_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[2]/ul/li/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Label"))
           functions.printResults("6597","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6597","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6609","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6609","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Content Types"))
           functions.printResults("6597","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6597","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6610","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6610","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Actions"))
           functions.printResults("6597","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6597","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6608","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6608","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_0_label"))
           functions.printResults("6594","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6594","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_1_label"))
           functions.printResults("6595","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6595","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("6596","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6596","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6596,6597,6594,6595,6608,6609,6610")
    functions.printDetailIds(detailids)

    unittest.main()

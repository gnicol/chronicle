from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageWorkflows_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manage_workflows_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li[4]/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Label"))
           functions.printResults("6489","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6489","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("6480","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6480","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("6484","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6484","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>States"))
           functions.printResults("6489","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6489","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6486","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6486","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Content Types"))
           functions.printResults("6489","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6489","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6485","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6485","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Actions"))
           functions.printResults("6489","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6489","fail")   
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr3 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6479","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6479","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_0_label"))
           functions.printResults("6490","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6490","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_1_label"))
           functions.printResults("6491","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6491","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("6487","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6487","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6479,6480,6484,6485,6486,6487,6489,6490,6491")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageContentTypes_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manangecontenttype_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Icon"))
           functions.printResults("6579","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6579","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6607","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6607","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Label"))
           functions.printResults("6579","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6579","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("6599","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6599","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("6598","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6598","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Group"))
           functions.printResults("6579","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6579","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("6600","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6600","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("6601","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6601","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Description"))
           functions.printResults("6579","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6579","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr3 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("6602","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6602","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr3 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("6603","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6603","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Actions"))
           functions.printResults("6579","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6579","fail")     
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_0_label"))
           functions.printResults("260","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("260","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_1_label"))
           functions.printResults("262","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("262","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("261","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("261","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("260,261,262,6579,6598,6599,6600,6601,6602,6603,6607")
    functions.printDetailIds(detailids)

    unittest.main()

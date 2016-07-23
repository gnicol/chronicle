from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageContent_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manangecontent_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Type"))
           functions.printResults("6481","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6481","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("43","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("43","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("44","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("44","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Title"))
           functions.printResults("6481","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6481","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("40","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("40","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("42","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("42","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Modified"))
           functions.printResults("6481","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6481","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("39","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("39","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("6133","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6133","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Workflows"))
           functions.printResults("6481","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6481","fail")    
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr3 > div.dojoxGridArrowButtonNode"))
           functions.printResults("6606","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6606","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Actions"))
           functions.printResults("6481","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6481","fail")    
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr4 > div.dojoxGridArrowButtonNode"))
           functions.printResults("38","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("38","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_0_label"))
           functions.printResults("6482","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6482","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("6483","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6483","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("38,39,40,42,43,44,6133,6481,6482,6483,6606")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageRoles_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manageroles_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[3]/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Name"))
           functions.printResults("703","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("703","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("721","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("721","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("722","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("722","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Type"))
           functions.printResults("703","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("703","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("723","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("723","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("724","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("724","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>User"))
           functions.printResults("703","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("703","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridArrowButtonNode"))
           functions.printResults("719","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("719","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Actions"))
           functions.printResults("703","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("703","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr3 > div.dojoxGridArrowButtonNode"))
           functions.printResults("719","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("719","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_0_label"))
           functions.printResults("6605","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6605","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("6605","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6605","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6605,703,719,721,722,723,724,725")
    functions.printDetailIds(detailids)

    unittest.main()

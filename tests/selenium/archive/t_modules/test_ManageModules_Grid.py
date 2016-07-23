from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageModules_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_managemodules_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[2]/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Module"))
           functions.printResults("6611","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6611","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("2292","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2292","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("2293","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2293","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Maintained By"))
           functions.printResults("6611","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6611","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("2290","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2290","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("2291","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2291","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Status"))
           functions.printResults("6611","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6611","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("2294","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2294","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("2295","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2295","fail")
        try: 
           self.failIf(sel.is_element_present("css=div class=dojoxGridScrollbox > wairole=presentation > dojoattachpoint=scrollboxNode > role=presentation > style=height: 241px"))
           functions.printResults("2329","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2329","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("2289","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("2289","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("2289,2290,2291,2292,2293,2294,2295,2329,6611")
    functions.printDetailIds(detailids)

    unittest.main()

from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageThemes_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_managethemes_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[2]/ul/li[4]/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Theme"))
           functions.printResults("6614","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6614","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("6615","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6615","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("6616","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6616","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Maintained By"))
           functions.printResults("6614","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6614","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("6617","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6617","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("6618","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6618","fail")
        try: 
           self.failIf(sel.is_text_present("Current Themes"))
           functions.printResults("6620","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6620","fail")
        try: 
           self.failUnless(sel.is_text_present("Available Themes"))
           functions.printResults("6614","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6614","fail")
        try: 
           self.failIf(sel.is_element_present("css=div class=dojoxGridScrollbox > wairole=presentation > dojoattachpoint=scrollboxNode > role=presentation > style=height: 241px"))
           functions.printResults("6612","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6612","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("6613","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6613","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6612,6613,6614,6615,6616,6617,6618,6620")
    functions.printDetailIds(detailids)

    unittest.main()

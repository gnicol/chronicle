from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ManageUsers_Grid(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_manangeusers_grid(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[3]/ul/li/a/span")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Username"))
           functions.printResults("1175","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1175","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("1222","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1222","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr0 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("1223","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1223","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Fullname"))
           functions.printResults("1175","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1175","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("1219","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1219","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr1 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("1220","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1220","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Email Address"))
           functions.printResults("1175","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1175","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortUp"))
           functions.printResults("1217","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1217","fail")
        try: 
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr2 > div.dojoxGridSortNode>dojoxGridSortDown"))
           functions.printResults("1218","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1218","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Roles"))
           functions.printResults("1175","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1175","fail")
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr3 > div.dojoxGridArrowButtonNode"))
           functions.printResults("1221","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1221","fail")
        try: 
           self.failIf(sel.is_element_present("css=div.dojoxGridColCaption>Actions"))
           functions.printResults("1175","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1175","fail")    
        try:
           self.failIf(sel.is_element_present("css=#p4cms_ui_grid_DataGrid_0Hdr4 > div.dojoxGridArrowButtonNode"))
           functions.printResults("1215","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1215","fail")
        try: 
           self.failUnless(sel.is_element_present("id=dijit_form_Button_0_label"))
           functions.printResults("1202","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1202","fail")
        try: 
           self.failUnless(sel.is_element_present("id=p4cms_ui_grid_Footer_0"))
           functions.printResults("1203","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1203","fail")
 
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("1175,1202,1203,1215,1217,1218,1219,1220,1221,1222,1223")
    functions.printDetailIds(detailids)

    unittest.main()

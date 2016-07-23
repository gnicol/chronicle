from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ContentTypes_Group_Assets(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_contenttypes_group_assets(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=group-groups-Assets")
        functions.waitForTextPresent(sel,"File")
        try: 
           self.failUnless(sel.is_text_present("File"))
           functions.printResults("1725","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1725","fail")
        try: 
           self.failUnless(sel.is_text_present("Image"))
           functions.printResults("1725","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1725","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("1725")
    functions.printDetailIds(detailids)

    unittest.main()

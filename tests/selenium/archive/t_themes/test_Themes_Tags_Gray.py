from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Themes_Tags_Gray(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_themes_tags_gray(self):
        sel = self.selenium
        sel.open("/")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0']/span")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[2]/ul/li[4]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=tagFilter-display-gray")
        functions.waitForTextPresent(sel,"960 Gray")
        try: 
           self.failUnless(sel.is_text_present("960 Gray"))
           functions.printResults("6554","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6554","fail")
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6554")
    functions.printDetailIds(detailids)

    unittest.main()

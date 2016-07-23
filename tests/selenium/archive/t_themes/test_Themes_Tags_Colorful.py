from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Themes_Tags_Colorful(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_themes_tags_colorful(self):
        sel = self.selenium
        sel.open("/")
        sel.click("//span[@id='p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0']/span")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[2]/ul/li[4]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=tagFilter-display-colorful")
        functions.waitForTextPresent(sel,"Graphical")
        try: 
           self.failUnless(sel.is_text_present("Graphical"))
           functions.printResults("6552","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6552","fail")
        try: 
           self.failUnless(sel.is_text_present("Orange"))
           functions.printResults("6552","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6552","fail")
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6552")
    functions.printDetailIds(detailids)

    unittest.main()

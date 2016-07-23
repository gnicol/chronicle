from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_ContentTypes_Group_Uncheck(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_contenttypes_group_uncheck(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li/ul/li[2]/a/span")
        sel.wait_for_page_to_load("30000")
        functions.waitForTextPresent(sel,"Basic Page")
        try: 
           self.failUnless(sel.is_text_present("Basic Page"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("Blog Post"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("File"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("Image"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("Microsoft Excel"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("Microsoft PowerPoint"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("Microsoft Word"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("PDF"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
        try: 
           self.failUnless(sel.is_text_present("Press Release"))
           functions.printResults("6644","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6644","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("6644")
    functions.printDetailIds(detailids)

    unittest.main()

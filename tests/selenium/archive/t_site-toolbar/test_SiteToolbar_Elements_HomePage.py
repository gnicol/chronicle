from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_SiteToolbar_Elements_HomePage(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()
    
    def test_sitetoolbar_elements_homepage(self):
        sel = self.selenium
        sel.open("/")
        try: 
            self.failUnless(sel.is_element_present("link=Home"))
            functions.printResults("6456","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6456","fail")
        try: 
            self.failUnless(sel.is_element_present("link=Search"))
            functions.printResults("6457","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6457","fail")
        try:
            self.failUnless(sel.is_element_present("link=Logout"))
            functions.printResults("6458","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6458","fail")
        try: 
            self.failUnless(sel.is_element_present("css=a.home"))
            functions.printResults("6459","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6459","fail")
        try: 
            self.failUnless(sel.is_element_present("css=div.footer-left > ul.navigation > li:nth(1) > a.p4cms-user-logout.user-logout"))
            functions.printResults("6460","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6460","fail")
        try:
            self.failUnless(sel.is_element_present("css=div.footer-right > p"))
            functions.printResults("6135","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6135","fail")
    
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
    
    detailids = ("6456,6457,6458,6459,6460,6135")
    functions.printDetailIds(detailids)
        
    unittest.main()

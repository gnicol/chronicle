from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Logout(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_Logout(self):
        sel = self.selenium
        sel.open("/")
        functions.waitForElementPresent(sel,"link=Logout")
        sel.click("link=Logout")
        functions.waitForElementPresent(sel,"link=Login")

        try:
            self.failUnless(sel.is_element_present("link=Login"))
            functions.printResults("6047","pass")
        except AssertionError, e:
            self.verificationErrors.append(str(e))
            functions.printResults("6047","fail")
            
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]

    detailids = ("6047")
    functions.printDetailIds(detailids)
  
    unittest.main()
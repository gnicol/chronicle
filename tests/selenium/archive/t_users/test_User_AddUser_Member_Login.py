from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_User_AddUser_Member_Login(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_user_adduser_member_login(self):
        sel = self.selenium
        sel.open("/")
        sel.click("link=Login")
        functions.waitForElementPresent(sel,"id=partial-addNewUser_label")
        sel.click("id=partial-addNewUser_label")
        functions.waitForElementPresent(sel,"id=id")       
        sel.type("id=id", "larry")
        sel.type("id=email", "larry@chronicle.com")
        sel.type("id=fullName", "larry leisure")
        sel.type("id=password", "p4cms123")
        sel.type("id=passwordConfirm", "p4cms123")
        sel.click("name=save")
        sel.click("id=save_label")
        sel.wait_for_page_to_load("30000")
        try: 
           self.failIf(sel.is_text_present("Add User"))
           functions.printResults("1403","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1403","fail")
        try: 
           self.failIf(sel.is_element_present("id=id"))
           functions.printResults("6093","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("6093","fail")
        try: 
           self.failIf(sel.is_element_present("id=email"))
           functions.printResults("1406","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1406","fail")
        try: 
           self.failIf(sel.is_element_present("id=fullName"))
           functions.printResults("1407","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1407","fail")
        try: 
           self.failIf(sel.is_element_present("id=password"))
           functions.printResults("1408","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1408","fail")
        try: 
           self.failIf(sel.is_element_present("css=message > User 'larry' has been successfuly added."))
           functions.printResults("1404","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1404","fail")
        try: 
           self.failIf(sel.is_element_present("css=message > You have been logged in as 'larry'."))
           functions.printResults("1405","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1405","fail")
        
        
    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("1403,1404,1405,1406,1407,1408,6093")
    functions.printDetailIds(detailids)

    unittest.main()

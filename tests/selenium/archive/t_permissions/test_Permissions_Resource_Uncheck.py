from selenium import selenium
import unittest, time, re, sys, getopt
sys.path.append("shared")
import functions

class test_Permissions_Resources_Uncheck(unittest.TestCase):
    def setUp(self):
        self.verificationErrors = []
        self.selenium = selenium(connection["host"], connection["port"], connection["browser"], connection["url"])
        self.selenium.start()

    def test_permissions_resources_uncheck(self):
        sel = self.selenium
        sel.open("/")
        sel.click("id=p4cms-site-toolbar-stack-controller_p4cms_ui_toolbar_ContentPane_0_label")
        sel.click("css=input.dijitOffScreen")
        sel.click("//div[@id='p4cms_ui_toolbar_ContentPane_0']/div/ul/li[3]/ul/li[3]/a/span")
        sel.wait_for_page_to_load("30000")
        sel.click("id=resource-resources-categories")
        functions.waitForElementPresent(sel,"css=span.resourceLabel > Categories")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Categories"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access Categories"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Add Categories"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Associate Categories"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Categories"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access Content History"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access Content Management"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access Unpublished Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Add Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Delete Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Delete Own Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Edit Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Edit Own Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Content Types"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Publish Content"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Menus"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Add Menu Items"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Menus"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Search"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access Search"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Search"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Site"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access Toolbar"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Add Sites"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Modules"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Themes"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > System Information"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Access System Information"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Users"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Add Users"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Permissions"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Roles"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Users"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Widgets"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Widgets"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.resourceLabel > Workflows"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")
        try: 
           self.failIf(sel.is_element_present("css=span.privilegeLabel > Manage Workflows"))
           functions.printResults("1551","pass")
        except AssertionError, e:
           self.verificationErrors.append(str(e))
           functions.printResults("1551","fail")

    def tearDown(self):
        self.selenium.stop()
        self.assertEqual([], self.verificationErrors)

if __name__ == "__main__":

    connection = functions.connect(sys.argv)
    del sys.argv[1:]
   
    detailids = ("1551")
    functions.printDetailIds(detailids)

    unittest.main()

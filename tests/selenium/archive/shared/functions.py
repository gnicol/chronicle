# Functions for Selenium tests.

from selenium import selenium
import time, sys, getopt, os

def connect(opts):
    print "Test: %s" % opts[0]
    opt, arg = getopt.getopt(opts[1:], 'b:p:u:h:')
    opts = {}
    connection = {}
    for k,v in opt:
        opts[k] = v
    for key in opts.keys():
      if key not in ("-b","-p","-u","-h"):
        usage()
        sys.exit(0)

    if '-b' in opts.keys():
       connection["browser"] = opts["-b"]
    if '-p' in opts.keys():
       connection["port"] = opts["-p"]
    if '-u' in opts.keys():
       connection["url"] = opts["-u"]
    if '-h' in opts.keys():
       connection["host"] = opts["-h"]
    return connection

def usage():
    print "test -h -p -u -b"

def printResults(detailId,result):
  print "Result:%s-%s" % (detailId,result)
  
def printDetailIds(detailIds):
    print "DetailIds:%s" % detailIds
    
def getContentPath(file):
    contentPath = "%s/testdata/%s" % (os.getcwd(),file)
    return contentPath
    
def waitForElementPresent(sel,element):
    for i in range(60):
        try:
            if sel.is_element_present(element): break
        except: pass
        time.sleep(1)
        
def waitForTextPresent(sel,text):
    for i in range(60):
        try:
            if sel.is_text_present(text): break
        except: pass
        time.sleep(1)

def waitForElementNotPresent(sel,element):
    for i in range(60):
        try:
            if not sel.is_element_present(element): break
        except: pass
        time.sleep(1)
    else: self.fail("time out")
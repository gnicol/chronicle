#!/usr/local/bin/python2.7

# Script used to run automated P4CMS test using Selenium.

import os
from subprocess import Popen, PIPE, STDOUT
import string, urllib, re, getopt, sys, datetime, signal, time

resultList = {}

def getTestEnvironment(options):
  testEnv = {}
  try:
    url = urllib.urlopen("%s" % options["URL"])
    htmlSource = url.read()
    url.close()
  except:
    print "Error: Can't open url."
    sys.exit(1)
          
  for line in htmlSource.split("\n"):
    if line.find("&copy;") > -1:
      m = re.match('.*- (.*)/(\d+)',line)
      if m:
        options["CODELINE"] = m.group(1)
        options["CLIENTCHANGE"] = m.group(2)
        options["SERVERCHANGE"] = m.group(2)
  if not options.has_key("CODELINE") or not options.has_key("CLIENTCHANGE"):
    print htmlSource 
    sys.exit(1)
  return options

def runCmd(cmd,opts=None):
  if opts <> None:
    cmd = cmd + " " + opts
  print cmd
  p = Popen(cmd, shell=True, stdin=PIPE, stdout=PIPE, stderr=STDOUT, close_fds=True)
  return p

def useConf(options, file):
  # Read config file.

  testList = []
  f = open(file)
  optionList = ["BROWSERS","INSERTRESULTS","SELENIUMPORT","SELENIUMHOST","CLIENTOS","SERVEROS","URL","REFRESHP4CHRONICLE"]
  for line in f.readlines():
    if line.find("#") == 1: continue
    opt = string.split(line,"=")
    if opt[0] in optionList:
      options["%s" % opt[0]] = string.strip(opt[1])
    if line.find("#") <> 1 and opt[0] not in optionList and line.find("TESTS") == -1 and len(line) > 1:
      testList.append(string.strip(line))

  options["TESTS"] = testList
    
  for option in optionList:
    if not options.has_key("%s" % option):
      print "%s not in config file" % option
      sys.exit(1)
      
  return options
      
def setUp(options):

  # Refresh the P4CMS Instance.
  if options["REFRESHP4CHRONICLE"] in ("Y","y"):
    cmd = "%s/scripts/getp4chronicle-linux.sh" % options["TESTDIR"]
    p = runCmd(cmd)
    print p.stdout.read()
   
  # Start the Selenium Server.
  cmd = "java -jar %s/scripts/selenium-server.jar -browserSessionReuse &" % options["TESTDIR"]
  p = runCmd(cmd)
  time.sleep(30)


def runTests(options):
  options = getTestEnvironment(options)

  # Create the results file.
  d = datetime.datetime.now()
  today = "%s-%s-%s" % (d.year,d.month,d.day)
  resultdir = "%s/results/%s/%s/%s/%s" % \
               (options["TESTDIR"],today,options["SERVEROS"],options["CLIENTOS"],options["BROWSER"])
  cmd = "mkdir -p %s" % resultdir
  p = runCmd(cmd)
  print p.stdout.read()

  # Run the tests.
  for test in options["TESTS"]:
    print "Running test %s" % test
    file = string.split(test,"/")
    index = len(file) - 1
    file = file[index]
    file = string.rstrip(file,".py") 
    resultFile = open("%s/%s" % (resultdir,file),"w")
    for key in options.keys():
      if key == "TESTS":  continue
      resultFile.write("%s: %s\n" % (key,options[key]))
    cmd = "python %s/%s" % (options["TESTDIR"], string.strip(test))
    opts = "-u %s -h %s -b *%s -p %s" % (options["URL"], options["SELENIUMHOST"], options["BROWSER"], options["SELENIUMPORT"])
    p = runCmd(cmd, opts)
    results = p.stdout.read()
    resultFile.write(results)
    resultFile.close()
    insertResults(results, options, options["BROWSER"])

def insertResults(results, options, browser):
  detailsList = []
  details = {}
  for line in results.split("\n"):
    if string.split(line,":")[0] == "DetailIds":
      detailsList = string.split(line,":")[1]
      for d in string.split(detailsList,","):
        details[d] = ""
  for line in results.split("\n"):
    if string.split(line,":")[0] == "Result":
        r = string.split(string.split(line,":")[1],"-")
        if details[r[0]] in ("pass",""):  details[r[0]] = r[1]
  for key in details.keys():
    if details[key] == "":  result = "fail"
    else:  result = details[key]
    resultList[key] = result
    if options["INSERTRESULTS"] in ("Y","y"):    
      cmd = "python %s/scripts/quartresults.py" % options["TESTDIR"]
      opts = "-d %s -r %s --clientos=%s --clientchange=%s --serveros=%s --serverchange=%s -u %s -c %s" % (key, result, options["CLIENTOS"], options["CLIENTCHANGE"], options["SERVEROS"], options["SERVERCHANGE"], browser, options["CODELINE"])
      p = runCmd(cmd, opts)
      print p.stdout.read()

def cleanUp(options):
  processList = ["java","firefox","Chrome","chrome","Safari"]
  for process in processList:
    cmd = "ps -ax | grep %s" % process
    p = runCmd(cmd)
    for line in p.stdout.readlines():
      if line.find(".py") < 1:
        try:  os.kill(int(string.split(line)[0]), signal.SIGKILL)
        except: pass

def usage():
  print """
  QA P4CMS Test Runner.
  Script to run automated P4CMS tests and insert results into QUART.
  Usage: python qatestrunner.py  -c configfile | -p <seleniumport> -b <browser> -u <url> --clientos=<clientos> --serveros=<serveros> -u <url> -q -h
  Example:
          Run suites using a config file.
            python qatestrunner.py -c sample
          Run a single test and exit.
            python qatestrunner.py -b firefox -u http://qa-lin-vm51.qa.perforce.com --clientos=LINUX26X86 --serveros=LINUX26X86 -p 4444 -s localhost -t t_login/test_Login_P4CMS.py,t_login/test_Logout.py
  
  -h Help
  -c <config file>
  -b <browser> (Default=firefox)
               firefox
               chrome
               iexplore
               safari
               opera
  --clientos=clientos (LINUX26X86)
  --serveros=serveros (LINUX26X86)
  -p <selenium port> (Default=4444)
  -q Insert results into QUART (Default=N)
  -r Refresh P4CMS Instance (Default=N Currently only works on qa-lin-vm51.qa.perforce.com)
  -s <selenium host> (Default=localhost)
  -t <tests>  
  -u <url>
  """
  
def main():

  options = {}
  options["TESTDIR"] = os.getcwd()
  opt, arg = getopt.getopt(sys.argv[1:], 'c:b:o:p:s:t:u:hqr')
  opts = {}
  for k,v in opt:
    opts[k] = v
  if opts.has_key('-h'):
    usage(); sys.exit(0)
  if opts.has_key('-c'):
    options = useConf(options, "%s/config/%s" % (options["TESTDIR"],opts["-c"]))
  else:
    if '-b' in opts.keys():
       options["BROWSERS"] = opts['-b']
    else:
      options["BROWSERS"] = 'firefox'
    if '--clientos' in opts.keys():
       options["CLIENTOS"] = opts['--clientos']
    else:
      options["CLIENTOS"] = 'LINUX26X86'
    if '--serveros' in opts.keys():
       options["SERVEROS"] = opts['--serveros']
    else:
      options["SERVEROS"] = 'LINUX26X86'
    if '-p' in opts.keys():
       options["SELENIUMPORT"] = opts['-p']
    else:
      options["SELENIUMPORT"] = 4444
    if '-q' in opts.keys():
      options["INSERTRESULTS"] = opts['-q']
    else:
      options["INSERTRESULTS"] = 'N'
    if '-r' in opts.keys():
      options["REFRESHP4CHRONICLE"] = opts['-r']
    else:
      options["REFRESHP4CHRONICLE"] = 'N'
    if '-s' in opts.keys():
       options["SELENIUMHOST"] = opts['-s']
    else:
      options["SELENIUMHOST"] = 'localhost'
    if '-t' in opts.keys():
      options["TESTS"] = string.split(opts['-t'],",")
    else:
      options["TESTS"] = ''
    if '-u' in opts.keys():
       options["URL"] = opts['-u']
    else:
      options["URL"] = 'http://qa-lin-vm51.qa.perforce.com'

  browsers = []
  browsers = string.split(options["BROWSERS"],",")
  for browser in browsers:
    options["BROWSER"] = browser
    cleanUp(options)
    setUp(options)
    runTests(options)
    cleanUp(options)

  print "-----"
  print "Results"
  print "Tests run: %s" % len(resultList.keys())
  passed = 0 
  failed = 0
  for key in resultList.keys():
    if resultList[key] == "pass":
      passed = passed + 1
    else:
      failed = failed + 1
  print "Tests passed: %s" % passed
  print "Tests failed: %s" % failed
    
if __name__ == "__main__":
  main()

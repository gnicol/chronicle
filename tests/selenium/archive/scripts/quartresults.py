#!/usr/local/bin/python2.7

# Script used to insert results into QUART.

import os
os.environ['PYTHON_EGG_CACHE'] = '/tmp'

from subprocess import Popen, PIPE, STDOUT
import string, urllib, re, getopt, sys, datetime
import MySQLdb

def runsql(sql, action=None):
  lastrowid = ""
  results = ""
  try:
    conn = MySQLdb.connect (host = "quart.perforce.com",
                            user = "quartuser",
                            port = 3307,
                            passwd = "p4qa",
                            db = "quart")

  except MySQLdb.Error, e:
    print "Error %d: %s" % (e.args[0], e.args[1])
    sys.exit (1)
  if action == None:
    cursor = conn.cursor(MySQLdb.cursors.DictCursor)
    cursor.execute(sql)
    results = cursor.fetchall()
  elif action == "insert":
    cursor = conn.cursor()
    cursor.execute("""%s""" % sql)
    results = cursor.rowcount
    lastrowid = int(cursor.lastrowid)
    cursor.execute("COMMIT");
    cursor.close()
  conn.close()
  if action == "insert":  return results, lastrowid
  else: return results


def insertResults(resultData):
  # Fields to be inserted into QUART.
  # detailid - Detailid from test.
  # resultid - From test output (1 pass 2 fail)
  # date - Default current date
  # releasestring - Url main page
  # clientosid - Input as script arg
  # clientchange - Input as script arg
  # serverosid - Input as script arg
  # serverchange - Input as script arg
  # uiid - Input as script arg
  # codelineid - Url main page
  # userid - Default (id=2 'automated')
  # testtypeid - Default (id=16 'Automated')

  optionFields=["detailid","result","clientchange","clientos","ui","codeline"]
  for field in optionFields:
    if field not in resultData.keys():
      print "Missing %s" % field
      sys.exit(1)

  print "Inserting results..."

  # Set default Automated values for user,testtype,date 
  resultData["user"] = "automated"
  resultData["testtype"] = "automated"  
  resultData["date"] = datetime.datetime.now()
  resultData["productid"] = "1" # (p4cms)

  getResultIdFields = ["result","clientos","serveros","ui","codeline","user","testtype"]
  resultFields = ["detailid","date","clientchange","serverchange","productid"]

  fields = ""
  values = ""

  # Get the field ids from the database.
  for field in getResultIdFields:
    if resultData[field] == 0: continue
    if field in ("clientos","serveros"):
      sql = "select id from qos where name='%s';" % (resultData[field])
    else:
      sql = "select id from q%s where name='%s';" % (field,resultData[field])
    results = runsql(sql)

    # If the clientos, serveros, ui, or codeline does not exist, create new ones.
    if len(results) == 0:
      if field in ("clientos","serveros","codeline","ui"):
        if field in ("clientos","serveros"):
          sql = "insert into qos (name) values ('%s');" % (resultData[field])
        else:
          sql = "insert into q%s (name) values ('%s');" % (field,resultData[field])
        result = runsql(sql,"insert")
        if field in ("clientos","serveros"):
          sql = "select id from qos where name='%s';" % (resultData[field])
        else:
          sql = "select id from q%s where name='%s';" % (field,resultData[field])
        results = runsql(sql)
    fields = fields + ",%sid" % field
    values = values + ",'%s'" % results[0]["id"]

  for field in resultFields:
    if resultData[field] == 0: continue
    fields = fields + "," + field
    values = values + ",'%s'" % resultData[field]

  fields = string.strip(fields,",")
  values = string.strip(values,",")

  sql = "insert into qtestresults (%s) values (%s);" % (fields,values)
  results,lastrowid = runsql(sql,"insert")
  if results > 0:
    print "Inserted result %s for Detail %s" % (resultData["result"],resultData["detailid"])
  else:
    print "No result inserted for Detail %s" % resultData["detailid"]

def usage():

  print """
  quartresults.py -d detailid -r result -u ui -c codeline --clientos=clientchange --clientchange=clientchange [--serveros=clientos --serverchange=clientchange]
  
  -d detailid (QUART detailid)
  -r result (pass,fail)
  -u ui (Command Line, Firefox)
  
  Parse the following from the versionstring.
  -c codeline (2011.1.beta,2011.1.main)
  --clientos=clientos (OS version LINUX26X86)
  --clientchange=clientchange (Change number 399210)
  --serveros=serveros (OS version LINUX26X86)
  --serverchange=serverchange (Change number 399210)
  """

def main():
  options = {}

  opt, arg = getopt.getopt(sys.argv[1:], "c:h:d:r:u:",["clientos=","clientchange=","serveros=","serverchange="])
  opts = {}
  for k,v in opt:
    opts[k] = v
  if '-c' in opts.keys():
    options["codeline"] = opts['-c']
  else:
    usage(); sys.exit(0)
  if '-d' in opts.keys():
    options["detailid"] = opts['-d']
  else:
    usage(); sys.exit(0)
  if '-r' in opts.keys():
    options["result"] = opts['-r']
  else:
    usage(); sys.exit(0)
  if '-u' in opts.keys():
    options["ui"] = opts['-u']
  else:
    usage(); sys.exit(0)
  if '--clientos' in opts.keys():
    options["clientos"] = opts['--clientos']
  else:
    usage(); sys.exit(0)
  if '--clientchange' in opts.keys():
    options["clientchange"] = opts['--clientchange']
  else:
    usage(); sys.exit(0)
  if '--serveros' in opts.keys():
    options["serveros"] = opts['--serveros']
  else:
    options["serveros"] = 0
  if '--serverchange' in opts.keys():
    options["serverchange"] = opts['--serverchange']
  else:
    options["serverchange"] = 0
  insertResults(options)

if __name__ == "__main__":
  main()

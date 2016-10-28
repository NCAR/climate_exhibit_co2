#!/usr/bin/env python
import calendar, datetime, MySQLdb, os, urllib2

from sys import path
if os.name == 'nt':
    path.append('J:\\Sharon\\db\\credentials\\')
else:
    path.append('/home/sclark/db/credentials/')
import db_config

file = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_NWR_most_recent.lme"; 
sitecode = "nwr"
# open external file
f = urllib2.urlopen(file)

myfile = f.read()
f.close()
lines = myfile.split("\n")

maxlines = 6720
#lastlines = lines[-maxlines:]

# open local file for writing
f2 = open("nwrdb.txt","w");
# Open database connection
# TODO: store credentials in external flat file
db = MySQLdb.connect(config.host,config.username,config.password,config.database )

# prepare a cursor object using cursor() method
cursor = db.cursor()

# only do counter if doing full set of data
counter = 0
lastlines = lines
for p in lastlines:
    if (counter > 29):
        parts = p.split()
        ary = []
        if (len(parts)> 0):
            year = parts[1]
            month = parts[2].zfill(2)
            day = parts[3].zfill(2)
            hour = parts[4].zfill(2)
            minute = parts[5].zfill(2)
            sec = parts[6].zfill(2)
            date_text = year+'-'+month+'-'+day+'T'+hour+':'+minute+':'+sec;
            date = datetime.datetime.strptime(date_text, "%Y-%m-%dT%H:%M:%S")
            timestamp = calendar.timegm(date.utctimetuple())
            co2_value = parts[8]
            f2.write('%i\t%s\n' %(timestamp, co2_value))

            # see if already exists in db
            sql = "SELECT * FROM climate_co2_data2 WHERE sitecode='%s' AND timestamp_co2_recorded='%i'" % (sitecode , timestamp)
            # Execute the SQL command
            cursor.execute(sql)
            numrows = cursor.rowcount
            if (numrows == 0):
                sql = "INSERT INTO climate_co2_data2(sitecode, co2_value, timestamp_co2_recorded) VALUES('%s','%s','%i')" % (sitecode, co2_value, timestamp)
                try:
                    # Execute the SQL command
                    cursor.execute(sql)
                    # Commit your changes in the database
                    db.commit()
                    print "Added %s - %s for %s to db.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), co2_value, sitecode)
                except:
                    # Rollback in case there is any error
                    db.rollback()
                    print "Could not commit %s - %s for %s to db.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), co2_value, sitecode)
            else:
                print "Value already exists on %s for %s.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode)
                
                #check if values are different. If so, update
                # if more than 1 row - report as error
                if (numrows > 1):
                    print "More than 1 value exists in the db for %s and %s - you should investigate." % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode)
                else:
                    # update the value
                    data = cursor.fetchone()
                    if (str(data[2]) != str(co2_value)):
                        print data[2]
                        print co2_value
                        sql = "UPDATE climate_co2_data2 SET co2_value='%s' WHERE sitecode='%s' AND timestamp_co2_recorded='%i')" % (co2_value, sitecode, timestamp)
                        try:
                            # Execute the SQL command
                            cursor.execute(sql)
                            # Commit your changes in the database
                            db.commit()
                            print "Updated %s - %s from %s for %s to db.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), co2_value, data[2], sitecode)
                        except:
                            # Rollback in case there is any error
                            db.rollback()
                            print "Could not update %s - %s from %s for %s to db.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), co2_value, data[2], sitecode)
                            
    counter = counter+1
# disconnect from server
db.close()
f2.close();
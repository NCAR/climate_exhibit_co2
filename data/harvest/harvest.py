#!/usr/bin/env python
import calendar, datetime, getopt, MySQLdb, os, sys, urllib2

from sys import path
if os.name == 'nt':
    path.append('J:\\Sharon\\db\\credentials\\')
else:
    path.append('/home/sclark/db/credentials/')
import db_config

argv = sys.argv[1:] 
totalargs = len(sys.argv)
print 'Started at: %s' % datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

# Open database connection
db = MySQLdb.connect(db_config.host,db_config.username,db_config.password,db_config.database )
# prepare a cursor object using cursor() method
cursor = db.cursor()
        
# function to print usage
def usage():
    print 'harvest.py -s <sitecode> -r <daterange>'
    print '<sitecode> can be one of nwr|mlb'
    print '<daterange> can be one an integer indicating the ' 
    print 'number of rows from the end of the file to process'
    print 'No value for <daterange> defaults to all data'

# ~480 per day
# ~3360 per week
# ~ 175200 per year

# funtion to retrieve list of data
def retrieve_data(filename):
    # open external file to retrieve data
    f = urllib2.urlopen(filename)
    myfile = f.read()
    f.close()
    # split on new lines
    return myfile.split("\n")
                    
#main 
def main():   
    #retrieve arguments
    daterange = 0
    filename = ''
    sitecode = ''
    try:
        opts, args = getopt.getopt(argv, "hs:r:", ["sitecode=", "daterange="])
    except getopt.GetoptError:          
        usage()                      
        sys.exit(2)  
    for opt, arg in opts:                
        if opt in ("-h", "--help"): 
            usage()
            sys.exit()                  
        elif opt in ("-s", "--sitecode"):              
            sitecode = str(arg)                 
        elif opt in ("-r", "--daterange"): 
            daterange = int(arg)
            # ~480 per day
            # ~3360 per week
            # ~ 175200 per year

    # only proceed if there are arguments        
    if (totalargs > 0):
        if(sitecode == 'nwr'):
            sitecode = "nwr"
            filename = "http://www.eol.ucar.edu/homes/stephens/RACCOON/NCAR_NWR_most_recent.lme"; 
        elif (sitecode == 'mlb'):
            sitecode = "mlb"
            filename = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_MLB_most_recent.lme"; 
        else:
            usage()
            sys.exit(2)
        lines = retrieve_data(filename)

        # NOTE: only do counter if re-processing full set of data
        # otherwise maxlines based on command input daterange
        if(daterange == ''):
            counter = 0
            lastlines = lines
        else:
            maxlines = daterange
            lastlines = lines[-maxlines:]

        for line in lastlines:
            # if doing full range of data, ignore the first 29 lines which are just text file comments
            message_failure = ''
            message_success = ''
            if (daterange != '' or counter > 29):
                
                parts = line.split()

                if (len(parts) > 0):
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

                    # see if already exists in db
                    sql = "SELECT * FROM climate_co2_data2 WHERE sitecode='%s' AND timestamp_co2_recorded='%i'" % (sitecode , timestamp)
                    # Execute the SQL command
                    cursor.execute(sql)
                    # ensure no results before insert
                    numrows = cursor.rowcount
                    time_formatted = datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S')
                    if (numrows == 0):
                        #db_transaction('insert', sitecode, str(co2_value), timestamp, 0)
                        sql = "INSERT INTO climate_co2_data2(sitecode, co2_value, timestamp_co2_recorded) VALUES('%s','%s','%i')" % (sitecode, co2_value, timestamp)
                        message_success = "Added %s - %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode)
                        message_failure = "Could not commit %s - %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode)      
                    else:
                        print "Value already exists on %s for %s.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode)
                        # if more than 1 row - report as error
                        if (numrows > 1):
                            print "More than 1 value exists in the db for %s and %s - you should investigate." % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode)
                        else:
                            #check if values are different. If so, update
                            data = cursor.fetchone()
                            if (str(data[2]) != str(co2_value)):
                                #db_transaction('update', sitecode, str(co2_value), timestamp, str(data[2]))
                                sql = "UPDATE climate_co2_data2 SET co2_value='%s' WHERE sitecode='%s' AND timestamp_co2_recorded='%i'" % (co2_value, sitecode, timestamp)
                                message_success = "Updated %s - %s from %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode, data[2])
                                message_failure = "Could not update %s - %s from %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode, data[2])
                                                                                                          
                    try:
                        # Execute the SQL command
                        cursor.execute(sql)
                        # Commit your changes in the database
                        db.commit()
                        print message_success
                    except:
                        # Rollback in case there is any error
                        db.rollback()
                        print message_failure

            # NOTE: only do counter if re-processing full set of data
            if(daterange == ''):                        
                counter = counter+1
        # disconnect from server
        db.close()
        
main()

print 'Ended at: %s' % datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
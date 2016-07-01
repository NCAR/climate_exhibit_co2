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
total_processed = 0
starttime = datetime.datetime.now()
print 'Started at: %s' % starttime.strftime('%Y-%m-%d %H:%M:%S')

# Open database connection
db = MySQLdb.connect(db_config.host,db_config.username,db_config.password,db_config.database )
# prepare a cursor object using cursor() method
cursor = db.cursor()
        
# function to print usage
def usage():
    print 'harvest.py -s <sitecode> -f <startfromend> -b <startfrombeginning> -e <endvalue>'
    print ''
    print '<sitecode> can be one of nwr or mlb'
    print ''
    print '<startfrombeginning> can be an integer indicating the number of rows from the beginning of valid data in the file (29 lines from top) to begin processing.'
    print '<startfromend> can be an integer indicating the number of rows from the end  of the file to begin processing.'
    print ''
    print '<endvalue>: if <startfrombeginning> is selected, <endvalue> can be an integer indicating the number of rows from the beginning  of valid data in the file (29 lines from top) to finish processing'
    print '<endvalue>: if <startfromend> is selected, <endvalue> can be an integer indicating the number of rows from the end of the file to finish processing'
    print ''
    print 'only one of -f or -b can be used for a given run and default is all data'

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
    input_beginfromstart = 0
    input_beginfromend = 0
    input_end = 0
    begin = 0
    end = 0
    filename = ''
    sitecode = ''
    
    try:
        opts, args = getopt.getopt(argv, "hs:f:b:e:", ["sitecode=", "startfromend=", "startfrombeginning=", "endvalue="])
    except getopt.GetoptError:          
        usage()                      
        sys.exit(2)  
    # retrieve arguments
    for opt, arg in opts:                
        if opt in ("-h", "--help"): 
            usage()
            sys.exit(2)                  
        elif opt in ("-s", "--sitecode"):              
            sitecode = str(arg)     
            
        if opt in ("-b", "--startfrombeginning"): 
            begin = int(arg)
            input_beginfromstart = 1
            
        if opt in ("-f", "--startfromend"): 
            begin = int(arg)
            input_beginfromend = 1
            
        if opt in ("-e", "--endvalue"): 
            end = int(arg)
            input_end = 1
            
    # checks for valid input
    # ensure only one of -l or -b is chosen    
    if(input_beginfromend == 1 and input_beginfromstart == 1):
        print "Only use one of -l or -f for a given run"
        usage()
        sys.exit(2)  
    elif (input_beginfromstart == 1):
        # checks - verify end is greater than begin if end was defined
        if end != 0 and end < begin:
            print "end value must be greater than begin value for begin from start"
            print "selected begin:%i and end:%i  " % (begin, end)
            usage()
            sys.exit(2)  
    else:            
        # checks - verify end is less than begin  
        if end > begin:
            print "end value must be less than begin value for begin from end"
            print "selected begin:%i and end:%i  " % (begin, end)
            usage()
            sys.exit(2)  
        
    # only proceed if there are arguments        
    if (totalargs > 0):
        str_print = []
        
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
        # ignore the first 29 lines which are just text file comments
        lines = lines[29:]
        
        # checks - verify begin and end are less than total lines
        numlines = len(lines)
        
        if begin > numlines or end > numlines:
            print "cannot take slice larger that dataset.  Only %i lines available. " % numlines
            print "provided begin:%i and end:%i" % (begin,end)
            usage()
            sys.exit(2)  
            

        # NOTE: only do counter if re-processing full set of data
        if(begin == 0 and end == 0):
            lastlines = lines
        elif (input_beginfromend == 1):
            print 'processing from end %i to %i' % (begin, end)
            #processing from end
            if ( end > 0):
                lastlines = lines[-begin:-end]
            else:
                # process all starting from -begin from end
                lastlines = lines[-begin:]
        elif (input_beginfromstart == 1):
            # processing from start
            print 'processing from beginning %i to %i' % (begin, end)
            if ( end > 0):
                lastlines = lines[begin:end]
            else:
                # process all starting from -begin from start
                lastlines = lines[begin:]
        else :
            print 'no begin or end: beginfromstart = %i, beginfromend=%i, end=%i, begin=%i' % (input_beginfromstart, input_beginfromend, end, begin)
            
        total_processed = len(lastlines)

        sql_insert = """INSERT INTO climate_co2_data2(sitecode, co2_value, timestamp_co2_recorded) VALUES(%s,%s,%s)"""
        sql_update = """UPDATE climate_co2_data2 SET co2_value=%s WHERE sitecode=%s AND timestamp_co2_recorded=%s"""
        values_insert = []
        values_update = []
        for line in lastlines:
            message_failure = ''
            message_success = ''
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
                    #sql = "INSERT INTO climate_co2_data2(sitecode, co2_value, timestamp_co2_recorded) VALUES('%s','%s','%i')" % (sitecode, co2_value, timestamp)
                    # message_success = "Added %s - %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode)
                    # message_failure = "Could not commit %s - %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode)  
                    values_insert.append((sitecode, co2_value, timestamp));
                    str_print.append("Attempting to add %s - %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode))
                else:
                    #str_print.append("Value already exists on %s for %s.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode))
                    # if more than 1 row - report as error
                    if (numrows > 1):
                        str_print.append("More than 1 value exists (%i: %i) in the db for %s and %s - you should investigate." % (numrows, int(timestamp), datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode))
                    else:
                        #check if values are different. If so, update
                        data = cursor.fetchone()
                        if (str(data[2]) != str(co2_value)):
                            #db_transaction('update', sitecode, str(co2_value), timestamp, str(data[2]))
                            #sql = "UPDATE climate_co2_data2 SET co2_value='%s' WHERE sitecode='%s' AND timestamp_co2_recorded='%i'" % (co2_value, sitecode, timestamp)
                            #message_success = "Updated %s - %s from %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode, data[2])
                            #message_failure = "Could not update %s - %s from %s for %s to db.\r\n" % (time_formatted, co2_value, sitecode, data[2])
                            values_update.append((co2_value, sitecode, timestamp));
                            str_print.append("Attempting to update %s - %s from %s for %s to db.\r\n" % (time_formatted, co2_value, data[2], sitecode))
        #inserts 
        print " \n ".join(str_print)
        if (len(values_insert) > 0):                                                                             
            try:
                # Execute the SQL command
                #cursor.execute(sql)
                cursor.executemany(sql_insert, values_insert)
                # Commit your changes in the database
                db.commit()
                #print message_success
                print 'Data committed'
            except MySQLdb.Error, e:
                try:
                    print "MySQL Error [%d]: %s" % (e.args[0], e.args[1])
                except IndexError:
                    print "MySQL Error: %s" % str(e)
                # Rollback in case there is any error
                db.rollback()
                #print message_failure
                print "Could not commit"    
        #updates
        if (len(values_update) > 0):                                                                             
            try:
                # Execute the SQL command
                #cursor.execute(sql)
                cursor.executemany(sql_update, values_update)
                # Commit your changes in the database
                db.commit()
                #print message_success
                print 'Data updated'
            except MySQLdb.Error, e:
                try:
                    print "MySQL Error [%d]: %s" % (e.args[0], e.args[1])
                except IndexError:
                    print "MySQL Error: %s" % str(e)
                # Rollback in case there is any error
                db.rollback()
                #print message_failure
                print "Could not update"        
        # disconnect from server
        db.close()
        
        
    print 'Total items processed: '
    print total_processed
        
main()
endtime = datetime.datetime.now()
print 'Ended at: %s' % endtime.strftime('%Y-%m-%d %H:%M:%S')
print 'Total time to run: '
print endtime - starttime
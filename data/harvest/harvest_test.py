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
def retrieve_data(filename,command_type, begin,end):
    # open external file to retrieve data
    res = []
    begin_modified = 0
    end_modified = 0
    
    f = urllib2.urlopen(filename)
    lines = f.readlines()
    totallines = len(lines)
    f.close()
    
    if command_type == 'bfs':
        begin_modified = begin + 29
        end_modified = end + 29
    elif command_type == 'bfe':
        begin_modified = totallines - begin
        end_modified = totallines - end
        if(begin_modified < 29):
            begin_modified = 29
        if(end_modified < 29):
            end_modified = 29
            
    res = lines[begin_modified:end_modified]
    print "Gathered relevant lines"
    return res
                    
#main 
def main():   
    #retrieve arguments
    command_type = ''
    input_end = 0
    begin = 0
    end = 0
    filename = ''
    sitecode = ''
    
    # retrieve arguments
    try:
        opts, args = getopt.getopt(argv, "hs:f:b:e:", ["sitecode=", "startfromend=", "startfrombeginning=", "endvalue="])
    except getopt.GetoptError:          
        usage()                      
        sys.exit(2)  
    for opt, arg in opts:                
        if opt in ("-h", "--help"): 
            usage()
            sys.exit(2)                  
        elif opt in ("-s", "--sitecode"):              
            sitecode = str(arg)     
            
        if opt in ("-b", "--startfrombeginning"): 
            begin = int(arg)
            command_type = 'bfs'
            
        if opt in ("-f", "--startfromend"): 
            begin = int(arg)
            command_type = 'bfe'
            
        if opt in ("-e", "--endvalue"): 
            end = int(arg)
            input_end = 1
            
    # checks for valid input
    # ensure only one of -l or -b is chosen    
    if (command_type == 'bfs'):
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
            filename = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_NWR_most_recent.lme"; 
        elif (sitecode == 'mlb'):
            sitecode = "mlb"
            filename = "http://www.eol.ucar.edu/homes/stephens/RACCOONlab/NCAR_MLB_most_recent.lme"; 
        else:
            usage()
            sys.exit(2)
        lines = retrieve_data(filename,command_type, begin,end)
        total_processed = len(lines)

        sql_insert = """INSERT INTO climate_co2_data2(sitecode, co2_value, timestamp_co2_recorded) VALUES(%s,%.3f,%s)"""
        sql_update = """UPDATE climate_co2_data2 SET co2_value=%.3f WHERE sitecode=%s AND timestamp_co2_recorded=%s"""
        sql_update_inactive = """UPDATE climate_co2_data2 SET active=0 WHERE sitecode=%s AND timestamp_co2_recorded=%s"""
        values_insert = []
        values_update = []
        values_update_inactive = []
        
        # Open database connection
        db = MySQLdb.connect(db_config.host,db_config.username,db_config.password,db_config.database )
        try:
            # prepare a cursor object using cursor() method
            cursor = db.cursor()

            for line in lines:
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
                    co2_value = float(parts[8])
                    hgt = float(parts[7])
                    print "parsed with co2=%.3f on %s"%(co2_value, date_text)

                    if(co2_value < 380):
                        print "CO2=%.3f on %s when HGT=%.3f"%(co2_value, date_text,hgt)
                    
                    # oct 28, 2016: Britt: if HGT = 0, then it is surveillence value and should be filtered out
                    if(hgt != 0):
                        # see if already exists in db
                        sql = "SELECT * FROM climate_co2_data2 WHERE sitecode='%s' AND timestamp_co2_recorded='%i'" % (sitecode , timestamp)
                        # Execute the SQL command
                        cursor.execute(sql)
                        # ensure no results before insert
                        numrows = cursor.rowcount
                        time_formatted = datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S')
                        if (numrows == 0):
                            print 'no'
                            values_insert.append((sitecode, co2_value, timestamp));
                            str_print.append("Attempting to add %s - %.3f for %s to db where HGT=%.3f.\r\n" % (time_formatted, co2_value, sitecode,hgt))
                        else:
                            #str_print.append("Value already exists on %s for %s.\r\n" % (datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode))
                            # if more than 1 row - report as error
                            if (numrows > 1):
                                str_print.append("More than 1 value exists (%i: %i) in the db for %s and %s - you should investigate." % (numrows, int(timestamp), datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S'), sitecode))
                            else:
                                #check if values are different. If so, update
                                data = cursor.fetchone()
                                if (str(data[2]) != str(co2_value)):
                                    values_update.append((co2_value, sitecode, timestamp));
                                    str_print.append("Attempting to update %s - %.3f from %s for %s to db where HGT=%.3f.\r\n" % (time_formatted, co2_value, data[2], sitecode, hgt))
                    else:
                        print 'invalid'
                        # there are some hgt = 0, see if already exists in db
                        sql = "SELECT * FROM climate_co2_data2 WHERE sitecode='%s' AND active='1' AND timestamp_co2_recorded='%i'" % (sitecode , timestamp)
                        # Execute the SQL command
                        cursor.execute(sql)
                        # ensure no results before insert
                        numrows = cursor.rowcount
                        time_formatted = datetime.datetime.fromtimestamp(int(timestamp)).strftime('%Y-%m-%d %H:%M:%S')
                        if (numrows == 0):
                            str_print.append("Filtering out %s - %.3f for %s to db where HGT=%.3f.\r\n" % (time_formatted, co2_value, sitecode,hgt))
                        else:
                            #already exists so make inactive
                            values_update_inactive.append((sitecode, timestamp));
                            str_print.append("Setting inactive %s - %.3f for %s to db where HGT=%.3f.\r\n" % (time_formatted, co2_value, sitecode,hgt))
                            
               
        finally:
            # disconnect from server
            db.close()
        
    print 'Total items processed: '
    print total_processed
        
main()
endtime = datetime.datetime.now()
print 'Ended at: %s' % endtime.strftime('%Y-%m-%d %H:%M:%S')
print 'Total time to run: '
print endtime - starttime
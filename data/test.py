#!/usr/bin/env python
import mysql.connector

cnx = mysql.connector.connect(user='sclark',password='VAU5hTpB',host='localhost',database='scied_exhibits' )

cnx.close()


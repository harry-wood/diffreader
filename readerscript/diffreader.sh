#!/bin/sh
#bash script to be run by cron to ensure that diffreader.rb process stays up
#Check if diffreader appears to already be running. If so we exit

cd "$(dirname "$0")" #set working directory to here

PID=`ps -eo 'tty pid args' | grep 'diffreader.rb' | grep -v grep | tr -s ' ' | cut -f2 -d ' '`
if [ -n "$PID" ]
then
   echo "'diffreader.rb' Process is already Running with PID=$PID ...exiting. `date`"
else
   echo "RESTARTING 'diffreader.rb' Process, since it appears to not be running `date`"
   ruby diffreader.rb directory=schools $*
fi

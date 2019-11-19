#!/usr/bin/env bash
#Fake sendmail script, adapted from:
#https://github.com/mrded/MNPP/blob/ee64fb2a88efc70ba523b78e9ce61f9f1ed3b4a9/init/fake-sendmail.sh

#Create a temp folder to put messages in
mkdir -p ${EMAIL_TEST_PATH}

name="${EMAIL_TEST_PATH}/message"
printf "" > ${name}

while read line
do
    printf "${line}\r\n" >> ${name}
done
exit 0

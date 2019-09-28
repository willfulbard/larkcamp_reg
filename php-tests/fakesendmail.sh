#!/usr/bin/env bash
#Fake sendmail script, adapted from:
#https://github.com/mrded/MNPP/blob/ee64fb2a88efc70ba523b78e9ce61f9f1ed3b4a9/init/fake-sendmail.sh

#Create a temp folder to put messages in
emailPath="/Users/will/Sources/larkcamp_reg/php-tests/emails"
mkdir -p ${emailPath}

name="${emailPath}/message"
echo "" > ${name}

while read line
do
    echo ${line} >> ${name}
done
exit 0

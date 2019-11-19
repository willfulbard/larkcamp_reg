#!/usr/bin/env node

const simpleParser = require('mailparser').simpleParser;
const fs = require('fs');

const body = fs.readFileSync(__dirname + '/emails/message');

simpleParser(body)
	.then(parsed => console.log(JSON.stringify(parsed)));

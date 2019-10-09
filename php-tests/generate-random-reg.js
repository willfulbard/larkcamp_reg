#!/usr/bin/env node

const jsf = require('json-schema-faker');
const schema = require('../public/config.json').dataSchema;

const registration = jsf.generate(schema); // [object Object]

console.log(registration);
// console.log(JSON.stringify(registration));


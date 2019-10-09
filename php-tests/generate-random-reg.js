#!/usr/bin/env node

const jsf = require('json-schema-faker');
const schema = require('../public/config.json').dataSchema;

jsf.option('alwaysFakeOptionals', true);

const registration = jsf.generate(schema); // [object Object]

// console.log(registration);
console.log(JSON.stringify(registration));


import fs from 'fs';
import jsonLogic from 'json-logic-js';
const config = fs.readFileSync('public/config.json');

const priceOf = (data) => {
  jsonLogic.apply(JSON.parse(config).pricingLogic, data);
};

describe('Config object', () => {
  it('is valid JSON', () => {
    expect(JSON.parse(config));
  });
  
  it('has the appropriate keys', () => {
    ['uiSchema', 'dataSchema', 'pricingLogic'].forEach((property) => {
      expect(JSON.parse(config)).toHaveProperty(property);
    });
  });

  it('correctly prices full-camp paying by check', () => {
    expect(priceOf({
      payment_type: 'Check'
      campers: [
        {
          
        }
      ],
    })).toEqual(784)

  });
});


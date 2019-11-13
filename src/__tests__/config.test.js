import fs from 'fs';
import jsonLogic from 'json-logic-js';
const config = fs.readFileSync('public/config.json');
  

describe('Config object', () => {
  it('is valid JSON', () => {
    expect(JSON.parse(config));
  });
  
  it('has the appropriate keys', () => {
    ['uiSchema', 'dataSchema', 'pricingLogic'].forEach((property) => {
      expect(JSON.parse(config)).toHaveProperty(property);
    });
  });
});


describe('Pricing Logic', () => {
  // A truth table is a little easier to work with here.
  // [Payment type, camp duration, meals, age, # parking passes]
  const truthTable = [
    // Discount logic
    ['Check', 'F', '', , 0, 764],
    ['Paypal', 'F', '', , 0, 784],
    ['Credit Card', 'F', '', , 0, 784],
  
    // Different ages across different camp types
    ['Paypal', 'F', '', 18, 0, 784],
    ['Paypal', 'F', '', 11, 0, 596],
    ['Paypal', 'F', '', 3, 0, 0],
    ['Paypal', 'A', '', 18, 0, 596],
    ['Paypal', 'A', '', 11, 0, 484],
    ['Paypal', 'A', '', 3, 0, 0],
  
    // Different ages for different meals
    ['Paypal', 'F', 'F', 18, 0, 784 + 405],
    ['Paypal', 'F', 'F', 11, 0, 596 + 304],
    ['Paypal', 'F', 'F', 3, 0, 304],

    ['Paypal', 'F', 'D', 18, 0, 784 + 221],
    ['Paypal', 'F', 'D', 11, 0, 596 + 162],
    ['Paypal', 'F', 'D', 3, 0, 162],

    ['Paypal', 'A', 'A', 18, 0, 596 + 215],
    ['Paypal', 'A', 'A', 11, 0, 484 + 154],
    ['Paypal', 'A', 'A', 3, 0, 154],
  
    // Parking Pass
    ['Paypal', 'F', 'None', 18, 1, 784 + 62],
  ];

  truthTable.forEach((entry) => {
    it(`correctly prices ${entry.join(', ')}`, () => {
      const [payment_type, session, meal_plan, age, numPasses, price] = entry;
      const pricingLogic = JSON.parse(config).pricingLogic;
      expect(jsonLogic.apply(pricingLogic, {
        payment_type,
        campers: [
          {
            session,
            age,
            meals: {
              meal_plan,
            }
          }
        ],
        parking_passes: Array(numPasses).fill({}),
      })).toEqual(price);
    });
  });
});



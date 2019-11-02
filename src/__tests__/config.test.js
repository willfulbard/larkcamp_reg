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
    ['Check', 'Full camp', 'None', , 0, 764],
    ['Paypal', 'Full camp', 'None', , 0, 784],
    ['Credit Card', 'Full camp', 'None', , 0, 784],
  
    // Different ages across different camp types
    ['Paypal', 'Full camp', 'None', 18, 0, 784],
    ['Paypal', 'Full camp', 'None', 11, 0, 596],
    ['Paypal', 'Full camp', 'None', 3, 0, 0],
    ['Paypal', 'Half camp (1st half)', 'None', 18, 0, 596],
    ['Paypal', 'Half camp (1st half)', 'None', 11, 0, 484],
    ['Paypal', 'Half camp (1st half)', 'None', 3, 0, 0],
  
    // Different ages for different meals
    ['Paypal', 'Full camp', 'Full Camp, All Meals', 18, 0, 784 + 405],
    ['Paypal', 'Full camp', 'Full Camp, All Meals', 11, 0, 596 + 304],
    ['Paypal', 'Full camp', 'Full Camp, All Meals', 3, 0, 304],

    ['Paypal', 'Full camp', 'Full Camp, Just Dinners', 18, 0, 784 + 221],
    ['Paypal', 'Full camp', 'Full Camp, Just Dinners', 11, 0, 596 + 162],
    ['Paypal', 'Full camp', 'Full Camp, Just Dinners', 3, 0, 162],

    ['Paypal', 'Half camp (1st half)', 'Half Camp (first half), All Meals', 18, 0, 596 + 215],
    ['Paypal', 'Half camp (1st half)', 'Half Camp (first half), All Meals', 11, 0, 484 + 154],
    ['Paypal', 'Half camp (1st half)', 'Half Camp (first half), All Meals', 3, 0, 154],
  
    // Parking Pass
    ['Paypal', 'Full camp', 'None', 18, 1, 784 + 62],
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



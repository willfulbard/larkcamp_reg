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
    ['Check', 'Full camp', 'None', , 0, 76400],
    ['Paypal', 'Full camp', 'None', , 0, 78400],
    ['Credit Card', 'Full camp', 'None', , 0, 78400],
  
    // Different ages across different camp types
    ['Paypal', 'Full camp', 'None', 18, 0, 78400],
    ['Paypal', 'Full camp', 'None', 11, 0, 59600],
    ['Paypal', 'Full camp', 'None', 3, 0, 0],
    ['Paypal', 'Half camp (1st half)', 'None', 18, 0, 59600],
    ['Paypal', 'Half camp (1st half)', 'None', 11, 0, 48400],
    ['Paypal', 'Half camp (1st half)', 'None', 3, 0, 0],
  
    // Different ages for different meals
    ['Paypal', 'Full camp', 'Full Camp, All Meals', 18, 0, 78400 + 40600],
    ['Paypal', 'Full camp', 'Full Camp, All Meals', 11, 0, 59600 + 30400],
    ['Paypal', 'Full camp', 'Full Camp, All Meals', 3, 0, 30400],

    ['Paypal', 'Full camp', 'Full Camp, Just Dinners', 18, 0, 78400 + 23000],
    ['Paypal', 'Full camp', 'Full Camp, Just Dinners', 11, 0, 59600 + 16600],
    ['Paypal', 'Full camp', 'Full Camp, Just Dinners', 3, 0, 16600],

    ['Paypal', 'Half camp (1st half)', 'Half Camp (first half), All Meals', 18, 0, 59600 + 23000],
    ['Paypal', 'Half camp (1st half)', 'Half Camp (first half), All Meals', 11, 0, 48400 + 16600],
    ['Paypal', 'Half camp (1st half)', 'Half Camp (first half), All Meals', 3, 0, 16600],
  
    // Parking Pass
    ['Paypal', 'Full camp', 'None', 18, 1, 78400 + 6200],
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

  it('correctly handles non-integer values for t-shirts', () => {
    const pricingLogic = JSON.parse(config).pricingLogic;
    expect(jsonLogic.apply(pricingLogic, {
      tshirt_sizes: {
        small: 1.05,
        medium: 1.05,
        large: 1.05,
        xl: 1.05,
        xxl: 1.05,
      },
      sweatshirt_sizes: {
        small: 1.05,
        medium: 1.05,
        large: 1.05,
        xl: 1.05,
        xxl: 1.05,
      }
    })).toEqual(18400);
  });

  it('correctly handles non-integer values for donations', () => {
    const pricingLogic = JSON.parse(config).pricingLogic;
    expect(jsonLogic.apply(pricingLogic, {
      lta_donation: 1.0540,
      woodlands_donation: 1.4390,
    })).toEqual(248);
  });
});



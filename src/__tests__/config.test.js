import fs from 'fs';
import jsonLogic from 'json-logic-js';
import { calculatePrice } from '../components/utils';
const config = fs.readFileSync('public/config.json');
  

describe('Config object', () => {
  it('is valid JSON', () => {
    expect(JSON.parse(config));
  });
  
  it('has the appropriate keys', () => {
    ['uiSchema', 'dataSchema', 'pricingLogic', 'pricing'].forEach((property) => {
      expect(JSON.parse(config)).toHaveProperty(property);
    });
  });
});

describe('Pricing Logic', () => {
  // A truth table is a little easier to work with here.
  // [Payment type, camp duration, meals, age, # parking passes]

	const conf = JSON.parse(config);
	
  const truthTable = [
    // Discount logic
    ['Check', 'F', '', , 0, conf.pricing.full_adult + conf.pricing.check_discount_full],
    ['Paypal', 'F', '', , 0, conf.pricing.full_adult],
    ['Credit Card', 'F', '', , 0, conf.pricing.full_adult],
  
		// Different ages across different camp types
    ['Paypal', 'F', '', 18, 0, conf.pricing.full_adult],
    ['Paypal', 'F', '', 11, 0, conf.pricing.full_teen],
    ['Paypal', 'F', '', 3, 0, 0],
    ['Paypal', 'A', '', 18, 0, conf.pricing.half_adult],
    ['Paypal', 'A', '', 11, 0, conf.pricing.half_teen],
    ['Paypal', 'A', '', 3, 0, 0],
  
    // Different ages for different meals
    ['Paypal', 'F', 'F', 18, 0, conf.pricing.full_adult + conf.pricing.meals_adult_full],
    ['Paypal', 'F', 'F', 11, 0, conf.pricing.full_teen  + conf.pricing.meals_teen_full ],
    ['Paypal', 'F', 'F', 3, 0, conf.pricing.meals_teen_full],

    ['Paypal', 'F', 'D', 18, 0, conf.pricing.full_adult + conf.pricing.meals_adult_dinners],
    ['Paypal', 'F', 'D', 11, 0, conf.pricing.full_teen  + conf.pricing.meals_teen_dinners],
    ['Paypal', 'F', 'D', 3, 0,  conf.pricing.meals_teen_dinners],

    ['Paypal', 'A', 'A', 18, 0, conf.pricing.half_adult + conf.pricing.meals_adult_half],
    ['Paypal', 'A', 'A', 11, 0, conf.pricing.half_teen  + conf.pricing.meals_teen_half],
    ['Paypal', 'A', 'A', 3, 0,  conf.pricing.meals_teen_half],
  
    // Parking Pass
    ['Paypal', 'F', '', 18, 1, conf.pricing.full_adult + conf.pricing.parking_pass],
  ];

  truthTable.forEach((entry) => {
    it(`correctly prices ${entry.join(', ')}`, () => {
      const [payment_type, session, meal_plan, age, numPasses, price] = entry;

			const state = {
				config: conf,
				formData: {
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
				}
			};

      expect(calculatePrice(state).total).toEqual(price);
    });
  });
});



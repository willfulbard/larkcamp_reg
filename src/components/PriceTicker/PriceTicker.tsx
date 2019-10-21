import React from 'react';
import { Cents } from '../App/appTypes';
import './PriceTicker.css';

const PriceTicker = (props: {price: Cents}) => (
  <div className="PriceTicker">
    Total: ${(props.price / 100).toFixed(2)}
  </div>
);

export default PriceTicker;
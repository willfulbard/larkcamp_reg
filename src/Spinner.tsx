import React from 'react';
import './Spinner.css';

const Spinner = ({text = 'Loading'}) => (
  <div className="spinner">
    <svg viewBox="-10 -10 70 70">
      <mask id="myMask">
        <rect x="0" y="0" width="50" height="50" fill="white" />
        <path d="M25,25 L0,-25 L0,25 Z" fill="black" />
      </mask>
      <circle cx="25" cy="25" r="20" mask="url(#myMask)" stroke="green" fill="none" stroke-width="5" />
    </svg>
    {text}
  </div>
);

export default Spinner;
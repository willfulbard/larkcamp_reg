import 'bootstrap/dist/css/bootstrap.min.css';

import React from 'react';
import Form, { IChangeEvent } from 'react-jsonschema-form';
import Spinner from '../Spinner';
import { AppState, Dollars } from './appTypes';
import jsonLogic from 'json-logic-js';
import DescriptionField from '../DescriptionField';
import ObjectFieldTemplate from '../ObjectFieldTemplate';
import PriceTicker from '../PriceTicker';

import './App.css';

class App extends React.Component {
    state: AppState = {
        status: 'fetching',
    }

    constructor(props = {}) {
        super(props);

        this.getConfig();
    }

    onSubmit = async ({formData}: any) => {
        this.setState({status: 'submitting'});
        try {
            await fetch('/register.php', {
                method: 'POST',
                body: JSON.stringify(formData),
            });
            this.setState({ status: 'submitted' });
        } catch {
            this.setState({ status: 'submissionError' });
        }
    }

    getConfig = async () => {
        let config;
        try {
            const res = await fetch('/config.json');
            config = await res.json();
        } catch (e) {
            console.error(e);
            return;
        }

        this.setState({
            status: 'loaded',
            config,
            formData: undefined,
        });
    }

    onChange = ({formData}: IChangeEvent) => {
        this.setState({ formData });
    }

    getPrice = (): Dollars => {
        // calculation should be done in whole dollars for sake of
        // avoiding funky issues with floats. If sub-dollar amounts 
        // are necessary, we should switch this to cents.
        if(this.state.status === 'fetching'){
            throw new Error('Got price while still fetching');
        }
        const cost = jsonLogic.apply(
            this.state.config.pricingLogic,
            this.state.formData
        );
        if (typeof cost !== 'number') {
            throw new Error(
                `Pricing returned incorrect type (expected number, got ${typeof cost})`);
        } else if (Math.floor(cost) !== cost) {
            throw new Error(`Pricing returned non-natural number (got ${cost}).`);
        }
        return cost;
    }

    transformErrors = (errors: Array<any>) => errors.map(error => {
        if (error.name === 'pattern' && error.property === '.payer_number') {
            return {
                ...error,
                message: 'Please enter a valid phone number',
            };
        }

        return error;
    });

    render() {
        let pageContent : JSX.Element;
        switch(this.state.status){
            case 'loaded':
            case 'submitting':
                pageContent = (
                    <section>
                        <Form
                            schema={this.state.config.dataSchema}
                            uiSchema={this.state.config.uiSchema}
                            fields={{DescriptionField: DescriptionField}}
                            ObjectFieldTemplate={ObjectFieldTemplate}
                            onChange={this.onChange}
                            onSubmit={this.onSubmit}
                            onError={() => console.log('errors')}
                            formData={this.state.formData}
                            transformErrors={this.transformErrors}
                            // liveValidate={true}
                        />
                        <PriceTicker price={this.getPrice()} />
                    </section>
                );  
                break;
            case 'submitted':
                pageContent = (
                    <div className="reciept">
                        <h1>You're all set!</h1>
                        <span>See you at Lark in the Morning 2020!</span>
                    </div>
                )
                break;
            default: 
                pageContent = (<Spinner />);
        }
        return (
            <div className="App">
                {pageContent}
            </div>
        );
    }
}

export default App;

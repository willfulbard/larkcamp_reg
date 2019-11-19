import 'bootstrap/dist/css/bootstrap.min.css';

import React from 'react';
import Form, { IChangeEvent } from 'react-jsonschema-form';
import Spinner from '../Spinner';
import { AppState, Dollars } from './appTypes';
import PhoneInput from 'react-phone-number-input';
import DescriptionField from '../DescriptionField';
import ObjectFieldTemplate from '../ObjectFieldTemplate';
import NaturalNumberInput from '../NaturalNumberInput';
import PriceTicker from '../PriceTicker';

import { calculatePrice } from '../utils';

import 'react-phone-number-input/style.css'
import './App.css';

// TODO(evinism): Make this better typed
const widgetMap: any = {
    PhoneInput: (props: any) => (
        <PhoneInput
            country="US"
            value={props.value}
            onChange={(value: string) => props.onChange(value)}
        />
    ),
    NaturalNumberInput: (props: any) => (
        <NaturalNumberInput
            value={props.value}
            onChange={(value: string) => props.onChange(value)}
        />
    ),
}


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
        console.log(formData);
        this.setState({ formData });
    }

    getPrice = (): Dollars => {
        // calculation should be done in whole dollars for sake of
        // avoiding funky issues with floats. If sub-dollar amounts 
        // are necessary, we should switch this to cents.
        if(this.state.status === 'fetching'){
            throw new Error('Got price while still fetching');
        }

        const costs = calculatePrice(this.state);

        return costs.total;
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
                            widgets={widgetMap}
                            fields={{DescriptionField: DescriptionField}}
                            ObjectFieldTemplate={ObjectFieldTemplate}
                            onChange={this.onChange}
                            onSubmit={this.onSubmit}
                            onError={() => console.log('errors')}
                            formData={this.state.formData}
                            transformErrors={this.transformErrors}
                            // liveValidate={true}
                        >
                            <div>
                                <button type="submit" className="btn btn-info">Submit Registration</button>
                            </div>
                        </Form>
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

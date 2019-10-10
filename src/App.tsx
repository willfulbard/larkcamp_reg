import 'bootstrap/dist/css/bootstrap.min.css';

import React from 'react';
import Form, { IChangeEvent } from 'react-jsonschema-form';
import Spinner from './Spinner';
import { AppState, Cents } from './appTypes';
import jsonLogic from 'json-logic-js';

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

    getPrice = (): Cents => {
        // calculation should be done in cents for sake of
        // avoiding funky issues with floats.
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

    render() {
        let pageContent : JSX.Element;
        switch(this.state.status){
            case 'loaded':
            case 'submitting':
                pageContent = (
                    <>
                        <Form
                            schema={this.state.config.dataSchema}
                            uiSchema={this.state.config.uiSchema}
                            onChange={this.onChange}
                            onSubmit={this.onSubmit}
                            onError={() => console.log('errors')}
                            formData={this.state.formData}
                        />
                        Price: {this.getPrice()}
                    </>
                );  
                break;
            case 'submitted':
                pageContent = (
                    <div className="app container-fluid">
                        <div className="reciept">
                            <h1>You're all set!</h1>
                            <span>See you at Lark in the Morning 2020!</span>
                        </div>
                    </div>
                )
                break;
            default: 
                pageContent = (
                    <div className="app container-fluid">
                        <Spinner />
                    </div>
                );
        }
        return (
            <div className="App container-fluid">
                <section>
                    {pageContent}
                </section>
            </div>
        );
    }
}

export default App;

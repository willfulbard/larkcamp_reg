import 'bootstrap/dist/css/bootstrap.min.css';

import React from 'react';
import Form, { UiSchema } from 'react-jsonschema-form';
import { JSONSchema6 } from "json-schema";
import Spinner from './Spinner';

import './App.css';


interface State {
    config?: {
        uiSchema: UiSchema,
        dataSchema: JSONSchema6,
    },
};

class App extends React.Component {
    state: State = {
        config: undefined,
    }

    constructor(props = {}) {
        super(props);

        this.getConfig();
    }

    async getConfig() {
        let config;
        try {
            const res = await fetch('/config.json');
            config = await res.json();
        } catch (e) {
            console.error(e);

            return;
        }

        this.setState({ config });
    }

    render() {
        let pageContent : JSX.Element;
        if (this.state.config) {
            pageContent = (
                <Form
                    schema={this.state.config.dataSchema}
                    uiSchema={this.state.config.uiSchema}
                    onChange={() => console.log('changed')}
                    onSubmit={() => console.log('submitted')}
                    onError={() => console.log('errors')}
                />
            );
        } else {
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

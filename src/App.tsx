import 'bootstrap/dist/css/bootstrap.min.css';

import React from 'react';
import Form, { UiSchema } from 'react-jsonschema-form';
import { JSONSchema6 } from "json-schema";
import getFromPath from 'lodash/get';

import './App.css';

interface State {
    configFetched: boolean,
    config?: {
        uiSchema: UiSchema,
        dataSchema: JSONSchema6,
    },
};

class App extends React.Component {
    state: State = {
        configFetched: false,
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
        const dataSchema = getFromPath(this.state, 'config.dataSchema');
        const uiSchema = getFromPath(this.state, 'config.uiSchema');

        return (
            <div className="App container-fluid">
                <section>
                    {
                        Boolean(dataSchema) && (
                            <Form
                                schema={dataSchema}
                                uiSchema={uiSchema}
                                onChange={() => console.log('changed')}
                                onSubmit={() => console.log('submitted')}
                                onError={() => console.log('errors')}
                            />
                        )

                    }
                </section>
            </div>
        );
    }
}

export default App;

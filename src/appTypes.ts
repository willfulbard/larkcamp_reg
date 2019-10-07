import { UiSchema } from 'react-jsonschema-form';
import { JSONSchema6 } from "json-schema";

interface WithConfig {
  config: {
      uiSchema: UiSchema,
      dataSchema: JSONSchema6,
  }
}

interface FetchingState {
  status: 'fetching',
}

interface LoadedState extends WithConfig {
  status: 'loaded',
}

interface SubmittingState extends WithConfig {
  status: 'submitting',
}

interface SubmittedState extends WithConfig {
  status: 'submitted',
}

interface SubmissionErrorState extends WithConfig {
  status: 'submissionError',
}

export type AppState = 
  FetchingState | 
  LoadedState |
  SubmittingState | 
  SubmittedState |
  SubmissionErrorState;
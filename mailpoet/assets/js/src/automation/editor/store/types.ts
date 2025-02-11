import { ComponentType } from 'react';
import { Step, Workflow } from '../components/workflow/types';

export interface AutomationEditorWindow extends Window {
  mailpoet_automation_context: Context;
  mailpoet_automation_workflow: Workflow;
}

export type Context = {
  steps: Record<
    string,
    {
      key: string;
      name: string;
      args_schema: {
        type: 'object';
        properties?: Record<string, { type: string; default?: unknown }>;
      };
    }
  >;
};

export type StepGroup = 'actions' | 'logical' | 'triggers';

export type StepType = {
  key: string;
  group: StepGroup;
  title: string;
  description: string;
  subtitle: (step: Step) => JSX.Element | string;
  icon: ComponentType;
  edit: ComponentType;
  foreground: string;
  background: string;
};

export type State = {
  context: Context;
  stepTypes: Record<string, StepType>;
  workflowData: Workflow;
  workflowSaved: boolean;
  selectedStep: Step | undefined;
  inserterSidebar: {
    isOpened: boolean;
  };
  inserterPopover?: {
    anchor: HTMLElement;
    type: 'steps' | 'triggers';
  };
};

export type Feature = 'fullscreenMode' | 'showIconLabels';

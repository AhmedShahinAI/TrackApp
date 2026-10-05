export interface StepperProps{
  /** step labels in order */
  steps:string[];
  /** 0-based index of the active step; earlier steps render as done */
  current:number;
}
export declare function Stepper(props:StepperProps):JSX.Element;

export interface StatCardProps{value:string|number;label:string;tone?:'blue'|'green'|'dark';icon?:string;
  /** e.g. "+4%" or "-2%" */ trend?:string}
export declare function StatCard(props:StatCardProps):JSX.Element;
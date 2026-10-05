export interface SelectProps{
  label?:string;options:(string|{value:string;label:string;disabled?:boolean})[];value?:string;defaultValue?:string;onChange?:(e:any)=>void;
  icon?:string;size?:'sm'|'md';error?:string;disabled?:boolean;required?:boolean;style?:React.CSSProperties;
}
export declare function Select(props:SelectProps):JSX.Element;

export interface InputProps{
  label?:string;placeholder?:string;value?:string;defaultValue?:string;onChange?:(e:any)=>void;
  /** validate-on-blur hook (FR-078) */ onBlur?:(e:any)=>void;
  /** leading Lucide icon (e.g. "search", "mail", "lock") */
  icon?:string;type?:string;hint?:string;error?:string;disabled?:boolean;
  /** shows a red asterisk and sets aria-required */ required?:boolean;
  size?:'sm'|'md'|'lg';dir?:'rtl'|'ltr';inputMode?:string;autoFocus?:boolean;name?:string;style?:React.CSSProperties;
}
export declare function Input(props:InputProps):JSX.Element;

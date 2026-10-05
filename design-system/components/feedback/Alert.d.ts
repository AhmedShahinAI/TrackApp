export interface AlertProps{
  tone?:'info'|'success'|'warning'|'danger';
  title?:React.ReactNode;children?:React.ReactNode;
  /** override the tone's default Lucide icon */
  icon?:string;
  /** trailing slot, e.g. a small Button */
  action?:React.ReactNode;onClose?:()=>void;style?:React.CSSProperties;
}
export declare function Alert(props:AlertProps):JSX.Element;

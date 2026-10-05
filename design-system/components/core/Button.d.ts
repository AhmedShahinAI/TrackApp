/**
 * Primary action control. Arabic label, optional Lucide icon.
 * @startingPoint section="Core" subtitle="Buttons in all variants" viewport="700x260"
 */
export interface ButtonProps{
  variant?:'primary'|'accent'|'secondary'|'ghost'|'danger';
  size?:'sm'|'md'|'lg';
  /** Lucide icon name shown before the label (right side in RTL) */
  icon?:string;iconEnd?:string;fullWidth?:boolean;disabled?:boolean;
  /** shows a spinner and blocks re-triggering until the outcome returns (FR-079) */
  loading?:boolean;
  type?:'button'|'submit';onClick?:(e:any)=>void;children?:React.ReactNode;style?:React.CSSProperties;
}
export declare function Button(props:ButtonProps):JSX.Element;

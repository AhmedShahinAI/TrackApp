export interface DialogProps{
  open?:boolean;title:string;subtitle?:string;children?:React.ReactNode;footer?:React.ReactNode;onClose?:()=>void;width?:number;
  /** render without the fixed overlay (for docs/cards) */
  inline?:boolean;
}
export declare function Dialog(props:DialogProps):JSX.Element|null;
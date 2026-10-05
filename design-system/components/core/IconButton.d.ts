export interface IconButtonProps{
  icon:string;label:string;variant?:'ghost'|'outline'|'filled';size?:number;
  /** red notification dot */
  dot?:boolean;onClick?:(e:any)=>void;style?:React.CSSProperties;
}
export declare function IconButton(props:IconButtonProps):JSX.Element;
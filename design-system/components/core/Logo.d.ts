export interface LogoProps{
  variant?:'primary'|'reversed'|'mono'|'lockup'|'mark'|'mark-reversed';
  height?:number;
  /** path to this design system's assets/ folder, relative to the page. Default "assets/" */
  assetBase?:string;style?:React.CSSProperties;
}
export declare function Logo(props:LogoProps):JSX.Element;
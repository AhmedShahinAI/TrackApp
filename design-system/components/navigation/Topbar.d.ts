export interface TopbarProps{userName?:string;userRole?:string;searchPlaceholder?:string;onMenu?:()=>void;notifications?:boolean;
  /** click on the user block, e.g. open profile */ onUserClick?:()=>void;
  children?:React.ReactNode;
  /** slot before the bell, e.g. language switcher */ end?:React.ReactNode}
export declare function Topbar(props:TopbarProps):JSX.Element;

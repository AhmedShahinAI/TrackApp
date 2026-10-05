export interface SidebarItem{id:string;label:string;icon:string;count?:number}
export interface SidebarProps{items:SidebarItem[];active?:string;onSelect?:(id:string)=>void;assetBase?:string;footer?:React.ReactNode}
export declare function Sidebar(props:SidebarProps):JSX.Element;
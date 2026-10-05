export interface DataTableColumn{key:string;label:string;width?:number|string;numeric?:boolean;
  /** allow text wrapping in this column */ wrap?:boolean;
  render?:(row:any)=>React.ReactNode}
/**
 * Bordered data table with muted header row, hover highlight, skeleton loading, and stacked cards below 768px.
 * @startingPoint section="Data" subtitle="Roster table with status badges" viewport="700x300"
 */
export interface DataTableProps{columns:DataTableColumn[];rows:any[];onRowClick?:(row:any)=>void;dense?:boolean;empty?:React.ReactNode;
  /** show skeleton rows */ loading?:boolean;
  /** viewport width (px) below which rows render as stacked cards. Default 768 (FR-085) */ stackBelow?:number}
export declare function DataTable(props:DataTableProps):JSX.Element;

export interface BreadcrumbItem{label:React.ReactNode;onClick?:()=>void}
export interface BreadcrumbsProps{
  /** last item is the current page */
  items:BreadcrumbItem[];
}
export declare function Breadcrumbs(props:BreadcrumbsProps):JSX.Element;

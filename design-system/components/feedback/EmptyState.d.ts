export interface EmptyStateProps{
  /** Lucide icon name */
  icon?:string;title?:React.ReactNode;message?:React.ReactNode;action?:React.ReactNode;
  /** danger = refusal pages (403) */
  tone?:'brand'|'danger';style?:React.CSSProperties;
}
export declare function EmptyState(props:EmptyStateProps):JSX.Element;

export interface StatusBadgeProps{
  /** Each status carries a fixed AR/EN label; colour is never the only signal. */
  status?:'active'|'completed'|'progress'|'late'|'pending'|'notStarted'|'closed'|'draft'|'archived'|'withdrawn'|'inactive'|'failed'|'queued'|'running'|'warning'|'error'|'valid'|'shared'|'current';
  /** override the default label */
  children?:React.ReactNode;dot?:boolean;
  /** defaults to <html lang> */
  lang?:'ar'|'en';
}
export declare function StatusBadge(props:StatusBadgeProps):JSX.Element;

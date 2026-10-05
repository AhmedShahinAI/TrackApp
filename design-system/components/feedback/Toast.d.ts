export interface ToastProps{tone?:'success'|'error'|'info';title:string;message?:string;onClose?:()=>void}
export declare function Toast(props:ToastProps):JSX.Element;
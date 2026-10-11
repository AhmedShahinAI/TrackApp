{{--
    US7 — names the dialog a form belongs to, so a refusal reopens it (see x-track-dialog,
    "Coming back with errors"). Goes inside the dialog's <form>.
--}}

@props(['for'])

<input type="hidden" name="_dialog" value="{{ $for }}">

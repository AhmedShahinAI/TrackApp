Inline banner for non-blocking notices: first-sign-in password recommendation, sign-in lockout countdown, scope notices, previously-imported-file warnings.

```jsx
<Alert tone="warning" title="يُنصح بتغيير كلمة المرور الأولية" action={<Button size="sm">تغيير</Button>} onClose={dismiss}/>
```

Tones info / success / warning / danger. Always has an icon + text (status never by colour alone).

/* @ds-bundle: {"format":4,"namespace":"TRACKDesignSystem_c33017","components":[{"name":"Button","sourcePath":"components/core/Button.jsx"},{"name":"Icon","sourcePath":"components/core/Icon.jsx"},{"name":"IconButton","sourcePath":"components/core/IconButton.jsx"},{"name":"Logo","sourcePath":"components/core/Logo.jsx"},{"name":"BarChart","sourcePath":"components/data/BarChart.jsx"},{"name":"Card","sourcePath":"components/data/Card.jsx"},{"name":"DataTable","sourcePath":"components/data/DataTable.jsx"},{"name":"DonutChart","sourcePath":"components/data/DonutChart.jsx"},{"name":"LineChart","sourcePath":"components/data/LineChart.jsx"},{"name":"StatCard","sourcePath":"components/data/StatCard.jsx"},{"name":"Alert","sourcePath":"components/feedback/Alert.jsx"},{"name":"Dialog","sourcePath":"components/feedback/Dialog.jsx"},{"name":"EmptyState","sourcePath":"components/feedback/EmptyState.jsx"},{"name":"ProgressBar","sourcePath":"components/feedback/ProgressBar.jsx"},{"name":"Skeleton","sourcePath":"components/feedback/Skeleton.jsx"},{"name":"StatusBadge","sourcePath":"components/feedback/StatusBadge.jsx"},{"name":"Toast","sourcePath":"components/feedback/Toast.jsx"},{"name":"Tooltip","sourcePath":"components/feedback/Tooltip.jsx"},{"name":"Checkbox","sourcePath":"components/forms/Checkbox.jsx"},{"name":"Input","sourcePath":"components/forms/Input.jsx"},{"name":"Select","sourcePath":"components/forms/Select.jsx"},{"name":"Switch","sourcePath":"components/forms/Switch.jsx"},{"name":"Breadcrumbs","sourcePath":"components/navigation/Breadcrumbs.jsx"},{"name":"Sidebar","sourcePath":"components/navigation/Sidebar.jsx"},{"name":"Stepper","sourcePath":"components/navigation/Stepper.jsx"},{"name":"Tabs","sourcePath":"components/navigation/Tabs.jsx"},{"name":"Topbar","sourcePath":"components/navigation/Topbar.jsx"}],"sourceHashes":{"components/core/Button.jsx":"d97f83f2586d","components/core/Icon.jsx":"3d6a528bd1d9","components/core/IconButton.jsx":"4a7842a7cc49","components/core/Logo.jsx":"08bb03dc8e2a","components/data/BarChart.jsx":"27ed744af6ff","components/data/Card.jsx":"966d8fc71aff","components/data/DataTable.jsx":"15ab51d3b1e4","components/data/DonutChart.jsx":"773b752d4833","components/data/LineChart.jsx":"fd4a28bc6abc","components/data/StatCard.jsx":"e73d7e3baee7","components/feedback/Alert.jsx":"f2b2ad871604","components/feedback/Dialog.jsx":"8d019348c764","components/feedback/EmptyState.jsx":"7c40fba707c4","components/feedback/ProgressBar.jsx":"a4dc6c424ab6","components/feedback/Skeleton.jsx":"5156ff84437a","components/feedback/StatusBadge.jsx":"e0e00acfdcb8","components/feedback/Toast.jsx":"ceaf915d8f8d","components/feedback/Tooltip.jsx":"287eedfac744","components/forms/Checkbox.jsx":"01e720cdbdbf","components/forms/Input.jsx":"98121ce9af80","components/forms/Select.jsx":"b72e14b598f6","components/forms/Switch.jsx":"1224d694da62","components/navigation/Breadcrumbs.jsx":"efda52f83be6","components/navigation/Sidebar.jsx":"95abc14fdbc1","components/navigation/Stepper.jsx":"800c8fd26047","components/navigation/Tabs.jsx":"994fd953377e","components/navigation/Topbar.jsx":"ac6e8ced8021","ui_kits/web-app/AuditScreen.jsx":"9fc21438622a","ui_kits/web-app/AuthScreens.jsx":"fc578895cf33","ui_kits/web-app/CourseImport.jsx":"98f1059abd6e","ui_kits/web-app/DashboardParts.jsx":"693deb0254ec","ui_kits/web-app/DashboardScreen.jsx":"007d832fc84f","ui_kits/web-app/HomeScreen.jsx":"f3389b1ab8d4","ui_kits/web-app/ImportScreens.jsx":"1c67e05db24b","ui_kits/web-app/InitiativesScreen.jsx":"f3f132fa360b","ui_kits/web-app/SectionsScreens.jsx":"d7149c508bf1","ui_kits/web-app/Shell.jsx":"67683f5a9a81","ui_kits/web-app/StructureScreens.jsx":"cd9a4654bb53","ui_kits/web-app/UsersScreen.jsx":"a14cc0b715c8","ui_kits/web-app/dash-map.js":"d6e4167fbae2","ui_kits/web-app/dashboard-data.js":"bbbafb618152","ui_kits/web-app/data.js":"e1d9617885a8","ui_kits/web-app/i18n.js":"8e174faabacc","ui_kits/web-app/store.js":"ea3917541fcf","ui_kits/web-app/xlsx.js":"a5b3514b863b"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {

const __ds_ns = (window.TRACKDesignSystem_c33017 = window.TRACKDesignSystem_c33017 || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/core/Icon.jsx
try { (() => {
const pascal = n => n.split('-').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('');
function Icon({
  name,
  size = 20,
  strokeWidth = 1.75,
  color = 'currentColor',
  style,
  ...rest
}) {
  const lib = typeof window !== 'undefined' && window.lucide && window.lucide.icons;
  const node = lib && (lib[pascal(name)] || lib[name]);
  const kids = Array.isArray(node) ? Array.isArray(node[2]) ? node[2] : node : [];
  return React.createElement('svg', {
    width: size,
    height: size,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: color,
    strokeWidth,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': true,
    style: {
      flex: 'none',
      display: 'block',
      ...style
    },
    ...rest
  }, kids.map(([tag, attrs], i) => React.createElement(tag, {
    key: i,
    ...attrs
  })));
}
Object.assign(__ds_scope, { Icon });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Icon.jsx", error: String((e && e.message) || e) }); }

// components/core/Button.jsx
try { (() => {
function ensureKeys() {
  if (typeof document === 'undefined' || document.getElementById('track-kf')) return;
  const s = document.createElement('style');
  s.id = 'track-kf';
  s.textContent = '@keyframes track-spin{to{transform:rotate(360deg)}}@keyframes track-pulse{0%,100%{opacity:1}50%{opacity:.45}}';
  document.head.appendChild(s);
}
const V = {
  primary: {
    bg: 'var(--action-primary)',
    hover: 'var(--action-primary-hover)',
    fg: '#fff',
    bd: 'transparent'
  },
  accent: {
    bg: 'var(--action-accent)',
    hover: 'var(--action-accent-hover)',
    fg: '#fff',
    bd: 'transparent'
  },
  secondary: {
    bg: '#fff',
    hover: 'var(--gray-100)',
    fg: 'var(--track-blue)',
    bd: 'var(--border-strong)'
  },
  ghost: {
    bg: 'transparent',
    hover: 'var(--blue-50)',
    fg: 'var(--track-blue)',
    bd: 'transparent'
  },
  danger: {
    bg: 'var(--red-600)',
    hover: '#B91C1C',
    fg: '#fff',
    bd: 'transparent'
  }
};
const S = {
  sm: {
    h: 32,
    px: 12,
    fs: 13
  },
  md: {
    h: 40,
    px: 16,
    fs: 14
  },
  lg: {
    h: 48,
    px: 20,
    fs: 15
  }
};
function Button({
  variant = 'primary',
  size = 'md',
  icon,
  iconEnd,
  fullWidth,
  disabled,
  loading,
  children,
  onClick,
  type = 'button',
  style
}) {
  const [h, setH] = React.useState(false);
  const [p, setP] = React.useState(false);
  const v = V[variant] || V.primary,
    s = S[size] || S.md;
  const off = disabled || loading;
  if (loading) ensureKeys();
  return /*#__PURE__*/React.createElement("button", {
    type: type,
    disabled: off,
    "aria-busy": loading || undefined,
    onClick: onClick,
    onMouseEnter: () => setH(true),
    onMouseLeave: () => {
      setH(false);
      setP(false);
    },
    onMouseDown: () => setP(true),
    onMouseUp: () => setP(false),
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      gap: 8,
      height: s.h,
      padding: '0 ' + s.px + 'px',
      width: fullWidth ? '100%' : undefined,
      font: '600 ' + s.fs + 'px/1 var(--font-sans)',
      color: v.fg,
      background: h && !off ? v.hover : v.bg,
      border: '1px solid ' + v.bd,
      borderRadius: 'var(--radius-md)',
      cursor: loading ? 'progress' : disabled ? 'not-allowed' : 'pointer',
      opacity: disabled && !loading ? .45 : 1,
      transform: p && !off ? 'scale(.98)' : 'none',
      transition: 'background var(--dur-fast) var(--ease-standard),transform var(--dur-fast)',
      whiteSpace: 'nowrap',
      ...style
    }
  }, loading ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "loader-circle",
    size: s.fs + 4,
    style: {
      animation: 'track-spin .8s linear infinite'
    }
  }) : icon && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: s.fs + 4
  }), children, iconEnd && !loading && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: iconEnd,
    size: s.fs + 4
  }));
}
Object.assign(__ds_scope, { Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Button.jsx", error: String((e && e.message) || e) }); }

// components/core/IconButton.jsx
try { (() => {
function IconButton({
  icon,
  label,
  variant = 'ghost',
  size = 36,
  dot,
  onClick,
  style
}) {
  const [h, setH] = React.useState(false);
  const bg = variant === 'filled' ? h ? 'var(--action-primary-hover)' : 'var(--action-primary)' : variant === 'outline' ? h ? 'var(--gray-100)' : '#fff' : h ? 'var(--gray-100)' : 'transparent';
  return /*#__PURE__*/React.createElement("button", {
    "aria-label": label,
    title: label,
    onClick: onClick,
    onMouseEnter: () => setH(true),
    onMouseLeave: () => setH(false),
    style: {
      position: 'relative',
      width: size,
      height: size,
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: 'var(--radius-md)',
      border: variant === 'outline' ? '1px solid var(--border-default)' : 'none',
      background: bg,
      color: variant === 'filled' ? '#fff' : 'var(--track-blue)',
      cursor: 'pointer',
      transition: 'background var(--dur-fast)',
      ...style
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: Math.round(size * 0.56)
  }), dot && /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      top: 6,
      insetInlineEnd: 7,
      width: 8,
      height: 8,
      borderRadius: 99,
      background: 'var(--red-500)',
      border: '2px solid #fff'
    }
  }));
}
Object.assign(__ds_scope, { IconButton });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/IconButton.jsx", error: String((e && e.message) || e) }); }

// components/core/Logo.jsx
try { (() => {
const F = {
  primary: 'logo-primary.png',
  reversed: 'logo-reversed.png',
  mono: 'logo-mono.png',
  lockup: 'logo-lockup-full.png',
  mark: 'logo-mark.png',
  'mark-reversed': 'logo-mark-reversed.png'
};
function Logo({
  variant = 'primary',
  height = 32,
  assetBase = 'assets/',
  style
}) {
  return /*#__PURE__*/React.createElement("img", {
    src: assetBase + (F[variant] || F.primary),
    alt: "TRACK",
    style: {
      height,
      width: 'auto',
      display: 'block',
      ...style
    }
  });
}
Object.assign(__ds_scope, { Logo });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Logo.jsx", error: String((e && e.message) || e) }); }

// components/data/BarChart.jsx
try { (() => {
const P = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];
function BarChart({
  data = [],
  height = 150,
  max = 100,
  suffix = '%'
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      gap: 14,
      height: height + 44
    }
  }, data.map((d, i) => /*#__PURE__*/React.createElement("div", {
    key: d.label,
    style: {
      flex: 1,
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      gap: 6,
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '600 11px var(--font-numeric)',
      color: 'var(--text-primary)'
    }
  }, d.value, suffix), /*#__PURE__*/React.createElement("div", {
    style: {
      width: '100%',
      maxWidth: 34,
      height: d.value / max * height,
      background: d.color || P[i % P.length],
      borderRadius: '4px 4px 0 0'
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      font: '500 11px var(--font-sans)',
      color: 'var(--text-secondary)',
      whiteSpace: 'nowrap',
      overflow: 'hidden',
      textOverflow: 'ellipsis',
      maxWidth: '100%'
    }
  }, d.label))));
}
Object.assign(__ds_scope, { BarChart });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/BarChart.jsx", error: String((e && e.message) || e) }); }

// components/data/Card.jsx
try { (() => {
function Card({
  title,
  action,
  children,
  padding = 20,
  style
}) {
  return /*#__PURE__*/React.createElement("section", {
    style: {
      background: 'var(--surface-card)',
      borderRadius: 'var(--radius-lg)',
      boxShadow: 'var(--shadow-card)',
      border: '1px solid var(--border-default)',
      padding,
      minWidth: 0,
      ...style
    }
  }, (title || action) && /*#__PURE__*/React.createElement("header", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: 12,
      marginBottom: 16
    }
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: 0,
      font: '700 15px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, title), action), children);
}
Object.assign(__ds_scope, { Card });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/Card.jsx", error: String((e && e.message) || e) }); }

// components/data/DonutChart.jsx
try { (() => {
function DonutChart({
  segments = [],
  total,
  caption,
  size = 130,
  thickness = 16
}) {
  const r = (size - thickness) / 2,
    C = 2 * Math.PI * r;
  let off = 0;
  const sum = segments.reduce((a, s) => a + s.value, 0) || 1;
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 20
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      width: size,
      height: size,
      flex: 'none'
    }
  }, /*#__PURE__*/React.createElement("svg", {
    width: size,
    height: size,
    style: {
      transform: 'rotate(-90deg)'
    }
  }, /*#__PURE__*/React.createElement("circle", {
    cx: size / 2,
    cy: size / 2,
    r: r,
    fill: "none",
    stroke: "var(--gray-100)",
    strokeWidth: thickness
  }), segments.map((s, i) => {
    const len = C * s.value / sum;
    const el = /*#__PURE__*/React.createElement("circle", {
      key: i,
      cx: size / 2,
      cy: size / 2,
      r: r,
      fill: "none",
      stroke: s.color,
      strokeWidth: thickness,
      strokeDasharray: len + ' ' + (C - len),
      strokeDashoffset: -off
    });
    off += len;
    return el;
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      inset: 0,
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 22px var(--font-numeric)',
      color: 'var(--track-dark)'
    }
  }, total), caption && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '500 11px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, caption))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 12
    }
  }, segments.map(s => /*#__PURE__*/React.createElement("div", {
    key: s.label,
    style: {
      display: 'flex',
      gap: 8,
      alignItems: 'flex-start'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 10,
      height: 10,
      borderRadius: 99,
      background: s.color,
      marginTop: 5
    }
  }), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '600 13px var(--font-sans)'
    }
  }, s.label), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '500 12px var(--font-numeric)',
      color: 'var(--text-secondary)'
    }
  }, Math.round(s.value / sum * 100), "%"))))));
}
Object.assign(__ds_scope, { DonutChart });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/DonutChart.jsx", error: String((e && e.message) || e) }); }

// components/data/LineChart.jsx
try { (() => {
function LineChart({
  data = [],
  height = 150,
  max = 100,
  color = 'var(--track-green)'
}) {
  const w = 100,
    n = data.length;
  const pts = data.map((d, i) => [n > 1 ? i / (n - 1) * w : w / 2, height - d.value / max * height]);
  const line = pts.map(p => p.join(',')).join(' ');
  const area = '0,' + height + ' ' + line + ' ' + w + ',' + height;
  return /*#__PURE__*/React.createElement("div", {
    dir: "ltr"
  }, /*#__PURE__*/React.createElement("svg", {
    viewBox: '0 -6 ' + w + ' ' + (height + 12),
    preserveAspectRatio: "none",
    style: {
      width: '100%',
      height: height + 12,
      display: 'block',
      overflow: 'visible'
    }
  }, [0, .25, .5, .75, 1].map(g => /*#__PURE__*/React.createElement("line", {
    key: g,
    x1: "0",
    x2: w,
    y1: height * g,
    y2: height * g,
    stroke: "var(--gray-100)",
    strokeWidth: "1",
    vectorEffect: "non-scaling-stroke"
  })), /*#__PURE__*/React.createElement("polygon", {
    points: area,
    fill: color,
    opacity: ".08"
  }), /*#__PURE__*/React.createElement("polyline", {
    points: line,
    fill: "none",
    stroke: color,
    strokeWidth: "2.5",
    vectorEffect: "non-scaling-stroke",
    strokeLinejoin: "round"
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      height: 0
    }
  }, pts.map((p, i) => /*#__PURE__*/React.createElement("span", {
    key: i,
    style: {
      position: 'absolute',
      left: p[0] + '%',
      top: -(height + 12) + p[1] + 6,
      width: 9,
      height: 9,
      marginLeft: -4.5,
      marginTop: -4.5,
      borderRadius: 99,
      background: '#fff',
      border: '2px solid ' + color
    }
  }))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      marginTop: 8
    }
  }, data.map(d => /*#__PURE__*/React.createElement("span", {
    key: d.label,
    style: {
      font: '500 10px/1.3 var(--font-sans)',
      color: 'var(--text-secondary)',
      textAlign: 'center',
      flex: 1
    }
  }, d.label))));
}
Object.assign(__ds_scope, { LineChart });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/LineChart.jsx", error: String((e && e.message) || e) }); }

// components/data/StatCard.jsx
try { (() => {
function StatCard({
  value,
  label,
  tone = 'blue',
  icon,
  trend
}) {
  const c = tone === 'green' ? 'var(--track-green)' : tone === 'dark' ? 'var(--track-dark)' : 'var(--track-blue)';
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: '#fff',
      borderRadius: 'var(--radius-lg)',
      border: '1px solid var(--border-default)',
      boxShadow: 'var(--shadow-card)',
      padding: '18px 16px',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      gap: 10,
      textAlign: 'center',
      minWidth: 0
    }
  }, icon && /*#__PURE__*/React.createElement("span", {
    style: {
      width: 36,
      height: 36,
      borderRadius: 10,
      background: tone === 'green' ? 'var(--green-50)' : 'var(--blue-50)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 18,
    color: c
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      font: 'var(--text-stat)',
      color: c
    }
  }, value), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '500 13px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, label), trend && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '600 11px var(--font-numeric)',
      color: trend.startsWith('-') ? 'var(--red-600)' : 'var(--green-700)'
    }
  }, trend));
}
Object.assign(__ds_scope, { StatCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/StatCard.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Alert.jsx
try { (() => {
const K = {
  info: {
    i: 'info',
    fg: 'var(--track-blue)',
    bg: 'var(--blue-50)',
    bd: 'var(--blue-100)'
  },
  success: {
    i: 'circle-check',
    fg: 'var(--green-700)',
    bg: 'var(--green-50)',
    bd: 'var(--green-100)'
  },
  warning: {
    i: 'triangle-alert',
    fg: 'var(--amber-600)',
    bg: '#FFFBEB',
    bd: '#FDE68A'
  },
  danger: {
    i: 'circle-alert',
    fg: 'var(--red-600)',
    bg: 'var(--red-50)',
    bd: 'var(--red-100)'
  }
};
function Alert({
  tone = 'info',
  title,
  children,
  icon,
  action,
  onClose,
  style
}) {
  const k = K[tone] || K.info;
  const en = typeof document !== 'undefined' && document.documentElement.lang === 'en';
  return /*#__PURE__*/React.createElement("div", {
    role: tone === 'danger' ? 'alert' : 'status',
    style: {
      display: 'flex',
      gap: 12,
      alignItems: 'flex-start',
      padding: '12px 16px',
      background: k.bg,
      border: '1px solid ' + k.bd,
      borderRadius: 'var(--radius-md)',
      ...style
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon || k.i,
    size: 18,
    color: k.fg,
    style: {
      marginTop: 2
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1,
      minWidth: 0
    }
  }, title && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 14px/1.5 var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, title), children && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 13px/1.6 var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, children)), action, onClose && /*#__PURE__*/React.createElement("button", {
    onClick: onClose,
    "aria-label": en ? 'Dismiss' : 'إغلاق',
    style: {
      border: 'none',
      background: 'none',
      cursor: 'pointer',
      color: 'var(--text-secondary)',
      padding: 2
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "x",
    size: 16
  })));
}
Object.assign(__ds_scope, { Alert });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Alert.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Dialog.jsx
try { (() => {
function Dialog({
  open = true,
  title,
  subtitle,
  children,
  footer,
  onClose,
  width = 520,
  inline
}) {
  if (!open) return null;
  const box = /*#__PURE__*/React.createElement("div", {
    role: "dialog",
    style: {
      width,
      maxWidth: '100%',
      background: '#fff',
      borderRadius: 'var(--radius-xl)',
      boxShadow: 'var(--shadow-overlay)',
      overflow: 'hidden'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-start',
      gap: 12,
      padding: '20px 24px 0'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: 'var(--text-h3)',
      color: 'var(--text-primary)'
    }
  }, title), subtitle && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 13px var(--font-sans)',
      color: 'var(--text-secondary)',
      marginTop: 4
    }
  }, subtitle)), onClose && /*#__PURE__*/React.createElement("button", {
    onClick: onClose,
    "aria-label": "\u0625\u063A\u0644\u0627\u0642",
    style: {
      border: 'none',
      background: 'var(--gray-100)',
      width: 32,
      height: 32,
      borderRadius: 8,
      cursor: 'pointer',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      color: 'var(--text-secondary)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "x",
    size: 16
  }))), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '20px 24px'
    }
  }, children), footer && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 10,
      justifyContent: 'flex-end',
      padding: '14px 24px',
      background: 'var(--gray-50)',
      borderTop: '1px solid var(--border-default)'
    }
  }, footer));
  if (inline) return box;
  return /*#__PURE__*/React.createElement("div", {
    onClick: e => {
      if (e.target === e.currentTarget && onClose) onClose();
    },
    style: {
      position: 'fixed',
      inset: 0,
      background: 'rgba(10,47,58,.45)',
      backdropFilter: 'blur(2px)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 100,
      padding: 20
    }
  }, box);
}
Object.assign(__ds_scope, { Dialog });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Dialog.jsx", error: String((e && e.message) || e) }); }

// components/feedback/EmptyState.jsx
try { (() => {
function EmptyState({
  icon = 'inbox',
  title,
  message,
  action,
  tone = 'brand',
  style
}) {
  const c = tone === 'danger' ? 'var(--red-600)' : 'var(--track-blue)';
  const bg = tone === 'danger' ? 'var(--red-50)' : 'var(--blue-50)';
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      textAlign: 'center',
      gap: 10,
      padding: '40px 20px',
      ...style
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 56,
      height: 56,
      borderRadius: 16,
      background: bg,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      marginBottom: 4
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 26,
    color: c
  })), title && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 16px/1.5 var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, title), message && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 13px/1.7 var(--font-sans)',
      color: 'var(--text-secondary)',
      maxWidth: 420,
      textWrap: 'pretty'
    }
  }, message), action && /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 6,
      display: 'flex',
      gap: 8
    }
  }, action));
}
Object.assign(__ds_scope, { EmptyState });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/EmptyState.jsx", error: String((e && e.message) || e) }); }

// components/feedback/ProgressBar.jsx
try { (() => {
function ProgressBar({
  value = 0,
  tone,
  showLabel = true,
  height = 6
}) {
  const c = tone || (value >= 70 ? 'var(--track-green)' : value >= 40 ? 'var(--blue-500)' : 'var(--red-500)');
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      minWidth: 90
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1,
      height,
      borderRadius: 99,
      background: 'var(--gray-100)',
      overflow: 'hidden'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      width: Math.max(0, Math.min(100, value)) + '%',
      height: '100%',
      background: c,
      borderRadius: 99,
      transition: 'width var(--dur-slow) var(--ease-standard)'
    }
  })), showLabel && /*#__PURE__*/React.createElement("span", {
    style: {
      font: '600 12px var(--font-numeric)',
      color: 'var(--text-primary)',
      minWidth: 32
    }
  }, value, "%"));
}
Object.assign(__ds_scope, { ProgressBar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/ProgressBar.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Skeleton.jsx
try { (() => {
function ensureKeys() {
  if (typeof document === 'undefined' || document.getElementById('track-kf')) return;
  const s = document.createElement('style');
  s.id = 'track-kf';
  s.textContent = '@keyframes track-spin{to{transform:rotate(360deg)}}@keyframes track-pulse{0%,100%{opacity:1}50%{opacity:.45}}';
  document.head.appendChild(s);
}
function Skeleton({
  width = '100%',
  height = 14,
  radius = 6,
  style
}) {
  ensureKeys();
  return /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      display: 'block',
      width,
      height,
      borderRadius: radius,
      background: 'var(--gray-100)',
      animation: 'track-pulse 1.4s ease-in-out infinite',
      ...style
    }
  });
}
Object.assign(__ds_scope, { Skeleton });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Skeleton.jsx", error: String((e && e.message) || e) }); }

// components/data/DataTable.jsx
try { (() => {
function DataTable({
  columns = [],
  rows = [],
  onRowClick,
  dense,
  empty,
  loading,
  stackBelow = 768
}) {
  const [hv, setHv] = React.useState(-1);
  const ref = React.useRef(null);
  const [w, setW] = React.useState(typeof window !== 'undefined' ? window.innerWidth : 9999);
  React.useEffect(() => {
    const f = () => setW(window.innerWidth);
    window.addEventListener('resize', f);
    return () => window.removeEventListener('resize', f);
  }, []);
  const en = typeof document !== 'undefined' && document.documentElement.lang === 'en';
  const emptyTxt = empty || (en ? 'No data' : 'لا توجد بيانات');
  const pad = dense ? '10px 12px' : '13px 14px';
  const stacked = w < stackBelow;
  const cell = (c, r) => c.render ? c.render(r) : r[c.key];
  if (stacked) return /*#__PURE__*/React.createElement("div", {
    ref: ref,
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 10
    }
  }, loading ? [0, 1, 2].map(i => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      padding: 14,
      display: 'flex',
      flexDirection: 'column',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Skeleton, {
    width: "60%"
  }), /*#__PURE__*/React.createElement(__ds_scope.Skeleton, {
    width: "40%"
  }))) : rows.length === 0 ? /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 24,
      textAlign: 'center',
      color: 'var(--text-muted)',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)'
    }
  }, emptyTxt) : rows.map((r, i) => /*#__PURE__*/React.createElement("div", {
    key: r.id || i,
    onClick: onRowClick ? () => onRowClick(r) : undefined,
    tabIndex: onRowClick ? 0 : undefined,
    onKeyDown: onRowClick ? e => {
      if (e.key === 'Enter') onRowClick(r);
    } : undefined,
    style: {
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      padding: 14,
      background: '#fff',
      cursor: onRowClick ? 'pointer' : 'default',
      display: 'grid',
      gridTemplateColumns: 'minmax(0,auto) minmax(0,1fr)',
      gap: '6px 12px',
      font: '400 13px var(--font-sans)'
    }
  }, columns.map(c => /*#__PURE__*/React.createElement(React.Fragment, {
    key: c.key
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--text-secondary)',
      font: '600 12px var(--font-sans)'
    }
  }, c.label), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: c.numeric ? 'var(--font-numeric)' : undefined,
      minWidth: 0
    }
  }, cell(c, r)))))));
  return /*#__PURE__*/React.createElement("div", {
    ref: ref,
    style: {
      overflowX: 'auto',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)'
    }
  }, /*#__PURE__*/React.createElement("table", {
    style: {
      width: '100%',
      borderCollapse: 'collapse',
      font: '400 13px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, /*#__PURE__*/React.createElement("thead", null, /*#__PURE__*/React.createElement("tr", {
    style: {
      background: 'var(--gray-50)'
    }
  }, columns.map(c => /*#__PURE__*/React.createElement("th", {
    key: c.key,
    scope: "col",
    style: {
      textAlign: 'start',
      padding: pad,
      font: '600 12px var(--font-sans)',
      color: 'var(--text-secondary)',
      borderBottom: '1px solid var(--border-default)',
      whiteSpace: 'nowrap',
      width: c.width
    }
  }, c.label)))), /*#__PURE__*/React.createElement("tbody", null, loading ? [0, 1, 2, 3].map(i => /*#__PURE__*/React.createElement("tr", {
    key: i
  }, columns.map(c => /*#__PURE__*/React.createElement("td", {
    key: c.key,
    style: {
      padding: pad,
      borderBottom: '1px solid var(--gray-100)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Skeleton, {
    width: c.numeric ? 48 : '70%'
  }))))) : rows.length === 0 ? /*#__PURE__*/React.createElement("tr", null, /*#__PURE__*/React.createElement("td", {
    colSpan: columns.length,
    style: {
      padding: 28,
      textAlign: 'center',
      color: 'var(--text-muted)'
    }
  }, emptyTxt)) : rows.map((r, i) => /*#__PURE__*/React.createElement("tr", {
    key: r.id || i,
    onClick: onRowClick ? () => onRowClick(r) : undefined,
    tabIndex: onRowClick ? 0 : undefined,
    onKeyDown: onRowClick ? e => {
      if (e.key === 'Enter') onRowClick(r);
    } : undefined,
    onMouseEnter: () => setHv(i),
    onMouseLeave: () => setHv(-1),
    style: {
      background: hv === i ? 'var(--blue-50)' : '#fff',
      cursor: onRowClick ? 'pointer' : 'default',
      transition: 'background var(--dur-fast)'
    }
  }, columns.map(c => /*#__PURE__*/React.createElement("td", {
    key: c.key,
    style: {
      padding: pad,
      borderBottom: i < rows.length - 1 ? '1px solid var(--gray-100)' : 'none',
      whiteSpace: c.wrap ? 'normal' : 'nowrap',
      fontFamily: c.numeric ? 'var(--font-numeric)' : undefined
    }
  }, cell(c, r))))))));
}
Object.assign(__ds_scope, { DataTable });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/data/DataTable.jsx", error: String((e && e.message) || e) }); }

// components/feedback/StatusBadge.jsx
try { (() => {
const T = {
  active: ['active', 'فعّال', 'Active'],
  completed: ['success', 'مكتمل', 'Completed'],
  progress: ['progress', 'قيد التنفيذ', 'In progress'],
  late: ['danger', 'متأخرة', 'Late'],
  pending: ['warning', 'بانتظار الاعتماد', 'Pending'],
  notStarted: ['neutral', 'لم تبدأ', 'Not started'],
  closed: ['neutral', 'مغلق', 'Closed'],
  draft: ['neutral', 'مسودة', 'Draft'],
  archived: ['neutral', 'مؤرشف', 'Archived'],
  withdrawn: ['danger', 'منسحب', 'Withdrawn'],
  inactive: ['neutral', 'معطّل', 'Deactivated'],
  failed: ['danger', 'فشل', 'Failed'],
  queued: ['warning', 'في الانتظار', 'Queued'],
  running: ['progress', 'قيد المعالجة', 'Processing'],
  warning: ['warning', 'تنبيه', 'Warning'],
  error: ['danger', 'خطأ', 'Error'],
  valid: ['success', 'صالح', 'Valid'],
  shared: ['progress', 'وصول مشترك', 'Shared access'],
  current: ['active', 'الفصل الحالي', 'Current']
};
function StatusBadge({
  status = 'active',
  children,
  dot,
  lang
}) {
  const [tone, ar, en] = T[status] || T.active;
  const L = lang || (typeof document !== 'undefined' && document.documentElement.lang === 'en' ? 'en' : 'ar');
  return /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: 6,
      height: 24,
      padding: '0 10px',
      borderRadius: 'var(--radius-sm)',
      background: 'var(--status-' + tone + '-bg)',
      color: 'var(--status-' + tone + '-fg)',
      font: '600 12px/1 var(--font-sans)',
      whiteSpace: 'nowrap'
    }
  }, dot && /*#__PURE__*/React.createElement("span", {
    style: {
      width: 6,
      height: 6,
      borderRadius: 99,
      background: 'currentColor'
    }
  }), children || (L === 'en' ? en : ar));
}
Object.assign(__ds_scope, { StatusBadge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/StatusBadge.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Toast.jsx
try { (() => {
const K = {
  success: ['circle-check', 'var(--track-green)'],
  error: ['circle-alert', 'var(--red-500)'],
  info: ['info', 'var(--blue-500)']
};
function Toast({
  tone = 'success',
  title,
  message,
  onClose
}) {
  const [i, c] = K[tone] || K.success;
  return /*#__PURE__*/React.createElement("div", {
    role: "status",
    style: {
      display: 'flex',
      gap: 12,
      alignItems: 'flex-start',
      width: 340,
      padding: '14px 16px',
      background: '#fff',
      borderRadius: 'var(--radius-lg)',
      boxShadow: 'var(--shadow-overlay)',
      border: '1px solid var(--border-default)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: i,
    size: 20,
    color: c
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 14px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, title), message && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 13px/1.5 var(--font-sans)',
      color: 'var(--text-secondary)',
      marginTop: 2
    }
  }, message)), onClose && /*#__PURE__*/React.createElement("button", {
    onClick: onClose,
    "aria-label": "\u0625\u063A\u0644\u0627\u0642",
    style: {
      border: 'none',
      background: 'none',
      cursor: 'pointer',
      color: 'var(--text-muted)',
      padding: 0
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "x",
    size: 16
  })));
}
Object.assign(__ds_scope, { Toast });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Toast.jsx", error: String((e && e.message) || e) }); }

// components/feedback/Tooltip.jsx
try { (() => {
function Tooltip({
  content,
  children
}) {
  const [o, setO] = React.useState(false);
  return /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      display: 'inline-flex'
    },
    onMouseEnter: () => setO(true),
    onMouseLeave: () => setO(false)
  }, children, o && /*#__PURE__*/React.createElement("span", {
    role: "tooltip",
    style: {
      position: 'absolute',
      bottom: 'calc(100% + 8px)',
      left: '50%',
      transform: 'translateX(-50%)',
      background: 'var(--track-dark)',
      color: '#fff',
      font: '500 12px/1.4 var(--font-sans)',
      padding: '6px 10px',
      borderRadius: 'var(--radius-sm)',
      whiteSpace: 'nowrap',
      zIndex: 50,
      boxShadow: 'var(--shadow-raised)'
    }
  }, content));
}
Object.assign(__ds_scope, { Tooltip });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/feedback/Tooltip.jsx", error: String((e && e.message) || e) }); }

// components/forms/Checkbox.jsx
try { (() => {
function Checkbox({
  label,
  checked,
  defaultChecked,
  onChange,
  disabled
}) {
  const [c, setC] = React.useState(!!defaultChecked);
  const on = checked !== undefined ? checked : c;
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: 8,
      cursor: disabled ? 'not-allowed' : 'pointer',
      opacity: disabled ? .5 : 1,
      font: '500 13px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    onClick: () => {
      if (disabled) return;
      setC(!on);
      onChange && onChange(!on);
    },
    style: {
      width: 18,
      height: 18,
      borderRadius: 'var(--radius-xs)',
      border: '1.5px solid ' + (on ? 'var(--track-blue)' : 'var(--border-strong)'),
      background: on ? 'var(--track-blue)' : '#fff',
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      transition: 'all var(--dur-fast)'
    }
  }, on && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "check",
    size: 13,
    strokeWidth: 3,
    color: "#fff"
  })), label);
}
Object.assign(__ds_scope, { Checkbox });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Checkbox.jsx", error: String((e && e.message) || e) }); }

// components/forms/Input.jsx
try { (() => {
function Input({
  label,
  placeholder,
  value,
  defaultValue,
  onChange,
  onBlur,
  icon,
  type = 'text',
  hint,
  error,
  disabled,
  required,
  size = 'md',
  dir,
  inputMode,
  autoFocus,
  name,
  style
}) {
  const [f, setF] = React.useState(false);
  const id = React.useId ? React.useId() : undefined;
  const h = size === 'sm' ? 34 : size === 'lg' ? 48 : 40;
  const bd = error ? 'var(--red-500)' : f ? 'var(--border-focus)' : 'var(--border-default)';
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 6,
      minWidth: 0,
      ...style
    }
  }, label && /*#__PURE__*/React.createElement("label", {
    htmlFor: id,
    style: {
      font: '600 13px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, label, required && /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      color: 'var(--red-600)',
      marginInlineStart: 3
    }
  }, "*")), /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      height: h,
      padding: '0 12px',
      background: disabled ? 'var(--gray-100)' : '#fff',
      border: '1px solid ' + bd,
      borderRadius: 'var(--radius-md)',
      boxShadow: f ? error ? '0 0 0 3px rgba(239,68,68,.15)' : 'var(--ring-focus)' : 'none',
      transition: 'box-shadow var(--dur-fast),border-color var(--dur-fast)'
    }
  }, icon && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 18,
    color: "var(--text-muted)"
  }), /*#__PURE__*/React.createElement("input", {
    id: id,
    name: name,
    type: type,
    dir: dir,
    inputMode: inputMode,
    autoFocus: autoFocus,
    placeholder: placeholder,
    value: value,
    defaultValue: defaultValue,
    onChange: onChange,
    disabled: disabled,
    "aria-invalid": !!error,
    "aria-required": required || undefined,
    onFocus: () => setF(true),
    onBlur: e => {
      setF(false);
      onBlur && onBlur(e);
    },
    style: {
      flex: 1,
      minWidth: 0,
      border: 'none',
      outline: 'none',
      background: 'transparent',
      font: '400 14px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  })), (hint || error) && /*#__PURE__*/React.createElement("span", {
    role: error ? 'alert' : undefined,
    style: {
      display: 'flex',
      gap: 4,
      alignItems: 'center',
      font: '400 12px/1.5 var(--font-sans)',
      color: error ? 'var(--red-600)' : 'var(--text-secondary)'
    }
  }, error && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "circle-alert",
    size: 13
  }), error || hint));
}
Object.assign(__ds_scope, { Input });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Input.jsx", error: String((e && e.message) || e) }); }

// components/forms/Select.jsx
try { (() => {
function Select({
  label,
  options = [],
  value,
  defaultValue,
  onChange,
  icon,
  size = 'md',
  error,
  disabled,
  required,
  style
}) {
  const h = size === 'sm' ? 34 : 40;
  const id = React.useId ? React.useId() : undefined;
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 6,
      minWidth: 0,
      ...style
    }
  }, label && /*#__PURE__*/React.createElement("label", {
    htmlFor: id,
    style: {
      font: '600 13px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, label, required && /*#__PURE__*/React.createElement("span", {
    "aria-hidden": "true",
    style: {
      color: 'var(--red-600)',
      marginInlineStart: 3
    }
  }, "*")), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'relative',
      display: 'flex',
      alignItems: 'center',
      height: h,
      background: disabled ? 'var(--gray-100)' : '#fff',
      border: '1px solid ' + (error ? 'var(--red-500)' : 'var(--border-default)'),
      borderRadius: 'var(--radius-md)'
    }
  }, icon && /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      insetInlineStart: 12,
      pointerEvents: 'none'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: icon,
    size: 16,
    color: "var(--text-muted)"
  })), /*#__PURE__*/React.createElement("select", {
    id: id,
    value: value,
    defaultValue: defaultValue,
    onChange: onChange,
    disabled: disabled,
    "aria-invalid": !!error,
    style: {
      appearance: 'none',
      WebkitAppearance: 'none',
      width: '100%',
      height: '100%',
      border: 'none',
      outline: 'none',
      background: 'transparent',
      font: '500 14px var(--font-sans)',
      color: 'var(--text-primary)',
      paddingInlineStart: icon ? 36 : 12,
      paddingInlineEnd: 34,
      cursor: disabled ? 'not-allowed' : 'pointer'
    }
  }, options.map(o => typeof o === 'string' ? /*#__PURE__*/React.createElement("option", {
    key: o
  }, o) : /*#__PURE__*/React.createElement("option", {
    key: o.value,
    value: o.value,
    disabled: o.disabled
  }, o.label))), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      insetInlineEnd: 10,
      pointerEvents: 'none'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "chevron-down",
    size: 16,
    color: "var(--text-secondary)"
  }))), error && /*#__PURE__*/React.createElement("span", {
    role: "alert",
    style: {
      display: 'flex',
      gap: 4,
      alignItems: 'center',
      font: '400 12px var(--font-sans)',
      color: 'var(--red-600)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "circle-alert",
    size: 13
  }), error));
}
Object.assign(__ds_scope, { Select });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Select.jsx", error: String((e && e.message) || e) }); }

// components/forms/Switch.jsx
try { (() => {
function Switch({
  label,
  checked,
  defaultChecked,
  onChange,
  disabled
}) {
  const [c, setC] = React.useState(!!defaultChecked);
  const on = checked !== undefined ? checked : c;
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: 10,
      cursor: 'pointer',
      opacity: disabled ? .5 : 1,
      font: '500 13px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    onClick: () => {
      if (disabled) return;
      setC(!on);
      onChange && onChange(!on);
    },
    style: {
      position: 'relative',
      width: 38,
      height: 22,
      borderRadius: 99,
      background: on ? 'var(--track-green)' : 'var(--gray-300)',
      transition: 'background var(--dur-base)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      top: 2,
      insetInlineStart: on ? 18 : 2,
      width: 18,
      height: 18,
      borderRadius: 99,
      background: '#fff',
      boxShadow: '0 1px 2px rgba(0,0,0,.2)',
      transition: 'inset-inline-start var(--dur-base) var(--ease-standard)'
    }
  })), label);
}
Object.assign(__ds_scope, { Switch });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/forms/Switch.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Breadcrumbs.jsx
try { (() => {
function Breadcrumbs({
  items = []
}) {
  const rtl = typeof document === 'undefined' || document.documentElement.dir !== 'ltr';
  return /*#__PURE__*/React.createElement("nav", {
    "aria-label": "breadcrumb",
    style: {
      display: 'flex',
      alignItems: 'center',
      flexWrap: 'wrap',
      gap: 6,
      font: '500 13px var(--font-sans)'
    }
  }, items.map((it, i) => {
    const last = i === items.length - 1;
    return /*#__PURE__*/React.createElement(React.Fragment, {
      key: i
    }, last || !it.onClick ? /*#__PURE__*/React.createElement("span", {
      "aria-current": last ? 'page' : undefined,
      style: {
        color: last ? 'var(--text-primary)' : 'var(--text-secondary)',
        fontWeight: last ? 700 : 500
      }
    }, it.label) : /*#__PURE__*/React.createElement("button", {
      onClick: it.onClick,
      style: {
        border: 'none',
        background: 'none',
        padding: 0,
        cursor: 'pointer',
        font: 'inherit',
        color: 'var(--blue-500)'
      }
    }, it.label), !last && /*#__PURE__*/React.createElement(__ds_scope.Icon, {
      name: rtl ? 'chevron-left' : 'chevron-right',
      size: 14,
      color: "var(--text-muted)"
    }));
  }));
}
Object.assign(__ds_scope, { Breadcrumbs });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Breadcrumbs.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Sidebar.jsx
try { (() => {
function Sidebar({
  items = [],
  active,
  onSelect,
  assetBase = 'assets/',
  footer
}) {
  const [hv, setHv] = React.useState(null);
  return /*#__PURE__*/React.createElement("aside", {
    style: {
      width: 'var(--sidebar-width)',
      flex: 'none',
      background: 'var(--surface-sidebar)',
      color: '#fff',
      display: 'flex',
      flexDirection: 'column',
      padding: '20px 12px',
      gap: 4,
      minHeight: '100%',
      boxSizing: 'border-box'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '0 8px 20px'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Logo, {
    variant: "reversed",
    height: 32,
    assetBase: assetBase
  })), items.map(it => {
    const on = it.id === active;
    return /*#__PURE__*/React.createElement("button", {
      key: it.id,
      onClick: () => onSelect && onSelect(it.id),
      onMouseEnter: () => setHv(it.id),
      onMouseLeave: () => setHv(null),
      style: {
        display: 'flex',
        alignItems: 'center',
        gap: 12,
        height: 42,
        padding: '0 14px',
        border: 'none',
        borderRadius: 'var(--radius-md)',
        cursor: 'pointer',
        textAlign: 'start',
        background: on ? 'var(--track-green)' : hv === it.id ? 'rgba(255,255,255,.08)' : 'transparent',
        color: on ? '#fff' : 'rgba(255,255,255,.82)',
        font: (on ? '700' : '500') + ' 14px var(--font-sans)',
        transition: 'background var(--dur-fast)'
      }
    }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
      name: it.icon,
      size: 19
    }), /*#__PURE__*/React.createElement("span", {
      style: {
        flex: 1
      }
    }, it.label), it.count != null && /*#__PURE__*/React.createElement("span", {
      style: {
        font: '600 11px var(--font-numeric)',
        background: 'rgba(255,255,255,.14)',
        borderRadius: 99,
        padding: '2px 8px'
      }
    }, it.count));
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1
    }
  }), footer);
}
Object.assign(__ds_scope, { Sidebar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Sidebar.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Stepper.jsx
try { (() => {
function Stepper({
  steps = [],
  current = 0
}) {
  return /*#__PURE__*/React.createElement("ol", {
    style: {
      display: 'flex',
      alignItems: 'flex-start',
      gap: 0,
      listStyle: 'none',
      margin: 0,
      padding: 0
    }
  }, steps.map((s, i) => {
    const done = i < current,
      on = i === current;
    return /*#__PURE__*/React.createElement("li", {
      key: i,
      "aria-current": on ? 'step' : undefined,
      style: {
        flex: 1,
        minWidth: 0,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: 8,
        position: 'relative'
      }
    }, i > 0 && /*#__PURE__*/React.createElement("span", {
      style: {
        position: 'absolute',
        top: 14,
        insetInlineEnd: 'calc(50% + 20px)',
        insetInlineStart: 'calc(-50% + 20px)',
        height: 2,
        borderRadius: 2,
        background: i <= current ? 'var(--track-green)' : 'var(--gray-200)'
      }
    }), /*#__PURE__*/React.createElement("span", {
      style: {
        width: 30,
        height: 30,
        borderRadius: 99,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        font: '700 13px var(--font-numeric)',
        background: done ? 'var(--track-green)' : on ? 'var(--track-blue)' : '#fff',
        color: done || on ? '#fff' : 'var(--text-secondary)',
        border: done || on ? 'none' : '1.5px solid var(--border-strong)',
        boxShadow: on ? '0 0 0 4px var(--blue-100)' : 'none'
      }
    }, done ? /*#__PURE__*/React.createElement(__ds_scope.Icon, {
      name: "check",
      size: 15,
      strokeWidth: 3
    }) : i + 1), /*#__PURE__*/React.createElement("span", {
      style: {
        font: (on ? '700' : '500') + ' 12px/1.4 var(--font-sans)',
        color: on ? 'var(--text-primary)' : 'var(--text-secondary)',
        textAlign: 'center'
      }
    }, s));
  }));
}
Object.assign(__ds_scope, { Stepper });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Stepper.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Tabs.jsx
try { (() => {
function Tabs({
  tabs = [],
  value,
  onChange,
  variant = 'underline'
}) {
  const [v, setV] = React.useState(value || tabs[0] && tabs[0].id);
  const cur = value !== undefined ? value : v;
  const seg = variant === 'segmented';
  return /*#__PURE__*/React.createElement("div", {
    role: "tablist",
    style: {
      display: 'flex',
      gap: seg ? 4 : 24,
      borderBottom: seg ? 'none' : '1px solid var(--border-default)',
      background: seg ? 'var(--gray-100)' : 'transparent',
      padding: seg ? 4 : 0,
      borderRadius: seg ? 'var(--radius-md)' : 0,
      width: seg ? 'fit-content' : undefined
    }
  }, tabs.map(t => {
    const on = t.id === cur;
    return /*#__PURE__*/React.createElement("button", {
      key: t.id,
      role: "tab",
      "aria-selected": on,
      onClick: () => {
        setV(t.id);
        onChange && onChange(t.id);
      },
      style: seg ? {
        height: 32,
        padding: '0 14px',
        border: 'none',
        borderRadius: 6,
        cursor: 'pointer',
        background: on ? '#fff' : 'transparent',
        boxShadow: on ? 'var(--shadow-xs)' : 'none',
        color: on ? 'var(--track-blue)' : 'var(--text-secondary)',
        font: (on ? '700' : '500') + ' 13px var(--font-sans)'
      } : {
        height: 42,
        padding: 0,
        border: 'none',
        background: 'none',
        cursor: 'pointer',
        borderBottom: '2px solid ' + (on ? 'var(--track-green)' : 'transparent'),
        marginBottom: -1,
        color: on ? 'var(--track-blue)' : 'var(--text-secondary)',
        font: (on ? '700' : '500') + ' 14px var(--font-sans)'
      }
    }, t.label, t.count != null && /*#__PURE__*/React.createElement("span", {
      style: {
        marginInlineStart: 6,
        font: '600 11px var(--font-numeric)',
        color: on ? 'var(--track-green)' : 'var(--text-muted)'
      }
    }, t.count));
  }));
}
Object.assign(__ds_scope, { Tabs });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Tabs.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Topbar.jsx
try { (() => {
function Topbar({
  userName = 'محمد أحمد',
  userRole = 'سوبر أدمن',
  searchPlaceholder,
  onMenu,
  notifications = true,
  onUserClick,
  children,
  end
}) {
  const en = typeof document !== 'undefined' && document.documentElement.lang === 'en';
  return /*#__PURE__*/React.createElement("header", {
    style: {
      height: 'var(--topbar-height)',
      flex: 'none',
      display: 'flex',
      alignItems: 'center',
      gap: 16,
      padding: '0 24px',
      background: '#fff',
      borderBottom: '1px solid var(--border-default)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "menu",
    label: en ? 'Menu' : 'القائمة',
    onClick: onMenu
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      height: 38,
      width: 320,
      maxWidth: '40%',
      padding: '0 12px',
      background: 'var(--gray-50)',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "search",
    size: 16,
    color: "var(--text-muted)"
  }), /*#__PURE__*/React.createElement("input", {
    "aria-label": searchPlaceholder || (en ? 'Search the platform' : 'بحث في المنصة'),
    placeholder: searchPlaceholder || (en ? 'Search the platform' : 'بحث في المنصة'),
    style: {
      flex: 1,
      minWidth: 0,
      border: 'none',
      background: 'transparent',
      outline: 'none',
      font: '400 13px var(--font-sans)'
    }
  })), children, /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1
    }
  }), end, /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "bell",
    label: en ? 'Notifications' : 'الإشعارات',
    dot: notifications
  }), /*#__PURE__*/React.createElement(__ds_scope.IconButton, {
    icon: "circle-help",
    label: en ? 'Help' : 'المساعدة'
  }), /*#__PURE__*/React.createElement("button", {
    onClick: onUserClick,
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      paddingInlineStart: 12,
      border: 'none',
      borderInlineStart: '1px solid var(--border-default)',
      background: 'none',
      cursor: onUserClick ? 'pointer' : 'default',
      textAlign: 'start'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 38,
      height: 38,
      borderRadius: 99,
      background: 'var(--blue-100)',
      color: 'var(--track-blue)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Icon, {
    name: "user",
    size: 20
  })), /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      font: '700 13px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, userName), /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, userRole))));
}
Object.assign(__ds_scope, { Topbar });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Topbar.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/AuditScreen.jsx
try { (() => {
function AuditTable({
  rows,
  dense
}) {
  const {
    DataTable,
    Button
  } = window.DS;
  const {
    t,
    lang
  } = useApp();
  const st = useStore();
  const [open, setOpen] = React.useState(null);
  const L = (p, c) => window.TRACK_I18N.lbl(lang, p, c);
  const V = x => Array.isArray(x) ? x.join('، ') : L('val', String(x));
  const vals = v => v ? Object.entries(v).map(([k, x]) => /*#__PURE__*/React.createElement("div", {
    key: k,
    style: {
      font: '400 12px/1.6 var(--font-sans)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, L('fld', k), ":"), " ", V(x))) : /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--text-muted)'
    }
  }, "\u2014");
  return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(DataTable, {
    dense: dense,
    columns: [{
      key: 'at',
      label: t('when'),
      numeric: true,
      render: r => fmtDateTime(r.at)
    }, {
      key: 'actor',
      label: t('actor'),
      render: r => r.actor ? st.uname(r.actor, lang) : /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-muted)'
        }
      }, "\u2014")
    }, {
      key: 'action',
      label: t('action'),
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          font: '600 13px var(--font-sans)',
          color: r.action === 'access.denied' || r.action === 'auth.failed' ? 'var(--red-600)' : 'var(--track-blue)'
        }
      }, L('act', r.action))
    }, {
      key: 'e',
      label: t('entity'),
      numeric: true,
      render: r => r.type ? L('ent', r.type) + (r.eid ? ' #' + r.eid : '') : '—'
    }, ...(dense ? [] : [{
      key: 'ip',
      label: t('ip'),
      numeric: true
    }]), {
      key: 'x',
      label: '',
      render: r => /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        onClick: () => setOpen(r)
      }, t('details'))
    }],
    rows: rows,
    empty: t('no_entries')
  }), open && /*#__PURE__*/React.createElement(window.DS.Dialog, {
    title: L('act', open.action),
    subtitle: fmtDateTime(open.at) + ' · ' + (open.actor ? st.uname(open.actor, lang) : '—'),
    onClose: () => setOpen(null),
    width: 560
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 12,
      marginBottom: 14
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 12,
      background: 'var(--red-50)',
      borderRadius: 8
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 12px var(--font-sans)',
      marginBottom: 6
    }
  }, t('before')), vals(open.old)), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 12,
      background: 'var(--green-50)',
      borderRadius: 8
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 12px var(--font-sans)',
      marginBottom: 6
    }
  }, t('after')), vals(open.neu))), /*#__PURE__*/React.createElement("dl", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'auto 1fr',
      gap: '6px 14px',
      margin: 0,
      font: '400 13px var(--font-sans)'
    }
  }, /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('entity')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0
    }
  }, L('ent', open.type), " #", open.eid ?? '—'), /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('ip')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-latin)'
    }
  }, open.ip), /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('browser')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-latin)'
    }
  }, open.ua))));
}
function AuditScreen({
  notify
}) {
  const {
    Select,
    Input,
    Button,
    Alert
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const [actor, setActor] = React.useState('');
  const [action, setAction] = React.useState('');
  const [type, setType] = React.useState('');
  const [from, setFrom] = React.useState('');
  const [to, setTo] = React.useState('');
  const loading = useFakeLoad(actor + action + type + from + to);
  const base = me.role === 'SUPER_ADMIN' ? st.S.audit : st.S.audit.filter(a => a.type !== 'user' && (a.type !== 'section' || st.S.sections.some(s => s.id === a.eid && st.canSee(me, s))));
  const rows = base.filter(a => (!actor || a.actor === +actor) && (!action || a.action === action) && (!type || a.type === type) && (!from || a.at.slice(0, 10) >= from) && (!to || a.at.slice(0, 10) <= to));
  const actions = [...new Set(st.S.audit.map(a => a.action))].sort();
  const types = [...new Set(st.S.audit.map(a => a.type).filter(Boolean))].sort();
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_audit'),
    subtitle: t('audit_sub'),
    actions: st.can(me, 'audit.export') && /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      icon: "download",
      onClick: () => notify(t('export'), rows.length + ' ' + t('rows') + ' · xlsx')
    }, t('export'))
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(170px,1fr))',
      gap: 12,
      marginBottom: 14
    }
  }, /*#__PURE__*/React.createElement(Select, {
    size: "sm",
    label: t('actor'),
    value: actor,
    onChange: e => setActor(e.target.value),
    options: [{
      value: '',
      label: t('all')
    }, ...st.S.users.map(u => ({
      value: u.id,
      label: lang === 'en' ? u.en : u.ar
    }))]
  }), /*#__PURE__*/React.createElement(Select, {
    size: "sm",
    label: t('action'),
    value: action,
    onChange: e => setAction(e.target.value),
    options: [{
      value: '',
      label: t('all')
    }, ...actions.map(x => ({
      value: x,
      label: window.TRACK_I18N.lbl(lang, 'act', x)
    }))]
  }), /*#__PURE__*/React.createElement(Select, {
    size: "sm",
    label: t('entity'),
    value: type,
    onChange: e => setType(e.target.value),
    options: [{
      value: '',
      label: t('all')
    }, ...types.map(x => ({
      value: x,
      label: window.TRACK_I18N.lbl(lang, 'ent', x)
    }))]
  }), /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    type: "date",
    label: t('date_from'),
    value: from,
    onChange: e => setFrom(e.target.value)
  }), /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    type: "date",
    label: t('date_to'),
    value: to,
    onChange: e => setTo(e.target.value)
  })), /*#__PURE__*/React.createElement(Alert, {
    tone: "info",
    icon: "lock",
    style: {
      marginBottom: 14
    }
  }, t('immutable'), " \xB7 ", rows.length, " ", t('rows')), loading ? /*#__PURE__*/React.createElement(window.DS.DataTable, {
    loading: true,
    columns: [{
      key: 'a',
      label: t('when')
    }, {
      key: 'b',
      label: t('actor')
    }, {
      key: 'c',
      label: t('action')
    }, {
      key: 'd',
      label: t('entity')
    }],
    rows: []
  }) : /*#__PURE__*/React.createElement(AuditTable, {
    rows: rows
  })));
}
Object.assign(window, {
  AuditTable,
  AuditScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/AuditScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/AuthScreens.jsx
try { (() => {
function LoginScreen({
  onLogin
}) {
  const {
    Input,
    Button,
    Logo,
    Alert
  } = window.DS;
  const {
    t,
    lang
  } = useApp();
  const st = useStore();
  const [email, setEmail] = React.useState('f.alotaibi@mu.edu.sa');
  const [pw, setPw] = React.useState('Track2026');
  const [err, setErr] = React.useState(null);
  const [fails, setFails] = React.useState(0);
  const [lock, setLock] = React.useState(0);
  const [busy, setBusy] = React.useState(false);
  React.useEffect(() => {
    if (lock <= 0) return;
    const x = setTimeout(() => setLock(lock - 1), 1000);
    return () => clearTimeout(x);
  }, [lock]);
  const submit = e => {
    e.preventDefault();
    if (lock > 0) return;
    setBusy(true);
    setTimeout(() => {
      setBusy(false);
      const u = st.S.users.find(x => x.email.toLowerCase() === email.trim().toLowerCase());
      if (!u || u.pw !== pw) {
        const f = fails + 1;
        setFails(f);
        st.audit(null, 'auth.failed', 'user', u ? u.id : null, null, {
          email
        });
        if (f >= 5) {
          setLock(60);
          setFails(0);
          setErr('lock');
        } else setErr('generic');
        return;
      }
      if (!u.active) {
        setErr('deactivated');
        return;
      }
      setErr(null);
      onLogin(u);
    }, 500);
  };
  const demo = [['m.alsalem@mu.edu.sa', 'SUPER_ADMIN'], ['n.aldosari@mu.edu.sa', 'BRANCH_COORDINATOR'], ['f.alotaibi@mu.edu.sa', 'INSTRUCTOR'], ['s.alrashidi@mu.edu.sa', 'inactive']];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      minHeight: '100vh',
      background: 'var(--surface-page)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      flex: '0 0 500px',
      maxWidth: '100%',
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      padding: '40px 64px',
      background: '#fff'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      marginBottom: 32
    }
  }, /*#__PURE__*/React.createElement(Logo, {
    variant: "lockup",
    height: 88,
    assetBase: "../../assets/"
  }), /*#__PURE__*/React.createElement(LangSwitch, null)), /*#__PURE__*/React.createElement("h1", {
    style: {
      margin: 0,
      font: 'var(--text-h2)'
    }
  }, t('login_title')), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '6px 0 22px',
      color: 'var(--text-secondary)'
    }
  }, t('login_sub')), /*#__PURE__*/React.createElement("form", {
    onSubmit: submit,
    noValidate: true,
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 16
    }
  }, err === 'generic' && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger"
  }, t('login_generic')), err === 'lock' && lock > 0 && /*#__PURE__*/React.createElement(Alert, {
    tone: "warning",
    icon: "timer"
  }, t('login_locked', {
    s: lock
  })), err === 'deactivated' && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger",
    icon: "user-x"
  }, t('login_deactivated')), /*#__PURE__*/React.createElement(Input, {
    label: t('email'),
    icon: "mail",
    type: "email",
    value: email,
    onChange: e => setEmail(e.target.value)
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('password'),
    icon: "lock",
    type: "password",
    value: pw,
    onChange: e => setPw(e.target.value)
  }), /*#__PURE__*/React.createElement(Button, {
    type: "submit",
    size: "lg",
    fullWidth: true,
    loading: busy,
    disabled: lock > 0
  }, lock > 0 ? lock + 's' : t('sign_in'))), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '16px 0 0',
      font: '400 12px/1.6 var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, t('no_register')), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 28,
      paddingTop: 18,
      borderTop: '1px dashed var(--border-strong)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '600 12px var(--font-sans)',
      color: 'var(--text-secondary)',
      marginBottom: 8
    }
  }, t('demo_accounts'), " \xB7 Track2026"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexWrap: 'wrap',
      gap: 6
    }
  }, demo.map(([e, r]) => /*#__PURE__*/React.createElement("button", {
    key: e,
    onClick: () => {
      setEmail(e);
      setPw('Track2026');
      setErr(null);
    },
    style: {
      height: 28,
      padding: '0 10px',
      border: '1px solid var(--border-default)',
      borderRadius: 6,
      background: email === e ? 'var(--blue-50)' : '#fff',
      cursor: 'pointer',
      font: '500 12px var(--font-sans)',
      color: 'var(--track-blue)'
    }
  }, r === 'inactive' ? lang === 'en' ? 'Deactivated' : 'حساب معطّل' : t('role_' + r)))))), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1,
      display: 'flex',
      flexDirection: 'column',
      justifyContent: 'center',
      gap: 36,
      padding: '0 64px',
      background: 'var(--surface-sidebar)',
      color: '#fff'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 34px/1.3 var(--font-arabic)'
    }
  }, "\u0645\u0639\u064B\u0627 .. \u0646\u062D\u0648 \u0623\u062B\u0631 \u0623\u0643\u0627\u062F\u064A\u0645\u064A \u0623\u0643\u0628\u0631"), /*#__PURE__*/React.createElement("div", {
    dir: "ltr",
    style: {
      font: '400 18px var(--font-latin)',
      opacity: .85,
      marginTop: 8,
      textAlign: lang === 'en' ? 'left' : 'right'
    }
  }, "Track Today, a Brighter Tomorrow"), /*#__PURE__*/React.createElement("div", {
    style: {
      width: 48,
      height: 4,
      borderRadius: 2,
      background: 'var(--track-green)',
      marginTop: 18
    }
  })), /*#__PURE__*/React.createElement("img", {
    src: "../../assets/banner-initiatives.png",
    alt: "",
    style: {
      width: '100%',
      maxWidth: 760,
      height: 'auto',
      borderRadius: 'var(--radius-xl)',
      boxShadow: 'var(--shadow-overlay)',
      display: 'block'
    }
  })));
}
function ProfileScreen({
  notify
}) {
  const {
    Input,
    Button,
    Card
  } = window.DS;
  const {
    t,
    me,
    lang,
    setLang,
    go
  } = useApp();
  const st = useStore();
  const [f, setF] = React.useState({
    cur: '',
    nw: '',
    cf: ''
  });
  const [e, setE] = React.useState({});
  const [busy, setBusy] = React.useState(false);
  const policy = v => v.length >= 8 && /[A-Za-z\u0600-\u06FF]/.test(v) && /\d/.test(v);
  const validate = (k, all) => {
    const n = {
      ...e
    };
    if (k === 'cur' || all) n.cur = !f.cur ? t('required') : null;
    if (k === 'nw' || all) n.nw = !f.nw ? t('required') : !policy(f.nw) ? t('pw_policy') : f.nw === me.pw ? t('pw_same') : null;
    if (k === 'cf' || all) n.cf = !f.cf ? t('required') : f.cf !== f.nw ? t('pw_mismatch') : null;
    setE(n);
    return n;
  };
  const submit = ev => {
    ev.preventDefault();
    const n = validate(null, true);
    if (Object.values(n).some(Boolean)) return;
    if (f.cur !== me.pw) {
      setE({
        ...n,
        cur: t('pw_wrong')
      });
      return;
    }
    setBusy(true);
    setTimeout(() => {
      me.pw = f.nw;
      st.audit(me.id, 'user.password_changed', 'user', me.id, null, {
        sessions_revoked: true
      });
      st.emit();
      setBusy(false);
      setF({
        cur: '',
        nw: '',
        cf: ''
      });
      notify(t('pw_changed'), t('pw_changed_msg'));
    }, 600);
  };
  return /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 880
    }
  }, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('profile'),
    crumbs: [{
      label: t('nav_home'),
      onClick: () => go('home')
    }, {
      label: t('profile')
    }]
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(320px,1fr))',
      gap: 16
    }
  }, /*#__PURE__*/React.createElement(Card, {
    title: lang === 'en' ? me.en : me.ar
  }, /*#__PURE__*/React.createElement("dl", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'auto 1fr',
      gap: '10px 16px',
      margin: 0,
      font: '400 13px var(--font-sans)'
    }
  }, /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('name_ar')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0
    }
  }, me.ar), /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('name_en')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0
    },
    dir: "ltr"
  }, me.en), /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('email')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-latin)'
    }
  }, me.email), /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('role')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0
    }
  }, t('role_' + me.role)), me.branches.length > 0 && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('branches')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0
    }
  }, me.branches.map(b => st.branchLabel(b, lang)).join('، '))), /*#__PURE__*/React.createElement("dt", {
    style: {
      color: 'var(--text-secondary)'
    }
  }, t('language')), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0
    }
  }, /*#__PURE__*/React.createElement(LangSwitch, null)))), /*#__PURE__*/React.createElement(Card, {
    title: t('change_pw')
  }, /*#__PURE__*/React.createElement("form", {
    onSubmit: submit,
    noValidate: true,
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, [['cur', 'current_pw'], ['nw', 'new_pw'], ['cf', 'confirm_pw']].map(([k, l]) => /*#__PURE__*/React.createElement(Input, {
    key: k,
    label: t(l),
    type: "password",
    icon: "lock",
    value: f[k],
    error: e[k],
    hint: k === 'nw' ? t('pw_rule') : undefined,
    onChange: ev => setF({
      ...f,
      [k]: ev.target.value
    }),
    style: {}
  })), /*#__PURE__*/React.createElement("div", {
    onBlur: () => {}
  }), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(Button, {
    type: "submit",
    loading: busy,
    icon: "key-round"
  }, t('change_pw')))))));
}
function RefusalScreen({
  kind
}) {
  const {
    EmptyState,
    Button
  } = window.DS;
  const {
    t,
    go
  } = useApp();
  const f = kind === '403';
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: '60vh'
    }
  }, /*#__PURE__*/React.createElement(Panel, {
    style: {
      maxWidth: 560,
      width: '100%'
    }
  }, /*#__PURE__*/React.createElement(EmptyState, {
    tone: f ? 'danger' : 'brand',
    icon: f ? 'shield-x' : 'file-question',
    title: /*#__PURE__*/React.createElement("span", {
      style: {
        font: '700 20px var(--font-sans)'
      }
    }, f ? t('forbidden') : t('notfound')),
    message: f ? t('forbidden_msg') : t('notfound_msg'),
    action: /*#__PURE__*/React.createElement(Button, {
      icon: "house",
      onClick: () => go('home')
    }, t('back_home'))
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center',
      paddingBottom: 20,
      font: '700 44px var(--font-numeric)',
      color: 'var(--gray-200)'
    }
  }, f ? '403' : '404')));
}
Object.assign(window, {
  LoginScreen,
  ProfileScreen,
  RefusalScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/AuthScreens.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/CourseImport.jsx
try { (() => {
function CourseImportDialog({
  onClose,
  notify
}) {
  const {
    Dialog,
    Button,
    Alert,
    DataTable,
    StatusBadge,
    Icon,
    Checkbox
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const ref = React.useRef(null);
  const [res, setRes] = React.useState(null);
  const [err, setErr] = React.useState(null);
  const [name, setName] = React.useState('');
  const [busy, setBusy] = React.useState(false);
  const [done, setDone] = React.useState(null);
  const [mkProg, setMkProg] = React.useState(true);
  const [raw, setRaw] = React.useState(null);
  const HDR = ['رمز المقرر', 'اسم المقرر بالعربية', 'اسم المقرر بالإنجليزية', 'البرنامج', 'رمز البرنامج'];
  const findProg = (nm, code) => st.S.programs.find(p => code && p.code.toUpperCase() === code.toUpperCase() || nm && (p.ar === nm || p.en.toLowerCase() === nm.toLowerCase()));
  const validate = (p, mk) => {
    const H = p.headers.map(h => String(h || '').trim());
    const col = re => H.findIndex(h => re.test(h));
    const c = {
      code: col(/رمز المقرر|course.?code|^code$/i),
      ar: col(/اسم المقرر بالعربي|اسم المقرر$|course.?name.?ar|^اسم المقرر/i),
      en: col(/بالإنجليزي|الانجليزي|name.?en|english/i),
      pg: col(/^البرنامج|البرامج|program(?!.?code)/i),
      pc: col(/رمز البرنامج|program.?code/i)
    };
    if (c.code < 0 || c.ar < 0 || c.pg < 0 && c.pc < 0) throw new Error('cols');
    const seen = {};
    const newProgs = {};
    return p.rows.map((r, k) => {
      const n = k + 2;
      const g = i => i >= 0 ? String(r[i] || '').trim() : '';
      const u = {
        id: n,
        n,
        code: g(c.code).toUpperCase(),
        ar: g(c.ar),
        en: g(c.en),
        pgRaw: g(c.pg),
        pcRaw: g(c.pc)
      };
      const errs = [],
        warns = [];
      if (!u.code) errs.push(en ? 'Course code missing' : 'رمز المقرر مفقود');else if (seen[u.code]) errs.push((en ? 'Duplicate of row ' : 'مكرر مع الصف ') + seen[u.code]);else seen[u.code] = n;
      if (!u.ar) errs.push(en ? 'Arabic name missing' : 'اسم المقرر بالعربية مفقود');
      if (!u.en && u.ar) {
        u.en = u.ar;
        warns.push(en ? 'English name copied from Arabic' : 'نُسخ الاسم العربي للإنجليزي');
      }
      const names = u.pgRaw.split(/[،,;]+/).map(x => x.trim()).filter(Boolean);
      const codes = u.pcRaw.split(/[،,;]+/).map(x => x.trim()).filter(Boolean);
      const len = Math.max(names.length, codes.length);
      u.progs = [];
      u.create = [];
      if (!len) errs.push(en ? 'Program missing' : 'البرنامج مفقود');
      for (let i = 0; i < len; i++) {
        const nm = names[i] || '',
          cd = codes[i] || '';
        const pr = findProg(nm, cd);
        if (pr) {
          if (!u.progs.includes(pr.id)) u.progs.push(pr.id);
        } else {
          const key = (cd || nm).toUpperCase();
          if (mk && nm) {
            u.create.push({
              key,
              ar: nm,
              code: cd.toUpperCase()
            });
            if (!newProgs[key]) {
              newProgs[key] = 1;
            }
            warns.push((en ? 'New program will be created: ' : 'سيُنشأ برنامج جديد: ') + nm);
          } else errs.push((en ? 'Unknown program: ' : 'برنامج غير معروف: ') + (nm || cd));
        }
      }
      const ex = u.code && st.S.courses.find(x => x.code.toUpperCase() === u.code);
      u.exists = !!ex;
      if (ex && !errs.length) warns.push(en ? 'Course exists — names and programs will be updated' : 'المقرر موجود — سيُحدَّث اسمه وبرامجه');
      u.status = errs.length ? 'error' : warns.length ? 'warning' : 'valid';
      u.reason = [...errs, ...warns].join(' · ') || (en ? 'New course' : 'مقرر جديد');
      return u;
    });
  };
  const load = (p, nm) => {
    setName(nm);
    setErr(null);
    setRaw(p);
    setRes(validate(p, mkProg));
  };
  const onFile = async ev => {
    const f = ev.target.files && ev.target.files[0];
    ev.target.value = '';
    if (!f) return;
    try {
      const p = await window.parseRosterFile(f);
      load(p, p.name);
    } catch (x) {
      setRes(null);
      setErr(x.message === 'cols' ? en ? 'The file needs “Course code”, “Course name (Arabic)” and “Program” columns. Download the template.' : 'يجب أن يحتوي الملف على أعمدة «رمز المقرر» و«اسم المقرر بالعربية» و«البرنامج». حمّل القالب.' : en ? 'Unsupported file. Use xlsx or csv.' : 'نوع الملف غير مدعوم. استخدم xlsx أو csv.');
    }
  };
  const sample = () => load({
    headers: HDR,
    rows: [['HR201', 'مبادئ إدارة الموارد البشرية', 'Principles of HRM', 'الموارد البشرية', ''], ['ACC110', 'مبادئ المحاسبة', 'Principles of Accounting', 'المحاسبة والضرائب', ''], ['CS212', 'برمجة 2', 'Programming 2', 'البرمجة وعلوم الحاسب، تطوير التطبيقات', ''], ['MKT150', 'مبادئ التسويق', '', 'التسويق', ''], ['DS101', 'مقدمة في علم البيانات', 'Intro to Data Science', 'علم البيانات', 'DS'], ['LOG120', 'إدارة سلاسل الإمداد', 'Supply Chain Management', '', 'LOG'], ['', 'مقرر بدون رمز', 'No code', 'التسويق', ''], ['HR201', 'مبادئ إدارة الموارد البشرية', 'Principles of HRM', 'الموارد البشرية', '']]
  }, 'college_courses.xlsx');
  React.useEffect(() => {
    if (raw) setRes(validate(raw, mkProg));
  }, [mkProg]);
  const ok = res ? res.filter(r => r.status !== 'error') : [];
  const run = () => {
    setBusy(true);
    setTimeout(() => {
      const created = {};
      let nc = 0,
        uc = 0,
        np = 0;
      ok.forEach((r, i) => {
        const ids = r.progs.slice();
        r.create.forEach(cp => {
          if (!created[cp.key]) {
            const code = cp.code || 'P' + (st.S.programs.length + 1);
            const p = {
              id: Date.now() + 1000 + np,
              code,
              ar: cp.ar,
              en: cp.ar,
              unit: 'admin',
              active: true
            };
            st.S.programs.push(p);
            created[cp.key] = p.id;
            np++;
            st.audit(me.id, 'program.created', 'program', p.id, null, {
              code,
              name_ar: p.ar,
              source: name
            });
          }
          ids.push(created[cp.key]);
        });
        const ex = st.S.courses.find(x => x.code.toUpperCase() === r.code);
        if (ex) {
          const old = {
            ar: ex.ar,
            programs: (ex.programs || []).map(x => st.programName(x, 'ar'))
          };
          Object.assign(ex, {
            ar: r.ar,
            en: r.en,
            programs: [...new Set(ids)]
          });
          st.audit(me.id, 'course.updated', 'course', ex.id, old, {
            ar: ex.ar,
            programs: ex.programs.map(x => st.programName(x, 'ar'))
          });
          uc++;
        } else {
          const c = {
            id: Date.now() + i,
            code: r.code,
            ar: r.ar,
            en: r.en,
            programs: [...new Set(ids)]
          };
          st.S.courses.push(c);
          st.audit(me.id, 'course.created', 'course', c.id, null, {
            code: c.code,
            ar: c.ar,
            programs: c.programs.map(x => st.programName(x, 'ar'))
          });
          nc++;
        }
      });
      st.audit(me.id, 'course.bulk_imported', 'course', null, null, {
        file: name,
        rows: res.length,
        created: nc,
        updated: uc,
        programs_created: np,
        skipped: res.length - ok.length
      });
      st.emit();
      setBusy(false);
      setDone({
        nc,
        uc,
        np,
        sk: res.length - ok.length
      });
      notify(en ? 'Courses imported' : 'تم استيراد المقررات', nc + uc + (en ? ' courses' : ' مقرر'));
    }, 700);
  };
  const cnt = k => res ? res.filter(r => r.status === k).length : 0;
  return /*#__PURE__*/React.createElement(Dialog, {
    title: en ? 'Import college courses from Excel' : 'استيراد مقررات الكلية من Excel',
    onClose: onClose,
    width: 1000,
    footer: done ? /*#__PURE__*/React.createElement(Button, {
      onClick: onClose
    }, en ? 'Done' : 'تم') : /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      icon: "book-open",
      loading: busy,
      disabled: !ok.length,
      onClick: run
    }, (en ? 'Import ' : 'استيراد ') + ok.length + (en ? ' courses' : ' مقرر')))
  }, done ? /*#__PURE__*/React.createElement(Alert, {
    tone: "success",
    title: en ? 'Import completed' : 'اكتمل الاستيراد'
  }, en ? `${done.nc} new courses, ${done.uc} updated, ${done.np} new programs, ${done.sk} rows skipped.` : `${done.nc} مقرر جديد، ${done.uc} مقرر مُحدَّث، ${done.np} برنامج جديد، ${done.sk} صف مرفوض.`) : /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      border: '2px dashed var(--border-strong)',
      borderRadius: 'var(--radius-lg)',
      padding: '22px 16px',
      textAlign: 'center',
      background: 'var(--gray-50)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "file-spreadsheet",
    size: 30,
    color: "var(--track-green)",
    style: {
      margin: '0 auto 8px'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 14px var(--font-sans)'
    }
  }, en ? 'Upload the college courses file (xlsx or csv)' : 'ارفع ملف مقررات الكلية (xlsx أو csv)'), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 12px/1.6 var(--font-sans)',
      color: 'var(--text-secondary)',
      marginTop: 4
    }
  }, en ? 'Columns: course code, Arabic name, program (required), English name and program code (optional). A course can belong to several programs — separate them with a comma.' : 'الأعمدة: رمز المقرر، اسم المقرر بالعربية، البرنامج (إلزامية)، والاسم بالإنجليزية ورمز البرنامج (اختيارية). يمكن ربط المقرر بأكثر من برنامج بفصلها بفاصلة.'), /*#__PURE__*/React.createElement("input", {
    ref: ref,
    type: "file",
    accept: ".xlsx,.csv",
    onChange: onFile,
    style: {
      display: 'none'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      justifyContent: 'center',
      marginTop: 12,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "download",
    onClick: () => downloadTemplate('track_courses_template.csv', [HDR, ['CS212', 'برمجة 2', 'Programming 2', 'البرمجة وعلوم الحاسب، تطوير التطبيقات', ''], ['HR201', 'مبادئ إدارة الموارد البشرية', 'Principles of HRM', 'الموارد البشرية', 'HR']])
  }, en ? 'Download template' : 'تنزيل القالب'), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "folder-open",
    onClick: () => ref.current && ref.current.click()
  }, en ? 'Choose file' : 'اختيار ملف'), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "ghost",
    onClick: sample
  }, en ? 'Use sample file' : 'استخدام ملف تجريبي'))), err && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger"
  }, err), res && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 10,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '600 13px var(--font-sans)'
    }
  }, name, " \xB7 ", res.length, " ", en ? 'rows' : 'صف', " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--green-700)'
    }
  }, cnt('valid'), " ", en ? 'new' : 'جديد'), " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--amber-600)'
    }
  }, cnt('warning'), " ", en ? 'with notes' : 'بملاحظات'), " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--red-600)'
    }
  }, cnt('error'), " ", en ? 'rejected' : 'مرفوض')), /*#__PURE__*/React.createElement(Checkbox, {
    label: en ? 'Create programs that don’t exist yet' : 'إنشاء البرامج غير الموجودة تلقائيًا',
    checked: mkProg,
    onChange: setMkProg
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      maxHeight: 360,
      overflow: 'auto'
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: 'n',
      label: en ? 'Row' : 'الصف',
      numeric: true
    }, {
      key: 'code',
      label: t('code'),
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r.code || '—')
    }, {
      key: 'ar',
      label: en ? 'Course' : 'المقرر',
      render: r => r.ar || '—'
    }, {
      key: 'p',
      label: en ? 'Programs' : 'البرامج',
      wrap: true,
      render: r => [...r.progs.map(x => st.programName(x, lang)), ...r.create.map(c => c.ar + ' ✚')].join('، ') || r.pgRaw || r.pcRaw || '—'
    }, {
      key: 's',
      label: en ? 'Result' : 'النتيجة',
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.status
      })
    }, {
      key: 'r',
      label: en ? 'Details' : 'التفاصيل',
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          font: '400 12px var(--font-sans)',
          color: r.status === 'error' ? 'var(--red-600)' : 'var(--text-secondary)'
        }
      }, r.reason)
    }],
    rows: res
  })))));
}
window.CourseImportDialog = CourseImportDialog;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/CourseImport.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/DashboardParts.jsx
try { (() => {
const DK = {
  panel: 'rgba(30,30,30,0.82)',
  panel2: 'rgba(40,40,40,0.9)',
  border: 'rgba(255,255,255,0.13)',
  border2: 'rgba(255,255,255,0.07)',
  teal: '#3bb8c3',
  teal2: '#2a9da8',
  coral: '#e07a6a',
  gold: '#d4a84b',
  blue: '#4a90c4',
  text: '#f0f0f0',
  text2: '#c0c0c0',
  text3: '#909090',
  red: '#e05555',
  font: "'Cairo','Tajawal',sans-serif"
};
const DK_BR = {
  majmaah: DK.gold,
  zulfi: DK.blue,
  rumah: DK.teal
};
const DK_CYC = [DK.blue, DK.teal, DK.gold, DK.coral];
const dkTone = p => p >= 90 ? DK.teal : p >= 70 ? DK.gold : DK.coral;
function DkCell({
  title,
  children,
  style
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: DK.panel,
      padding: '1.1rem 1rem',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      textAlign: 'center',
      minHeight: 130,
      position: 'relative',
      overflow: 'hidden',
      ...style
    }
  }, title && /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      top: 8,
      insetInlineStart: 11,
      fontSize: 11,
      color: DK.text3,
      fontWeight: 600
    }
  }, title), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: title ? 20 : 0,
      width: '100%',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center'
    }
  }, children));
}
function DkKpi({
  icon,
  label,
  value,
  sub
}) {
  const {
    Icon
  } = window.DS;
  return /*#__PURE__*/React.createElement(DkCell, null, /*#__PURE__*/React.createElement("div", {
    style: {
      width: 54,
      height: 54,
      border: '2px solid ' + DK.border,
      borderRadius: 8,
      background: 'rgba(255,255,255,0.06)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      marginBottom: 8
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: icon,
    size: 26,
    color: DK.text2
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      color: DK.text3,
      marginBottom: 4
    }
  }, label), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 34,
      fontWeight: 900,
      color: DK.red,
      lineHeight: 1,
      marginTop: 4
    }
  }, value), sub && /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      color: DK.text3,
      marginTop: 4
    }
  }, sub));
}
function DkDonut({
  segs,
  size = 110,
  hole = .72,
  center
}) {
  const tot = segs.reduce((a, s) => a + s.v, 0) || 1;
  let acc = 0;
  const g = segs.map(s => {
    const a = acc / tot * 360;
    acc += s.v;
    return `${s.c} ${a}deg ${acc / tot * 360}deg`;
  }).join(',');
  return /*#__PURE__*/React.createElement("div", {
    style: {
      width: size,
      height: size,
      borderRadius: '50%',
      background: `conic-gradient(${g})`,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      width: size * hole,
      height: size * hole,
      borderRadius: '50%',
      background: '#262626',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontWeight: 800,
      fontSize: size / 6,
      color: DK.text
    }
  }, center));
}
function DkGauge({
  pct
}) {
  return /*#__PURE__*/React.createElement("svg", {
    viewBox: "0 0 110 64",
    style: {
      width: 130,
      height: 76
    }
  }, /*#__PURE__*/React.createElement("defs", null, /*#__PURE__*/React.createElement("linearGradient", {
    id: "dkgg",
    x1: "0%",
    y1: "0%",
    x2: "100%",
    y2: "0%"
  }, /*#__PURE__*/React.createElement("stop", {
    offset: "0%",
    stopColor: DK.teal
  }), /*#__PURE__*/React.createElement("stop", {
    offset: "100%",
    stopColor: DK.gold
  }))), /*#__PURE__*/React.createElement("path", {
    d: "M10 60 A45 45 0 0 1 100 60",
    fill: "none",
    stroke: "rgba(255,255,255,0.12)",
    strokeWidth: "11",
    strokeLinecap: "round"
  }), /*#__PURE__*/React.createElement("path", {
    d: "M10 60 A45 45 0 0 1 100 60",
    fill: "none",
    stroke: "url(#dkgg)",
    strokeWidth: "11",
    strokeLinecap: "round",
    strokeDasharray: "141",
    strokeDashoffset: 141 - pct / 100 * 141,
    style: {
      transition: 'stroke-dashoffset .8s'
    }
  }), /*#__PURE__*/React.createElement("text", {
    x: "55",
    y: "54",
    textAnchor: "middle",
    fill: DK.red,
    fontSize: "16",
    fontWeight: "900",
    fontFamily: "Cairo"
  }, pct, "%"));
}
function DkShutters({
  data,
  h = 90
}) {
  const mx = Math.max(1, ...data.map(s => s.v));
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      justifyContent: 'center',
      gap: 8,
      height: h + 60
    }
  }, data.map(s => /*#__PURE__*/React.createElement("div", {
    key: s.n,
    style: {
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      gap: 3
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      fontWeight: 700,
      color: DK.text2
    }
  }, s.v), /*#__PURE__*/React.createElement("div", {
    style: {
      width: 20,
      height: Math.round(s.v / mx * h),
      background: s.c,
      borderRadius: '2px 2px 0 0',
      transition: 'height .8s'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 10,
      color: DK.text3,
      writingMode: 'vertical-rl',
      height: 48,
      overflow: 'hidden'
    }
  }, s.n))));
}
function DkProg3D({
  data,
  h = 88
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      justifyContent: 'center',
      gap: 9,
      height: h + 50
    }
  }, data.map((p, i) => {
    const c = DK_CYC[i % 4];
    return /*#__PURE__*/React.createElement("div", {
      key: p.n,
      style: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: 3
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        fontSize: 11,
        fontWeight: 700,
        color: DK.text2
      }
    }, p.p, "%"), /*#__PURE__*/React.createElement("div", {
      style: {
        width: 22,
        height: Math.round(p.p / 100 * h),
        background: c,
        borderRadius: '2px 2px 0 0',
        position: 'relative',
        transition: 'height .8s'
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        position: 'absolute',
        top: 0,
        right: -4,
        width: 4,
        height: '100%',
        background: c,
        filter: 'brightness(.7)'
      }
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        fontSize: 10,
        color: DK.text3,
        maxWidth: 40,
        lineHeight: 1.2,
        height: 26,
        overflow: 'hidden'
      }
    }, p.n));
  }));
}
function DkLine({
  labels,
  series,
  h = 200
}) {
  const W = 600,
    P = 24,
    mx = Math.max(1, ...series.flatMap(s => s.data));
  const n = labels.length;
  const x = i => P + (n < 2 ? 0 : i * (W - 2 * P) / (n - 1)),
    y = v => h - P - v / mx * (h - 2 * P);
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("svg", {
    viewBox: `0 0 ${W} ${h}`,
    style: {
      width: '100%',
      height: h,
      display: 'block'
    }
  }, [0, .25, .5, .75, 1].map(t => /*#__PURE__*/React.createElement("line", {
    key: t,
    x1: P,
    x2: W - P,
    y1: P + t * (h - 2 * P),
    y2: P + t * (h - 2 * P),
    stroke: "rgba(255,255,255,0.06)"
  })), series.map(s => {
    const pts = s.data.map((v, i) => x(i) + ',' + y(v)).join(' ');
    return /*#__PURE__*/React.createElement("g", {
      key: s.label
    }, /*#__PURE__*/React.createElement("polygon", {
      points: `${x(0)},${h - P} ${pts} ${x(n - 1)},${h - P}`,
      fill: s.c,
      opacity: ".08"
    }), /*#__PURE__*/React.createElement("polyline", {
      points: pts,
      fill: "none",
      stroke: s.c,
      strokeWidth: "2.2",
      strokeLinejoin: "round"
    }), s.data.map((v, i) => /*#__PURE__*/React.createElement("circle", {
      key: i,
      cx: x(i),
      cy: y(v),
      r: "3.2",
      fill: s.c
    })));
  }), labels.map((l, i) => /*#__PURE__*/React.createElement("text", {
    key: i,
    x: x(i),
    y: h - 6,
    textAnchor: "middle",
    fontSize: "10",
    fill: "rgba(255,255,255,0.4)",
    fontFamily: "Cairo"
  }, l))), /*#__PURE__*/React.createElement(DkLegend, {
    items: series.map(s => [s.label, s.c])
  }));
}
function DkGroup({
  labels,
  series,
  h = 180,
  suffix = ''
}) {
  const mx = Math.max(1, ...series.flatMap(s => s.data));
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      gap: 6,
      height: h + 34,
      overflowX: 'auto',
      paddingTop: 14
    }
  }, labels.map((l, i) => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      flex: '1 0 26px',
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      gap: 4
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      gap: 2,
      height: h
    }
  }, series.map(s => /*#__PURE__*/React.createElement("div", {
    key: s.label,
    title: s.label + ': ' + s.data[i] + suffix,
    style: {
      width: series.length > 1 ? 9 : 22,
      height: Math.max(2, s.data[i] / mx * h),
      background: s.c,
      opacity: s.op || .8,
      borderRadius: '3px 3px 0 0',
      position: 'relative'
    }
  }, series.length === 1 && /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      top: -16,
      left: '50%',
      transform: 'translateX(-50%)',
      fontSize: 10,
      fontWeight: 700,
      color: DK.text2,
      whiteSpace: 'nowrap'
    }
  }, s.data[i], suffix)))), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 10,
      color: DK.text3,
      whiteSpace: 'nowrap'
    }
  }, l)))), series.length > 1 && /*#__PURE__*/React.createElement(DkLegend, {
    items: series.map(s => [s.label, s.c])
  }));
}
function DkLegend({
  items
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 14,
      justifyContent: 'center',
      flexWrap: 'wrap',
      marginTop: 8
    }
  }, items.map(([l, c]) => /*#__PURE__*/React.createElement("span", {
    key: l,
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 5,
      fontSize: 11,
      color: DK.text2
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 10,
      height: 10,
      borderRadius: 2,
      background: c
    }
  }), l)));
}
function DkPanel({
  title,
  children,
  style
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: DK.panel2,
      border: '1px solid ' + DK.border,
      borderRadius: 10,
      padding: '1.3rem 1.2rem',
      minWidth: 0,
      ...style
    }
  }, title && /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 15,
      fontWeight: 800,
      color: DK.teal,
      marginBottom: 16,
      display: 'flex',
      alignItems: 'center',
      gap: 10
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 3,
      height: 16,
      background: `linear-gradient(to bottom,${DK.teal},${DK.gold})`,
      borderRadius: 2,
      flexShrink: 0
    }
  }), title), children);
}
function DkKcard({
  icon,
  v,
  l,
  accent,
  top
}) {
  const {
    Icon
  } = window.DS;
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: DK.panel2,
      border: '1px solid ' + DK.border,
      borderTop: top ? '3px solid ' + top : undefined,
      borderRadius: 10,
      padding: '1.2rem .9rem',
      textAlign: 'center',
      position: 'relative',
      overflow: 'hidden'
    }
  }, !top && /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      top: 0,
      left: 0,
      right: 0,
      height: 2,
      background: `linear-gradient(90deg,${DK.teal},${DK.gold})`
    }
  }), icon && /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 8,
      display: 'flex',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: icon,
    size: 24,
    color: accent || DK.text2
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 30,
      fontWeight: 900,
      color: accent || DK.text,
      lineHeight: 1
    }
  }, v), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 12,
      color: DK.text3,
      marginTop: 6
    }
  }, l));
}
function DkBar({
  label,
  value,
  c
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 14
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      fontSize: 13,
      marginBottom: 6
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      color: DK.text2
    }
  }, label), /*#__PURE__*/React.createElement("b", {
    style: {
      color: c
    }
  }, value, "%")), /*#__PURE__*/React.createElement("div", {
    style: {
      height: 7,
      borderRadius: 7,
      background: 'rgba(255,255,255,0.1)',
      overflow: 'hidden'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      height: '100%',
      width: value + '%',
      background: c,
      borderRadius: 7,
      transition: 'width 1s'
    }
  })));
}
function DkSat({
  q,
  d
}) {
  const L = window.TRACK_DASH.SAT_SCALE;
  const C = [DK.teal, DK.blue, DK.gold, DK.coral, '#8B0000'];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      background: DK.panel2,
      border: '1px solid ' + DK.border,
      borderRadius: 10,
      padding: '1.2rem'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 14,
      fontWeight: 700,
      color: DK.text2,
      textAlign: 'center',
      marginBottom: 14,
      paddingBottom: 11,
      borderBottom: '1px solid ' + DK.border
    }
  }, q), d.map((v, i) => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      marginBottom: 8
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 12,
      color: DK.text3,
      width: 96,
      flexShrink: 0
    }
  }, L[i]), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1,
      height: 6,
      background: 'rgba(255,255,255,0.08)',
      borderRadius: 5,
      overflow: 'hidden'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      height: '100%',
      width: v + '%',
      background: C[i],
      borderRadius: 5
    }
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 12,
      color: DK.text3,
      width: 34
    }
  }, v, "%"))));
}
function DkTable({
  cols,
  rows,
  hi
}) {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      overflowX: 'auto'
    }
  }, /*#__PURE__*/React.createElement("table", {
    style: {
      width: '100%',
      borderCollapse: 'collapse',
      fontSize: 13
    }
  }, /*#__PURE__*/React.createElement("thead", null, /*#__PURE__*/React.createElement("tr", {
    style: {
      background: 'rgba(59,184,195,0.1)',
      color: DK.teal
    }
  }, cols.map(c => /*#__PURE__*/React.createElement("th", {
    key: c.k,
    style: {
      padding: '11px 16px',
      textAlign: c.num ? 'center' : 'start',
      borderBottom: '1px solid ' + DK.border,
      fontWeight: 700,
      whiteSpace: 'nowrap'
    }
  }, c.l)))), /*#__PURE__*/React.createElement("tbody", null, rows.map((r, i) => /*#__PURE__*/React.createElement("tr", {
    key: i,
    style: {
      borderBottom: '1px solid rgba(255,255,255,0.06)',
      background: hi === i ? 'rgba(255,255,255,0.05)' : 'transparent'
    }
  }, cols.map(c => /*#__PURE__*/React.createElement("td", {
    key: c.k,
    style: {
      padding: '10px 16px',
      textAlign: c.num ? 'center' : 'start',
      color: c.num ? DK.text2 : DK.text
    }
  }, c.r ? c.r(r, i) : r[c.k])))))));
}
function DkMap({
  d,
  br,
  onPick,
  en
}) {
  const ref = React.useRef(null);
  const [tip, setTip] = React.useState(null);
  React.useEffect(() => {
    if (!ref.current) return;
    ref.current.querySelectorAll('[data-br]').forEach(g => {
      g.style.opacity = br !== 'all' && g.getAttribute('data-br') !== br ? '.3' : '1';
      g.style.transition = 'opacity .3s';
    });
  }, [br]);
  const names = {
    majmaah: en ? 'Majmaah – Main campus' : 'المجمعة – المقر الرئيسي',
    zulfi: en ? 'Zulfi – Branch' : 'الزلفي – فرع',
    rumah: en ? 'Rumah – Branch' : 'رماح – فرع'
  };
  const pos = {
    majmaah: {
      top: '55%',
      right: '45%'
    },
    zulfi: {
      top: '36%',
      right: '58%'
    },
    rumah: {
      top: '65%',
      right: '28%'
    }
  };
  const click = e => {
    const g = e.target.closest && e.target.closest('[data-br]');
    if (!g) return;
    const k = g.getAttribute('data-br');
    setTip(k);
    clearTimeout(ref.current._t);
    ref.current._t = setTimeout(() => setTip(null), 3200);
    onPick && onPick(k);
  };
  const b = tip && d.branches[tip];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      width: '100%'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      borderRadius: 8,
      overflow: 'hidden',
      background: 'rgba(240,245,255,0.08)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    ref: ref,
    onClick: click,
    dangerouslySetInnerHTML: {
      __html: window.TRACK_DASH_MAP
    }
  }), b && /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'absolute',
      ...pos[tip],
      background: 'rgba(20,20,20,0.95)',
      border: '1px solid ' + DK.teal,
      borderRadius: 8,
      padding: '.8rem 1rem',
      minWidth: 170,
      pointerEvents: 'none',
      zIndex: 5,
      textAlign: 'start'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      color: DK.teal,
      fontSize: 14,
      fontWeight: 700,
      marginBottom: 8
    }
  }, names[tip]), [[en ? 'Certificates' : 'الشهادات', b.v], [en ? 'Male' : 'طلاب', b.m], [en ? 'Female' : 'طالبات', b.f]].map(([l, v]) => /*#__PURE__*/React.createElement("div", {
    key: l,
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      fontSize: 12,
      marginBottom: 4,
      color: DK.text2
    }
  }, /*#__PURE__*/React.createElement("span", null, l), /*#__PURE__*/React.createElement("b", {
    style: {
      color: DK.teal
    }
  }, v))))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 18,
      marginTop: 12,
      flexWrap: 'wrap',
      justifyContent: 'center'
    }
  }, Object.keys(names).map(k => /*#__PURE__*/React.createElement("span", {
    key: k,
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 6,
      fontSize: 12,
      color: DK.text3
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 10,
      height: 10,
      borderRadius: '50%',
      background: DK_BR[k]
    }
  }), names[k])), /*#__PURE__*/React.createElement("span", {
    style: {
      fontSize: 12,
      color: DK.text3
    }
  }, en ? 'Click a branch to filter' : 'انقر على الفرع للتصفية')));
}
Object.assign(window, {
  DK,
  DK_BR,
  DK_CYC,
  dkTone,
  DkCell,
  DkKpi,
  DkDonut,
  DkGauge,
  DkShutters,
  DkProg3D,
  DkLine,
  DkGroup,
  DkLegend,
  DkPanel,
  DkKcard,
  DkBar,
  DkSat,
  DkTable,
  DkMap
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/DashboardParts.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/DashboardScreen.jsx
try { (() => {
function DashboardScreen() {
  const {
    Icon
  } = window.DS;
  const {
    lang
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const DD = window.TRACK_DASH;
  const saved = JSON.parse(localStorage.getItem('track-dash') || '{}');
  const [ini, setIni] = React.useState(saved.ini || 2);
  const [ti, setTi] = React.useState(saved.ti ?? null);
  const [br, setBr] = React.useState(saved.br || 'all');
  const [view, setView] = React.useState(saved.view || 'ov');
  React.useEffect(() => {
    localStorage.setItem('track-dash', JSON.stringify({
      ini,
      ti,
      br,
      view
    }));
  }, [ini, ti, br, view]);
  const I = st.initiatives.find(x => x.id === ini);
  const recs = DD.byIni[ini] || [];
  const idx = ti == null || ti >= recs.length ? recs.length - 1 : ti;
  const d = recs[idx];
  const B = DD.BR;
  const bName = b => en ? b.en : b.ar;
  const bSel = B.find(b => b.k === br);
  const pick = k => setBr(br === k ? 'all' : k);
  const selSt = {
    height: 36,
    padding: '0 12px',
    background: 'rgba(255,255,255,0.06)',
    color: DK.text,
    border: '1px solid ' + DK.border,
    borderRadius: 4,
    font: '600 13px ' + DK.font,
    minWidth: 170
  };
  const wrap = {
    margin: '-28px -28px -48px',
    minHeight: 'calc(100% + 76px)',
    paddingBottom: 48,
    color: DK.text,
    fontFamily: DK.font,
    background: 'var(--surface-page)'
  };
  const iniName = I ? en ? I.en : I.ar : '';
  const hero = /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '28px 32px 20px',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      flexWrap: 'wrap',
      gap: 16,
      borderBottom: '1px solid var(--border-default)'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h1", {
    style: {
      margin: 0,
      fontSize: 'clamp(22px,2.5vw,30px)',
      fontWeight: 900,
      lineHeight: 1.25,
      color: 'var(--text-primary)'
    }
  }, en ? 'Dashboard · ' : 'لوحة مؤشرات مبادرة ', /*#__PURE__*/React.createElement("span", {
    style: {
      color: DK.teal2
    }
  }, iniName), d && /*#__PURE__*/React.createElement(React.Fragment, null, " \u2013 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: '#b88c30'
    }
  }, d.label))), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '6px 0 0',
      fontSize: 13,
      color: 'var(--text-secondary)'
    }
  }, I && I.owner, I && I.status === 'draft' && (en ? ' · Draft' : ' · مسودة'))), d && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 12,
      flexWrap: 'wrap'
    }
  }, [[brD() ? brD().v : d.kpi.total, en ? 'Certificates' : 'شهادة احترافية'], [d.kpi.days + (en ? ' d' : ' يوم'), en ? 'Duration' : 'مدة المبادرة'], [d.kpi.comp + '%', en ? 'Completion' : 'نسبة الإنجاز'], [d.kpi.fac, en ? 'Faculty' : 'عضو هيئة تدريس']].map(([v, l]) => /*#__PURE__*/React.createElement("div", {
    key: l,
    style: {
      background: DK.panel2,
      border: '1px solid ' + DK.border,
      borderRadius: 8,
      padding: '10px 18px',
      textAlign: 'center',
      minWidth: 96
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 24,
      fontWeight: 900,
      color: DK.teal,
      lineHeight: 1
    }
  }, v), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      color: DK.text3,
      marginTop: 4
    }
  }, l)))));
  function brD() {
    return bSel && d ? d.branches[br] : null;
  }
  const bar = /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'sticky',
      top: -28,
      zIndex: 20,
      background: 'rgba(20,20,20,0.92)',
      backdropFilter: 'blur(10px)',
      borderBottom: '1px solid ' + DK.border2,
      padding: '10px 32px',
      display: 'flex',
      alignItems: 'center',
      gap: 14,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      fontSize: 13,
      color: DK.text3,
      fontWeight: 600
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "lightbulb",
    size: 15,
    color: DK.text3
  }), en ? 'Initiative:' : 'المبادرة:', /*#__PURE__*/React.createElement("select", {
    value: String(ini),
    onChange: e => {
      setIni(+e.target.value);
      setTi(null);
    },
    style: selSt
  }, st.initiatives.map(i => /*#__PURE__*/React.createElement("option", {
    key: i.id,
    value: String(i.id),
    style: {
      background: '#2a2a2a'
    }
  }, en ? i.en : i.ar)))), /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      fontSize: 13,
      color: DK.text3,
      fontWeight: 600
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "map-pin",
    size: 15,
    color: DK.text3
  }), en ? 'Branch:' : 'الفرع:', /*#__PURE__*/React.createElement("select", {
    value: br,
    onChange: e => setBr(e.target.value),
    style: selSt
  }, /*#__PURE__*/React.createElement("option", {
    value: "all",
    style: {
      background: '#2a2a2a'
    }
  }, en ? 'All branches' : 'جميع الفروع'), B.map(b => /*#__PURE__*/React.createElement("option", {
    key: b.k,
    value: b.k,
    style: {
      background: '#2a2a2a'
    }
  }, bName(b))))), recs.length > 0 && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      fontSize: 13,
      color: DK.text3,
      fontWeight: 600
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "calendar-days",
    size: 15,
    color: DK.text3
  }), en ? 'Term:' : 'الفصل الدراسي:'), recs.map((r, i) => {
    const on = i === idx;
    const last = i === recs.length - 1;
    return /*#__PURE__*/React.createElement("button", {
      key: i,
      onClick: () => setTi(i),
      style: {
        padding: '5px 18px',
        border: '1px solid ' + (on ? DK.teal : last ? DK.gold : DK.border),
        background: on ? DK.teal : 'transparent',
        color: on ? '#fff' : last ? DK.gold : DK.text3,
        borderRadius: 3,
        cursor: 'pointer',
        font: '600 13px ' + DK.font,
        transition: 'all .18s'
      }
    }, r.label);
  })));
  const views = [['ov', 'نظرة عامة', 'Overview'], ['br', 'الفروع', 'Branches'], ['pg', 'البرامج', 'Programs'], ['sc', 'الشعب', 'Sections'], ['sa', 'رضا المستفيدين', 'Satisfaction'], ['cmp', 'مقارنة الفصول', 'Compare terms']];
  const nav = /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 0,
      padding: '0 32px',
      borderBottom: '1px solid ' + DK.border2,
      background: '#262626',
      overflowX: 'auto'
    }
  }, views.map(([id, a, e]) => {
    const on = view === id;
    return /*#__PURE__*/React.createElement("button", {
      key: id,
      onClick: () => setView(id),
      style: {
        padding: '0 16px',
        height: 48,
        border: 'none',
        borderBottom: '2px solid ' + (on ? DK.teal : 'transparent'),
        background: 'transparent',
        color: on ? DK.teal : DK.text3,
        font: '600 14px ' + DK.font,
        cursor: 'pointer',
        whiteSpace: 'nowrap'
      }
    }, en ? e : a);
  }));
  if (!d) return /*#__PURE__*/React.createElement("div", {
    style: wrap
  }, hero, bar, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 32
    }
  }, /*#__PURE__*/React.createElement(DkPanel, null, /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center',
      padding: '40px 0',
      color: DK.text2
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "bar-chart-3",
    size: 36,
    color: DK.text3
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 18,
      fontWeight: 800,
      marginTop: 10,
      color: DK.text
    }
  }, en ? 'No data yet' : 'لا توجد بيانات بعد'), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 13,
      color: DK.text3,
      marginTop: 4
    }
  }, en ? 'This initiative has no recorded term results.' : 'لم تُسجَّل نتائج فصلية لهذه المبادرة حتى الآن.')))));
  const bd = brD();
  const total = bd ? bd.v : d.kpi.total;
  const gM = bd ? Math.round(bd.m / (bd.m + bd.f) * 100) : d.gender.m;
  const shutters = d.shutters.filter(s => !bd || s.br === br).map((s, i) => ({
    ...s,
    c: [DK.teal, DK.coral, DK.blue, DK.gold, DK.teal, DK.coral][d.shutters.indexOf(s)]
  }));
  const brTotal = B.reduce((a, b) => a + d.branches[b.k].v, 0);
  const secL = d.sec.st.map((_, i) => String(i + 1));
  const grid = (cols, gap = 1) => ({
    display: 'grid',
    gridTemplateColumns: cols,
    gap,
    background: gap === 1 ? DK.border : 'transparent'
  });
  const sec = (title, tag, children) => /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '28px 32px 0'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      textAlign: 'center',
      marginBottom: 28
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      color: DK.teal2,
      fontSize: 12,
      fontWeight: 700,
      letterSpacing: '.12em',
      marginBottom: 6
    }
  }, tag), /*#__PURE__*/React.createElement("h2", {
    style: {
      margin: 0,
      fontSize: 'clamp(22px,2.5vw,28px)',
      fontWeight: 900,
      color: 'var(--text-primary)'
    }
  }, title), /*#__PURE__*/React.createElement("div", {
    style: {
      width: 50,
      height: 2,
      background: `linear-gradient(90deg,${DK.teal},${DK.gold})`,
      margin: '8px auto 0',
      borderRadius: 2
    }
  })), children);
  const two = {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit,minmax(360px,1fr))',
    gap: 22,
    marginBottom: 22
  };
  const kRow = {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fit,minmax(150px,1fr))',
    gap: 16,
    marginBottom: 26
  };
  const branchList = /*#__PURE__*/React.createElement("div", {
    style: {
      width: '100%',
      textAlign: 'start',
      padding: '0 8px'
    }
  }, B.map(b => /*#__PURE__*/React.createElement("div", {
    key: b.k,
    onClick: () => pick(b.k),
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      marginBottom: 12,
      fontSize: 14,
      cursor: 'pointer',
      opacity: bd && br !== b.k ? .4 : 1
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 9,
      height: 9,
      borderRadius: '50%',
      background: DK_BR[b.k]
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      flex: 1,
      color: DK.text2
    }
  }, bName(b)), /*#__PURE__*/React.createElement("b", {
    style: {
      fontSize: 16
    }
  }, d.branches[b.k].v))));
  let body;
  if (view === 'ov') body = /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '24px 32px 0'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 1,
      background: DK.border,
      border: '1px solid ' + DK.border
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: grid('repeat(auto-fit,minmax(150px,1fr))')
  }, /*#__PURE__*/React.createElement(DkKpi, {
    icon: "award",
    label: bd ? (en ? 'Certificates · ' : 'الشهادات المحققة · ') + bName(bSel) : en ? 'Total certificates' : 'إجمالي الشهادات المحققة',
    value: total,
    sub: en ? 'accredited certificates' : 'شهادة تدريب مهني معتمدة'
  }), /*#__PURE__*/React.createElement(DkKpi, {
    icon: "calendar-days",
    label: en ? 'Initiative days' : 'عدد أيام المبادرة',
    value: d.kpi.days + (en ? ' d' : ' يوم')
  }), /*#__PURE__*/React.createElement(DkKpi, {
    icon: "headphones",
    label: en ? 'Support requests' : 'طلب دعم فني',
    value: d.kpi.supp
  }), /*#__PURE__*/React.createElement(DkKpi, {
    icon: "graduation-cap",
    label: en ? 'Faculty members' : 'عضو هيئة تدريس',
    value: d.kpi.fac
  }), /*#__PURE__*/React.createElement(DkKpi, {
    icon: "clipboard-list",
    label: en ? 'Branch reports' : 'التقارير الدورية لأداء الفروع',
    value: d.kpi.rep
  }), /*#__PURE__*/React.createElement(DkCell, {
    title: en ? 'By specialty unit' : 'نسبة الإنجاز حسب الوحدات التخصصية',
    style: {
      gridColumn: 'span 2'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 28
    }
  }, [[en ? 'Administrative' : 'البرامج الإدارية', d.kpi.admin, DK.teal], [en ? 'Technical' : 'البرامج التقنية', d.kpi.tech, DK.gold]].map(([l, v, c]) => /*#__PURE__*/React.createElement("div", {
    key: l,
    style: {
      textAlign: 'center'
    }
  }, /*#__PURE__*/React.createElement(DkDonut, {
    size: 78,
    hole: .68,
    segs: [{
      v,
      c
    }, {
      v: 100 - v,
      c: 'rgba(255,255,255,0.08)'
    }],
    center: /*#__PURE__*/React.createElement("span", {
      style: {
        color: c,
        fontSize: 14
      }
    }, v, "%")
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      color: DK.text3,
      marginTop: 4
    }
  }, l))))), /*#__PURE__*/React.createElement(DkCell, {
    title: en ? 'By gender' : 'الحاصلون على الشهادات حسب النوع',
    style: {
      gridColumn: 'span 2'
    }
  }, /*#__PURE__*/React.createElement(DkDonut, {
    size: 104,
    segs: [{
      v: gM,
      c: DK.teal
    }, {
      v: 100 - gM,
      c: DK.coral
    }],
    center: total
  }), /*#__PURE__*/React.createElement(DkLegend, {
    items: [[gM + (en ? '% male' : '% طلاب'), DK.teal], [100 - gM + (en ? '% female' : '% طالبات'), DK.coral]]
  }))), /*#__PURE__*/React.createElement("div", {
    style: grid('repeat(auto-fit,minmax(240px,1fr))')
  }, /*#__PURE__*/React.createElement(DkCell, null, /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 11,
      color: DK.text3,
      marginBottom: 8
    }
  }, en ? 'Actual completion' : 'نسب الإنجاز الفعلي'), /*#__PURE__*/React.createElement(DkGauge, {
    pct: d.kpi.comp
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 30,
      fontWeight: 900,
      color: DK.red,
      lineHeight: 1,
      marginTop: 4
    }
  }, d.kpi.comp, "%")), /*#__PURE__*/React.createElement(DkCell, {
    title: en ? 'Certificates per campus section' : 'عدد الشهادات الاحترافية حسب كل شطر',
    style: {
      minHeight: 200
    }
  }, /*#__PURE__*/React.createElement(DkShutters, {
    data: shutters
  })), /*#__PURE__*/React.createElement(DkCell, {
    title: en ? 'Completion by program' : 'نسب الإنجاز حسب البرامج',
    style: {
      minHeight: 200,
      gridColumn: 'span 2'
    }
  }, /*#__PURE__*/React.createElement(DkProg3D, {
    data: d.programs
  })), /*#__PURE__*/React.createElement(DkCell, {
    title: en ? 'Beneficiary satisfaction' : 'رضا المستفيدين عن المبادرة',
    style: {
      minHeight: 200
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 12,
      width: '100%'
    }
  }, (d.sat || []).slice(0, 4).map(s => {
    const a = s.d[0] + s.d[1],
      n = s.d[2];
    return /*#__PURE__*/React.createElement("div", {
      key: s.q,
      style: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: 4
      }
    }, /*#__PURE__*/React.createElement(DkDonut, {
      size: 62,
      hole: .62,
      segs: [{
        v: a,
        c: DK.teal
      }, {
        v: n,
        c: DK.gold
      }, {
        v: 100 - a - n,
        c: DK.coral
      }],
      center: /*#__PURE__*/React.createElement("span", {
        style: {
          fontSize: 11
        }
      }, a, "%")
    }), /*#__PURE__*/React.createElement("div", {
      style: {
        fontSize: 10,
        color: DK.text3,
        lineHeight: 1.3,
        maxWidth: 130
      }
    }, s.q));
  })))), /*#__PURE__*/React.createElement("div", {
    style: grid('repeat(auto-fit,minmax(320px,1fr))')
  }, /*#__PURE__*/React.createElement(DkCell, {
    title: en ? 'Branches – Riyadh region' : 'الشهادات حسب الفروع – منطقة الرياض',
    style: {
      gridColumn: 'span 2',
      minHeight: 320
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'minmax(0,2fr) minmax(130px,1fr)',
      gap: 16,
      alignItems: 'center',
      width: '100%'
    }
  }, /*#__PURE__*/React.createElement(DkMap, {
    d: d,
    br: br,
    onPick: pick,
    en: en
  }), branchList)), /*#__PURE__*/React.createElement(DkCell, {
    title: en ? 'Students vs registered per section' : 'الطلبة والمسجلون حسب الشعب',
    style: {
      minHeight: 320
    }
  }, /*#__PURE__*/React.createElement(DkLine, {
    labels: secL,
    series: [{
      label: en ? 'Students' : 'إجمالي الطلبة',
      data: d.sec.st,
      c: DK.gold
    }, {
      label: en ? 'Registered' : 'إجمالي المسجلين',
      data: d.sec.rg,
      c: DK.teal
    }]
  })))), d.excellence && /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'linear-gradient(135deg,rgba(59,184,195,0.15),rgba(212,168,75,0.1))',
      border: '1px solid rgba(59,184,195,0.3)',
      borderRadius: 12,
      padding: '1.6rem',
      textAlign: 'center',
      marginTop: 22
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "medal",
    size: 34,
    color: DK.gold
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 44,
      fontWeight: 900,
      color: DK.teal,
      lineHeight: 1.1
    }
  }, d.excellenceNum), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 16,
      fontWeight: 700,
      margin: '4px 0 2px'
    }
  }, en ? 'Excellence initiative participations' : 'مشاركة في مبادرة تميز'), /*#__PURE__*/React.createElement("div", {
    style: {
      fontSize: 13,
      color: DK.text3
    }
  }, "Excellence Award \u2013 ", d.label)));else if (view === 'br') body = sec(en ? 'Branches & geography' : 'الفروع والتوزيع الجغرافي', 'Branches · الفروع', /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    style: kRow
  }, B.map(b => /*#__PURE__*/React.createElement(DkKcard, {
    key: b.k,
    icon: b.k === 'majmaah' ? 'landmark' : 'school',
    v: d.branches[b.k].v,
    l: bName(b) + ' – ' + b.sub,
    accent: DK_BR[b.k]
  })), /*#__PURE__*/React.createElement(DkKcard, {
    icon: "bar-chart-3",
    v: brTotal,
    l: en ? 'Total' : 'إجمالي الشهادات'
  })), /*#__PURE__*/React.createElement("div", {
    style: two
  }, /*#__PURE__*/React.createElement(DkPanel, {
    title: en ? 'Interactive map – Riyadh region' : 'الخريطة التفاعلية – منطقة الرياض'
  }, /*#__PURE__*/React.createElement(DkMap, {
    d: d,
    br: br,
    onPick: pick,
    en: en
  })), /*#__PURE__*/React.createElement(DkPanel, {
    title: en ? 'Branch details' : 'تفاصيل الفروع'
  }, B.map(b => {
    const x = d.branches[b.k],
      c = DK_BR[b.k],
      p = Math.round(x.v / brTotal * 100);
    return /*#__PURE__*/React.createElement("div", {
      key: b.k,
      onClick: () => pick(b.k),
      style: {
        cursor: 'pointer',
        background: 'rgba(255,255,255,0.04)',
        border: '1px solid rgba(255,255,255,0.1)',
        borderInlineStart: '3px solid ' + c,
        borderRadius: 8,
        padding: '1rem',
        marginBottom: 14,
        opacity: bd && br !== b.k ? .45 : 1
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 8
      }
    }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("b", {
      style: {
        color: c,
        fontSize: 15
      }
    }, bName(b)), /*#__PURE__*/React.createElement("span", {
      style: {
        fontSize: 12,
        color: DK.text3,
        marginInlineStart: 6
      }
    }, b.sub)), /*#__PURE__*/React.createElement("div", {
      style: {
        fontSize: 26,
        fontWeight: 900,
        color: c
      }
    }, x.v)), /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        gap: 22,
        fontSize: 13,
        marginBottom: 8,
        color: DK.text2
      }
    }, /*#__PURE__*/React.createElement("span", null, en ? 'Male' : 'طلاب', ": ", /*#__PURE__*/React.createElement("b", {
      style: {
        color: DK.teal
      }
    }, x.m)), /*#__PURE__*/React.createElement("span", null, en ? 'Female' : 'طالبات', ": ", /*#__PURE__*/React.createElement("b", {
      style: {
        color: DK.coral
      }
    }, x.f))), /*#__PURE__*/React.createElement("div", {
      style: {
        height: 6,
        background: 'rgba(255,255,255,0.08)',
        borderRadius: 5,
        overflow: 'hidden'
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        height: '100%',
        width: p + '%',
        background: c,
        borderRadius: 5
      }
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        fontSize: 11,
        color: DK.text3,
        textAlign: 'end',
        marginTop: 4
      }
    }, p, "% ", en ? 'of total' : 'من الإجمالي'));
  })))));else if (view === 'pg') body = sec(en ? 'Completion by program' : 'نسب الإنجاز حسب البرامج', 'Programs · البرامج', /*#__PURE__*/React.createElement("div", {
    style: two
  }, /*#__PURE__*/React.createElement(DkPanel, {
    title: en ? 'Programs' : 'البرامج'
  }, d.programs.map(p => /*#__PURE__*/React.createElement(DkBar, {
    key: p.n,
    label: p.n,
    value: p.p,
    c: dkTone(p.p)
  }))), /*#__PURE__*/React.createElement(DkPanel, {
    title: en ? 'Branch comparison' : 'مقارنة الفروع'
  }, /*#__PURE__*/React.createElement(DkGroup, {
    labels: B.map(bName),
    series: [{
      label: '',
      data: B.map(b => d.branches[b.k].v),
      c: DK.gold
    }]
  }))));else if (view === 'sc') {
    const rows = d.sec.st.map((s, i) => ({
      n: (en ? 'Section ' : 'شعبة ') + (i + 1),
      st: s,
      rg: d.sec.rg[i],
      ct: d.sec.ct[i],
      p: d.sec.rg[i] ? Math.round(d.sec.ct[i] / d.sec.rg[i] * 100) : 0
    }));
    body = sec(en ? 'Sections performance' : 'أداء الشعب', 'Sections · الشعب', /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
      style: two
    }, /*#__PURE__*/React.createElement(DkPanel, {
      title: en ? 'Students vs registered' : 'إجمالي الطلبة والمسجلين'
    }, /*#__PURE__*/React.createElement(DkLine, {
      labels: secL,
      series: [{
        label: en ? 'Students' : 'إجمالي الطلبة',
        data: d.sec.st,
        c: DK.gold
      }, {
        label: en ? 'Registered' : 'إجمالي المسجلين',
        data: d.sec.rg,
        c: DK.teal
      }]
    })), /*#__PURE__*/React.createElement(DkPanel, {
      title: en ? 'Registered vs certified' : 'المسجلون والحاصلون على الشهادة'
    }, /*#__PURE__*/React.createElement(DkGroup, {
      labels: secL,
      series: [{
        label: en ? 'Registered' : 'المسجلين',
        data: d.sec.rg,
        c: DK.teal
      }, {
        label: en ? 'Certified' : 'الحاصلين على الشهادة',
        data: d.sec.ct,
        c: DK.gold
      }]
    }))), /*#__PURE__*/React.createElement(DkPanel, null, /*#__PURE__*/React.createElement(DkTable, {
      rows: rows,
      cols: [{
        k: 'n',
        l: en ? 'Section' : 'الشعبة'
      }, {
        k: 'st',
        l: en ? 'Students' : 'إجمالي الطلبة',
        num: 1
      }, {
        k: 'rg',
        l: en ? 'Registered' : 'المسجلون',
        num: 1
      }, {
        k: 'ct',
        l: en ? 'Certified' : 'الحاصلون',
        num: 1
      }, {
        k: 'p',
        l: en ? 'Rate' : 'النسبة',
        num: 1,
        r: r => /*#__PURE__*/React.createElement("b", {
          style: {
            color: dkTone(r.p)
          }
        }, r.p, "%")
      }]
    }))));
  } else if (view === 'sa') body = sec(en ? 'Beneficiary satisfaction' : 'رضا المستفيدين عن المبادرة', 'Satisfaction · رضا المستفيدين', /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(340px,1fr))',
      gap: 20
    }
  }, (d.sat || []).map(s => /*#__PURE__*/React.createElement(DkSat, {
    key: s.q,
    q: s.q,
    d: s.d
  }))));else {
    const C = [DK.teal, DK.blue, DK.gold, DK.coral];
    const tv = r => bd ? r.branches[br].v : r.kpi.total;
    body = sec((en ? 'Compare all terms' : 'مقارنة جميع الفصول الدراسية') + (bd ? ' · ' + bName(bSel) : ''), 'Compare · المقارنة', /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
      style: kRow
    }, recs.map((r, i) => /*#__PURE__*/React.createElement("div", {
      key: i,
      onClick: () => setTi(i),
      style: {
        cursor: 'pointer'
      }
    }, /*#__PURE__*/React.createElement(DkKcard, {
      top: C[i % 4],
      accent: C[i % 4],
      v: tv(r),
      l: r.label + ' · ' + (en ? 'completion ' : 'إنجاز ') + r.kpi.comp + '%'
    })))), /*#__PURE__*/React.createElement("div", {
      style: two
    }, /*#__PURE__*/React.createElement(DkPanel, {
      title: en ? 'Total certificates' : 'إجمالي الشهادات'
    }, /*#__PURE__*/React.createElement(DkGroup, {
      labels: recs.map(r => r.label),
      series: [{
        label: '',
        data: recs.map(tv),
        c: DK.teal
      }]
    })), /*#__PURE__*/React.createElement(DkPanel, {
      title: en ? 'Completion rates' : 'نسب الإنجاز'
    }, /*#__PURE__*/React.createElement(DkLine, {
      labels: recs.map(r => r.label),
      series: [{
        label: en ? 'Completion %' : 'نسبة الإنجاز %',
        data: recs.map(r => r.kpi.comp),
        c: DK.teal
      }, {
        label: en ? 'Administrative %' : 'البرامج الإدارية %',
        data: recs.map(r => r.kpi.admin),
        c: DK.gold
      }, {
        label: en ? 'Technical %' : 'البرامج التقنية %',
        data: recs.map(r => r.kpi.tech),
        c: DK.coral
      }]
    })), /*#__PURE__*/React.createElement(DkPanel, {
      title: en ? 'Branches by term' : 'الفروع حسب الفصل'
    }, /*#__PURE__*/React.createElement(DkGroup, {
      labels: recs.map(r => r.label),
      series: B.map(b => ({
        label: bName(b),
        data: recs.map(r => r.branches[b.k].v),
        c: DK_BR[b.k]
      }))
    })), /*#__PURE__*/React.createElement(DkPanel, {
      title: en ? 'Support & faculty' : 'الدعم الفني وهيئة التدريس'
    }, /*#__PURE__*/React.createElement(DkGroup, {
      labels: recs.map(r => r.label),
      series: [{
        label: en ? 'Support requests' : 'طلبات الدعم الفني',
        data: recs.map(r => r.kpi.supp),
        c: DK.coral
      }, {
        label: en ? 'Faculty' : 'أعضاء هيئة التدريس',
        data: recs.map(r => r.kpi.fac),
        c: DK.teal
      }]
    }))), /*#__PURE__*/React.createElement(DkPanel, null, /*#__PURE__*/React.createElement(DkTable, {
      hi: idx,
      rows: recs,
      cols: [{
        k: 'label',
        l: en ? 'Term' : 'الفصل الدراسي',
        r: (r, i) => /*#__PURE__*/React.createElement("b", {
          style: {
            color: C[i % 4]
          }
        }, r.label)
      }, {
        k: 't',
        l: en ? 'Certificates' : 'إجمالي الشهادات',
        num: 1,
        r: r => /*#__PURE__*/React.createElement("b", {
          style: {
            color: DK.text
          }
        }, tv(r))
      }, {
        k: 'c',
        l: en ? 'Completion' : 'نسبة الإنجاز',
        num: 1,
        r: r => /*#__PURE__*/React.createElement("span", {
          style: {
            background: r.kpi.comp >= 98 ? 'rgba(59,184,195,0.2)' : 'rgba(212,168,75,0.2)',
            color: r.kpi.comp >= 98 ? DK.teal : DK.gold,
            padding: '3px 10px',
            borderRadius: 6,
            fontWeight: 700
          }
        }, r.kpi.comp, "%")
      }, {
        k: 'd',
        l: en ? 'Days' : 'أيام المبادرة',
        num: 1,
        r: r => r.kpi.days
      }, {
        k: 'f',
        l: en ? 'Faculty' : 'هيئة التدريس',
        num: 1,
        r: r => r.kpi.fac
      }, {
        k: 's',
        l: en ? 'Support' : 'طلبات الدعم',
        num: 1,
        r: r => r.kpi.supp
      }, {
        k: 'e',
        l: en ? 'Excellence' : 'مبادرة تميز',
        num: 1,
        r: r => r.excellence ? /*#__PURE__*/React.createElement("b", {
          style: {
            color: DK.gold
          }
        }, r.excellenceNum) : '—'
      }]
    }))));
  }
  return /*#__PURE__*/React.createElement("div", {
    style: wrap
  }, hero, bar, nav, body);
}
window.DashboardScreen = DashboardScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/DashboardScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/HomeScreen.jsx
try { (() => {
function HomeScreen() {
  const {
    StatCard,
    Card,
    Alert,
    Button,
    DataTable,
    StatusBadge,
    EmptyState
  } = window.DS;
  const {
    t,
    me,
    lang,
    go,
    firstBanner,
    dismissBanner
  } = useApp();
  const st = useStore();
  const term = st.currentTerm();
  const secs = st.sectionScope(me).filter(s => s.term === term.id);
  const ids = new Set(secs.map(s => s.id));
  const enr = st.S.enrollments.filter(e => ids.has(e.section) && e.status === 'active');
  const studs = new Set(enr.map(e => e.student)).size;
  const recent = st.S.audit.filter(a => me.role === 'SUPER_ADMIN' || a.actor === me.id).slice(0, 5);
  const quick = {
    SUPER_ADMIN: [['lightbulb', 'nav_initiatives', 'initiatives'], ['user-plus', 'new_user', 'users'], ['file-up', 'new_import', 'imports'], ['calendar-plus', 'new_term', 'terms'], ['shield-check', 'nav_audit', 'audit']],
    BRANCH_COORDINATOR: [['users', 'nav_sections', 'sections'], ['shield-check', 'nav_audit', 'audit']],
    INSTRUCTOR: [['users', 'nav_mysections', 'mysections'], ['file-up', 'import_list', 'imports']]
  }[me.role];
  return /*#__PURE__*/React.createElement("div", null, firstBanner && /*#__PURE__*/React.createElement(Alert, {
    tone: "warning",
    icon: "key-round",
    title: t('first_login'),
    onClose: dismissBanner,
    style: {
      marginBottom: 18
    },
    action: /*#__PURE__*/React.createElement(Button, {
      size: "sm",
      variant: "secondary",
      onClick: () => go('profile')
    }, t('change_now'))
  }, t('first_login_msg')), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'flex-end',
      gap: 16,
      marginBottom: 22,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h1", {
    style: {
      margin: 0,
      font: 'var(--text-h1)'
    }
  }, t('welcome', {
    n: lang === 'en' ? me.en : me.ar
  })), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '6px 0 0',
      color: 'var(--text-secondary)'
    }
  }, t('role_' + me.role), me.branches.length > 0 && ' · ' + me.branches.map(b => st.branchLabel(b, lang)).join('، '))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      padding: '8px 14px',
      background: '#fff',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '500 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, t('current_term')), /*#__PURE__*/React.createElement("b", {
    style: {
      font: '700 13px var(--font-sans)'
    }
  }, lang === 'en' ? term.en : term.ar), /*#__PURE__*/React.createElement(StatusBadge, {
    status: "current"
  }))), me.role === 'INSTRUCTOR' && secs.length === 0 ? /*#__PURE__*/React.createElement(Panel, null, /*#__PURE__*/React.createElement(EmptyState, {
    icon: "users",
    title: t('no_sections_inst'),
    message: t('no_sections_inst_msg')
  })) : /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(150px,1fr))',
      gap: 14,
      marginBottom: 18
    }
  }, me.role === 'SUPER_ADMIN' && /*#__PURE__*/React.createElement(StatCard, {
    value: st.S.users.filter(u => u.active).length,
    label: t('k_users')
  }), me.role !== 'INSTRUCTOR' && /*#__PURE__*/React.createElement(StatCard, {
    value: me.role === 'SUPER_ADMIN' ? st.S.branches.filter(b => b.active).length : me.branches.length,
    label: t('k_branches')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: secs.length,
    label: t('k_sections')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: studs,
    label: t('k_students'),
    tone: "green"
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: enr.length,
    label: t('k_enroll'),
    tone: "green"
  }), me.role === 'SUPER_ADMIN' && /*#__PURE__*/React.createElement(StatCard, {
    value: st.S.imports.length,
    label: t('k_imports')
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(340px,1fr))',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Card, {
    title: t('quick')
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 10
    }
  }, quick.map(([i, l, r]) => /*#__PURE__*/React.createElement(Button, {
    key: l,
    variant: "secondary",
    icon: i,
    onClick: () => go(r),
    style: {
      justifyContent: 'flex-start',
      height: 52
    }
  }, t(l))))), /*#__PURE__*/React.createElement(Card, {
    title: t('recent_activity'),
    action: me.role !== 'INSTRUCTOR' && /*#__PURE__*/React.createElement("a", {
      href: "#",
      onClick: e => {
        e.preventDefault();
        go('audit');
      }
    }, t('view_all'))
  }, /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: 'at',
      label: t('when'),
      numeric: true,
      render: r => fmtDateTime(r.at)
    }, {
      key: 'action',
      label: t('action'),
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          font: '600 13px var(--font-sans)',
          color: 'var(--track-blue)'
        }
      }, window.TRACK_I18N.lbl(lang, 'act', r.action))
    }, {
      key: 'actor',
      label: t('actor'),
      render: r => r.actor ? st.uname(r.actor, lang) : '—'
    }],
    rows: recent
  })))));
}
window.HomeScreen = HomeScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/HomeScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/ImportScreens.jsx
try { (() => {
const CENTRAL_FIELDS = [['term', 1], ['branch', 1], ['gender', 1], ['course_code', 1], ['course_name', 0], ['section_no', 1], ['inst_name', 0], ['inst_email', 1], ['uid', 1], ['student_name', 1]];
const SECTION_FIELDS = [['seq', 0], ['uid', 1], ['student_name', 1], ['acad_status', 0]];
const CENTRAL_HEADERS = ['الفصل', 'الفرع', 'النوع', 'رمز المقرر', 'اسم المقرر', 'رقم الشعبة', 'اسم المدرس', 'البريد', 'الرقم الجامعي', 'اسم الطالب', 'ملاحظات'];
const SECTION_HEADERS = ['تسلسل', 'رقم الطالب', 'اسم الطالب', 'الحالة'];
const HDR_MATCH = {
  seq: /تسلسل|seq/i,
  uid: /رقم الطالب|الرقم الجامعي|university id|student no/i,
  student_name: /اسم الطالب|student name|name/i,
  acad_status: /^الحالة$|status/i
};
function autoMap(fields, headers) {
  const m = {};
  fields.forEach(([k]) => {
    const i = headers.findIndex(h => HDR_MATCH[k] && HDR_MATCH[k].test(h));
    m[k] = i >= 0 ? i : '';
  });
  return m;
}
function sampleCentral(st) {
  const rows = [];
  const ok = st.S.students.slice(0, 8);
  ok.forEach((s, i) => rows.push(['1448-1', 'الزلفي', 'طالبات', 'ENG102', 'اللغة الإنجليزية 2', '214', 'د. فيصل العتيبي', 'f.alotaibi@mu.edu.sa', s.uid, s.ar]));
  for (let i = 0; i < 6; i++) rows.push(['1448-1', 'المجمعة', 'طلاب', 'CS101', 'مقدمة في الحاسب', '131', 'د. هند المطيري', 'h.almutairi@mu.edu.sa', '44200' + (1100 + i), ['سلطان', 'مازن', 'عمر', 'زياد', 'راكان', 'حمد'][i] + ' القحطاني']);
  rows.splice(4, 0, rows[2].slice());
  rows.push(['1448-1', 'الزلفي', 'طالبات', 'ENG102', 'اللغة الإنجليزية 2', '214', 'د. فيصل العتيبي', 'f.alotaibi@mu.edu.sa', '44A10023', 'جنى الحربي']);
  rows.push(['1448-1', 'الزلفي', 'طالبات', 'ENG102', 'اللغة الإنجليزية 2', '214', 'د. فيصل العتيبي', 'f.alotaibi@mu.edu.sa', '', 'رزان المطيري']);
  rows.push(['1448-1', 'الدرعية', 'طلاب', 'CS101', 'مقدمة في الحاسب', '131', 'د. هند المطيري', 'h.almutairi@mu.edu.sa', '442001200', 'ماجد الشهري']);
  rows.push(['1448-1', 'رماح', 'طلاب', 'PHYS110', 'فيزياء عامة', '401', 'أ. بدر السهلي', 'b.alsahli@mu.edu.sa', '442001201', 'عبدالله السهلي']);
  rows.push(['1448-1', 'رماح', 'طلاب', 'PHYS110', 'فيزياء عامة', '401', 'أ. بدر السهلي', 'b.alsahli@mu.edu.sa', '442001202', 'صالح الدوسري']);
  rows.push(['1448-1', 'الزلفي', 'طالبات', 'ENG102', 'اللغة الإنجليزية 2', '214', 'د. فيصل العتيبي', 'f.alotaibi@mu.edu.sa', ok[1].uid, 'اسم مختلف تمامًا']);
  rows.push(['1448-1', 'الزلفي', 'طالبات', 'ENG102', 'اللغة الإنجليزية 2', '', 'د. فيصل العتيبي', 'f.alotaibi@mu.edu.sa', '442001203', 'نوف العنزي']);
  return rows.map(r => [...r, '']);
}
function sampleSection(st) {
  const ex = st.S.students[20];
  return [['442009001', 'لين الحربي'], [ex.uid, 'اسم آخر'], ['442009002', 'هديل الغامدي'], ['442009001', 'لين الحربي'], ['4420X9003', 'ترف السبيعي']];
}
function validate(rows, map, mode, st, sectionId) {
  const col = k => map[k];
  const seen = {};
  const out = [];
  const idx = Object.fromEntries(Object.entries(map).filter(([k, v]) => v !== ''));
  rows.forEach((r, i) => {
    const n = i + 2;
    const v = k => idx[k] != null ? String(r[idx[k]] || '').trim() : '';
    const reasons = [];
    let status = 'valid';
    const uid = v('uid');
    const key = mode === 'central' ? [v('branch'), v('gender'), v('course_code'), v('section_no'), uid].join('|') : uid;
    if (seen[key]) {
      reasons.push(['r_dup_file', {
        n: seen[key]
      }]);
      status = 'error';
    } else seen[key] = n;
    const req = (mode === 'central' ? CENTRAL_FIELDS : SECTION_FIELDS).filter(f => f[1]).map(f => f[0]);
    req.forEach(f => {
      if (!v(f)) {
        reasons.push(['r_missing', {
          c: 'f_' + f
        }]);
        status = 'error';
      }
    });
    if (uid && !/^\d{9}$/.test(uid)) {
      reasons.push(['r_uid', {}]);
      status = 'error';
    }
    let flags = {};
    if (mode === 'central' && status !== 'error') {
      const b = st.S.branches.find(b => b.ar === v('branch') && (b.gender === 'male' ? /^(طلاب|بنين)$/ : /^(طالبات|بنات)$/).test(v('gender')));
      if (!b) {
        reasons.push(['r_branch', {
          v: v('branch') + ' — ' + v('gender')
        }]);
        status = 'error';
      }
      if (!st.S.courses.find(c => c.code === v('course_code'))) {
        reasons.push(['r_course', {
          v: v('course_code')
        }]);
        flags.course = true;
        if (status === 'valid') status = 'warning';
      }
      if (!st.S.users.find(u => u.email === v('inst_email'))) {
        reasons.push(['r_inst', {
          v: v('inst_email')
        }]);
        flags.inst = true;
        if (status === 'valid') status = 'warning';
      }
    }
    const ex = uid && st.S.students.find(s => s.uid === uid);
    if (ex && status !== 'error') {
      if (ex.ar !== v('student_name')) {
        reasons.push(['r_name', {
          v: ex.ar
        }]);
        if (status === 'valid') status = 'warning';
      } else reasons.push(['r_linked', {}]);
      flags.existing = true;
    }
    out.push({
      n,
      raw: r,
      uid,
      name: v('student_name'),
      acad: v('acad_status'),
      seq: v('seq'),
      status,
      reasons,
      flags,
      sec: mode === 'central' ? v('course_code') + '/' + v('section_no') : null
    });
  });
  return out;
}
function FileMeta({
  meta,
  sec
}) {
  const {
    Icon
  } = window.DS;
  const {
    lang
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const c = st.course(sec.course);
  const rows = [[en ? 'Campus' : 'المقر', meta.campus, null], [en ? 'Degree' : 'الدرجة', meta.degree, null], [en ? 'Course' : 'اسم المقرر', meta.course, meta.course ? meta.course === c.ar || meta.course === c.en : null], [en ? 'Activity' : 'النشاط', meta.activity, null], [en ? 'Section' : 'الشعبة', meta.section, meta.section ? meta.section === sec.no : null]];
  const bad = rows.some(r => r[2] === false);
  return /*#__PURE__*/React.createElement("div", {
    style: {
      border: '1px solid ' + (bad ? 'var(--red-100)' : 'var(--green-100)'),
      background: bad ? 'var(--red-50)' : 'var(--green-50)',
      borderRadius: 'var(--radius-md)',
      padding: 14
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 8,
      font: '700 13px var(--font-sans)',
      marginBottom: 10,
      color: bad ? 'var(--red-600)' : 'var(--green-700)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: bad ? 'circle-alert' : 'circle-check',
    size: 16
  }), bad ? en ? 'File header does not match this section' : 'بيانات رأس الملف لا تطابق هذه الشعبة' : en ? 'File header matches this section' : 'بيانات رأس الملف مطابقة لهذه الشعبة'), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(120px,1fr))',
      gap: 10
    }
  }, rows.map(([l, v, ok]) => /*#__PURE__*/React.createElement("div", {
    key: l
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '500 11px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, l), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 4,
      font: '700 13px var(--font-sans)'
    }
  }, v || '—', ok === true && /*#__PURE__*/React.createElement(Icon, {
    name: "check",
    size: 13,
    color: "var(--green-700)"
  }), ok === false && /*#__PURE__*/React.createElement(Icon, {
    name: "x",
    size: 13,
    color: "var(--red-600)"
  }))))), bad && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--red-600)',
      marginTop: 8
    }
  }, en ? 'Expected: ' : 'المتوقع: ', c.ar, " \xB7 ", sec.no));
}
function ImportsScreen() {
  const {
    Button,
    DataTable,
    StatusBadge
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const rows = me.role === 'SUPER_ADMIN' ? st.S.imports : st.S.imports.filter(i => i.by === me.id || i.section && st.S.sections.some(s => s.id === i.section && st.canSee(me, s)));
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_imports'),
    subtitle: t('imports_sub'),
    actions: /*#__PURE__*/React.createElement(React.Fragment, null, st.can(me, 'import.central.run') && /*#__PURE__*/React.createElement(Button, {
      icon: "file-up",
      onClick: () => go('wizard', {
        mode: 'central'
      })
    }, t('central')), st.can(me, 'import.section.run') && me.role === 'INSTRUCTOR' && /*#__PURE__*/React.createElement(Button, {
      icon: "file-up",
      onClick: () => go('mysections')
    }, t('section_mode')))
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    columns: [{
      key: 'file',
      label: t('file'),
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          display: 'flex',
          gap: 8,
          alignItems: 'center'
        }
      }, /*#__PURE__*/React.createElement(window.DS.Icon, {
        name: "file-spreadsheet",
        size: 18,
        color: "var(--track-green)"
      }), /*#__PURE__*/React.createElement("b", {
        style: {
          fontFamily: 'var(--font-latin)',
          fontWeight: 600
        }
      }, r.file))
    }, {
      key: 'm',
      label: lang === 'en' ? 'Mode' : 'النوع',
      render: r => r.mode === 'central' ? t('central') : t('section_mode') + (r.section ? ' · ' + st.S.sections.find(s => s.id === r.section).no : '')
    }, {
      key: 'by',
      label: t('ran_by'),
      render: r => st.uname(r.by, lang)
    }, {
      key: 'at',
      label: t('date'),
      numeric: true,
      render: r => fmtDateTime(r.at)
    }, {
      key: 'total',
      label: t('rows'),
      numeric: true,
      render: r => r.total.toLocaleString('en')
    }, {
      key: 'imported',
      label: t('imported'),
      numeric: true,
      render: r => r.imported.toLocaleString('en')
    }, {
      key: 'duplicates',
      label: t('duplicates'),
      numeric: true
    }, {
      key: 'errors',
      label: t('errors'),
      numeric: true
    }, {
      key: 's',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.status === 'completed' ? 'completed' : r.status === 'failed' ? 'failed' : 'running'
      })
    }, {
      key: 'x',
      label: '',
      render: r => r.status === 'failed' ? /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "rotate-cw"
      }, lang === 'en' ? 'Resume' : 'استئناف') : r.errors > 0 ? /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "download"
      }, "xlsx") : null
    }],
    rows: rows
  })));
}
function ImportWizard({
  params,
  notify
}) {
  const {
    Stepper,
    Button,
    Select,
    Alert,
    DataTable,
    StatusBadge,
    StatCard,
    Checkbox,
    Icon,
    ProgressBar
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const mode = params && params.mode || 'central';
  const sectionId = params && params.section;
  const sec = sectionId && st.S.sections.find(s => s.id === sectionId);
  const [step, setStep] = React.useState(0);
  const [file, setFile] = React.useState(null);
  const [fErr, setFErr] = React.useState(null);
  const [term, setTerm] = React.useState(st.currentTerm().id);
  const [map, setMap] = React.useState({});
  const [prog, setProg] = React.useState(0);
  const [res, setRes] = React.useState(null);
  const [opt, setOpt] = React.useState({
    course: true,
    inst: false
  });
  const [report, setReport] = React.useState(null);
  const denied = React.useRef(false);
  if (mode === 'section' && (!sec || !(me.role === 'SUPER_ADMIN' || sec.instructor === me.id || st.isShared(me, sec)))) {
    if (!denied.current) {
      denied.current = true;
      st.audit(me.id, 'access.denied', 'import_job', sectionId, null, {
        reason: 'section_not_taught'
      });
    }
    return /*#__PURE__*/React.createElement(RefusalScreen, {
      kind: "403"
    });
  }
  const fields = mode === 'central' ? CENTRAL_FIELDS : SECTION_FIELDS;
  const headers = file && file.headers ? file.headers : mode === 'central' ? CENTRAL_HEADERS : SECTION_HEADERS;
  const fileRef = React.useRef(null);
  const onFile = async ev => {
    const f = ev.target.files && ev.target.files[0];
    ev.target.value = '';
    if (!f) return;
    try {
      const p = await window.parseRosterFile(f);
      if (!p.rows.length) {
        setFErr(lang === 'en' ? 'The file contains no importable rows.' : 'الملف لا يحتوي على صفوف قابلة للاستيراد.');
        return;
      }
      setFErr(null);
      setFile({
        ...p,
        prev: st.S.imports.some(i => i.file === p.name && (mode === 'central' ? i.term === term : i.section === sectionId))
      });
      setMap(mode === 'central' && !p.headers.some(h => HDR_MATCH.uid.test(h)) ? {} : autoMap(fields, p.headers));
    } catch (e) {
      setFErr(e.message === 'size' ? t('too_big') : e.message === 'rows' ? lang === 'en' ? 'File exceeds 50,000 rows.' : 'الملف يتجاوز 50,000 صف.' : t('bad_type'));
    }
  };
  const steps = ['s_upload', 's_map', 's_check', 's_preview', 's_import', 's_report'].map(k => t(k));
  const pickSample = bad => {
    if (bad === 'big') {
      setFErr(t('too_big'));
      return;
    }
    if (bad === 'type') {
      setFErr(t('bad_type'));
      return;
    }
    if (mode === 'section') {
      const R = st.REAL_FILE;
      setFErr(null);
      setFile({
        ...R,
        rows: R.rows.map(r => r.slice()),
        prev: st.S.imports.some(i => i.file === R.name && i.section === sectionId)
      });
      setMap(autoMap(fields, R.headers));
      return;
    }
    const rows = sampleCentral(st);
    const name = 'roster_1448_first.xlsx';
    setFErr(null);
    setFile({
      name,
      rows,
      size: '412 KB',
      headers: CENTRAL_HEADERS,
      prev: st.S.imports.some(i => i.file === name && i.term === term)
    });
    const m = {};
    fields.forEach(([k], i) => {
      m[k] = mode === 'central' ? k === 'inst_name' ? 6 : i < 6 ? i : i < 8 ? i : i : i;
    });
    if (mode === 'central') {
      m.course_name = '';
    }
    setMap(m);
  };
  const mapOk = fields.filter(f => f[1]).every(([k]) => map[k] !== '' && map[k] != null);
  const runCheck = () => {
    setStep(2);
    setProg(0);
    let p = 0;
    const x = setInterval(() => {
      p += 20;
      setProg(p);
      if (p >= 100) {
        clearInterval(x);
        setRes(validate(file.rows, map, mode, st, sectionId));
        setStep(3);
      }
    }, 350);
  };
  const runImport = () => {
    setStep(4);
    setProg(0);
    let p = 0;
    const x = setInterval(() => {
      p += 25;
      setProg(p);
      if (p >= 100) {
        clearInterval(x);
        const ok = res.filter(r => r.status !== 'error' && !(r.flags.course && !opt.course) && !(r.flags.inst && !opt.inst));
        let imported = 0,
          dup = 0;
        if (!file.prev) {
          ok.forEach(r => {
            let s = st.S.students.find(x => x.uid === r.uid);
            if (!s) {
              s = {
                id: Date.now() + Math.random(),
                uid: r.uid,
                ar: r.name,
                en: ''
              };
              st.S.students.push(s);
              imported++;
            } else dup++;
            if (mode === 'section' && !st.S.enrollments.some(e => e.student === s.id && e.section === sectionId)) st.S.enrollments.push({
              id: Date.now() + Math.random(),
              student: s.id,
              section: sectionId,
              status: 'active',
              acad: r.acad || 'منتظم',
              seq: +r.seq || 0,
              enrolled: st.now().slice(0, 10)
            });
          });
        } else dup = ok.length;
        const errs = res.filter(r => !ok.includes(r));
        const rep = {
          processed: res.length,
          imported: file.prev ? 0 : imported,
          duplicates: dup,
          errors: errs.length,
          errs
        };
        st.S.imports.unshift({
          id: Date.now(),
          mode,
          term,
          section: sectionId || null,
          file: file.name,
          by: me.id,
          at: st.now().slice(0, 19),
          status: 'completed',
          total: res.length,
          processed: res.length,
          imported: rep.imported,
          duplicates: dup,
          errors: errs.length
        });
        st.audit(me.id, 'import.completed', 'import_job', null, null, {
          file: file.name,
          processed: res.length,
          imported: rep.imported,
          duplicates: dup,
          errors: errs.length
        });
        st.emit();
        setReport(rep);
        setStep(5);
        notify(t('import_done'), file.name);
      }
    }, 500);
  };
  const rsn = r => r.reasons.map(([k, p], i) => /*#__PURE__*/React.createElement("div", {
    key: i
  }, t(k, p.c ? {
    ...p,
    c: t(p.c)
  } : p)));
  const cnt = s => res ? res.filter(r => r.status === s).length : 0;
  const crumbs = [{
    label: t('nav_imports'),
    onClick: () => go('imports')
  }, {
    label: mode === 'central' ? t('central') : t('section_mode') + ' · ' + sec.no
  }];
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    crumbs: crumbs,
    title: mode === 'central' ? t('central') : t('section_mode'),
    subtitle: mode === 'section' ? st.course(sec.course).code + ' / ' + sec.no + ' · ' + st.branchLabel(sec.branch, lang) : t('imports_sub')
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: '24px 24px 8px',
      marginBottom: 16
    }
  }, /*#__PURE__*/React.createElement(Stepper, {
    steps: steps,
    current: step
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      height: 16
    }
  })), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 24
    }
  }, step === 0 && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 16,
      maxWidth: 720
    }
  }, mode === 'central' && /*#__PURE__*/React.createElement(Select, {
    label: t('target_term'),
    value: term,
    onChange: e => setTerm(+e.target.value),
    options: st.S.terms.map(x => ({
      value: x.id,
      label: lang === 'en' ? x.en : x.ar
    })),
    style: {
      maxWidth: 320
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      border: '2px dashed ' + (fErr ? 'var(--red-500)' : 'var(--border-strong)'),
      borderRadius: 'var(--radius-lg)',
      padding: '36px 20px',
      textAlign: 'center',
      background: file ? 'var(--green-50)' : 'var(--gray-50)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: file ? 'file-check-2' : 'upload-cloud',
    size: 36,
    color: file ? 'var(--track-green)' : 'var(--track-blue)',
    style: {
      margin: '0 auto 10px'
    }
  }), file ? /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 15px var(--font-latin)'
    }
  }, file.name), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 13px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, file.size, " \xB7 ", file.rows.length, " ", t('rows'))) : /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 15px var(--font-sans)'
    }
  }, t('drop')), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 13px var(--font-sans)',
      color: 'var(--text-secondary)',
      marginTop: 4
    }
  }, t('drop_hint'))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      justifyContent: 'center',
      marginTop: 16,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("input", {
    ref: fileRef,
    type: "file",
    accept: ".xlsx,.csv",
    onChange: onFile,
    style: {
      display: 'none'
    }
  }), /*#__PURE__*/React.createElement(Button, {
    variant: "secondary",
    icon: "download",
    onClick: () => downloadTemplate(mode === 'central' ? 'track_central_roster_template.csv' : 'track_section_roster_template.csv', mode === 'central' ? [CENTRAL_HEADERS, sampleCentral(st)[0].slice(0, CENTRAL_HEADERS.length)] : [['تسلسل', 'رقم الطالب', 'اسم الطالب', 'الحالة'], ['1', '442104959', 'اسم الطالب الرباعي', 'منتظم']])
  }, lang === 'en' ? 'Download template' : 'تنزيل القالب'), /*#__PURE__*/React.createElement(Button, {
    variant: "secondary",
    icon: "folder-open",
    onClick: () => fileRef.current && fileRef.current.click()
  }, t('choose')), /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "sm",
    icon: "file-spreadsheet",
    onClick: () => pickSample()
  }, t('use_sample')), /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "sm",
    onClick: () => pickSample('big')
  }, lang === 'en' ? 'Try 25 MB file' : 'تجربة ملف 25MB'), /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "sm",
    onClick: () => pickSample('type')
  }, lang === 'en' ? 'Try .pdf' : 'تجربة ملف pdf'))), file && file.meta && Object.keys(file.meta).length > 0 && mode === 'section' && /*#__PURE__*/React.createElement(FileMeta, {
    meta: file.meta,
    sec: sec
  }), fErr && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger"
  }, fErr), file && file.prev && /*#__PURE__*/React.createElement(Alert, {
    tone: "warning",
    icon: "copy"
  }, t('prev_imported')), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'flex-end'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    disabled: !file,
    iconEnd: lang === 'en' ? 'arrow-right' : 'arrow-left',
    onClick: () => setStep(1)
  }, t('s_map')))), step === 1 && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 16
    }
  }, /*#__PURE__*/React.createElement(Alert, {
    tone: "info"
  }, t('map_help')), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fill,minmax(300px,1fr))',
      gap: 12
    }
  }, fields.map(([k, req]) => /*#__PURE__*/React.createElement("div", {
    key: k,
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr',
      gap: 6,
      padding: 12,
      border: '1px solid ' + (req && (map[k] === '' || map[k] == null) ? 'var(--red-500)' : 'var(--border-default)'),
      borderRadius: 'var(--radius-md)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 13px var(--font-sans)'
    }
  }, t('f_' + k), req ? /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--red-600)'
    }
  }, " *") : null), /*#__PURE__*/React.createElement("select", {
    "aria-label": t('f_' + k),
    value: map[k] ?? '',
    onChange: e => setMap({
      ...map,
      [k]: e.target.value === '' ? '' : +e.target.value
    }),
    style: nativeStyle
  }, /*#__PURE__*/React.createElement("option", {
    value: ""
  }, t('unmapped')), headers.map((h, i) => /*#__PURE__*/React.createElement("option", {
    key: i,
    value: i
  }, h))), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, t('sample'), ": ", map[k] !== '' && map[k] != null ? String(file.rows[0][map[k]] || '—') : '—')))), !mapOk && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger"
  }, t('map_need')), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    onClick: () => setStep(0)
  }, t('back')), /*#__PURE__*/React.createElement(Button, {
    disabled: !mapOk,
    onClick: runCheck,
    icon: "scan-search"
  }, t('s_check')))), (step === 2 || step === 4) && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      gap: 14,
      padding: '30px 0',
      textAlign: 'center'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "loader-circle",
    size: 36,
    color: "var(--track-green)",
    style: {
      animation: 'track-spin 1s linear infinite'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 16px var(--font-sans)'
    }
  }, step === 2 ? t('checking') : t('importing')), /*#__PURE__*/React.createElement("div", {
    style: {
      width: 'min(420px,100%)'
    }
  }, /*#__PURE__*/React.createElement(ProgressBar, {
    value: prog,
    tone: "var(--track-green)"
  })), step === 4 && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '500 12px var(--font-numeric)',
      color: 'var(--text-secondary)'
    }
  }, t('chunk', {
    a: Math.max(1, Math.ceil(prog / 25)),
    b: 4
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 13px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, t('checking_hint'), step === 2 && ' ' + t('nothing_saved')), /*#__PURE__*/React.createElement(Button, {
    variant: "secondary",
    size: "sm",
    icon: "house",
    onClick: () => go('home')
  }, t('nav_home'))), step === 3 && res && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 16
    }
  }, /*#__PURE__*/React.createElement(Alert, {
    tone: "info",
    icon: "shield-check"
  }, t('nothing_saved')), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(130px,1fr))',
      gap: 12
    }
  }, mode === 'central' && /*#__PURE__*/React.createElement(StatCard, {
    value: new Set(res.filter(r => r.status !== 'error' && r.flags.course).map(r => r.sec)).size + 1,
    label: t('p_new_sections')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: res.filter(r => r.status !== 'error' && !r.flags.existing).length,
    label: t('p_new_students'),
    tone: "green"
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: res.filter(r => r.status !== 'error' && !(mode === 'section' && st.S.enrollments.some(e => e.section === sectionId && e.status === 'active' && (st.student(e.student) || {}).uid === r.uid))).length,
    label: t('p_new_enroll'),
    tone: "green"
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: res.filter(r => r.flags.existing && r.status !== 'error').length,
    label: t('p_matched')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: cnt('warning'),
    label: t('p_warnings'),
    tone: "dark"
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: cnt('error'),
    label: t('p_errors'),
    tone: "dark"
  })), mode === 'central' && (res.some(r => r.flags.course) || res.some(r => r.flags.inst)) && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(300px,1fr))',
      gap: 12
    }
  }, res.some(r => r.flags.course) && /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 14,
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      display: 'flex',
      flexDirection: 'column',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement("b", {
    style: {
      font: '700 13px var(--font-sans)'
    }
  }, t('opt_courses'), ": PHYS110"), /*#__PURE__*/React.createElement(Checkbox, {
    label: t('opt_courses_q'),
    checked: opt.course,
    onChange: v => setOpt({
      ...opt,
      course: v
    })
  }), !opt.course && /*#__PURE__*/React.createElement("span", {
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, t('skip_rows'))), res.some(r => r.flags.inst) && /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 14,
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      display: 'flex',
      flexDirection: 'column',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement("b", {
    style: {
      font: '700 13px var(--font-sans)'
    }
  }, t('opt_inst'), ": b.alsahli@mu.edu.sa"), /*#__PURE__*/React.createElement(Checkbox, {
    label: t('opt_inst_q'),
    checked: opt.inst,
    onChange: v => setOpt({
      ...opt,
      inst: v
    })
  }), !opt.inst && /*#__PURE__*/React.createElement("span", {
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, t('skip_rows')))), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 14px var(--font-sans)'
    }
  }, t('first100')), /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: 'n',
      label: t('row'),
      numeric: true
    }, {
      key: 'uid',
      label: t('uid'),
      numeric: true,
      render: r => r.uid || '—'
    }, {
      key: 'name',
      label: t('student')
    }, ...(mode === 'central' ? [{
      key: 'sec',
      label: t('course') + ' / ' + t('section_no'),
      numeric: true
    }] : []), {
      key: 's',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.status
      })
    }, {
      key: 'r',
      label: t('reason'),
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          font: '400 12px/1.5 var(--font-sans)',
          color: r.status === 'error' ? 'var(--red-600)' : 'var(--text-secondary)'
        }
      }, rsn(r))
    }],
    rows: res.slice(0, 100)
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    onClick: () => setStep(1)
  }, t('back')), /*#__PURE__*/React.createElement(Button, {
    icon: "check",
    onClick: runImport
  }, t('confirm_import')))), step === 5 && report && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 16
    }
  }, /*#__PURE__*/React.createElement(Alert, {
    tone: "success",
    title: t('import_done')
  }, file.name), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(4,minmax(0,1fr))',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement(StatCard, {
    value: report.processed,
    label: t('processed')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: report.imported,
    label: t('imported'),
    tone: "green"
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: report.duplicates,
    label: t('duplicates')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: report.errors,
    label: t('errors'),
    tone: "dark"
  })), report.errs.length > 0 && /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: 'n',
      label: t('row'),
      numeric: true
    }, {
      key: 'c',
      label: lang === 'en' ? 'Column' : 'العمود',
      render: r => {
        const k = r.reasons[0];
        return k && k[1].c ? t(k[1].c) : k && k[0] === 'r_uid' ? t('f_uid') : k && k[0] === 'r_branch' ? t('f_branch') : k && k[0] === 'r_course' ? t('f_course_code') : k && k[0] === 'r_inst' ? t('f_inst_email') : '—';
      }
    }, {
      key: 'v',
      label: lang === 'en' ? 'Submitted value' : 'القيمة المُدخلة',
      numeric: true,
      render: r => r.uid || '—'
    }, {
      key: 'r',
      label: t('reason'),
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          color: 'var(--red-600)',
          font: '400 12px/1.5 var(--font-sans)'
        }
      }, rsn(r))
    }],
    rows: report.errs
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 10,
      justifyContent: 'flex-end',
      flexWrap: 'wrap'
    }
  }, report.errs.length > 0 && /*#__PURE__*/React.createElement(Button, {
    variant: "secondary",
    icon: "download",
    onClick: () => notify(t('download_errors'), report.errs.length + ' ' + t('rows'))
  }, t('download_errors')), mode === 'section' && /*#__PURE__*/React.createElement(Button, {
    variant: "secondary",
    onClick: () => go('section', sectionId)
  }, lang === 'en' ? 'Open section' : 'فتح الشعبة'), /*#__PURE__*/React.createElement(Button, {
    icon: "rotate-ccw",
    onClick: () => {
      setStep(0);
      setFile(null);
      setRes(null);
      setReport(null);
    }
  }, t('new_import2'))))));
}
Object.assign(window, {
  ImportsScreen,
  ImportWizard
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/ImportScreens.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/InitiativesScreen.jsx
try { (() => {
function StagesEditor({
  stages,
  setStages
}) {
  const {
    Icon,
    Button
  } = window.DS;
  const {
    lang
  } = useApp();
  const en = lang === 'en';
  const mv = (i, d) => {
    const n = stages.slice();
    const j = i + d;
    if (j < 0 || j >= n.length) return;
    [n[i], n[j]] = [n[j], n[i]];
    setStages(n);
  };
  const sq = {
    border: '1px solid var(--border-default)',
    background: '#fff',
    borderRadius: 8,
    width: 32,
    height: 32,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    flex: 'none'
  };
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 8
    }
  }, stages.map((x, i) => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      display: 'flex',
      gap: 8,
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 26,
      height: 26,
      borderRadius: 99,
      background: i === stages.length - 1 ? 'var(--green-100)' : 'var(--blue-50)',
      color: i === stages.length - 1 ? 'var(--green-700)' : 'var(--track-blue)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      font: '700 12px var(--font-numeric)',
      flex: 'none'
    }
  }, i + 1), /*#__PURE__*/React.createElement("input", {
    "aria-label": (en ? 'Status ' : 'الحالة ') + (i + 1),
    value: x,
    placeholder: en ? 'Status name' : 'اسم الحالة',
    onChange: e => {
      const n = stages.slice();
      n[i] = e.target.value;
      setStages(n);
    },
    style: {
      ...nativeStyle,
      flex: 1,
      minWidth: 0
    }
  }), /*#__PURE__*/React.createElement("button", {
    onClick: () => mv(i, -1),
    "aria-label": en ? 'Move up' : 'تحريك لأعلى',
    style: sq
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "arrow-up",
    size: 15
  })), /*#__PURE__*/React.createElement("button", {
    onClick: () => mv(i, 1),
    "aria-label": en ? 'Move down' : 'تحريك لأسفل',
    style: sq
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "arrow-down",
    size: 15
  })), /*#__PURE__*/React.createElement("button", {
    onClick: () => setStages(stages.filter((_, j) => j !== i)),
    "aria-label": en ? 'Remove' : 'حذف',
    style: {
      ...sq,
      border: 'none',
      background: 'var(--red-50)',
      color: 'var(--red-600)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "trash-2",
    size: 15
  }))))), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 10
    }
  }, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "plus",
    onClick: () => setStages([...stages, ''])
  }, en ? 'Add status' : 'إضافة حالة')));
}
function SectionFilePicker({
  term,
  termSecs,
  onAdd
}) {
  const {
    Button,
    Alert,
    DataTable,
    StatusBadge,
    Icon
  } = window.DS;
  const {
    lang
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const ref = React.useRef(null);
  const [res, setRes] = React.useState(null);
  const [err, setErr] = React.useState(null);
  const [name, setName] = React.useState('');
  const match = p => {
    const H = p.headers.map(h => String(h || ''));
    const iNo = H.findIndex(h => /رقم الشعبة|^الشعبة$|section/i.test(h));
    const iC = H.findIndex(h => /رمز المقرر|course code|^code$/i.test(h));
    const iB = H.findIndex(h => /الفرع|المقر|branch|campus/i.test(h));
    let items = [];
    if (p.meta && p.meta.section && iNo < 0) items = [{
      n: 1,
      no: p.meta.section,
      code: '',
      course: p.meta.course || '',
      branch: p.meta.campus || ''
    }];else {
      if (iNo < 0) throw new Error('cols');
      items = p.rows.map((r, k) => ({
        n: k + 2,
        no: String(r[iNo] || '').trim(),
        code: iC >= 0 ? String(r[iC] || '').trim() : '',
        branch: iB >= 0 ? String(r[iB] || '').trim() : ''
      }));
    }
    const seen = {};
    return items.map(it => {
      let c = termSecs.filter(s => s.no === it.no);
      if (it.code) c = c.filter(s => st.course(s.course).code.toUpperCase() === it.code.toUpperCase());
      if (it.course) c = c.filter(s => {
        const x = st.course(s.course);
        return x.ar === it.course || x.en === it.course;
      });
      if (it.branch && c.length > 1) c = c.filter(s => {
        const b = st.S.branches.find(x => x.id === s.branch);
        return it.branch.includes(b.ar) || it.branch.toLowerCase().includes(b.en.toLowerCase());
      });
      const key = c.length === 1 ? c[0].id : null;
      let status, reason;
      if (!it.no) {
        status = 'error';
        reason = en ? 'Missing section number' : 'رقم الشعبة مفقود';
      } else if (c.length === 0) {
        status = 'error';
        reason = en ? 'Section not found in this term' : 'الشعبة غير مسجلة في هذا الفصل';
      } else if (c.length > 1) {
        status = 'warning';
        reason = en ? 'Matches several sections — add course code or branch' : 'تطابق أكثر من شعبة — أضف رمز المقرر أو الفرع';
      } else if (seen[key]) {
        status = 'warning';
        reason = (en ? 'Duplicate of row ' : 'مكرر مع الصف ') + seen[key];
      } else {
        seen[key] = it.n;
        status = 'valid';
        reason = st.course(c[0].course).code + ' / ' + c[0].no + ' · ' + st.branchLabel(c[0].branch, lang);
      }
      return {
        ...it,
        id: it.n,
        status,
        reason,
        sid: status === 'valid' ? key : null
      };
    });
  };
  const onFile = async ev => {
    const f = ev.target.files && ev.target.files[0];
    ev.target.value = '';
    if (!f) return;
    try {
      const p = await window.parseRosterFile(f);
      setName(p.name);
      setErr(null);
      setRes(match(p));
    } catch (x) {
      setRes(null);
      setErr(x.message === 'cols' ? en ? 'The file needs a “section number” column.' : 'يجب أن يحتوي الملف على عمود «رقم الشعبة».' : x.message === 'size' ? en ? 'File exceeds 20 MB.' : 'الملف يتجاوز 20 ميجابايت.' : en ? 'Unsupported file. Use xlsx or csv.' : 'نوع الملف غير مدعوم. استخدم xlsx أو csv.');
    }
  };
  const sample = () => {
    setName('sections_list.xlsx');
    setErr(null);
    setRes(match({
      headers: ['الفرع', 'رمز المقرر', 'رقم الشعبة'],
      rows: [['المجمعة', 'CS212', '2298'], ['الزلفي', 'ENG102', '214'], ['', 'MATH101', '123'], ['الزلفي', 'MATH101', '215'], ['رماح', 'ISLM101', '157'], ['المجمعة', 'CS101', '999'], ['الزلفي', 'ENG102', '214']]
    }));
  };
  const ok = res ? res.filter(r => r.sid) : [];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      border: '2px dashed var(--border-strong)',
      borderRadius: 'var(--radius-lg)',
      padding: '22px 16px',
      textAlign: 'center',
      background: 'var(--gray-50)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "file-spreadsheet",
    size: 30,
    color: "var(--track-green)",
    style: {
      margin: '0 auto 8px'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 14px var(--font-sans)'
    }
  }, en ? 'Upload a list of sections' : 'ارفع قائمة الشعب'), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 12px/1.6 var(--font-sans)',
      color: 'var(--text-secondary)',
      marginTop: 4
    }
  }, en ? 'Columns: section number (required), course code and branch (recommended). A section roster file with a header block also works.' : 'الأعمدة: رقم الشعبة (إلزامي)، رمز المقرر والفرع (يُنصح بهما). ويُقبل أيضًا كشف الشعبة بصيغة ملف الطلبة.'), /*#__PURE__*/React.createElement("input", {
    ref: ref,
    type: "file",
    accept: ".xlsx,.csv",
    onChange: onFile,
    style: {
      display: 'none'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      justifyContent: 'center',
      marginTop: 12,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "download",
    onClick: () => downloadTemplate('track_sections_list_template.csv', [['الفرع', 'رمز المقرر', 'رقم الشعبة'], ['المجمعة', 'CS212', '2298'], ['الزلفي', 'ENG102', '214']])
  }, en ? 'Download template' : 'تنزيل القالب'), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "folder-open",
    onClick: () => ref.current && ref.current.click()
  }, en ? 'Choose file' : 'اختيار ملف'), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "ghost",
    onClick: sample
  }, en ? 'Use sample file' : 'استخدام ملف تجريبي'))), err && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger"
  }, err), res && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 8,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '600 13px var(--font-sans)'
    }
  }, name, " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--green-700)'
    }
  }, ok.length, " ", en ? 'matched' : 'مطابقة'), " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--red-600)'
    }
  }, res.length - ok.length, " ", en ? 'not added' : 'لن تُضاف')), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    icon: "check",
    disabled: !ok.length,
    onClick: () => onAdd(ok.map(r => r.sid))
  }, en ? 'Add matched sections' : 'إضافة الشعب المطابقة')), /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: 'n',
      label: en ? 'Row' : 'الصف',
      numeric: true
    }, {
      key: 'b',
      label: en ? 'Branch' : 'الفرع',
      render: r => r.branch || '—'
    }, {
      key: 'code',
      label: en ? 'Course' : 'المقرر',
      numeric: true,
      render: r => r.code || r.course || '—'
    }, {
      key: 'no',
      label: en ? 'Section' : 'الشعبة',
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r.no || '—')
    }, {
      key: 's',
      label: en ? 'Result' : 'النتيجة',
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.status
      })
    }, {
      key: 'r',
      label: en ? 'Details' : 'التفاصيل',
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          font: '400 12px var(--font-sans)',
          color: r.status === 'error' ? 'var(--red-600)' : 'var(--text-secondary)'
        }
      }, r.reason)
    }],
    rows: res
  })));
}
function InitiativeDialog({
  ini,
  onClose,
  notify
}) {
  const {
    Dialog,
    Button,
    Input,
    Select,
    Checkbox,
    Tabs,
    Alert
  } = window.DS;
  const {
    lang,
    me,
    t
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const isNew = !ini.id;
  const [step, setStep] = React.useState('info');
  const [f, setF] = React.useState({
    ar: ini.ar || '',
    en: ini.en || '',
    term: ini.term || st.currentTerm().id,
    start: ini.start || '2026-10-15',
    end: ini.end || '2027-01-05',
    owner: ini.owner || 'عمادة التطوير الأكاديمي',
    desc: ini.desc || '',
    status: ini.status || 'draft'
  });
  const [stages, setStages] = React.useState(ini.id ? (st.TPL[ini.id] || []).slice() : ['تسجيل الطالب', 'التوثيق', 'الانتهاء']);
  const assigned = ini.id ? st.S.assignments.filter(a => a.ini === ini.id).map(a => a.section) : [];
  const [secs, setSecs] = React.useState(assigned);
  const [e, setE] = React.useState({});
  const [busy, setBusy] = React.useState(false);
  const [src, setSrc] = React.useState('list');
  const termSecs = st.S.sections.filter(s => s.term === +f.term);
  const byBranch = st.S.branches.map(b => ({
    b,
    list: termSecs.filter(s => s.branch === b.id)
  })).filter(x => x.list.length);
  const set = (k, v) => setF({
    ...f,
    [k]: v
  });
  const save = () => {
    const clean = stages.map(x => x.trim()).filter(Boolean);
    const n = {
      ar: f.ar.trim() ? null : t('required'),
      en: f.en.trim() ? null : t('required'),
      end: f.end <= f.start ? t('end_after') : null,
      stages: clean.length ? null : en ? 'Add at least one status.' : 'أضف حالة واحدة على الأقل.'
    };
    setE(n);
    if (n.ar || n.en || n.end) {
      setStep('info');
      return;
    }
    if (n.stages) {
      setStep('stages');
      return;
    }
    setBusy(true);
    setTimeout(() => {
      let obj = ini;
      if (isNew) {
        obj = {
          id: Date.now(),
          ...f,
          term: +f.term
        };
        st.initiatives.push(obj);
        st.audit(me.id, 'initiative.created', 'initiative', obj.id, null, {
          name_ar: f.ar,
          stages: clean,
          sections: secs.length
        });
      } else {
        const old = {
          ar: ini.ar,
          start: ini.start,
          end: ini.end,
          status: ini.status
        };
        Object.assign(ini, f, {
          term: +f.term
        });
        st.audit(me.id, 'initiative.updated', 'initiative', ini.id, old, {
          ar: f.ar,
          start: f.start,
          end: f.end,
          status: f.status
        });
      }
      st.TPL[obj.id] = clean;
      secs.forEach(sid => {
        if (!st.S.assignments.some(a => a.ini === obj.id && a.section === sid)) {
          st.S.assignments.push({
            id: Date.now() + sid,
            section: sid,
            ini: obj.id,
            stages: clean.slice()
          });
          st.audit(me.id, 'section.initiative_assigned', 'section', sid, null, {
            initiative: obj.ar,
            stages: clean
          });
        }
      });
      st.emit();
      setBusy(false);
      onClose();
      notify(isNew ? en ? 'Initiative created' : 'تم إنشاء المبادرة' : en ? 'Initiative saved' : 'تم حفظ المبادرة', secs.length + ' ' + (en ? 'sections' : 'شعبة'));
    }, 600);
  };
  const toggle = (id, on) => setSecs(on ? [...secs, id] : secs.filter(x => x !== id));
  return /*#__PURE__*/React.createElement(Dialog, {
    title: isNew ? en ? 'Create initiative' : 'إنشاء مبادرة' : en ? 'Edit initiative' : 'تعديل المبادرة',
    onClose: onClose,
    width: 680,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: save
    }, isNew ? en ? 'Create initiative' : 'إنشاء المبادرة' : t('save')))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: -8,
      marginBottom: 16
    }
  }, /*#__PURE__*/React.createElement(Tabs, {
    value: step,
    onChange: setStep,
    tabs: [{
      id: 'info',
      label: en ? 'Details' : 'البيانات'
    }, {
      id: 'stages',
      label: en ? 'Student statuses' : 'حالات الطالب',
      count: stages.filter(x => x.trim()).length
    }, {
      id: 'sections',
      label: en ? 'Target sections' : 'الشعب المستهدفة',
      count: secs.length
    }]
  })), step === 'info' && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('name_ar'),
    required: true,
    value: f.ar,
    onChange: x => set('ar', x.target.value),
    onBlur: () => setE({
      ...e,
      ar: f.ar.trim() ? null : t('required')
    }),
    error: e.ar,
    placeholder: "\u0645\u062B\u0627\u0644: \u0645\u0647\u0627\u0631\u0627\u062A \u0627\u0644\u0645\u0633\u062A\u0642\u0628\u0644"
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('name_en'),
    required: true,
    dir: "ltr",
    value: f.en,
    onChange: x => set('en', x.target.value),
    onBlur: () => setE({
      ...e,
      en: f.en.trim() ? null : t('required')
    }),
    error: e.en,
    placeholder: "e.g. Future Skills"
  }), /*#__PURE__*/React.createElement(Select, {
    label: t('f_term'),
    value: f.term,
    onChange: x => {
      set('term', x.target.value);
      setSecs([]);
    },
    options: st.S.terms.filter(x => x.status !== 'archived').map(x => ({
      value: x.id,
      label: en ? x.en : x.ar
    }))
  }), /*#__PURE__*/React.createElement(Input, {
    label: en ? 'Owner' : 'الجهة المالكة',
    value: f.owner,
    onChange: x => set('owner', x.target.value),
    placeholder: en ? 'e.g. Deanship of Academic Development' : 'مثال: عمادة التطوير الأكاديمي'
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('start'),
    type: "date",
    value: f.start,
    onChange: x => set('start', x.target.value)
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('end'),
    type: "date",
    value: f.end,
    onChange: x => set('end', x.target.value),
    error: e.end
  }), /*#__PURE__*/React.createElement("label", {
    style: {
      gridColumn: '1 / -1',
      display: 'flex',
      flexDirection: 'column',
      gap: 6
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '600 13px var(--font-sans)'
    }
  }, en ? 'Description' : 'الوصف'), /*#__PURE__*/React.createElement("textarea", {
    value: f.desc,
    onChange: x => set('desc', x.target.value),
    rows: 3,
    style: {
      ...nativeStyle,
      height: 'auto',
      padding: '10px 12px',
      resize: 'vertical',
      font: '400 14px/1.6 var(--font-sans)'
    }
  })), /*#__PURE__*/React.createElement(Select, {
    label: t('status'),
    value: f.status,
    onChange: x => set('status', x.target.value),
    options: [{
      value: 'draft',
      label: en ? 'Draft' : 'مسودة'
    }, {
      value: 'active',
      label: en ? 'Active' : 'فعّالة'
    }, {
      value: 'closed',
      label: en ? 'Closed' : 'مغلقة'
    }]
  })), step === 'stages' && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement(Alert, {
    tone: "info"
  }, en ? 'These statuses become columns in every target section. The instructor ticks them for each student, in order; the last one means the student completed the initiative. You can still adjust them per section later.' : 'تصبح هذه الحالات أعمدة في كل شعبة مستهدفة، يؤشّر عليها المدرس لكل طالب بالترتيب، والحالة الأخيرة تعني إنهاء الطالب للمبادرة. يمكن تعديلها لكل شعبة لاحقًا.'), /*#__PURE__*/React.createElement(StagesEditor, {
    stages: stages,
    setStages: x => {
      setStages(x);
      setE({
        ...e,
        stages: null
      });
    }
  }), e.stages && /*#__PURE__*/React.createElement("div", {
    role: "alert",
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--red-600)'
    }
  }, e.stages)), step === 'sections' && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Tabs, {
    variant: "segmented",
    value: src,
    onChange: setSrc,
    tabs: [{
      id: 'list',
      label: en ? 'From registered sections' : 'من الشعب المسجلة'
    }, {
      id: 'file',
      label: en ? 'Upload Excel file' : 'رفع ملف Excel'
    }]
  }), src === 'file' && /*#__PURE__*/React.createElement(SectionFilePicker, {
    term: +f.term,
    termSecs: termSecs,
    onAdd: ids => {
      setSecs([...new Set([...secs, ...ids])]);
      setSrc('list');
    }
  }), src === 'list' && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '400 13px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, en ? 'Sections registered in' : 'الشعب المسجلة في', " ", (() => {
    const x = st.term(+f.term);
    return x ? en ? x.en : x.ar : '';
  })(), " \xB7 ", secs.length, "/", termSecs.length), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 6
    }
  }, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "ghost",
    onClick: () => setSecs(termSecs.map(s => s.id))
  }, en ? 'Select all' : 'تحديد الكل'), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "ghost",
    onClick: () => setSecs(assigned)
  }, en ? 'Clear' : 'إلغاء التحديد'))), byBranch.length === 0 && /*#__PURE__*/React.createElement(Alert, {
    tone: "warning"
  }, en ? 'No sections in this term yet.' : 'لا توجد شعب في هذا الفصل بعد.'), byBranch.map(({
    b,
    list
  }) => /*#__PURE__*/React.createElement("div", {
    key: b.id,
    style: {
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      padding: 12
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: 10
    }
  }, /*#__PURE__*/React.createElement("b", {
    style: {
      font: '700 13px var(--font-sans)'
    }
  }, st.branchLabel(b.id, lang)), /*#__PURE__*/React.createElement(Checkbox, {
    label: en ? 'All' : 'الكل',
    checked: list.every(s => secs.includes(s.id)),
    onChange: v => setSecs(v ? [...new Set([...secs, ...list.map(s => s.id)])] : secs.filter(x => !list.some(s => s.id === x) || assigned.includes(x)))
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fill,minmax(190px,1fr))',
      gap: 8
    }
  }, list.map(s => {
    const c = st.course(s.course);
    const locked = assigned.includes(s.id);
    return /*#__PURE__*/React.createElement(Checkbox, {
      key: s.id,
      disabled: locked,
      checked: secs.includes(s.id),
      onChange: v => toggle(s.id, v),
      label: /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("span", {
        style: {
          fontFamily: 'var(--font-latin)'
        }
      }, c.code, "/", s.no), " ", /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-secondary)'
        }
      }, "\xB7 ", en ? c.en : c.ar))
    });
  })))), assigned.length > 0 && /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, en ? 'Already-assigned sections are locked here; manage them from the section page.' : 'الشعب المسندة سابقًا مثبّتة هنا، وتُدار من صفحة الشعبة.'))));
}
function InitiativesScreen({
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    Tabs,
    Input,
    ProgressBar
  } = window.DS;
  const {
    lang,
    me,
    go,
    t
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const [edit, setEdit] = React.useState(null);
  const [tab, setTab] = React.useState('all');
  const [q, setQ] = React.useState('');
  const scope = new Set(st.sectionScope(me).map(s => s.id));
  const list = st.initiatives.filter(i => (tab === 'all' || i.status === tab) && (i.ar + i.en).toLowerCase().includes(q.toLowerCase()));
  const secsOf = i => st.S.assignments.filter(a => a.ini === i.id && scope.has(a.section));
  const pct = i => {
    const as = secsOf(i);
    let tot = 0,
      done = 0;
    as.forEach(a => {
      st.S.enrollments.filter(e => e.section === a.section && e.status === 'active').forEach(e => {
        tot++;
        const m = st.marks(a, e.student);
        if (m[m.length - 1]) done++;
      });
    });
    return tot ? Math.round(done / tot * 100) : 0;
  };
  const stTone = s => s === 'active' ? 'active' : s === 'draft' ? 'draft' : 'closed';
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_initiatives'),
    subtitle: en ? 'Initiatives run inside course sections. Each has its own student statuses.' : 'المبادرات التي تُنفَّذ داخل الشعب الدراسية، ولكل منها حالات الطالب الخاصة بها.',
    actions: me.role === 'SUPER_ADMIN' && /*#__PURE__*/React.createElement(Button, {
      icon: "plus",
      onClick: () => setEdit({})
    }, en ? 'Create initiative' : 'إنشاء مبادرة')
  }), /*#__PURE__*/React.createElement(Panel, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 16,
      padding: '8px 20px 0',
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Tabs, {
    value: tab,
    onChange: setTab,
    tabs: [{
      id: 'all',
      label: t('all'),
      count: st.initiatives.length
    }, {
      id: 'active',
      label: en ? 'Active' : 'فعّالة',
      count: st.initiatives.filter(i => i.status === 'active').length
    }, {
      id: 'draft',
      label: en ? 'Draft' : 'مسودة',
      count: st.initiatives.filter(i => i.status === 'draft').length
    }, {
      id: 'closed',
      label: en ? 'Closed' : 'مغلقة',
      count: st.initiatives.filter(i => i.status === 'closed').length
    }]
  }), /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    icon: "search",
    placeholder: en ? 'Search initiatives' : 'بحث في المبادرات',
    value: q,
    onChange: e => setQ(e.target.value),
    style: {
      width: 240
    }
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    onRowClick: r => go('initiative', r.id),
    columns: [{
      key: 'n',
      label: en ? 'Initiative' : 'المبادرة',
      render: r => /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
        style: {
          fontWeight: 700
        }
      }, en ? r.en : r.ar), /*#__PURE__*/React.createElement("div", {
        style: {
          font: '400 12px var(--font-sans)',
          color: 'var(--text-secondary)'
        }
      }, r.owner))
    }, {
      key: 't',
      label: t('f_term'),
      render: r => {
        const x = st.term(r.term);
        return x ? en ? x.en : x.ar : '—';
      }
    }, {
      key: 'd',
      label: en ? 'Period' : 'الفترة',
      numeric: true,
      render: r => fmtDate(r.start) + ' — ' + fmtDate(r.end)
    }, {
      key: 's',
      label: en ? 'Student statuses' : 'حالات الطالب',
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          display: 'flex',
          gap: 4,
          flexWrap: 'wrap'
        }
      }, (st.TPL[r.id] || []).map((x, i, a) => /*#__PURE__*/React.createElement("span", {
        key: i,
        style: {
          font: '500 11px var(--font-sans)',
          padding: '3px 8px',
          borderRadius: 6,
          background: i === a.length - 1 ? 'var(--green-50)' : 'var(--gray-100)',
          color: i === a.length - 1 ? 'var(--green-700)' : 'var(--text-secondary)'
        }
      }, i + 1, ". ", x)))
    }, {
      key: 'c',
      label: t('sections_n'),
      numeric: true,
      render: r => secsOf(r).length
    }, {
      key: 'p',
      label: en ? 'Completion' : 'نسبة الإنهاء',
      width: 160,
      render: r => /*#__PURE__*/React.createElement(ProgressBar, {
        value: pct(r)
      })
    }, {
      key: 'st',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: stTone(r.status)
      }, r.status === 'active' ? en ? 'Active' : 'فعّالة' : r.status === 'draft' ? en ? 'Draft' : 'مسودة' : en ? 'Closed' : 'مغلقة')
    }, ...(me.role === 'SUPER_ADMIN' ? [{
      key: 'x',
      label: '',
      render: r => /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "pencil",
        onClick: e => {
          e.stopPropagation();
          setEdit(r);
        }
      }, t('edit'))
    }] : [])],
    rows: list,
    empty: en ? 'No initiatives' : 'لا توجد مبادرات'
  }))), edit && /*#__PURE__*/React.createElement(InitiativeDialog, {
    ini: edit,
    onClose: () => setEdit(null),
    notify: notify
  }));
}
function InitiativeDetailScreen({
  id,
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    StatCard,
    ProgressBar
  } = window.DS;
  const {
    lang,
    me,
    go,
    t
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const ini = st.initiatives.find(i => i.id === id);
  const [edit, setEdit] = React.useState(false);
  if (!ini) return /*#__PURE__*/React.createElement(RefusalScreen, {
    kind: "404"
  });
  const scope = new Set(st.sectionScope(me).map(s => s.id));
  const as = st.S.assignments.filter(a => a.ini === id && scope.has(a.section));
  const rows = as.map(a => {
    const sec = st.S.sections.find(s => s.id === a.section);
    const enr = st.S.enrollments.filter(e => e.section === a.section && e.status === 'active');
    const M = enr.map(e => st.marks(a, e.student));
    return {
      id: a.id,
      a,
      sec,
      n: enr.length,
      counts: a.stages.map((_, i) => M.filter(m => m[i]).length),
      done: M.filter(m => m[m.length - 1]).length
    };
  });
  const totN = rows.reduce((x, r) => x + r.n, 0),
    totD = rows.reduce((x, r) => x + r.done, 0);
  const stages = st.TPL[id] || [];
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    crumbs: [{
      label: t('nav_initiatives'),
      onClick: () => go('initiatives')
    }, {
      label: en ? ini.en : ini.ar
    }],
    title: en ? ini.en : ini.ar,
    subtitle: ini.desc,
    actions: me.role === 'SUPER_ADMIN' && /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      icon: "pencil",
      onClick: () => setEdit(true)
    }, t('edit'))
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(150px,1fr))',
      gap: 14,
      marginBottom: 16
    }
  }, /*#__PURE__*/React.createElement(StatCard, {
    value: rows.length,
    label: t('sections_n')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: new Set(rows.map(r => r.sec.branch)).size,
    label: t('branches')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: totN,
    label: t('students_n')
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: totD,
    label: en ? 'Completed' : 'أنهوا المبادرة',
    tone: "green"
  }), /*#__PURE__*/React.createElement(StatCard, {
    value: (totN ? Math.round(totD / totN * 100) : 0) + '%',
    label: en ? 'Completion' : 'نسبة الإنهاء',
    tone: "green"
  })), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 15px var(--font-sans)',
      marginBottom: 14
    }
  }, en ? 'Target sections' : 'الشعب المستهدفة'), /*#__PURE__*/React.createElement(DataTable, {
    onRowClick: r => go('section', r.sec.id),
    columns: [{
      key: 'no',
      label: t('section_no'),
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r.sec.no)
    }, {
      key: 'c',
      label: t('course'),
      render: r => {
        const c = st.course(r.sec.course);
        return /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("span", {
          style: {
            fontFamily: 'var(--font-latin)'
          }
        }, c.code), " ", /*#__PURE__*/React.createElement("span", {
          style: {
            color: 'var(--text-secondary)'
          }
        }, "\xB7 ", en ? c.en : c.ar));
      }
    }, {
      key: 'b',
      label: t('branch'),
      render: r => st.branchLabel(r.sec.branch, lang)
    }, {
      key: 'i',
      label: t('instructor'),
      render: r => r.sec.instructor ? st.uname(r.sec.instructor, lang) : '—'
    }, {
      key: 'n',
      label: t('students_n'),
      numeric: true,
      render: r => r.n
    }, ...stages.map((s, i) => ({
      key: 's' + i,
      label: s,
      numeric: true,
      render: r => (r.counts[i] ?? 0) + '/' + r.n
    })), {
      key: 'p',
      label: en ? 'Completion' : 'نسبة الإنهاء',
      width: 150,
      render: r => /*#__PURE__*/React.createElement(ProgressBar, {
        value: r.n ? Math.round(r.done / r.n * 100) : 0
      })
    }],
    rows: rows,
    empty: en ? 'No sections assigned yet' : 'لم تُسند لأي شعبة بعد'
  })), edit && /*#__PURE__*/React.createElement(InitiativeDialog, {
    ini: ini,
    onClose: () => setEdit(false),
    notify: notify
  }));
}
Object.assign(window, {
  SectionFilePicker,
  StagesEditor,
  InitiativeDialog,
  InitiativesScreen,
  InitiativeDetailScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/InitiativesScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/SectionsScreens.jsx
try { (() => {
function SectionsScreen({
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    Select,
    Input,
    Dialog,
    EmptyState,
    Alert
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const [term, setTerm] = React.useState(st.currentTerm().id);
  const [br, setBr] = React.useState('all');
  const [q, setQ] = React.useState('');
  const [create, setCreate] = React.useState(false);
  const [pg, setPg] = React.useState('all');
  const loading = useFakeLoad(term + br + pg);
  const scope = st.sectionScope(me);
  const rows = scope.filter(s => s.term === term && (br === 'all' || s.branch === +br) && (pg === 'all' || s.program === +pg) && (st.course(s.course).code + s.no).toLowerCase().includes(q.toLowerCase()));
  const visBranches = me.role === 'SUPER_ADMIN' ? st.S.branches : st.S.branches.filter(b => me.branches.includes(b.id));
  const sub = me.role === 'SUPER_ADMIN' ? t('sections_sub_admin') : me.role === 'BRANCH_COORDINATOR' ? t('sections_sub_coord') : t('sections_sub_inst');
  const count = id => st.S.enrollments.filter(e => e.section === id && e.status === 'active').length;
  if (me.role === 'INSTRUCTOR' && scope.length === 0) return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_mysections')
  }), /*#__PURE__*/React.createElement(Panel, null, /*#__PURE__*/React.createElement(EmptyState, {
    icon: "users",
    title: t('no_sections_inst'),
    message: t('no_sections_inst_msg')
  })));
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: me.role === 'INSTRUCTOR' ? t('nav_mysections') : t('nav_sections'),
    subtitle: sub,
    actions: /*#__PURE__*/React.createElement(React.Fragment, null, me.role !== 'SUPER_ADMIN' && /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      size: "sm",
      icon: "flask-conical",
      onClick: () => go('section', 13)
    }, t('demo_scope')), st.can(me, 'section.create') && /*#__PURE__*/React.createElement(Button, {
      icon: "plus",
      onClick: () => setCreate(true)
    }, t('new_section')))
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 12,
      marginBottom: 16,
      flexWrap: 'wrap',
      alignItems: 'flex-end'
    }
  }, /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    icon: "search",
    placeholder: t('course') + ' / ' + t('section_no'),
    value: q,
    onChange: e => setQ(e.target.value),
    style: {
      width: 220
    }
  }), /*#__PURE__*/React.createElement(Select, {
    size: "sm",
    icon: "calendar-range",
    value: term,
    onChange: e => setTerm(+e.target.value),
    options: st.S.terms.map(x => ({
      value: x.id,
      label: lang === 'en' ? x.en : x.ar
    })),
    style: {
      width: 220
    }
  }), me.role !== 'INSTRUCTOR' && /*#__PURE__*/React.createElement(Select, {
    size: "sm",
    icon: "building-2",
    value: br,
    onChange: e => setBr(e.target.value),
    options: [{
      value: 'all',
      label: t('all') + ' — ' + t('branches')
    }, ...visBranches.map(b => ({
      value: b.id,
      label: st.branchLabel(b.id, lang)
    }))],
    style: {
      width: 220
    }
  }), /*#__PURE__*/React.createElement(Select, {
    size: "sm",
    icon: "graduation-cap",
    value: pg,
    onChange: e => setPg(e.target.value),
    options: [{
      value: 'all',
      label: lang === 'en' ? 'All programs' : 'جميع البرامج'
    }, ...st.S.programs.map(p => ({
      value: p.id,
      label: lang === 'en' ? p.en : p.ar
    }))],
    style: {
      width: 220
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1
    }
  }), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "download",
    onClick: () => {
      st.audit(me.id, 'section.exported', 'section', null, null, {
        rows: rows.length
      });
      notify(t('export'), rows.length + ' ' + t('rows'));
    }
  }, t('export'))), /*#__PURE__*/React.createElement(DataTable, {
    loading: loading,
    onRowClick: r => go('section', r.id),
    columns: [{
      key: 'no',
      label: t('section_no'),
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          display: 'flex',
          gap: 8,
          alignItems: 'center'
        }
      }, /*#__PURE__*/React.createElement("b", null, r.no), st.isShared(me, r) && /*#__PURE__*/React.createElement(StatusBadge, {
        status: "shared"
      }))
    }, {
      key: 'c',
      label: t('course'),
      render: r => {
        const c = st.course(r.course);
        return /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("span", {
          style: {
            fontFamily: 'var(--font-latin)'
          }
        }, c.code), " ", /*#__PURE__*/React.createElement("span", {
          style: {
            color: 'var(--text-secondary)'
          }
        }, "\xB7 ", lang === 'en' ? c.en : c.ar));
      }
    }, {
      key: 'p',
      label: lang === 'en' ? 'Program' : 'البرنامج',
      render: r => st.programName(r.program, lang)
    }, {
      key: 'b',
      label: t('branch'),
      render: r => st.branchLabel(r.branch, lang)
    }, ...(me.role === 'INSTRUCTOR' ? [] : [{
      key: 'i',
      label: t('instructor'),
      render: r => r.instructor ? st.uname(r.instructor, lang) : /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-muted)'
        }
      }, t('no_instructor'))
    }]), {
      key: 's',
      label: t('students_n'),
      numeric: true,
      render: r => count(r.id)
    }],
    rows: rows,
    empty: /*#__PURE__*/React.createElement(EmptyState, {
      icon: "users",
      title: lang === 'en' ? 'No sections in this term' : 'لا توجد شعب في هذا الفصل',
      action: st.can(me, 'section.create') && /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        icon: "plus",
        onClick: () => setCreate(true)
      }, t('new_section'))
    })
  })), create && /*#__PURE__*/React.createElement(SectionDialog, {
    onClose: () => setCreate(false),
    notify: notify,
    term: term
  }));
}
function SectionDialog({
  onClose,
  notify,
  term
}) {
  const {
    Dialog,
    Button,
    Input,
    Select,
    Alert
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const [f, setF] = React.useState({
    term,
    branch: 4,
    course: 2,
    program: '',
    no: '214',
    instructor: ''
  });
  const [err, setErr] = React.useState(null);
  const [busy, setBusy] = React.useState(false);
  const save = () => {
    if (!f.no) {
      setErr(t('required'));
      return;
    }
    if (!f.program) {
      setErr(lang === 'en' ? 'Select the academic program for this section.' : 'اختر البرنامج الأكاديمي للشعبة.');
      return;
    }
    const dup = st.S.sections.find(s => s.term === +f.term && s.branch === +f.branch && s.course === +f.course && s.no === f.no.trim());
    if (dup) {
      setErr(t('dup_section', {
        k: st.course(dup.course).code + ' / ' + dup.no + ' / ' + st.branchLabel(dup.branch, lang)
      }));
      return;
    }
    setBusy(true);
    setTimeout(() => {
      const s = {
        id: Date.now(),
        term: +f.term,
        branch: +f.branch,
        course: +f.course,
        no: f.no.trim(),
        program: +f.program,
        instructor: f.instructor ? +f.instructor : null
      };
      st.S.sections.push(s);
      if (s.instructor) st.S.history.push({
        section: s.id,
        instructor: s.instructor,
        from: st.now().slice(0, 10),
        to: null,
        by: me.id
      });
      st.audit(me.id, 'section.created', 'section', s.id, null, {
        course: st.course(s.course).code,
        no: s.no,
        branch: st.branchLabel(s.branch, 'ar'),
        program: st.programName(s.program, 'ar')
      });
      st.emit();
      setBusy(false);
      onClose();
      notify(t('section_created'));
      go('section', s.id);
    }, 500);
  };
  const set = (k, v) => {
    setF({
      ...f,
      [k]: v
    });
    setErr(null);
  };
  return /*#__PURE__*/React.createElement(Dialog, {
    title: t('new_section'),
    onClose: onClose,
    width: 560,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: save
    }, t('create')))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 14
    }
  }, st.S.terms.length === 0 && /*#__PURE__*/React.createElement(Alert, {
    tone: "warning",
    style: {
      gridColumn: '1 / -1'
    }
  }, t('no_term_first')), /*#__PURE__*/React.createElement(Select, {
    label: t('f_term'),
    value: f.term,
    onChange: e => set('term', e.target.value),
    options: st.S.terms.map(x => ({
      value: x.id,
      label: lang === 'en' ? x.en : x.ar
    }))
  }), /*#__PURE__*/React.createElement(Select, {
    label: t('branch'),
    value: f.branch,
    onChange: e => set('branch', e.target.value),
    options: st.S.branches.filter(b => b.active).map(b => ({
      value: b.id,
      label: st.branchLabel(b.id, lang)
    }))
  }), /*#__PURE__*/React.createElement(Select, {
    label: t('course'),
    value: f.course,
    onChange: e => set('course', e.target.value),
    options: st.S.courses.map(c => ({
      value: c.id,
      label: c.code + ' · ' + (lang === 'en' ? c.en : c.ar)
    }))
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('section_no'),
    required: true,
    dir: "ltr",
    value: f.no,
    onChange: e => set('no', e.target.value)
  }), /*#__PURE__*/React.createElement(Select, {
    label: lang === 'en' ? 'Academic program' : 'البرنامج الأكاديمي',
    required: true,
    value: f.program,
    onChange: e => set('program', e.target.value),
    options: [{
      value: '',
      label: lang === 'en' ? 'Select program…' : 'اختر البرنامج…'
    }, ...st.S.programs.filter(p => p.active).map(p => ({
      value: p.id,
      label: p.code + ' · ' + (lang === 'en' ? p.en : p.ar)
    }))],
    style: {
      gridColumn: '1 / -1'
    }
  }), /*#__PURE__*/React.createElement(Select, {
    label: t('instructor'),
    value: f.instructor,
    onChange: e => set('instructor', e.target.value),
    options: [{
      value: '',
      label: t('no_instructor')
    }, ...st.S.users.filter(u => u.role === 'INSTRUCTOR' && u.active).map(u => ({
      value: u.id,
      label: lang === 'en' ? u.en : u.ar
    }))],
    style: {
      gridColumn: '1 / -1'
    }
  }), err && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger",
    style: {
      gridColumn: '1 / -1'
    }
  }, err)));
}
function SectionDetail({
  id,
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    Tabs,
    StatCard,
    Dialog,
    Input,
    Select,
    Alert
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const sec = st.S.sections.find(s => s.id === id);
  const [tab, setTab] = React.useState('ini');
  const [ini, setIni] = React.useState(null);
  const [rq, setRq] = React.useState('');
  const [rf, setRf] = React.useState('all');
  const [wd, setWd] = React.useState(null);
  const [reason, setReason] = React.useState('');
  const [rErr, setRErr] = React.useState(null);
  const [busy, setBusy] = React.useState(false);
  const [enroll, setEnroll] = React.useState(false);
  const [uid, setUid] = React.useState('');
  const [uErr, setUErr] = React.useState(null);
  const [chg, setChg] = React.useState(false);
  const [newI, setNewI] = React.useState('');
  const [grant, setGrant] = React.useState(false);
  const [gUser, setGUser] = React.useState('');
  const denied = React.useRef(false);
  if (!sec || !st.canSee(me, sec)) {
    if (!denied.current) {
      denied.current = true;
      st.audit(me.id, 'access.denied', 'section', id, null, {
        reason: 'out_of_scope'
      });
    }
    return /*#__PURE__*/React.createElement(RefusalScreen, {
      kind: "404"
    });
  }
  const c = st.course(sec.course);
  const tm = st.term(sec.term);
  const enr = st.S.enrollments.filter(e => e.section === id);
  const canWrite = me.role === 'SUPER_ADMIN' || me.role === 'INSTRUCTOR';
  const listLabel = me.role === 'INSTRUCTOR' ? t('nav_mysections') : t('nav_sections');
  const doWithdraw = () => {
    if (!reason.trim()) {
      setRErr(t('required'));
      return;
    }
    setBusy(true);
    setTimeout(() => {
      wd.status = 'withdrawn';
      wd.withdrawn = st.now().slice(0, 10);
      wd.reason = reason;
      st.audit(me.id, 'enrollment.withdrawn', 'enrollment', wd.id, {
        status: 'active'
      }, {
        status: 'withdrawn',
        reason
      });
      st.emit();
      setBusy(false);
      setWd(null);
      setReason('');
      notify(t('withdrawn_ok'));
    }, 500);
  };
  const doEnroll = () => {
    if (!/^\d{9}$/.test(uid)) {
      setUErr(t('uid_bad'));
      return;
    }
    let s = st.S.students.find(x => x.uid === uid);
    if (s && enr.some(e => e.student === s.id && e.status === 'active')) {
      setUErr(t('already_enrolled'));
      return;
    }
    setBusy(true);
    setTimeout(() => {
      if (!s) {
        s = {
          id: Date.now(),
          uid,
          ar: lang === 'en' ? 'طالب جديد' : 'طالب جديد',
          en: 'New student'
        };
        st.S.students.push(s);
        st.audit(me.id, 'student.created', 'student', s.id, null, {
          uid
        });
      }
      const ex = enr.find(e => e.student === s.id);
      if (ex) {
        ex.status = 'active';
        ex.withdrawn = null;
        ex.reason = null;
      } else st.S.enrollments.push({
        id: Date.now() + 1,
        student: s.id,
        section: id,
        status: 'active',
        enrolled: st.now().slice(0, 10)
      });
      st.audit(me.id, 'enrollment.created', 'enrollment', id, null, {
        uid,
        section: sec.no
      });
      st.emit();
      setBusy(false);
      setEnroll(false);
      setUid('');
      notify(t('enrolled_ok'));
    }, 500);
  };
  const doChange = () => {
    setBusy(true);
    setTimeout(() => {
      const old = sec.instructor;
      const h = st.S.history.find(x => x.section === id && !x.to);
      if (h) h.to = st.now().slice(0, 10);
      sec.instructor = +newI;
      st.S.history.push({
        section: id,
        instructor: +newI,
        from: st.now().slice(0, 10),
        to: null,
        by: me.id
      });
      st.audit(me.id, 'section.instructor_changed', 'section', id, {
        instructor: st.uname(old, 'ar')
      }, {
        instructor: st.uname(+newI, 'ar')
      });
      st.emit();
      setBusy(false);
      setChg(false);
      notify(t('instr_changed'));
    }, 500);
  };
  const doGrant = () => {
    if (!gUser) return;
    st.S.grants.push({
      id: Date.now(),
      section: id,
      user: +gUser,
      by: me.id,
      at: st.now().slice(0, 19)
    });
    st.audit(me.id, 'section.access_granted', 'section', id, null, {
      user: st.uname(+gUser, 'ar')
    });
    st.emit();
    setGrant(false);
    setGUser('');
    notify(t('grant_ok'));
  };
  const hist = st.S.history.filter(h => h.section === id).slice().reverse();
  const grants = st.S.grants.filter(g => g.section === id);
  const acts = st.S.audit.filter(a => a.type === 'section' && a.eid === id || a.type === 'enrollment' && enr.some(e => e.id === a.eid));
  const inis = st.S.assignments.filter(a => a.section === id);
  const iniObj = ini && inis.find(a => a.id === ini);
  const [asg, setAsg] = React.useState(undefined);
  const tabs = [{
    id: 'ini',
    label: lang === 'en' ? 'Initiatives' : 'المبادرات',
    count: inis.length
  }, {
    id: 'roster',
    label: t('tab_roster'),
    count: enr.filter(e => e.status === 'active').length
  }];
  if (me.role === 'SUPER_ADMIN') tabs.push({
    id: 'access',
    label: t('tab_access'),
    count: grants.length
  });
  tabs.push({
    id: 'activity',
    label: t('tab_activity'),
    count: acts.length
  });
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    crumbs: [{
      label: listLabel,
      onClick: () => go(me.role === 'INSTRUCTOR' ? 'mysections' : 'sections')
    }, {
      label: c.code + ' / ' + sec.no,
      onClick: iniObj && tab === 'ini' ? () => setIni(null) : undefined
    }, ...(iniObj && tab === 'ini' ? [{
      label: st.iniName(iniObj, lang)
    }] : [])],
    title: /*#__PURE__*/React.createElement("span", {
      style: {
        display: 'inline-flex',
        alignItems: 'center',
        gap: 10,
        flexWrap: 'wrap'
      }
    }, (lang === 'en' ? 'Section ' : 'الشعبة ') + sec.no, /*#__PURE__*/React.createElement("span", {
      style: {
        font: '500 18px var(--font-sans)',
        color: 'var(--text-secondary)'
      }
    }, c.code, " \xB7 ", lang === 'en' ? c.en : c.ar), st.isShared(me, sec) && /*#__PURE__*/React.createElement(StatusBadge, {
      status: "shared"
    })),
    subtitle: st.programName(sec.program, lang) + ' · ' + st.branchLabel(sec.branch, lang) + ' · ' + (lang === 'en' ? tm.en : tm.ar) + (me.role === 'INSTRUCTOR' ? '' : ' · ' + t('instructor') + ': ' + (sec.instructor ? st.uname(sec.instructor, lang) : t('no_instructor'))),
    actions: /*#__PURE__*/React.createElement(React.Fragment, null, st.can(me, 'import.section.run') && (me.role === 'SUPER_ADMIN' || sec.instructor === me.id || st.isShared(me, sec)) && /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      icon: "file-up",
      onClick: () => go('wizard', {
        mode: 'section',
        section: id
      })
    }, t('import_list')), st.can(me, 'enrollment.create') && /*#__PURE__*/React.createElement(Button, {
      icon: "user-plus",
      onClick: () => {
        setEnroll(true);
        setUErr(null);
      }
    }, t('enroll')), st.can(me, 'section.change_instructor') && /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      icon: "repeat",
      onClick: () => {
        setNewI('');
        setChg(true);
      }
    }, t('change_instr')))
  }), /*#__PURE__*/React.createElement(SectionMeta, {
    sec: sec
  }), asg !== undefined && /*#__PURE__*/React.createElement(AssignDialog, {
    sec: sec,
    assign: asg,
    onClose: () => setAsg(undefined),
    notify: notify
  }), /*#__PURE__*/React.createElement(Panel, null, /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '8px 20px 0'
    }
  }, /*#__PURE__*/React.createElement(Tabs, {
    value: tab,
    onChange: v => {
      setTab(v);
      if (v !== 'ini') setIni(null);
    },
    tabs: tabs
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 20
    }
  }, tab === 'ini' && (iniObj ? /*#__PURE__*/React.createElement(InitiativeRoster, {
    sec: sec,
    enr: enr,
    assign: iniObj,
    onBack: () => setIni(null),
    notify: notify
  }) : /*#__PURE__*/React.createElement(InitiativeCards, {
    sec: sec,
    enr: enr,
    list: inis,
    onOpen: setIni,
    onAssign: a => setAsg(a)
  })), tab === 'roster' && /*#__PURE__*/React.createElement(RosterTable, {
    enr: enr,
    sec: sec,
    q: rq,
    setQ: setRq,
    f: rf,
    setF: setRf,
    onWithdraw: r => {
      setWd(r);
      setReason('');
      setRErr(null);
    }
  }), tab === 'access' && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    icon: "share-2",
    onClick: () => setGrant(true)
  }, t('grant'))), /*#__PURE__*/React.createElement(DataTable, {
    columns: [{
      key: 'u',
      label: t('grant_to'),
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          display: 'flex',
          gap: 8,
          alignItems: 'center'
        }
      }, /*#__PURE__*/React.createElement("b", null, st.uname(r.user, lang)), /*#__PURE__*/React.createElement(StatusBadge, {
        status: "shared"
      }))
    }, {
      key: 'b',
      label: t('granted_by'),
      render: r => st.uname(r.by, lang)
    }, {
      key: 'a',
      label: t('granted_at'),
      numeric: true,
      render: r => fmtDateTime(r.at)
    }],
    rows: grants
  })), tab === 'activity' && /*#__PURE__*/React.createElement(AuditTable, {
    rows: acts
  }))), /*#__PURE__*/React.createElement(ConfirmDialog, {
    open: !!wd,
    busy: busy,
    onClose: () => setWd(null),
    onConfirm: doWithdraw,
    title: wd && t('withdraw_q', {
      n: lang === 'en' ? st.student(wd.student).en : st.student(wd.student).ar
    }),
    impact: t('withdraw_impact'),
    confirmLabel: t('withdraw')
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('wd_date'),
    type: "date",
    defaultValue: st.now().slice(0, 10)
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('reason'),
    required: true,
    value: reason,
    onChange: e => {
      setReason(e.target.value);
      setRErr(null);
    },
    onBlur: () => !reason.trim() && setRErr(t('required')),
    error: rErr
  })), /*#__PURE__*/React.createElement(Dialog, {
    open: enroll,
    title: t('enroll'),
    onClose: () => setEnroll(false),
    width: 460,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: () => setEnroll(false)
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: doEnroll
    }, t('enroll')))
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('uid'),
    required: true,
    dir: "ltr",
    inputMode: "numeric",
    placeholder: "441002315",
    value: uid,
    onChange: e => {
      setUid(e.target.value);
      setUErr(null);
    },
    onBlur: () => uid && !/^\d{9}$/.test(uid) && setUErr(t('uid_bad')),
    error: uErr,
    hint: lang === 'en' ? '9 digits' : '9 أرقام'
  })), /*#__PURE__*/React.createElement(ConfirmDialog, {
    open: chg,
    busy: busy,
    onClose: () => setChg(false),
    onConfirm: () => newI && doChange(),
    title: t('change_instr'),
    impact: t('instr_impact'),
    confirmLabel: t('change_instr')
  }, /*#__PURE__*/React.createElement(Select, {
    label: t('instructor'),
    value: newI,
    onChange: e => setNewI(e.target.value),
    options: [{
      value: '',
      label: '—'
    }, ...st.S.users.filter(u => u.role === 'INSTRUCTOR' && u.active && u.id !== sec.instructor).map(u => ({
      value: u.id,
      label: lang === 'en' ? u.en : u.ar
    }))]
  })), /*#__PURE__*/React.createElement(Dialog, {
    open: grant,
    title: t('grant'),
    onClose: () => setGrant(false),
    width: 460,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: () => setGrant(false)
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      onClick: doGrant
    }, t('grant')))
  }, /*#__PURE__*/React.createElement(Select, {
    label: t('grant_to'),
    value: gUser,
    onChange: e => setGUser(e.target.value),
    options: [{
      value: '',
      label: '—'
    }, ...st.S.users.filter(u => u.role === 'INSTRUCTOR' && u.active && u.id !== sec.instructor && !grants.some(g => g.user === u.id)).map(u => ({
      value: u.id,
      label: lang === 'en' ? u.en : u.ar
    }))]
  })));
}
function StudentsScreen() {
  const {
    DataTable,
    Input,
    Select,
    Icon
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const [q, setQ] = React.useState('');
  const [ini, setIni] = React.useState(1);
  const loading = useFakeLoad(ini);
  const rows = st.studentScope(me).filter(s => (s.uid + s.ar + s.en).toLowerCase().includes(q.toLowerCase()));
  const en = lang === 'en';
  const flag = (on, yes, no) => /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: 6,
      font: '600 12px var(--font-sans)',
      color: on ? 'var(--green-700)' : 'var(--text-muted)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 22,
      height: 22,
      borderRadius: 99,
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      background: on ? 'var(--green-100)' : 'var(--gray-100)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: on ? 'check' : 'minus',
    size: 13,
    strokeWidth: 3
  })), on ? yes : no);
  const P = r => st.progress(r.id, ini);
  const tot = rows.length,
    cR = rows.filter(r => P(r).registered).length,
    cD = rows.filter(r => P(r).documented).length,
    cC = rows.filter(r => P(r).completed).length;
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_students'),
    subtitle: t('students_sub')
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 12,
      marginBottom: 16,
      flexWrap: 'wrap',
      alignItems: 'flex-end'
    }
  }, /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    icon: "search",
    placeholder: t('uid') + ' / ' + t('student'),
    value: q,
    onChange: e => setQ(e.target.value),
    style: {
      width: 260
    }
  }), /*#__PURE__*/React.createElement(Select, {
    size: "sm",
    icon: "lightbulb",
    value: ini,
    onChange: e => setIni(+e.target.value),
    options: st.initiatives.map(i => ({
      value: i.id,
      label: en ? i.en : i.ar
    })),
    style: {
      width: 240
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 16,
      font: '500 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, /*#__PURE__*/React.createElement("span", null, en ? 'Registered' : 'سجّلوا', " ", /*#__PURE__*/React.createElement("b", {
    style: {
      fontFamily: 'var(--font-numeric)',
      color: 'var(--text-primary)'
    }
  }, cR, "/", tot)), /*#__PURE__*/React.createElement("span", null, en ? 'Documented' : 'وثّقوا', " ", /*#__PURE__*/React.createElement("b", {
    style: {
      fontFamily: 'var(--font-numeric)',
      color: 'var(--text-primary)'
    }
  }, cD, "/", tot)), /*#__PURE__*/React.createElement("span", null, en ? 'Completed' : 'أنهوا', " ", /*#__PURE__*/React.createElement("b", {
    style: {
      fontFamily: 'var(--font-numeric)',
      color: 'var(--green-700)'
    }
  }, cC, "/", tot)))), /*#__PURE__*/React.createElement(DataTable, {
    loading: loading,
    onRowClick: r => go('student', r.id),
    columns: [{
      key: 'uid',
      label: t('uid'),
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r.uid)
    }, {
      key: 'ar',
      label: t('name_ar')
    }, {
      key: 'en',
      label: t('name_en')
    }, {
      key: 'reg',
      label: en ? 'Registered' : 'قام بالتسجيل',
      render: r => flag(P(r).registered, en ? 'Yes' : 'نعم', en ? 'No' : 'لا')
    }, {
      key: 'doc',
      label: en ? 'Documented' : 'قام بالتوثيق',
      render: r => flag(P(r).documented, en ? 'Yes' : 'نعم', en ? 'No' : 'لا')
    }, {
      key: 'done',
      label: en ? 'Completed' : 'أنهى المبادرة',
      render: r => flag(P(r).completed, en ? 'Completed' : 'مكتمل', en ? 'Not yet' : 'لم يُنهِ')
    }],
    rows: rows
  })));
}
function StudentDetail({
  id
}) {
  const {
    DataTable,
    StatusBadge,
    Card,
    Tabs
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const s = st.student(id);
  const vis = st.studentScope(me).some(x => x.id === id);
  if (!s || !vis) return /*#__PURE__*/React.createElement(RefusalScreen, {
    kind: "404"
  });
  const scope = new Set(st.sectionScope(me).map(x => x.id));
  const enr = st.S.enrollments.filter(e => e.student === id && scope.has(e.section));
  const acts = st.S.audit.filter(a => a.type === 'student' && a.eid === id || a.type === 'enrollment' && enr.some(e => e.id === a.eid));
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    crumbs: [{
      label: me.role === 'INSTRUCTOR' ? t('nav_mysections') : t('nav_sections'),
      onClick: () => go(me.role === 'INSTRUCTOR' ? 'mysections' : 'sections')
    }, {
      label: s.uid
    }],
    title: lang === 'en' ? s.en : s.ar,
    subtitle: t('uid') + ': ' + s.uid + ' · ' + (lang === 'en' ? s.ar : s.en)
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(420px,1fr))',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Card, {
    title: t('enrollments')
  }, /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    onRowClick: r => go('section', r.section),
    columns: [{
      key: 's',
      label: t('course'),
      render: r => {
        const x = st.S.sections.find(y => y.id === r.section);
        return st.course(x.course).code + ' / ' + x.no;
      }
    }, {
      key: 'b',
      label: t('branch'),
      render: r => st.branchLabel(st.S.sections.find(y => y.id === r.section).branch, lang)
    }, {
      key: 'd',
      label: t('enrolled_on'),
      numeric: true,
      render: r => fmtDate(r.enrolled)
    }, {
      key: 'st',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.status === 'active' ? 'active' : 'withdrawn'
      })
    }],
    rows: enr
  })), /*#__PURE__*/React.createElement(Card, {
    title: t('tab_activity')
  }, /*#__PURE__*/React.createElement(AuditTable, {
    rows: acts,
    dense: true
  }))));
}
const stageFlag = (on, label) => /*#__PURE__*/React.createElement("span", {
  style: {
    display: 'inline-flex',
    alignItems: 'center',
    gap: 6,
    font: '600 12px var(--font-sans)',
    color: on ? 'var(--green-700)' : 'var(--text-muted)',
    whiteSpace: 'nowrap'
  }
}, /*#__PURE__*/React.createElement("span", {
  style: {
    width: 22,
    height: 22,
    borderRadius: 99,
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    background: on ? 'var(--green-100)' : 'var(--gray-100)',
    flex: 'none'
  }
}, /*#__PURE__*/React.createElement(window.DS.Icon, {
  name: on ? 'check' : 'minus',
  size: 13,
  strokeWidth: 3
})), label);
const acadTone = a => a === 'منتظم' ? 'active' : a === 'منسحب' ? 'withdrawn' : 'warning';
function SectionMeta({
  sec
}) {
  const {
    lang
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const c = st.course(sec.course);
  const b = st.S.branches.find(x => x.id === sec.branch);
  const items = [[en ? 'Campus' : 'المقر', en ? b.en + ' (' + (b.gender === 'male' ? 'Male' : 'Female') + ')' : b.ar + ' (' + (b.gender === 'male' ? 'طلاب' : 'طالبات') + ')'], [en ? 'Degree' : 'الدرجة', sec.degree], [en ? 'Course' : 'اسم المقرر', en ? c.en : c.ar], [en ? 'Activity' : 'النشاط', sec.activity], [en ? 'Section' : 'الشعبة', sec.no]];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit,minmax(150px,1fr))',
      gap: 0,
      background: '#fff',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-lg)',
      marginBottom: 16,
      overflow: 'hidden'
    }
  }, items.map(([l, v], i) => /*#__PURE__*/React.createElement("div", {
    key: l,
    style: {
      padding: '12px 16px',
      borderInlineStart: i ? '1px solid var(--gray-100)' : 'none'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '500 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, l), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 14px var(--font-sans)',
      color: 'var(--text-primary)',
      marginTop: 2,
      fontFamily: i === 4 ? 'var(--font-numeric)' : undefined
    }
  }, v))));
}
function RosterTable({
  enr,
  sec,
  q,
  setQ,
  f,
  setF,
  onWithdraw
}) {
  const {
    DataTable,
    StatusBadge,
    Button,
    Input,
    Tabs
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const rows = enr.map((e, i) => ({
    ...e,
    _i: e.seq || i + 1,
    _s: st.student(e.student)
  })).sort((a, b) => a._i - b._i).filter(r => (f === 'all' || r.status === f) && (r._s.uid + r._s.ar + r._s.en).toLowerCase().includes(q.toLowerCase()));
  const nA = enr.filter(e => e.status === 'active').length,
    nW = enr.length - nA;
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 12,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Tabs, {
    variant: "segmented",
    value: f,
    onChange: setF,
    tabs: [{
      id: 'all',
      label: t('all'),
      count: enr.length
    }, {
      id: 'active',
      label: en ? 'Enrolled' : 'مسجّل',
      count: nA
    }, {
      id: 'withdrawn',
      label: en ? 'Withdrawn' : 'مسحوب',
      count: nW
    }]
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    icon: "search",
    placeholder: en ? 'Student no. / name' : 'رقم الطالب / الاسم',
    value: q,
    onChange: e => setQ(e.target.value),
    style: {
      width: 240
    }
  }), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "download"
  }, t('export')))), /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: '_i',
      label: en ? 'Seq.' : 'تسلسل',
      numeric: true,
      width: 60,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-secondary)'
        }
      }, r._i)
    }, {
      key: 'uid',
      label: en ? 'Student no.' : 'رقم الطالب',
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r._s.uid)
    }, {
      key: 'ar',
      label: en ? 'Student name' : 'اسم الطالب',
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("a", {
        href: "#",
        onClick: e => {
          e.preventDefault();
          go('student', r._s.id);
        },
        style: {
          fontWeight: 600,
          color: 'var(--text-primary)'
        }
      }, r._s.ar)
    }, {
      key: 'acad',
      label: en ? 'Status' : 'الحالة',
      render: r => r.status === 'withdrawn' ? /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(StatusBadge, {
        status: "withdrawn"
      }, en ? 'Withdrawn' : 'مسحوب من الشعبة'), /*#__PURE__*/React.createElement("div", {
        style: {
          font: '400 11px var(--font-sans)',
          color: 'var(--text-secondary)',
          marginTop: 4,
          whiteSpace: 'normal'
        }
      }, fmtDate(r.withdrawn), " \xB7 ", r.reason)) : /*#__PURE__*/React.createElement(StatusBadge, {
        status: acadTone(r.acad || 'منتظم')
      }, r.acad || 'منتظم')
    }, {
      key: 'x',
      label: '',
      render: r => r.status === 'active' && st.can(me, 'enrollment.withdraw') && /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "user-minus",
        onClick: () => onWithdraw(r)
      }, t('withdraw'))
    }],
    rows: rows,
    empty: en ? 'No students match' : 'لا يوجد طلبة مطابقون'
  }));
}
function InitiativeCards({
  sec,
  enr,
  list,
  onOpen,
  onAssign
}) {
  const {
    Icon,
    ProgressBar,
    EmptyState,
    Button
  } = window.DS;
  const {
    lang,
    me
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const act = enr.filter(e => e.status === 'active');
  const add = me.role === 'SUPER_ADMIN' && /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    icon: "plus",
    onClick: () => onAssign(null)
  }, en ? 'Assign initiative' : 'إسناد مبادرة');
  if (list.length === 0) return /*#__PURE__*/React.createElement(EmptyState, {
    icon: "lightbulb",
    title: en ? 'This section is not part of any initiative' : 'هذه الشعبة غير مسندة لأي مبادرة',
    message: en ? 'The Super Admin assigns initiatives to sections and defines their statuses.' : 'يُسند مسؤول النظام المبادرات إلى الشعب ويحدد حالاتها.',
    action: add
  });
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, add && /*#__PURE__*/React.createElement("div", null, add), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fill,minmax(300px,1fr))',
      gap: 14
    }
  }, list.map(a => {
    const n = act.length || 1;
    const M = act.map(e => st.marks(a, e.student));
    const last = a.stages.length - 1;
    const done = M.filter(m => m[last]).length;
    return /*#__PURE__*/React.createElement("div", {
      key: a.id,
      style: {
        position: 'relative'
      }
    }, /*#__PURE__*/React.createElement("button", {
      onClick: () => onOpen(a.id),
      style: {
        width: '100%',
        textAlign: 'start',
        font: 'inherit',
        cursor: 'pointer',
        background: '#fff',
        border: '1px solid var(--border-default)',
        borderRadius: 'var(--radius-lg)',
        padding: 18,
        display: 'flex',
        flexDirection: 'column',
        gap: 14,
        boxShadow: 'var(--shadow-card)'
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'flex',
        alignItems: 'center',
        gap: 12
      }
    }, /*#__PURE__*/React.createElement("span", {
      style: {
        width: 40,
        height: 40,
        borderRadius: 10,
        background: 'var(--green-50)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center'
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: "lightbulb",
      size: 20,
      color: "var(--track-green)"
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        flex: 1,
        minWidth: 0
      }
    }, /*#__PURE__*/React.createElement("div", {
      style: {
        font: '700 16px var(--font-sans)',
        color: 'var(--text-primary)'
      }
    }, st.iniName(a, lang)), /*#__PURE__*/React.createElement("div", {
      style: {
        font: '400 12px var(--font-sans)',
        color: 'var(--text-secondary)'
      }
    }, act.length, " ", en ? 'students' : 'طالب', " \xB7 ", a.stages.length, " ", en ? 'statuses' : 'حالات')), /*#__PURE__*/React.createElement(Icon, {
      name: en ? 'chevron-right' : 'chevron-left',
      size: 18,
      color: "var(--text-muted)"
    })), /*#__PURE__*/React.createElement("div", {
      style: {
        display: 'grid',
        gridTemplateColumns: 'repeat(' + a.stages.length + ',1fr)',
        gap: 8
      }
    }, a.stages.map((l, i) => {
      const v = M.filter(m => m[i]).length;
      return /*#__PURE__*/React.createElement("div", {
        key: i,
        style: {
          background: 'var(--gray-50)',
          borderRadius: 8,
          padding: '8px 10px',
          minWidth: 0
        }
      }, /*#__PURE__*/React.createElement("div", {
        style: {
          font: '700 17px var(--font-numeric)',
          color: i === last ? 'var(--green-700)' : 'var(--track-blue)'
        }
      }, v, /*#__PURE__*/React.createElement("span", {
        style: {
          font: '500 12px var(--font-numeric)',
          color: 'var(--text-muted)'
        }
      }, "/", act.length)), /*#__PURE__*/React.createElement("div", {
        style: {
          font: '500 12px var(--font-sans)',
          color: 'var(--text-secondary)',
          whiteSpace: 'nowrap',
          overflow: 'hidden',
          textOverflow: 'ellipsis'
        }
      }, l));
    })), /*#__PURE__*/React.createElement(ProgressBar, {
      value: Math.round(done / n * 100)
    })), me.role === 'SUPER_ADMIN' && /*#__PURE__*/React.createElement("button", {
      onClick: () => onAssign(a),
      "aria-label": en ? 'Edit statuses' : 'تعديل الحالات',
      title: en ? 'Edit statuses' : 'تعديل الحالات',
      style: {
        position: 'absolute',
        top: 14,
        insetInlineEnd: 40,
        width: 30,
        height: 30,
        border: 'none',
        borderRadius: 8,
        background: 'var(--gray-100)',
        cursor: 'pointer',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: 'var(--track-blue)'
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: "settings-2",
      size: 16
    })));
  })));
}
function AssignDialog({
  sec,
  assign,
  onClose,
  notify
}) {
  const {
    Dialog,
    Button,
    Select,
    Input,
    Icon,
    Alert
  } = window.DS;
  const {
    lang,
    me
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const used = st.S.assignments.filter(a => a.section === sec.id).map(a => a.ini);
  const avail = st.initiatives.filter(i => assign ? i.id === assign.ini : !used.includes(i.id));
  const [ini, setIni] = React.useState(assign ? assign.ini : avail[0] && avail[0].id);
  const [stages, setStages] = React.useState(assign ? assign.stages.slice() : (st.TPL[avail[0] && avail[0].id] || ['تسجيل الطالب', 'التوثيق', 'الانتهاء']).slice());
  const [err, setErr] = React.useState(null);
  const pick = v => {
    setIni(+v);
    if (!assign) setStages((st.TPL[+v] || ['تسجيل الطالب', 'التوثيق', 'الانتهاء']).slice());
  };
  const save = () => {
    const clean = stages.map(x => x.trim()).filter(Boolean);
    if (clean.length === 0) {
      setErr(en ? 'Add at least one status.' : 'أضف حالة واحدة على الأقل.');
      return;
    }
    if (assign) {
      const old = assign.stages.slice();
      assign.stages = clean;
      st.audit(me.id, 'section.initiative_updated', 'section', sec.id, {
        stages: old
      }, {
        stages: clean
      });
    } else {
      const a = {
        id: Date.now(),
        section: sec.id,
        ini,
        stages: clean
      };
      st.S.assignments.push(a);
      st.audit(me.id, 'section.initiative_assigned', 'section', sec.id, null, {
        initiative: st.iniName(a, 'ar'),
        stages: clean
      });
    }
    st.emit();
    onClose();
    notify(en ? 'Saved' : 'تم الحفظ');
  };
  const mv = (i, d) => {
    const n = stages.slice();
    const j = i + d;
    if (j < 0 || j >= n.length) return;
    [n[i], n[j]] = [n[j], n[i]];
    setStages(n);
  };
  return /*#__PURE__*/React.createElement(Dialog, {
    title: assign ? en ? 'Edit initiative statuses' : 'تعديل حالات المبادرة' : en ? 'Assign initiative to section' : 'إسناد مبادرة للشعبة',
    subtitle: (en ? 'Section ' : 'الشعبة ') + sec.no + ' · ' + (en ? st.course(sec.course).en : st.course(sec.course).ar),
    onClose: onClose,
    width: 560,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, en ? 'Cancel' : 'إلغاء'), /*#__PURE__*/React.createElement(Button, {
      onClick: save,
      disabled: !ini
    }, en ? 'Save' : 'حفظ'))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, avail.length === 0 ? /*#__PURE__*/React.createElement(Alert, {
    tone: "info"
  }, en ? 'All initiatives are already assigned to this section.' : 'جميع المبادرات مسندة لهذه الشعبة.') : /*#__PURE__*/React.createElement(Select, {
    label: en ? 'Initiative' : 'المبادرة',
    value: ini,
    onChange: e => pick(e.target.value),
    options: avail.map(i => ({
      value: i.id,
      label: en ? i.en : i.ar
    }))
  }), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '600 13px var(--font-sans)',
      marginBottom: 4
    }
  }, en ? 'Student statuses in this initiative' : 'حالات الطالب في هذه المبادرة'), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)',
      marginBottom: 10
    }
  }, en ? 'Each status becomes a column the instructor ticks for every student, in order. The last one means completion.' : 'كل حالة تصبح عمودًا يؤشّر عليه المدرس لكل طالب بالترتيب، والحالة الأخيرة تعني إنهاء المبادرة.'), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 8
    }
  }, stages.map((x, i) => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      display: 'flex',
      gap: 8,
      alignItems: 'center'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: 26,
      height: 26,
      borderRadius: 99,
      background: i === stages.length - 1 ? 'var(--green-100)' : 'var(--blue-50)',
      color: i === stages.length - 1 ? 'var(--green-700)' : 'var(--track-blue)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      font: '700 12px var(--font-numeric)',
      flex: 'none'
    }
  }, i + 1), /*#__PURE__*/React.createElement("input", {
    "aria-label": (en ? 'Status ' : 'الحالة ') + (i + 1),
    value: x,
    onChange: e => {
      const n = stages.slice();
      n[i] = e.target.value;
      setStages(n);
      setErr(null);
    },
    style: {
      ...nativeStyle,
      flex: 1
    }
  }), /*#__PURE__*/React.createElement("button", {
    onClick: () => mv(i, -1),
    "aria-label": "up",
    style: {
      border: '1px solid var(--border-default)',
      background: '#fff',
      borderRadius: 8,
      width: 32,
      height: 32,
      cursor: 'pointer',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "arrow-up",
    size: 15
  })), /*#__PURE__*/React.createElement("button", {
    onClick: () => mv(i, 1),
    "aria-label": "down",
    style: {
      border: '1px solid var(--border-default)',
      background: '#fff',
      borderRadius: 8,
      width: 32,
      height: 32,
      cursor: 'pointer',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "arrow-down",
    size: 15
  })), /*#__PURE__*/React.createElement("button", {
    onClick: () => setStages(stages.filter((_, j) => j !== i)),
    "aria-label": en ? 'Remove' : 'حذف',
    style: {
      border: 'none',
      background: 'var(--red-50)',
      color: 'var(--red-600)',
      borderRadius: 8,
      width: 32,
      height: 32,
      cursor: 'pointer',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "trash-2",
    size: 15
  }))))), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 10
    }
  }, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "plus",
    onClick: () => setStages([...stages, ''])
  }, en ? 'Add status' : 'إضافة حالة')), err && /*#__PURE__*/React.createElement("div", {
    role: "alert",
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--red-600)',
      marginTop: 8
    }
  }, err))));
}
function InitiativeRoster({
  sec,
  enr,
  assign,
  onBack,
  notify
}) {
  const {
    DataTable,
    Input,
    Tabs,
    Button,
    StatusBadge,
    Alert
  } = window.DS;
  const {
    t,
    lang,
    go,
    me
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const [q, setQ] = React.useState('');
  const [f, setF] = React.useState('all');
  const canEdit = me.role === 'INSTRUCTOR' && (sec.instructor === me.id || st.isShared(me, sec));
  const all = enr.filter(e => e.status === 'active').map((e, i) => ({
    ...e,
    _i: e.seq || i + 1,
    _s: st.student(e.student),
    _m: st.marks(assign, e.student)
  })).sort((a, b) => a._i - b._i);
  const lvl = r => r._m.filter(Boolean).length;
  const rows = all.filter(r => (f === 'all' || lvl(r) === +f) && (r._s.uid + r._s.ar).toLowerCase().includes(q.toLowerCase()));
  const toggle = (r, i) => {
    const was = r._m.slice();
    const now = st.setMark(assign, r.student, i, !r._m[i]);
    st.audit(me.id, 'student.stage_updated', 'student', r.student, {
      initiative: st.iniName(assign, 'ar'),
      stages: assign.stages.filter((_, k) => was[k])
    }, {
      stages: assign.stages.filter((_, k) => now[k])
    });
    st.emit();
  };
  const segs = [{
    id: 'all',
    label: t('all'),
    count: all.length
  }, {
    id: '0',
    label: en ? 'None yet' : 'لم يبدأ',
    count: all.filter(r => lvl(r) === 0).length
  }, ...assign.stages.map((s, i) => ({
    id: String(i + 1),
    label: s,
    count: all.filter(r => lvl(r) === i + 1).length
  }))];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: 10,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "ghost",
    icon: en ? 'arrow-left' : 'arrow-right',
    onClick: onBack
  }, en ? 'Initiatives' : 'المبادرات'), /*#__PURE__*/React.createElement("span", {
    style: {
      font: '700 16px var(--font-sans)'
    }
  }, st.iniName(assign, lang)), !canEdit && /*#__PURE__*/React.createElement("span", {
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, "\xB7 ", en ? 'Read only — the section instructor updates statuses' : 'للعرض فقط — يحدّث مدرس الشعبة الحالات')), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 12,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Tabs, {
    variant: "segmented",
    value: f,
    onChange: setF,
    tabs: segs
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8
    }
  }, /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    icon: "search",
    placeholder: en ? 'Student no. / name' : 'رقم الطالب / الاسم',
    value: q,
    onChange: e => setQ(e.target.value),
    style: {
      width: 220
    }
  }), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "download"
  }, t('export')))), canEdit && /*#__PURE__*/React.createElement(Alert, {
    tone: "info",
    icon: "mouse-pointer-click"
  }, en ? 'Tick a status to record it. Ticking a later status fills the earlier ones; changes save automatically.' : 'اضغط على الحالة لتسجيلها. التأشير على حالة لاحقة يؤشّر ما قبلها تلقائيًا، والحفظ تلقائي.'), /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: '_i',
      label: en ? 'Seq.' : 'تسلسل',
      numeric: true,
      width: 60,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-secondary)'
        }
      }, r._i)
    }, {
      key: 'uid',
      label: en ? 'Student no.' : 'رقم الطالب',
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r._s.uid)
    }, {
      key: 'ar',
      label: en ? 'Student name' : 'اسم الطالب',
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          fontWeight: 600
        }
      }, r._s.ar)
    }, {
      key: 'acad',
      label: en ? 'Status' : 'الحالة',
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: acadTone(r.acad || 'منتظم')
      }, r.acad || 'منتظم')
    }, ...assign.stages.map((s, i) => ({
      key: 'st' + i,
      label: s,
      render: r => canEdit ? /*#__PURE__*/React.createElement("button", {
        role: "checkbox",
        "aria-checked": r._m[i],
        "aria-label": s + ' — ' + r._s.ar,
        onClick: () => toggle(r, i),
        style: {
          border: 'none',
          background: 'none',
          padding: 0,
          cursor: 'pointer'
        }
      }, stageFlag(r._m[i], r._m[i] ? en ? 'Done' : 'تم' : en ? 'Not yet' : 'لم يتم')) : stageFlag(r._m[i], r._m[i] ? en ? 'Done' : 'تم' : en ? 'Not yet' : 'لم يتم')
    }))],
    rows: rows,
    empty: en ? 'No students match' : 'لا يوجد طلبة مطابقون'
  }));
}
Object.assign(window, {
  SectionsScreen,
  SectionDetail,
  StudentsScreen,
  StudentDetail,
  RosterTable,
  InitiativeCards,
  InitiativeRoster,
  AssignDialog,
  SectionMeta
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/SectionsScreens.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/Shell.jsx
try { (() => {
const {
  t: _t,
  fmtDate,
  fmtDateTime
} = window.TRACK_I18N;
const AppCtx = React.createContext(null);
function useApp() {
  return React.useContext(AppCtx);
}
function useStore() {
  const [, f] = React.useState(0);
  React.useEffect(() => window.TRACK_STORE.subscribe(() => f(x => x + 1)), []);
  return window.TRACK_STORE;
}
function PageHeader({
  title,
  subtitle,
  actions,
  crumbs
}) {
  const {
    Breadcrumbs
  } = window.DS;
  return /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 20
    }
  }, crumbs && /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 10
    }
  }, /*#__PURE__*/React.createElement(Breadcrumbs, {
    items: crumbs
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      justifyContent: 'space-between',
      gap: 16,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      minWidth: 0
    }
  }, /*#__PURE__*/React.createElement("h1", {
    style: {
      margin: 0,
      font: 'var(--text-h1)',
      color: 'var(--text-primary)'
    }
  }, title), subtitle && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '6px 0 0',
      font: '400 14px/1.6 var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, subtitle)), actions && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 10,
      alignItems: 'flex-end',
      flexWrap: 'wrap'
    }
  }, actions)));
}
function Field({
  label,
  children,
  error,
  hint,
  required
}) {
  return /*#__PURE__*/React.createElement("label", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 6
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '600 13px var(--font-sans)',
      color: 'var(--text-primary)'
    }
  }, label, required && /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--red-600)'
    }
  }, " *")), children, (error || hint) && /*#__PURE__*/React.createElement("span", {
    role: error ? 'alert' : undefined,
    style: {
      font: '400 12px var(--font-sans)',
      color: error ? 'var(--red-600)' : 'var(--text-secondary)'
    }
  }, error || hint));
}
const nativeStyle = {
  height: 40,
  padding: '0 12px',
  border: '1px solid var(--border-default)',
  borderRadius: 'var(--radius-md)',
  font: '400 14px var(--font-sans)',
  background: '#fff',
  color: 'var(--text-primary)',
  width: '100%'
};
function ConfirmDialog({
  open,
  title,
  impact,
  confirmLabel,
  tone = 'danger',
  onConfirm,
  onClose,
  children,
  busy
}) {
  const {
    Dialog,
    Button,
    Alert
  } = window.DS;
  const {
    t
  } = useApp();
  return /*#__PURE__*/React.createElement(Dialog, {
    open: open,
    title: title,
    onClose: onClose,
    width: 480,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      variant: tone === 'danger' ? 'danger' : 'primary',
      loading: busy,
      onClick: onConfirm
    }, confirmLabel || t('confirm')))
  }, /*#__PURE__*/React.createElement(Alert, {
    tone: tone === 'danger' ? 'warning' : 'info'
  }, impact), children && /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 14,
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, children));
}
function Panel({
  children,
  style
}) {
  return /*#__PURE__*/React.createElement("section", {
    style: {
      background: '#fff',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-lg)',
      boxShadow: 'var(--shadow-card)',
      minWidth: 0,
      ...style
    }
  }, children);
}
function useFakeLoad(dep) {
  const [l, setL] = React.useState(true);
  React.useEffect(() => {
    setL(true);
    const x = setTimeout(() => setL(false), 450);
    return () => clearTimeout(x);
  }, [dep]);
  return l;
}
const NAV = {
  SUPER_ADMIN: [['home', 'house'], ['dashboard', 'layout-dashboard'], ['initiatives', 'lightbulb'], ['users', 'user-cog'], ['terms', 'calendar-range'], ['branches', 'building-2'], ['programs', 'graduation-cap'], ['courses', 'book-open'], ['sections', 'users'], ['imports', 'file-up'], ['audit', 'shield-check']],
  BRANCH_COORDINATOR: [['home', 'house'], ['initiatives', 'lightbulb'], ['sections', 'users'], ['imports', 'file-up'], ['audit', 'shield-check']],
  INSTRUCTOR: [['home', 'house'], ['mysections', 'users'], ['imports', 'file-up']]
};
function LangSwitch() {
  const {
    lang,
    setLang
  } = useApp();
  return /*#__PURE__*/React.createElement("div", {
    role: "group",
    "aria-label": "Language",
    style: {
      display: 'flex',
      background: 'var(--gray-100)',
      borderRadius: 8,
      padding: 3,
      gap: 2
    }
  }, [['ar', 'عربي'], ['en', 'EN']].map(([k, l]) => /*#__PURE__*/React.createElement("button", {
    key: k,
    "aria-pressed": lang === k,
    onClick: () => setLang(k),
    style: {
      height: 28,
      padding: '0 10px',
      border: 'none',
      borderRadius: 6,
      cursor: 'pointer',
      background: lang === k ? '#fff' : 'transparent',
      boxShadow: lang === k ? 'var(--shadow-xs)' : 'none',
      font: (lang === k ? '700' : '500') + ' 12px var(--font-sans)',
      color: lang === k ? 'var(--track-blue)' : 'var(--text-secondary)'
    }
  }, l)));
}
function Shell({
  children
}) {
  const {
    Sidebar,
    Topbar,
    Icon
  } = window.DS;
  const {
    me,
    route,
    go,
    t,
    lang,
    logout
  } = useApp();
  const items = NAV[me.role].map(([id, icon]) => ({
    id,
    icon,
    label: t('nav_' + id)
  }));
  const base = route.name === 'initiative' ? 'initiatives' : route.name === 'students' || route.name === 'section' || route.name === 'student' ? me.role === 'INSTRUCTOR' ? 'mysections' : 'sections' : route.name === 'wizard' ? 'imports' : route.name;
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      height: '100vh',
      background: 'var(--surface-page)'
    }
  }, /*#__PURE__*/React.createElement(Sidebar, {
    items: items,
    active: base,
    onSelect: id => go(id),
    assetBase: "../../assets/",
    footer: /*#__PURE__*/React.createElement("button", {
      onClick: logout,
      style: {
        display: 'flex',
        alignItems: 'center',
        gap: 12,
        height: 42,
        padding: '0 14px',
        border: 'none',
        borderRadius: 8,
        background: 'transparent',
        color: 'rgba(255,255,255,.75)',
        font: '500 14px var(--font-sans)',
        cursor: 'pointer'
      }
    }, /*#__PURE__*/React.createElement(Icon, {
      name: "log-out",
      size: 19
    }), t('logout'))
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      flex: 1,
      minWidth: 0,
      display: 'flex',
      flexDirection: 'column'
    }
  }, /*#__PURE__*/React.createElement(Topbar, {
    userName: lang === 'en' ? me.en : me.ar,
    userRole: t('role_' + me.role),
    onUserClick: () => go('profile'),
    notifications: false,
    end: /*#__PURE__*/React.createElement(LangSwitch, null)
  }), /*#__PURE__*/React.createElement("main", {
    style: {
      flex: 1,
      overflow: 'auto',
      padding: '28px 28px 48px'
    }
  }, children)));
}
function downloadTemplate(name, rows) {
  const esc = v => /[",\n]/.test(String(v)) ? '"' + String(v).replace(/"/g, '""') + '"' : String(v);
  const csv = '\uFEFF' + rows.map(r => r.map(esc).join(',')).join('\n');
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], {
    type: 'text/csv;charset=utf-8'
  }));
  a.download = name;
  document.body.appendChild(a);
  a.click();
  a.remove();
}
Object.assign(window, {
  downloadTemplate,
  AppCtx,
  useApp,
  useStore,
  PageHeader,
  Field,
  nativeStyle,
  ConfirmDialog,
  Panel,
  useFakeLoad,
  Shell,
  LangSwitch,
  fmtDate,
  fmtDateTime
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/Shell.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/StructureScreens.jsx
try { (() => {
function TermsScreen({
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    Dialog,
    Input,
    Select,
    Alert
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const [edit, setEdit] = React.useState(null);
  const [conf, setConf] = React.useState(null);
  const [busy, setBusy] = React.useState(false);
  const [newState, setNewState] = React.useState('active');
  const cur = st.currentTerm();
  const doConf = () => {
    setBusy(true);
    setTimeout(() => {
      const tm = conf.term;
      if (conf.kind === 'current') {
        const o = st.currentTerm();
        if (o) o.current = false;
        tm.current = true;
        st.audit(me.id, 'term.current_set', 'term', tm.id, {
          current: o && o.ar
        }, {
          current: tm.ar
        });
      } else {
        const old = tm.status;
        tm.status = newState;
        st.audit(me.id, 'term.state_changed', 'term', tm.id, {
          status: old
        }, {
          status: newState
        });
      }
      st.emit();
      setBusy(false);
      setConf(null);
      notify(t('term_saved'));
    }, 500);
  };
  const next = {
    draft: ['active'],
    active: ['closed', 'draft'],
    closed: ['archived', 'active'],
    archived: []
  };
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_terms'),
    subtitle: t('terms_sub'),
    actions: /*#__PURE__*/React.createElement(Button, {
      icon: "calendar-plus",
      onClick: () => setEdit({})
    }, t('new_term'))
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    columns: [{
      key: 'n',
      label: t('nav_terms'),
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          display: 'flex',
          alignItems: 'center',
          gap: 8
        }
      }, /*#__PURE__*/React.createElement("b", null, lang === 'en' ? r.en : r.ar), r.current && /*#__PURE__*/React.createElement(StatusBadge, {
        status: "current"
      }))
    }, {
      key: 'year',
      label: t('year'),
      numeric: true
    }, {
      key: 'sem',
      label: t('semester'),
      render: r => t('sem_' + r.semester)
    }, {
      key: 'start',
      label: t('start'),
      numeric: true,
      render: r => fmtDate(r.start)
    }, {
      key: 'end',
      label: t('end'),
      numeric: true,
      render: r => fmtDate(r.end)
    }, {
      key: 'sc',
      label: t('sections_n'),
      numeric: true,
      render: r => st.S.sections.filter(s => s.term === r.id).length
    }, {
      key: 'status',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.status
      })
    }, {
      key: 'x',
      label: '',
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          display: 'flex',
          gap: 6
        }
      }, /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "pencil",
        onClick: () => setEdit(r)
      }, t('edit')), !r.current && r.status !== 'archived' && /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "star",
        onClick: () => setConf({
          kind: 'current',
          term: r
        })
      }, t('set_current')), next[r.status].length > 0 && /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "repeat",
        onClick: () => {
          setNewState(next[r.status][0]);
          setConf({
            kind: 'state',
            term: r
          });
        }
      }, t('change_state')))
    }],
    rows: st.S.terms
  })), edit && /*#__PURE__*/React.createElement(TermDialog, {
    term: edit,
    onClose: () => setEdit(null),
    notify: notify
  }), /*#__PURE__*/React.createElement(ConfirmDialog, {
    open: !!conf,
    busy: busy,
    onClose: () => setConf(null),
    onConfirm: doConf,
    tone: conf && conf.kind === 'state' ? 'danger' : 'info',
    title: conf && (conf.kind === 'current' ? t('current_q', {
      n: lang === 'en' ? conf.term.en : conf.term.ar
    }) : t('state_q', {
      s: t('st_' + newState)
    })),
    impact: conf && (conf.kind === 'current' ? t('current_impact', {
      o: cur ? lang === 'en' ? cur.en : cur.ar : '—'
    }) : t('state_impact'))
  }, conf && conf.kind === 'state' && /*#__PURE__*/React.createElement(Select, {
    label: t('status'),
    value: newState,
    onChange: e => setNewState(e.target.value),
    options: next[conf.term.status].map(s => ({
      value: s,
      label: t('st_' + s)
    }))
  })));
}
function TermDialog({
  term,
  onClose,
  notify
}) {
  const {
    Dialog,
    Button,
    Input,
    Select
  } = window.DS;
  const {
    t,
    me
  } = useApp();
  const st = useStore();
  const isNew = !term.id;
  const [f, setF] = React.useState({
    ar: term.ar || '',
    en: term.en || '',
    year: term.year || '2027/2028',
    semester: term.semester || 'first',
    start: term.start || '2027-08-29',
    end: term.end || '2028-01-06'
  });
  const [e, setE] = React.useState({});
  const [busy, setBusy] = React.useState(false);
  const set = (k, v) => setF({
    ...f,
    [k]: v
  });
  const save = () => {
    const n = {
      ar: f.ar ? null : t('required'),
      en: f.en ? null : t('required'),
      end: f.end <= f.start ? t('end_after') : null
    };
    setE(n);
    if (Object.values(n).some(Boolean)) return;
    setBusy(true);
    setTimeout(() => {
      if (isNew) {
        const x = {
          id: Date.now(),
          ...f,
          status: 'draft',
          current: false
        };
        st.S.terms.push(x);
        st.audit(me.id, 'term.created', 'term', x.id, null, f);
      } else {
        const old = {
          ar: term.ar,
          start: term.start,
          end: term.end
        };
        Object.assign(term, f);
        st.audit(me.id, 'term.updated', 'term', term.id, old, {
          ar: f.ar,
          start: f.start,
          end: f.end
        });
      }
      st.emit();
      setBusy(false);
      onClose();
      notify(t('term_saved'));
    }, 500);
  };
  return /*#__PURE__*/React.createElement(Dialog, {
    title: isNew ? t('new_term') : t('edit'),
    onClose: onClose,
    width: 560,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: save
    }, t('save')))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('name_ar'),
    required: true,
    value: f.ar,
    onChange: x => set('ar', x.target.value),
    error: e.ar
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('name_en'),
    required: true,
    dir: "ltr",
    value: f.en,
    onChange: x => set('en', x.target.value),
    error: e.en
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('year'),
    dir: "ltr",
    value: f.year,
    onChange: x => set('year', x.target.value)
  }), /*#__PURE__*/React.createElement(Select, {
    label: t('semester'),
    value: f.semester,
    onChange: x => set('semester', x.target.value),
    options: ['first', 'second', 'summer'].map(s => ({
      value: s,
      label: t('sem_' + s)
    }))
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('start'),
    type: "date",
    value: f.start,
    onChange: x => set('start', x.target.value)
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('end'),
    type: "date",
    value: f.end,
    onChange: x => set('end', x.target.value),
    error: e.end
  })));
}
function BranchesScreen({
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    Dialog,
    Input,
    Select,
    Switch,
    Alert
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const [edit, setEdit] = React.useState(null);
  const [f, setF] = React.useState({});
  const [busy, setBusy] = React.useState(false);
  const open = b => {
    setEdit(b);
    setF({
      ar: b.ar || '',
      en: b.en || '',
      gender: b.gender || 'male',
      active: b.active !== false
    });
  };
  const save = () => {
    if (!f.ar || !f.en) return;
    setBusy(true);
    setTimeout(() => {
      if (!edit.id) {
        const b = {
          id: Date.now(),
          ...f
        };
        st.S.branches.push(b);
        st.audit(me.id, 'branch.created', 'branch', b.id, null, f);
      } else {
        const old = {
          ar: edit.ar,
          en: edit.en,
          active: edit.active
        };
        Object.assign(edit, f);
        st.audit(me.id, 'branch.updated', 'branch', edit.id, old, f);
      }
      st.emit();
      setBusy(false);
      setEdit(null);
      notify(t('branch_saved'));
    }, 500);
  };
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_branches'),
    subtitle: t('branches_sub'),
    actions: /*#__PURE__*/React.createElement(Button, {
      icon: "plus",
      onClick: () => open({})
    }, t('new_branch'))
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    columns: [{
      key: 'n',
      label: t('branch'),
      render: r => /*#__PURE__*/React.createElement("b", null, st.branchLabel(r.id, lang))
    }, {
      key: 'ar',
      label: t('campus_ar')
    }, {
      key: 'en',
      label: t('campus_en')
    }, {
      key: 'g',
      label: t('gender'),
      render: r => t(r.gender)
    }, {
      key: 'c',
      label: t('coordinators'),
      wrap: true,
      render: r => st.S.users.filter(u => u.branches.includes(r.id)).map(u => st.uname(u.id, lang)).join('، ') || '—'
    }, {
      key: 's',
      label: t('sections_n'),
      numeric: true,
      render: r => st.S.sections.filter(s => s.branch === r.id).length
    }, {
      key: 'a',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.active ? 'active' : 'inactive'
      })
    }, {
      key: 'x',
      label: '',
      render: r => /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "pencil",
        onClick: () => open(r)
      }, t('edit'))
    }],
    rows: st.S.branches
  })), /*#__PURE__*/React.createElement(Dialog, {
    open: !!edit,
    title: edit && edit.id ? t('edit') : t('new_branch'),
    onClose: () => setEdit(null),
    width: 520,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: () => setEdit(null)
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: save
    }, t('save')))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('campus_ar'),
    required: true,
    value: f.ar,
    onChange: x => setF({
      ...f,
      ar: x.target.value
    }),
    error: f.ar === '' ? t('required') : null
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('campus_en'),
    required: true,
    dir: "ltr",
    value: f.en,
    onChange: x => setF({
      ...f,
      en: x.target.value
    }),
    error: f.en === '' ? t('required') : null
  }), /*#__PURE__*/React.createElement(Select, {
    label: t('gender'),
    value: f.gender,
    onChange: x => setF({
      ...f,
      gender: x.target.value
    }),
    options: [{
      value: 'male',
      label: t('male')
    }, {
      value: 'female',
      label: t('female')
    }]
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'flex-end',
      paddingBottom: 8
    }
  }, /*#__PURE__*/React.createElement(Switch, {
    label: t('active_flag'),
    checked: f.active,
    onChange: v => setF({
      ...f,
      active: v
    })
  })), edit && edit.id && /*#__PURE__*/React.createElement(Alert, {
    tone: "info",
    style: {
      gridColumn: '1 / -1'
    }
  }, t('no_delete')))));
}
function CoursesScreen({
  notify
}) {
  const {
    Button,
    DataTable,
    Dialog,
    Input
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const [edit, setEdit] = React.useState(null);
  const [f, setF] = React.useState({});
  const [e, setE] = React.useState({});
  const [busy, setBusy] = React.useState(false);
  const open = c => {
    setEdit(c);
    setF({
      code: c.code || '',
      ar: c.ar || '',
      en: c.en || ''
    });
    setE({});
  };
  const save = () => {
    const n = {
      code: !f.code ? t('required') : st.S.courses.some(c => c.code.toUpperCase() === f.code.toUpperCase() && c.id !== edit.id) ? t('code_taken') : null,
      ar: f.ar ? null : t('required'),
      en: f.en ? null : t('required')
    };
    setE(n);
    if (Object.values(n).some(Boolean)) return;
    setBusy(true);
    setTimeout(() => {
      if (!edit.id) {
        const c = {
          id: Date.now(),
          ...f,
          code: f.code.toUpperCase()
        };
        st.S.courses.push(c);
        st.audit(me.id, 'course.created', 'course', c.id, null, f);
      } else {
        const old = {
          ...edit
        };
        Object.assign(edit, f);
        st.audit(me.id, 'course.updated', 'course', edit.id, {
          code: old.code,
          ar: old.ar
        }, f);
      }
      st.emit();
      setBusy(false);
      setEdit(null);
      notify(t('course_saved'));
    }, 500);
  };
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_courses'),
    subtitle: t('courses_sub'),
    actions: /*#__PURE__*/React.createElement(Button, {
      icon: "plus",
      onClick: () => open({})
    }, t('new_course'))
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    columns: [{
      key: 'code',
      label: t('code'),
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r.code)
    }, {
      key: 'ar',
      label: t('name_ar')
    }, {
      key: 'en',
      label: t('name_en')
    }, {
      key: 'p',
      label: lang === 'en' ? 'Programs' : 'البرامج',
      wrap: true,
      render: r => (r.programs || []).map(x => st.programName(x, lang)).join('، ') || '—'
    }, {
      key: 's',
      label: t('sections_n'),
      numeric: true,
      render: r => st.S.sections.filter(s => s.course === r.id).length
    }, {
      key: 'x',
      label: '',
      render: r => /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "pencil",
        onClick: () => open(r)
      }, t('edit'))
    }],
    rows: st.S.courses
  })), /*#__PURE__*/React.createElement(Dialog, {
    open: !!edit,
    title: edit && edit.id ? t('edit') : t('new_course'),
    onClose: () => setEdit(null),
    width: 520,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: () => setEdit(null)
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: save
    }, t('save')))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('code'),
    required: true,
    dir: "ltr",
    value: f.code,
    onChange: x => setF({
      ...f,
      code: x.target.value
    }),
    error: e.code
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('name_ar'),
    required: true,
    value: f.ar,
    onChange: x => setF({
      ...f,
      ar: x.target.value
    }),
    error: e.ar
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('name_en'),
    required: true,
    dir: "ltr",
    value: f.en,
    onChange: x => setF({
      ...f,
      en: x.target.value
    }),
    error: e.en
  }))));
}
function ProgramsScreen({
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    Dialog,
    Input,
    Select,
    Switch,
    Alert
  } = window.DS;
  const {
    t,
    me,
    lang,
    go
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const [edit, setEdit] = React.useState(null);
  const [f, setF] = React.useState({});
  const [e, setE] = React.useState({});
  const [busy, setBusy] = React.useState(false);
  const [imp, setImp] = React.useState(false);
  const open = p => {
    setEdit(p);
    setF({
      code: p.code || '',
      ar: p.ar || '',
      en: p.en || '',
      unit: p.unit || 'admin',
      active: p.active !== false
    });
    setE({});
  };
  const unitL = u => u === 'tech' ? en ? 'Technical' : 'البرامج التقنية' : en ? 'Administrative' : 'البرامج الإدارية';
  const save = () => {
    const n = {
      code: !f.code ? t('required') : st.S.programs.some(p => p.code.toUpperCase() === f.code.toUpperCase() && p.id !== edit.id) ? t('code_taken') : null,
      ar: f.ar ? null : t('required'),
      en: f.en ? null : t('required')
    };
    setE(n);
    if (Object.values(n).some(Boolean)) return;
    setBusy(true);
    setTimeout(() => {
      if (!edit.id) {
        const p = {
          id: Date.now(),
          ...f,
          code: f.code.toUpperCase()
        };
        st.S.programs.push(p);
        st.audit(me.id, 'program.created', 'program', p.id, null, {
          code: p.code,
          name_ar: p.ar
        });
      } else {
        const old = {
          code: edit.code,
          ar: edit.ar,
          active: edit.active
        };
        Object.assign(edit, f, {
          code: f.code.toUpperCase()
        });
        st.audit(me.id, 'program.updated', 'program', edit.id, old, {
          code: edit.code,
          ar: edit.ar,
          active: edit.active
        });
      }
      st.emit();
      setBusy(false);
      setEdit(null);
      notify(en ? 'Program saved' : 'تم حفظ البرنامج');
    }, 500);
  };
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_programs'),
    subtitle: en ? 'Every section in the college belongs to one academic program.' : 'كل شعبة في الكلية مرتبطة ببرنامج أكاديمي واحد.',
    actions: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      icon: "file-spreadsheet",
      onClick: () => setImp(true)
    }, en ? 'Import courses from Excel' : 'استيراد المقررات من Excel'), /*#__PURE__*/React.createElement(Button, {
      icon: "plus",
      onClick: () => open({})
    }, en ? 'New program' : 'برنامج جديد'))
  }), /*#__PURE__*/React.createElement(Panel, {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    columns: [{
      key: 'code',
      label: t('code'),
      numeric: true,
      render: r => /*#__PURE__*/React.createElement("b", null, r.code)
    }, {
      key: 'ar',
      label: t('name_ar')
    }, {
      key: 'en',
      label: t('name_en')
    }, {
      key: 'u',
      label: en ? 'Specialty unit' : 'الوحدة التخصصية',
      render: r => unitL(r.unit)
    }, {
      key: 'cc',
      label: en ? 'Courses' : 'المقررات',
      numeric: true,
      render: r => st.S.courses.filter(c => (c.programs || []).includes(r.id)).length
    }, {
      key: 's',
      label: t('sections_n'),
      numeric: true,
      render: r => st.S.sections.filter(s => s.program === r.id).length
    }, {
      key: 'a',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.active ? 'active' : 'inactive'
      })
    }, {
      key: 'x',
      label: '',
      render: r => /*#__PURE__*/React.createElement(Button, {
        size: "sm",
        variant: "ghost",
        icon: "pencil",
        onClick: () => open(r)
      }, t('edit'))
    }],
    rows: st.S.programs
  })), /*#__PURE__*/React.createElement(Dialog, {
    open: !!edit,
    title: edit && edit.id ? t('edit') : en ? 'New program' : 'برنامج جديد',
    onClose: () => setEdit(null),
    width: 560,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: () => setEdit(null)
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: save
    }, t('save')))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('code'),
    required: true,
    dir: "ltr",
    value: f.code,
    onChange: x => setF({
      ...f,
      code: x.target.value
    }),
    error: e.code
  }), /*#__PURE__*/React.createElement(Select, {
    label: en ? 'Specialty unit' : 'الوحدة التخصصية',
    value: f.unit,
    onChange: x => setF({
      ...f,
      unit: x.target.value
    }),
    options: [{
      value: 'admin',
      label: unitL('admin')
    }, {
      value: 'tech',
      label: unitL('tech')
    }]
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('name_ar'),
    required: true,
    value: f.ar,
    onChange: x => setF({
      ...f,
      ar: x.target.value
    }),
    error: e.ar
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('name_en'),
    required: true,
    dir: "ltr",
    value: f.en,
    onChange: x => setF({
      ...f,
      en: x.target.value
    }),
    error: e.en
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      gridColumn: '1 / -1'
    }
  }, /*#__PURE__*/React.createElement(Switch, {
    label: t('active_flag'),
    checked: f.active,
    onChange: v => setF({
      ...f,
      active: v
    })
  })), edit && edit.id && /*#__PURE__*/React.createElement(Alert, {
    tone: "info",
    style: {
      gridColumn: '1 / -1'
    }
  }, en ? 'Programs linked to sections cannot be deleted; deactivate instead.' : 'لا يمكن حذف برنامج مرتبط بشعب؛ يمكن إيقافه بدلاً من ذلك.'))), imp && /*#__PURE__*/React.createElement(CourseImportDialog, {
    onClose: () => setImp(false),
    notify: notify
  }));
}
Object.assign(window, {
  TermsScreen,
  BranchesScreen,
  CoursesScreen,
  ProgramsScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/StructureScreens.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/UsersScreen.jsx
try { (() => {
function UsersScreen({
  notify
}) {
  const {
    Button,
    DataTable,
    StatusBadge,
    Input,
    Tabs,
    Dialog,
    Checkbox,
    IconButton,
    Tooltip
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const [tab, setTab] = React.useState('all');
  const [q, setQ] = React.useState('');
  const [edit, setEdit] = React.useState(null);
  const [conf, setConf] = React.useState(null);
  const [busy, setBusy] = React.useState(false);
  const [imp, setImp] = React.useState(false);
  const loading = useFakeLoad(tab);
  const rows = st.S.users.filter(u => (tab === 'all' || u.role === tab) && (u.ar + u.en + u.email).toLowerCase().includes(q.toLowerCase()));
  const doConf = () => {
    setBusy(true);
    setTimeout(() => {
      const u = conf.u;
      if (conf.kind === 'deact') {
        const old = u.active;
        u.active = !u.active;
        st.audit(me.id, u.active ? 'user.reactivated' : 'user.deactivated', 'user', u.id, {
          is_active: old
        }, {
          is_active: u.active
        });
        notify(t('user_saved'));
      } else {
        u.pw = 'Reset' + Math.floor(1000 + Math.random() * 9000);
        st.audit(me.id, 'user.password_reset', 'user', u.id, null, {
          sessions_revoked: true
        });
        notify(t('reset_done'), u.pw);
      }
      st.emit();
      setBusy(false);
      setConf(null);
    }, 500);
  };
  return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement(PageHeader, {
    title: t('nav_users'),
    subtitle: t('users_sub'),
    actions: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      icon: "file-spreadsheet",
      onClick: () => setImp(true)
    }, lang === 'en' ? 'Import from Excel' : 'استيراد من Excel'), /*#__PURE__*/React.createElement(Button, {
      icon: "user-plus",
      onClick: () => setEdit({})
    }, t('new_user')))
  }), /*#__PURE__*/React.createElement(Panel, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 16,
      padding: '8px 20px 0',
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Tabs, {
    value: tab,
    onChange: setTab,
    tabs: [{
      id: 'all',
      label: t('all'),
      count: st.S.users.length
    }, ...['SUPER_ADMIN', 'BRANCH_COORDINATOR', 'INSTRUCTOR'].map(r => ({
      id: r,
      label: t('role_' + r),
      count: st.S.users.filter(u => u.role === r).length
    }))]
  }), /*#__PURE__*/React.createElement(Input, {
    size: "sm",
    icon: "search",
    placeholder: t('search'),
    value: q,
    onChange: e => setQ(e.target.value),
    style: {
      width: 240
    }
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 20
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    loading: loading,
    columns: [{
      key: 'n',
      label: t('name_ar'),
      render: r => /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
        style: {
          fontWeight: 700
        }
      }, lang === 'en' ? r.en : r.ar), /*#__PURE__*/React.createElement("div", {
        style: {
          font: '400 12px var(--font-sans)',
          color: 'var(--text-secondary)'
        }
      }, lang === 'en' ? r.ar : r.en))
    }, {
      key: 'email',
      label: t('email'),
      numeric: true
    }, {
      key: 'role',
      label: t('role'),
      render: r => t('role_' + r.role)
    }, {
      key: 'b',
      label: t('branches'),
      wrap: true,
      render: r => r.role === 'BRANCH_COORDINATOR' ? r.branches.map(b => st.branchLabel(b, lang)).join('، ') : '—'
    }, {
      key: 'll',
      label: t('last_login'),
      numeric: true,
      render: r => r.lastLogin ? fmtDateTime(r.lastLogin) : /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-muted)'
        }
      }, t('never'))
    }, {
      key: 's',
      label: t('status'),
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.active ? 'active' : 'inactive'
      })
    }, {
      key: 'x',
      label: '',
      render: r => /*#__PURE__*/React.createElement("div", {
        style: {
          display: 'flex',
          gap: 4
        }
      }, /*#__PURE__*/React.createElement(Tooltip, {
        content: t('edit')
      }, /*#__PURE__*/React.createElement(IconButton, {
        icon: "pencil",
        label: t('edit'),
        size: 32,
        onClick: () => setEdit(r)
      })), /*#__PURE__*/React.createElement(Tooltip, {
        content: t('reset_pw')
      }, /*#__PURE__*/React.createElement(IconButton, {
        icon: "key-round",
        label: t('reset_pw'),
        size: 32,
        onClick: () => setConf({
          kind: 'reset',
          u: r
        })
      })), r.id !== me.id && /*#__PURE__*/React.createElement(Tooltip, {
        content: r.active ? t('deactivate') : t('reactivate')
      }, /*#__PURE__*/React.createElement(IconButton, {
        icon: r.active ? 'user-x' : 'user-check',
        label: r.active ? t('deactivate') : t('reactivate'),
        size: 32,
        onClick: () => r.active ? setConf({
          kind: 'deact',
          u: r
        }) : setConf({
          kind: 'deact',
          u: r
        })
      })))
    }],
    rows: rows
  }))), edit && /*#__PURE__*/React.createElement(UserDialog, {
    user: edit,
    onClose: () => setEdit(null),
    notify: notify
  }), imp && /*#__PURE__*/React.createElement(UserImportDialog, {
    onClose: () => setImp(false),
    notify: notify
  }), /*#__PURE__*/React.createElement(ConfirmDialog, {
    open: !!conf,
    busy: busy,
    onClose: () => setConf(null),
    onConfirm: doConf,
    title: conf && (conf.kind === 'deact' ? conf.u.active ? t('deact_q', {
      n: lang === 'en' ? conf.u.en : conf.u.ar
    }) : t('reactivate') : t('reset_q', {
      n: lang === 'en' ? conf.u.en : conf.u.ar
    })),
    impact: conf && (conf.kind === 'deact' ? t('deact_impact') : t('reset_impact')),
    confirmLabel: conf && (conf.kind === 'deact' ? conf.u.active ? t('deactivate') : t('reactivate') : t('reset_pw')),
    tone: conf && conf.kind === 'deact' && conf.u.active ? 'danger' : 'info'
  }));
}
function UserDialog({
  user,
  onClose,
  notify
}) {
  const {
    Dialog,
    Button,
    Input,
    Select,
    Checkbox
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const isNew = !user.id;
  const [f, setF] = React.useState({
    ar: user.ar || '',
    en: user.en || '',
    email: user.email || '',
    pw: '',
    role: user.role || 'INSTRUCTOR',
    branches: user.branches || []
  });
  const [e, setE] = React.useState({});
  const [busy, setBusy] = React.useState(false);
  const check = (k, all) => {
    const n = {
      ...e
    };
    const v = f;
    if (all || k === 'ar') n.ar = v.ar.trim() ? null : t('required');
    if (all || k === 'en') n.en = v.en.trim() ? null : t('required');
    if (all || k === 'email') n.email = !v.email ? t('required') : !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v.email) ? t('email_bad') : st.S.users.some(u => u.email.toLowerCase() === v.email.toLowerCase() && u.id !== user.id) ? t('email_taken') : null;
    if (isNew && (all || k === 'pw')) n.pw = !v.pw ? t('required') : !(v.pw.length >= 8 && /[A-Za-z]/.test(v.pw) && /\d/.test(v.pw)) ? t('pw_policy') : null;
    if (all || k === 'branches') n.branches = v.role === 'BRANCH_COORDINATOR' && v.branches.length === 0 ? t('coord_need') : null;
    setE(n);
    return n;
  };
  const save = () => {
    const n = check(null, true);
    if (Object.values(n).some(Boolean)) return;
    setBusy(true);
    setTimeout(() => {
      if (isNew) {
        const u = {
          id: Date.now(),
          ar: f.ar,
          en: f.en,
          email: f.email,
          role: f.role,
          branches: f.role === 'BRANCH_COORDINATOR' ? f.branches : [],
          active: true,
          firstLogin: null,
          lastLogin: null,
          pw: f.pw
        };
        st.S.users.push(u);
        st.audit(me.id, 'user.created', 'user', u.id, null, {
          name_ar: u.ar,
          email: u.email,
          role: u.role
        });
        notify(t('user_created'), u.email);
      } else {
        const old = {
          role: user.role,
          branches: user.branches.map(b => st.branchLabel(b, 'ar'))
        };
        Object.assign(user, {
          ar: f.ar,
          en: f.en,
          email: f.email,
          role: f.role,
          branches: f.role === 'BRANCH_COORDINATOR' ? f.branches : []
        });
        st.audit(me.id, old.role !== f.role || JSON.stringify(old.branches) !== JSON.stringify(user.branches.map(b => st.branchLabel(b, 'ar'))) ? 'user.role_changed' : 'user.updated', 'user', user.id, old, {
          role: user.role,
          branches: user.branches.map(b => st.branchLabel(b, 'ar'))
        });
        notify(t('user_saved'));
      }
      st.emit();
      setBusy(false);
      onClose();
    }, 600);
  };
  const set = (k, v) => setF({
    ...f,
    [k]: v
  });
  return /*#__PURE__*/React.createElement(Dialog, {
    title: isNew ? t('new_user') : t('edit_user'),
    onClose: onClose,
    width: 620,
    footer: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      loading: busy,
      onClick: save
    }, isNew ? t('create') : t('save')))
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Input, {
    label: t('name_ar'),
    required: true,
    value: f.ar,
    onChange: x => set('ar', x.target.value),
    onBlur: () => check('ar'),
    error: e.ar
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('name_en'),
    required: true,
    dir: "ltr",
    value: f.en,
    onChange: x => set('en', x.target.value),
    onBlur: () => check('en'),
    error: e.en
  }), /*#__PURE__*/React.createElement(Input, {
    label: t('email'),
    required: true,
    icon: "mail",
    dir: "ltr",
    value: f.email,
    onChange: x => set('email', x.target.value),
    onBlur: () => check('email'),
    error: e.email,
    style: {
      gridColumn: isNew ? 'auto' : '1 / -1'
    }
  }), isNew && /*#__PURE__*/React.createElement(Input, {
    label: t('initial_pw'),
    required: true,
    icon: "lock",
    dir: "ltr",
    value: f.pw,
    onChange: x => set('pw', x.target.value),
    onBlur: () => check('pw'),
    error: e.pw,
    hint: t('pw_rule')
  }), /*#__PURE__*/React.createElement(Select, {
    label: t('role'),
    value: f.role,
    onChange: x => set('role', x.target.value),
    options: ['SUPER_ADMIN', 'BRANCH_COORDINATOR', 'INSTRUCTOR'].map(r => ({
      value: r,
      label: t('role_' + r)
    })),
    style: {
      gridColumn: '1 / -1'
    }
  }), f.role === 'BRANCH_COORDINATOR' && /*#__PURE__*/React.createElement("div", {
    style: {
      gridColumn: '1 / -1'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      font: '600 13px var(--font-sans)',
      marginBottom: 8
    }
  }, t('coord_branches'), /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--red-600)'
    }
  }, " *")), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(3,1fr)',
      gap: 10
    }
  }, st.S.branches.filter(b => b.active).map(b => /*#__PURE__*/React.createElement(Checkbox, {
    key: b.id,
    label: st.branchLabel(b.id, lang),
    checked: f.branches.includes(b.id),
    onChange: v => {
      const nb = v ? [...f.branches, b.id] : f.branches.filter(x => x !== b.id);
      setF({
        ...f,
        branches: nb
      });
      setE({
        ...e,
        branches: null
      });
    }
  }))), e.branches && /*#__PURE__*/React.createElement("div", {
    role: "alert",
    style: {
      font: '400 12px var(--font-sans)',
      color: 'var(--red-600)',
      marginTop: 6
    }
  }, e.branches)), f.role === 'INSTRUCTOR' && /*#__PURE__*/React.createElement("div", {
    style: {
      gridColumn: '1 / -1',
      font: '400 12px var(--font-sans)',
      color: 'var(--text-secondary)'
    }
  }, t('sections_link'), ": ", t('optional_later'))));
}
function UserImportDialog({
  onClose,
  notify
}) {
  const {
    Dialog,
    Button,
    Alert,
    DataTable,
    StatusBadge,
    Icon,
    Checkbox
  } = window.DS;
  const {
    t,
    me,
    lang
  } = useApp();
  const st = useStore();
  const en = lang === 'en';
  const ref = React.useRef(null);
  const [res, setRes] = React.useState(null);
  const [err, setErr] = React.useState(null);
  const [name, setName] = React.useState('');
  const [busy, setBusy] = React.useState(false);
  const [done, setDone] = React.useState(null);
  const [skipWarn, setSkipWarn] = React.useState(false);
  const HDR = ['الاسم بالعربية', 'الاسم بالإنجليزية', 'البريد الإلكتروني', 'الدور', 'الفروع', 'كلمة المرور'];
  const genPw = () => 'Track' + Math.floor(1000 + Math.random() * 9000) + String.fromCharCode(97 + Math.floor(Math.random() * 26));
  const roleOf = v => {
    v = String(v || '').trim();
    if (!v) return null;
    if (/سوبر|super|admin|مدير/i.test(v)) return 'SUPER_ADMIN';
    if (/منسق|coord/i.test(v)) return 'BRANCH_COORDINATOR';
    if (/مدرس|تدريس|محاضر|instructor|teacher|faculty/i.test(v)) return 'INSTRUCTOR';
    return 'bad';
  };
  const branchesOf = v => {
    const out = [];
    let bad = [];
    String(v || '').split(/[،,;\n]+/).map(x => x.trim()).filter(Boolean).forEach(p => {
      const g = /طالبات|بنات|female/i.test(p) ? 'female' : /طلاب|بنين|male/i.test(p) ? 'male' : null;
      const m = st.S.branches.filter(b => b.active && (p.includes(b.ar) || p.toLowerCase().includes(b.en.toLowerCase())) && (!g || b.gender === g));
      if (m.length) m.forEach(b => {
        if (!out.includes(b.id)) out.push(b.id);
      });else bad.push(p);
    });
    return {
      ids: out,
      bad
    };
  };
  const validate = p => {
    const H = p.headers.map(h => String(h || '').trim());
    const col = re => H.findIndex(h => re.test(h));
    const c = {
      ar: col(/الاسم بالعربي|الاسم العربي|name.?ar|^الاسم$/i),
      en: col(/بالإنجليزي|الانجليزي|name.?en|english/i),
      email: col(/البريد|email|e-mail/i),
      role: col(/الدور|الصلاحية|role/i),
      br: col(/الفرع|الفروع|branch/i),
      pw: col(/كلمة المرور|password/i)
    };
    if (c.email < 0 || c.ar < 0) throw new Error('cols');
    const seen = {};
    return p.rows.map((r, k) => {
      const n = k + 2;
      const g = i => i >= 0 ? String(r[i] || '').trim() : '';
      const u = {
        id: n,
        n,
        ar: g(c.ar),
        en: g(c.en),
        email: g(c.email).toLowerCase(),
        roleRaw: g(c.role),
        brRaw: g(c.br),
        pw: g(c.pw)
      };
      const errs = [],
        warns = [];
      if (!u.ar) errs.push(en ? 'Arabic name missing' : 'الاسم بالعربية مفقود');
      if (!u.email) errs.push(en ? 'Email missing' : 'البريد مفقود');else if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(u.email)) errs.push(en ? 'Invalid email' : 'بريد غير صالح');else if (st.S.users.some(x => x.email.toLowerCase() === u.email)) errs.push(en ? 'Email already registered' : 'البريد مسجل مسبقًا');else if (seen[u.email]) errs.push((en ? 'Duplicate of row ' : 'مكرر مع الصف ') + seen[u.email]);else seen[u.email] = n;
      if (!u.en) {
        u.en = u.ar;
        warns.push(en ? 'English name copied from Arabic' : 'نُسخ الاسم العربي للإنجليزي');
      }
      let role = roleOf(u.roleRaw);
      if (role === 'bad') {
        errs.push(en ? 'Unknown role' : 'دور غير معروف');
        role = null;
      } else if (!role) {
        role = 'INSTRUCTOR';
        warns.push(en ? 'Role empty — set to instructor' : 'الدور فارغ — عُيّن مدرسًا');
      }
      u.role = role;
      const b = branchesOf(u.brRaw);
      u.branches = role === 'BRANCH_COORDINATOR' ? b.ids : [];
      if (role === 'BRANCH_COORDINATOR' && !b.ids.length) errs.push(en ? 'Coordinator needs a branch' : 'منسق الفرع يحتاج فرعًا');
      if (b.bad.length) (role === 'BRANCH_COORDINATOR' ? errs : warns).push((en ? 'Unknown branch: ' : 'فرع غير معروف: ') + b.bad.join('، '));
      if (u.pw && !(u.pw.length >= 8 && /[A-Za-z\u0600-\u06FF]/.test(u.pw) && /\d/.test(u.pw))) errs.push(t('pw_policy'));
      u.genPw = !u.pw;
      u.status = errs.length ? 'error' : warns.length ? 'warning' : 'valid';
      u.reason = [...errs, ...warns].join(' · ') || (en ? 'Ready' : 'جاهز للإضافة');
      return u;
    });
  };
  const onFile = async ev => {
    const f = ev.target.files && ev.target.files[0];
    ev.target.value = '';
    if (!f) return;
    try {
      const p = await window.parseRosterFile(f);
      setName(p.name);
      setErr(null);
      setRes(validate(p));
    } catch (x) {
      setRes(null);
      setErr(x.message === 'cols' ? en ? 'The file needs at least “Arabic name” and “Email” columns. Download the template.' : 'يجب أن يحتوي الملف على عمودي «الاسم بالعربية» و«البريد الإلكتروني» على الأقل. حمّل القالب.' : x.message === 'size' ? en ? 'File exceeds 20 MB.' : 'الملف يتجاوز 20 ميجابايت.' : en ? 'Unsupported file. Use xlsx or csv.' : 'نوع الملف غير مدعوم. استخدم xlsx أو csv.');
    }
  };
  const sample = () => {
    setName('users_batch.xlsx');
    setErr(null);
    setRes(validate({
      headers: HDR,
      rows: [['د. سارة القحطاني', 'Dr. Sarah Al-Qahtani', 's.alqahtani@mu.edu.sa', 'مدرس', '', ''], ['أ. عبدالله الحربي', 'Abdullah Al-Harbi', 'a.alharbi@mu.edu.sa', 'عضو هيئة تدريس', '', 'Welcome2026'], ['د. منيرة الشهري', 'Dr. Munira Al-Shehri', 'm.alshehri@mu.edu.sa', 'منسق فرع', 'الزلفي طالبات', ''], ['د. تركي العنزي', '', 't.alanazi@mu.edu.sa', 'منسق فرع', 'المجمعة', ''], ['أ. لمى الغامدي', 'Lama Al-Ghamdi', 'f.alotaibi@mu.edu.sa', 'مدرس', '', ''], ['أ. نايف السبيعي', 'Nayef Al-Subaie', 'n.alsubaie@mu', 'مدرس', '', ''], ['د. هيا الزهراني', 'Dr. Haya Al-Zahrani', 'h.alzahrani@mu.edu.sa', '', '', ''], ['أ. بندر المطيري', 'Bandar Al-Mutairi', 'b.almutairi@mu.edu.sa', 'منسق فرع', 'الدمام', '']]
    }));
  };
  const template = () => {
    const csv = '\uFEFF' + [HDR, ['د. مثال الاسم', 'Dr. Example Name', 'example@mu.edu.sa', 'مدرس', '', ''], ['د. منسق مثال', 'Dr. Coordinator', 'coord@mu.edu.sa', 'منسق فرع', 'المجمعة طلاب، الزلفي طالبات', '']].map(r => r.join(',')).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], {
      type: 'text/csv'
    }));
    a.download = 'track_users_template.csv';
    a.click();
  };
  const ok = res ? res.filter(r => r.status === 'valid' || r.status === 'warning' && !skipWarn) : [];
  const run = () => {
    setBusy(true);
    setTimeout(() => {
      const made = ok.map((r, i) => {
        const pw = r.pw || genPw();
        const u = {
          id: Date.now() + i,
          ar: r.ar,
          en: r.en,
          email: r.email,
          role: r.role,
          branches: r.branches,
          active: true,
          firstLogin: null,
          lastLogin: null,
          pw
        };
        st.S.users.push(u);
        st.audit(me.id, 'user.created', 'user', u.id, null, {
          name_ar: u.ar,
          email: u.email,
          role: u.role,
          source: name
        });
        return {
          ...u,
          gen: r.genPw
        };
      });
      st.audit(me.id, 'user.bulk_imported', 'user', null, null, {
        file: name,
        rows: res.length,
        created: made.length,
        skipped: res.length - made.length
      });
      st.emit();
      setBusy(false);
      setDone(made);
      notify(en ? 'Users imported' : 'تم استيراد المستخدمين', made.length + (en ? ' users added' : ' مستخدم أُضيف'));
    }, 700);
  };
  const creds = () => {
    const csv = '\uFEFF' + [['الاسم', 'البريد الإلكتروني', 'الدور', 'كلمة المرور المؤقتة'], ...done.map(u => [u.ar, u.email, t('role_' + u.role), u.gen ? u.pw : '(من الملف)'])].map(r => r.join(',')).join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], {
      type: 'text/csv'
    }));
    a.download = 'track_users_credentials.csv';
    a.click();
  };
  const cnt = k => res ? res.filter(r => r.status === k).length : 0;
  return /*#__PURE__*/React.createElement(Dialog, {
    title: en ? 'Import users from Excel' : 'استيراد المستخدمين من Excel',
    onClose: onClose,
    width: 980,
    footer: done ? /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "secondary",
      icon: "download",
      onClick: creds
    }, en ? 'Download credentials' : 'تنزيل بيانات الدخول'), /*#__PURE__*/React.createElement(Button, {
      onClick: onClose
    }, en ? 'Done' : 'تم')) : /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement(Button, {
      variant: "ghost",
      onClick: onClose
    }, t('cancel')), /*#__PURE__*/React.createElement(Button, {
      icon: "users",
      loading: busy,
      disabled: !ok.length,
      onClick: run
    }, (en ? 'Add ' : 'إضافة ') + ok.length + (en ? ' users' : ' مستخدم')))
  }, done ? /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 14
    }
  }, /*#__PURE__*/React.createElement(Alert, {
    tone: "success",
    title: (en ? 'Added ' : 'تمت إضافة ') + done.length + (en ? ' users' : ' مستخدم')
  }, en ? 'Generated temporary passwords are in the credentials file. Users must change them at first sign-in.' : 'كلمات المرور المؤقتة المولّدة موجودة في ملف بيانات الدخول، ويُطلب من كل مستخدم تغييرها عند أول تسجيل دخول.'), /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: 'ar',
      label: t('name_ar'),
      render: r => /*#__PURE__*/React.createElement("b", null, r.ar)
    }, {
      key: 'email',
      label: t('email'),
      numeric: true
    }, {
      key: 'role',
      label: t('role'),
      render: r => t('role_' + r.role)
    }, {
      key: 'b',
      label: t('branches'),
      wrap: true,
      render: r => r.branches.map(b => st.branchLabel(b, lang)).join('، ') || '—'
    }, {
      key: 'pw',
      label: t('initial_pw'),
      numeric: true,
      render: r => r.gen ? /*#__PURE__*/React.createElement("code", null, r.pw) : /*#__PURE__*/React.createElement("span", {
        style: {
          color: 'var(--text-secondary)'
        }
      }, en ? 'from file' : 'من الملف')
    }],
    rows: done
  })) : /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 12
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      border: '2px dashed var(--border-strong)',
      borderRadius: 'var(--radius-lg)',
      padding: '22px 16px',
      textAlign: 'center',
      background: 'var(--gray-50)'
    }
  }, /*#__PURE__*/React.createElement(Icon, {
    name: "file-spreadsheet",
    size: 30,
    color: "var(--track-green)",
    style: {
      margin: '0 auto 8px'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '700 14px var(--font-sans)'
    }
  }, en ? 'Upload a users file (xlsx or csv)' : 'ارفع ملف المستخدمين (xlsx أو csv)'), /*#__PURE__*/React.createElement("div", {
    style: {
      font: '400 12px/1.6 var(--font-sans)',
      color: 'var(--text-secondary)',
      marginTop: 4
    }
  }, en ? 'Columns: Arabic name and email (required), English name, role, branches, password. Empty passwords are generated automatically.' : 'الأعمدة: الاسم بالعربية والبريد الإلكتروني (إلزامية)، الاسم بالإنجليزية، الدور، الفروع، كلمة المرور. تُولَّد كلمة مرور مؤقتة تلقائيًا إذا تُركت فارغة.'), /*#__PURE__*/React.createElement("input", {
    ref: ref,
    type: "file",
    accept: ".xlsx,.csv",
    onChange: onFile,
    style: {
      display: 'none'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: 8,
      justifyContent: 'center',
      marginTop: 12,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "download",
    onClick: template
  }, en ? 'Download template' : 'تنزيل القالب'), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "secondary",
    icon: "folder-open",
    onClick: () => ref.current && ref.current.click()
  }, en ? 'Choose file' : 'اختيار ملف'), /*#__PURE__*/React.createElement(Button, {
    size: "sm",
    variant: "ghost",
    onClick: sample
  }, en ? 'Use sample file' : 'استخدام ملف تجريبي'))), err && /*#__PURE__*/React.createElement(Alert, {
    tone: "danger"
  }, err), res && /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 10,
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      font: '600 13px var(--font-sans)'
    }
  }, name, " \xB7 ", res.length, " ", en ? 'rows' : 'صف', " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--green-700)'
    }
  }, cnt('valid'), " ", en ? 'ready' : 'جاهز'), " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--amber-600)'
    }
  }, cnt('warning'), " ", en ? 'with notes' : 'بملاحظات'), " \xB7 ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--red-600)'
    }
  }, cnt('error'), " ", en ? 'rejected' : 'مرفوض')), cnt('warning') > 0 && /*#__PURE__*/React.createElement(Checkbox, {
    label: en ? 'Skip rows with notes' : 'تجاهل الصفوف ذات الملاحظات',
    checked: skipWarn,
    onChange: setSkipWarn
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      maxHeight: 360,
      overflow: 'auto'
    }
  }, /*#__PURE__*/React.createElement(DataTable, {
    dense: true,
    columns: [{
      key: 'n',
      label: en ? 'Row' : 'الصف',
      numeric: true
    }, {
      key: 'ar',
      label: t('name_ar'),
      render: r => /*#__PURE__*/React.createElement("b", null, r.ar || '—')
    }, {
      key: 'email',
      label: t('email'),
      numeric: true,
      render: r => r.email || '—'
    }, {
      key: 'role',
      label: t('role'),
      render: r => r.role ? t('role_' + r.role) : r.roleRaw || '—'
    }, {
      key: 'b',
      label: t('branches'),
      wrap: true,
      render: r => r.branches.length ? r.branches.map(b => st.branchLabel(b, lang)).join('، ') : '—'
    }, {
      key: 's',
      label: en ? 'Result' : 'النتيجة',
      render: r => /*#__PURE__*/React.createElement(StatusBadge, {
        status: r.status
      })
    }, {
      key: 'r',
      label: en ? 'Details' : 'التفاصيل',
      wrap: true,
      render: r => /*#__PURE__*/React.createElement("span", {
        style: {
          font: '400 12px var(--font-sans)',
          color: r.status === 'error' ? 'var(--red-600)' : 'var(--text-secondary)'
        }
      }, r.reason)
    }],
    rows: res
  })))));
}
window.UsersScreen = UsersScreen;
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/UsersScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/web-app/dash-map.js
try { (() => {
window.TRACK_DASH_MAP = "        <svg viewBox=\"0 0 730 620\" xmlns=\"http://www.w3.org/2000/svg\" style=\"width:100%;display:block;\">\n<defs>\n<filter id=\"glow4\"><feGaussianBlur stdDeviation=\"4\" result=\"b\"/><feMerge><feMergeNode in=\"b\"/><feMergeNode in=\"SourceGraphic\"/></feMerge></filter>\n<radialGradient id=\"mapR\" cx=\"50%\" cy=\"50%\"><stop offset=\"0%\" stop-color=\"#1a3050\"/><stop offset=\"100%\" stop-color=\"#080e1c\"/></radialGradient>\n<linearGradient id=\"ksaGrad2\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">\n<stop offset=\"0%\" stop-color=\"rgba(59,184,195,0.12)\"/>\n<stop offset=\"100%\" stop-color=\"rgba(74,144,196,0.05)\"/>\n</linearGradient>\n<linearGradient id=\"ksaGradHi2\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">\n<stop offset=\"0%\" stop-color=\"rgba(59,184,195,0.28)\"/>\n<stop offset=\"100%\" stop-color=\"rgba(74,144,196,0.18)\"/>\n</linearGradient>\n</defs>\n<rect width=\"730\" height=\"620\" fill=\"url(#mapR)\" rx=\"8\"/>\n<g stroke=\"rgba(255,255,255,0.04)\" stroke-width=\"1\">\n<line x1=\"0\" y1=\"155\" x2=\"730\" y2=\"155\"/><line x1=\"0\" y1=\"310\" x2=\"730\" y2=\"310\"/>\n<line x1=\"0\" y1=\"465\" x2=\"730\" y2=\"465\"/><line x1=\"182\" y1=\"0\" x2=\"182\" y2=\"620\"/>\n<line x1=\"365\" y1=\"0\" x2=\"365\" y2=\"620\"/><line x1=\"547\" y1=\"0\" x2=\"547\" y2=\"620\"/>\n</g>\n<path d=\"m 456.82718,487.03041 -10.13,2.63 -16.39,2.39 -58.85,5.04 -8.85,-0.7 -3.24,-0.72 -5.22,-2.39 -8.51,-5.59 0,0 -13.54,-12.09 -3.91,-6.36 -2.75,-2.96 -0.22,-1.6 1.23,-6.11 3.34,-6.09 0.08,-2.96 -1.06,-1.73 -3.6,-3.6 -3.07,-4.93 -5.76,-5.01 0,0 -4.34,-7.06 -4.77,-6.36 -0.58,-1.47 1.76,-8.48 -0.54,-6.95 2.33,-3.42 0.68,-2.34 -1.79,-13.1 0.96,-6.16 -0.31,-1.57 -6.45,-1.41 -5.65,1.47 -3.7,-1.28 -1.26,-2.3 0.28,-5.34 0.86,-2.93 -1.84,-1.88 -3.09,-0.85 -14.1,-0.63 -3.01,-1.63 -5.15,-11.26 -0.91,-5.64 -2.1,-4.72 -1.13,-4.86 -4.16,-3.3 -0.36,-3.13 0.44,-3.64 0,0 -0.04,-9.03 1.41,-4.88 -0.94,-3.6 2.95,-4.25 0.4,-3.14 0,0 3.04,-5.08 2.17,-1.58 1.5,0.12 5.17,2.55 3.87,0.81 5.73,0.5 5.67,-0.89 5.69,0.87 2.43,-1.79 0.85,-2.18 -1.91,-3.83 0.15,-2.3 5.42,-6.69 3.27,-2.52 12.88,-3.15 2.84,-2.81 3.09,-1.74 2.28,-4.47 2.02,-1.97 3.13,-0.82 8.3,1.06 7.2,-0.68 5.53,0.22 0.84,-3.9 -0.31,-3.16 -2.52,-4.86 1.08,-2.91 -0.14,-0.86 -6.69,-10.16 -3.6,-3.57 -0.49,-1.52 0.84,-5.74 -0.43,-5.12 4.01,-1.1 4.5,-5.87 2.46,-2.11 4.18,-1.84 0.62,-1.48 -0.15,-2.5 -1.34,-2.5 -3.6,-2.99 -2.35,-0.93 0,0 -0.68,-1.88 0.06,-1.59 0.99,-3.1 2.42,-3.32 3.15,-1.64 0,0 1.94,2.12 1.51,0.52 1.8,-0.86 1.3,-2.23 0,0 3.32,2.32 2.12,2.21 1.4,5.11 1.24,1.53 10.41,6.08 5.75,1.06 3.46,4.22 4.06,3.38 2.34,0.63 5.64,0.11 3.01,0.81 9.86,6.94 2.49,1.19 2.36,0.05 4.41,-1.01 1.55,0.22 6.45,6.08 1.47,2.52 8.88,3.16 3.86,2.08 1.09,2.56 0.43,4.96 0.38,22.63 -0.63,15.07 0.35,7.41 0.68,3.7 1.12,1.79 2.28,1.8 9.44,6.3 9.19,9.16 1.68,2.06 2.95,5.78 1.36,6.89 -0.02,7.75 z\" fill=\"url(#ksaGradHi2)\" stroke=\"rgba(200,220,230,0.75)\" stroke-width=\"1.5\"/>\n<path d=\"m 143.70718,343.80041 0,0 0.12,-0.02 -0.12,0.02 z m 114.74,-19.79 -0.44,3.64 0.36,3.13 4.15,3.3 1.13,4.86 2.11,4.72 0.91,5.64 5.15,11.26 3.01,1.63 14.1,0.63 3.09,0.85 1.84,1.88 -0.85,2.93 -0.29,5.33 1.26,2.31 3.7,1.28 5.65,-1.47 6.45,1.41 0.31,1.57 -0.96,6.17 1.79,13.1 -0.68,2.34 -2.33,3.43 0.55,6.94 -1.76,8.48 0.58,1.47 4.77,6.36 4.34,7.06 0,0 -4.02,1.47 -5.71,3.77 -5.22,-1.3 -2.26,0.25 -3.16,1.52 -4.55,0.28 -4.26,3.81 -6.41,3.06 -10.11,9.24 -2.31,1.14 -2.29,-0.24 -1.4,-1.02 -4.67,-9.07 0,0 -1.98,-1.93 -1.48,-0.22 -5.42,2.76 -4.16,-0.2 -4.61,1.77 -1.91,-1.46 -1.28,-3.77 -1.22,-1.45 -1.43,-0.61 -2.26,0.32 -1.36,1.41 -0.7,2.12 1.11,4.54 -0.12,2.36 -2.69,4.63 -1.37,3.56 -7.73,1.86 -4,4.1 0.17,1.42 0.67,0.7 3.66,1.09 0.97,1.53 -0.31,5.28 0.91,3.89 -0.27,4.44 1.3,1.54 3.69,2.13 2.06,3.06 1.59,1.05 2.59,0 2.21,-1.08 2.58,-8.98 2.4,-4.46 1.52,-0.59 4.61,-0.04 0.79,-0.5 0,0 1.51,-0.74 2.29,0.13 1.85,1.88 0.21,1.52 -1.31,6.92 2.19,4.44 -1.27,4.6 -0.76,1.42 -1.52,0.75 -4.74,-0.43 -2.24,0.37 -3.88,2.82 -0.75,0.06 -1.97,-1.87 -0.49,10 0.61,2.97 1.02,1.48 1.44,0.48 6.71,0.21 2.11,1.21 2.98,9.14 -1.5,6.41 0,0 -1.01,1.94 -3.55,2.05 -1.97,3.15 0,0 -0.4,-1.87 -0.27,-0.33 -0.95,-1.23 0.03,-0.49 -0.32,-0.57 -0.37,-0.37 -0.51,0.76 -0.36,-0.35 0.36,-1.15 -0.46,-0.24 -0.48,-0.65 0.15,-0.87 -0.73,-0.87 -0.54,-2.04 0.05,-0.8 -0.17,-1.41 -0.82,-0.8 0.15,-1.21 -1.41,-1.06 -0.63,-1.3 -2.26,-0.09 -1.06,-0.83 -0.19,-1.62 -1.74,-2.17 1.24,-4.89 -1.56,-0.4 -1.15,-0.92 -1.2,-2.73 1.29,-3 -0.2,-1.69 -3.05,-1.3 -1.11,-2.63 0.38,-1.51 -0.45,-1.44 -0.82,-0.58 -0.56,-1.36 -1.58,-0.81 0.3,-0.43 -0.48,-2.03 0.5,-2.77 -1.22,-1.31 -2.27,-0.56 -2.79,-2.11 -0.28,-1.02 1.04,0.93 0.34,-2.1 -1.13,-3.14 -1.43,-1.64 -2.06,0.57 -1.01,-0.32 0.18,0.78 1.11,0.52 -0.3,0.28 -1.35,-0.84 -0.43,-1.5 -1.42,-0.71 -0.3,-2 -1.3,-0.58 0.42,-1.26 -0.41,-1.12 -5.69,-3.91 -2.98,-0.95 -1.24,-2.03 -0.65,-0.05 0.14,-1.25 -2.29,0.07 -1.45,-1.52 -0.51,-1.48 -1.65,-0.59 -1.89,0.26 0.06,0.51 -0.75,0.13 -2.1,-1.56 -1.14,0.2 1.53,0.77 0.28,0.74 -2.67,-1.27 -5.15,-3.89 -1.76,-1.95 0.58,-0.54 -1.07,-0.75 0.18,-1.07 -0.96,0.31 -0.7,-0.68 -1.01,-1.32 0.23,-0.37 1,0.68 -0.4,-1.17 -0.99,0.09 -1.48,-1.98 -1.64,-3.02 0.52,-0.77 -0.17,-0.74 -1.45,-0.9 -0.15,0.64 0.6,0.97 -0.58,-0.22 -0.8,-2.17 0.89,0.43 0.34,-0.2 -0.53,-0.43 0.29,-0.25 -0.67,-0.12 -0.55,0.19 -2.05,-1.72 0.25,-1.31 -0.84,-0.77 -1.29,-0.07 -1.24,-1.94 0.16,-1.2 -1.32,-2.35 -1.66,-1 -0.89,-4.24 -0.83,-0.7 -1.27,-2.5 0.11,-0.78 0.87,-0.39 1.29,-0.9 0.7,-1.87 -1.22,-1.02 0.13,-0.82 -0.26,-0.74 0.9,-0.1 -0.92,-1.34 0.25,-0.39 -0.59,0 -0.24,0.29 -0.78,-0.67 -0.93,-6.07 -1.43,-3.12 -3.24,-4.23 -0.38,-1.86 0.39,-0.47 -0.51,-1.44 0.59,-0.78 1.37,-0.28 -1.02,1.88 0.52,0.64 0.46,-0.88 0.79,0.17 0.2,-2.75 1.22,0.1 -0.24,-0.86 0.56,-2.21 -0.34,-0.69 -0.56,2.85 -0.58,-0.26 0.8,-1.95 -0.32,-1.47 2.25,-4.19 -0.02,-1.7 0.44,-0.72 0.84,-0.18 0.32,-1.38 -1.17,-0.01 -0.15,0.92 -0.33,-0.19 -0.72,-0.57 0.48,-2.14 -0.1,-3.6 -1.88,-3.15 -0.39,-1.76 -1.17,-1.43 0.89,-0.9 -0.93,-0.07 -0.43,0.31 -4.27,-7.13 1.3,0.49 1.37,2.24 -0.19,0.47 1.19,0.21 0.51,-0.72 -1.58,-2.25 -0.05,-1.49 -2.29,0 0.32,-0.53 -0.89,0.1 -0.26,0.35 -1.44,-2.19 0.74,-1.65 -0.73,-3.8 -0.25,-0.76 -0.68,0.68 -1.68,-2.45 -0.09,-1.34 -0.33,-0.39 -0.23,-0.15 -0.98,-2.23 -0.07,-2.6 0,0 5.12,-0.4 3.08,-0.92 0.82,0.34 2.13,3.42 2.42,1.85 3.42,4.08 1.68,1.3 1.55,0.23 4.88,-1.11 1.58,0.37 0.43,3.86 0.7,0.58 10.44,-3.25 4.76,-0.31 1.07,1.39 0.28,3.25 -2.65,6.93 0.06,0.86 0.59,0.74 3.98,1.18 2.09,1.91 0.45,2.31 -0.83,3.87 0.65,1.58 4.76,0.56 2.33,-1.12 4.63,-4.1 6.79,2.21 1.62,-0.02 4.47,-6.66 9.34,-7.12 5.12,-5.32 2.45,-1.77 5.67,-2.31 0.56,-0.76 -0.4,-1.57 -5.35,-2.94 -0.76,-0.66 -0.2,-1.49 3.59,-2.87 11.75,-5.83 1.06,-1.53 0.69,-2.21 -0.16,-3.22 -1.11,-2.52 0.58,-2.32 3.24,-1.03 5.03,0.65 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 130.84718,324.65041 -0.9,-0.61 -0.33,0.61 -0.7,-0.29 -0.33,-0.44 -1.1,-1 0.38,-0.9 -1,0.41 -1.03,-1.53 -1.82,-1.19 0.16,-0.31 -0.62,-0.34 -1.13,0.46 -3.64,-2.84 0.62,-0.51 -0.59,-0.29 0.58,-1.68 -1.39,0.76 0.45,0.38 -0.44,1.27 -1.68,-0.19 -2.75,-2.06 -1.19,-2.11 -0.79,0.16 -0.38,-0.71 -0.69,-0.14 -0.09,-0.83 -1.55,0.6 -1.04,-0.36 -0.08,0.81 0.54,0.16 -0.37,0.91 -1.51,0.05 -0.66,-0.98 -1.17,0.09 -1.82,-2.71 -1.49,-1.07 0.31,-1.82 0.67,0.46 0.26,-1.12 -1.18,-1.47 -0.42,-1.45 -1.040004,-0.88 0,0 0.550004,-3.24 0.95,-1.47 5.65,-0.48 0,0 2.36,-0.91 0,0 0.79,-0.71 0,0 1.17,-1.81 0,0 0.55,-0.71 0,0 1.55,-0.41 2.31,0.45 0,0 2.58,-0.03 1.61,-0.83 0.92,-1.54 0.22,-1.56 0,0 -0.11,-3.18 0,0 -0.22,-10.27 0,0 0.84,-4.77 0,0 0.1,-0.74 0,0 0.25,-2.3 0,0 -1.12,-1.4 0,0 -3.17,-0.13 0,0 -0.74,-0.31 0,0 -1.79,-6.32 0,0 -1.8,-3.88 -3.72,-3.33 0,0 -2.17,-1.85 -0.67,-1.64 0,0 -0.54,-2.27 -0.73,-0.61 0,0 -2.23,-1.15 0,0 -4.010004,-2.52 -1.53,0.17 0,0 -1.95,1.78 -0.28,-1.56 0,0 -0.81,-3.12 -1.69,-3.17 0,0 -0.67,-1.68 0,0 0.16,-2.33 0,0 3.15,-5.54 0.26,-1.76 0,0 -0.36,-1.18 0,0 -1.54,-0.95 0,0 -4.78,-1.35 0,0 -1.6,-1.31 0,0 -1,-1.58 -0.69,-2.63 0.81,-8.93 0,0 -0.99,-4.2 0,0 -1.43,-1.38 0,0 -1.54,-0.18 0,0 -3.12,0.69 0,0 -0.81,-0.48 -0.09,-0.75 1,-1.47 0,0 3.12,-0.69 0,0 1.62,-0.43 0,0 1.67,-0.81 0,0 1.59,-0.57 0,0 3.22,-1.5 0,0 1.64,-1.06 0,0 0.76,-0.34 0,0 1.55,-0.39 0,0 3.96,1.66 0,0 2.360004,1.74 0,0 2.42,0.31 1.73,-0.42 5.85,-3.33 0,0 1.55,-0.46 0,0 0.78,0.31 0,0 1.11,1.52 0,0 0.8,3.16 0,0 0.56,1.68 0,0 2.45,4.27 0,0 1.09,1.55 0,0 0.81,0.56 0,0 1.52,0.26 0,0 5.38,0.24 1,1.24 0,0 -0.2,1.59 0,0 0.15,0.79 1.47,1.04 0,0 2.27,1.42 0,0 0.15,0.75 0,0 -0.43,2.4 0.19,0.75 0.74,0.34 0,0 0.75,-0.01 1.57,-1.19 0,0 1.54,-1.37 0,0 1.69,-1.29 1.65,-0.33 6.16,2.51 0.8,-0.64 -0.52,-1.5 0,0 -0.31,-1.5 0.42,-0.73 0.75,-0.04 0,0 1.28,1.18 0.96,3.08 0,0 2.36,0.83 0,0 2.36,0.58 2.27,1.43 0,0 1.52,1.23 0,0 2.42,1.83 2.26,0.41 0,0 6.57,-0.52 0,0 10.45,-2.02 0,0 1.53,-0.07 0,0 0.77,0.43 0,0 1.22,1.43 0,0 0.56,1.01 0,0 0.68,1.56 0.24,2.66 -0.75,2.42 -1.62,2.25 0,1.63 1.18,1.5 0.77,0.24 3.27,-0.84 0.73,0.25 0.36,0.79 -0.11,4.16 -1.9,10.91 2.08,9.8 -0.52,10.14 0.63,1.61 1.3,1.49 1.56,0.71 1.55,0.03 9.37,-3.24 8.06,-0.39 2.41,-1.18 3.87,-4.64 3.27,-1.25 6.68,-0.15 11.8,2.31 0,0 6.92,1.83 1.35,1.16 0.28,1.98 -0.57,11.32 1.74,2.02 5.3,1.39 1.41,1.13 3.15,10.79 4.08,3.38 0,0 -0.4,3.14 -2.95,4.25 0.94,3.6 -1.41,4.88 0.04,9.03 0,0 -5.05,-0.85 -3.23,1.03 -0.58,2.32 1.11,2.52 0.16,3.23 -0.69,2.21 -1.06,1.53 -11.75,5.84 -3.59,2.87 0.2,1.49 0.76,0.66 5.35,2.94 0.4,1.57 -0.56,0.76 -5.67,2.31 -2.45,1.77 -5.12,5.33 -9.34,7.12 -4.47,6.66 -1.62,0.02 -6.79,-2.21 -4.63,4.1 -2.33,1.12 -4.76,-0.56 -0.65,-1.58 0.83,-3.86 -0.45,-2.31 -2.09,-1.91 -3.98,-1.18 -0.59,-0.74 -0.06,-0.86 2.65,-6.92 -0.28,-3.25 -1.07,-1.39 -4.76,0.31 -10.44,3.25 -0.7,-0.58 -0.43,-3.86 -1.58,-0.37 -4.88,1.11 -1.55,-0.23 -1.67,-1.3 -3.42,-4.08 -2.42,-1.84 -2.12,-3.42 -0.82,-0.34 -3.08,0.92 -5.12,0.4 0,0 -0.21,0.03 0,0 -0.12,0.02 0,0 -0.64,-3.24 -1.32,-1.32 0.21,-1.05 -0.97,0.4 -0.65,-0.35 -0.13,0.75 0.84,0.4 -0.37,0.4 -0.86,-0.5 0.1,-1.51 -0.57,-1.35 0.13,-0.52 -0.78,-0.4 -0.7,-0.82 -0.95,-2.79 -0.01,-1.63 -1.02,-1.12 -0.92,-0.09 -0.87,-1.56 -2.41,-1.65 -0.58,-1.03 -0.44,-0.28 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 545.08718,262.60041 1.22,0.53 0.35,2.07 -0.6,-1.32 -1.01,-0.6 0.04,-0.68 z m -8.35,-39.01 0.87,0.69 -0.05,1.6 -1.18,-1.16 -1.01,-0.12 0.21,-0.65 1.16,-0.36 z m -18.37,-29.14 1.75,1.05 4.11,1.08 -1.87,0.05 -0.52,-0.26 -2.37,0.75 -0.45,-0.6 0.45,-0.25 -0.11,-0.49 -1.17,-0.55 -2.13,2.34 0.34,0.57 -0.4,-0.13 -0.22,-0.78 0.55,-1.55 0.7,-0.87 1.34,-0.36 z m 19.57,33.55 0.38,0.99 0.46,-1.63 0.45,0.11 -0.06,2.39 0.79,0.44 1.19,-1.76 -0.36,-0.33 0.38,-1.81 0.25,0.39 0.12,1.06 0.38,-0.61 0.05,1.18 -0.98,0.58 0.08,1.05 -0.63,0.11 1.65,1.52 0.52,1.62 -0.54,4.1 0.45,0.68 -0.36,0.03 -0.1,1.48 -1,1.13 0.19,-0.78 -0.87,-1.22 -0.16,2.24 0.42,0.52 -0.83,2.12 0.19,1.3 -0.75,0.42 -2.62,-5.96 -0.76,-0.54 -0.52,0.32 -1.15,2.79 0.89,1.29 -0.52,0.67 0.04,3.02 0.61,0.16 0.8,-0.92 0.81,0.74 1.37,0.1 0.5,0.75 -0.15,0.79 0.63,0.57 -0.69,0.16 -0.37,1.04 1.28,2.91 1.81,2.68 1.09,0.66 0.7,2.02 0.75,0.4 -0.23,0.88 -0.41,0.18 -0.4,-1.47 -1.42,-0.33 -1,-1.47 -0.55,0.17 -0.47,-0.58 0.68,1.74 1.09,0.76 0.05,0.56 0.51,-0.72 0.22,0.28 0.24,1.8 1.28,1.16 0.67,1.46 1.26,0.44 0.07,1.1 2.04,2.54 0.4,0.04 -0.05,-1.1 -0.68,-0.91 0.66,-0.53 0.62,1.64 3.23,2.49 1.33,4.04 -0.34,2.41 1.25,2.03 -0.41,1.78 0.83,2.56 -0.27,0.88 0.89,0.45 -0.5,-0.65 0.32,-0.82 2.52,3.53 1.05,3.3 0.95,0.04 0.54,0.69 1.49,6.53 0.64,-1.84 1.58,-0.3 -0.25,0.48 1.08,2.13 3.69,4.38 4.58,0.8 2.45,-0.96 1.63,-1.8 1.16,0.39 1,-0.33 -0.1,0.51 0.6,0.44 0.76,-0.35 -0.66,1.28 0.13,2.42 1.45,-0.87 0.37,-1.39 1.6,-1.3 -0.09,-0.95 0.89,0.53 0.53,-0.43 0.41,1.64 1.03,-0.58 0.76,0.76 -0.62,0.64 -1.21,0.07 -0.54,0.95 -0.44,-0.16 -0.42,1.48 -3.07,2.44 -0.6,3.09 -0.56,0.13 0.13,1.7 2.24,0.42 0.9,-1.18 2.03,-0.18 1.67,0.66 1.51,2.12 1.63,-0.07 0.75,1.18 -0.51,4.23 34.3,41.63 23.79,4.76 59.71,13.76 7.05,-7.46 15.68,25.98 -22.97,73.24 -103.4,36.4 -39.96,7.94 -57.87,6.31 -32.09,15.68 -20.91,25.6 -4.87,12.21 -1.53,0.93 0,0 2.59,-24.45 7.99,-63.49 0,0 19.5,-152.83 0.02,-7.75 -1.36,-6.9 -2.96,-5.78 -1.67,-2.06 -9.19,-9.15 -9.44,-6.3 -2.29,-1.8 -1.12,-1.79 -0.68,-3.7 -0.34,-7.4 0.63,-15.07 -0.38,-22.62 -0.43,-4.96 -1.09,-2.56 -3.86,-2.08 -8.88,-3.17 -1.46,-2.52 -6.45,-6.08 -1.55,-0.22 -4.41,1.01 -2.35,-0.04 -2.5,-1.19 -9.86,-6.94 -3.01,-0.81 -5.64,-0.11 -2.35,-0.63 -4.06,-3.38 -3.46,-4.22 -5.74,-1.06 -10.41,-6.08 -1.23,-1.53 -1.41,-5.11 -2.12,-2.21 -3.32,-2.32 0,0 -1.47,-1.82 -0.38,-1.53 1.28,-4.67 9.15,-12.15 12.66,-23.67 8.26,-8.72 12.45,-4.39 0,0 5.1,0.13 2.64,-1.27 4.09,-3.16 31.35,4.11 0.87,0.67 3.36,5.38 0.61,2.55 0.22,4.61 3.53,4.7 -0.17,0.7 24.98,-0.43 1.18,1.14 1.28,0.47 -0.04,2.25 0.84,0.66 -0.18,0.34 0.85,0.15 -1.01,3.01 0.97,1.98 1.47,1.03 1.7,2.23 -0.69,0.53 -0.18,2.67 0.39,1 2.05,2.38 3.89,1.05 -0.77,0.92 -0.84,-0.08 0.08,1.05 1.59,1.81 1,0.1 0.05,1.35 2.03,1.26 -0.36,2.1 0.46,0.44 -0.08,0.47 -0.9,0.6 0,-0.89 -0.55,-0.04 0.29,-2 -0.72,1.02 -0.29,-1.41 -1.3,4.4 0.64,0.08 0.45,-1.25 0.35,2.38 0.64,-0.95 0.42,0.68 -0.34,1.18 1.07,-0.31 -0.32,0.58 -0.52,0.11 -0.05,0.82 -0.32,0.69 1.12,0.54 0.75,-1.32 0.63,0.19 -0.57,1.04 0.11,1.29 0.81,-0.41 -0.1,-1.04 1.56,-0.79 0.94,0.37 1.08,1.57 2.59,1.16 3.66,-0.06 1.69,0.83 0.61,1.33 1.12,0.63 0.2,1.82 -0.56,-0.15 0.25,-0.69 -0.36,-0.54 -0.71,1.15 -0.54,-0.61 -0.71,0.71 -0.75,-0.72 -0.74,0.47 -0.31,-0.45 -0.69,0.92 -0.82,-0.34 -0.33,0.73 0.41,-0.21 1.54,0.94 0.56,0.71 1.01,-0.24 -0.03,-1.05 1.36,0.77 -0.61,0.76 -0.73,0.07 0.32,0.92 -0.67,1.23 1.72,-1.09 1.62,0.54 -0.01,3.71 0.67,1.13 0.54,0.02 0.42,-0.06 0.53,-0.23 1.14,0.57 -0.48,1.89 -0.65,-0.23 -0.44,0.41 0.51,0.01 -0.2,0.56 0.76,-0.31 0.53,1.03 0.67,-1.73 0.5,0.35 -0.15,1.06 1.2,-0.66 1.46,0.51 -0.89,-1.68 1.27,-1.31 -1.73,-1.58 -0.68,0.08 0.65,-1.04 -0.21,-0.53 0.74,-0.47 0.46,1.97 1.11,0.06 0.23,1.5 -0.74,0.31 0.97,0.96 0.9,-0.7 0.59,-2.69 0.97,-1.31 -0.99,2.95 -0.44,1.56 -0.63,0.96 0.55,1.96 1.17,1.16 2.4,-1.43 1.07,0.41 -0.03,0.09 -0.49,-0.01 -0.09,0.04 -0.27,0.47 -0.27,-0.47 -2.28,1.16 0.96,1.23 1.49,-0.36 -0.16,0.73 -0.56,0.02 0.02,1.19 0.71,0.03 0.24,0.85 0.13,0.2 2.3,1.81 0.37,0.11 1.21,0.73 1.13,0.37 0.79,0.61 2.98,0.3 2.07,2.21 4.49,5.29 0.49,0.45 0.41,0.4 -0.59,0.09 -1.3,-1.73 -1.68,-0.43 -0.98,0.68 -1.23,-1.95 -0.06,-0.88 -0.47,2.24 0.15,0.95 0.81,0.2 -0.35,1.09 0.31,2.66 0.93,2.33 1.41,1.84 0.1,-1.15 0.31,-0.31 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 238.57718,264.11041 1.2,-1.37 4.95,-0.74 6,-3.57 5.95,-0.77 0.81,-0.65 0.05,-0.82 -2.4,-4.78 0.32,-2.44 1.26,-1.51 0.86,-0.19 3.95,1.42 2.43,0.25 0.47,-0.77 -0.82,-6.33 1.89,-4.8 0.77,-0.41 1.6,0.52 3.32,-0.78 0.87,-4.3 4.75,-4.83 11.89,-9.24 11.31,-5.23 2.76,-5.79 2.72,-2.76 14.5,-7.43 4.15,-1.34 2.37,0.86 4.44,4.49 5.99,4.12 7.47,0.54 5.05,-5.84 0,0 2.35,0.93 3.6,2.99 1.34,2.5 0.15,2.5 -0.62,1.48 -4.18,1.84 -2.46,2.11 -4.5,5.87 -4.01,1.1 0.43,5.12 -0.84,5.74 0.49,1.52 3.6,3.57 6.69,10.16 0.14,0.86 -1.08,2.91 2.52,4.86 0.31,3.16 -0.84,3.9 -5.53,-0.22 -7.2,0.68 -8.3,-1.06 -3.13,0.82 -2.02,1.97 -2.28,4.47 -3.09,1.74 -2.84,2.81 -12.88,3.15 -3.27,2.52 -5.42,6.69 -0.15,2.3 1.91,3.83 -0.85,2.18 -2.43,1.79 -5.69,-0.87 -5.67,0.89 -5.73,-0.5 -3.87,-0.81 -5.17,-2.55 -1.5,-0.12 -2.17,1.58 -3.04,5.08 0,0 -4.08,-3.38 -3.15,-10.8 -1.4,-1.13 -5.31,-1.39 -1.74,-2.01 0.58,-11.32 -0.28,-1.98 -1.35,-1.16 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 183.65718,221.36041 1.99,-2.39 2.34,-7.76 3.13,-3.74 0,0 4.44,-4.13 0.29,-2.41 0,0 -0.36,-0.76 0,0 -2.95,-1.84 0,0 -0.82,-0.36 0,0 -3.73,-2.16 -1.4,-3.91 0,0 -0.14,-0.92 0,0 -0.39,-1.63 0,0 -2.24,-9.37 -1.05,-2.51 0,0 -1.4,-1.64 -2.37,-0.99 0,0 -0.77,-0.46 -1.33,-2.43 -0.75,-0.31 0,0 -2.31,-0.67 0,0 -1.3,-1.59 0,0 -2.94,-2.27 -4.95,-1.3 0,0 -1.54,-0.24 0,0 -4.08,-0.72 0,0 -2.36,-0.52 -1.13,-1.33 0,0 3.69,-4.2 6.27,-3.54 9.93,-4.38 4.29,-0.69 13.07,-7.6 12.9,-5.53 3.04,0.66 0,0 4.38,1.9 2.37,0.37 20.96,-4.87 12.17,3.53 4.76,-1.35 4.45,-0.36 14.47,3.51 2.37,1.99 4.2,5.16 2.52,1.46 3.99,-0.21 4.62,-2.48 2.68,-0.47 0.67,0.8 0.88,6.22 1.42,1.46 0.96,0.31 6.49,-0.31 6,3.12 2.52,-0.84 5.05,-3.38 1.57,-0.21 1.58,1.23 1.08,1.62 5.28,9.66 2.78,7.7 2.02,2.48 2.44,1.44 9.98,1.29 3.28,1.26 1.62,0.95 6.08,5.52 1.03,1.87 0,0 -3.15,1.64 -2.42,3.32 -0.99,3.1 -0.06,1.59 0.68,1.88 0,0 -5.05,5.84 -7.47,-0.54 -5.99,-4.12 -4.44,-4.49 -2.37,-0.86 -4.15,1.34 -14.5,7.43 -2.72,2.76 -2.76,5.79 -11.31,5.23 -11.89,9.24 -4.75,4.83 -0.87,4.3 -3.32,0.78 -1.6,-0.52 -0.77,0.41 -1.89,4.8 0.82,6.33 -0.47,0.77 -2.43,-0.25 -3.95,-1.42 -0.86,0.19 -1.26,1.51 -0.32,2.44 2.4,4.78 -0.05,0.82 -0.81,0.65 -5.95,0.77 -6,3.57 -4.95,0.74 -1.2,1.37 0,0 -11.8,-2.31 -6.69,0.15 -3.27,1.25 -3.87,4.64 -2.41,1.18 -8.06,0.39 -9.37,3.24 -1.55,-0.03 -1.56,-0.7 -1.3,-1.49 -0.63,-1.61 0.51,-10.14 -2.07,-9.81 1.9,-10.9 0.12,-4.16 -0.37,-0.79 -0.73,-0.24 -3.27,0.83 -0.76,-0.24 -1.18,-1.5 0,-1.62 1.62,-2.26 0.74,-2.41 -0.24,-2.66 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 89.197176,284.95041 1.2,0.84 0,0.66 -1.49,-0.68 0.29,-0.82 z m -7.94,-17.27 0.92,0.6 0.42,-0.21 0.96,2.68 -0.7,0 0.08,-1.17 -2.04,-1.11 0.36,-0.79 z m 4.56,-1.3 0.65,0.16 0.38,1.27 0.55,-0.18 -0.26,0.83 -0.21,-0.47 -1.25,0.04 0.41,-1.09 -0.67,-0.43 0.4,-0.13 z m -0.81,-1.47 0.9,0.87 -1.33,-0.1 0.43,-0.77 z m -3.62,-2.17 0.56,0.93 -0.85,0.56 -0.43,-0.67 0.72,-0.82 z m 2.01,-0.4 0.94,2.34 -1.03,-0.85 0.09,-1.49 z m 0.41,-0.85 0.41,0.44 -0.26,0.62 -0.63,-0.79 0.48,-0.27 z m -14.24,-2.19 1.39,1.61 -1.55,0.1 -0.63,-1.01 0.79,-0.7 z m 2.62,-1.99 0.67,0.53 -0.74,0.07 -1.33,-1.36 -0.02,-1.67 0.47,1.79 1.05,-0.63 -0.1,1.27 z m -65.0999998,-86.2 0.88,0.19 0.6,1.53 -1.02,0.43 -0.73,-1.53 -0.45,0.03 -0.06,0.82 -0.6,-0.11 0.24,-0.9 1.14,-0.46 z m -5.35,-2.13 0.48,0.71 -1.23,0.49 -0.45,-0.32 0.02,0.38 1.1,0.84 1.93,-0.34 -0.1,0.81 1.17,1.07 -2.38,0.35 -1.67,-1.25 -0.33,-2.16 1.46,-0.58 z m 47.9399998,-37.38 5.08,1.55 0,0 2.51,1.93 1.99,2.37 0,0 1.17,1.64 0,0 6.23,8.13 0,0 1.72,0.64 1.81,-0.32 0,0 3.28,-1.38 4.39,-1.03 0,0 4.46,-0.83 0,0 5.16,0.72 4.62,1.8 8.550004,0.72 0,0 1.57,-0.57 0,0 2.58,-6.05 0,0 1.26,-1.48 0,0 1.58,-0.72 0,0 2.87,-2.88 0,0 0.77,-0.41 1.57,0.95 0,0 0.46,1.64 0,0 -0.45,7.19 0.31,4.39 0,0 0.57,2.64 0,0 0.75,1.64 0,0 3.48,3.55 2.42,1.21 0,0 3.31,1.1 0,0 10.14,1.18 0,0 2.4,-1.75 0.79,-0.04 0,0 1.54,1.26 0,0 0.78,0.47 0,0 1.64,-0.31 0,0 0.75,0.21 0.79,2.47 1.29,1.08 0,0 1.73,-0.09 3.56,-1.88 0,0 3.39,-0.99 1.64,0.49 1.4,1.22 0,0 1.13,1.32 2.36,0.52 0,0 4.08,0.72 0,0 1.54,0.24 0,0 4.95,1.31 2.94,2.27 0,0 1.3,1.6 0,0 2.31,0.67 0,0 0.75,0.31 1.33,2.42 0.78,0.46 0,0 2.36,0.99 1.41,1.64 0,0 1.04,2.51 2.24,9.37 0,0 0.39,1.63 0,0 0.14,0.92 0,0 1.4,3.91 3.73,2.15 0,0 0.81,0.36 0,0 2.96,1.84 0,0 0.35,0.76 0,0 -0.29,2.41 -4.44,4.13 0,0 -3.13,3.74 -2.33,7.77 -1.99,2.39 0,0 -0.56,-1.01 0,0 -1.22,-1.43 0,0 -0.77,-0.43 0,0 -1.53,0.08 0,0 -10.45,2.03 0,0 -6.57,0.52 0,0 -2.26,-0.4 -2.42,-1.83 0,0 -1.52,-1.23 0,0 -2.27,-1.43 -2.36,-0.58 0,0 -2.35,-0.83 0,0 -0.96,-3.08 -1.28,-1.17 0,0 -0.75,0.04 -0.42,0.73 0.31,1.51 0,0 0.52,1.5 -0.8,0.64 -6.15,-2.51 -1.65,0.33 -1.68,1.29 0,0 -1.54,1.37 0,0 -1.57,1.19 -0.75,0.01 0,0 -0.74,-0.34 -0.19,-0.75 0.43,-2.4 0,0 -0.15,-0.75 0,0 -2.27,-1.42 0,0 -1.47,-1.04 -0.15,-0.79 0,0 0.2,-1.59 0,0 -1,-1.24 -5.38,-0.23 0,0 -1.52,-0.26 0,0 -0.81,-0.56 0,0 -1.09,-1.55 0,0 -2.45,-4.27 0,0 -0.56,-1.68 0,0 -0.8,-3.16 0,0 -1.11,-1.52 0,0 -0.77,-0.31 0,0 -1.55,0.46 0,0 -5.85,3.33 -1.73,0.42 -2.42,-0.31 0,0 -2.350004,-1.74 0,0 -3.96,-1.66 0,0 -1.55,0.39 0,0 -0.76,0.34 0,0 -1.64,1.06 0,0 -3.22,1.5 0,0 -1.59,0.57 0,0 -1.67,0.81 0,0 -1.62,0.43 0,0 -3.12,0.69 0,0 -1,1.47 0.09,0.75 0.81,0.48 0,0 3.12,-0.68 0,0 1.54,0.18 0,0 1.43,1.38 0,0 0.99,4.2 0,0 -0.81,8.93 0.69,2.63 1,1.58 0,0 1.6,1.31 0,0 4.78,1.35 0,0 1.54,0.95 0,0 0.36,1.18 0,0 -0.26,1.76 -3.15,5.54 0,0 -0.16,2.33 0,0 0.67,1.68 0,0 1.69,3.17 0.81,3.12 0,0 0.28,1.56 1.95,-1.78 0,0 1.53,-0.17 4.010004,2.52 0,0 2.23,1.16 0,0 0.73,0.62 0.54,2.27 0,0 0.67,1.64 2.17,1.85 0,0 3.72,3.33 1.8,3.88 0,0 1.79,6.32 0,0 0.74,0.31 0,0 3.17,0.13 0,0 1.12,1.4 0,0 -0.25,2.3 0,0 -0.1,0.74 0,0 -0.84,4.77 0,0 0.22,10.27 0,0 0.12,3.18 0,0 -0.22,1.56 -0.92,1.54 -1.61,0.83 -2.58,0.03 0,0 -2.31,-0.45 -1.55,0.41 0,0 -0.55,0.71 0,0 -1.17,1.81 0,0 -0.79,0.71 0,0 -2.36,0.91 0,0 -5.65,0.48 -0.95,1.47 -0.550004,3.24 0,0 -1.62,-2.9 0.14,-0.69 -0.84,0.33 -0.56,-1.64 -2.78,-1.51 0.33,-0.45 -0.77,-1.66 0.84,0.07 -0.05,-1.24 -0.92,-0.35 0.42,0.41 -0.78,0.58 -1.33,-1.78 0.74,-0.6 1.35,0.3 -0.89,0.44 1.2,0.37 0.35,-1.18 1.15,-0.94 -0.69,-1.49 1.33,-3.11 -1.45,-2.83 0.79,-1.46 -0.59,-2.62 -4.67,-5.22 -1.37,-3.49 0.09,-1.57 -2.7,-2.19 0.04,-1.78 -0.93,-0.25 -0.22,-2.07 -1.07,-1.57 -2.11,-0.91 -2.23,-3.18 -0.39,0.01 0.53,1.51 -0.37,0.18 -0.61,-0.06 -0.53,-0.89 -0.9,-0.06 -1.1,-0.86 0.18,-1.06 -0.49,0.39 -0.45,-0.52 -0.58,-1.92 -0.96,-0.3 1.62,-2.56 0.64,-2.31 -0.24,-1.94 -1.79,-1.12 -2.77,-0.16 -0.28,-0.17 -2.35,-1.96 -1.09,-3.69 -3.07,-4.99 -2.17,-5.7 -2.64,-4.47 -2.02,-2.17 -1.02,-1.98 -1.75,-0.97 -1.79,-4.21 -0.16,-1.73 -3.01,-3.32 -1.93,-1.1 -0.29,-0.81 -3.08,-3.08 -0.43,-2.1 1.01,-1.06 -0.31,-1.15 -2.3,-3.78 -2.56,-1.31 -2.59,-2.96 -0.18,-1.36 -1.79,-2.4 -0.4,-3.1 -1.48,-1.89 -0.38,-1.99 -1.02,-1.86 -0.85,-0.86 -1.85,-0.56 -0.19,-2.51 -2.49,-2 -0.65,-1.62 -3.32,-1.72 -0.19,-0.58 2.18,-0.65 -0.07,-0.59 -2,-0.36 -0.68,-0.59 -1.3,0.06 -1.7,-1.66 -0.56,0.22 -0.9,-0.6 0,0.26 0.51,0.81 -1.1,-0.65 -2.4,1.32 -2.25,-0.17 -0.53,0.41 -0.23,-0.96 1.02,-0.56 -1.66,0.11 -0.58,1.35 -0.27,-0.42 0.64,-1.12 -0.56,0.08 -0.54,0.73 -1.0199998,-0.16 0.44,1.27 -0.96,0.02 0.44,-1.68 -1.35,-0.19 0.32,-0.33 -0.44,-0.83 -0.25,0.49 -0.72,-0.3 -1.13,1.56 0.59,1.1 -0.68,0.14 -0.71,1.44 -0.78,-1.45 0.2,-1.28 -0.53,-0.22 -0.75,0.5 0.01,-0.79 1.96,-2.53 0.97,0.36 -0.42,-1.04 0.6,-0.43 0.76,-2.54 -0.34,-0.08 3.31,-8.17 1.1499998,-1.73 -0.27,-3.51 -0.7699998,-1.93 1.9199998,-5.76 0.03,-2.95 0,0 3.23,0.16 1.64,-0.61 2.49,0.78 0,0 5.25,0.69 0,0 1.92,-0.12 2.31,-2.25 0,0 3.26,-0.84 0,0 6.03,-1.09 0,0 2.35,0.22 0,0 3.31,-0.69 0,0 3.38,-0.17 0,0 2.61,0.51 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 403.89718,130.68041 -12.46,4.39 -8.25,8.72 -12.66,23.68 -9.15,12.15 -1.29,4.67 0.39,1.52 1.47,1.83 0,0 -1.3,2.23 -1.8,0.86 -1.51,-0.52 -1.94,-2.12 0,0 -1.03,-1.87 -6.08,-5.52 -1.62,-0.95 -3.28,-1.26 -9.98,-1.29 -2.44,-1.44 -2.02,-2.48 -2.78,-7.7 -5.28,-9.66 -1.08,-1.62 -1.58,-1.23 -1.57,0.21 -5.05,3.38 -2.52,0.84 -6,-3.12 -6.49,0.31 -0.96,-0.31 -1.42,-1.46 -0.88,-6.22 -0.67,-0.8 -2.68,0.47 -4.62,2.48 -3.99,0.21 -2.52,-1.46 -4.2,-5.16 -2.37,-1.99 -14.47,-3.51 -4.45,0.36 -4.76,1.35 -12.17,-3.53 -20.96,4.87 -2.37,-0.37 -4.38,-1.9 0,0 5.62,-5.69 0.89,-6.06 0.39,-9.36 1.4,-2.47 6.16,-4.72 27.27,-16.849995 5.26,-4.73 -0.1,-1.57 -7.78,-6.26 -3.13,-6.39 -2.09,-2.5 -3.6,-1.59 -5.45,0.04 -3.23,-0.65 -7.57,-6.08 -2.7,-1.26 -7.01,0.88 -17.95,-0.62 -3.7,-1.12 -3.98,-3.44 -1.78,-0.77 -4.38,-0.08 -19.01,-3.45 -8.05,0.14 -7.25,1.36 -13.58,1.49 -3.42,-0.04 -2.31,-0.62 -2.19,-1.9 -3.89,-6.96 -4.7,-3.69 -0.58,-6.72 2.84,-8.34 1.27,-6.06 0,0 35.03,-10.31 10.52,-9.1700004 -0.45,-2.01 1.16,1.54 2.33,1.41 18.56,5.42 6.07,2.8400004 3.42,2.31 88.69,62.66 66.05,45.649995 7,1.62 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 260.53718,593.84041 0.42,0.19 0.18,-0.09 0.34,0.26 -0.65,1.12 -0.68,-0.76 0.39,-0.72 z m -0.48,-3.22 0.36,0.53 -0.42,1.5 -0.08,-0.96 -0.49,0.77 -0.56,-1.17 0.35,-0.56 0.84,-0.11 z m -6.48,-0.49 1.42,1.06 -0.8,-0.19 -0.62,-0.87 z m -3.48,-2.47 1.93,0.5 1.13,1.63 -3.2,-1.03 -0.65,-0.76 0.79,-0.34 z m 9.66,-3.75 0.49,0.34 -1.62,0.14 1.13,-0.48 z m -9.18,-1.81 3.17,1.84 1.47,3.11 2.35,0.67 1.59,-0.36 0.99,1.34 1.6,-0.47 -0.28,-0.9 -0.44,-0.07 0.28,0.62 -0.84,0.04 -0.43,-0.61 0.52,-0.58 0.1,-1.28 0.59,0.1 0.18,-0.63 0.86,0.1 0.21,1.43 0.76,1.02 0.42,-0.08 0.59,1 0.91,-0.21 0.03,0.51 0.47,-0.06 -0.08,0.94 -0.62,0.65 0.44,2.97 -0.93,0.11 -1.24,-1.42 -1.81,-0.37 1.24,-0.6 -0.09,-1.26 -1.8,-0.44 -3.04,0.93 -1.97,-1.64 -1.14,-0.32 -1.19,-1.03 -0.96,-1.9 -1.33,-0.03 0.46,-1.25 -1,-0.81 -0.04,-1.06 z m -6.47,-0.16 2.05,3.05 -1.59,-0.6 0.21,-0.33 -0.75,-0.69 0.08,-1.43 z m 11.93,-2.08 1.14,0.78 -0.56,0.94 0.58,0.92 1.38,0.42 -0.13,0.87 -1.3,-0.32 -0.74,0.59 2.19,1.66 0.74,1.04 -0.28,0.45 -0.76,-1.15 -3.02,-1 -1.83,-2.61 0.01,-1.08 1.64,-0.2 0.94,-1.31 z m -8.03,-0.22 1.51,1.1 -0.69,1.16 -1,0.31 -0.94,-1.16 1.12,-1.41 z m 3.62,-0.86 1.13,0.65 -0.47,0.57 -0.44,-0.06 -0.22,-1.16 z m 5.16,0.24 0.42,1.41 -2.06,-2.08 1.64,0.67 z m -5.78,-47.93 4.46,-0.47 1.26,1.03 -0.82,7.27 0.28,1.5 0.74,0.47 2.95,-0.45 2.15,0.23 1.08,1.35 0.85,2.89 0.73,0.64 8.28,0.4 1.28,1.24 1.59,3.73 3.25,3.17 1.52,0.63 1.41,-0.14 0.56,-0.66 1.67,-6.75 1.21,-2.1 2.87,-2.16 2.21,-0.88 1,1.12 -0.13,4.42 3.6,1.82 2.64,4.64 0.69,0.57 2.95,0.6 1.98,3.01 0,0 0.97,1.39 -1.92,2.92 0.09,2.37 1.55,0.9 0,0.65 0.81,0.35 0.26,0.69 -4.49,1.41 0.42,1.87 -1.52,1.37 0.05,1.45 -0.62,0.32 -0.06,0.88 0.09,0.35 0.98,-0.4 1.63,0.48 0.82,1.64 -0.55,0.91 -2.27,-0.26 0.26,2.81 -1.5,0.7 -0.04,0.6 1.45,1.89 1.55,0.34 0.33,0.83 1.01,0.48 -0.22,1.26 0.48,0.86 -1.43,0.11 -0.32,0.81 0.49,2.6 -0.84,0.15 -2.07,-1.11 -0.66,0.18 0.44,0.9 -1.03,2.74 0.05,1.06 -4.42,1.16 -1.67,1.19 0.42,2.71 -1.71,0.38 -4.82,-0.49 -0.34,-0.69 0.6,-1.77 -1.21,-1.16 -0.84,-2.21 0.56,-3.56 -0.33,-0.29 -0.47,-0.6 0,-0.93 0.01,-0.57 -2.51,-3.11 -1.41,-0.69 -0.64,0.25 -0.39,-1.35 -0.51,-0.19 -0.69,-0.31 1.07,-0.96 -0.53,-1.39 0.31,-1.85 -1.37,-1.12 -2.17,-0.67 -1.33,-1.88 0.23,-1.83 -0.46,-0.18 -1.12,-0.67 -0.26,0.3 1.13,1.95 -0.66,0.66 0.05,1.98 -0.3,0.95 -0.18,-3.73 -0.98,-3.19 -0.53,-4.16 -0.56,-0.31 0.66,-2.11 -0.29,-1.63 -1.44,-0.38 -1.57,-2.47 -1.97,-1.51 -0.98,0.05 -0.2,-1.49 -0.79,-0.94 -7.32,-5.21 -0.65,-1.03 -1.45,-0.33 -1.24,-1.02 -1.05,-0.03 0.19,-0.8 -3.78,-3.48 0.69,-0.01 -1.01,-0.91 0.26,-1.07 -0.59,0.79 -0.49,-0.33 -0.12,-0.55 0.59,-0.18 -0.22,-1.01 -1.39,-0.54 -0.76,-1.79 0,0 1.97,-3.15 3.55,-2.05 0.98,-2.11 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 399.93718,569.40041 -17.23,-2.36 -6.25,-0.42 -6.25,-4.03 -19.73,0 -1.39,0.98 -1.95,0.27 -2.64,-1.39 -2.7,0.12 -8.79,1.12 -0.82,1.52 -1.08,-1.34 -2.95,0.53 -1.37,1.92 -2.61,-0.72 -1.6,0.43 -1.97,-0.29 -0.68,-0.89 -2.02,0.36 -2.59,-2.7 0.07,-1.04 0,0 1.15,-3.24 -1.3,-5.93 0.67,-1.47 0.77,-8.37 -1.38,-6.12 0.08,-1.66 0.88,-1.69 3.08,-3.43 8.21,-7.56 1.08,-1.48 1.06,-3.02 0.95,-1.05 6.32,-2.02 2.38,-1.42 1.22,-1.41 0.97,-2.22 0.17,-2.12 -1.76,-8.57 0.69,-1.59 3.85,-1.94 1.12,-1.39 0.04,-6.08 0,0 8.51,5.59 5.22,2.39 3.24,0.72 8.85,0.7 58.85,-5.04 16.39,-2.39 10.13,-2.63 0,0 -7.99,63.49 -2.59,24.45 0,0 -7.48,4.72 -7.03,-0.36 -8.18,-11.04 -10.7,1.53 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 260.01718,447.12041 -2.15,6.42 0.23,0.79 2.05,1.97 0.11,1.59 -1.04,1.45 -5.82,3.97 -0.84,1.79 0.23,3.55 -4.26,8.69 0,0 -0.79,0.51 -4.62,0.04 -1.52,0.58 -2.4,4.46 -2.58,8.99 -2.21,1.08 -2.58,0 -1.59,-1.05 -2.06,-3.06 -3.68,-2.13 -1.3,-1.54 0.26,-4.43 -0.91,-3.89 0.31,-5.29 -0.97,-1.52 -3.65,-1.1 -0.67,-0.7 -0.18,-1.41 4.01,-4.1 7.73,-1.86 1.37,-3.56 2.69,-4.63 0.12,-2.35 -1.11,-4.55 0.7,-2.11 1.36,-1.42 2.26,-0.31 1.43,0.61 1.21,1.45 1.28,3.78 1.92,1.46 4.61,-1.77 4.17,0.2 5.41,-2.75 1.49,0.22 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 13.437176,126.05041 0.66,-2.82 0.74,-0.76 -0.18,-0.82 1.04,-0.61 -0.48,-1.92 0.81,-0.46 -0.24,-0.86 0.61,-1.36 38.21,6.79 14.65,-12.07 8.85,-14.779995 25.760004,-5.16 5.78,-13.18 11.32,-6.78 -34.260004,-39.72 33.690004,-9.9 0,0 -1.27,6.06 -2.84,8.34 0.58,6.72 4.7,3.69 3.89,6.96 2.19,1.9 2.31,0.62 3.42,0.04 13.58,-1.49 7.25,-1.36 8.05,-0.14 19.01,3.45 4.38,0.08 1.78,0.77 3.98,3.44 3.7,1.12 17.95,0.62 7.01,-0.88 2.7,1.26 7.57,6.08 3.23,0.65 5.45,-0.04 3.6,1.59 2.09,2.5 3.13,6.39 7.78,6.26 0.1,1.57 -5.26,4.73 -27.27,16.849995 -6.16,4.72 -1.4,2.47 -0.39,9.36 -0.89,6.06 -5.62,5.69 0,0 -3.04,-0.66 -12.9,5.53 -13.07,7.6 -4.29,0.69 -9.93,4.38 -6.27,3.54 -3.69,4.2 0,0 -1.41,-1.21 -1.63,-0.49 -3.39,1 0,0 -3.56,1.88 -1.73,0.08 0,0 -1.3,-1.08 -0.78,-2.47 -0.75,-0.21 0,0 -1.64,0.32 0,0 -0.77,-0.47 0,0 -1.54,-1.26 0,0 -0.8,0.04 -2.4,1.75 0,0 -10.13,-1.18 0,0 -3.32,-1.1 0,0 -2.42,-1.21 -3.47,-3.55 0,0 -0.75,-1.63 0,0 -0.57,-2.65 0,0 -0.31,-4.39 0.45,-7.19 0,0 -0.46,-1.64 0,0 -1.57,-0.94 -0.77,0.41 0,0 -2.87,2.88 0,0 -1.58,0.72 0,0 -1.26,1.47 0,0 -2.58,6.06 0,0 -1.57,0.57 0,0 -8.550004,-0.71 -4.62,-1.8 -5.16,-0.72 0,0 -4.47,0.84 0,0 -4.39,1.03 -3.28,1.37 0,0 -1.81,0.33 -1.72,-0.64 0,0 -6.23,-8.13 0,0 -1.17,-1.64 0,0 -1.99,-2.37 -2.51,-1.93 0,0 -5.08,-1.54 0,0 -2.61,-0.51 0,0 -3.38,0.17 0,0 -3.31,0.69 0,0 -2.35,-0.22 0,0 -6.03,1.09 0,0 -3.27,0.83 0,0 -2.3,2.26 -1.93,0.12 0,0 -5.24,-0.69 0,0 -2.49,-0.78 -1.64,0.61 -3.23,-0.16 0,0 0.39,-2.96 0.64,-1.27 -0.41,-1.66 0.82,-1.35 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<path d=\"m 303.24718,558.19041 -1.98,-3.01 -2.95,-0.6 -0.68,-0.57 -2.65,-4.63 -3.6,-1.82 0.14,-4.42 -1,-1.12 -2.21,0.88 -2.87,2.16 -1.21,2.1 -1.66,6.75 -0.57,0.66 -1.4,0.14 -1.52,-0.63 -3.25,-3.16 -1.59,-3.73 -1.28,-1.24 -8.28,-0.4 -0.73,-0.64 -0.85,-2.89 -1.08,-1.35 -2.16,-0.23 -2.94,0.45 -0.74,-0.47 -0.28,-1.5 0.83,-7.27 -1.27,-1.03 -4.46,0.47 0,0 1.5,-6.41 -2.97,-9.14 -2.11,-1.2 -6.71,-0.21 -1.43,-0.49 -1.03,-1.47 -0.61,-2.97 0.49,-10 1.97,1.88 0.75,-0.06 3.87,-2.82 2.24,-0.38 4.74,0.44 1.52,-0.75 0.76,-1.42 1.27,-4.6 -2.19,-4.44 1.31,-6.92 -0.21,-1.51 -1.85,-1.89 -2.29,-0.13 -1.5,0.74 0,0 4.26,-8.69 -0.23,-3.55 0.84,-1.79 5.82,-3.97 1.04,-1.45 -0.11,-1.59 -2.05,-1.97 -0.23,-0.79 2.15,-6.42 0,0 4.67,9.08 1.4,1.02 2.28,0.24 2.31,-1.13 10.11,-9.25 6.41,-3.06 4.26,-3.8 4.55,-0.28 3.16,-1.52 2.26,-0.24 5.21,1.3 5.72,-3.77 4.02,-1.46 0,0 5.76,5.01 3.07,4.93 3.6,3.6 1.06,1.73 -0.08,2.96 -3.34,6.09 -1.23,6.11 0.22,1.6 2.75,2.96 3.91,6.36 13.54,12.09 0,0 -0.04,6.08 -1.12,1.39 -3.85,1.94 -0.69,1.59 1.76,8.57 -0.17,2.12 -0.97,2.22 -1.22,1.41 -2.38,1.42 -6.32,2.02 -0.95,1.05 -1.06,3.02 -1.08,1.48 -8.21,7.56 -3.08,3.43 -0.88,1.69 -0.08,1.66 1.38,6.12 -0.77,8.37 -0.67,1.47 1.3,5.93 -1.15,3.24 0,0 -0.11,1.8 -2.37,-2.3 -5.29,-3.37 -2.46,-0.34 z\" fill=\"url(#ksaGrad2)\" stroke=\"rgba(200,220,230,0.55)\" stroke-width=\"1.2\"/>\n<text x=\"420\" y=\"440\" text-anchor=\"middle\" fill=\"rgba(255,255,255,0.22)\"\nfont-size=\"18\" font-family=\"Cairo\" font-weight=\"700\">منطقة الرياض</text>\n<line x1=\"340\" y1=\"262\" x2=\"380\" y2=\"280\" stroke=\"rgba(212,168,75,0.35)\" stroke-width=\"1.5\" stroke-dasharray=\"6,4\"/>\n<line x1=\"380\" y1=\"280\" x2=\"445\" y2=\"300\" stroke=\"rgba(59,184,195,0.35)\" stroke-width=\"1.5\" stroke-dasharray=\"6,4\"/>\n<line x1=\"340\" y1=\"262\" x2=\"445\" y2=\"300\" stroke=\"rgba(74,144,196,0.3)\" stroke-width=\"1.5\" stroke-dasharray=\"6,4\"/>\n<g style=\"cursor:pointer\" data-br=\"majmaah\">\n<circle cx=\"380\" cy=\"280\" r=\"26\" fill=\"rgba(212,168,75,0.13)\" stroke=\"rgba(212,168,75,0.55)\" stroke-width=\"2\">\n<animate attributeName=\"r\" values=\"24;33;24\" dur=\"2.5s\" repeatCount=\"indefinite\"/>\n<animate attributeName=\"opacity\" values=\"1;0.4;1\" dur=\"2.5s\" repeatCount=\"indefinite\"/>\n</circle>\n<circle cx=\"380\" cy=\"280\" r=\"12\" fill=\"#d4a84b\" filter=\"url(#glow4)\"/>\n<circle cx=\"380\" cy=\"280\" r=\"5.5\" fill=\"#fff\"/>\n<text x=\"380\" y=\"320\" text-anchor=\"middle\" fill=\"#d4a84b\" font-size=\"14\" font-family=\"Cairo\" font-weight=\"800\">المجمعة</text>\n<text x=\"380\" y=\"338\" text-anchor=\"middle\" fill=\"rgba(255,255,255,0.4)\" font-size=\"11\" font-family=\"Cairo\">المقر الرئيسي</text>\n</g>\n<g style=\"cursor:pointer\" data-br=\"zulfi\">\n<circle cx=\"340\" cy=\"262\" r=\"23\" fill=\"rgba(74,144,196,0.13)\" stroke=\"rgba(74,144,196,0.55)\" stroke-width=\"2\">\n<animate attributeName=\"r\" values=\"21;29;21\" dur=\"3s\" repeatCount=\"indefinite\"/>\n<animate attributeName=\"opacity\" values=\"1;0.4;1\" dur=\"3s\" repeatCount=\"indefinite\"/>\n</circle>\n<circle cx=\"340\" cy=\"262\" r=\"11\" fill=\"#4a90c4\" filter=\"url(#glow4)\"/>\n<circle cx=\"340\" cy=\"262\" r=\"5\" fill=\"#fff\"/>\n<text x=\"335\" y=\"245\" text-anchor=\"middle\" fill=\"#4a90c4\" font-size=\"14\" font-family=\"Cairo\" font-weight=\"800\">الزلفي</text>\n<text x=\"335\" y=\"230\" text-anchor=\"middle\" fill=\"rgba(255,255,255,0.35)\" font-size=\"11\" font-family=\"Cairo\">فرع</text>\n</g>\n<g style=\"cursor:pointer\" data-br=\"rumah\">\n<circle cx=\"445\" cy=\"300\" r=\"23\" fill=\"rgba(59,184,195,0.13)\" stroke=\"rgba(59,184,195,0.55)\" stroke-width=\"2\">\n<animate attributeName=\"r\" values=\"21;29;21\" dur=\"3.5s\" repeatCount=\"indefinite\"/>\n<animate attributeName=\"opacity\" values=\"1;0.4;1\" dur=\"3.5s\" repeatCount=\"indefinite\"/>\n</circle>\n<circle cx=\"445\" cy=\"300\" r=\"11\" fill=\"#3bb8c3\" filter=\"url(#glow4)\"/>\n<circle cx=\"445\" cy=\"300\" r=\"5\" fill=\"#fff\"/>\n<text x=\"460\" y=\"295\" text-anchor=\"start\" fill=\"#3bb8c3\" font-size=\"14\" font-family=\"Cairo\" font-weight=\"800\">رماح</text>\n<text x=\"460\" y=\"312\" text-anchor=\"start\" fill=\"rgba(255,255,255,0.35)\" font-size=\"11\" font-family=\"Cairo\">فرع</text>\n</g>\n</svg>";
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/dash-map.js", error: String((e && e.message) || e) }); }

// ui_kits/web-app/dashboard-data.js
try { (() => {
(function () {
  const BR = [{
    k: 'majmaah',
    ar: 'المجمعة',
    en: 'Majmaah',
    sub: 'المقر الرئيسي',
    c: 'var(--track-blue)'
  }, {
    k: 'zulfi',
    ar: 'الزلفي',
    en: 'Zulfi',
    sub: 'فرع',
    c: 'var(--blue-500)'
  }, {
    k: 'rumah',
    ar: 'رماح',
    en: 'Rumah',
    sub: 'فرع',
    c: 'var(--track-green)'
  }];
  const SH = ['المجمعة طلاب', 'المجمعة طالبات', 'الزلفي طلاب', 'الزلفي طالبات', 'رماح طلاب', 'رماح طالبات'];
  const SHK = ['majmaah', 'majmaah', 'zulfi', 'zulfi', 'rumah', 'rumah'];
  const mk = (label, o) => Object.assign({
    label,
    shutters: o.sh.map((v, i) => ({
      n: SH[i],
      v,
      br: SHK[i],
      g: i % 2 ? 'f' : 'm'
    }))
  }, o);
  const P8 = ['الموارد البشرية', 'المحاسبة والضرائب', 'إدارة اللوجستيات', 'تطوير التطبيقات', 'البرمجة وعلوم الحاسب', 'التصميم الجرافيكي', 'إدارة وتطوير العقار', 'التجارة الإلكترونية'];
  const P7 = ['الموارد البشرية', 'المحاسبة والضرائب', 'إدارة اللوجستيات', 'التسويق', 'البرمجة وعلوم الحاسب', 'إدارة وتطوير العقار', 'التجارة الإلكترونية'];
  const prog = (names, ps) => names.map((n, i) => ({
    n,
    p: ps[i]
  }));
  const SAT_Q = ['تلقيت معلومات كافية عن أهمية مبادرة محترف', 'استفدت من الدورة في تعزيز مهاراتي المهنية', 'أشعر بالرضا عن مستوى جودة مبادرة محترف', 'الرضا عن دور عضو هيئة التدريس في مساعدتي للحصول على الشهادة'];
  const SAT = [[55, 30, 10, 3, 2], [60, 28, 8, 3, 1], [50, 33, 12, 4, 1], [52, 30, 12, 4, 2]].map((d, i) => ({
    q: SAT_Q[i],
    d
  }));
  const s461 = {
    st: [7, 22, 9, 22, 32, 22, 31, 50, 43, 48, 29, 32, 20, 13, 34, 45, 8],
    rg: [7, 20, 9, 20, 27, 9, 28, 46, 43, 42, 29, 29, 20, 10, 12, 43, 5],
    ct: [4, 18, 9, 20, 21, 9, 24, 39, 42, 42, 29, 27, 20, 9, 4, 42, 2]
  };
  const cert = [mk('الفصل 461', {
    kpi: {
      total: 388,
      days: 60,
      supp: 159,
      fac: 17,
      rep: 8,
      comp: 95,
      admin: 91,
      tech: 98
    },
    gender: {
      m: 55,
      f: 45
    },
    sh: [53, 53, 84, 56, 63, 79],
    branches: {
      majmaah: {
        v: 151,
        m: 53,
        f: 53
      },
      rumah: {
        v: 120,
        m: 63,
        f: 79
      },
      zulfi: {
        v: 117,
        m: 84,
        f: 56
      }
    },
    programs: prog(P8, [56, 81, 74, 99, 57, 82, 66, 96]),
    sec: s461,
    excellence: false
  }), mk('الفصل 462', {
    kpi: {
      total: 388,
      days: 60,
      supp: 159,
      fac: 17,
      rep: 8,
      comp: 95,
      admin: 91,
      tech: 98
    },
    gender: {
      m: 55,
      f: 45
    },
    sh: [53, 53, 84, 56, 63, 79],
    branches: {
      majmaah: {
        v: 151,
        m: 53,
        f: 53
      },
      rumah: {
        v: 120,
        m: 63,
        f: 79
      },
      zulfi: {
        v: 117,
        m: 84,
        f: 56
      }
    },
    programs: prog(P8, [56, 81, 74, 99, 57, 82, 66, 96]),
    sec: s461,
    excellence: false
  }), mk('الفصل الأول 471', {
    kpi: {
      total: 404,
      days: 60,
      supp: 43,
      fac: 16,
      rep: 8,
      comp: 99,
      admin: 98,
      tech: 100
    },
    gender: {
      m: 48,
      f: 52
    },
    sh: [115, 91, 69, 55, 40, 38],
    branches: {
      majmaah: {
        v: 206,
        m: 115,
        f: 91
      },
      rumah: {
        v: 123,
        m: 40,
        f: 38
      },
      zulfi: {
        v: 77,
        m: 69,
        f: 55
      }
    },
    programs: prog(P7, [85, 96, 97, 90, 100, 98, 100]),
    sec: {
      st: [4, 29, 33, 34, 22, 15, 31, 48, 19, 36, 26, 44, 19, 21, 13, 27],
      rg: [4, 28, 32, 34, 21, 15, 31, 48, 18, 36, 26, 44, 19, 21, 13, 25],
      ct: [4, 26, 32, 32, 21, 15, 30, 46, 18, 36, 25, 44, 16, 21, 13, 25]
    },
    excellence: true,
    excellenceNum: 118
  }), mk('الفصل الأول 472', {
    kpi: {
      total: 431,
      days: 120,
      supp: 240,
      fac: 23,
      rep: 12,
      comp: 97,
      admin: 96,
      tech: 98
    },
    gender: {
      m: 42,
      f: 58
    },
    sh: [110, 73, 114, 68, 19, 47],
    branches: {
      majmaah: {
        v: 183,
        m: 110,
        f: 73
      },
      rumah: {
        v: 66,
        m: 19,
        f: 47
      },
      zulfi: {
        v: 182,
        m: 114,
        f: 68
      }
    },
    programs: prog(P7, [85, 96, 98, 90, 100, 97, 100]),
    sec: {
      st: [22, 35, 42, 38, 30, 25, 44, 52, 28, 40, 33, 48, 26, 30, 18, 35, 22, 38],
      rg: [22, 34, 41, 37, 29, 24, 43, 51, 27, 39, 32, 47, 25, 29, 17, 34, 21, 37],
      ct: [21, 33, 40, 36, 28, 23, 42, 50, 26, 38, 31, 46, 24, 28, 16, 33, 20, 36]
    },
    excellence: true,
    excellenceNum: 124
  })];
  cert.forEach(r => r.sat = SAT);
  const FS_Q = ['اكتسبت مهارات جديدة في التفكير النقدي', 'أسهمت الأنشطة في تحسين عملي ضمن فريق'];
  const future = [mk('الفصل الثاني 1447', {
    kpi: {
      total: 512,
      days: 90,
      supp: 34,
      fac: 28,
      rep: 6,
      comp: 88,
      admin: 86,
      tech: 90
    },
    gender: {
      m: 47,
      f: 53
    },
    sh: [98, 104, 86, 92, 61, 71],
    branches: {
      majmaah: {
        v: 202,
        m: 98,
        f: 104
      },
      rumah: {
        v: 132,
        m: 61,
        f: 71
      },
      zulfi: {
        v: 178,
        m: 86,
        f: 92
      }
    },
    programs: prog(P7, [84, 88, 91, 86, 93, 82, 90]),
    sec: {
      st: [30, 28, 34, 26, 31, 29, 35, 27, 33, 30, 28, 32],
      rg: [30, 27, 33, 26, 30, 28, 35, 27, 32, 29, 27, 31],
      ct: [27, 24, 30, 22, 27, 25, 31, 23, 29, 26, 24, 28]
    },
    excellence: false,
    sat: [{
      q: FS_Q[0],
      d: [48, 34, 12, 4, 2]
    }, {
      q: FS_Q[1],
      d: [51, 31, 11, 5, 2]
    }]
  }), mk('الفصل الأول 1448', {
    kpi: {
      total: 286,
      days: 45,
      supp: 18,
      fac: 31,
      rep: 3,
      comp: 62,
      admin: 58,
      tech: 66
    },
    gender: {
      m: 45,
      f: 55
    },
    sh: [54, 63, 49, 58, 29, 33],
    branches: {
      majmaah: {
        v: 117,
        m: 54,
        f: 63
      },
      rumah: {
        v: 62,
        m: 29,
        f: 33
      },
      zulfi: {
        v: 107,
        m: 49,
        f: 58
      }
    },
    programs: prog(P7, [55, 61, 64, 58, 70, 57, 66]),
    sec: {
      st: [31, 29, 35, 27, 33, 30, 34, 28, 32, 29],
      rg: [29, 27, 33, 25, 31, 28, 32, 26, 30, 27],
      ct: [19, 17, 22, 15, 21, 18, 20, 16, 19, 17]
    },
    excellence: false,
    sat: [{
      q: FS_Q[0],
      d: [44, 36, 13, 5, 2]
    }, {
      q: FS_Q[1],
      d: [46, 33, 14, 5, 2]
    }]
  })];
  window.TRACK_DASH = {
    BR,
    byIni: {
      1: future,
      2: cert,
      3: []
    },
    SAT_SCALE: ['موافق بشدة', 'موافق', 'محايد', 'غير موافق', 'غير موافق بشدة']
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/dashboard-data.js", error: String((e && e.message) || e) }); }

// ui_kits/web-app/data.js
try { (() => {
window.DS = window.TRACKDesignSystem_c33017;
window.TRACK_DATA = function () {
  const semesters = [{
    id: '2026-1',
    label: '2026/2027 - الأول',
    current: true
  }, {
    id: '2025-2',
    label: '2025/2026 - الثاني'
  }, {
    id: '2025-1',
    label: '2025/2026 - الأول'
  }];
  const branches = [{
    id: 'mj',
    name: 'المجمعة',
    coordinator: 'د. خالد العتيبي',
    sections: 42,
    students: 1024,
    completion: 92
  }, {
    id: 'zl',
    name: 'الزلفي',
    coordinator: 'د. سارة القحطاني',
    sections: 31,
    students: 768,
    completion: 78
  }, {
    id: 'hl',
    name: 'حائل',
    coordinator: 'د. فهد الشمري',
    sections: 27,
    students: 655,
    completion: 64
  }, {
    id: 'gt',
    name: 'الغاط',
    coordinator: 'د. نورة الدوسري',
    sections: 25,
    students: 590,
    completion: 88
  }, {
    id: 'hs',
    name: 'حوطة سدير',
    coordinator: 'د. عبدالله السبيعي',
    sections: 20,
    students: 445,
    completion: 70
  }];
  const initiatives = [{
    id: 'i1',
    name: 'مهارات المستقبل',
    term: '2026-1',
    start: '2026/09/01',
    end: '2026/12/01',
    status: 'active',
    sections: 48,
    branches: 5,
    completion: 81,
    owner: 'عمادة التطوير الأكاديمي',
    desc: 'تنمية مهارات التفكير النقدي والعمل الجماعي لدى طلبة السنة الأولى عبر أنشطة صفية وتقييم مرحلي.'
  }, {
    id: 'i2',
    name: 'الشهادات الاحترافية',
    term: '2026-1',
    start: '2026/09/10',
    end: '2026/11/15',
    status: 'progress',
    sections: 32,
    branches: 4,
    completion: 54,
    owner: 'وكالة الكلية للشؤون التعليمية',
    desc: 'تهيئة الطلبة لاجتياز شهادات مهنية معتمدة مرتبطة بالمقررات التخصصية.'
  }, {
    id: 'i3',
    name: 'الوعي السيبراني',
    term: '2026-1',
    start: '2026/09/05',
    end: '2026/10/20',
    status: 'completed',
    sections: 26,
    branches: 5,
    completion: 100,
    owner: 'وحدة الأمن السيبراني',
    desc: 'ورش توعوية قصيرة داخل الشعب حول حماية البيانات والسلوك الرقمي الآمن.'
  }, {
    id: 'i4',
    name: 'القراءة المنهجية',
    term: '2026-1',
    start: '2026/10/01',
    end: '2027/01/10',
    status: 'notStarted',
    sections: 18,
    branches: 3,
    completion: 0,
    owner: 'عمادة التطوير الأكاديمي',
    desc: 'برنامج قراءة موجّهة لمراجع المقرر مع تقارير أسبوعية.'
  }, {
    id: 'i5',
    name: 'التعلم بالمشاريع',
    term: '2025-2',
    start: '2026/02/01',
    end: '2026/05/20',
    status: 'closed',
    sections: 40,
    branches: 5,
    completion: 96,
    owner: 'وكالة الكلية للشؤون التعليمية',
    desc: 'مشاريع تطبيقية جماعية ضمن المقررات الأساسية.'
  }, {
    id: 'i6',
    name: 'الإرشاد الأكاديمي',
    term: '2025-1',
    start: '2025/09/05',
    end: '2025/12/25',
    status: 'closed',
    sections: 35,
    branches: 4,
    completion: 88,
    owner: 'عمادة شؤون الطلاب',
    desc: 'متابعة فردية للطلبة المتعثرين داخل الشعب.'
  }];
  const sections = [{
    id: 's123',
    no: '123',
    course: 'ريض 101',
    courseName: 'مبادئ الرياضيات',
    branch: 'mj',
    instructor: 'أ. محمد الحربي',
    students: 38,
    completion: 20,
    status: 'late',
    initiative: 'i1'
  }, {
    id: 's214',
    no: '214',
    course: 'نجل 102',
    courseName: 'اللغة الإنجليزية 2',
    branch: 'zl',
    instructor: 'أ. ريم العنزي',
    students: 34,
    completion: 35,
    status: 'late',
    initiative: 'i1'
  }, {
    id: 's305',
    no: '305',
    course: 'نجل 201',
    courseName: 'الكتابة الأكاديمية',
    branch: 'hl',
    instructor: 'أ. سلمان الرشيدي',
    students: 29,
    completion: 0,
    status: 'notStarted',
    initiative: 'i1'
  }, {
    id: 's118',
    no: '118',
    course: 'حاسب 101',
    courseName: 'مقدمة في الحاسب',
    branch: 'mj',
    instructor: 'د. هند المطيري',
    students: 41,
    completion: 100,
    status: 'completed',
    initiative: 'i1'
  }, {
    id: 's142',
    no: '142',
    course: 'إحص 101',
    courseName: 'مبادئ الإحصاء',
    branch: 'gt',
    instructor: 'أ. تركي الزهراني',
    students: 36,
    completion: 72,
    status: 'active',
    initiative: 'i1'
  }, {
    id: 's157',
    no: '157',
    course: 'سلم 101',
    courseName: 'الثقافة الإسلامية',
    branch: 'hs',
    instructor: 'أ. منى الغامدي',
    students: 40,
    completion: 88,
    status: 'active',
    initiative: 'i1'
  }, {
    id: 's231',
    no: '231',
    course: 'حاسب 210',
    courseName: 'قواعد البيانات',
    branch: 'zl',
    instructor: 'د. ماجد الشهري',
    students: 27,
    completion: 64,
    status: 'progress',
    initiative: 'i2'
  }];
  const firstNames = ['عبدالرحمن', 'لمى', 'يوسف', 'جود', 'فيصل', 'رهف', 'سعود', 'غلا', 'نايف', 'دانة', 'بندر', 'شهد'];
  const lastNames = ['العتيبي', 'القحطاني', 'الحربي', 'الدوسري', 'الشمري', 'المطيري', 'الغامدي', 'الزهراني', 'العنزي', 'السبيعي', 'الرشيدي', 'الشهري'];
  const students = firstNames.map((f, i) => ({
    id: '4410' + (2315 + i * 7),
    name: f + ' ' + lastNames[i * 5 % 12],
    gender: i % 2 ? 'أنثى' : 'ذكر',
    attendance: [100, 92, 85, 100, 78, 96, 64, 100, 88, 90, 72, 100][i],
    score: [18, 17, 12, 20, 9, 19, null, 16, 14, null, 11, 20][i],
    done: [1, 1, 0, 1, 0, 1, 0, 1, 1, 0, 0, 1][i]
  }));
  const trend = [{
    label: '2024/2025 الأول',
    value: 34
  }, {
    label: '2024/2025 الثاني',
    value: 42
  }, {
    label: '2025/2026 الأول',
    value: 51
  }, {
    label: '2025/2026 الثاني',
    value: 63
  }, {
    label: '2026/2027 الأول',
    value: 76
  }];
  return {
    semesters,
    branches,
    initiatives,
    sections,
    students,
    trend,
    branchName: id => (branches.find(b => b.id === id) || {}).name,
    initiativeName: id => (initiatives.find(b => b.id === id) || {}).name
  };
}();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/data.js", error: String((e && e.message) || e) }); }

// ui_kits/web-app/i18n.js
try { (() => {
window.DS = new Proxy(window.TRACKDesignSystem_c33017 || {}, {
  get(o, k) {
    if (o[k]) return o[k];
    if (typeof k === 'string' && /^[A-Z]/.test(k)) {
      console.warn('DS component missing (stale bundle?):', k);
      return function Missing(p) {
        return p && p.children || null;
      };
    }
    return undefined;
  }
});
(function () {
  const I18N = {
    ar: {
      app: 'منصة إدارة ومتابعة المبادرات الأكاديمية',
      nav_home: 'الرئيسية',
      nav_dashboard: 'لوحة المؤشرات',
      nav_programs: 'البرامج الأكاديمية',
      nav_initiatives: 'المبادرات',
      nav_users: 'المستخدمون',
      nav_terms: 'الفصول الدراسية',
      nav_branches: 'الفروع',
      nav_courses: 'المقررات',
      nav_sections: 'الشعب الدراسية',
      nav_mysections: 'شعبي',
      nav_students: 'الطلبة',
      nav_imports: 'استيراد البيانات',
      nav_audit: 'سجل التدقيق',
      logout: 'تسجيل الخروج',
      role_SUPER_ADMIN: 'سوبر أدمن',
      role_BRANCH_COORDINATOR: 'منسق فرع',
      role_INSTRUCTOR: 'مدرس',
      login_title: 'تسجيل الدخول',
      login_sub: 'ادخل بريدك المؤسسي وكلمة المرور',
      email: 'البريد الإلكتروني المؤسسي',
      password: 'كلمة المرور',
      sign_in: 'تسجيل الدخول',
      login_generic: 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
      login_locked: 'محاولات كثيرة. حاول مجددًا بعد {s} ثانية.',
      login_deactivated: 'تم تعطيل هذا الحساب. يرجى التواصل مع مسؤول النظام.',
      demo_accounts: 'حسابات تجريبية',
      no_register: 'لا يوجد تسجيل ذاتي — يُنشئ مسؤول النظام جميع الحسابات.',
      first_login: 'يُنصح بتغيير كلمة المرور الأولية',
      first_login_msg: 'أنت تستخدم كلمة المرور التي أنشأها المسؤول. يمكنك تغييرها الآن أو لاحقًا من ملفك الشخصي.',
      change_now: 'تغيير الآن',
      welcome: 'مرحبًا، {n}',
      current_term: 'الفصل الحالي',
      k_users: 'المستخدمون النشطون',
      k_branches: 'الفروع',
      k_sections: 'الشعب',
      k_students: 'الطلبة',
      k_enroll: 'التسجيلات النشطة',
      k_imports: 'عمليات الاستيراد',
      quick: 'إجراءات سريعة',
      recent_activity: 'آخر النشاط',
      view_all: 'عرض الكل',
      profile: 'الملف الشخصي',
      change_pw: 'تغيير كلمة المرور',
      current_pw: 'كلمة المرور الحالية',
      new_pw: 'كلمة المرور الجديدة',
      confirm_pw: 'تأكيد كلمة المرور',
      pw_rule: '8 أحرف على الأقل، تتضمن حرفًا ورقمًا.',
      pw_same: 'كلمة المرور الجديدة مطابقة للحالية.',
      pw_mismatch: 'التأكيد لا يطابق كلمة المرور الجديدة.',
      pw_wrong: 'كلمة المرور الحالية غير صحيحة.',
      pw_policy: 'يجب أن تكون 8 أحرف على الأقل وتتضمن حرفًا ورقمًا.',
      pw_changed: 'تم تغيير كلمة المرور',
      pw_changed_msg: 'تم إنهاء جلساتك الأخرى.',
      language: 'اللغة',
      save: 'حفظ',
      cancel: 'إلغاء',
      confirm: 'تأكيد',
      close: 'إغلاق',
      edit: 'تعديل',
      create: 'إنشاء',
      add: 'إضافة',
      export: 'تصدير',
      search: 'بحث',
      all: 'الكل',
      back: 'رجوع',
      retry: 'إعادة المحاولة',
      required: 'هذا الحقل مطلوب.',
      users_sub: 'إنشاء حسابات المنسقين والمدرسين وإدارتها',
      new_user: 'مستخدم جديد',
      name_ar: 'الاسم بالعربية',
      name_en: 'الاسم بالإنجليزية',
      role: 'الدور',
      branches: 'الفروع',
      status: 'الحالة',
      last_login: 'آخر دخول',
      never: 'لم يسجّل الدخول',
      initial_pw: 'كلمة المرور الأولية',
      coord_branches: 'الفروع التي يشرف عليها',
      coord_need: 'اختر فرعًا واحدًا على الأقل.',
      email_taken: 'هذا البريد مستخدم في حساب آخر (نشط أو معطّل).',
      email_bad: 'صيغة البريد غير صحيحة.',
      user_created: 'تم إنشاء الحساب',
      user_saved: 'تم حفظ التعديلات',
      deactivate: 'تعطيل الحساب',
      reactivate: 'إعادة التفعيل',
      reset_pw: 'إعادة تعيين كلمة المرور',
      deact_q: 'تعطيل حساب {n}؟',
      deact_impact: 'لن يتمكن من تسجيل الدخول، وستبقى جميع سجلاته منسوبة إليه.',
      reset_q: 'إعادة تعيين كلمة مرور {n}؟',
      reset_impact: 'سيتم إنهاء جميع جلساته المفتوحة فورًا.',
      reset_done: 'تمت إعادة التعيين',
      sections_link: 'الشعب المُسندة',
      optional_later: 'اختياري — يمكن إسنادها لاحقًا أو عبر الاستيراد.',
      edit_user: 'تعديل المستخدم',
      terms_sub: 'الفصول الدراسية وحالاتها. فصل واحد فقط يكون حاليًا.',
      new_term: 'فصل دراسي جديد',
      year: 'العام الأكاديمي',
      semester: 'الفصل',
      sem_first: 'الأول',
      sem_second: 'الثاني',
      sem_summer: 'الصيفي',
      start: 'تاريخ البداية',
      end: 'تاريخ النهاية',
      set_current: 'تعيين كفصل حالي',
      st_draft: 'مسودة',
      st_active: 'فعّال',
      st_closed: 'مغلق',
      st_archived: 'مؤرشف',
      change_state: 'تغيير الحالة',
      state_q: 'تغيير حالة الفصل إلى «{s}»؟',
      state_impact: 'تغيير حالة الفصل يؤثر على جميع الشعب والتسجيلات التابعة له.',
      current_q: 'تعيين {n} كفصل حالي؟',
      current_impact: 'سيتوقف {o} عن كونه الفصل الحالي.',
      term_saved: 'تم حفظ الفصل',
      end_after: 'تاريخ النهاية يجب أن يكون بعد البداية.',
      branches_sub: 'كل فرع يمثل مقرًا ونوعًا. نوع الشعبة مستمد من فرعها.',
      new_branch: 'فرع جديد',
      campus_ar: 'اسم المقر بالعربية',
      campus_en: 'اسم المقر بالإنجليزية',
      gender: 'النوع',
      male: 'طلاب',
      female: 'طالبات',
      coordinators: 'المنسقون',
      sections_n: 'الشعب',
      active_flag: 'فعّال',
      branch_saved: 'تم حفظ الفرع',
      no_delete: 'لا يمكن حذف فرع يحتوي على سجلات — يمكن تعطيله فقط.',
      courses_sub: 'رمز المقرر واسمه بالعربية والإنجليزية',
      new_course: 'مقرر جديد',
      code: 'الرمز',
      course: 'المقرر',
      course_saved: 'تم حفظ المقرر',
      code_taken: 'رمز المقرر مستخدم.',
      sections_sub_admin: 'جميع الشعب في الفصل المحدد',
      sections_sub_coord: 'شعب الفروع التي تشرف عليها فقط',
      sections_sub_inst: 'الشعب التي تدرّسها حاليًا والشعب المشتركة معك',
      new_section: 'شعبة جديدة',
      section_no: 'رقم الشعبة',
      branch: 'الفرع',
      instructor: 'المدرس',
      students_n: 'الطلبة',
      no_instructor: 'بدون مدرس',
      no_term_first: 'يجب إنشاء فصل دراسي أولًا قبل إضافة الشعب.',
      dup_section: 'توجد شعبة بنفس الفصل والفرع والمقرر ورقم الشعبة ({k}).',
      section_created: 'تم إنشاء الشعبة',
      no_sections_inst: 'لم تُسند إليك أي شعبة بعد',
      no_sections_inst_msg: 'تواصل مع مكتب المبادرات بالكلية (مسؤول النظام) لإسناد شعبك أو إضافتها عبر الاستيراد.',
      tab_roster: 'الطلبة المسجلون',
      tab_instr: 'سجل المدرسين',
      tab_access: 'الوصول المشترك',
      tab_activity: 'النشاط',
      enroll: 'تسجيل طالب',
      import_list: 'استيراد كشف',
      withdraw: 'سحب',
      withdraw_q: 'سحب {n} من الشعبة؟',
      withdraw_impact: 'سيُعلَّم التسجيل «منسحب» ويبقى سجل الطالب محفوظًا.',
      reason: 'السبب',
      wd_date: 'تاريخ الانسحاب',
      withdrawn_ok: 'تم سحب الطالب',
      enrolled_on: 'تاريخ التسجيل',
      uid: 'الرقم الجامعي',
      uid_bad: 'الرقم الجامعي يجب أن يتكون من 9 أرقام.',
      student: 'الطالب',
      enrolled_ok: 'تم تسجيل الطالب',
      already_enrolled: 'الطالب مسجّل مسبقًا في هذه الشعبة.',
      change_instr: 'تغيير المدرس',
      instr_q: 'نقل الشعبة إلى {n}؟',
      instr_impact: 'ينتقل الوصول إلى المدرس الجديد، وتبقى جميع السجلات الحالية منسوبة لمؤلفها الأصلي.',
      instr_changed: 'تم تغيير المدرس',
      from: 'من',
      to: 'إلى',
      changed_by: 'بواسطة',
      present: 'حتى الآن',
      grant: 'منح وصول',
      grant_to: 'المدرس',
      granted_by: 'منحه',
      granted_at: 'تاريخ المنح',
      grant_ok: 'تم منح الوصول',
      revoke: 'إلغاء',
      students_sub: 'كل طالب سجل واحد برقمه الجامعي مهما تعددت شعبه',
      new_student: 'طالب جديد',
      enrollments: 'التسجيلات',
      student_created: 'تم إنشاء الطالب',
      uid_taken: 'الرقم الجامعي موجود مسبقًا.',
      imports_sub: 'استيراد الكشوف من ملفات Excel أو CSV',
      new_import: 'استيراد جديد',
      central: 'استيراد مركزي',
      section_mode: 'استيراد شعبة',
      file: 'الملف',
      rows: 'الصفوف',
      ran_by: 'بواسطة',
      date: 'التاريخ',
      imported: 'مستورد',
      duplicates: 'مكرر',
      errors: 'أخطاء',
      s_upload: 'رفع الملف',
      s_map: 'مطابقة الأعمدة',
      s_check: 'الفحص',
      s_preview: 'المعاينة',
      s_import: 'الاستيراد',
      s_report: 'التقرير',
      drop: 'اسحب الملف هنا أو اختر من جهازك',
      drop_hint: 'xlsx أو csv · حتى 20 ميجابايت · حتى 50,000 صف',
      choose: 'اختيار ملف',
      use_sample: 'استخدام ملف تجريبي',
      too_big: 'الملف يتجاوز 20 ميجابايت.',
      bad_type: 'نوع الملف غير مدعوم. الأنواع المقبولة: xlsx، csv.',
      target_term: 'الفصل المستهدف',
      target_section: 'الشعبة المستهدفة',
      prev_imported: 'تم استيراد هذا الملف مسبقًا لهذا الفصل. المتابعة لن تنشئ أي سجلات جديدة.',
      map_help: 'تم اقتراح المطابقة من صف العناوين. راجعها وصحّحها. الحقول المعلّمة * إلزامية.',
      src_col: 'عمود الملف',
      target: 'الحقل في المنصة',
      sample: 'مثال',
      unmapped: '— تجاهل —',
      map_need: 'يجب مطابقة جميع الحقول الإلزامية للمتابعة.',
      checking: 'جارٍ فحص الصفوف في الخلفية…',
      checking_hint: 'يمكنك متابعة العمل في شاشات أخرى؛ ستظهر النتيجة هنا.',
      nothing_saved: 'لم يُحفظ أي شيء حتى الآن.',
      p_new_sections: 'شعب جديدة',
      p_new_students: 'طلبة جدد',
      p_new_enroll: 'تسجيلات جديدة',
      p_matched: 'سجلات مطابقة',
      p_warnings: 'تنبيهات',
      p_errors: 'أخطاء',
      first100: 'أول 100 صف',
      row: 'الصف',
      opt_courses: 'مقررات غير معروفة',
      opt_courses_q: 'إنشاء المقررات غير المعروفة',
      opt_inst: 'مدرسون بلا حساب',
      opt_inst_q: 'إنشاء حسابات للمدرسين (غير قابلة للاستخدام حتى إعادة تعيين كلمة المرور)',
      skip_rows: 'تخطي الصفوف',
      confirm_import: 'تأكيد الاستيراد',
      importing: 'جارٍ الاستيراد على دفعات من 500 صف…',
      chunk: 'الدفعة {a} من {b}',
      import_done: 'اكتمل الاستيراد',
      processed: 'تمت معالجته',
      download_errors: 'تنزيل تقرير الأخطاء (xlsx)',
      new_import2: 'استيراد ملف آخر',
      r_dup_file: 'مكرر داخل الملف (الصف {n})',
      r_uid: 'الرقم الجامعي لا يطابق الصيغة (9 أرقام)',
      r_missing: 'قيمة إلزامية مفقودة: {c}',
      r_branch: 'فرع غير معروف: {v}',
      r_course: 'مقرر غير معروف: {v}',
      r_inst: 'مدرس بلا حساب: {v}',
      r_name: 'الاسم يختلف عن المحفوظ ({v}) — سيُحتفظ بالاسم المحفوظ',
      r_section: 'تعذّر تحديد الشعبة',
      r_linked: 'طالب موجود — سيتم ربطه',
      f_term: 'الفصل',
      f_branch: 'الفرع',
      f_gender: 'النوع',
      f_course_code: 'رمز المقرر',
      f_course_name: 'اسم المقرر',
      f_section_no: 'رقم الشعبة',
      f_inst_name: 'اسم المدرس',
      f_inst_email: 'بريد المدرس',
      f_uid: 'الرقم الجامعي',
      f_student_name: 'اسم الطالب',
      f_seq: 'التسلسل',
      f_acad_status: 'الحالة',
      audit_sub: 'سجل دائم لكل إجراء حساس. لا يمكن تعديل أي قيد أو حذفه.',
      actor: 'المنفّذ',
      action: 'الإجراء',
      entity: 'السجل',
      when: 'الوقت',
      ip: 'عنوان الشبكة',
      browser: 'المتصفح',
      before: 'قبل',
      after: 'بعد',
      date_from: 'من تاريخ',
      date_to: 'إلى تاريخ',
      immutable: 'القيود غير قابلة للتعديل أو الحذف',
      details: 'التفاصيل',
      no_entries: 'لا توجد قيود مطابقة',
      forbidden: 'ليست لديك صلاحية لهذا الإجراء',
      forbidden_msg: 'تم تسجيل هذه المحاولة. إذا كنت تعتقد أن هذا خطأ، تواصل مع مسؤول النظام.',
      notfound: 'الصفحة غير موجودة',
      notfound_msg: 'قد يكون الرابط غير صحيح أو أن السجل غير متاح لك.',
      back_home: 'العودة إلى صفحتي',
      shared: 'وصول مشترك',
      saved: 'تم الحفظ',
      demo_scope: 'تجربة: فتح شعبة من فرع آخر',
      demo_forbidden: 'تجربة: إجراء بدون صلاحية',
      idle: 'انتهت الجلسة بسبب عدم النشاط',
      idle_msg: 'لم تفقد بياناتك المُدخلة. سجّل الدخول مجددًا للمتابعة.'
    },
    en: {
      app: 'Academic Initiatives Management Platform',
      nav_home: 'Home',
      nav_dashboard: 'Dashboard',
      nav_programs: 'Academic programs',
      nav_initiatives: 'Initiatives',
      nav_users: 'Users',
      nav_terms: 'Academic terms',
      nav_branches: 'Branches',
      nav_courses: 'Courses',
      nav_sections: 'Sections',
      nav_mysections: 'My sections',
      nav_students: 'Students',
      nav_imports: 'Data import',
      nav_audit: 'Audit log',
      logout: 'Sign out',
      role_SUPER_ADMIN: 'Super Admin',
      role_BRANCH_COORDINATOR: 'Branch coordinator',
      role_INSTRUCTOR: 'Instructor',
      login_title: 'Sign in',
      login_sub: 'Enter your institutional email and password',
      email: 'Institutional email',
      password: 'Password',
      sign_in: 'Sign in',
      login_generic: 'The email or password is incorrect.',
      login_locked: 'Too many attempts. Try again in {s} seconds.',
      login_deactivated: 'This account has been deactivated. Please contact the administrator.',
      demo_accounts: 'Demo accounts',
      no_register: 'There is no self-registration — the administrator creates all accounts.',
      first_login: 'We recommend changing your initial password',
      first_login_msg: 'You are using the password set by the administrator. Change it now or later from your profile.',
      change_now: 'Change now',
      welcome: 'Welcome, {n}',
      current_term: 'Current term',
      k_users: 'Active users',
      k_branches: 'Branches',
      k_sections: 'Sections',
      k_students: 'Students',
      k_enroll: 'Active enrollments',
      k_imports: 'Imports',
      quick: 'Quick actions',
      recent_activity: 'Recent activity',
      view_all: 'View all',
      profile: 'Profile',
      change_pw: 'Change password',
      current_pw: 'Current password',
      new_pw: 'New password',
      confirm_pw: 'Confirm password',
      pw_rule: 'At least 8 characters, including a letter and a digit.',
      pw_same: 'The new password matches your current one.',
      pw_mismatch: 'Confirmation does not match the new password.',
      pw_wrong: 'Current password is incorrect.',
      pw_policy: 'Must be at least 8 characters with a letter and a digit.',
      pw_changed: 'Password changed',
      pw_changed_msg: 'Your other sessions have been signed out.',
      language: 'Language',
      save: 'Save',
      cancel: 'Cancel',
      confirm: 'Confirm',
      close: 'Close',
      edit: 'Edit',
      create: 'Create',
      add: 'Add',
      export: 'Export',
      search: 'Search',
      all: 'All',
      back: 'Back',
      retry: 'Retry',
      required: 'This field is required.',
      users_sub: 'Create and manage coordinator and instructor accounts',
      new_user: 'New user',
      name_ar: 'Arabic name',
      name_en: 'English name',
      role: 'Role',
      branches: 'Branches',
      status: 'Status',
      last_login: 'Last sign-in',
      never: 'Never signed in',
      initial_pw: 'Initial password',
      coord_branches: 'Branches overseen',
      coord_need: 'Select at least one branch.',
      email_taken: 'This email belongs to another account (active or deactivated).',
      email_bad: 'Email format is invalid.',
      user_created: 'Account created',
      user_saved: 'Changes saved',
      deactivate: 'Deactivate account',
      reactivate: 'Reactivate',
      reset_pw: 'Reset password',
      deact_q: 'Deactivate {n}?',
      deact_impact: 'They will not be able to sign in. All their records stay attributed to them.',
      reset_q: 'Reset password for {n}?',
      reset_impact: 'All of their open sessions will end immediately.',
      reset_done: 'Password reset',
      sections_link: 'Assigned sections',
      optional_later: 'Optional — can be assigned later or via import.',
      edit_user: 'Edit user',
      terms_sub: 'Academic terms and their state. Only one term is current.',
      new_term: 'New term',
      year: 'Academic year',
      semester: 'Semester',
      sem_first: 'First',
      sem_second: 'Second',
      sem_summer: 'Summer',
      start: 'Start date',
      end: 'End date',
      set_current: 'Set as current',
      st_draft: 'Draft',
      st_active: 'Active',
      st_closed: 'Closed',
      st_archived: 'Archived',
      change_state: 'Change state',
      state_q: 'Change term state to “{s}”?',
      state_impact: 'Changing a term state affects all of its sections and enrollments.',
      current_q: 'Make {n} the current term?',
      current_impact: '{o} will no longer be current.',
      term_saved: 'Term saved',
      end_after: 'End date must be after start date.',
      branches_sub: 'Each branch is one campus and one gender. Section gender follows its branch.',
      new_branch: 'New branch',
      campus_ar: 'Campus name (Arabic)',
      campus_en: 'Campus name (English)',
      gender: 'Gender',
      male: 'Male',
      female: 'Female',
      coordinators: 'Coordinators',
      sections_n: 'Sections',
      active_flag: 'Active',
      branch_saved: 'Branch saved',
      no_delete: 'A branch that holds records cannot be deleted — only deactivated.',
      courses_sub: 'Course code with Arabic and English names',
      new_course: 'New course',
      code: 'Code',
      course: 'Course',
      course_saved: 'Course saved',
      code_taken: 'Course code already exists.',
      sections_sub_admin: 'All sections in the selected term',
      sections_sub_coord: 'Sections in the branches you oversee only',
      sections_sub_inst: 'Sections you currently teach and sections shared with you',
      new_section: 'New section',
      section_no: 'Section no.',
      branch: 'Branch',
      instructor: 'Instructor',
      students_n: 'Students',
      no_instructor: 'Unassigned',
      no_term_first: 'Create an academic term before adding sections.',
      dup_section: 'A section with the same term, branch, course and number already exists ({k}).',
      section_created: 'Section created',
      no_sections_inst: 'No sections are assigned to you yet',
      no_sections_inst_msg: 'Contact the college initiatives office (Super Admin) to have your sections assigned or imported.',
      tab_roster: 'Enrolled students',
      tab_instr: 'Instructor history',
      tab_access: 'Shared access',
      tab_activity: 'Activity',
      enroll: 'Enroll student',
      import_list: 'Import list',
      withdraw: 'Withdraw',
      withdraw_q: 'Withdraw {n} from this section?',
      withdraw_impact: 'The enrollment will be marked withdrawn; the student record is kept.',
      reason: 'Reason',
      wd_date: 'Withdrawal date',
      withdrawn_ok: 'Student withdrawn',
      enrolled_on: 'Enrolled on',
      uid: 'University ID',
      uid_bad: 'University ID must be 9 digits.',
      student: 'Student',
      enrolled_ok: 'Student enrolled',
      already_enrolled: 'Student is already enrolled in this section.',
      change_instr: 'Change instructor',
      instr_q: 'Reassign section to {n}?',
      instr_impact: 'Access moves to the new instructor. Every existing record keeps its original author.',
      instr_changed: 'Instructor changed',
      from: 'From',
      to: 'To',
      changed_by: 'By',
      present: 'Present',
      grant: 'Grant access',
      grant_to: 'Instructor',
      granted_by: 'Granted by',
      granted_at: 'Granted on',
      grant_ok: 'Access granted',
      revoke: 'Revoke',
      students_sub: 'Each student is one record by University ID, however many sections they are in',
      new_student: 'New student',
      enrollments: 'Enrollments',
      student_created: 'Student created',
      uid_taken: 'University ID already exists.',
      imports_sub: 'Load rosters from Excel or CSV files',
      new_import: 'New import',
      central: 'Central import',
      section_mode: 'Section import',
      file: 'File',
      rows: 'Rows',
      ran_by: 'Run by',
      date: 'Date',
      imported: 'Imported',
      duplicates: 'Duplicates',
      errors: 'Errors',
      s_upload: 'Upload',
      s_map: 'Map columns',
      s_check: 'Check',
      s_preview: 'Preview',
      s_import: 'Import',
      s_report: 'Report',
      drop: 'Drop a file here or choose from your device',
      drop_hint: 'xlsx or csv · up to 20 MB · up to 50,000 rows',
      choose: 'Choose file',
      use_sample: 'Use sample file',
      too_big: 'File exceeds 20 MB.',
      bad_type: 'Unsupported file type. Accepted: xlsx, csv.',
      target_term: 'Target term',
      target_section: 'Target section',
      prev_imported: 'This file was already imported for this term. Continuing will create no new records.',
      map_help: 'Mapping proposed from the header row. Review and correct it. Fields marked * are required.',
      src_col: 'File column',
      target: 'Platform field',
      sample: 'Sample',
      unmapped: '— Ignore —',
      map_need: 'All required fields must be mapped to continue.',
      checking: 'Checking rows in the background…',
      checking_hint: 'You can keep working on other screens; the result will appear here.',
      nothing_saved: 'Nothing has been saved yet.',
      p_new_sections: 'New sections',
      p_new_students: 'New students',
      p_new_enroll: 'New enrollments',
      p_matched: 'Matched records',
      p_warnings: 'Warnings',
      p_errors: 'Errors',
      first100: 'First 100 rows',
      row: 'Row',
      opt_courses: 'Unknown courses',
      opt_courses_q: 'Create unknown courses',
      opt_inst: 'Instructors without accounts',
      opt_inst_q: 'Create instructor accounts (unusable until password reset)',
      skip_rows: 'Skip rows',
      confirm_import: 'Confirm import',
      importing: 'Importing in chunks of 500 rows…',
      chunk: 'Chunk {a} of {b}',
      import_done: 'Import completed',
      processed: 'Processed',
      download_errors: 'Download error report (xlsx)',
      new_import2: 'Import another file',
      r_dup_file: 'Duplicate within file (row {n})',
      r_uid: 'University ID does not match the format (9 digits)',
      r_missing: 'Missing required value: {c}',
      r_branch: 'Unknown branch: {v}',
      r_course: 'Unknown course: {v}',
      r_inst: 'Instructor has no account: {v}',
      r_name: 'Name differs from stored ({v}) — stored name kept',
      r_section: 'Section could not be resolved',
      r_linked: 'Existing student — will be linked',
      f_term: 'Term',
      f_branch: 'Branch',
      f_gender: 'Gender',
      f_course_code: 'Course code',
      f_course_name: 'Course name',
      f_section_no: 'Section no.',
      f_inst_name: 'Instructor name',
      f_inst_email: 'Instructor email',
      f_uid: 'University ID',
      f_student_name: 'Student name',
      f_seq: 'Seq.',
      f_acad_status: 'Status',
      audit_sub: 'Permanent record of every sensitive action. Entries cannot be edited or removed.',
      actor: 'Actor',
      action: 'Action',
      entity: 'Record',
      when: 'When',
      ip: 'Network address',
      browser: 'Browser',
      before: 'Before',
      after: 'After',
      date_from: 'From',
      date_to: 'To',
      immutable: 'Entries are immutable',
      details: 'Details',
      no_entries: 'No matching entries',
      forbidden: 'You do not have permission for this action',
      forbidden_msg: 'This attempt has been recorded. If you think this is a mistake, contact the administrator.',
      notfound: 'Page not found',
      notfound_msg: 'The link may be wrong, or the record is not available to you.',
      back_home: 'Back to my start page',
      shared: 'Shared access',
      saved: 'Saved',
      demo_scope: 'Demo: open another branch’s section',
      demo_forbidden: 'Demo: action without permission',
      idle: 'Session ended after inactivity',
      idle_msg: 'Your entered data is kept. Sign in again to continue.'
    }
  };
  const EXTRA = {
    ar: {
      'act.auth.signed_in': 'تسجيل دخول',
      'act.auth.failed': 'محاولة دخول فاشلة',
      'act.access.denied': 'رفض وصول',
      'act.import.completed': 'اكتمال استيراد',
      'act.enrollment.withdrawn': 'سحب طالب',
      'act.enrollment.created': 'تسجيل طالب في شعبة',
      'act.section.access_granted': 'منح وصول مشترك',
      'act.section.instructor_changed': 'تغيير مدرس الشعبة',
      'act.section.created': 'إنشاء شعبة',
      'act.section.exported': 'تصدير الشعب',
      'act.term.current_set': 'تعيين الفصل الحالي',
      'act.term.state_changed': 'تغيير حالة فصل',
      'act.term.created': 'إنشاء فصل',
      'act.term.updated': 'تعديل فصل',
      'act.user.created': 'إنشاء حساب',
      'act.user.updated': 'تعديل حساب',
      'act.user.deactivated': 'تعطيل حساب',
      'act.user.reactivated': 'إعادة تفعيل حساب',
      'act.user.role_changed': 'تغيير الدور أو الفروع',
      'act.user.password_reset': 'إعادة تعيين كلمة المرور',
      'act.user.password_changed': 'تغيير كلمة المرور',
      'act.student.created': 'إنشاء طالب',
      'act.initiative.created': 'إنشاء مبادرة',
      'act.initiative.updated': 'تعديل مبادرة',
      'act.student.stage_updated': 'تحديث حالة طالب في المبادرة',
      'act.section.initiative_assigned': 'إسناد مبادرة للشعبة',
      'act.section.initiative_updated': 'تعديل حالات المبادرة للشعبة',
      'act.branch.created': 'إنشاء فرع',
      'act.branch.updated': 'تعديل فرع',
      'act.course.created': 'إنشاء مقرر',
      'act.course.updated': 'تعديل مقرر',
      'ent.user': 'مستخدم',
      'ent.initiative': 'مبادرة',
      'ent.section': 'شعبة',
      'ent.enrollment': 'تسجيل',
      'ent.import_job': 'عملية استيراد',
      'ent.term': 'فصل دراسي',
      'ent.student': 'طالب',
      'ent.branch': 'فرع',
      'ent.course': 'مقرر',
      'fld.status': 'الحالة',
      'fld.initiative': 'المبادرة',
      'fld.stage': 'الحالة',
      'fld.stages': 'الحالات',
      'fld.student': 'الطالب',
      'fld.value': 'القيمة',
      'fld.reason': 'السبب',
      'fld.instructor': 'المدرس',
      'fld.user': 'المستخدم',
      'fld.is_active': 'فعّال',
      'fld.current': 'الفصل الحالي',
      'fld.is_current': 'الفصل الحالي',
      'fld.role': 'الدور',
      'fld.branches': 'الفروع',
      'fld.email': 'البريد',
      'fld.name_ar': 'الاسم بالعربية',
      'fld.file': 'الملف',
      'fld.processed': 'تمت معالجته',
      'fld.imported': 'مستورد',
      'fld.duplicates': 'مكرر',
      'fld.errors': 'أخطاء',
      'fld.uid': 'الرقم الجامعي',
      'fld.sessions_revoked': 'إنهاء الجلسات',
      'fld.no': 'رقم الشعبة',
      'fld.section': 'الشعبة',
      'fld.course': 'المقرر',
      'fld.branch': 'الفرع',
      'fld.start': 'البداية',
      'fld.end': 'النهاية',
      'fld.ar': 'الاسم بالعربية',
      'fld.en': 'الاسم بالإنجليزية',
      'fld.code': 'الرمز',
      'fld.gender': 'النوع',
      'fld.active': 'فعّال',
      'fld.rows': 'الصفوف',
      'fld.year': 'العام',
      'fld.semester': 'الفصل',
      'val.true': 'نعم',
      'val.false': 'لا',
      'val.active': 'فعّال',
      'val.withdrawn': 'منسحب',
      'val.out_of_scope': 'خارج النطاق',
      'val.section_not_taught': 'شعبة لا يدرّسها',
      'val.draft': 'مسودة',
      'val.closed': 'مغلق',
      'val.archived': 'مؤرشف',
      'val.SUPER_ADMIN': 'سوبر أدمن',
      'val.BRANCH_COORDINATOR': 'منسق فرع',
      'val.INSTRUCTOR': 'مدرس',
      'val.male': 'طلاب',
      'val.female': 'طالبات',
      'val.first': 'الأول',
      'val.second': 'الثاني',
      'val.summer': 'الصيفي'
    },
    en: {
      'act.auth.signed_in': 'Signed in',
      'act.auth.failed': 'Failed sign-in',
      'act.access.denied': 'Access denied',
      'act.import.completed': 'Import completed',
      'act.enrollment.withdrawn': 'Student withdrawn',
      'act.enrollment.created': 'Student enrolled',
      'act.section.access_granted': 'Shared access granted',
      'act.section.instructor_changed': 'Instructor changed',
      'act.section.created': 'Section created',
      'act.section.exported': 'Sections exported',
      'act.term.current_set': 'Current term set',
      'act.term.state_changed': 'Term state changed',
      'act.term.created': 'Term created',
      'act.term.updated': 'Term edited',
      'act.user.created': 'Account created',
      'act.user.updated': 'Account edited',
      'act.user.deactivated': 'Account deactivated',
      'act.user.reactivated': 'Account reactivated',
      'act.user.role_changed': 'Role or branches changed',
      'act.user.password_reset': 'Password reset',
      'act.user.password_changed': 'Password changed',
      'act.student.created': 'Student created',
      'act.initiative.created': 'Initiative created',
      'act.initiative.updated': 'Initiative edited',
      'act.student.stage_updated': 'Student initiative status updated',
      'act.section.initiative_assigned': 'Initiative assigned to section',
      'act.section.initiative_updated': 'Section initiative statuses edited',
      'act.branch.created': 'Branch created',
      'act.branch.updated': 'Branch edited',
      'act.course.created': 'Course created',
      'act.course.updated': 'Course edited',
      'ent.user': 'User',
      'ent.initiative': 'Initiative',
      'ent.section': 'Section',
      'ent.enrollment': 'Enrollment',
      'ent.import_job': 'Import',
      'ent.term': 'Term',
      'ent.student': 'Student',
      'ent.branch': 'Branch',
      'ent.course': 'Course',
      'fld.status': 'Status',
      'fld.initiative': 'Initiative',
      'fld.stage': 'Stage',
      'fld.stages': 'Stages',
      'fld.student': 'Student',
      'fld.value': 'Value',
      'fld.reason': 'Reason',
      'fld.instructor': 'Instructor',
      'fld.user': 'User',
      'fld.is_active': 'Active',
      'fld.current': 'Current term',
      'fld.is_current': 'Current term',
      'fld.role': 'Role',
      'fld.branches': 'Branches',
      'fld.email': 'Email',
      'fld.name_ar': 'Arabic name',
      'fld.file': 'File',
      'fld.processed': 'Processed',
      'fld.imported': 'Imported',
      'fld.duplicates': 'Duplicates',
      'fld.errors': 'Errors',
      'fld.uid': 'University ID',
      'fld.sessions_revoked': 'Sessions ended',
      'fld.no': 'Section no.',
      'fld.section': 'Section',
      'fld.course': 'Course',
      'fld.branch': 'Branch',
      'fld.start': 'Start',
      'fld.end': 'End',
      'fld.ar': 'Arabic name',
      'fld.en': 'English name',
      'fld.code': 'Code',
      'fld.gender': 'Gender',
      'fld.active': 'Active',
      'fld.rows': 'Rows',
      'fld.year': 'Year',
      'fld.semester': 'Semester',
      'val.true': 'Yes',
      'val.false': 'No',
      'val.active': 'Active',
      'val.withdrawn': 'Withdrawn',
      'val.out_of_scope': 'Out of scope',
      'val.section_not_taught': 'Section not taught',
      'val.draft': 'Draft',
      'val.closed': 'Closed',
      'val.archived': 'Archived',
      'val.SUPER_ADMIN': 'Super Admin',
      'val.BRANCH_COORDINATOR': 'Branch coordinator',
      'val.INSTRUCTOR': 'Instructor',
      'val.male': 'Male',
      'val.female': 'Female',
      'val.first': 'First',
      'val.second': 'Second',
      'val.summer': 'Summer'
    }
  };
  function lbl(lang, prefix, code) {
    const d = EXTRA[lang] || EXTRA.ar;
    const k = prefix + '.' + code;
    return d[k] != null ? d[k] : code;
  }
  function t(lang, key, p) {
    let s = (I18N[lang] || I18N.ar)[key];
    if (s == null) s = key;
    if (p) for (const k in p) s = s.split('{' + k + '}').join(p[k]);
    return s;
  }
  function fmtDate(iso) {
    if (!iso) return '—';
    const d = iso.slice(0, 10).split('-');
    return d[2] + '/' + d[1] + '/' + d[0];
  }
  function fmtDateTime(iso) {
    if (!iso) return '—';
    return fmtDate(iso) + ' ' + iso.slice(11, 16);
  }
  window.TRACK_I18N = {
    I18N,
    t,
    fmtDate,
    fmtDateTime,
    lbl
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/i18n.js", error: String((e && e.message) || e) }); }

// ui_kits/web-app/store.js
try { (() => {
(function () {
  const now = () => new Date().toISOString();
  const S = {
    branches: [{
      id: 1,
      ar: 'المجمعة',
      en: 'Majmaah',
      gender: 'male',
      active: true
    }, {
      id: 2,
      ar: 'المجمعة',
      en: 'Majmaah',
      gender: 'female',
      active: true
    }, {
      id: 3,
      ar: 'الزلفي',
      en: 'Zulfi',
      gender: 'male',
      active: true
    }, {
      id: 4,
      ar: 'الزلفي',
      en: 'Zulfi',
      gender: 'female',
      active: true
    }, {
      id: 5,
      ar: 'رماح',
      en: 'Rumah',
      gender: 'male',
      active: true
    }, {
      id: 6,
      ar: 'رماح',
      en: 'Rumah',
      gender: 'female',
      active: false
    }],
    terms: [{
      id: 1,
      ar: 'الفصل الأول 1448',
      en: 'First Semester 1448',
      year: '2026/2027',
      semester: 'first',
      start: '2026-08-30',
      end: '2027-01-07',
      status: 'active',
      current: true
    }, {
      id: 2,
      ar: 'الفصل الثاني 1447',
      en: 'Second Semester 1447',
      year: '2025/2026',
      semester: 'second',
      start: '2026-01-18',
      end: '2026-05-28',
      status: 'closed',
      current: false
    }, {
      id: 3,
      ar: 'الفصل الثاني 1448',
      en: 'Second Semester 1448',
      year: '2026/2027',
      semester: 'second',
      start: '2027-01-17',
      end: '2027-05-27',
      status: 'draft',
      current: false
    }],
    courses: [{
      id: 1,
      code: 'MATH101',
      ar: 'مبادئ الرياضيات',
      en: 'Principles of Mathematics'
    }, {
      id: 2,
      code: 'ENG102',
      ar: 'اللغة الإنجليزية 2',
      en: 'English Language 2'
    }, {
      id: 3,
      code: 'CS101',
      ar: 'مقدمة في الحاسب',
      en: 'Introduction to Computing'
    }, {
      id: 4,
      code: 'STAT101',
      ar: 'مبادئ الإحصاء',
      en: 'Principles of Statistics'
    }, {
      id: 5,
      code: 'ISLM101',
      ar: 'الثقافة الإسلامية',
      en: 'Islamic Culture'
    }, {
      id: 6,
      code: 'CS212',
      ar: 'برمجة 2',
      en: 'Programming 2'
    }],
    users: [{
      id: 1,
      ar: 'مها السالم',
      en: 'Maha Al-Salem',
      email: 'm.alsalem@mu.edu.sa',
      role: 'SUPER_ADMIN',
      branches: [],
      active: true,
      firstLogin: '2026-08-20T08:10:00',
      lastLogin: '2026-09-26T07:55:00',
      pw: 'Track2026'
    }, {
      id: 2,
      ar: 'د. نورة الدوسري',
      en: 'Dr. Noura Al-Dosari',
      email: 'n.aldosari@mu.edu.sa',
      role: 'BRANCH_COORDINATOR',
      branches: [4],
      active: true,
      firstLogin: '2026-09-01T09:00:00',
      lastLogin: '2026-09-25T12:30:00',
      pw: 'Track2026'
    }, {
      id: 3,
      ar: 'د. فيصل العتيبي',
      en: 'Dr. Faisal Al-Otaibi',
      email: 'f.alotaibi@mu.edu.sa',
      role: 'INSTRUCTOR',
      branches: [],
      active: true,
      firstLogin: null,
      lastLogin: null,
      pw: 'Track2026'
    }, {
      id: 4,
      ar: 'د. خالد الشمري',
      en: 'Dr. Khalid Al-Shammari',
      email: 'k.alshammari@mu.edu.sa',
      role: 'BRANCH_COORDINATOR',
      branches: [1, 2],
      active: true,
      firstLogin: '2026-09-02T10:00:00',
      lastLogin: '2026-09-24T09:10:00',
      pw: 'Track2026'
    }, {
      id: 5,
      ar: 'أ. ريم العنزي',
      en: 'Reem Al-Anazi',
      email: 'r.alanazi@mu.edu.sa',
      role: 'INSTRUCTOR',
      branches: [],
      active: true,
      firstLogin: '2026-09-03T11:00:00',
      lastLogin: '2026-09-22T13:00:00',
      pw: 'Track2026'
    }, {
      id: 6,
      ar: 'أ. سلمان الرشيدي',
      en: 'Salman Al-Rashidi',
      email: 's.alrashidi@mu.edu.sa',
      role: 'INSTRUCTOR',
      branches: [],
      active: false,
      firstLogin: '2026-01-20T08:00:00',
      lastLogin: '2026-05-10T08:00:00',
      pw: 'Track2026'
    }, {
      id: 7,
      ar: 'د. هند المطيري',
      en: 'Dr. Hind Al-Mutairi',
      email: 'h.almutairi@mu.edu.sa',
      role: 'INSTRUCTOR',
      branches: [],
      active: true,
      firstLogin: '2026-09-01T08:00:00',
      lastLogin: '2026-09-26T06:40:00',
      pw: 'Track2026'
    }],
    sections: [{
      id: 11,
      term: 1,
      branch: 4,
      course: 2,
      no: '214',
      instructor: 3
    }, {
      id: 12,
      term: 1,
      branch: 4,
      course: 1,
      no: '215',
      instructor: 3
    }, {
      id: 13,
      term: 1,
      branch: 1,
      course: 1,
      no: '123',
      instructor: 5
    }, {
      id: 14,
      term: 1,
      branch: 4,
      course: 1,
      no: '123',
      instructor: 7
    }, {
      id: 15,
      term: 1,
      branch: 3,
      course: 3,
      no: '305',
      instructor: 7
    }, {
      id: 16,
      term: 1,
      branch: 2,
      course: 4,
      no: '142',
      instructor: 5
    }, {
      id: 17,
      term: 1,
      branch: 5,
      course: 5,
      no: '157',
      instructor: null
    }, {
      id: 18,
      term: 2,
      branch: 4,
      course: 2,
      no: '214',
      instructor: 6
    }, {
      id: 19,
      term: 1,
      branch: 1,
      course: 6,
      no: '2298',
      instructor: 3,
      degree: 'الدبلوم المتوسط',
      activity: 'محاضرة'
    }],
    programs: [{
      id: 1,
      code: 'HR',
      ar: 'الموارد البشرية',
      en: 'Human Resources',
      unit: 'admin',
      active: true
    }, {
      id: 2,
      code: 'ACC',
      ar: 'المحاسبة والضرائب',
      en: 'Accounting & Tax',
      unit: 'admin',
      active: true
    }, {
      id: 3,
      code: 'LOG',
      ar: 'إدارة اللوجستيات',
      en: 'Logistics Management',
      unit: 'admin',
      active: true
    }, {
      id: 4,
      code: 'MKT',
      ar: 'التسويق',
      en: 'Marketing',
      unit: 'admin',
      active: true
    }, {
      id: 5,
      code: 'CS',
      ar: 'البرمجة وعلوم الحاسب',
      en: 'Programming & Computer Science',
      unit: 'tech',
      active: true
    }, {
      id: 6,
      code: 'APP',
      ar: 'تطوير التطبيقات',
      en: 'Application Development',
      unit: 'tech',
      active: true
    }, {
      id: 7,
      code: 'GD',
      ar: 'التصميم الجرافيكي',
      en: 'Graphic Design',
      unit: 'tech',
      active: true
    }, {
      id: 8,
      code: 'RE',
      ar: 'إدارة وتطوير العقار',
      en: 'Real Estate Management',
      unit: 'admin',
      active: true
    }, {
      id: 9,
      code: 'EC',
      ar: 'التجارة الإلكترونية',
      en: 'E-Commerce',
      unit: 'tech',
      active: true
    }],
    grants: [{
      id: 1,
      section: 15,
      user: 3,
      by: 1,
      at: '2026-09-10T10:00:00'
    }],
    history: [{
      section: 11,
      instructor: 6,
      from: '2026-08-30',
      to: '2026-09-08',
      by: 1
    }, {
      section: 11,
      instructor: 3,
      from: '2026-09-08',
      to: null,
      by: 1
    }],
    students: [],
    enrollments: [],
    audit: [],
    imports: []
  };
  const fn = ['عبدالرحمن', 'لمى', 'يوسف', 'جود', 'فيصل', 'رهف', 'سعود', 'غلا', 'نايف', 'دانة', 'بندر', 'شهد', 'ريان', 'أروى', 'مشاري', 'هيا', 'تركي', 'وعد'];
  const ln = ['العتيبي', 'القحطاني', 'الحربي', 'الدوسري', 'الشمري', 'المطيري', 'الغامدي', 'الزهراني', 'العنزي', 'السبيعي', 'الرشيدي', 'الشهري'];
  const fe = ['Abdulrahman', 'Lama', 'Yousef', 'Jood', 'Faisal', 'Rahaf', 'Saud', 'Ghala', 'Nayef', 'Dana', 'Bandar', 'Shahad', 'Rayan', 'Arwa', 'Mishari', 'Haya', 'Turki', 'Waad'];
  const le = ['Al-Otaibi', 'Al-Qahtani', 'Al-Harbi', 'Al-Dosari', 'Al-Shammari', 'Al-Mutairi', 'Al-Ghamdi', 'Al-Zahrani', 'Al-Anazi', 'Al-Subaie', 'Al-Rashidi', 'Al-Shehri'];
  for (let i = 0; i < 36; i++) {
    S.students.push({
      id: 100 + i,
      uid: '44' + String(1002315 + i * 37).padStart(7, '0'),
      ar: fn[i % 18] + ' ' + ln[i * 5 % 12],
      en: fe[i % 18] + ' ' + le[i * 5 % 12]
    });
  }
  const secIds = [11, 12, 13, 14, 15, 16, 17];
  let eid = 1;
  S.students.forEach((st, i) => {
    const a = secIds[i % 7];
    S.enrollments.push({
      id: eid++,
      student: st.id,
      section: a,
      status: 'active',
      enrolled: '2026-09-01',
      withdrawn: null,
      reason: null
    });
    if (i % 3 === 0) {
      const b = secIds[(i + 2) % 7];
      S.enrollments.push({
        id: eid++,
        student: st.id,
        section: b,
        status: 'active',
        enrolled: '2026-09-02',
        withdrawn: null,
        reason: null
      });
    }
  });
  S.enrollments.forEach((e, i) => {
    e.seq = 0;
    e.acad = i % 11 === 5 ? 'منسحب' : i % 13 === 7 ? 'معتذر' : 'منتظم';
  });
  const REAL_FILE = {
    name: 'filedownloadservlet(21).xlsx',
    size: '9 KB',
    meta: {
      campus: 'المجمعة (طلاب)',
      degree: 'الدبلوم المتوسط',
      course: 'برمجة 2',
      activity: 'محاضرة',
      section: '2298'
    },
    headers: ['تسلسل', 'رقم الطالب', 'اسم الطالب', 'الحالة'],
    rows: [["1", "442104959", "سعود بن محمد بن ابراهيم الشدوخي", "منتظم"], ["2", "462103556", "علي بن ابراهيم بن علي حدادي", "منتظم"], ["3", "462109105", "عبدالعزيز بن عبدالرحمن بن عبدالعزيز الجويعي", "منتظم"], ["4", "462109230", "يونس بن علي بن محمد حدادي", "منتظم"], ["5", "462109232", "فهد بن سلطان بن ماضي العتيبي", "منتظم"], ["6", "471103890", "عبدالمجيد بن محمد بن عبدالله الراشد", "منتظم"], ["7", "471104932", "عبدالعزيز بن محمد بن سالم البديري المطيري", "منتظم"], ["8", "471105041", "ثامر بن ابراهيم بن حمد اللهيب", "منتظم"], ["9", "471105043", "عبدالسلام بن فهد بن حمود السبيعي", "منتظم"], ["10", "471105048", "عبدالرحمن بن عبدالله بن ضميان السبيعي العنزي", "منتظم"], ["11", "471105050", "مازن بن بشير بن نافع العنزي", "منتظم"], ["12", "471105056", "ريان بن جلوي بن مسفر المطيري", "منتظم"], ["13", "471105058", "تركي بن سلمان بن فيصل العتيبي", "منتظم"], ["14", "471105060", "بدر بن فهد بن سليمان السبيعي العنزي", "منتظم"], ["15", "471105061", "سلمان بن بدر بن محمد الشثيلي المطيري", "منتظم"], ["16", "471105064", "عبدالعزيز بن مطلق بن حمد العيباني المطيري", "منتظم"], ["17", "471105067", "فهد بن منيور بن قريزح الضبيطي المطيري", "منتظم"], ["18", "471105068", "فارس بن مرزوق بن مقبل البديرى المطيرى", "منتظم"], ["19", "471105069", "عبدالعزيز بن حمد بن عبدالعزيز الجوف", "منتظم"], ["20", "471105074", "نواف بن عبدالله بن محمد التويجري", "منتظم"], ["21", "471105147", "سليمان بن عبدالله بن سليمان العطيوي", "منتظم"], ["22", "472113211", "زياد بن عبدالله بن احمد الزعيبي", "منتظم"]]
  };
  REAL_FILE.rows.slice(0, 19).forEach((r, i) => {
    const st = {
      id: 500 + i,
      uid: r[1],
      ar: r[2],
      en: ''
    };
    S.students.push(st);
    S.enrollments.push({
      id: eid++,
      student: st.id,
      section: 19,
      status: 'active',
      enrolled: '2026-09-01',
      acad: r[3],
      seq: +r[0]
    });
  });
  S.sections.forEach(x => {
    x.degree = x.degree || 'البكالوريوس';
    x.activity = x.activity || 'محاضرة';
  });
  const SEC_PROG = {
    11: 1,
    12: 2,
    13: 3,
    14: 2,
    15: 5,
    16: 4,
    17: 8,
    18: 1,
    19: 5
  };
  const COURSE_PROG = {
    1: [2, 3, 5],
    2: [1, 4],
    3: [5, 6, 9],
    4: [2, 4],
    5: [1, 2, 3, 4, 5, 6, 7, 8, 9],
    6: [5, 6]
  };
  S.courses.forEach(c => {
    c.programs = c.programs || COURSE_PROG[c.id] || [];
  });
  S.sections.forEach(x => {
    x.program = x.program || SEC_PROG[x.id] || 1;
  });
  const TPL = {
    1: ['تسجيل الطالب', 'التوثيق', 'الانتهاء'],
    2: ['التسجيل في المنصة', 'اجتياز الاختبار', 'رفع الشهادة'],
    3: ['حضور الورشة', 'الانتهاء']
  };
  S.assignments = [];
  let asid = 1;
  S.sections.filter(x => x.term === 1).forEach(x => {
    S.assignments.push({
      id: asid++,
      section: x.id,
      ini: 1,
      stages: TPL[1].slice()
    });
    if (x.id % 2 === 1 && x.id !== 19) S.assignments.push({
      id: asid++,
      section: x.id,
      ini: 2,
      stages: TPL[2].slice()
    });
  });
  S.marks = {};
  S.enrollments.find(e => e.section === 11).status = 'withdrawn';
  Object.assign(S.enrollments.find(e => e.section === 11), {
    withdrawn: '2026-09-15',
    reason: 'انسحاب من المقرر عبر عمادة القبول'
  });
  const seedAudit = [['2026-09-26T07:55:12', 1, 'auth.signed_in', 'user', 1, null, null], ['2026-09-25T14:02:40', 2, 'access.denied', 'section', 13, null, {
    reason: 'out_of_scope'
  }], ['2026-09-25T11:20:03', 1, 'import.completed', 'import_job', 1, null, {
    file: 'roster_1448_first.xlsx',
    processed: 4210,
    imported: 4102,
    duplicates: 64,
    errors: 44
  }], ['2026-09-15T09:31:00', 3, 'enrollment.withdrawn', 'enrollment', 1, {
    status: 'active'
  }, {
    status: 'withdrawn',
    reason: 'انسحاب من المقرر عبر عمادة القبول'
  }], ['2026-09-10T10:00:00', 1, 'section.access_granted', 'section', 15, null, {
    user: 'د. فيصل العتيبي'
  }], ['2026-09-08T08:12:45', 1, 'section.instructor_changed', 'section', 11, {
    instructor: 'أ. سلمان الرشيدي'
  }, {
    instructor: 'د. فيصل العتيبي'
  }], ['2026-09-05T13:40:00', 1, 'user.deactivated', 'user', 6, {
    is_active: true
  }, {
    is_active: false
  }], ['2026-09-01T08:00:00', 1, 'user.created', 'user', 3, null, {
    name_ar: 'د. فيصل العتيبي',
    email: 'f.alotaibi@mu.edu.sa',
    role: 'INSTRUCTOR'
  }], ['2026-08-28T10:15:00', 1, 'term.current_set', 'term', 1, {
    is_current: false
  }, {
    is_current: true
  }], ['2026-08-27T09:00:00', 1, 'user.role_changed', 'user', 2, {
    branches: ['الزلفي — طلاب']
  }, {
    branches: ['الزلفي — طالبات']
  }]];
  S.audit = seedAudit.map((a, i) => ({
    id: 1000 - i,
    at: a[0],
    actor: a[1],
    action: a[2],
    type: a[3],
    eid: a[4],
    old: a[5],
    neu: a[6],
    ip: ['10.12.4.21', '10.12.8.77', '10.12.4.21'][i % 3],
    ua: ['Chrome 128 / Windows', 'Edge 128 / Windows', 'Safari 17 / macOS'][i % 3]
  }));
  S.imports = [{
    id: 1,
    mode: 'central',
    term: 1,
    section: null,
    file: 'roster_1448_first.xlsx',
    hash: 'a1f3',
    by: 1,
    at: '2026-09-25T11:12:00',
    status: 'completed',
    total: 4210,
    processed: 4210,
    imported: 4102,
    duplicates: 64,
    errors: 44
  }, {
    id: 2,
    mode: 'section',
    term: 1,
    section: 11,
    file: 'late_registrations_214.csv',
    hash: 'b77c',
    by: 3,
    at: '2026-09-20T15:02:00',
    status: 'completed',
    total: 3,
    processed: 3,
    imported: 2,
    duplicates: 1,
    errors: 0
  }, {
    id: 3,
    mode: 'central',
    term: 1,
    section: null,
    file: 'roster_update_week3.xlsx',
    hash: 'c09e',
    by: 1,
    at: '2026-09-19T10:00:00',
    status: 'failed',
    total: 2600,
    processed: 1500,
    imported: 1480,
    duplicates: 12,
    errors: 8
  }];
  let subs = [];
  let aid = 2000;
  const store = {
    initiatives: [{
      id: 1,
      ar: 'مهارات المستقبل',
      en: 'Future Skills',
      term: 1,
      start: '2026-09-01',
      end: '2026-12-01',
      status: 'active',
      owner: 'عمادة التطوير الأكاديمي',
      desc: 'تنمية مهارات التفكير النقدي والعمل الجماعي عبر أنشطة صفية وتوثيقها.'
    }, {
      id: 2,
      ar: 'الشهادات الاحترافية',
      en: 'Professional Certificates',
      term: 1,
      start: '2026-09-10',
      end: '2026-11-15',
      status: 'active',
      owner: 'وكالة الكلية للشؤون التعليمية',
      desc: 'تهيئة الطلبة لاجتياز شهادات مهنية معتمدة.'
    }, {
      id: 3,
      ar: 'الوعي السيبراني',
      en: 'Cyber Awareness',
      term: 1,
      start: '2026-10-01',
      end: '2026-10-30',
      status: 'draft',
      owner: 'وحدة الأمن السيبراني',
      desc: 'ورش توعوية قصيرة حول حماية البيانات.'
    }],
    TPL,
    REAL_FILE,
    iniName(a, lang) {
      const i = store.initiatives.find(x => x.id === (a.ini || a.id));
      return i ? lang === 'en' ? i.en : i.ar : '—';
    },
    marks(a, studentId) {
      const k = a.id + '|' + studentId;
      if (!S.marks[k]) {
        const h = (studentId * 7 + a.id * 3) % 10;
        const n = a.stages.length;
        const done = h < 4 ? n : h < 6 ? Math.max(n - 1, 0) : h < 8 ? Math.min(1, n) : 0;
        S.marks[k] = a.stages.map((_, i) => i < done);
      }
      if (S.marks[k].length !== a.stages.length) S.marks[k] = a.stages.map((_, i) => !!S.marks[k][i]);
      return S.marks[k];
    },
    setMark(a, studentId, i, on) {
      const m = store.marks(a, studentId).slice();
      if (on) {
        for (let j = 0; j <= i; j++) m[j] = true;
      } else {
        for (let j = i; j < m.length; j++) m[j] = false;
      }
      S.marks[a.id + '|' + studentId] = m;
      return m;
    },
    sectionInitiatives(secId) {
      const sec = S.sections.find(x => x.id === secId);
      if (!sec || sec.term !== 1) return [];
      return secId % 2 === 1 ? [1, 2] : [1];
    },
    progress(studentId, iniId) {
      const i = (studentId * 7 + iniId * 3) % 10;
      const reg = i < 8;
      const doc = reg && i < 6;
      const done = doc && i < 4;
      return {
        registered: reg,
        documented: doc,
        completed: done
      };
    },
    S,
    now,
    subscribe(f) {
      subs.push(f);
      return () => {
        subs = subs.filter(x => x !== f);
      };
    },
    emit() {
      subs.forEach(f => f());
    },
    audit(actor, action, type, eid, old, neu) {
      S.audit.unshift({
        id: aid++,
        at: now().slice(0, 19),
        actor,
        action,
        type,
        eid,
        old,
        neu,
        ip: '10.12.4.21',
        ua: 'Chrome 128 / Windows'
      });
    },
    branchLabel(id, lang) {
      const b = S.branches.find(x => x.id === id);
      if (!b) return '—';
      return lang === 'en' ? b.en + ' — ' + (b.gender === 'male' ? 'Male' : 'Female') : b.ar + ' — ' + (b.gender === 'male' ? 'طلاب' : 'طالبات');
    },
    user(id) {
      return S.users.find(u => u.id === id);
    },
    uname(id, lang) {
      const u = S.users.find(x => x.id === id);
      return u ? lang === 'en' ? u.en : u.ar : '—';
    },
    course(id) {
      return S.courses.find(c => c.id === id);
    },
    program(id) {
      return S.programs.find(p => p.id === id);
    },
    programName(id, lang) {
      const p = S.programs.find(x => x.id === id);
      return p ? lang === 'en' ? p.en : p.ar : '—';
    },
    term(id) {
      return S.terms.find(t => t.id === id);
    },
    currentTerm() {
      return S.terms.find(t => t.current);
    },
    student(id) {
      return S.students.find(s => s.id === id);
    },
    sectionScope(u) {
      if (!u) return [];
      if (u.role === 'SUPER_ADMIN') return S.sections;
      if (u.role === 'BRANCH_COORDINATOR') return S.sections.filter(s => u.branches.includes(s.branch));
      const shared = S.grants.filter(g => g.user === u.id).map(g => g.section);
      return S.sections.filter(s => s.instructor === u.id || shared.includes(s.id));
    },
    isShared(u, sec) {
      return u && u.role === 'INSTRUCTOR' && sec.instructor !== u.id && S.grants.some(g => g.user === u.id && g.section === sec.id);
    },
    canSee(u, sec) {
      return store.sectionScope(u).some(s => s.id === sec.id);
    },
    studentScope(u) {
      const ids = new Set(store.sectionScope(u).map(s => s.id));
      const st = new Set(S.enrollments.filter(e => ids.has(e.section)).map(e => e.student));
      return u.role === 'SUPER_ADMIN' ? S.students : S.students.filter(s => st.has(s.id));
    },
    PERMS: {
      SUPER_ADMIN: ['*'],
      BRANCH_COORDINATOR: ['term.view', 'branch.view', 'course.view', 'section.view', 'student.view', 'import.view', 'audit.view'],
      INSTRUCTOR: ['section.view', 'student.view', 'student.create', 'enrollment.create', 'enrollment.withdraw', 'import.section.run', 'import.view']
    },
    can(u, p) {
      const l = store.PERMS[u.role] || [];
      return l.includes('*') || l.includes(p);
    }
  };
  window.TRACK_STORE = store;
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/store.js", error: String((e && e.message) || e) }); }

// ui_kits/web-app/xlsx.js
try { (() => {
(function () {
  async function unzip(buf) {
    const dv = new DataView(buf.buffer, buf.byteOffset, buf.byteLength);
    let eocd = -1;
    for (let i = buf.length - 22; i >= 0; i--) {
      if (dv.getUint32(i, true) === 0x06054b50) {
        eocd = i;
        break;
      }
    }
    if (eocd < 0) throw new Error('not-zip');
    const cd = dv.getUint32(eocd + 16, true),
      n = dv.getUint16(eocd + 10, true);
    let p = cd;
    const E = {};
    for (let i = 0; i < n; i++) {
      const comp = dv.getUint16(p + 10, true),
        cs = dv.getUint32(p + 20, true),
        nl = dv.getUint16(p + 28, true),
        el = dv.getUint16(p + 30, true),
        cl = dv.getUint16(p + 32, true),
        lo = dv.getUint32(p + 42, true);
      E[new TextDecoder().decode(buf.slice(p + 46, p + 46 + nl))] = {
        comp,
        cs,
        lo
      };
      p += 46 + nl + el + cl;
    }
    return async name => {
      const e = E[name];
      if (!e) return null;
      const a = dv.getUint16(e.lo + 26, true),
        b = dv.getUint16(e.lo + 28, true);
      const d = buf.slice(e.lo + 30 + a + b, e.lo + 30 + a + b + e.cs);
      if (e.comp === 0) return new TextDecoder().decode(d);
      return await new Response(new Blob([d]).stream().pipeThrough(new DecompressionStream('deflate-raw'))).text();
    };
  }
  const unesc = s => s.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'").replace(/&amp;/g, '&');
  const colIdx = c => {
    let n = 0;
    for (const ch of c) n = n * 26 + (ch.charCodeAt(0) - 64);
    return n - 1;
  };
  async function readXlsx(buf) {
    const get = await unzip(buf);
    const ss = await get('xl/sharedStrings.xml');
    const strs = ss ? [...ss.matchAll(/<si>([\s\S]*?)<\/si>/g)].map(m => unesc([...m[1].matchAll(/<t[^>]*>([\s\S]*?)<\/t>/g)].map(x => x[1]).join(''))) : [];
    const x = await get('xl/worksheets/sheet1.xml');
    if (!x) throw new Error('no-sheet');
    return [...x.matchAll(/<row[^>]*>([\s\S]*?)<\/row>/g)].map(r => {
      const out = [];
      for (const c of r[1].matchAll(/<c r="([A-Z]+)\d+"([^>]*?)(?:\/>|>([\s\S]*?)<\/c>)/g)) {
        const t = /t="(\w+)"/.exec(c[2]);
        const v = /<v>([\s\S]*?)<\/v>/.exec(c[3] || '');
        const is = /<t[^>]*>([\s\S]*?)<\/t>/.exec(c[3] || '');
        let val = v ? v[1] : is ? is[1] : '';
        if (t && t[1] === 's') val = strs[+val];
        out[colIdx(c[1])] = unesc(String(val)).replace(/\s+/g, ' ').trim();
      }
      return Array.from(out, v => v ?? '');
    });
  }
  function readCsv(text) {
    return text.replace(/^\uFEFF/, '').split(/\r?\n/).filter(l => l.trim()).map(l => l.split(/,(?=(?:[^"]*"[^"]*")*[^"]*$)/).map(c => c.replace(/^"|"$/g, '').trim()));
  }
  const META = {
    'المقر': 'campus',
    'الدرجة': 'degree',
    'اسم المقرر': 'course',
    'النشاط': 'activity',
    'الشعبة': 'section'
  };
  function shape(grid) {
    const meta = {};
    let h = -1;
    for (let i = 0; i < grid.length; i++) {
      const r = grid[i];
      if (r.some(c => /رقم الطالب|University ID|الرقم الجامعي/i.test(c))) {
        h = i;
        break;
      }
      if (r[0] && META[r[0]] && r.filter(Boolean).length <= 2) meta[META[r[0]]] = r[1] || '';
    }
    if (h < 0) h = 0;
    const headers = grid[h].map(String);
    const rows = grid.slice(h + 1).filter(r => r.some(Boolean));
    return {
      meta,
      headers,
      rows
    };
  }
  window.parseRosterFile = async function (file) {
    const ext = (file.name.split('.').pop() || '').toLowerCase();
    if (!['xlsx', 'csv'].includes(ext)) throw new Error('type');
    if (file.size > 20 * 1024 * 1024) throw new Error('size');
    const buf = new Uint8Array(await file.arrayBuffer());
    if (ext === 'xlsx' && !(buf[0] === 0x50 && buf[1] === 0x4b)) throw new Error('type');
    const grid = ext === 'xlsx' ? await readXlsx(buf) : readCsv(new TextDecoder().decode(buf));
    const s = shape(grid);
    if (s.rows.length > 50000) throw new Error('rows');
    return {
      name: file.name,
      size: file.size < 1024 ? file.size + ' B' : Math.round(file.size / 1024) + ' KB',
      ...s
    };
  };
})();
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/web-app/xlsx.js", error: String((e && e.message) || e) }); }

__ds_ns.Button = __ds_scope.Button;

__ds_ns.Icon = __ds_scope.Icon;

__ds_ns.IconButton = __ds_scope.IconButton;

__ds_ns.Logo = __ds_scope.Logo;

__ds_ns.BarChart = __ds_scope.BarChart;

__ds_ns.Card = __ds_scope.Card;

__ds_ns.DataTable = __ds_scope.DataTable;

__ds_ns.DonutChart = __ds_scope.DonutChart;

__ds_ns.LineChart = __ds_scope.LineChart;

__ds_ns.StatCard = __ds_scope.StatCard;

__ds_ns.Alert = __ds_scope.Alert;

__ds_ns.Dialog = __ds_scope.Dialog;

__ds_ns.EmptyState = __ds_scope.EmptyState;

__ds_ns.ProgressBar = __ds_scope.ProgressBar;

__ds_ns.Skeleton = __ds_scope.Skeleton;

__ds_ns.StatusBadge = __ds_scope.StatusBadge;

__ds_ns.Toast = __ds_scope.Toast;

__ds_ns.Tooltip = __ds_scope.Tooltip;

__ds_ns.Checkbox = __ds_scope.Checkbox;

__ds_ns.Input = __ds_scope.Input;

__ds_ns.Select = __ds_scope.Select;

__ds_ns.Switch = __ds_scope.Switch;

__ds_ns.Breadcrumbs = __ds_scope.Breadcrumbs;

__ds_ns.Sidebar = __ds_scope.Sidebar;

__ds_ns.Stepper = __ds_scope.Stepper;

__ds_ns.Tabs = __ds_scope.Tabs;

__ds_ns.Topbar = __ds_scope.Topbar;

})();

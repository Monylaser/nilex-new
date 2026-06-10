const {
  Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType,
  Table, TableRow, TableCell, WidthType, BorderStyle, ShadingType,
  PageBreak, HorizontalPositionAlign, VerticalPositionAlign,
  TableBorders, UnderlineType, convertInchesToTwip, PageOrientation,
  Header, Footer, PageNumber, NumberFormat
} = require("docx");
const fs = require("fs");
const path = require("path");

// ─── Helpers ─────────────────────────────────────────────────────────────────

const BRAND   = "2C6DB5"; // corporate blue
const ACCENT  = "E8502A"; // orange-red accent
const DARK    = "1A1A2E"; // near-black
const LIGHT   = "F0F4FA"; // light blue-grey fill
const BORDER  = "BDC7D8";
const CODE_BG = "F3F4F6";
const WHITE   = "FFFFFF";

function h1(text) {
  return new Paragraph({
    text,
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 400, after: 160 },
    shading: { type: ShadingType.CLEAR, fill: BRAND },
    run: { color: WHITE, size: 36, bold: true, font: "Calibri" }
  });
}

function h2(text) {
  return new Paragraph({
    children: [
      new TextRun({ text, bold: true, size: 28, color: BRAND, font: "Calibri" })
    ],
    spacing: { before: 320, after: 120 },
    border: { bottom: { color: BRAND, size: 6, space: 1, style: BorderStyle.SINGLE } }
  });
}

function h3(text) {
  return new Paragraph({
    children: [
      new TextRun({ text, bold: true, size: 24, color: DARK, font: "Calibri" })
    ],
    spacing: { before: 240, after: 80 }
  });
}

function p(text, opts = {}) {
  return new Paragraph({
    children: [new TextRun({ text, size: 22, font: "Calibri", ...opts })],
    spacing: { before: 60, after: 60 },
    alignment: AlignmentType.JUSTIFIED
  });
}

function bullet(text, level = 0) {
  return new Paragraph({
    children: [new TextRun({ text, size: 21, font: "Calibri" })],
    bullet: { level },
    spacing: { before: 40, after: 40 }
  });
}

function code(text) {
  return new Paragraph({
    children: [new TextRun({ text, font: "Courier New", size: 18, color: "1F2937" })],
    shading: { type: ShadingType.CLEAR, fill: CODE_BG },
    spacing: { before: 40, after: 40 },
    indent: { left: 360 }
  });
}

function pageBreak() {
  return new Paragraph({ children: [new PageBreak()] });
}

function spacer() {
  return new Paragraph({ text: "", spacing: { before: 60, after: 60 } });
}

function noteBox(text) {
  return new Paragraph({
    children: [new TextRun({ text: "ℹ  " + text, size: 20, font: "Calibri", color: "1E40AF" })],
    shading: { type: ShadingType.CLEAR, fill: "DBEAFE" },
    spacing: { before: 80, after: 80 },
    indent: { left: 180, right: 180 },
    border: {
      top: { color: "93C5FD", size: 4, style: BorderStyle.SINGLE },
      bottom: { color: "93C5FD", size: 4, style: BorderStyle.SINGLE },
      left: { color: "2563EB", size: 12, style: BorderStyle.SINGLE },
      right: { color: "93C5FD", size: 4, style: BorderStyle.SINGLE }
    }
  });
}

function warnBox(text) {
  return new Paragraph({
    children: [new TextRun({ text: "⚠  " + text, size: 20, font: "Calibri", color: "92400E" })],
    shading: { type: ShadingType.CLEAR, fill: "FEF3C7" },
    spacing: { before: 80, after: 80 },
    indent: { left: 180, right: 180 },
    border: {
      top: { color: "FCD34D", size: 4, style: BorderStyle.SINGLE },
      bottom: { color: "FCD34D", size: 4, style: BorderStyle.SINGLE },
      left: { color: "D97706", size: 12, style: BorderStyle.SINGLE },
      right: { color: "FCD34D", size: 4, style: BorderStyle.SINGLE }
    }
  });
}

function tip(text) {
  return new Paragraph({
    children: [new TextRun({ text: "✓  " + text, size: 20, font: "Calibri", color: "065F46" })],
    shading: { type: ShadingType.CLEAR, fill: "D1FAE5" },
    spacing: { before: 80, after: 80 },
    indent: { left: 180, right: 180 },
    border: {
      top: { color: "6EE7B7", size: 4, style: BorderStyle.SINGLE },
      bottom: { color: "6EE7B7", size: 4, style: BorderStyle.SINGLE },
      left: { color: "059669", size: 12, style: BorderStyle.SINGLE },
      right: { color: "6EE7B7", size: 4, style: BorderStyle.SINGLE }
    }
  });
}

function makeTable(headers, rows, colWidths) {
  const headerRow = new TableRow({
    tableHeader: true,
    children: headers.map((h, i) =>
      new TableCell({
        shading: { type: ShadingType.CLEAR, fill: BRAND },
        width: colWidths ? { size: colWidths[i], type: WidthType.PERCENTAGE } : undefined,
        children: [new Paragraph({
          children: [new TextRun({ text: h, bold: true, color: WHITE, size: 20, font: "Calibri" })],
          alignment: AlignmentType.CENTER
        })]
      })
    )
  });

  const dataRows = rows.map((row, ri) =>
    new TableRow({
      children: row.map((cell, ci) =>
        new TableCell({
          shading: { type: ShadingType.CLEAR, fill: ri % 2 === 0 ? WHITE : LIGHT },
          width: colWidths ? { size: colWidths[ci], type: WidthType.PERCENTAGE } : undefined,
          children: [new Paragraph({
            children: [new TextRun({ text: String(cell), size: 20, font: "Calibri" })],
            spacing: { before: 40, after: 40 },
            indent: { left: 80 }
          })]
        })
      )
    })
  );

  return new Table({
    width: { size: 100, type: WidthType.PERCENTAGE },
    borders: {
      top: { style: BorderStyle.SINGLE, size: 4, color: BORDER },
      bottom: { style: BorderStyle.SINGLE, size: 4, color: BORDER },
      left: { style: BorderStyle.SINGLE, size: 4, color: BORDER },
      right: { style: BorderStyle.SINGLE, size: 4, color: BORDER },
      insideHorizontal: { style: BorderStyle.SINGLE, size: 2, color: BORDER },
      insideVertical: { style: BorderStyle.SINGLE, size: 2, color: BORDER }
    },
    rows: [headerRow, ...dataRows]
  });
}

// ─── Cover Page ───────────────────────────────────────────────────────────────

const coverPage = [
  spacer(), spacer(), spacer(),
  new Paragraph({
    children: [new TextRun({ text: "NILEX PLATFORM", bold: true, size: 72, color: BRAND, font: "Calibri" })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 120 }
  }),
  new Paragraph({
    children: [new TextRun({ text: "Technical Architecture Document", size: 40, color: DARK, font: "Calibri" })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 240 }
  }),
  new Paragraph({
    children: [new TextRun({ text: "─────────────────────────────────────────────", color: BRAND, size: 24, font: "Calibri" })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 240 }
  }),
  new Paragraph({
    children: [new TextRun({ text: "Egyptian Classified-Ads Marketplace", size: 28, color: "555555", font: "Calibri", italics: true })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 80 }
  }),
  new Paragraph({
    children: [new TextRun({ text: "Laravel 13  ·  Filament 5  ·  Meilisearch  ·  Redis  ·  Gemini AI", size: 22, color: "777777", font: "Calibri" })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 480 }
  }),
  spacer(), spacer(), spacer(),
  new Paragraph({
    children: [new TextRun({ text: "Version 1.0  ·  June 2026  ·  CONFIDENTIAL", size: 20, color: "999999", font: "Calibri" })],
    alignment: AlignmentType.CENTER
  }),
  pageBreak()
];

// ─── Table of Contents ────────────────────────────────────────────────────────

const tocPage = [
  h1("Table of Contents"),
  ...[ 
    "1.   Project Overview",
    "2.   Technology Stack",
    "3.   Laravel Architecture Structure",
    "4.   Folder Structure",
    "5.   Service Providers",
    "6.   Middleware System",
    "7.   Authentication Flow",
    "8.   OTP Verification Flow",
    "9.   Device Fingerprinting Flow",
    "10.  Database Architecture",
    "11.  Queue System",
    "12.  Cache System",
    "13.  Search System — Scout + Meilisearch",
    "14.  Real-Time System — Laravel Reverb",
    "15.  AI Integration Architecture",
    "16.  Payment Architecture",
    "17.  Filament Admin Architecture",
    "18.  Livewire Architecture",
    "19.  Security Architecture",
    "20.  SEO Architecture",
    "21.  Activity Logs System",
    "22.  Notification System",
    "23.  Fraud Detection System",
    "24.  Media Upload System",
    "25.  Localization Architecture",
    "26.  Performance Optimizations",
    "27.  Deployment Recommendations",
    "28.  Strengths, Risks & Future Recommendations"
  ].map(line => new Paragraph({
    children: [new TextRun({ text: line, size: 22, font: "Calibri", color: DARK })],
    spacing: { before: 40, after: 40 },
    indent: { left: 360 }
  })),
  pageBreak()
];

// ─── Section 1 ────────────────────────────────────────────────────────────────

const sec1 = [
  h1("1. Project Overview"),
  p("Nilex is a full-stack, Arabic-first classified-ads marketplace designed for the Egyptian market. It provides a public browsing and listing experience for end-users and a comprehensive back-office control panel for platform administrators. The system encompasses the full lifecycle of a classified advertisement: creation, moderation, featuring, expiry, and analytics tracking."),
  spacer(),
  h2("1.1 Core Business Features"),
  bullet("Classified ad listings with category hierarchy, geo-location, and rich media"),
  bullet("Points-based monetization (featuring ads, purchasing point plans)"),
  bullet("Bilingual interface (Arabic default, English secondary)"),
  bullet("Role-based admin panel with moderation workflows"),
  bullet("AI-assisted listing creation via Google Gemini"),
  bullet("Campaign management and referral link tracking"),
  bullet("Real-time messaging between users"),
  bullet("OTP and social OAuth authentication"),
  bullet("SEO templates for category and governorate pages"),
  bullet("Legal content management (Privacy Policy, Terms, etc.)"),
  spacer(),
  h2("1.2 Project Scope"),
  makeTable(
    ["Dimension", "Details"],
    [
      ["Platform Type", "Web application (SPA-lite with Livewire)"],
      ["Primary Market", "Egypt"],
      ["Default Language", "Arabic (RTL)"],
      ["Secondary Language", "English"],
      ["Target Users", "End users (buyers/sellers) + Admin staff"],
      ["Authentication", "Email/Password, OTP, Google, Facebook, TikTok, Instagram"],
      ["Monetization", "Points economy + plan purchases"],
      ["Moderation", "Manual + automated fraud detection"]
    ],
    [35, 65]
  ),
  pageBreak()
];

// ─── Section 2 ────────────────────────────────────────────────────────────────

const sec2 = [
  h1("2. Technology Stack"),
  h2("2.1 Full Stack Overview"),
  makeTable(
    ["Layer", "Technology", "Version", "Purpose"],
    [
      ["Runtime",       "PHP",                    "8.3",     "Application runtime"],
      ["Framework",     "Laravel",                "13.x",    "Core MVC framework"],
      ["Admin Panel",   "Filament",               "5.4.x",   "Back-office CRUD panel"],
      ["RBAC",          "Filament Shield",        "4.2.x",   "Role & permission management"],
      ["Translations",  "Filament Translatable",  "3.0",     "AR/EN content translation in Filament"],
      ["Auth",          "Laravel Breeze",         "2.4.x",   "Auth scaffolding"],
      ["Auth",          "Laravel Sanctum",        "4.3.x",   "API token authentication"],
      ["Auth",          "Laravel Socialite",      "5.27.x",  "OAuth (Google, Facebook, TikTok, Instagram)"],
      ["Search",        "Laravel Scout",          "11.1.x",  "Search abstraction layer"],
      ["Search",        "Meilisearch",            "1.x",     "Full-text + geo search engine"],
      ["Media",         "Spatie Media Library",   "11.21.x", "File uploads, conversions, watermarks"],
      ["Media",         "Spatie Image Optimizer", "1.8.x",   "Automatic image compression"],
      ["Localisation",  "Spatie Translatable",    "6.14.x",  "Model-level AR/EN content"],
      ["Activity Log",  "Spatie Activity Log",    "4.12.x",  "Audit trail for all model changes"],
      ["Frontend",      "Blade + Livewire",       "3.x",     "Server-side UI components"],
      ["Frontend",      "Alpine.js",              "3.15.x",  "Lightweight client-side reactivity"],
      ["CSS",           "Tailwind CSS",           "3/4.x",   "Utility-first styling"],
      ["Build",         "Vite",                   "8.x",     "Asset bundling and HMR"],
      ["Animations",    "GSAP",                   "3.15.x",  "Advanced CSS/JS animations"],
      ["3D",            "Three.js",               "0.184.x", "3D homepage effects"],
      ["Cache/Queue",   "Redis",                  "7.x",     "Cache, session, and queue backend"],
      ["Database",      "MySQL",                  "8.4",     "Primary relational datastore"],
      ["Monitoring",    "Laravel Telescope",      "5.20.x",  "Debug & request monitoring (dev)"],
      ["Testing",       "Pest",                   "4.x",     "Unit and feature tests"],
      ["E2E Testing",   "Cypress",                "14.x",    "End-to-end browser tests"],
      ["Container",     "Docker + Compose",       "Latest",  "Local development environment"],
      ["CI/CD",         "GitHub Actions",         "—",       "Automated test & deploy pipeline"]
    ],
    [20, 25, 12, 43]
  ),
  spacer(),
  h2("2.2 Architecture Pattern"),
  p("The application follows a Domain-Oriented Modular Monolith pattern layered over classic Laravel MVC. The auth domain is isolated under app/Auth/ with its own service provider, services, jobs, and middleware. The admin domain is isolated under app/Filament/Admin/. The core domain (models, observers, services) lives in the standard Laravel locations. This design provides modularity without the operational complexity of microservices."),
  pageBreak()
];

// ─── Section 3 ────────────────────────────────────────────────────────────────

const sec3 = [
  h1("3. Laravel Architecture Structure"),
  h2("3.1 Architectural Layers"),
  p("The application is structured in four distinct layers:"),
  spacer(),
  h3("Layer 1 — Presentation"),
  bullet("Blade templates for server-rendered HTML"),
  bullet("Livewire components for reactive UI (UserDashboard, ListingGrid, SmartAdCreator)"),
  bullet("Alpine.js for lightweight client interaction"),
  bullet("Filament panels for admin UI"),
  spacer(),
  h3("Layer 2 — HTTP / Application"),
  bullet("Controllers handle HTTP requests and delegate to services"),
  bullet("Form Requests (LoginRequest, ProfileUpdateRequest) for validation"),
  bullet("Middleware pipeline enforces auth, OTP, ban, locale, and campaign tracking"),
  bullet("Route model binding with policy-based authorization"),
  spacer(),
  h3("Layer 3 — Domain / Business Logic"),
  bullet("Eloquent Models with relationships, scopes, and business methods"),
  bullet("Service classes: PointService, GeminiService, OtpService, DeviceFingerprintService, DeviceLimitService"),
  bullet("Observers: ListingObserver (fraud detection), PointTransactionObserver (balance ledger)"),
  bullet("Value Objects: OtpCode (encapsulates generation, hashing, verification)"),
  bullet("Events & Listeners for decoupled side-effects (auth logging, notifications)"),
  spacer(),
  h3("Layer 4 — Infrastructure"),
  bullet("Redis for cache, sessions, and queued jobs"),
  bullet("MySQL 8.4 as the primary data store (50 migrations)"),
  bullet("Meilisearch for full-text and geo search"),
  bullet("Laravel Queue workers on named channels (sms-high, email-high, auth-critical, notifications, default)"),
  bullet("Spatie Media Library + local/S3 disk for file storage"),
  bullet("Laravel Telescope for observability (dev/staging)"),
  spacer(),
  h2("3.2 Request Lifecycle"),
  makeTable(
    ["Step", "Component", "Responsibility"],
    [
      ["1", "nginx",                      "Terminates HTTPS, proxies to PHP-FPM"],
      ["2", "bootstrap/app.php",          "Registers middleware stack, routes, exception handler"],
      ["3", "Global Middleware",          "PreventStorageCache, SecurityHeaders"],
      ["4", "Web Group Middleware",       "EnsureUserIsNotBanned, TrackCampaign, SetLocale, CSRF"],
      ["5", "Route Matching",             "Route::match() selects controller + method"],
      ["6", "Route Middleware",           "auth, otp.verified, throttle — per-route checks"],
      ["7", "Form Request Validation",    "Validates & authorizes the incoming request"],
      ["8", "Controller",                 "Delegates to Service / Model"],
      ["9", "Service / Model",            "Business logic, DB query, queue dispatch"],
      ["10","Response",                   "Blade view rendered or JSON returned"]
    ],
    [8, 30, 62]
  ),
  pageBreak()
];

// ─── Section 4 ────────────────────────────────────────────────────────────────

const sec4 = [
  h1("4. Folder Structure"),
  h2("4.1 Top-Level Structure"),
  code("nilex_platform/"),
  code("├── app/                   # Application code"),
  code("│   ├── Auth/              # Auth domain module"),
  code("│   ├── Console/           # Artisan commands"),
  code("│   ├── Events/            # Domain events"),
  code("│   ├── Exceptions/        # Custom exceptions"),
  code("│   ├── Filament/          # Admin panel resources"),
  code("│   ├── Http/              # Controllers, Middleware, Requests"),
  code("│   ├── Jobs/              # Async jobs"),
  code("│   ├── Listeners/         # Event listeners"),
  code("│   ├── Livewire/          # Livewire components"),
  code("│   ├── Models/            # Eloquent models"),
  code("│   ├── Notifications/     # Notification classes"),
  code("│   ├── Observers/         # Model observers"),
  code("│   ├── Policies/          # Authorization policies"),
  code("│   ├── Providers/         # Service providers"),
  code("│   └── Services/          # Domain services"),
  code("├── bootstrap/             # App bootstrap & providers"),
  code("├── config/                # 16 configuration files"),
  code("├── database/              # Factories, migrations, seeders"),
  code("├── docs/                  # Architecture docs"),
  code("├── docker/                # nginx, PHP-FPM Dockerfiles"),
  code("├── lang/                  # AR/EN translation strings"),
  code("├── public/                # Web root, compiled assets, SEO images"),
  code("├── resources/             # Blade views, CSS, JS sources"),
  code("├── routes/                # web.php, auth.php, console.php"),
  code("├── storage/               # Logs, media, compiled views"),
  code("├── tests/                 # Pest feature & unit tests"),
  code("└── cypress/               # E2E test suites"),
  spacer(),
  h2("4.2 Auth Domain Module (app/Auth/)"),
  p("The auth domain is a self-contained module — a deliberate architectural choice to isolate security-sensitive code."),
  code("app/Auth/"),
  code("├── Jobs/"),
  code("│   ├── SendOtpEmailJob.php     # Queued: email-high channel"),
  code("│   └── SendOtpSmsJob.php       # Queued: sms-high channel"),
  code("├── Middleware/"),
  code("│   ├── EnsureUserIsNotBanned.php"),
  code("│   └── SecurityHeaders.php"),
  code("├── Providers/"),
  code("│   └── AuthSecurityServiceProvider.php  # Registers services + rate limiters"),
  code("├── Services/"),
  code("│   ├── DeviceFingerprintService.php"),
  code("│   ├── DeviceLimitService.php"),
  code("│   └── OtpService.php"),
  code("└── ValueObjects/"),
  code("    └── OtpCode.php"),
  spacer(),
  h2("4.3 Filament Admin Structure (app/Filament/)"),
  code("app/Filament/Admin/"),
  code("├── Resources/             # 13 CRUD resources"),
  code("│   ├── ActivityLogs/"),
  code("│   ├── CampaignLinks/"),
  code("│   ├── Campaigns/"),
  code("│   ├── Categories/"),
  code("│   ├── LegalPages/"),
  code("│   ├── Listings/          # + dynamic field schemas (Car, RealEstate)"),
  code("│   ├── Locations/"),
  code("│   ├── Moderation/"),
  code("│   ├── PointPlans/"),
  code("│   ├── SeoTemplates/"),
  code("│   ├── SiteSettingResource.php"),
  code("│   └── UserResource/"),
  code("└── Widgets/"),
  code("    ├── CategoriesChartWidget.php"),
  code("    ├── GovernoratesChartWidget.php"),
  code("    ├── ListingsChart.php"),
  code("    └── StatsOverviewWidget.php"),
  pageBreak()
];

// ─── Section 5 ────────────────────────────────────────────────────────────────

const sec5 = [
  h1("5. Service Providers"),
  h2("5.1 Registered Providers"),
  makeTable(
    ["Provider", "File", "Responsibilities"],
    [
      ["AppServiceProvider",          "app/Providers/AppServiceProvider.php",                    "Singletons, observers, event listeners, view composers"],
      ["AdminPanelProvider",          "app/Providers/Filament/AdminPanelProvider.php",            "Filament panel config, plugins, navigation, Livewire aliases"],
      ["AuthSecurityServiceProvider", "app/Auth/Providers/AuthSecurityServiceProvider.php",       "OTP/Device singletons, rate limiters"],
      ["TelescopeServiceProvider",    "app/Providers/TelescopeServiceProvider.php",               "Telescope filters, gates, sensitive-header hiding"]
    ],
    [28, 42, 30]
  ),
  spacer(),
  h2("5.2 AppServiceProvider Details"),
  bullet("Singleton binding: PointService (shared instance across request)"),
  bullet("Observers: PointTransactionObserver → PointTransaction, ListingObserver → Listing"),
  bullet("Auth event bindings: Login → LogSuccessfulLogin, Failed → LogFailedLogin, Logout → LogSuccessfulLogout, roleAttached → LogRoleAssigned, roleDetached → LogRoleRevoked"),
  bullet("View composer: layouts.app shares top 6 active root categories with all views"),
  spacer(),
  h2("5.3 AdminPanelProvider Details"),
  bullet("Panel ID: admin, Path: /admin, Dark mode enabled, Violet theme"),
  bullet("Auto-discovery: Resources, Pages, Widgets under app/Filament/Admin/"),
  bullet("Plugins: FilamentShieldPlugin (RBAC), FilamentTranslatablePlugin (AR + EN)"),
  bullet("Livewire component alias: smart-ad-creator → SmartAdCreator"),
  bullet("Navigation groups: الإشراف (Moderation), المحتوى (Content), الإدارة (Admin)"),
  spacer(),
  h2("5.4 AuthSecurityServiceProvider Details"),
  bullet("app(OtpService::class) — bound as singleton"),
  bullet("app(DeviceFingerprintService::class) — bound as singleton"),
  bullet("app(DeviceLimitService::class) — bound as singleton"),
  bullet("Rate limiter otp-resend: 3 attempts / 1 minute per user"),
  bullet("Rate limiter otp-verify: 10 attempts / 1 minute per user"),
  bullet("Rate limiter registration: 10 attempts / 1 hour per IP"),
  pageBreak()
];

// ─── Section 6 ────────────────────────────────────────────────────────────────

const sec6 = [
  h1("6. Middleware System"),
  h2("6.1 Middleware Stack"),
  makeTable(
    ["Middleware", "Location", "Applies To", "Function"],
    [
      ["PreventStorageCache",    "app/Http/Middleware/",  "Global",    "Sets no-cache headers for /storage/ paths"],
      ["SecurityHeaders",        "app/Auth/Middleware/",  "Global",    "X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS"],
      ["EnsureUserIsNotBanned",  "app/Auth/Middleware/",  "Web group", "Redirects banned users to logout"],
      ["TrackCampaign",          "app/Http/Middleware/",  "Web group", "Reads ?ref= query param, stores campaign link in session"],
      ["SetLocale",              "app/Http/Middleware/",  "Web group", "Sets App locale from session (ar/en)"],
      ["EnsureOtpIsVerified",    "app/Http/Middleware/",  "Protected", "Redirects unverified users to /verify-otp"],
      ["RedirectIfAuthenticated","app/Http/Middleware/",  "Guest",     "Redirects authenticated users away from guest-only routes"],
      ["auth",                   "Laravel built-in",      "Protected", "Standard authentication check"],
      ["throttle",               "Laravel built-in",      "Per-route", "Rate limiting for OTP, auth, registration routes"]
    ],
    [25, 22, 18, 35]
  ),
  spacer(),
  h2("6.2 Security Headers Implementation"),
  p("The SecurityHeaders middleware adds the following response headers on every request:"),
  makeTable(
    ["Header", "Value", "Purpose"],
    [
      ["X-Frame-Options",          "SAMEORIGIN",                      "Prevents clickjacking via iframe embedding"],
      ["X-Content-Type-Options",   "nosniff",                         "Prevents MIME-type sniffing"],
      ["Referrer-Policy",          "strict-origin-when-cross-origin", "Controls referrer information leakage"],
      ["Strict-Transport-Security","max-age=31536000; includeSubDomains","Forces HTTPS for 1 year (added on HTTPS only)"]
    ],
    [32, 38, 30]
  ),
  spacer(),
  h2("6.3 Middleware Registration in bootstrap/app.php"),
  code("$middleware->web(append: ["),
  code("    EnsureUserIsNotBanned::class,"),
  code("    TrackCampaign::class,"),
  code("    SetLocale::class,"),
  code("]);"),
  code("$middleware->alias(["),
  code("    'otp.verified' => EnsureOtpIsVerified::class,"),
  code("    'not.banned'   => EnsureUserIsNotBanned::class,"),
  code("]);"),
  pageBreak()
];

// ─── Section 7 ────────────────────────────────────────────────────────────────

const sec7 = [
  h1("7. Authentication Flow"),
  h2("7.1 Registration Flow"),
  bullet("1. User submits email or Egyptian phone number (01XXXXXXXXX format)"),
  bullet("2. LoginRequest validates format and uniqueness"),
  bullet("3. DeviceLimitService checks registration limit: max 3 accounts per fingerprint/IP"),
  bullet("4. DeviceFingerprintService generates SHA-256 device hash from: User-Agent, IP, device cookie, client headers"),
  bullet("5. User record created with points=0"),
  bullet("6. PointService credits 100 welcome points"),
  bullet("7. OtpService generates and dispatches OTP (SendOtpEmailJob or SendOtpSmsJob)"),
  bullet("8. If ?ref= campaign link present in session: CampaignLink reward credited to referrer"),
  bullet("9. User is logged in but redirected to /verify-otp (EnsureOtpIsVerified blocks dashboard)"),
  spacer(),
  h2("7.2 Login Flow"),
  bullet("1. User submits credentials via LoginRequest"),
  bullet("2. auth()->attempt() validates against users table"),
  bullet("3. If user is admin/super_admin: redirect to /admin"),
  bullet("4. If OTP not verified: redirect to /verify-otp"),
  bullet("5. Else: redirect to intended or /dashboard"),
  spacer(),
  h2("7.3 Social OAuth Flow"),
  bullet("1. User clicks social provider button → redirect to provider OAuth URL"),
  bullet("2. Provider redirects to /auth/{provider}/callback"),
  bullet("3. SocialiteController::callback() handles response"),
  bullet("4. Try to find existing user by provider_id first, then by email"),
  bullet("5. If new user: create record, mark phone/email verified, credit 100 points"),
  bullet("6. If existing user: update provider token/avatar"),
  bullet("7. Login user and redirect to dashboard (OTP step skipped for social logins)"),
  spacer(),
  makeTable(
    ["Provider", "Scope Requested", "Notes"],
    [
      ["Google",    "email, profile",                     "Full support"],
      ["Facebook",  "email, public_profile",              "Full support"],
      ["TikTok",    "user.info.basic, user.info.profile", "Custom scopes"],
      ["Instagram", "user_profile, user_media",           "Custom scopes"]
    ],
    [20, 45, 35]
  ),
  spacer(),
  h2("7.4 Session & Token Architecture"),
  bullet("Sessions stored in Redis (session driver: redis)"),
  bullet("Laravel Sanctum installed for future API token needs"),
  bullet("CSRF protection active on all stateful web routes"),
  bullet("Auth session regenerated on login to prevent session fixation"),
  pageBreak()
];

// ─── Section 8 ────────────────────────────────────────────────────────────────

const sec8 = [
  h1("8. OTP Verification Flow"),
  h2("8.1 Architecture"),
  p("OTP verification is a mandatory post-registration step for email/password registered users. It is implemented as a standalone security domain in app/Auth/ with its own service, jobs, and value object."),
  spacer(),
  h2("8.2 OTP Configuration"),
  makeTable(
    ["Parameter", "Value", "Config Key"],
    [
      ["Code Length",        "4 digits",                   "OTP_DIGITS"],
      ["Expiry",             "5 minutes",                  "OTP_EXPIRY_MINUTES"],
      ["Max Attempts",       "5 failures before lockout",  "OTP_MAX_ATTEMPTS"],
      ["Lockout Delays",     "30s → 60s → 120s",          "OTP_LOCKOUT_DELAYS"],
      ["Resend Rate Limit",  "3 requests / minute",        "Rate limiter: otp-resend"],
      ["Verify Rate Limit",  "10 attempts / minute",       "Rate limiter: otp-verify"]
    ],
    [30, 40, 30]
  ),
  spacer(),
  h2("8.3 OTP Generation & Delivery"),
  bullet("OtpCode::generate() produces a cryptographically random 4-digit numeric string"),
  bullet("OtpCode::hash() applies bcrypt to the plain code for secure DB storage"),
  bullet("OtpService::issue() saves hashed code + expiry to users table with DB row lock"),
  bullet("If user has email: dispatches SendOtpEmailJob on email-high queue"),
  bullet("If user has phone number: dispatches SendOtpSmsJob on sms-high queue"),
  spacer(),
  h2("8.4 OTP Verification"),
  bullet("OtpService::verify() checks: code not expired, attempts < max, hash matches"),
  bullet("Progressive lockout: after 5 failures, enforces delay based on attempt count"),
  bullet("On success: clears OTP fields, marks user as OTP-verified in session"),
  bullet("On failure: increments otp_attempts, may trigger lockout"),
  spacer(),
  h2("8.5 Users Table OTP Columns"),
  code("otp_code         VARCHAR  -- bcrypt hash of plain code"),
  code("otp_expires_at   TIMESTAMP"),
  code("otp_attempts     TINYINT DEFAULT 0"),
  code("is_phone_verified BOOLEAN DEFAULT false"),
  pageBreak()
];

// ─── Section 9 ────────────────────────────────────────────────────────────────

const sec9 = [
  h1("9. Device Fingerprinting Flow"),
  h2("9.1 Purpose"),
  p("The device fingerprinting system prevents registration abuse by identifying and limiting the number of accounts created from a single device. It complements IP-based limits with a more durable fingerprint that persists across IP changes."),
  spacer(),
  h2("9.2 Fingerprint Generation (DeviceFingerprintService)"),
  bullet("Reads or generates a device_id cookie (5-year TTL, configurable)"),
  bullet("Collects: User-Agent, Client-IP, device cookie value, Accept-Language, Accept-Encoding"),
  bullet("Computes SHA-256 hash over the concatenated values"),
  bullet("Stores hash in users.fingerprint_hash and users.device_id at registration"),
  spacer(),
  h2("9.3 Device Limit Enforcement (DeviceLimitService)"),
  bullet("Checks account count by: fingerprint_hash, device_id, IP address"),
  bullet("Maximum accounts per dimension: 3 (configurable via DEVICE_MAX_ACCOUNTS)"),
  bullet("If limit exceeded: registration is rejected with a friendly error"),
  spacer(),
  h2("9.4 Database Columns"),
  code("fingerprint_hash   VARCHAR -- SHA-256 of UA+IP+cookie+headers"),
  code("device_id          VARCHAR -- Cookie value set at first visit"),
  spacer(),
  h2("9.5 Configuration"),
  code("// config/auth-security.php"),
  code("'device' => ["),
  code("    'max_accounts'       => env('DEVICE_MAX_ACCOUNTS', 3),"),
  code("    'cookie_name'        => 'device_id',"),
  code("    'cookie_ttl_days'    => 365 * 5,"),
  code("]"),
  pageBreak()
];

// ─── Section 10 ───────────────────────────────────────────────────────────────

const sec10 = [
  h1("10. Database Architecture"),
  h2("10.1 Overview"),
  p("The database is MySQL 8.4. All schema changes are managed through 50 Laravel migrations, organized chronologically. The schema follows a normalized relational design with JSON columns for flexible data (custom fields, schema markup)."),
  spacer(),
  h2("10.2 Core Entity Tables"),
  makeTable(
    ["Table", "Key Columns", "Purpose"],
    [
      ["users",               "id, name, email, phone, points, points_balance, is_banned, strike_count, fingerprint_hash, device_id, otp_*, roles via pivot", "Platform members"],
      ["listings",            "id, title, description, price, category_id, location_id, user_id, status, is_featured, featured_until, is_flagged, flag_reason, custom_fields_values (JSON)", "Core ad entity"],
      ["categories",          "id, name_ar, name_en, slug, parent_id, icon, custom_fields_schema (JSON), is_active, sort_order", "Hierarchical category tree"],
      ["locations",           "id, name_ar, name_en, slug, parent_id, level, lat, lng, is_active, sort_order", "Governorates and cities"],
      ["messages",            "id, sender_id, receiver_id, body, read_at", "User-to-user messages"],
      ["campaigns",           "id, title, message, target_group, status, scheduled_at, sent_at, recipients_count", "Marketing campaigns"],
      ["campaign_links",      "id, code, points_reward, expires_at, usage_limit, used_count, is_active", "Referral tracking links"],
      ["point_plans",         "id, name_ar, name_en, points, price, description, is_active", "Purchasable point packages"],
      ["point_transactions",  "id, user_id, amount, type, description, current_balance, created_at", "Points ledger"],
      ["legal_pages",         "id, title (JSON), content (JSON), slug, meta_title, meta_description, is_published", "Translatable legal content"],
      ["seo_templates",       "id, target_model, target_id, meta_title, meta_description, og_title, og_description, schema_markup (JSON)", "Polymorphic SEO entries"],
      ["activity_log",        "id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties (JSON), event, batch_uuid", "Spatie audit trail"],
      ["audit_logs",          "id, admin_id, action, target_type, target_id, notes", "Admin action log"],
      ["media",               "id, model_type, model_id, collection_name, file_name, conversions_disk, disk, manipulations (JSON)", "Spatie media files"],
      ["sessions",            "id, user_id, ip_address, user_agent, payload, last_activity", "Redis-backed sessions (DB fallback)"],
      ["notifications",       "id, type, notifiable_id, notifiable_type, data (JSON), read_at", "Laravel notifications"],
      ["jobs",                "id, queue, payload, attempts, reserved_at, available_at, created_at", "Queue job table"]
    ],
    [22, 48, 30]
  ),
  spacer(),
  h2("10.3 Relationships Diagram (key associations)"),
  code("User         hasMany  Listing"),
  code("User         hasMany  PointTransaction"),
  code("User         hasMany  Message (as sender)"),
  code("User         belongsToMany Role (via Filament Shield)"),
  code("Listing      belongsTo Category"),
  code("Listing      belongsTo Location"),
  code("Listing      belongsTo User"),
  code("Listing      hasMany  Media (images via Spatie)"),
  code("Category     hasMany  Category (children, self-join)"),
  code("Category     hasMany  Listing"),
  code("Location     hasMany  Location (children, self-join)"),
  code("Location     hasMany  Listing"),
  code("SeoTemplate  morphTo  Category | Location  (target_model/target_id)"),
  spacer(),
  h2("10.4 JSON Columns"),
  makeTable(
    ["Table", "Column", "Content"],
    [
      ["categories",    "custom_fields_schema",  "Dynamic field definitions (label, type, options) per category"],
      ["listings",      "custom_fields_values",  "User-supplied values matching the category schema"],
      ["seo_templates", "schema_markup",          "JSON-LD structured data for rich snippets"],
      ["legal_pages",   "title",                 "{ \"ar\": \"...\", \"en\": \"...\" } after migration"],
      ["legal_pages",   "content",               "{ \"ar\": \"...\", \"en\": \"...\" } after migration"]
    ],
    [22, 25, 53]
  ),
  pageBreak()
];

// ─── Section 11 ───────────────────────────────────────────────────────────────

const sec11 = [
  h1("11. Queue System"),
  h2("11.1 Architecture"),
  p("Laravel's queue system is used extensively to offload time-sensitive side-effects from the HTTP request cycle. The production queue driver is Redis (configured via QUEUE_CONNECTION=redis in .env). The development fallback is the database driver."),
  spacer(),
  h2("11.2 Named Queue Channels"),
  makeTable(
    ["Queue Name", "Priority", "Jobs Dispatched", "Worker Assignment"],
    [
      ["sms-high",        "Highest", "SendOtpSmsJob",             "Dedicated worker in Docker Compose"],
      ["email-high",      "High",    "SendOtpEmailJob",           "Same worker thread (comma-separated)"],
      ["auth-critical",   "High",    "Auth security notifications","Same worker thread"],
      ["notifications",   "Medium",  "CampaignNotification",      "Same worker thread"],
      ["default",         "Normal",  "All other jobs",            "Same worker thread"]
    ],
    [22, 16, 32, 30]
  ),
  spacer(),
  h2("11.3 Docker Worker Command"),
  code("php artisan queue:work redis \\"),
  code("    --queue=sms-high,email-high,auth-critical,notifications,default \\"),
  code("    --tries=3 \\"),
  code("    --timeout=60"),
  spacer(),
  h2("11.4 Scheduled Jobs (routes/console.php)"),
  code("Schedule::job(new ProcessScheduledCampaigns)"),
  code("    ->everyFiveMinutes()"),
  code("    ->withoutOverlapping();"),
  spacer(),
  h2("11.5 ProcessScheduledCampaigns Job"),
  bullet("Runs every 5 minutes via the scheduler"),
  bullet("Finds all Campaign records where status=scheduled and scheduled_at <= now"),
  bullet("For each campaign: dispatches CampaignNotification to target group"),
  bullet("Updates campaign status to sent and records recipients_count"),
  bullet("withoutOverlapping() prevents concurrent runs during long sends"),
  spacer(),
  noteBox("In production, run a dedicated scheduler container (docker/scheduler) that executes `php artisan schedule:run` every 60 seconds in a loop."),
  pageBreak()
];

// ─── Section 12 ───────────────────────────────────────────────────────────────

const sec12 = [
  h1("12. Cache System"),
  h2("12.1 Cache Driver"),
  p("The cache driver is Redis in production, configured via CACHE_STORE=redis. The application uses the default redis connection pointing to the Redis server defined in docker-compose.yml."),
  spacer(),
  h2("12.2 Cached Data"),
  makeTable(
    ["Data", "Where Cached", "Notes"],
    [
      ["Category tree (root categories)", "View composer in AppServiceProvider", "Top 6 active root categories for footer navigation"],
      ["Location data",                   "Location model — has media cache",   "Location images and conversions cached by Spatie"],
      ["Session data",                    "Redis session driver",               "All user sessions stored in Redis"],
      ["Rate limiter counters",           "Redis via RateLimiter facade",       "OTP attempts, registration throttle"],
      ["Scout query results",             "Collection driver (dev) / Meilisearch (prod)", "Search results are not additionally cached"]
    ],
    [30, 35, 35]
  ),
  spacer(),
  h2("12.3 Cache Configuration"),
  code("// config/cache.php (effective via .env)"),
  code("CACHE_STORE=redis"),
  code("REDIS_HOST=127.0.0.1"),
  code("REDIS_PORT=6379"),
  code("REDIS_DB=0"),
  spacer(),
  noteBox("Consider adding explicit cache tags for listing and category data as the platform scales. Cache::tags(['listings'])->remember() enables surgical invalidation from ListingObserver."),
  pageBreak()
];

// ─── Section 13 ───────────────────────────────────────────────────────────────

const sec13 = [
  h1("13. Search System — Scout + Meilisearch"),
  h2("13.1 Architecture"),
  p("Search is built on Laravel Scout, which provides a clean abstraction layer over search engines. The Listing model implements the Searchable trait and defines its own index schema including geo-coordinates for location-aware search."),
  spacer(),
  h2("13.2 Configuration"),
  makeTable(
    ["Environment", "Driver", "Config"],
    [
      ["Development", "collection", "SCOUT_DRIVER=collection — pure in-memory, zero infrastructure"],
      ["Production",  "meilisearch","SCOUT_DRIVER=meilisearch, MEILISEARCH_HOST=http://meilisearch:7700"]
    ],
    [20, 20, 60]
  ),
  spacer(),
  h2("13.3 Listing Search Index"),
  p("The Listing::toSearchableArray() method defines the indexed fields:"),
  code("return ["),
  code("    'id'       => $this->id,"),
  code("    'title'    => $this->title,"),
  code("    'price'    => $this->price,"),
  code("    'category' => $this->category?->name_ar,"),
  code("    'province' => $this->location?->name_ar,"),
  code("    'location' => $this->location?->name_ar,"),
  code("    '_geo'     => ['lat' => $this->lat, 'lng' => $this->lng],"),
  code("];"),
  spacer(),
  h2("13.4 Search Query (HomeController)"),
  bullet("Full-text search on query parameter q"),
  bullet("Filter by price range (min_price, max_price)"),
  bullet("Filter by category_id"),
  bullet("Filter by province (location)"),
  bullet("Geo-radius filtering via _geo Meilisearch feature"),
  bullet("Returns paginated results as Listing models with media loaded"),
  spacer(),
  h2("13.5 Index Synchronisation"),
  bullet("Scout auto-syncs on Listing::create, update, delete via model events"),
  bullet("Run `php artisan scout:import App\\Models\\Listing` to rebuild index from scratch"),
  bullet("ListingObserver::saved() can additionally trigger conditional re-indexing"),
  pageBreak()
];

// ─── Section 14 ───────────────────────────────────────────────────────────────

const sec14 = [
  h1("14. Real-Time System — Broadcasting"),
  h2("14.1 Current State"),
  p("The real-time infrastructure is implemented and ready for production enablement. The NewMessage event implements ShouldBroadcast and broadcasts on a private channel. The .env.example sets BROADCAST_CONNECTION=log for safe development defaults."),
  spacer(),
  h2("14.2 NewMessage Event"),
  code("class NewMessage implements ShouldBroadcast"),
  code("{"),
  code("    public function broadcastOn(): array"),
  code("    {"),
  code("        return ["),
  code("            new PrivateChannel('chat.' . $this->message->receiver_id),"),
  code("        ];"),
  code("    }"),
  code("}"),
  spacer(),
  h2("14.3 Broadcasting Drivers"),
  makeTable(
    ["Driver", "Use Case", "Configuration"],
    [
      ["log",    "Development — events logged to laravel.log", "BROADCAST_CONNECTION=log"],
      ["redis",  "Production with Laravel Reverb or Soketi",  "BROADCAST_CONNECTION=redis, REDIS_*"],
      ["pusher", "Managed production alternative",             "BROADCAST_CONNECTION=pusher, PUSHER_*"]
    ],
    [18, 50, 32]
  ),
  spacer(),
  h2("14.4 Recommended: Laravel Reverb"),
  p("For production, Laravel Reverb is the recommended self-hosted WebSocket server. It integrates natively with Laravel's broadcasting system."),
  bullet("Add `laravel/reverb` to composer.json"),
  bullet("Run `php artisan reverb:install`"),
  bullet("Configure REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET in .env"),
  bullet("Add a reverb service to docker-compose.yml"),
  bullet("Frontend: install laravel-echo + pusher-js, configure Echo to connect to Reverb"),
  spacer(),
  noteBox("The message architecture (messages table, MessageController, NewMessage event) is complete. Only the WebSocket server and Echo frontend setup need to be added to go live."),
  pageBreak()
];

// ─── Section 15 ───────────────────────────────────────────────────────────────

const sec15 = [
  h1("15. AI Integration Architecture"),
  h2("15.1 Provider"),
  p("AI functionality is powered by Google Gemini via a custom GeminiService class (app/Services/GeminiService.php). The service abstracts all Gemini API calls and provides domain-specific methods for listing creation workflows."),
  spacer(),
  h2("15.2 GeminiService Methods"),
  makeTable(
    ["Method", "Input", "Output", "Use Case"],
    [
      ["generateDescription()", "title, category, price, condition", "Arabic listing description text",   "Auto-generate ad body"],
      ["suggestPrice()",        "title, category, condition",        "Suggested price (integer)",         "Price recommendation"],
      ["autoCategorize()",      "title, description",               "Category slug/id",                  "Auto-categorize listing"],
      ["generateFromInput()",   "photos, audio transcript, title",  "Full listing draft",                "SmartAdCreator Livewire flow"],
      ["generateListingDescription()", "same as generateDescription", "Alias for consistent API",       "Alternate call signature"]
    ],
    [30, 28, 22, 20]
  ),
  spacer(),
  h2("15.3 SmartAdCreator Livewire Component"),
  bullet("Integrated into Filament admin panel via Livewire alias smart-ad-creator"),
  bullet("Allows admin to upload listing photos and optionally provide audio description"),
  bullet("Audio is transcribed (or text provided directly) and sent to Gemini"),
  bullet("Gemini returns a complete listing draft (title, description, price, category)"),
  bullet("Admin can review and save the AI-generated listing"),
  spacer(),
  h2("15.4 Configuration"),
  code("GEMINI_API_KEY=your-google-gemini-api-key"),
  spacer(),
  warnBox("Gemini API calls are synchronous in the current implementation. For high-volume use, consider wrapping generateFromInput() in a queued Job to prevent HTTP timeouts."),
  pageBreak()
];

// ─── Section 16 ───────────────────────────────────────────────────────────────

const sec16 = [
  h1("16. Payment Architecture"),
  h2("16.1 Overview"),
  p("The payment system facilitates the purchase of Point Plans. Users buy a plan to receive a points credit, which they can then spend on ad featuring. The PaymentController handles purchase initiation and webhook callbacks from the payment provider."),
  spacer(),
  h2("16.2 Routes"),
  code("// Authenticated users"),
  code("GET  /pricing                   → PricingController@index"),
  code("POST /payment/checkout          → PaymentController@checkout"),
  code("GET  /payment/success           → PaymentController@success"),
  code("GET  /payment/cancel            → PaymentController@cancel"),
  code("// Unauthenticated webhook (no CSRF)"),
  code("POST /payment/webhook           → PaymentController@webhook"),
  spacer(),
  h2("16.3 Point Plan Model"),
  code("Schema::create('point_plans', function (Blueprint $table) {"),
  code("    $table->id();"),
  code("    $table->string('name_ar');"),
  code("    $table->string('name_en');"),
  code("    $table->integer('points');        // Points to credit on purchase"),
  code("    $table->decimal('price', 8, 2);   // Price in EGP"),
  code("    $table->text('description')->nullable();"),
  code("    $table->boolean('is_active')->default(true);"),
  code("    $table->timestamps();"),
  code("});"),
  spacer(),
  h2("16.4 Payment Flow"),
  bullet("1. User browses plans at /pricing — PointPlanResource serves plan data"),
  bullet("2. User selects plan → POST /payment/checkout → PaymentController initiates charge"),
  bullet("3. Payment gateway redirects to /payment/success or /payment/cancel"),
  bullet("4. Asynchronously: gateway POSTs to /payment/webhook with payment confirmation"),
  bullet("5. Webhook handler verifies signature, calls PointService::credit(user, plan->points, 'purchase')"),
  bullet("6. PointTransaction record created, user.points updated"),
  spacer(),
  warnBox("Payment gateway credentials and webhook signature verification logic are not yet implemented. This must be completed before launch. Recommended providers: Paymob, Fawry (Egyptian market), or Stripe with EGP support."),
  pageBreak()
];

// ─── Section 17 ───────────────────────────────────────────────────────────────

const sec17 = [
  h1("17. Filament Admin Architecture"),
  h2("17.1 Panel Configuration"),
  makeTable(
    ["Setting", "Value"],
    [
      ["Panel ID",         "admin"],
      ["URL Path",         "/admin"],
      ["Theme",            "Violet (dark mode enabled)"],
      ["Auth Guard",       "web (roles checked via Filament Shield)"],
      ["Access Control",   "Filament Shield — roles: super_admin, admin, moderator"],
      ["Discovery",        "Auto-discovers Resources, Pages, Widgets under app/Filament/Admin/"],
      ["Plugins",          "FilamentShieldPlugin, FilamentTranslatablePlugin (AR + EN)"],
      ["Livewire Alias",   "smart-ad-creator → App\\Livewire\\Admin\\SmartAdCreator"]
    ],
    [30, 70]
  ),
  spacer(),
  h2("17.2 Navigation Structure"),
  makeTable(
    ["Navigation Group", "Resources"],
    [
      ["الإشراف (Moderation)", "UserResource, ModerationResource"],
      ["المحتوى (Content)",    "CategoryResource, LocationResource, ListingResource, LegalPageResource, SeoTemplateResource"],
      ["الإدارة (Admin)",      "CampaignResource, CampaignLinkResource, PointPlanResource, ActivityLogResource, AuditLogResource, SiteSettingResource"]
    ],
    [30, 70]
  ),
  spacer(),
  h2("17.3 Resource Schema"),
  p("Each Filament resource follows a split-file pattern:"),
  bullet("ResourceClass.php — defines navigation, model, form/table methods"),
  bullet("Schemas/ResourceForm.php — defines the form fields"),
  bullet("Tables/ResourceTable.php — defines the table columns and filters"),
  bullet("Pages/ListResource.php, CreateResource.php, EditResource.php, ViewResource.php"),
  bullet("RelationManagers/ChildrenRelationManager.php (for hierarchical models)"),
  spacer(),
  h2("17.4 Dashboard Widgets"),
  makeTable(
    ["Widget", "Data Source", "Access"],
    [
      ["StatsOverviewWidget",      "DB counts: total_revenue, users, ads, pending, flagged", "super_admin only"],
      ["ListingsChart",            "7-day line chart of new listings",                        "super_admin only"],
      ["CategoriesChartWidget",    "Listings count by top categories",                        "All admins"],
      ["GovernoratesChartWidget",  "Listings count by governorate",                           "All admins"]
    ],
    [30, 50, 20]
  ),
  spacer(),
  h2("17.5 Key Admin Actions"),
  bullet("UserResource: Ban user, Unban user, Add strike, Reset strikes — all logged to AuditLog"),
  bullet("ListingResource: Approve, Reject (with reason), Flag — tabs: All/Pending/Published/Flagged/Rejected"),
  bullet("CategoryResource: Manage custom field schema JSON, reorder, set icon"),
  bullet("CampaignResource: Schedule campaigns with target group (all_users, new_users, active_sellers)"),
  bullet("SeoTemplateResource: Set per-category/per-location meta tags and JSON-LD schema"),
  pageBreak()
];

// ─── Section 18 ───────────────────────────────────────────────────────────────

const sec18 = [
  h1("18. Livewire Architecture"),
  h2("18.1 Livewire Version"),
  p("The application uses Livewire 3.x (installed as part of Filament 5 dependencies) for reactive server-side components without writing a separate SPA or API layer."),
  spacer(),
  h2("18.2 Components"),
  makeTable(
    ["Component", "Location", "Purpose"],
    [
      ["UserDashboard",   "app/Livewire/Frontend/",      "User's listing management, offers inbox, featured toggle, delete"],
      ["ListingGrid",     "app/Livewire/Frontend/",      "Public listing grid with search, filters, geo-location, pagination"],
      ["SmartAdCreator",  "app/Livewire/Admin/",         "AI-powered listing creation — photo upload, Gemini API, form prefill"]
    ],
    [22, 28, 50]
  ),
  spacer(),
  h2("18.3 SmartAdCreator Flow (Admin)"),
  bullet("1. Admin opens SmartAdCreator modal in Filament"),
  bullet("2. Uploads photos and optionally provides text or audio description"),
  bullet("3. Component calls GeminiService::generateFromInput(photos, audio, title)"),
  bullet("4. Gemini API returns generated title, description, suggested price, category"),
  bullet("5. Livewire fills the Filament listing form fields with the generated values"),
  bullet("6. Admin reviews, adjusts, and saves the listing"),
  spacer(),
  h2("18.4 AlpineJS Integration"),
  bullet("Alpine.js complements Livewire for lightweight client-side interactivity"),
  bullet("Used for: dropdown toggles, modal open/close, counter animations"),
  bullet("GSAP handles complex animations (homepage hero, transitions)"),
  bullet("Three.js powers the 3D background effect on the homepage"),
  pageBreak()
];

// ─── Section 19 ───────────────────────────────────────────────────────────────

const sec19 = [
  h1("19. Security Architecture"),
  h2("19.1 Security Layers"),
  makeTable(
    ["Layer", "Mechanism", "Protects Against"],
    [
      ["HTTP Headers",        "SecurityHeaders middleware",               "Clickjacking, MIME sniffing, SSL stripping"],
      ["CSRF",                "Laravel CSRF tokens on all POST routes",   "Cross-Site Request Forgery"],
      ["Authentication",      "Breeze + bcrypt password hashing",         "Credential theft"],
      ["OTP",                 "Hashed codes, 5-min expiry, lockout",      "Account takeover"],
      ["Device Limits",       "Fingerprint + IP + cookie checks",         "Registration abuse, bot accounts"],
      ["Rate Limiting",       "Named rate limiters on auth endpoints",    "Brute force, DDoS on auth routes"],
      ["Authorization",       "Filament Shield roles, Laravel Policies",  "Unauthorized resource access"],
      ["Moderation",          "ListingObserver, strike system, ban flag", "Spam, fraud, policy violations"],
      ["Fraud Detection",     "Keyword scanning in ListingObserver",      "Scam listings, Arabic fraud patterns"],
      ["Audit Trail",         "Spatie Activity Log + AuditLog model",     "Forensic accountability"],
      ["Session Security",    "Redis sessions, regenerate on login",      "Session fixation, hijacking"],
      ["Input Validation",    "Form Requests (LoginRequest, etc.)",       "SQL injection, XSS via validation"],
      ["Dependency Security", "Enlightn Security Checker (CI)",           "Vulnerable dependencies in CI pipeline"]
    ],
    [22, 35, 43]
  ),
  spacer(),
  h2("19.2 Auth Security Config"),
  code("// config/auth-security.php"),
  code("["),
  code("    'otp' => ["),
  code("        'digits'          => 4,"),
  code("        'expiry_minutes'  => 5,"),
  code("        'max_attempts'    => 5,"),
  code("        'lockout_delays'  => [30, 60, 120],"),
  code("    ],"),
  code("    'device' => ["),
  code("        'max_accounts'  => 3,"),
  code("        'cookie_name'   => 'device_id',"),
  code("    ],"),
  code("    'queues' => ["),
  code("        'sms'   => 'sms-high',"),
  code("        'email' => 'email-high',"),
  code("        'auth'  => 'auth-critical',"),
  code("    ],"),
  code("]"),
  spacer(),
  h2("19.3 User Ban and Strike System"),
  bullet("Users can receive strikes from admins for policy violations"),
  bullet("User::addStrike() increments strike_count, auto-bans at 3 strikes"),
  bullet("EnsureUserIsNotBanned middleware: checks is_banned flag on every web request"),
  bullet("Banned users are immediately logged out and redirected to login with explanation"),
  bullet("All ban/unban/strike actions are logged to AuditLog via UserResource admin actions"),
  pageBreak()
];

// ─── Section 20 ───────────────────────────────────────────────────────────────

const sec20 = [
  h1("20. SEO Architecture"),
  h2("20.1 Overview"),
  p("The SEO system has two complementary layers: (1) SeoTemplate records in the database for category and governorate pages, and (2) programmatically generated Open Graph images for social sharing."),
  spacer(),
  h2("20.2 SeoTemplate System"),
  bullet("Polymorphic model: target_model (Category|Location) + target_id"),
  bullet("Stores: meta_title, meta_description, og_title, og_description, og_image"),
  bullet("Stores: schema_markup JSON-LD for rich snippets"),
  bullet("Admin manages via SeoTemplateResource in Filament"),
  bullet("Views inject the template data into <head> meta tags"),
  spacer(),
  h2("20.3 SeoTemplate Schema"),
  code("seo_templates"),
  code("├── id"),
  code("├── target_model   -- 'App\\Models\\Category' | 'App\\Models\\Location'"),
  code("├── target_id      -- FK to target model"),
  code("├── meta_title"),
  code("├── meta_description"),
  code("├── og_title"),
  code("├── og_description"),
  code("├── og_image"),
  code("└── schema_markup  -- JSON (JSON-LD structured data)"),
  spacer(),
  h2("20.4 GenerateSeoImages Command"),
  bullet("Artisan command: app/Console/Commands/GenerateSeoImages.php (330 lines)"),
  bullet("Uses PHP GD library to generate Open Graph images for all governorates"),
  bullet("Renders governorate name in Cairo Arabic font over a background template"),
  bullet("Output images saved to public/images/SEO/governorates/"),
  bullet("Pre-generated images exist for all 27 Egyptian governorates"),
  bullet("Cairo font files bundled: public/fonts/Cairo-Bold.ttf, Cairo-VariableFont.ttf"),
  spacer(),
  h2("20.5 URL Structure for SEO"),
  makeTable(
    ["Route Pattern", "Purpose", "SEO Notes"],
    [
      ["/",                       "Homepage with hero search",    "Meta injected from SiteSetting"],
      ["/category/{slug}",        "Category listing page",        "SeoTemplate by category"],
      ["/listings/{listing}",     "Single listing detail page",   "Listing title/description used"],
      ["/{slug}",                 "Legal pages (catch-all)",      "LegalPage.meta_title used"]
    ],
    [30, 40, 30]
  ),
  pageBreak()
];

// ─── Section 21 ───────────────────────────────────────────────────────────────

const sec21 = [
  h1("21. Activity Logs System"),
  h2("21.1 Technology"),
  p("Activity logging uses Spatie Laravel Activity Log (v4.12), the industry-standard package for Eloquent model change tracking and custom event logging."),
  spacer(),
  h2("21.2 Database Tables"),
  makeTable(
    ["Table", "Purpose"],
    [
      ["activity_log",  "Core Spatie table: log_name, description, subject, causer, properties JSON"],
      ["audit_logs",    "Custom admin action table: admin_id, action, target_type, target_id, notes"]
    ],
    [30, 70]
  ),
  spacer(),
  h2("21.3 Auth Event Logging"),
  p("Five listeners registered in AppServiceProvider capture authentication events:"),
  makeTable(
    ["Event", "Listener", "Logged Data"],
    [
      ["Login",          "LogSuccessfulLogin",  "user_id, IP, user-agent, timestamp"],
      ["Failed",         "LogFailedLogin",      "email/phone attempted, IP, user-agent"],
      ["Logout",         "LogSuccessfulLogout", "user_id, session_id"],
      ["roleAttached",   "LogRoleAssigned",     "user_id, role_name, assigned_by"],
      ["roleDetached",   "LogRoleRevoked",      "user_id, role_name, revoked_by"]
    ],
    [25, 30, 45]
  ),
  spacer(),
  h2("21.4 Model Activity Logging"),
  bullet("Category model uses LogsActivity — logs created, updated, deleted"),
  bullet("User model actions logged via UserResource admin actions → AuditLog"),
  bullet("Listing moderation actions (approve, reject, flag) logged via Filament actions"),
  spacer(),
  h2("21.5 Filament Admin View"),
  bullet("ActivityLogResource provides a read-only list view of all activity_log entries"),
  bullet("AuditLogResource lists all admin action records from audit_logs table"),
  bullet("Both are restricted to admin navigation group — visible to super_admin, admin roles"),
  pageBreak()
];

// ─── Section 22 ───────────────────────────────────────────────────────────────

const sec22 = [
  h1("22. Notification System"),
  h2("22.1 Notification Channels"),
  makeTable(
    ["Notification Class", "Channels", "Trigger", "Queue"],
    [
      ["CampaignNotification", "mail + database", "ProcessScheduledCampaigns job", "notifications"],
      ["OTP (email)",          "mail",            "OtpService::issue()",           "email-high"],
      ["OTP (SMS)",            "SMS provider",    "OtpService::issue()",           "sms-high"],
      ["Database notifications","database",       "Various Filament actions",      "default"]
    ],
    [30, 20, 30, 20]
  ),
  spacer(),
  h2("22.2 CampaignNotification"),
  code("class CampaignNotification extends Notification"),
  code("{"),
  code("    public function via(object $notifiable): array"),
  code("    {"),
  code("        return ['mail', 'database'];"),
  code("    }"),
  code("}"),
  spacer(),
  p("The notification stores a copy in the notifications table (for in-app inbox) and sends an email to the user using the configured mail driver."),
  spacer(),
  h2("22.3 In-App Notification Store"),
  bullet("notifications table created by migration 2026_05_12_214103_create_notifications_table.php"),
  bullet("Standard Laravel notification morphMany pattern on User model"),
  bullet("Notification data stored as JSON in the data column"),
  bullet("read_at nullable timestamp marks read/unread status"),
  spacer(),
  h2("22.4 Mail Configuration"),
  code("MAIL_MAILER=smtp"),
  code("MAIL_HOST=smtp.mailgun.org   # or any SMTP provider"),
  code("MAIL_PORT=587"),
  code("MAIL_USERNAME="),
  code("MAIL_PASSWORD="),
  code("MAIL_FROM_ADDRESS=noreply@nilex.com"),
  pageBreak()
];

// ─── Section 23 ───────────────────────────────────────────────────────────────

const sec23 = [
  h1("23. Fraud Detection System"),
  h2("23.1 Architecture"),
  p("The fraud detection system operates at two levels: (1) automated real-time keyword scanning in the ListingObserver, and (2) manual moderation tools in the Filament admin panel."),
  spacer(),
  h2("23.2 ListingObserver — Auto-Flagging"),
  bullet("Hooked into Listing::saved() lifecycle event"),
  bullet("Scans listing title and description for Arabic fraud keywords"),
  bullet("Keyword list covers common Egyptian classified ad scam patterns"),
  bullet("If match found: sets is_flagged=true, populates flag_reason with matched keyword"),
  bullet("Flagged listings appear in the Auto-flagged tab in Filament Listings resource"),
  spacer(),
  h2("23.3 Fraud Detection Database Columns"),
  code("// Added by migration 2026_06_04_200001_add_fraud_detection_to_listings_table.php"),
  code("$table->boolean('is_flagged')->default(false);"),
  code("$table->string('flag_reason')->nullable();"),
  code("$table->timestamp('flagged_at')->nullable();"),
  spacer(),
  h2("23.4 Moderation Pipeline"),
  makeTable(
    ["Stage", "Mechanism", "Outcome"],
    [
      ["Auto-flag",       "ListingObserver keyword scan on save",      "Sets is_flagged=true, status remains as-is"],
      ["Pending review",  "All new listings set to status=pending",    "Listing not shown publicly until approved"],
      ["Admin review",    "Filament Moderation resource / Listings",   "Admin approves or rejects with reason"],
      ["Strike system",   "Admin adds strike via UserResource action", "3 strikes → auto-ban"],
      ["Ban",             "EnsureUserIsNotBanned middleware",          "Banned user cannot access any route"]
    ],
    [20, 42, 38]
  ),
  spacer(),
  h2("23.5 Strike System (User Model)"),
  code("public function addStrike(): void"),
  code("{"),
  code("    $this->increment('strike_count');"),
  code("    if ($this->strike_count >= 3) {"),
  code("        $this->ban();"),
  code("    }"),
  code("}"),
  pageBreak()
];

// ─── Section 24 ───────────────────────────────────────────────────────────────

const sec24 = [
  h1("24. Media Upload System"),
  h2("24.1 Technology"),
  p("Media management is handled by Spatie Laravel Media Library (v11.21) with automatic image optimization via Spatie Image Optimizer (v1.8). The system supports multiple collections per model, image conversions, and optional watermarking."),
  spacer(),
  h2("24.2 Listing Media"),
  bullet("Listings use Spatie's HasMedia trait with a photos collection"),
  bullet("On upload: optimizer compresses JPG/PNG automatically"),
  bullet("Two conversions defined per image: thumb (small preview) and full_hd (full quality)"),
  bullet("Watermark applied to full_hd conversion with platform branding"),
  bullet("Images served from the local or S3 disk based on FILESYSTEM_DISK env"),
  spacer(),
  h2("24.3 Media Table Schema"),
  makeTable(
    ["Column", "Purpose"],
    [
      ["model_type / model_id", "Polymorphic relation to the owning model (Listing, Location, etc.)"],
      ["collection_name",       "Grouping key (photos, avatar, og_image)"],
      ["file_name",             "Original filename stored on disk"],
      ["disk",                  "Storage disk name (local, s3)"],
      ["conversions_disk",      "Separate disk for conversions (optional)"],
      ["manipulations",         "JSON array of applied image manipulations"],
      ["custom_properties",     "JSON for watermark state, alt text, etc."]
    ],
    [30, 70]
  ),
  spacer(),
  h2("24.4 Location Media"),
  bullet("Location model supports header images via Spatie Media Library"),
  bullet("Used for governorate/city banner images on category pages"),
  spacer(),
  h2("24.5 SEO Pre-Generated Images"),
  bullet("public/images/SEO/ contains pre-generated category images"),
  bullet("public/images/SEO/governorates/ contains 27 governorate OG images"),
  bullet("Generated by GenerateSeoImages artisan command using PHP GD"),
  pageBreak()
];

// ─── Section 25 ───────────────────────────────────────────────────────────────

const sec25 = [
  h1("25. Localization Architecture"),
  h2("25.1 Overview"),
  p("Nilex is an Arabic-first platform with full bilingual support. Localization operates at multiple levels: application locale, model content, and admin panel UI."),
  spacer(),
  h2("25.2 Application Locale"),
  makeTable(
    ["Config", "Value", "Description"],
    [
      ["app.locale",           "ar",         "Default locale — Arabic"],
      ["app.fallback_locale",  "en",         "Fallback locale — English"],
      ["available_locales",    "['ar','en']", "Supported locales for SetLocale middleware"]
    ],
    [28, 20, 52]
  ),
  spacer(),
  h2("25.3 Locale Switching"),
  bullet("POST /language/{locale} route accepts ar or en"),
  bullet("SetLocale middleware reads locale from session on each request"),
  bullet("App::setLocale() applied per request — no page reload needed for Livewire components"),
  spacer(),
  h2("25.4 Translation Files"),
  bullet("lang/ar/ui.php — Arabic UI strings"),
  bullet("lang/en/ui.php — English UI strings"),
  bullet("Standard Laravel trans() and __() helpers used in Blade views"),
  spacer(),
  h2("25.5 Model-Level Translation"),
  makeTable(
    ["Approach", "Models", "Package"],
    [
      ["Separate columns (name_ar / name_en)",    "Category, Location, PointPlan, CarBrand, CarModel", "None — manual columns"],
      ["Spatie Translatable (JSON)",              "LegalPage (title, content after migration)",        "spatie/laravel-translatable"],
      ["Filament Translatable plugin",            "All Filament resources with translatable content",  "jeffersongoncalves/filament-translatable"]
    ],
    [40, 35, 25]
  ),
  spacer(),
  h2("25.6 RTL Support"),
  bullet("Tailwind CSS configured with RTL support for Arabic layouts"),
  bullet("HTML dir=rtl set for Arabic locale, dir=ltr for English"),
  bullet("Filament panel uses Tailwind RTL classes for admin interface"),
  pageBreak()
];

// ─── Section 26 ───────────────────────────────────────────────────────────────

const sec26 = [
  h1("26. Performance Optimizations"),
  h2("26.1 Current Optimizations"),
  makeTable(
    ["Area", "Optimization", "Impact"],
    [
      ["Search",     "Meilisearch (external engine)",       "Offloads full-text search from MySQL, sub-millisecond queries"],
      ["Cache",      "Redis cache driver",                  "In-memory caching, 10–100x faster than DB reads"],
      ["Sessions",   "Redis session driver",                "Session reads from memory, scales horizontally"],
      ["Queue",      "Named priority queues on Redis",      "SMS/email delivered without blocking HTTP thread"],
      ["Images",     "Spatie Image Optimizer",              "Reduced media file sizes automatically on upload"],
      ["Images",     "Pre-generated SEO images (GD)",       "OG images served as static files — zero generation overhead"],
      ["Assets",     "Vite with code splitting",            "JS bundles tree-shaken and split per page"],
      ["DB queries", "Eager loading in controllers",        "N+1 prevented with with() calls"],
      ["DB queries", "Category view counts (indexed)",      "Cached view_count increments"],
      ["Scheduler",  "withoutOverlapping() on campaigns",   "Prevents duplicate campaign sends"]
    ],
    [18, 38, 44]
  ),
  spacer(),
  h2("26.2 Production Optimization Commands"),
  code("php artisan config:cache        # Cache configuration files"),
  code("php artisan route:cache         # Cache route list"),
  code("php artisan view:cache          # Pre-compile Blade templates"),
  code("php artisan event:cache         # Cache event-listener map"),
  code("php artisan icons:cache         # Cache Filament icon sets"),
  code("php artisan filament:cache-components  # Cache Filament component registry"),
  code("npm run build                   # Compile and minify CSS/JS assets"),
  spacer(),
  h2("26.3 Recommended Additional Optimizations"),
  bullet("Add OPcache to PHP-FPM configuration (docker/php/php.ini already present — enable opcache.ini)"),
  bullet("Enable Meilisearch geo-indexing attributes for fast location-based filtering"),
  bullet("Add Laravel Horizon for Redis queue monitoring and auto-scaling"),
  bullet("Implement HTTP caching headers (Cache-Control, ETag) on listing detail pages"),
  bullet("Consider read replicas for the MySQL database as traffic grows"),
  pageBreak()
];

// ─── Section 27 ───────────────────────────────────────────────────────────────

const sec27 = [
  h1("27. Deployment Recommendations"),
  h2("27.1 Docker Compose Architecture"),
  makeTable(
    ["Service", "Image", "Role"],
    [
      ["nginx",      "docker/nginx/default.conf", "Reverse proxy, SSL termination, static file serving"],
      ["php",        "docker/php/Dockerfile (PHP 8.3-FPM)", "Laravel application server"],
      ["mysql",      "mysql:8.4",                 "Primary relational database"],
      ["redis",      "redis:7-alpine",             "Cache, session, queue backend"],
      ["queue",      "Same PHP image",             "Queue worker (all named channels)"],
      ["scheduler",  "Same PHP image",             "Artisan schedule:run loop every 60s"],
      ["horizon",    "Same PHP image (optional)",  "Redis queue dashboard (activate via profile)"]
    ],
    [18, 42, 40]
  ),
  spacer(),
  h2("27.2 CI/CD Pipeline (GitHub Actions)"),
  makeTable(
    ["Stage", "Steps", "Trigger"],
    [
      ["Lint & Security",  "PHP CS Fixer (Pint), Enlightn Security Checker", "All PRs"],
      ["Unit Tests",       "Pest with SQLite in-memory database",            "All PRs"],
      ["E2E Tests",        "Cypress against full stack (auth, OTP, social, abuse)", "All PRs"],
      ["Docker Build",     "Build image, push to registry",                  "main branch only"],
      ["Deploy",           "Deployment step (placeholder — implement)",      "main branch only"]
    ],
    [20, 48, 32]
  ),
  spacer(),
  h2("27.3 Environment Variables Checklist"),
  makeTable(
    ["Variable", "Required", "Notes"],
    [
      ["APP_KEY",             "Yes", "php artisan key:generate"],
      ["DB_*",                "Yes", "MySQL credentials"],
      ["REDIS_HOST/PORT",     "Yes", "Redis connection"],
      ["QUEUE_CONNECTION",    "Yes", "Set to redis in production"],
      ["CACHE_STORE",         "Yes", "Set to redis in production"],
      ["SESSION_DRIVER",      "Yes", "Set to redis in production"],
      ["MAIL_*",              "Yes", "SMTP credentials for email OTP + campaigns"],
      ["GEMINI_API_KEY",      "Yes", "Google AI Studio API key"],
      ["MEILISEARCH_HOST",    "Yes", "Meilisearch server URL"],
      ["MEILISEARCH_KEY",     "Yes", "Meilisearch master key"],
      ["BROADCAST_CONNECTION","Yes", "Set to reverb or pusher (not log)"],
      ["SCOUT_DRIVER",        "Yes", "Set to meilisearch (not collection)"],
      ["FILESYSTEM_DISK",     "Yes", "s3 for production media storage"],
      ["OTP_*",               "Yes", "OTP configuration values"],
      ["DEVICE_MAX_ACCOUNTS", "No",  "Default: 3"],
      ["Payment gateway vars","Yes", "Paymob/Fawry/Stripe credentials (implement)"]
    ],
    [35, 12, 53]
  ),
  spacer(),
  h2("27.4 Production Checklist"),
  bullet("Set APP_ENV=production, APP_DEBUG=false"),
  bullet("Run all cache commands (config, route, view, event, icons)"),
  bullet("Run php artisan migrate --force"),
  bullet("Run php artisan scout:import App\\Models\\Listing"),
  bullet("Configure SSL certificate on nginx"),
  bullet("Set up S3 bucket and configure FILESYSTEM_DISK=s3"),
  bullet("Enable Laravel Horizon for queue monitoring"),
  bullet("Configure log aggregation (Sentry, Papertrail, or Logtail)"),
  bullet("Disable Laravel Telescope in production (only enable for admin users if needed)"),
  bullet("Set up database backups (mysqldump cron or managed DB service)"),
  pageBreak()
];

// ─── Section 28 ───────────────────────────────────────────────────────────────

const sec28 = [
  h1("28. Strengths, Risks & Future Recommendations"),
  spacer(),
  h2("28.1 Architecture Strengths"),
  tip("Auth Domain Isolation — The app/Auth/ module is self-contained with its own provider, services, jobs, and middleware. This significantly reduces the blast radius of security changes and makes testing auth logic in isolation straightforward."),
  spacer(),
  tip("Modular Filament Structure — Each Filament resource is split across Schema, Table, and Pages files. This prevents God-files and makes large admin panels maintainable as features grow."),
  spacer(),
  tip("Priority Queue Channels — OTP and auth jobs are dispatched to dedicated high-priority Redis queues (sms-high, email-high, auth-critical), ensuring critical notifications are never delayed by background work."),
  spacer(),
  tip("Scout Abstraction — Using Laravel Scout as the search layer means switching from the collection driver (dev) to Meilisearch (prod) requires no code changes — only an environment variable."),
  spacer(),
  tip("Dual-Layer SEO — Programmatic OG image generation (GD) combined with database-driven SeoTemplate meta tags covers both social sharing and search engine optimization without a third-party service."),
  spacer(),
  tip("Points Economy Foundation — The PointService uses lockForUpdate() for atomic balance operations, preventing race-condition double-credits. The PointTransaction ledger provides a complete audit trail."),
  spacer(),
  tip("Comprehensive Activity Logging — Five auth event listeners + Spatie Activity Log + custom AuditLog create a three-layer forensic trail covering end-user auth events, model changes, and admin actions."),
  spacer(),
  tip("Docker + CI/CD Ready — A complete docker-compose.yml with dedicated queue, scheduler, and optional Horizon services, plus a multi-stage GitHub Actions workflow, indicates production readiness awareness."),
  spacer(),
  h2("28.2 Technical Risks"),
  warnBox("Dual Points Balance Fields: The users table has both points (managed by PointService) and points_balance (referral rewards). If both are incremented independently by different code paths, the user's effective balance can become inconsistent. Consolidate into a single source of truth or clearly document the distinction and enforce separation at the service layer."),
  spacer(),
  warnBox("Duplicate Social Auth Routes: Social OAuth routes (/auth/{provider} and /auth/{provider}/callback) are registered in both routes/web.php and routes/auth.php. Duplicate named routes can cause url() helper conflicts and unpredictable redirects. Remove duplicates from one file."),
  spacer(),
  warnBox("Payment Gateway Not Implemented: The PaymentController, /payment/webhook route, and PointPlan model exist but payment gateway integration (Paymob, Fawry, Stripe) is not complete. Webhook signature verification is mandatory before go-live to prevent fraudulent point crediting."),
  spacer(),
  warnBox("WebSocket Broadcast Driver: BROADCAST_CONNECTION=log in .env.example means real-time messaging (NewMessage event) is non-functional until a proper driver (Reverb, Pusher) is configured. The messages table and controller are ready; only the transport layer is missing."),
  spacer(),
  warnBox("Synchronous Gemini API Calls: GeminiService methods make synchronous HTTP calls to the Google Gemini API. A slow or failed API response will stall the HTTP request and potentially cause a 504 timeout under load. Wrap in a queued Job with retry logic."),
  spacer(),
  warnBox("Telescope in Production: TelescopeServiceProvider is registered globally and collects request/query/job data. Even with the production filter, it should be disabled entirely in production or gated strictly behind admin authentication to avoid performance overhead and data exposure."),
  spacer(),
  warnBox("Catch-All Legal Route Risk: The GET /{slug} route for legal pages must remain the last route in web.php. Any future route added after it will be unreachable. Add a comment guard and document this constraint clearly."),
  spacer(),
  h2("28.3 Scalability Recommendations"),
  makeTable(
    ["Scale Level", "Bottleneck", "Recommended Solution"],
    [
      ["10K users",    "Single MySQL server",          "Add read replica, query caching with Redis tags"],
      ["50K users",    "Session/cache contention",     "Redis Cluster or Redis Sentinel for HA"],
      ["100K users",   "Queue throughput",             "Laravel Horizon with auto-scaling worker groups"],
      ["200K users",   "Media storage & delivery",     "S3 + CloudFront CDN for media and static assets"],
      ["500K users",   "Single monolith bottleneck",   "Extract Search, Notification, and Media into separate services or use SQS"],
      ["1M+ users",    "DB write throughput",          "MySQL to Aurora Serverless or Vitess horizontal sharding"]
    ],
    [18, 32, 50]
  ),
  spacer(),
  h2("28.4 Future Architecture Recommendations"),
  bullet("API Layer: Add routes/api.php with Sanctum token authentication for a mobile app (React Native / Flutter). The models and services are ready; only API controllers and resources are needed."),
  spacer(),
  bullet("Horizon Dashboard: Install Laravel Horizon (it's in the docker-compose horizon profile). Horizon provides queue throughput metrics, job retry management, and worker auto-scaling rules based on queue depth."),
  spacer(),
  bullet("Event Sourcing for Points: As the points economy grows, migrate PointTransaction to an append-only event-sourced store (Spatie Event Sourcing). This enables point balance replay, fraud investigation, and chargeback handling."),
  spacer(),
  bullet("Microservice Candidates: The AI (Gemini) integration, the search indexing pipeline, and the campaign sending engine are natural candidates for extraction into separate services or Lambda functions as traffic grows, decoupling their failure modes from the core web application."),
  spacer(),
  bullet("Multi-tenancy: If Nilex expands to other Arab markets (e.g., Saudi Arabia, UAE), consider adding a country_id scope to Listing, Category, and Location, or use a multi-tenant package (Tenancy for Laravel) to isolate data per market."),
  spacer(),
  bullet("CDN Integration: Integrate Cloudflare or AWS CloudFront in front of nginx for: static asset edge caching, DDoS protection, bot management, and SSL termination — reducing load on origin servers significantly."),
  spacer(),
  bullet("Full-Text Search Expansion: Extend Meilisearch indexing to Category and Location models, enabling global site search across all content types. Add Arabic-language tokenization settings (Meilisearch supports Arabic natively)."),
  spacer(),
  bullet("Observability Stack: Add Sentry for error tracking (laravel/sentry-laravel) and Datadog or Prometheus+Grafana for infrastructure metrics. This replaces Telescope in production with a purpose-built observability stack."),
  spacer(),
  new Paragraph({
    children: [new TextRun({ text: "─────────────────────────────────────────────", color: BRAND, size: 24, font: "Calibri" })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 400, after: 200 }
  }),
  new Paragraph({
    children: [new TextRun({ text: "End of Document", bold: true, size: 24, color: BRAND, font: "Calibri" })],
    alignment: AlignmentType.CENTER
  }),
  new Paragraph({
    children: [new TextRun({ text: "Nilex Platform  ·  Technical Architecture  ·  June 2026  ·  v1.0", size: 18, color: "999999", font: "Calibri" })],
    alignment: AlignmentType.CENTER,
    spacing: { before: 80 }
  })
];

// ─── Assemble Document ────────────────────────────────────────────────────────

const doc = new Document({
  creator: "Nilex Architecture Generator",
  title: "Nilex Platform — Technical Architecture Document",
  description: "Comprehensive technical architecture document for the Nilex classified-ads platform",
  styles: {
    default: {
      document: {
        run: { font: "Calibri", size: 22 }
      }
    },
    paragraphStyles: [
      {
        id: "Heading1",
        name: "Heading 1",
        basedOn: "Normal",
        next: "Normal",
        run: { bold: true, size: 36, color: WHITE, font: "Calibri" },
        paragraph: {
          shading: { type: ShadingType.CLEAR, fill: BRAND },
          spacing: { before: 400, after: 160 }
        }
      }
    ]
  },
  sections: [
    {
      properties: {
        page: {
          margin: {
            top: convertInchesToTwip(1.0),
            bottom: convertInchesToTwip(1.0),
            left: convertInchesToTwip(1.25),
            right: convertInchesToTwip(1.25)
          }
        }
      },
      headers: {
        default: new Header({
          children: [
            new Paragraph({
              children: [
                new TextRun({ text: "Nilex Platform — Technical Architecture Document", size: 18, color: "777777", font: "Calibri" }),
                new TextRun({ text: "       " }),
                new TextRun({ text: "Confidential", size: 18, color: "999999", font: "Calibri", italics: true })
              ],
              border: { bottom: { color: BORDER, size: 4, style: BorderStyle.SINGLE } }
            })
          ]
        })
      },
      footers: {
        default: new Footer({
          children: [
            new Paragraph({
              children: [
                new TextRun({ text: "© 2026 Nilex Platform  ·  Page ", size: 18, color: "999999", font: "Calibri" }),
                new TextRun({ children: [PageNumber.CURRENT], size: 18, font: "Calibri", color: "555555" }),
                new TextRun({ text: " of ", size: 18, color: "999999", font: "Calibri" }),
                new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 18, font: "Calibri", color: "555555" })
              ],
              alignment: AlignmentType.CENTER,
              border: { top: { color: BORDER, size: 4, style: BorderStyle.SINGLE } }
            })
          ]
        })
      },
      children: [
        ...coverPage,
        ...tocPage,
        ...sec1,
        ...sec2,
        ...sec3,
        ...sec4,
        ...sec5,
        ...sec6,
        ...sec7,
        ...sec8,
        ...sec9,
        ...sec10,
        ...sec11,
        ...sec12,
        ...sec13,
        ...sec14,
        ...sec15,
        ...sec16,
        ...sec17,
        ...sec18,
        ...sec19,
        ...sec20,
        ...sec21,
        ...sec22,
        ...sec23,
        ...sec24,
        ...sec25,
        ...sec26,
        ...sec27,
        ...sec28
      ]
    }
  ]
});

const outputPath = path.resolve(__dirname, "..", "Nilex-Architecture.docx");
Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync(outputPath, buffer);
  console.log("SUCCESS: " + outputPath);
  console.log("Size: " + (buffer.length / 1024).toFixed(1) + " KB");
});

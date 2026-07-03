<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Nilex') }} — {{ $title ?? __('ui.auth.page_title_default') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .input-field {
            width: 100%;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            color: #1f2937;
            font-family: 'Cairo', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
            outline: none;
            display: block;
        }
        .input-field::placeholder { color: #9ca3af; }
        .input-field:focus {
            background: white;
            border-color: #11407A;
            box-shadow: 0 0 0 3px rgba(17, 64, 122, 0.12);
        }

        .btn-nilex-primary {
            width: 100%;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 15px;
            padding: 13px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: block;
        }

        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
            font-weight: 500;
            padding: 10px;
            border-radius: 12px;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-social:hover  { background: #f9fafb; border-color: #d1d5db; }
        .btn-social:active { transform: scale(0.98); }

        .tab-btn {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            color: #6b7280;
            border: none;
            background: transparent;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .tab-btn.active {
            background: #14A5A8;
            color: white;
            box-shadow: 0 2px 8px rgba(20, 165, 168, 0.22);
        }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            transition: background 0.3s;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #9ca3af;
            font-size: 13px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
    </style>
</head>
<body style="margin:0; background:#f4f4f5; min-height:100vh; -webkit-font-smoothing:antialiased;" class="font-sans">

{{-- 🌐 مبدّل اللغة --}}
@php $nextLocale = app()->getLocale() === 'ar' ? 'en' : 'ar'; @endphp
<form method="POST" action="{{ route('language.switch', $nextLocale) }}"
      style="position:fixed; top:16px; inset-inline-end:16px; z-index:50;">
    @csrf
    <button type="submit"
            style="font-size:12px; font-weight:700; color:#6b7280; background:#fff; border:1px solid #e5e7eb; padding:5px 10px; border-radius:8px; cursor:pointer;">
        {{ app()->getLocale() === 'ar' ? 'EN' : 'ع' }}
    </button>
</form>

@php
    use Illuminate\Support\Facades\Storage;
    $settings = \App\Models\SiteSetting::getSettings();
    
    // Filament 5.4 FileUpload array structure compatibility
    $imagePath = is_array($settings->auth_bg_image) 
                 ? ($settings->auth_bg_image[0] ?? null) 
                 : $settings->auth_bg_image;

    $bgImageUrl = $imagePath ? Storage::disk('public')->url($imagePath) : null;

    if ($settings && $settings->auth_bg_type === 'image' && $bgImageUrl) {
        $bgStyle = "background-image:url('{$bgImageUrl}'); background-size:cover; background-position:center;";
    } else {
        $bgStyle = "background:" . ($settings->auth_bg_color ?? '#085041') . ";";
    }
@endphp

<div style="min-height:100vh; display:flex;">

    {{-- ── LEFT PANEL: Trust + brand (desktop only) ───────────────────────── --}}
    <div id="auth-left"
         style="display:none; width:50%; flex-direction:column; justify-content:center; padding:48px; position:relative; overflow:hidden; {{ $bgStyle }}">

        {{-- Subtle dot texture --}}
        <div style="position:absolute; inset:0; opacity:0.07;"
             aria-hidden="true">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <pattern id="dots" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.5" fill="white"/>
                </pattern>
                <rect width="100%" height="100%" fill="url(#dots)"/>
            </svg>
        </div>

        <div style="position:relative; z-index:1; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">

            {{-- Logo --}}
            <div style="margin-bottom:40px;">
                <a href="{{ route('home') }}" style="display:inline-flex; align-items:center; gap:10px; text-decoration:none;">
                    <img src="{{ asset('images/logo/download.png') }}"
                         style="height:36px; filter:brightness(0) invert(1);"
                         alt="Nilex logo"
                         onerror="this.style.display='none'; this.nextSibling.style.display='inline'">
                    <span style="font-size:26px; font-weight:900; color:white; letter-spacing:-0.5px; display:none;">نايلكس</span>
                </a>
                <p style="color:rgba(255,255,255,0.6); font-size:14px; margin-top:8px;">
                    {{ __('ui.auth.panel_tagline') }}
                </p>
            </div>

            {{-- Trust points --}}
            <div style="display:flex; flex-direction:column; gap:16px; margin-bottom:40px;">
                @php
                    $trustPoints = [
                        ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'text' => __('ui.auth.panel_trust_1')],
                        ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'text' => __('ui.auth.panel_trust_2')],
                        ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => __('ui.auth.panel_trust_3')],
                    ];
                @endphp
                @foreach($trustPoints as $point)
                    <div style="display:flex; align-items:center; gap:14px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); border-radius:14px; padding:14px 16px;">
                        <div style="width:36px; height:36px; background:rgba(159,225,203,0.2); border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg style="width:18px;height:18px;color:#9FE1CB;" fill="none" stroke="#9FE1CB" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $point['icon'] }}"/>
                            </svg>
                        </div>
                        <p style="color:rgba(255,255,255,0.85); font-size:14px; font-weight:600; margin:0;">{{ $point['text'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Stats --}}
            <div style="display:flex; gap:0; border:1px solid rgba(255,255,255,0.15); border-radius:16px; overflow:hidden;">
                @php
                    $stats = [
                        ['value' => __('ui.auth.panel_stat_1_value'), 'label' => __('ui.auth.panel_stat_1_label')],
                        ['value' => __('ui.auth.panel_stat_2_value'), 'label' => __('ui.auth.panel_stat_2_label')],
                        ['value' => __('ui.auth.panel_stat_3_value'), 'label' => __('ui.auth.panel_stat_3_label')],
                    ];
                @endphp
                @foreach($stats as $i => $stat)
                    <div style="flex:1; text-align:center; padding:16px 12px; {{ $i < count($stats)-1 ? 'border-left:1px solid rgba(255,255,255,0.12);' : '' }}">
                        <p style="color:white; font-size:22px; font-weight:900; margin:0; line-height:1;">{{ $stat['value'] }}</p>
                        <p style="color:rgba(255,255,255,0.55); font-size:12px; margin:6px 0 0;">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>

        </div>
    </div>

    {{-- ── RIGHT PANEL: Form ───────────────────────────────────────────────── --}}
    <div style="flex:1; display:flex; align-items:center; justify-content:center; padding:24px; background:white; overflow-y:auto; min-height:100vh;">
        <div class="form-card" style="width:100%; max-width:440px;">

            {{-- Mobile logo --}}
            <div id="mobile-logo" style="text-align:center; margin-bottom:32px;">
                <a href="{{ route('home') }}"
                   style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
                    <img src="{{ asset('images/logo/download.png') }}"
                         style="height:32px;"
                         alt="Nilex"
                         onerror="this.style.display='none'; this.nextSibling.style.display='inline'">
                    <span style="font-size:22px; font-weight:900; color:#14A5A8; display:none;">نايلكس</span>
                </a>
            </div>

            {{ $slot }}

        </div>
    </div>

</div>

<script>
    (function() {
        var left = document.getElementById('auth-left');
        var logo = document.getElementById('mobile-logo');
        function check() {
            if (window.innerWidth >= 1024) {
                left.style.display = 'flex';
                logo.style.display = 'none';
            } else {
                left.style.display = 'none';
                logo.style.display = 'block';
            }
        }
        check();
        window.addEventListener('resize', check);
    })();
</script>

@stack('scripts')

</body>
</html>
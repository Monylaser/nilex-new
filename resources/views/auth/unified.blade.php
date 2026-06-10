<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Nilex') }} - منصة الإعلانات الأولى</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:400,500,600,700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Cairo', 'Inter', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }
        
        /* Animated gradient overlay */
        .animated-gradient {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            background-size: 200% 200%;
            animation: gradientShift 10s ease infinite;
            z-index: -2;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Floating orbs */
        .floating-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            animation: floatOrb 20s ease-in-out infinite;
            z-index: -1;
        }
        
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 30px) scale(0.9); }
        }
        
        /* Card flip animation */
        .auth-card {
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            transform-style: preserve-3d;
        }
        
        .auth-card.flipped {
            transform: rotateY(180deg);
        }
        
        /* Smooth tab transition */
        .tab-content {
            transition: all 0.4s ease;
        }
        
        .tab-content.hidden {
            opacity: 0;
            transform: translateX(20px);
            pointer-events: none;
            position: absolute;
        }
        
        /* Ripple effect on button */
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .ripple:active:after {
            width: 300px;
            height: 300px;
            opacity: 0;
        }
    </style>
</head>
<body class="antialiased">

<!-- Animated Background -->
<div class="animated-gradient"></div>

<!-- Floating Orbs -->
<div class="floating-orb" style="width: 300px; height: 300px; background: #8B5CF6; top: -100px; right: -100px;"></div>
<div class="floating-orb" style="width: 500px; height: 500px; background: #EC4899; bottom: -200px; left: -100px; animation-delay: 3s;"></div>
<div class="floating-orb" style="width: 200px; height: 200px; background: #06B6D4; top: 50%; left: 50%; animation-delay: 7s;"></div>

<!-- 3D Background Container -->
<div id="three-bg" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;"></div>

<!-- Main Container -->
<div class="min-h-screen flex items-center justify-center px-4 py-12 relative z-10">
    <div class="w-full max-w-md">
        
        <!-- Logo -->
        <div class="text-center mb-8 animate-float">
            <div class="inline-block">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center shadow-2xl">
                    <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-black text-white mt-4 tracking-tight bg-gradient-to-r from-white to-purple-200 bg-clip-text text-transparent">
                {{ config('app.name', 'Nilex') }}
            </h1>
            <p class="text-purple-200 mt-2 text-sm">سوقك الأول للبيع والشراء</p>
        </div>
        
        <!-- Glass Card -->
        <div class="glass-card rounded-3xl p-8 shadow-2xl" x-data="authTabs()">
            
            <!-- Tabs Navigation -->
            <div class="flex gap-2 mb-8 bg-white/5 rounded-xl p-1">
                <button @click="setTab('login')" 
                        :class="{'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg': activeTab === 'login', 'text-gray-300 hover:text-white': activeTab !== 'login'}"
                        class="flex-1 py-3 rounded-lg font-bold transition-all duration-300 text-sm">
                    دخول
                </button>
                <button @click="setTab('register')" 
                        :class="{'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg': activeTab === 'register', 'text-gray-300 hover:text-white': activeTab !== 'register'}"
                        class="flex-1 py-3 rounded-lg font-bold transition-all duration-300 text-sm">
                    حساب جديد
                </button>
            </div>
            
            <!-- Login Form -->
            <div x-show="activeTab === 'login'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="tab-content">
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    
                    <!-- Identifier Field (Email or Phone) -->
                    <div>
                        <label class="block text-white/80 text-sm font-bold mb-2">البريد الإلكتروني أو رقم الجوال</label>
                        <div class="relative">
                            <input type="text" 
                                   name="identifier" 
                                   value="{{ old('identifier') }}"
                                   class="glass-input w-full text-white placeholder-white/40"
                                   placeholder="example@nilex.com أو 05xxxxxxxx"
                                   required autofocus>
                            <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
                                <svg class="w-5 h-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                        </div>
                        @error('identifier')
                            <p class="text-pink-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Password Field with Eye Toggle -->
                    <div x-data="passwordToggle()">
                        <label class="block text-white/80 text-sm font-bold mb-2">كلمة المرور</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" 
                                   name="password"
                                   class="glass-input w-full text-white placeholder-white/40"
                                   placeholder="••••••••"
                                   required>
                            <button type="button" @click="toggle()" class="eye-icon text-white/60 hover:text-white">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/10 text-purple-600 focus:ring-purple-500">
                            <span class="mr-2 text-sm text-white/70">تذكرني</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm text-purple-300 hover:text-white transition">
                            نسيت كلمة المرور؟
                        </a>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="glass-button w-full text-white ripple">
                        دخول إلى حسابي
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/10"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-transparent text-white/50">أو</span>
                    </div>
                </div>
                
                <!-- Social Login -->
                <div class="grid grid-cols-3 gap-3">
                    <a href="{{ route('social.redirect', 'google') }}" class="social-icon group">
                        <svg class="w-6 h-6 text-white/80 group-hover:text-white" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </a>
                    <a href="{{ route('social.redirect', 'instagram') }}" class="social-icon group">
                        <svg class="w-6 h-6 text-white/80 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    <a href="{{ route('social.redirect', 'tiktok') }}" class="social-icon group">
                        <svg class="w-6 h-6 text-white/80 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 015.2-1.74V8.39a9.38 9.38 0 004.89 1.39v-3.3a4.83 4.83 0 01-2.67.21z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Register Form -->
            <div x-show="activeTab === 'register'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="tab-content" x-cloak>
                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    
                    <!-- Name -->
                    <div>
                        <label class="block text-white/80 text-sm font-bold mb-2">الاسم الكامل</label>
                        <input type="text" 
                               name="name" 
                               value="{{ old('name') }}"
                               class="glass-input w-full text-white placeholder-white/40"
                               placeholder="أحمد محمد"
                               required>
                        @error('name')
                            <p class="text-pink-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Email or Phone -->
                    <div>
                        <label class="block text-white/80 text-sm font-bold mb-2">البريد الإلكتروني أو رقم الجوال</label>
                        <input type="text" 
                               name="identifier" 
                               value="{{ old('identifier') }}"
                               class="glass-input w-full text-white placeholder-white/40"
                               placeholder="example@nilex.com أو 05xxxxxxxx"
                               required>
                        @error('identifier')
                            <p class="text-pink-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Password -->
                    <div x-data="passwordToggle()">
                        <label class="block text-white/80 text-sm font-bold mb-2">كلمة المرور</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" 
                                   name="password"
                                   class="glass-input w-full text-white placeholder-white/40"
                                   placeholder="••••••••"
                                   required>
                            <button type="button" @click="toggle()" class="eye-icon text-white/60 hover:text-white">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-pink-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Confirm Password -->
                    <div x-data="passwordToggle()">
                        <label class="block text-white/80 text-sm font-bold mb-2">تأكيد كلمة المرور</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" 
                                   name="password_confirmation"
                                   class="glass-input w-full text-white placeholder-white/40"
                                   placeholder="••••••••"
                                   required>
                        </div>
                    </div>
                    
                    <!-- Terms -->
                    <label class="flex items-center">
                        <input type="checkbox" name="terms" required class="rounded border-white/20 bg-white/10 text-purple-600 focus:ring-purple-500">
                        <span class="mr-2 text-sm text-white/70">أوافق على <a href="#" class="text-purple-300 hover:text-white">الشروط والأحكام</a></span>
                    </label>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="glass-button w-full text-white ripple">
                        إنشاء حساب جديد
                    </button>
                </form>
                
                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-white/10"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-transparent text-white/50">أو سجل عبر</span>
                    </div>
                </div>
                
                <!-- Social Register -->
                <div class="grid grid-cols-3 gap-3">
                    <a href="{{ route('social.redirect', 'google') }}" class="social-icon group">
                        <svg class="w-6 h-6 text-white/80 group-hover:text-white" viewBox="0 0 24 24">
                            <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </a>
                    <a href="{{ route('social.redirect', 'instagram') }}" class="social-icon group">
                        <svg class="w-6 h-6 text-white/80 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    <a href="{{ route('social.redirect', 'tiktok') }}" class="social-icon group">
                        <svg class="w-6 h-6 text-white/80 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 015.2-1.74V8.39a9.38 9.38 0 004.89 1.39v-3.3a4.83 4.83 0 01-2.67.21z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Footer Text -->
        <p class="text-center text-white/50 text-xs mt-8">
            © 2026 {{ config('app.name', 'Nilex') }}. جميع الحقوق محفوظة
        </p>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

</body>
</html>
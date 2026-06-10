<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Nilex') }} — {{ $title ?? 'تسجيل الدخول' }}</title>

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
            border-color: #1D9E75;
            box-shadow: 0 0 0 3px rgba(29, 158, 117, 0.12);
        }

        .btn-primary {
            width: 100%;
            background: #1D9E75;
            color: white;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 15px;
            padding: 13px;
<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        // الضربة القاضية: زيادة العداد تلقائياً عند الدخول
        $category->increment('views_count');

        // جلب الإعلانات التابعة للقسم (مع الأقسام الفرعية لو حبيت)
        $listings = $category->listings()->where('status', 'published')->latest()->paginate(12);

        return view('frontend.category', compact('category', 'listings'));
    }
}
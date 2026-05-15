<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Layout;
use App\Models\Listing;
use App\Models\Category;
use App\Models\CarBrand;
use Meilisearch\Endpoints\Indexes;

#[Layout('layouts.app')]
class ListingGrid extends Component
{
    use WithPagination;

    #[Url(history: true)] public $search = '';
    #[Url(history: true)] public $category_id = '';
    #[Url(history: true)] public $car_brand_id = '';
    #[Url(history: true)] public $min_price = '';
    #[Url(history: true)] public $max_price = '';
    
    // متغيرات الـ GPS
    #[Url(history: true)] public $lat = null;
    #[Url(history: true)] public $lng = null;
    public $radiusInMeters = 20000; // 20 كيلو متر

    public function updated($property)
    {
        if (in_array($property, ['search', 'category_id', 'car_brand_id', 'min_price', 'max_price'])) {
            $this->resetPage();
        }
    }

    public function setLocation($latitude, $longitude)
    {
        $this->lat = $latitude;
        $this->lng = $longitude;
        $this->resetPage();
    }

    public function clearLocation()
    {
        $this->lat = null;
        $this->lng = null;
        $this->resetPage();
    }

    public function render()
    {
        // 1. استخدام Scout مع Meilisearch للبحث الفائق السرعة
        $listings = Listing::search($this->search, function (Indexes $meilisearch, string $query, array $options) {
            
            // بناء فلاتر Meilisearch
            $filters = ["status = 'published'"];

            if ($this->category_id) {
                $filters[] = "category_id = {$this->category_id}";
            }
            if ($this->car_brand_id) {
                $filters[] = "car_brand_id = {$this->car_brand_id}";
            }
            if ($this->min_price) {
                $filters[] = "price >= " . (float)$this->min_price;
            }
            if ($this->max_price) {
                $filters[] = "price <= " . (float)$this->max_price;
            }
            
            // 📍 الفلتر الجغرافي السحري
            if ($this->lat && $this->lng) {
                $filters[] = "_geoRadius({$this->lat}, {$this->lng}, {$this->radiusInMeters})";
            }

            // دمج الفلاتر
            $options['filter'] = implode(' AND ', $filters);
            
            return $meilisearch->search($query, $options);
        })
        ->query(fn ($query) => $query->with(['category', 'province', 'location', 'carBrand']))
        ->paginate(12);

        $categories = Category::whereNull('parent_id')->where('is_active', true)->get();
        $carBrands = CarBrand::orderBy('name_ar')->get();

        return view('livewire.frontend.listing-grid', compact('listings', 'categories', 'carBrands'));
    }
}
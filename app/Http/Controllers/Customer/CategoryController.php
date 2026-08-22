<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CategoryService;

class CategoryController extends Controller
{
    public function __construct(public CategoryService $category_service) {}

    public function popularCategories()
    {
        try {
            return $this->category_service->popularCategories();
        } catch (\Throwable $th) {
            return responseError('Failed to fetch popular categories: ' . $th->getMessage(), 500);
        }
    }
}

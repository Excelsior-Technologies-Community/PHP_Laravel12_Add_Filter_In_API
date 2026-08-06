<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // 🔹 Show all products
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $color = $request->get('color');
        $size = $request->get('size');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');

        $products = Product::query();

        if ($search) {
            $products->where('product_name', 'like', '%' . $search . '%');
        }

        if ($category) {
            $categories = explode(',', $category);
            $products->whereIn('category', $categories);
        }

        if ($color) {
            $products->where('color', $color);
        }

        if ($size) {
            $products->where('size', $size);
        }

        if ($minPrice !== null && $minPrice !== '') {
            $products->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $products->where('price', '<=', $maxPrice);
        }

        $products = $products->latest()->paginate(15);

        $allCategories = Product::orderBy('category')->pluck('category')->unique()->values();
        $allColors = Product::orderBy('color')->pluck('color')->unique()->values();
        $allSizes = Product::orderBy('size')->pluck('size')->unique()->values();

        return view('products.index', compact('products', 'search', 'category', 'color', 'size', 'minPrice', 'maxPrice', 'allCategories', 'allColors', 'allSizes'));
    }

    // 🔹 Show create form
    public function create()
    {
        return view('products.create');
    }

    // 🔹 Store product
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required',
            'details'      => 'required',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg',
            'size'         => 'required',
            'color'        => 'required',
            'category'     => 'required',
            'price'        => 'required|numeric',
        ]);

        $imageName = null;

        // ✅ IMAGE SAVE IN public/image
        if ($request->hasFile('image')) {
            $imageName = time().'_'.$request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        Product::create([
            'product_name' => $request->product_name,
            'details'      => $request->details,
            'image'        => $imageName,
            'size'         => $request->size,
            'color'        => $request->color,
            'category'     => $request->category,
            'price'        => $request->price,
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Product Created Successfully');
    }

    // 🔹 Edit product
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    // 🔹 Update product
    public function update(Request $request, Product $product)
    {
        $imageName = $product->image;

        if ($request->hasFile('image')) {

            // 🔥 old image delete
            if ($product->image && file_exists(public_path('image/'.$product->image))) {
                unlink(public_path('image/'.$product->image));
            }

            $imageName = time().'_'.$request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        $product->update([
            'product_name' => $request->product_name,
            'details'      => $request->details,
            'image'        => $imageName,
            'size'         => $request->size,
            'color'        => $request->color,
            'category'     => $request->category,
            'price'        => $request->price,
        ]);

        return redirect()->route('products.index')
            ->with('success', 'Product Updated Successfully');
    }

    // 🔹 Delete product
    public function destroy(Product $product)
    {
        // 🔥 delete image from public/image
        if ($product->image && file_exists(public_path('image/'.$product->image))) {
            unlink(public_path('image/'.$product->image));
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product Deleted Successfully');
    }
        // ===============================
    // 🔥 API METHODS (FOR POSTMAN)
    // ===============================

// 🔹 API: Get all products with filters
    public function apiIndex(Request $request)
    {
        $priceSort = $request->price;
        $dateFilter = $request->date;
        $idSort = $request->id_sort;
        $categoryFilter = $request->category;
        $colorFilter = $request->color;
        $sizeFilter = $request->size;
        $minPrice = $request->min_price;
        $maxPrice = $request->max_price;
        $exactPrice = $request->price_eq;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $search = $request->search;
        $perPage = $request->per_page ?? 15;

        $query = Product::query();

        // -----------------------------------------
        //  SEARCH BY PRODUCT NAME
        // -----------------------------------------
        if ($search) {
            $query->where('product_name', 'like', '%' . $search . '%');
        }

        // -----------------------------------------
        //  DATE FILTERING
        // -----------------------------------------
        if ($dateFilter == 'today') {
            $query->whereDate('created_at', now()->toDateString());
        }
        elseif ($dateFilter == 'this_week') {
            $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ]);
        }
        elseif ($dateFilter == 'this_month') {
            $query->whereMonth('created_at', now()->month);
        }

        // -----------------------------------------
        //  DATE RANGE FILTER
        // -----------------------------------------
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // -----------------------------------------
        //  CATEGORY FILTER (comma-separated for multiple)
        // -----------------------------------------
        if ($categoryFilter) {
            $categories = explode(',', $categoryFilter);
            $query->whereIn('category', $categories);
        }

        // -----------------------------------------
        //  COLOR FILTER
        // -----------------------------------------
        if ($colorFilter) {
            $query->where('color', $colorFilter);
        }

        // -----------------------------------------
        //  SIZE FILTER
        // -----------------------------------------
        if ($sizeFilter) {
            $query->where('size', $sizeFilter);
        }

        // -----------------------------------------
        //  PRICE RANGE FILTER
        // -----------------------------------------
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        // -----------------------------------------
        //  EXACT PRICE FILTER
        // -----------------------------------------
        if ($exactPrice !== null) {
            $query->where('price', $exactPrice);
        }

        // -----------------------------------------
        //  ID SORTING (Highest Priority)
        // -----------------------------------------
        if ($idSort == 'low_high') {
            $query->orderBy('id', 'asc');
        }
        elseif ($idSort == 'high_low') {
            $query->orderBy('id', 'desc');
        }

        // -----------------------------------------
        //  PRICE SORTING (Second Priority)
        // -----------------------------------------
        if ($priceSort == 'low_high') {
            $query->orderBy('price', 'asc');
        }
        elseif ($priceSort == 'high_low') {
            $query->orderBy('price', 'desc');
        }

        // -----------------------------------------
        //  DEFAULT SORT (If no filters selected)
        // -----------------------------------------
        if (!$priceSort && !$idSort) {
            $query->latest();
        }

        // -----------------------------------------
        //  PAGINATION
        // -----------------------------------------
        $products = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'price_sort' => $priceSort,
            'date_filter' => $dateFilter,
            'id_sort' => $idSort,
            'category_filter' => $categoryFilter,
            'color_filter' => $colorFilter,
            'size_filter' => $sizeFilter,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'exact_price' => $exactPrice,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'search' => $search,
            'per_page' => $perPage,
            'data' => $products
        ]);
    }




    //  API: Filter documentation endpoint
    public function apiDocs()
    {
        return response()->json([
            'status' => true,
            'message' => 'Product API Filters Documentation',
            'filters' => [
                'search' => 'Search products by name (e.g., ?search=laptop)',
                'category' => 'Filter by category, comma-separated for multiple (e.g., ?category=electronics,clothing)',
                'color' => 'Filter by color (e.g., ?color=red)',
                'size' => 'Filter by size (e.g., ?size=M)',
                'min_price' => 'Minimum price filter (e.g., ?min_price=100)',
                'max_price' => 'Maximum price filter (e.g., ?max_price=500)',
                'price_eq' => 'Exact price filter (e.g., ?price_eq=299)',
                'from_date' => 'Products created from this date (YYYY-MM-DD)',
                'to_date' => 'Products created until this date (YYYY-MM-DD)',
                'date' => 'Date preset: today, this_week, this_month',
                'price' => 'Price sort: low_high, high_low',
                'id_sort' => 'ID sort: low_high, high_low',
                'per_page' => 'Items per page for pagination (default: 15)',
            ],
            'sorting' => [
                'id_sort' => 'Sort by ID: low_high (asc), high_low (desc)',
                'price' => 'Sort by price: low_high (asc), high_high (desc)',
            ],
            'example_requests' => [
                'GET /api/products?category=electronics&min_price=100&max_price=500',
                'GET /api/products?search=laptop&price=low_high&per_page=10',
                'GET /api/products?color=red&size=M&from_date=2025-01-01&to_date=2025-12-31',
                'GET /api/products?date=today&id_sort=high_low',
            ],
            'live_search' => [
                'GET /api/products/search?q=keyword' => 'Returns up to 8 matching products for live suggestions',
                'GET /api/products/search/history' => 'Returns recent 10 search history entries',
                'POST /api/products/search/history' => 'Store a new search history entry (send {action: "store", keyword: "..."})',
            ],
        ]);
    }

    //  API: Store product
    public function apiStore(Request $request)
    {
        $request->validate([
            'product_name' => 'required',
            'details'      => 'required',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg',
            'size'         => 'required',
            'color'        => 'required',
            'category'     => 'required',
            'price'        => 'required|numeric',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $imageName = time().'_'.$request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        $product = Product::create([
            'product_name' => $request->product_name,
            'details'      => $request->details,
            'image'        => $imageName,
            'size'         => $request->size,
            'color'        => $request->color,
            'category'     => $request->category,
            'price'        => $request->price,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product Created Successfully',
            'data' => $product
        ], 201);
    }

    // 🔹 API: Show single product
    public function apiShow($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product Not Found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $product
        ]);
    }

    // 🔹 API: Update product
    public function apiUpdate(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product Not Found'
            ], 404);
        }

        $imageName = $product->image;

        if ($request->hasFile('image')) {

            if ($product->image && file_exists(public_path('image/'.$product->image))) {
                unlink(public_path('image/'.$product->image));
            }

            $imageName = time().'_'.$request->image->getClientOriginalName();
            $request->image->move(public_path('image'), $imageName);
        }

        $product->update([
            'product_name' => $request->product_name,
            'details'      => $request->details,
            'image'        => $imageName,
            'size'         => $request->size,
            'color'        => $request->color,
            'category'     => $request->category,
            'price'        => $request->price,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product Updated Successfully',
            'data' => $product
        ]);
    }

    // 🔹 API: Delete product
    public function apiDelete($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product Not Found'
            ], 404);
        }

        if ($product->image && file_exists(public_path('image/'.$product->image))) {
            unlink(public_path('image/'.$product->image));
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product Deleted Successfully'
        ]);
    }

    // 🔹 API: AJAX live search suggestions
    public function apiSearch(Request $request)
    {
        $keyword = $request->get('q');

        if (strlen($keyword) < 2) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'Please type at least 2 characters'
            ]);
        }

        $products = Product::where('product_name', 'like', '%' . $keyword . '%')
            ->limit(8)
            ->get();

        return response()->json([
            'status' => true,
            'keyword' => $keyword,
            'data' => $products
        ]);
    }

    // 🔹 API: Search history - store and retrieve
    public function apiSearchHistory(Request $request)
    {
        $action = $request->get('action', 'list');

        if ($action === 'store') {
            $keyword = $request->get('keyword');

            if ($keyword) {
                \App\Models\SearchHistory::create([
                    'keyword' => $keyword,
                    'results_count' => $request->get('results_count', 0),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Search history saved'
            ]);
        }

        // List recent search history
        $histories = \App\Models\SearchHistory::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $histories
        ]);
    }

}

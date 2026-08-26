<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\SearchHistory;

class ProductController extends Controller
{
    // ============================================================
    // WEB METHODS
    // ============================================================

    // Show all products
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
            $products->where(
                'product_name',
                'like',
                '%' . $search . '%'
            );
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

        $products = $products
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $allCategories = Product::orderBy('category')
            ->pluck('category')
            ->unique()
            ->values();

        $allColors = Product::orderBy('color')
            ->pluck('color')
            ->unique()
            ->values();

        $allSizes = Product::orderBy('size')
            ->pluck('size')
            ->unique()
            ->values();

        return view('products.index', compact(
            'products',
            'search',
            'category',
            'color',
            'size',
            'minPrice',
            'maxPrice',
            'allCategories',
            'allColors',
            'allSizes'
        ));
    }

    // Show create form
    public function create()
    {
        return view('products.create');
    }

    // Store product
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required',
            'details'      => 'required',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'size'         => 'required',
            'color'        => 'required',
            'category'     => 'required',
            'price'        => 'required|numeric|min:0',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {
            $imageName =
                time() . '_' .
                $request->image->getClientOriginalName();

            $request->image->move(
                public_path('image'),
                $imageName
            );
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

        return redirect()
            ->route('products.index')
            ->with('success', 'Product Created Successfully');
    }

    // Edit product
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    // Update product
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'product_name' => 'required',
            'details'      => 'required',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'size'         => 'required',
            'color'        => 'required',
            'category'     => 'required',
            'price'        => 'required|numeric|min:0',
        ]);

        $imageName = $product->image;

        if ($request->hasFile('image')) {

            if (
                $product->image &&
                file_exists(
                    public_path('image/' . $product->image)
                )
            ) {
                unlink(
                    public_path('image/' . $product->image)
                );
            }

            $imageName =
                time() . '_' .
                $request->image->getClientOriginalName();

            $request->image->move(
                public_path('image'),
                $imageName
            );
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

        return redirect()
            ->route('products.index')
            ->with('success', 'Product Updated Successfully');
    }

    // Delete product
    public function destroy(Product $product)
    {
        if (
            $product->image &&
            file_exists(
                public_path('image/' . $product->image)
            )
        ) {
            unlink(
                public_path('image/' . $product->image)
            );
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product Deleted Successfully');
    }


    // ============================================================
    // API METHODS
    // ============================================================

    /**
     * API: Get products with advanced filters and sorting
     *
     * Existing filters:
     * - search
     * - category
     * - color
     * - size
     * - min_price
     * - max_price
     * - price_eq
     * - date
     * - from_date
     * - to_date
     * - price
     * - id_sort
     * - per_page
     *
     * New:
     * - multiple colors
     * - multiple sizes
     * - sort_by
     * - sort_order
     */
    public function apiIndex(Request $request)
    {
        // ========================================================
        // EXISTING PARAMETERS
        // ========================================================

        $priceSort = $request->get('price');
        $dateFilter = $request->get('date');
        $idSort = $request->get('id_sort');

        $categoryFilter = $request->get('category');
        $colorFilter = $request->get('color');
        $sizeFilter = $request->get('size');

        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $exactPrice = $request->get('price_eq');

        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $search = $request->get('search');

        // ========================================================
        // NEW PARAMETERS
        // ========================================================

        $sortBy = $request->get('sort_by');
        $sortOrder = $request->get('sort_order', 'asc');

        // ========================================================
        // VALIDATE PARAMETERS
        // ========================================================

        $request->validate([
            'price' => 'nullable|in:low_high,high_low',

            'date' => 'nullable|in:today,this_week,this_month',

            'id_sort' => 'nullable|in:low_high,high_low',

            'min_price' => 'nullable|numeric|min:0',

            'max_price' => 'nullable|numeric|min:0',

            'price_eq' => 'nullable|numeric|min:0',

            'from_date' => 'nullable|date_format:Y-m-d',

            'to_date' => 'nullable|date_format:Y-m-d',

            'per_page' => 'nullable|integer|min:1|max:100',

            // NEW SORT VALIDATION
            'sort_by' => 'nullable|in:id,name,price,created_at',

            'sort_order' => 'nullable|in:asc,desc',
        ]);

        // ========================================================
        // PAGINATION
        // ========================================================

        $perPage = $request->get('per_page', 15);

        // ========================================================
        // BUILD QUERY
        // ========================================================

        $query = Product::query();


        // ========================================================
        // SEARCH BY PRODUCT NAME
        // ========================================================

        if ($search) {
            $query->where(
                'product_name',
                'like',
                '%' . $search . '%'
            );
        }


        // ========================================================
        // DATE PRESET FILTER
        // ========================================================

        if ($dateFilter === 'today') {

            $query->whereDate(
                'created_at',
                now()->toDateString()
            );
        }

        elseif ($dateFilter === 'this_week') {

            $query->whereBetween(
                'created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]
            );
        }

        elseif ($dateFilter === 'this_month') {

            $query->whereBetween(
                'created_at',
                [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]
            );
        }


        // ========================================================
        // CUSTOM DATE RANGE
        // ========================================================

        if ($fromDate) {

            $query->whereDate(
                'created_at',
                '>=',
                $fromDate
            );
        }

        if ($toDate) {

            $query->whereDate(
                'created_at',
                '<=',
                $toDate
            );
        }


        // ========================================================
        // CATEGORY FILTER
        // Existing: Multiple categories
        //
        // Example:
        // ?category=Electronics,Clothing
        // ========================================================

        if ($categoryFilter) {

            $categories = array_filter(
                array_map(
                    'trim',
                    explode(',', $categoryFilter)
                )
            );

            if (!empty($categories)) {

                $query->whereIn(
                    'category',
                    $categories
                );
            }
        }


        // ========================================================
        // NEW: MULTIPLE COLOR FILTER
        //
        // Example:
        // ?color=Red,Blue,Black
        // ========================================================

        if ($colorFilter) {

            $colors = array_filter(
                array_map(
                    'trim',
                    explode(',', $colorFilter)
                )
            );

            if (!empty($colors)) {

                $query->whereIn(
                    'color',
                    $colors
                );
            }
        }


        // ========================================================
        // NEW: MULTIPLE SIZE FILTER
        //
        // Example:
        // ?size=M,L,XL
        // ========================================================

        if ($sizeFilter) {

            $sizes = array_filter(
                array_map(
                    'trim',
                    explode(',', $sizeFilter)
                )
            );

            if (!empty($sizes)) {

                $query->whereIn(
                    'size',
                    $sizes
                );
            }
        }


        // ========================================================
        // PRICE RANGE
        // ========================================================

        if ($minPrice !== null && $minPrice !== '') {

            $query->where(
                'price',
                '>=',
                $minPrice
            );
        }

        if ($maxPrice !== null && $maxPrice !== '') {

            $query->where(
                'price',
                '<=',
                $maxPrice
            );
        }


        // ========================================================
        // EXACT PRICE
        // ========================================================

        if ($exactPrice !== null && $exactPrice !== '') {

            $query->where(
                'price',
                $exactPrice
            );
        }


        // ========================================================
        // NEW: DYNAMIC SORTING
        //
        // sort_by:
        // id
        // name
        // price
        // created_at
        //
        // sort_order:
        // asc
        // desc
        // ========================================================

        if ($sortBy) {

            $sortColumn = match ($sortBy) {

                'id' => 'id',

                'name' => 'product_name',

                'price' => 'price',

                'created_at' => 'created_at',

                default => 'created_at',
            };

            $query->orderBy(
                $sortColumn,
                $sortOrder
            );
        }

        // ========================================================
        // EXISTING ID SORTING
        //
        // Only use this if dynamic sort_by isn't provided.
        // ========================================================

        elseif ($idSort === 'low_high') {

            $query->orderBy(
                'id',
                'asc'
            );
        }

        elseif ($idSort === 'high_low') {

            $query->orderBy(
                'id',
                'desc'
            );
        }

        // ========================================================
        // EXISTING PRICE SORTING
        // ========================================================

        elseif ($priceSort === 'low_high') {

            $query->orderBy(
                'price',
                'asc'
            );
        }

        elseif ($priceSort === 'high_low') {

            $query->orderBy(
                'price',
                'desc'
            );
        }

        // ========================================================
        // DEFAULT SORT
        // ========================================================

        else {

            $query->latest();
        }


        // ========================================================
        // PAGINATION
        // ========================================================

        $products = $query
            ->paginate($perPage)
            ->appends($request->query());


        // ========================================================
        // RESPONSE
        // ========================================================

        return response()->json([

            'status' => true,

            'filters' => [

                'search' => $search,

                'category' => $categoryFilter,

                'colors' => $colorFilter
                    ? array_filter(
                        array_map(
                            'trim',
                            explode(',', $colorFilter)
                        )
                    )
                    : [],

                'sizes' => $sizeFilter
                    ? array_filter(
                        array_map(
                            'trim',
                            explode(',', $sizeFilter)
                        )
                    )
                    : [],

                'min_price' => $minPrice,

                'max_price' => $maxPrice,

                'exact_price' => $exactPrice,

                'date' => $dateFilter,

                'from_date' => $fromDate,

                'to_date' => $toDate,
            ],

            'sorting' => [

                'sort_by' => $sortBy,

                'sort_order' => $sortBy
                    ? $sortOrder
                    : null,

                'price_sort' => $priceSort,

                'id_sort' => $idSort,
            ],

            'pagination' => [

                'current_page' => $products->currentPage(),

                'per_page' => $products->perPage(),

                'total' => $products->total(),

                'last_page' => $products->lastPage(),

                'from' => $products->firstItem(),

                'to' => $products->lastItem(),
            ],

            'data' => $products->items(),
        ]);
    }


    // ============================================================
    // API DOCUMENTATION
    // ============================================================

    public function apiDocs()
    {
        return response()->json([

            'status' => true,

            'message' => 'Product API Filters Documentation',

            'filters' => [

                'search' =>
                    'Search products by name. Example: ?search=laptop',

                'category' =>
                    'Filter by category. Multiple categories supported. Example: ?category=electronics,clothing',

                'color' =>
                    'Filter by color. Multiple colors supported. Example: ?color=Red,Blue,Black',

                'size' =>
                    'Filter by size. Multiple sizes supported. Example: ?size=M,L,XL',

                'min_price' =>
                    'Minimum price filter. Example: ?min_price=100',

                'max_price' =>
                    'Maximum price filter. Example: ?max_price=500',

                'price_eq' =>
                    'Exact price filter. Example: ?price_eq=299',

                'from_date' =>
                    'Products created from this date. Format: YYYY-MM-DD',

                'to_date' =>
                    'Products created until this date. Format: YYYY-MM-DD',

                'date' =>
                    'Date preset: today, this_week, this_month',

                'price' =>
                    'Price sorting: low_high, high_low',

                'id_sort' =>
                    'ID sorting: low_high, high_low',

                'per_page' =>
                    'Items per page. Allowed: 1-100. Default: 15',

                // NEW
                'sort_by' =>
                    'Dynamic sorting field: id, name, price, created_at',

                'sort_order' =>
                    'Dynamic sorting direction: asc or desc',
            ],

            'new_features' => [

                'multiple_color_filter' =>
                    'Use comma-separated colors. Example: ?color=Red,Blue,Black',

                'multiple_size_filter' =>
                    'Use comma-separated sizes. Example: ?size=M,L,XL',

                'dynamic_sorting' =>
                    'Use sort_by and sort_order together. Example: ?sort_by=name&sort_order=asc',
            ],

            'sorting' => [

                'id_sort' =>
                    'Sort by ID: low_high, high_low',

                'price' =>
                    'Sort by price: low_high, high_low',

                'sort_by' =>
                    'Dynamic field: id, name, price, created_at',

                'sort_order' =>
                    'Dynamic direction: asc, desc',
            ],

            'example_requests' => [

                'GET /api/products?category=electronics&min_price=100&max_price=500',

                'GET /api/products?search=laptop&price=low_high&per_page=10',

                'GET /api/products?color=red,blue&size=M,L',

                'GET /api/products?category=electronics,clothing&color=red,blue&size=M,L',

                'GET /api/products?sort_by=name&sort_order=asc',

                'GET /api/products?sort_by=price&sort_order=desc',

                'GET /api/products?sort_by=created_at&sort_order=desc',

                'GET /api/products?color=red,blue&size=M,L&min_price=100&max_price=1000&sort_by=name&sort_order=asc',
            ],

            'live_search' => [

                'GET /api/products/search?q=keyword' =>
                    'Returns up to 8 matching products for live suggestions',

                'GET /api/products/search/history' =>
                    'Returns recent 10 search history entries',

                'POST /api/products/search/history' =>
                    'Store a new search history entry',
            ],
        ]);
    }


    // ============================================================
    // API: STORE PRODUCT
    // ============================================================

    public function apiStore(Request $request)
    {
        $request->validate([
            'product_name' => 'required',
            'details'      => 'required',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'size'         => 'required',
            'color'        => 'required',
            'category'     => 'required',
            'price'        => 'required|numeric|min:0',
        ]);

        $imageName = null;

        if ($request->hasFile('image')) {

            $imageName =
                time() . '_' .
                $request->image->getClientOriginalName();

            $request->image->move(
                public_path('image'),
                $imageName
            );
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


    // ============================================================
    // API: SHOW SINGLE PRODUCT
    // ============================================================

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


    // ============================================================
    // API: UPDATE PRODUCT
    // ============================================================

    public function apiUpdate(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product Not Found'
            ], 404);
        }

        $request->validate([
            'product_name' => 'required',
            'details'      => 'required',
            'image'        => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'size'         => 'required',
            'color'        => 'required',
            'category'     => 'required',
            'price'        => 'required|numeric|min:0',
        ]);

        $imageName = $product->image;

        if ($request->hasFile('image')) {

            if (
                $product->image &&
                file_exists(
                    public_path(
                        'image/' . $product->image
                    )
                )
            ) {
                unlink(
                    public_path(
                        'image/' . $product->image
                    )
                );
            }

            $imageName =
                time() . '_' .
                $request->image->getClientOriginalName();

            $request->image->move(
                public_path('image'),
                $imageName
            );
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


    // ============================================================
    // API: DELETE PRODUCT
    // ============================================================

    public function apiDelete($id)
    {
        $product = Product::find($id);

        if (!$product) {

            return response()->json([
                'status' => false,
                'message' => 'Product Not Found'
            ], 404);
        }

        if (
            $product->image &&
            file_exists(
                public_path(
                    'image/' . $product->image
                )
            )
        ) {
            unlink(
                public_path(
                    'image/' . $product->image
                )
            );
        }

        $product->delete();

        return response()->json([
            'status' => true,
            'message' => 'Product Deleted Successfully'
        ]);
    }


    // ============================================================
    // API: LIVE SEARCH
    // ============================================================

    public function apiSearch(Request $request)
    {
        $keyword = $request->get('q');

        if (!$keyword || strlen($keyword) < 2) {

            return response()->json([
                'status' => true,
                'data' => [],
                'message' => 'Please type at least 2 characters'
            ]);
        }

        $products = Product::where(
            'product_name',
            'like',
            '%' . $keyword . '%'
        )
            ->limit(8)
            ->get();

        return response()->json([
            'status' => true,
            'keyword' => $keyword,
            'data' => $products
        ]);
    }


    // ============================================================
    // API: SEARCH HISTORY
    // ============================================================

    public function apiSearchHistory(Request $request)
    {
        $action = $request->get(
            'action',
            'list'
        );

        if ($action === 'store') {

            $keyword = $request->get('keyword');

            if ($keyword) {

                SearchHistory::create([
                    'keyword' =>
                        $keyword,

                    'results_count' =>
                        $request->get(
                            'results_count',
                            0
                        ),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Search history saved'
            ]);
        }

        $histories = SearchHistory::orderBy(
            'created_at',
            'desc'
        )
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $histories
        ]);
    }
}
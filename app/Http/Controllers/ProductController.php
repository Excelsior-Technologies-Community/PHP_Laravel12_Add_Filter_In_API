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

    /**
     * Show all products on web page
     */
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

            $products->whereIn(
                'category',
                $categories
            );
        }

        if ($color) {
            $products->where(
                'color',
                $color
            );
        }

        if ($size) {
            $products->where(
                'size',
                $size
            );
        }

        if ($minPrice !== null && $minPrice !== '') {
            $products->where(
                'price',
                '>=',
                $minPrice
            );
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $products->where(
                'price',
                '<=',
                $maxPrice
            );
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

    /**
     * Show create form
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store product from web
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required',
            'details' => 'required',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'size' => 'required',
            'color' => 'required',
            'category' => 'required',
            'price' => 'required|numeric|min:0',
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
            'details' => $request->details,
            'image' => $imageName,
            'size' => $request->size,
            'color' => $request->color,
            'category' => $request->category,
            'price' => $request->price,

            // New fields
            'stock' => $request->get('stock', 0),
            'status' => $request->get('status', 'active'),
            'featured' => $request->boolean('featured'),
            'discount_percent' => $request->get('discount_percent', 0),
        ]);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product Created Successfully'
            );
    }

    /**
     * Edit product
     */
    public function edit(Product $product)
    {
        return view(
            'products.edit',
            compact('product')
        );
    }

    /**
     * Update product from web
     */
    public function update(
        Request $request,
        Product $product
    ) {
        $request->validate([
            'product_name' => 'required',
            'details' => 'required',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'size' => 'required',
            'color' => 'required',
            'category' => 'required',
            'price' => 'required|numeric|min:0',
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
            'details' => $request->details,
            'image' => $imageName,
            'size' => $request->size,
            'color' => $request->color,
            'category' => $request->category,
            'price' => $request->price,

            // New fields
            'stock' => $request->get(
                'stock',
                $product->stock
            ),

            'status' => $request->get(
                'status',
                $product->status
            ),

            'featured' => $request->has('featured')
                ? $request->boolean('featured')
                : $product->featured,

            'discount_percent' => $request->get(
                'discount_percent',
                $product->discount_percent
            ),
        ]);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product Updated Successfully'
            );
    }

    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
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

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product Deleted Successfully'
            );
    }


    // ============================================================
    // API: PRODUCT LIST
    // ============================================================

    /**
     * API:
     *
     * Existing:
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
     * - stock
     * - low_stock_limit
     * - status
     * - featured
     */
    public function apiIndex(Request $request)
    {
        // ========================================================
        // PARAMETERS
        // ========================================================

        $priceSort = $request->get('price');

        $dateFilter = $request->get('date');

        $idSort = $request->get('id_sort');

        $categoryFilter = $request->get('category');

        $colorFilter = $request->get('color');

        $sizeFilter = $request->get('size');

        $search = $request->get('search');

        $minPrice = $request->get('min_price');

        $maxPrice = $request->get('max_price');

        $exactPrice = $request->get('price_eq');

        $fromDate = $request->get('from_date');

        $toDate = $request->get('to_date');

        // Dynamic sorting

        $sortBy = $request->get('sort_by');

        $sortOrder = $request->get(
            'sort_order',
            'asc'
        );

        // New filters

        $stockFilter = $request->get('stock');

        $lowStockLimit = $request->get(
            'low_stock_limit',
            5
        );

        $statusFilter = $request->get('status');

        $featured = $request->get('featured');


        // ========================================================
        // VALIDATION
        // ========================================================

        $request->validate([

            'price' =>
                'nullable|in:low_high,high_low',

            'date' =>
                'nullable|in:today,this_week,this_month',

            'id_sort' =>
                'nullable|in:low_high,high_low',

            'min_price' =>
                'nullable|numeric|min:0',

            'max_price' =>
                'nullable|numeric|min:0',

            'price_eq' =>
                'nullable|numeric|min:0',

            'from_date' =>
                'nullable|date_format:Y-m-d',

            'to_date' =>
                'nullable|date_format:Y-m-d',

            'per_page' =>
                'nullable|integer|min:1|max:100',

            'sort_by' =>
                'nullable|in:id,name,price,created_at,stock',

            'sort_order' =>
                'nullable|in:asc,desc',

            // New

            'stock' =>
                'nullable|in:in_stock,out_of_stock,low_stock',

            'low_stock_limit' =>
                'nullable|integer|min:1',

            'status' =>
                'nullable|in:active,inactive',

            'featured' =>
                'nullable|in:true,false,1,0',
        ]);


        // ========================================================
        // PAGINATION
        // ========================================================

        $perPage = $request->get(
            'per_page',
            15
        );


        // ========================================================
        // QUERY
        // ========================================================

        $query = Product::query();


        // ========================================================
        // SEARCH
        // ========================================================

        if ($search) {

            $query->where(
                'product_name',
                'like',
                '%' . $search . '%'
            );
        }


        // ========================================================
        // DATE PRESET
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
        // CATEGORY
        // ========================================================

        if ($categoryFilter) {

            $categories = array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        $categoryFilter
                    )
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
        // MULTIPLE COLORS
        // ========================================================

        if ($colorFilter) {

            $colors = array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        $colorFilter
                    )
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
        // MULTIPLE SIZES
        // ========================================================

        if ($sizeFilter) {

            $sizes = array_filter(
                array_map(
                    'trim',
                    explode(
                        ',',
                        $sizeFilter
                    )
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
        // FEATURED FILTER
        // ========================================================

        if ($featured !== null) {

            $featuredValue = filter_var(
                $featured,
                FILTER_VALIDATE_BOOLEAN
            );

            $query->where(
                'featured',
                $featuredValue
            );
        }


        // ========================================================
        // STATUS FILTER
        // ========================================================

        if ($statusFilter !== null) {

            $query->where(
                'status',
                $statusFilter
            );
        }


        // ========================================================
        // STOCK FILTER
        // ========================================================

        if ($stockFilter === 'in_stock') {

            $query->where(
                'stock',
                '>',
                0
            );
        }

        elseif ($stockFilter === 'out_of_stock') {

            $query->where(
                'stock',
                '=',
                0
            );
        }

        elseif ($stockFilter === 'low_stock') {

            $query->where(
                'stock',
                '>',
                0
            );

            $query->where(
                'stock',
                '<=',
                $lowStockLimit
            );
        }


        // ========================================================
        // PRICE RANGE
        // ========================================================

        if (
            $minPrice !== null &&
            $minPrice !== ''
        ) {

            $query->where(
                'price',
                '>=',
                $minPrice
            );
        }

        if (
            $maxPrice !== null &&
            $maxPrice !== ''
        ) {

            $query->where(
                'price',
                '<=',
                $maxPrice
            );
        }


        // ========================================================
        // EXACT PRICE
        // ========================================================

        if (
            $exactPrice !== null &&
            $exactPrice !== ''
        ) {

            $query->where(
                'price',
                $exactPrice
            );
        }


        // ========================================================
        // DYNAMIC SORTING
        // ========================================================

        if ($sortBy) {

            $sortColumn = match ($sortBy) {

                'id' =>
                    'id',

                'name' =>
                    'product_name',

                'price' =>
                    'price',

                'stock' =>
                    'stock',

                'created_at' =>
                    'created_at',

                default =>
                    'created_at',
            };

            $query->orderBy(
                $sortColumn,
                $sortOrder
            );
        }

        // ========================================================
        // ID SORT
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
        // PRICE SORT
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
        // DEFAULT
        // ========================================================

        else {

            $query->latest();
        }


        // ========================================================
        // PAGINATION
        // ========================================================

        $products = $query
            ->paginate($perPage)
            ->appends(
                $request->query()
            );


        // ========================================================
        // ADD CALCULATED VALUES
        // ========================================================

        $products->getCollection()
            ->transform(
                function ($product) {

                    $price = (float) $product->price;

                    $discount = (float) (
                        $product->discount_percent ?? 0
                    );

                    $finalPrice =
                        $price -
                        (
                            $price *
                            $discount /
                            100
                        );

                    $product->final_price =
                        round(
                            $finalPrice,
                            2
                        );

                    $product->in_stock =
                        (int) ($product->stock ?? 0) > 0;

                    $product->low_stock =
                        (int) ($product->stock ?? 0) > 0 &&
                        (int) ($product->stock ?? 0) <= 5;

                    return $product;
                }
            );


        // ========================================================
        // RESPONSE
        // ========================================================

        return response()->json([

            'status' => true,

            'filters' => [

                'search' =>
                    $search,

                'category' =>
                    $categoryFilter,

                'colors' =>
                    $colorFilter
                        ? array_values(
                            array_filter(
                                array_map(
                                    'trim',
                                    explode(
                                        ',',
                                        $colorFilter
                                    )
                                )
                            )
                        )
                        : [],

                'sizes' =>
                    $sizeFilter
                        ? array_values(
                            array_filter(
                                array_map(
                                    'trim',
                                    explode(
                                        ',',
                                        $sizeFilter
                                    )
                                )
                            )
                        )
                        : [],

                'featured' =>
                    $featured,

                'status' =>
                    $statusFilter,

                'stock' =>
                    $stockFilter,

                'low_stock_limit' =>
                    $lowStockLimit,

                'min_price' =>
                    $minPrice,

                'max_price' =>
                    $maxPrice,

                'exact_price' =>
                    $exactPrice,

                'date' =>
                    $dateFilter,

                'from_date' =>
                    $fromDate,

                'to_date' =>
                    $toDate,
            ],

            'sorting' => [

                'sort_by' =>
                    $sortBy,

                'sort_order' =>
                    $sortBy
                        ? $sortOrder
                        : null,

                'price_sort' =>
                    $priceSort,

                'id_sort' =>
                    $idSort,
            ],

            'pagination' => [

                'current_page' =>
                    $products->currentPage(),

                'per_page' =>
                    $products->perPage(),

                'total' =>
                    $products->total(),

                'last_page' =>
                    $products->lastPage(),

                'from' =>
                    $products->firstItem(),

                'to' =>
                    $products->lastItem(),
            ],

            'data' =>
                $products->items(),
        ]);
    }


    // ============================================================
    // API: DOCUMENTATION
    // ============================================================

    public function apiDocs()
    {
        return response()->json([

            'status' => true,

            'message' =>
                'Product API Filters Documentation',

            'filters' => [

                'search' =>
                    'Search products by name. Example: ?search=laptop',

                'category' =>
                    'Multiple categories. Example: ?category=electronics,clothing',

                'color' =>
                    'Multiple colors. Example: ?color=Red,Blue,Black',

                'size' =>
                    'Multiple sizes. Example: ?size=M,L,XL',

                'min_price' =>
                    'Minimum price',

                'max_price' =>
                    'Maximum price',

                'price_eq' =>
                    'Exact price',

                'date' =>
                    'today, this_week, this_month',

                'from_date' =>
                    'YYYY-MM-DD',

                'to_date' =>
                    'YYYY-MM-DD',

                'price' =>
                    'low_high, high_low',

                'id_sort' =>
                    'low_high, high_low',

                'per_page' =>
                    '1-100',

                'sort_by' =>
                    'id, name, price, stock, created_at',

                'sort_order' =>
                    'asc, desc',

                // New

                'stock' =>
                    'in_stock, out_of_stock, low_stock',

                'low_stock_limit' =>
                    'Custom low stock limit',

                'status' =>
                    'active, inactive',

                'featured' =>
                    'true, false',
            ],

            'new_features' => [

                'stock_management' =>
                    'Filter and update product stock',

                'status_management' =>
                    'Filter and toggle active/inactive status',

                'featured_products' =>
                    'Filter and toggle featured products',

                'discount_pricing' =>
                    'Calculate final price using discount percentage',

                'product_statistics' =>
                    'Get product statistics',
            ],

            'example_requests' => [

                'GET /api/products?featured=true',

                'GET /api/products?featured=false',

                'GET /api/products?status=active',

                'GET /api/products?stock=in_stock',

                'GET /api/products?stock=out_of_stock',

                'GET /api/products?stock=low_stock',

                'GET /api/products?stock=low_stock&low_stock_limit=10',

                'GET /api/products?sort_by=stock&sort_order=asc',

                'GET /api/products?sort_by=price&sort_order=desc',

                'GET /api/products?search=phone&status=active&featured=true',

                'GET /api/products?featured=true&stock=in_stock&status=active',

                'GET /api/products?min_price=1000&max_price=50000',

                'GET /api/products/statistics',
            ],
        ]);
    }


    // ============================================================
    // API: PRODUCT STATISTICS
    // ============================================================

    public function statistics()
    {
        $totalProducts =
            Product::count();

        $activeProducts =
            Product::where(
                'status',
                'active'
            )->count();

        $inactiveProducts =
            Product::where(
                'status',
                'inactive'
            )->count();

        $featuredProducts =
            Product::where(
                'featured',
                true
            )->count();

        $totalStock =
            Product::sum('stock');

        $outOfStock =
            Product::where(
                'stock',
                0
            )->count();

        $lowStock =
            Product::where(
                'stock',
                '>',
                0
            )
            ->where(
                'stock',
                '<=',
                5
            )
            ->count();

        $averagePrice =
            Product::avg('price');

        return response()->json([

            'status' => true,

            'data' => [

                'total_products' =>
                    $totalProducts,

                'active_products' =>
                    $activeProducts,

                'inactive_products' =>
                    $inactiveProducts,

                'featured_products' =>
                    $featuredProducts,

                'total_stock' =>
                    (int) $totalStock,

                'out_of_stock' =>
                    $outOfStock,

                'low_stock' =>
                    $lowStock,

                'average_price' =>
                    round(
                        (float) ($averagePrice ?? 0),
                        2
                    ),
            ],
        ]);
    }


    // ============================================================
    // API: STORE PRODUCT
    // ============================================================

    public function apiStore(Request $request)
    {
        $request->validate([

            'product_name' =>
                'required|string|max:255',

            'details' =>
                'required|string',

            'image' =>
                'nullable|image|mimes:jpg,png,jpeg|max:2048',

            'size' =>
                'required|string',

            'color' =>
                'required|string',

            'category' =>
                'required|string',

            'price' =>
                'required|numeric|min:0',

            'stock' =>
                'nullable|integer|min:0',

            'status' =>
                'nullable|in:active,inactive',

            'featured' =>
                'nullable|boolean',

            'discount_percent' =>
                'nullable|numeric|min:0|max:100',
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

            'product_name' =>
                $request->product_name,

            'details' =>
                $request->details,

            'image' =>
                $imageName,

            'size' =>
                $request->size,

            'color' =>
                $request->color,

            'category' =>
                $request->category,

            'price' =>
                $request->price,

            'stock' =>
                $request->get(
                    'stock',
                    0
                ),

            'status' =>
                $request->get(
                    'status',
                    'active'
                ),

            'featured' =>
                $request->boolean(
                    'featured'
                ),

            'discount_percent' =>
                $request->get(
                    'discount_percent',
                    0
                ),
        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Product Created Successfully',

            'data' =>
                $product,

        ], 201);
    }


    // ============================================================
    // API: SHOW SINGLE PRODUCT
    // ============================================================

    public function apiShow($id)
    {
        $product =
            Product::find($id);

        if (!$product) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Product Not Found',

            ], 404);
        }

        $price =
            (float) $product->price;

        $discount =
            (float) (
                $product->discount_percent ?? 0
            );

        $finalPrice =
            $price -
            (
                $price *
                $discount /
                100
            );

        $product->final_price =
            round(
                $finalPrice,
                2
            );

        $product->in_stock =
            (int) ($product->stock ?? 0) > 0;

        $product->low_stock =
            (int) ($product->stock ?? 0) > 0 &&
            (int) ($product->stock ?? 0) <= 5;

        return response()->json([

            'status' => true,

            'data' =>
                $product,

        ]);
    }


    // ============================================================
    // API: UPDATE PRODUCT
    // ============================================================

    public function apiUpdate(
        Request $request,
        $id
    ) {
        $product =
            Product::find($id);

        if (!$product) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Product Not Found',

            ], 404);
        }

        $request->validate([

            'product_name' =>
                'required|string|max:255',

            'details' =>
                'required|string',

            'image' =>
                'nullable|image|mimes:jpg,png,jpeg|max:2048',

            'size' =>
                'required|string',

            'color' =>
                'required|string',

            'category' =>
                'required|string',

            'price' =>
                'required|numeric|min:0',

            'stock' =>
                'nullable|integer|min:0',

            'status' =>
                'nullable|in:active,inactive',

            'featured' =>
                'nullable|boolean',

            'discount_percent' =>
                'nullable|numeric|min:0|max:100',
        ]);

        $imageName =
            $product->image;

        if ($request->hasFile('image')) {

            if (
                $product->image &&
                file_exists(
                    public_path(
                        'image/' .
                        $product->image
                    )
                )
            ) {

                unlink(
                    public_path(
                        'image/' .
                        $product->image
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

            'product_name' =>
                $request->product_name,

            'details' =>
                $request->details,

            'image' =>
                $imageName,

            'size' =>
                $request->size,

            'color' =>
                $request->color,

            'category' =>
                $request->category,

            'price' =>
                $request->price,

            'stock' =>
                $request->has('stock')
                    ? $request->stock
                    : $product->stock,

            'status' =>
                $request->has('status')
                    ? $request->status
                    : $product->status,

            'featured' =>
                $request->has('featured')
                    ? $request->boolean(
                        'featured'
                    )
                    : $product->featured,

            'discount_percent' =>
                $request->has(
                    'discount_percent'
                )
                    ? $request->discount_percent
                    : $product->discount_percent,
        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Product Updated Successfully',

            'data' =>
                $product->fresh(),

        ]);
    }


    // ============================================================
    // API: DELETE PRODUCT
    // ============================================================

    public function apiDelete($id)
    {
        $product =
            Product::find($id);

        if (!$product) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Product Not Found',

            ], 404);
        }

        if (
            $product->image &&
            file_exists(
                public_path(
                    'image/' .
                    $product->image
                )
            )
        ) {

            unlink(
                public_path(
                    'image/' .
                    $product->image
                )
            );
        }

        $product->delete();

        return response()->json([

            'status' => true,

            'message' =>
                'Product Deleted Successfully',

        ]);
    }


    // ============================================================
    // API: UPDATE STOCK
    // ============================================================

    public function updateStock(
        Request $request,
        $id
    ) {
        $product =
            Product::find($id);

        if (!$product) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Product Not Found',

            ], 404);
        }

        $request->validate([

            'stock' =>
                'required|integer|min:0',

        ]);

        $product->update([

            'stock' =>
                $request->stock,

        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Stock updated successfully',

            'data' => [

                'id' =>
                    $product->id,

                'stock' =>
                    $product->stock,

                'in_stock' =>
                    $product->stock > 0,

                'low_stock' =>
                    $product->stock > 0 &&
                    $product->stock <= 5,
            ],

        ]);
    }


    // ============================================================
    // API: TOGGLE STATUS
    // ============================================================

    public function toggleStatus($id)
    {
        $product =
            Product::find($id);

        if (!$product) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Product Not Found',

            ], 404);
        }

        $newStatus =
            $product->status === 'active'
                ? 'inactive'
                : 'active';

        $product->update([

            'status' =>
                $newStatus,

        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Product status updated successfully',

            'data' => [

                'id' =>
                    $product->id,

                'status' =>
                    $product->status,
            ],

        ]);
    }


    // ============================================================
    // API: TOGGLE FEATURED
    // ============================================================

    public function toggleFeatured($id)
    {
        $product =
            Product::find($id);

        if (!$product) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Product Not Found',

            ], 404);
        }

        $product->update([

            'featured' =>
                !$product->featured,

        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Featured status updated successfully',

            'data' => [

                'id' =>
                    $product->id,

                'featured' =>
                    (bool) $product->featured,
            ],

        ]);
    }


    // ============================================================
    // API: LIVE SEARCH
    // ============================================================

    public function apiSearch(
        Request $request
    ) {
        $keyword =
            $request->get('q');

        if (
            !$keyword ||
            strlen($keyword) < 2
        ) {

            return response()->json([

                'status' => true,

                'data' => [],

                'message' =>
                    'Please type at least 2 characters',

            ]);
        }

        $products =
            Product::where(
                'product_name',
                'like',
                '%' . $keyword . '%'
            )
            ->limit(8)
            ->get();

        return response()->json([

            'status' => true,

            'keyword' =>
                $keyword,

            'data' =>
                $products,

        ]);
    }


    // ============================================================
    // API: SEARCH HISTORY
    // ============================================================

    public function apiSearchHistory(
        Request $request
    ) {
        $action =
            $request->get(
                'action',
                'list'
            );

        if ($action === 'store') {

            $keyword =
                $request->get('keyword');

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

                'message' =>
                    'Search history saved',

            ]);
        }

        $histories =
            SearchHistory::orderBy(
                'created_at',
                'desc'
            )
            ->limit(10)
            ->get();

        return response()->json([

            'status' => true,

            'data' =>
                $histories,

        ]);
    }
}
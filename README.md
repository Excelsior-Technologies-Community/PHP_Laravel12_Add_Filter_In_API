# PHP_Laravel12_Add_Filter_In_API


# STEP 1: Install Laravel 12 Project
Open terminal / command prompt and run:
```php
composer create-project laravel/laravel your folder name
```
Explanation:
This command installs a fresh Laravel 12 project
Project folder name will be your folder name 

# STEP 2: Go to Project Directory
```php
cd your folder
```
# STEP 3: Database Configuration
```php
Open .env file and update database details:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your database
DB_USERNAME=root
DB_PASSWORD=
```
Explanation:
Connects Laravel project to MySQL database
Make sure database name  exists in phpMyAdmin




# Now Adding for filtering in price ,id's shorting and date filtering
# Now added route for routes/api.php file
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// 🔹 PRODUCT API ROUTES
Route::get('/products', [ProductController::class, 'apiIndex']);
Route::post('/products', [ProductController::class, 'apiStore']);
Route::get('/products/{id}', [ProductController::class, 'apiShow']);
Route::post('/products/{id}', [ProductController::class, 'apiUpdate']);
Route::delete('/products/{id}', [ProductController::class, 'apiDelete']);
```

# Now Create for api crud method and used price , id’s and date filtering for existing productcontroller and added this method
```php
// ============================================================
//  API METHODS (JSON Responses for Mobile Apps/Postman)
// ============================================================

/**
 *  API: GET ALL PRODUCTS
 * 
 * URL: GET /api/products
 * 
 * Response:
 * {
 *   "status": true,
 *   "data": [ {products array} ]
 * }
 * 
 * Returns all products ordered by newest first (latest()->get())
 * No image upload - just database query
 */
public function apiIndex(Request $request)
{
    $products = Product::latest()->get();
    return response()->json([
        'status' => true,
        'data' => $products
    ]);
}

/**
 *  API: CREATE NEW PRODUCT
 * 
 * URL: POST /api/products
 * Headers: Content-Type: multipart/form-data
 * 
 * Request Body (Form Data):
 * - product_name (required)
 * - details (required) 
 * - image (optional file)
 * - size (required)
 * - color (required)
 * - category (required)
 * 
 * ========================================
 * COMPLETE IMAGE UPLOAD PROCESS:
 * ========================================
 * 1. Client sends image file via POST
 * 2. Laravel stores in TEMP: /tmp/phpXXXXX.jpg
 * 3. Validate: image|mimes:jpg,png,jpeg|max:2048 (2MB)
 * 4. Generate unique name: time()_original.jpg
 * 5. Move to PERMANENT: public/image/newname.jpg
 * 6. Save filename to database
 * 7. Image accessible: http://yoursite.com/image/filename.jpg
 */
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

}
```
# Now Open the postman and run this method and get all data
# Open postman 
# Select method :Get
# Paste this url : http://127.0.0.1:8000/api/products

<img width="628" height="67" alt="image" src="https://github.com/user-attachments/assets/a96d210a-4706-4026-86d4-a99cc79cba44" />

# Now Adding Price shorting , id’s sorting and date sorting for high to low and low to high


# Update api index method :
```php
public function apiIndex(Request $request)
{
    $priceSort = $request->price;       // low_high / high_low
    $dateFilter = $request->date;       // today / this_week / this_month
    $idSort = $request->id_sort;        // low_high / high_low

    $query = Product::query();

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
    //  ID SORTING (Highest Priority)
    // -----------------------------------------
    if ($idSort == 'low_high') {
        $query->orderBy('id', 'asc');   // 1,2,3,4,5
    }
    elseif ($idSort == 'high_low') {
        $query->orderBy('id', 'desc');  // 5,4,3,2,1
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
    //  FETCH DATA
    // -----------------------------------------
    $products = $query->get();

    return response()->json([
        'status' => true,
        'price_sort' => $priceSort,
        'date_filter' => $dateFilter,
        'id_sort' => $idSort,
        'data' => $products
    ]);
}
```

# Now this method is run the postman
# Select  method  for:Get
# Paste this url : http://127.0.0.1:8000/api/products
<img width="628" height="67" alt="image" src="https://github.com/user-attachments/assets/d1a39e33-6312-41c5-8084-fc6d2acce59e" />

# Now  params used for postman in price, id’s and date 
<img width="628" height="230" alt="image" src="https://github.com/user-attachments/assets/69818ef0-ac44-42bd-b515-f21dc7bc3130" />

# Now Click Send button and show the result:

<img width="628" height="164" alt="image" src="https://github.com/user-attachments/assets/12d93cfb-34cd-49d9-bc2d-f102f79efda6" />

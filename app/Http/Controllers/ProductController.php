<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // 🔹 Show all products
    public function index()
    {
        $products = Product::latest()->get();
        return view('products.index', compact('products'));
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

    // 🔹 API: Get all products
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

}

<h2>Edit Product</h2>

{{-- SUCCESS MESSAGE --}}
@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<form action="{{ route('products.update', $product->id) }}" 
      method="POST" 
      enctype="multipart/form-data">

    @csrf
    @method('PUT')

    {{-- PRODUCT NAME --}}
    <input 
        type="text" 
        name="product_name" 
        value="{{ $product->product_name }}" 
        placeholder="Product Name">
    <br><br>

    {{-- DETAILS --}}
    <textarea 
        name="details" 
        placeholder="Details">{{ $product->details }}</textarea>
    <br><br>

    {{-- CURRENT IMAGE --}}
  @if($product->image)
    <img src="{{ asset('image/'.$product->image) }}" width="100">
@endif


    {{-- NEW IMAGE --}}
    <input type="file" name="image">
    <br><br>

    {{-- SIZE --}}
    <input 
        type="text" 
        name="size" 
        value="{{ $product->size }}" 
        placeholder="Size">
    <br><br>

    {{-- COLOR --}}
    <input 
        type="text" 
        name="color" 
        value="{{ $product->color }}" 
        placeholder="Color">
    <br><br>

    {{-- CATEGORY --}}
    <input 
        type="text" 
        name="category" 
        value="{{ $product->category }}" 
        placeholder="Category">
    <br><br>
    <input 
        type="text" 
        name="price" 
        value="{{ $product->price }}" 
        placeholder="Price"><br>

    <button type="submit">Update Product</button>
</form>

<br>
<a href="{{ route('products.index') }}">⬅ Back to Product List</a>

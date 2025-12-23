<h2>Products</h2>

<a href="{{ route('products.create') }}">Add Product</a>

@if(session('success'))
    <p>{{ session('success') }}</p>
@endif

<table border="1" cellpadding="10">
<tr>
    <th>Name</th>
    <th>Image</th>
    <th>Size</th>
    <th>Color</th>
    <th>Category</th>
    <th>Price</th>
    <th>Action</th>
</tr>

@foreach($products as $product)
<tr>
    <td>{{ $product->product_name }}</td>
    <td>
       @if($product->image)
    <img src="{{ asset('image/'.$product->image) }}" width="80">
@endif
    </td>
    <td>{{ $product->size }}</td>
    <td>{{ $product->color }}</td>
    <td>{{ $product->category }}</td>
    <td>{{ $product->price }}</td>
    <td>
        <a href="{{ route('products.edit',$product->id) }}">Edit</a>

        <form action="{{ route('products.destroy',$product->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit">Delete</button>
        </form>
    </td>
</tr>
@endforeach

</table>

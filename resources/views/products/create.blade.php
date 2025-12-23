<h2>Add Product</h2>

<form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<input type="text" name="product_name" placeholder="Product Name"><br><br>
<textarea name="details" placeholder="Details"></textarea><br><br>
<input type="text" name="price" placeholder="Price"><br><br>

<input type="file" name="image"><br><br>

<input type="text" name="size" placeholder="Size"><br><br>
<input type="text" name="color" placeholder="Color"><br><br>
<input type="text" name="category" placeholder="Category"><br><br>

<button type="submit">Save</button>
</form>

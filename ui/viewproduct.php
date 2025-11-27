<?php


include_once 'connectdb.php';
session_start();


include_once"header.php";


?>


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <!-- <h1 class="m-0">Admin Dashboard</h1> -->
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
             <!-- <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Starter Page</li> -->
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-12">
            

          <div class="card card-info card-outline">
              <div class="card-header">
                <h5 class="m-0">View Product</h5>
              </div>
              <div class="card-body">

<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$select = $pdo->prepare("SELECT * FROM tbl_product WHERE pid = :id");
$select->bindParam(':id', $id, PDO::PARAM_INT);
$select->execute();

if($row = $select->fetch(PDO::FETCH_OBJ)){

    // Prepare safe/display values
    $productCode = htmlspecialchars($row->product);
    $category = htmlspecialchars($row->category);
    $description = htmlspecialchars($row->description);
    $servicetype = htmlspecialchars($row->servicetype);
    $additionalfee = htmlspecialchars($row->additionalfee);
    $purchaseprice = htmlspecialchars($row->purchaseprice);
    $saleprice = htmlspecialchars($row->saleprice);
    $profit = $saleprice - $purchaseprice;
    $image = htmlspecialchars($row->image);

    // New fields
    $stock = isset($row->stock) ? intval($row->stock) : 0;
    $brand = !empty($row->brand) ? htmlspecialchars($row->brand) : 'N/A';
    $expiryDisplay = (!empty($row->expirydate) && $row->expirydate != '0000-00-00') ? date('F j, Y', strtotime($row->expirydate)) : 'N/A';

echo '

<div class="row">
<div class="col-md-6">

<ul class="list-group">

<center><p class="list-group-item list-group-item-info"><b>PRODUCT DETAILS</b></p></center>

   <li class="list-group-item"><b>Product Code</b><span class="badge badge-warning float-right">'.$productCode.'</span></li>
  <li class="list-group-item"><b>Category</b><span class="badge badge-success float-right">'.$category.'</span></li>
  <li class="list-group-item"><b>Description</b><span class="badge badge-primary float-right">'.$description.'</span></li>
  <li class="list-group-item"><b>Service Type</b><span class="badge badge-secondary float-right">'.$servicetype.'</span></li>
  <li class="list-group-item"><b>Additional Fee</b><span class="badge badge-danger float-right">'.$additionalfee.'</span></li>
  <li class="list-group-item"><b>Purchase Price</b><span class="badge badge-secondary float-right">'.$purchaseprice.'</span></li>
  <li class="list-group-item"><b>Sale Price</b><span class="badge badge-dark float-right">'.$saleprice.'</span></li>
  

  <!-- New fields displayed -->
  <li class="list-group-item"><b>Stock</b><span class="badge badge-info float-right">'.$stock.'</span></li>
  <li class="list-group-item"><b>Brand</b><span class="badge badge-light float-right">'.$brand.'</span></li>
  <li class="list-group-item"><b>Expiry Date</b><span class="badge badge-secondary float-right">'.$expiryDisplay.'</span></li>
<li class="list-group-item"><b>Product Profit</b><span class="badge badge-success float-right">'.$profit.'</span></li>
</ul>

</div>


<div class="col-md-6">

<ul class="list-group">

<center><p class="list-group-item list-group-item-info"><b>PRODUCT IMAGE</b></p></center>

<img src="productimages/'.$image.'" class="img-responsive" alt="Product Image"/>

</ul>

</div>
</div>


';

} else {
    echo '<div class="alert alert-warning">Product not found.</div>';
}





?>





              </div>
            </div>
          
            

           
          </div>
          <!-- /.col-md-6 -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
 
 
 <?php

include_once"footer.php";


?>

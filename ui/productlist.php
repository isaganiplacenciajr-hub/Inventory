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
            

          <div class="card card-primary card-outline">
              <div class="card-header">
                <h5 class="m-0">Product List</h5>
              </div>
              <div class="card-body">
              

              <table class="table table-striped table-hover" id="table_product">
  <thead> 
<tr>

  <td>Product Code</td>
  <td>Category</td>
  <td>Description</td>
  <td>Service Type</td>
  <td>Additional Fee</td>
  <td>Purchase Price</td>
  <td>Sale Price</td>
  <td>Product Image</td>
  <td>Action Icons</td>
 

</tr>

</thead>




<tbody>
<?php 

$select = $pdo->prepare("select * from tbl_product order by pid ASC");
$select->execute();

while($row=$select->fetch(PDO::FETCH_OBJ))

{

  echo'
  <tr>
  <td>'.$row->product.'</td>
  <td>'.$row->category.'</td>
  <td>'.$row->description.'</td>
  <td>'.$row->servicetype.'</td>
  <td>'.$row->additionalfee.'</td>
  <td>'.$row->purchaseprice.'</td>
  <td>'.$row->saleprice.'</td>
  <td><img src="productimages/'.$row->image.'" class="img-rounded" width="40px"/></td>


  <td>
<div class="btn-group">


<a href="viewproduct.php?id='.$row->pid.'" class="btn btn-warning btn-xs" role="button"><span class="fa fa-eye" style="color:#ffffff" data-toggle="tooltip" title="View Product"></span> </a>



<button id="'.$row->pid.'"class="btn btn-danger btn-xs btndelete"><span class="fa fa-trash" style="color:#ffffff" data-toggle="tooltip" title="Delete Product"></span> </button>



</div>

</td>

</tr>';

}

?>


  </script>



</table>




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



<script>

$(document).ready(function () {     
    $('#table_product').DataTable();
} );


</script>


<script>

$(document).ready(function () {     
   $('[data-toggle="tooltip"]').tooltip();
} );


</script>


<script>

$(document).ready(function(){
  $('.btndelete').click( function() {
    var tdh = $(this);
    var id = $(this).attr("id");

    Swal.fire({
    title: "Do you Want to Delete?",
    text: "You Won't Able to Revert This",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes"
    }).then((result) => {
      if (result.isConfirmed) {

    $.ajax({

      url: 'productdelete.php',
      type: "post",
      data: {
      pidd: id
      },
      success: function(data) {
      tdh.parents('tr').hide();
      }

      });

        Swal.fire({
          title: "Deleted!",
          text: "Product deleted successfully",
          icon: "success"
        });
      }
    });

});

});

</script>


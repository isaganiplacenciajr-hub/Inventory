<?php
if(isset($_POST['submit'])){

//echo "<pre>" ;
$f_name = $_FILES['myfile']['name'];
$f_tmp = $_FILES['myfile']['tmp_name'];


$f_size = $_FILES['myfile']['size'];
$f_extension = explode('.',$f_name);
$f_extension = strtolower(end($f_extension));

 $f_newfile = uniqid().'.'. $f_extension;    

 $store = "upload/".$f_newfile;


if($f_extension=='jpg' || $f_extension=='png' || $f_extension=='gif'){

    if($f_size>=1000000 ){

        echo 'Max File should be 1MB';


    }else{

        if(move_uploaded_file($f_tmp,$store)){

            echo 'uploaded Successfully';

        }


    }

}else{

    echo 'Only jpg, png and gif can be uploaded';

}



}


?>




<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Files</title>
    </head>
    <body>

    <form action="" method="post" enctype="multipart/form-data">
<p>
    <input type="file" name="myfile">
</p>
 <p>   
    <input type="submit" value="upload" name="submit">
</p>


    </form>
    </body>
</html>
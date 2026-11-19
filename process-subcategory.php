<?php
session_start();
include("db/config.php");

// Helper function to redirect with status
function redirect_with_status($status_code) {
    $encoded_status = base64_encode($status_code);
    header("location: manage-acc-sub-category.php?status=" . $encoded_status);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $category_id = (int)$_POST['category_id'];
        $subcategory_name = mysqli_real_escape_string($db, trim($_POST['subcategory_name']));
        $duration_days = (int)$_POST['duration_days'];
        $status = (int)$_POST['status'];

        $query = "INSERT INTO accommodation_subcategories (category_id, subcategory_name, price, status) 
                  VALUES ($category_id, '$subcategory_name', $duration_days, $status)";
        
        if (mysqli_query($db, $query)) {
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'><strong>Success!</strong> Sub-category added successfully.<button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'><strong>Error!</strong> Failed to add sub-category.<button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div>";
        }
        header("location: manage-acc-sub-category.php");
        exit();

    case 'update':
        $encoded_id = $_POST['encoded_id'];
        $id = (int)base64_decode($encoded_id);
        $category_id = (int)$_POST['category_id'];
        $subcategory_name = mysqli_real_escape_string($db, trim($_POST['subcategory_name']));
        $duration_days = (int)$_POST['duration_days'];
        $status = (int)$_POST['status'];

        $query = "UPDATE accommodation_subcategories SET 
                  category_id = $category_id, 
                  subcategory_name = '$subcategory_name', 
                  price = $duration_days, 
                  status = $status 
                  WHERE id = $id";
        
        if (mysqli_query($db, $query)) {
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'><strong>Success!</strong> Sub-category updated successfully.<button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'><strong>Error!</strong> Failed to update sub-category.<button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div>";
        }
        header("location: manage-acc-sub-category.php");
        exit();

    case 'delete':
        $encoded_id = $_GET['id'];
        $id = (int)base64_decode($encoded_id);
        
        // ON DELETE CASCADE in DB will handle child records if any, but here we just delete the subcategory
        $query = "DELETE FROM accommodation_subcategories WHERE id = $id";
        
        if (mysqli_query($db, $query)) {
            redirect_with_status(1); // Success
        } else {
            redirect_with_status(0); // Error
        }
        exit();

    case 'multi_delete':
        if (!empty($_POST['item_ids'])) {
            $ids = $_POST['item_ids'];
            $decoded_ids = [];
            foreach ($ids as $encoded) {
                $decoded_ids[] = (int)base64_decode($encoded);
            }
            $id_list = implode(',', $decoded_ids);
            
            $query = "DELETE FROM accommodation_subcategories WHERE id IN ($id_list)";
            
            if (mysqli_query($db, $query)) {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'><strong>Success!</strong> Selected sub-categories deleted successfully.<button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'><strong>Error!</strong> Failed to delete selected items.<button type='button' class='close' data-bs-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div>";
            }
        }
        header("location: manage-acc-sub-category.php");
        exit();

    default:
        header("location: manage-acc-sub-category.php");
        exit();
}
?>
<?php 
require_once '../Database/database.php';
require_once '../models/Room.php';
include '../layout/header.php';

$database = new database();
$conn = $database->getConnection();

Room::setConnection($conn);

$id = $_GET['id'];
$room = Room::find($id);

$room->room_number = $_GET['room_number'];
$room->type_id = $_GET['type_id'];
$room->price = $_GET['price'];
$room->status = $_GET['status'];
$room->description = $_GET['description'];
$room->capacity = $_GET['capacity'];

$room->save();


    if ($room) {
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "Success!",
                        text: "room record has been updated.",
                        icon: "success"
                    }).then(() => {
                        window.location = "index.php";
                    });
                });
            </script>';
    } else {
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to update room record.",
                        icon: "error",
                        confirmButtonText: "Ok"
                    }).then(() => {
                        window.location = "index.php";
                    });
                });
            </script>';
    }


?>

<?php include '../layout/footer.php';?>
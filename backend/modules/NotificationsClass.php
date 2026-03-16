<?php
/*CLass Name: Notifications
Description: This class is responsible for handling notifications in the app. It provides error/success messages and displays them
Paraskevas Vafeidis
16-Mar-2024
Inputs: message(string)
Outputs: none
Error Messages: None
*/


class Notifications{


    public static function success($message){
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => $message
        ];
    }

    public static function error($message){
        $_SESSION['notification'] = [
            'type' => 'danger',
            'message' => $message
        ];
    }


    public static function createNotification(){
        if (!isset($_SESSION['notification'])) return;

        $type = $_SESSION['notification']['type'];
        $message = $_SESSION['notification']['message'];


        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";

        unset($_SESSION['notification']);      
    }

}
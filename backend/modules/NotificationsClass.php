<?php
/*CLass Name: Notifications
Description: This class is responsible for handling notifications in the app. It provides error/success messages and displays them
Paraskevas Vafeidis
16-Mar-2024

UPDATED BY: Panteleimoni Alexandrou
Date: 18-Apr-2026
Changes:
- Replaced Bootstrap alert with custom toast popup notification (no browser alert/confirm)
- Improved UI display (top-right popup instead of inline message)

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
        $message = htmlspecialchars($_SESSION['notification']['message'], ENT_QUOTES, 'UTF-8');

        $bgColor = ($type === 'success') ? '#198754' : '#dc3545';
        $title = ($type === 'success') ? 'Success' : 'Error';

        echo "
        <style>
            .custom-toast-notification{
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                min-width: 320px;
                max-width: 420px;
                background: #ffffff;
                border-left: 6px solid {$bgColor};
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.18);
                z-index: 99999;
                overflow: hidden;
                animation: popInCenter 0.3s ease;
                font-family: Arial, Helvetica, sans-serif;
            }

            @media (max-width: 576px){
                .custom-toast-notification{
                    min-width: calc(100vw - 32px);
                    max-width: calc(100vw - 32px);
                }
            }

            .custom-toast-header{
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px 8px 16px;
                font-weight: bold;
                font-size: 16px;
                color: {$bgColor};
            }

            .custom-toast-body{
                padding: 0 16px 14px 16px;
                font-size: 14px;
                color: #333;
                line-height: 1.5;
            }

            .custom-toast-close{
                background: none;
                border: none;
                font-size: 20px;
                line-height: 1;
                cursor: pointer;
                color: #666;
            }

            .custom-toast-close:hover{
                color: #000;
            }

            @keyframes popInCenter{
                from{
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.96);
                }
                to{
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
            }

            @keyframes fadeOutToast{
                from{
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
                to{
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.96);
                }
            }
        </style>

        <div id='customToastNotification' class='custom-toast-notification'>
            <div class='custom-toast-header'>
                <span>{$title}</span>
                <button type='button' class='custom-toast-close' onclick='closeCustomToast()'>&times;</button>
            </div>
            <div class='custom-toast-body'>
                {$message}
            </div>
        </div>

        <script>
            function closeCustomToast(){
                var toast = document.getElementById('customToastNotification');
                if(toast){
                    toast.style.animation = 'fadeOutToast 0.3s ease forwards';
                    setTimeout(function(){
                        if(toast){
                            toast.remove();
                        }
                    }, 300);
                }
            }

            setTimeout(function(){
                closeCustomToast();
            }, 4000);
        </script>
        ";

        unset($_SESSION['notification']);
    }
}
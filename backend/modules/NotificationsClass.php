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

UPDATED BY: Panteleimoni Alexandrou
Date: 21-Apr-2026
Changes:
- Adjusted popup notification rendering to top-center floating layout with success/error accent styling

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

        $accentColor = ($type === 'success') ? '#198754' : '#dc3545';
        $accentSoft = ($type === 'success') ? '#e8f6ee' : '#fdecec';
        $title = ($type === 'success') ? 'Success' : 'Error';

        echo "
        <style>
            .custom-toast-notification{
                position: fixed;
                top: 24px;
                left: 50%;
                transform: translateX(-50%);
                min-width: 320px;
                width: min(420px, calc(100vw - 32px));
                background: #ffffff;
                border-left: 5px solid {$accentColor};
                border-right: 5px solid {$accentColor};
                border-radius: 14px;
                box-shadow: 0 16px 36px rgba(15, 23, 42, 0.16);
                z-index: 99999;
                overflow: hidden;
                animation: popInToast 0.3s ease;
                font-family: Arial, Helvetica, sans-serif;
                border-top: 1px solid rgba(15, 23, 42, 0.08);
                border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            }

            @media (max-width: 576px){
                .custom-toast-notification{
                    min-width: 0;
                    top: 20px;
                }
            }

            .custom-toast-header{
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px 8px 16px;
                font-weight: bold;
                font-size: 16px;
                color: {$accentColor};
                background: linear-gradient(180deg, {$accentSoft} 0%, #ffffff 100%);
            }

            .custom-toast-body{
                padding: 0 16px 14px 16px;
                font-size: 14px;
                color: #334155;
                line-height: 1.5;
            }

            .custom-toast-close{
                background: none;
                border: none;
                font-size: 20px;
                line-height: 1;
                cursor: pointer;
                color: #64748b;
            }

            .custom-toast-close:hover{
                color: #000;
            }

            @keyframes popInToast{
                from{
                    opacity: 0;
                    transform: translateX(-50%) translateY(-10px) scale(0.97);
                }
                to{
                    opacity: 1;
                    transform: translateX(-50%) translateY(0) scale(1);
                }
            }

            @keyframes fadeOutToast{
                from{
                    opacity: 1;
                    transform: translateX(-50%) translateY(0) scale(1);
                }
                to{
                    opacity: 0;
                    transform: translateX(-50%) translateY(-10px) scale(0.97);
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

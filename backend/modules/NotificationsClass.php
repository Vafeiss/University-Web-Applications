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

    //csrf token generation for notifications that are created from the backend and need to be displayed in the frontend
    private static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['csrf_token'];
    }


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
                static $assetsRendered = false;

                if (!$assetsRendered) {
                        $assetsRendered = true;
                $csrfToken = self::getCsrfToken();
                $jsCsrfToken = json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                        echo "
<style>
    #system-notification-root {
        position: fixed;
        top: 16px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 11000;
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-width: 420px;
        width: calc(100vw - 32px);
        pointer-events: none;
    }

    .system-notification {
        pointer-events: auto;
        border: 1px solid #d1d5db;  
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 10px 20px rgba(0, 0, 0, .12);
        padding: 12px 14px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        animation: system-notification-in .2s ease-out;
    }

    .system-notification.success {
        border-left: 5px solid #16a34a;
    }

    .system-notification.danger {
        border-left: 5px solid #dc2626;
    }

    .system-notification .msg {
        color: #111827;
        line-height: 1.4;
        font-size: .92rem;
        flex: 1;
        word-break: break-word;
    }

    .system-notification .close-btn {
        border: 0;
        background: transparent;
        color: #6b7280;
        font-size: 1.1rem;
        line-height: 1;
        cursor: pointer;
        padding: 0;
    }

    @keyframes system-notification-in {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
<div id='system-notification-root' aria-live='polite' aria-atomic='false'></div>
<script>
    window.APP_CSRF_TOKEN = $jsCsrfToken;

    function isDispatcherActionForm(form) {
        if (!form || !form.getAttribute) return false;
        const action = form.getAttribute('action') || '';
        return action.indexOf('backend/modules/dispatcher.php') !== -1;
    }

    function appendCsrfTokenToForm(form) {
        if (!window.APP_CSRF_TOKEN || !isDispatcherActionForm(form)) return;
        let input = form.querySelector('input[name=\"_csrf\"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf';
            form.appendChild(input);
        }
        input.value = window.APP_CSRF_TOKEN;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form').forEach(appendCsrfTokenToForm);
    });

    document.addEventListener('submit', function (event) {
        const form = event.target && event.target.closest ? event.target.closest('form') : null;
        if (!form) return;
        appendCsrfTokenToForm(form);
    }, true);

    if (!window.__csrfFetchWrapped) {
        window.__csrfFetchWrapped = true;
        const nativeFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            let url = '';
            if (typeof input === 'string') {
                url = input;
            } else if (input && typeof input.url === 'string') {
                url = input.url;
            }

            const isDispatcherRequest = url.indexOf('backend/modules/dispatcher.php') !== -1;
            if (!isDispatcherRequest || !window.APP_CSRF_TOKEN) {
                return nativeFetch(input, init);
            }

            const finalInit = init ? Object.assign({}, init) : {};
            const headers = new Headers(finalInit.headers || (input && input.headers ? input.headers : undefined));
            if (!headers.has('X-CSRF-Token')) {
                headers.set('X-CSRF-Token', window.APP_CSRF_TOKEN);
            }
            finalInit.headers = headers;

            return nativeFetch(input, finalInit);
        };
    }

    window.showSystemNotification = function (type, message, durationMs) {
        const root = document.getElementById('system-notification-root');
        if (!root) return;

        const safeType = (type === 'success' || type === 'danger') ? type : 'success';
        const duration = Number.isFinite(durationMs) ? durationMs : 3500;

        const toast = document.createElement('div');
        toast.className = 'system-notification ' + safeType;

        const msg = document.createElement('div');
        msg.className = 'msg';
        msg.textContent = String(message || '');

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'close-btn';
        closeBtn.setAttribute('aria-label', 'Close');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', function () { toast.remove(); });

        toast.appendChild(msg);
        toast.appendChild(closeBtn);
        root.appendChild(toast);

        if (duration > 0) {
            setTimeout(function () { toast.remove(); }, duration);
        }
    };
</script>
";
                }

                if (!isset($_SESSION['notification'])) {
                        return;
                }

                $type = (string)($_SESSION['notification']['type'] ?? 'success');
                $message = (string)($_SESSION['notification']['message'] ?? '');
                $safeType = ($type === 'danger' || $type === 'success') ? $type : 'success';
                $jsType = json_encode($safeType, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                $jsMessage = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

                echo "<script>window.showSystemNotification && window.showSystemNotification($jsType, $jsMessage);</script>";

                unset($_SESSION['notification']);
    }

}
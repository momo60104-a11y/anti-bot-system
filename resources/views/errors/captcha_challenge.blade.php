<!DOCTYPE html>
<html>
<head>
    <title>安全性驗證</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 400px;
            margin: 100px auto;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .g-recaptcha {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .error-msg {
            color: #ff4757;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 安全性驗證</h2>
        <p>偵測到異常流量，請完成下方的驗證以繼續訪問</p>
        
        <form id="captcha-form">
            <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}" data-callback="onCaptchaSuccess" data-expired-callback="onCaptchaExpired"></div>
            <div class="error-msg" id="error-msg"></div>
            <br>
            <button type="submit" id="submit-btn" disabled>送出驗證</button>
        </form>
    </div>

    <script>
        // reCAPTCHA v2 回調函數
        function onCaptchaSuccess() {
            // 驗證完成後啟用提交按鈕
            document.getElementById('submit-btn').disabled = false;
            document.getElementById('error-msg').style.display = 'none';
        }

        function onCaptchaExpired() {
            // 驗證過期，禁用提交按鈕
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('error-msg').textContent = '驗證已過期，請重新驗證';
            document.getElementById('error-msg').style.display = 'block';
        }

        // 表單提交
        $('#captcha-form').submit(function(e) {
            e.preventDefault();
            
            const response = grecaptcha.getResponse();
            
            if(!response) {
                alert('請先勾選驗證碼');
                return;
            }

            // 禁用提交按鈕，防止重複提交
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('submit-btn').textContent = '驗證中...';

            $.ajax({
                url: "{{ route('captcha.verify') }}",
                type: "POST",
                data: {
                    'g-recaptcha-response': response,
                    '_token': '{{ csrf_token() }}'
                },
                dataType: "json",
                success: function(data) {
                    if(data.success){
                        alert('✅ 驗證成功！即將回到原頁面');
                        location.reload(); 
                    } else {
                        alert('❌ ' + (data.message || '驗證失敗，請重新嘗試'));
                        // 重設驗證碼
                        grecaptcha.reset();
                        document.getElementById('submit-btn').disabled = true;
                        document.getElementById('submit-btn').textContent = '送出驗證';
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    try {
                        const response = JSON.parse(jqXHR.responseText);
                        if (response.message) {
                            alert('❌ ' + response.message);
                        } else {
                            alert('❌ 驗證失敗，請重新嘗試');
                        }
                    } catch(e) {
                        alert('❌ 驗證失敗，請重新嘗試');
                    }
                    
                    // 重設驗證碼
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                    document.getElementById('submit-btn').disabled = true;
                    document.getElementById('submit-btn').textContent = '送出驗證';
                }
            });
        });
    </script>
</body>
</html>
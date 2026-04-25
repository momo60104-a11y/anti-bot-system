<!DOCTYPE html>
<html>
<head>
    <title>安全性驗證</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div style="text-align:center; margin-top:100px;">
        <h2>偵測到異常流量</h2>
        <p>為了確保帳戶安全，請完成下方的驗證碼：</p>
        
        <form id="captcha-form">
            <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}" style="display: inline-block;"></div>
            {{-- <input id="recaptchar_text"/> --}}
            <br>
            <button type="submit" style="margin-top:20px; padding:10px 20px;">送出驗證</button>
        </form>
    </div>

    <script>
        $('#captcha-form').submit(function(e) {
            e.preventDefault();
            const response = grecaptcha.getResponse();
            
            if(!response) {
                alert('請先完成驗證');
                return;
            }

            $.ajax({
                url: "{{ route('captcha.verify') }}",
                type: "POST",
                data: {
                    'g-recaptcha-response': response,
                    '_token': '{{ csrf_token() }}'
                },
                dataType: "json", // 預期後端回傳 JSON
                success: function(data) {
                    if(data.success){
                        alert('驗證成功！即將回到原頁面');
                        location.reload(); 
                    } else {
                        alert('驗證失敗，請重新嘗試');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    try {
                        const response = JSON.parse(jqXHR.responseText);
                        if (response.message) {
                            alert(response.message);
                        } else {
                            alert('驗證失敗，請重新嘗試');
                        }
                    } catch(e) {
                        alert('驗證失敗，請重新嘗試');
                    }
                    
                    // 重設 reCAPTCHA
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }
                }
            });
        });
    </script>
</body>
</html>
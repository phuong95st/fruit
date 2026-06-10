<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Khôi phục mật khẩu | Hoa quả Sơn Tây</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-top: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        .header {
            background-color: #0d2b1c;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-family: 'Georgia', serif;
            font-size: 24px;
            margin: 0;
            font-weight: bold;
        }
        .content {
            padding: 30px 40px;
            line-height: 1.6;
        }
        .content h2 {
            font-size: 18px;
            margin-top: 0;
            color: #1a5c35;
        }
        .button-wrapper {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            background-color: #1a5c35;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 30px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 4px;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn:hover {
            background-color: #0d2b1c;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #1a5c35;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <div class="container">
                    <div class="header">
                        <h1>Hoa quả Sơn Tây</h1>
                    </div>
                    <div class="content">
                        <h2>Xin chào {{ $userName }},</h2>
                        <p>Chúng tôi nhận được yêu cầu thiết lập lại mật khẩu cho tài khoản liên kết với địa chỉ email này của bạn trên website Hoa quả Sơn Tây.</p>
                        <p>Vui lòng nhấn vào nút dưới đây để hoàn tất việc khôi phục mật khẩu. Liên kết này sẽ hết hạn trong vòng 60 phút.</p>
                        
                        <div class="button-wrapper">
                            <a href="{{ $resetLink }}" class="btn">Đặt lại mật khẩu</a>
                        </div>
                        
                        <p>Nếu bạn không thực hiện yêu cầu này, bạn có thể bỏ qua email này một cách an toàn. Mật khẩu của bạn sẽ được giữ nguyên.</p>
                        <p style="margin-top:25px; font-size:12px; color:#6c757d;">Nếu nút trên không hoạt động, bạn có thể sao chép liên kết dưới đây và dán vào thanh địa chỉ của trình duyệt:</p>
                        <p style="font-size:11px; word-break:break-all;"><a href="{{ $resetLink }}" style="color:#1a5c35;">{{ $resetLink }}</a></p>
                    </div>
                    <div class="footer">
                        <p>© 2026 Hoa quả Sơn Tây. Bảo lưu mọi quyền.</p>
                        <p>Thị xã Sơn Tây, Hà Nội | Hotline: 0909 123 456</p>
                        <p><a href="{{ route('home') }}">Ghé thăm cửa hàng</a></p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập | Tiba Boutique</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #fff7f9;
            color: #3f2730;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }

        .page {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(360px, .9fr);
            min-height: 100vh;
        }

        .visual {
            background:
                linear-gradient(115deg, rgba(255, 247, 249, .88), rgba(255, 229, 238, .58)),
                url("https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=1600&q=80");
            background-position: center;
            background-size: cover;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(28px, 5vw, 64px);
        }

        .brand {
            color: #9d345a;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 4vw, 54px);
            font-weight: 700;
            line-height: 1;
            margin: 0;
        }

        .visual-copy {
            max-width: 620px;
        }

        .visual-copy h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(38px, 5vw, 72px);
            line-height: 1.02;
            margin: 0 0 18px;
        }

        .visual-copy p {
            color: #704252;
            font-size: 18px;
            line-height: 1.65;
            margin: 0;
            max-width: 560px;
        }

        .login-pane {
            align-items: center;
            background: linear-gradient(180deg, #fff 0%, #fff8fa 100%);
            border-left: 1px solid #f2d3dc;
            display: flex;
            justify-content: center;
            padding: 32px;
        }

        .login-box {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 24px 60px rgba(117, 44, 69, .12);
            padding: clamp(26px, 4vw, 38px);
            width: min(100%, 430px);
        }

        .eyebrow {
            color: #be476f;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .08em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        h2 {
            color: #542033;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 34px;
            line-height: 1.1;
            margin: 0 0 8px;
        }

        .hint {
            color: #80606a;
            line-height: 1.55;
            margin: 0 0 24px;
        }

        label {
            color: #7a344c;
            display: block;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="password"] {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 16px;
            margin-bottom: 16px;
            min-height: 48px;
            padding: 12px 14px;
            width: 100%;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }

        .remember {
            align-items: center;
            display: flex;
            gap: 9px;
            margin: 2px 0 22px;
        }

        .remember input {
            accent-color: #be476f;
            height: 16px;
            width: 16px;
        }

        .remember label {
            color: #704252;
            font-weight: 700;
            margin: 0;
        }

        button {
            background: #be476f;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
            font-weight: 800;
            min-height: 48px;
            padding: 12px 16px;
            width: 100%;
        }

        button:hover {
            background: #a83d61;
        }

        .error {
            background: #fff2f4;
            border: 1px solid #f0b7c1;
            border-left: 5px solid #d43f5f;
            border-radius: 8px;
            color: #9f1d38;
            font-weight: 700;
            line-height: 1.45;
            margin-bottom: 18px;
            padding: 12px 14px;
        }

        .footer-note {
            color: #9b7580;
            font-size: 13px;
            line-height: 1.5;
            margin: 18px 0 0;
            text-align: center;
        }

        @media (max-width: 900px) {
            .page {
                grid-template-columns: 1fr;
            }

            .visual {
                min-height: 330px;
                padding: 28px;
            }

            .login-pane {
                border-left: 0;
                padding: 24px 18px 36px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="visual" aria-label="Tiba Boutique">
            <p class="brand">Tiba Boutique</p>
            <div class="visual-copy">
                <h1>Quản lí cửa hàng thật gọn gàng</h1>
                <p>Theo dõi kho hàng, size, đơn hàng và lịch trả đồ trong một không gian làm việc mềm mại cho boutique thời trang.</p>
            </div>
        </section>

        <section class="login-pane">
            <div class="login-box">
                <div class="eyebrow">Hệ thống quản lí</div>
                <h2>Đăng nhập</h2>
                <p class="hint">Nhập mã đăng nhập để tiếp tục quản lí kho và đơn hàng.</p>

                @if ($errors->any())
                    <div class="error">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <label for="code">Mã đăng nhập</label>
                    <input id="code" name="code" type="text" value="{{ old('code') }}" autocomplete="username" autofocus>

                    <label for="password">Mật khẩu</label>
                    <input id="password" name="password" type="password" autocomplete="current-password">

                    <div class="remember">
                        <input id="remember" name="remember" type="checkbox" value="1">
                        <label for="remember">Ghi nhớ đăng nhập</label>
                    </div>

                    <button type="submit">Đăng nhập</button>
                </form>

                <p class="footer-note">Tiba Boutique - Quản lí kho & đơn hàng</p>
            </div>
        </section>
    </main>
</body>
</html>



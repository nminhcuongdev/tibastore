<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mode === 'create' ? 'Tạo người dùng' : 'Sửa người dùng' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #f5f8ff;
            color: #1e293b;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .topbar {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #dce7f7;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            padding: 18px clamp(18px, 5vw, 56px);
        }
        .brand {
            color: #1e40af;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 28px;
            font-weight: 700;
        }
        .content { padding: 36px clamp(18px, 5vw, 56px) 56px; }
        .heading { margin-bottom: 22px; }
        .heading h1 {
            color: #1e3a5f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 4vw, 48px);
            line-height: 1.08;
            margin: 0 0 8px;
        }
        .heading p { color: #475569; margin: 0; }
        .form-shell {
            background: #fff;
            border: 1px solid #d8e4f5;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(30, 64, 175, .08);
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: 760px;
            padding: 24px;
        }
        .field { display: grid; gap: 7px; }
        .field.full { grid-column: 1 / -1; }
        label { color: #334e75; font-size: 13px; font-weight: 800; }
        .hint { color: #64748b; font-size: 12px; font-weight: 600; }
        input, select {
            background: #fff;
            border: 1px solid #c3d4ed;
            border-radius: 8px;
            color: #1e293b;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }
        input:focus, select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .16);
            outline: none;
        }
        .error { color: #b4233f; font-size: 13px; font-weight: 700; }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            grid-column: 1 / -1;
            justify-content: flex-end;
            margin-top: 4px;
        }
        .button {
            align-items: center;
            background: #1d4ed8;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-weight: 800;
            min-height: 44px;
            padding: 10px 16px;
        }
        .button.secondary { background: #fff; border: 1px solid #c3d4ed; color: #1e40af; }
        @media (max-width: 760px) {
            .topbar, .form-shell { align-items: stretch; grid-template-columns: 1fr; }
            .topbar { flex-direction: column; }
            .actions { justify-content: stretch; }
            .button { justify-content: center; width: 100%; }
        }
    </style>
    @include('partials.compact')
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'users'])
<div class="page">

    <main class="content">
        <div class="heading">
            <h1>{{ $mode === 'create' ? 'Tạo người dùng mới' : 'Sửa người dùng' }}</h1>
            <p>Đặt mã đăng nhập, tên hiển thị và phân quyền cho tài khoản.</p>
        </div>

        <form class="form-shell" method="POST" action="{{ $mode === 'create' ? route('users.store') : route('users.update', $user) }}">
            @csrf
            @if ($mode === 'edit')
                @method('PUT')
            @endif

            <div class="field">
                <label for="code">Mã đăng nhập</label>
                <input id="code" name="code" type="text" value="{{ old('code', $user->code) }}" autocomplete="off" required>
                @error('code') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="name">Tên người dùng</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                @error('name') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="role">Quyền</label>
                <select id="role" name="role" required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $user->role ?? \App\Models\User::DEFAULT_ROLE) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="new-password" {{ $mode === 'create' ? 'required' : '' }}>
                @if ($mode === 'edit')
                    <span class="hint">Để trống nếu không đổi mật khẩu.</span>
                @endif
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $mode === 'create' ? 'required' : '' }}>
            </div>

            <div class="actions">
                <a class="button secondary" href="{{ route('users.index') }}">Hủy</a>
                <button class="button" type="submit">{{ $mode === 'create' ? 'Tạo người dùng' : 'Lưu thay đổi' }}</button>
            </div>
        </form>
    </main>
    </div>
</div>
</body>
</html>

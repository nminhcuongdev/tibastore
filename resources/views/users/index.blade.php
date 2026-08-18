<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lí người dùng</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #fff7f9;
            color: #3f2730;
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .topbar {
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #f5d9e2;
            display: flex;
            gap: 20px;
            justify-content: space-between;
            padding: 18px clamp(18px, 5vw, 56px);
        }
        .brand {
            color: #b63f68;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 28px;
            font-weight: 700;
        }
        .nav {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .nav a,
        .logout {
            background: #fff;
            border: 1px solid #e8b8c8;
            border-radius: 999px;
            color: #8b2f4d;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 16px;
        }
        .nav a.active {
            background: #be476f;
            color: #fff;
        }
        .hero {
            background: linear-gradient(115deg, rgba(255, 255, 255, .93), rgba(255, 232, 241, .8));
            border-bottom: 1px solid #f5d9e2;
            padding: 40px clamp(18px, 5vw, 56px);
        }
        .hero h1 {
            color: #6f253f;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(32px, 5vw, 52px);
            line-height: 1.04;
            margin: 0 0 12px;
        }
        .hero p {
            color: #704252;
            font-size: 17px;
            line-height: 1.6;
            margin: 0;
            max-width: 720px;
        }
        .content { padding: 28px clamp(18px, 5vw, 56px) 56px; }
        .toolbar {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: 1fr auto;
            margin-bottom: 18px;
        }
        .search { display: grid; gap: 8px; max-width: 560px; }
        label { color: #7a344c; font-size: 13px; font-weight: 700; }
        input {
            background: #fff;
            border: 1px solid #ebc5d2;
            border-radius: 8px;
            color: #3f2730;
            font-size: 15px;
            min-height: 44px;
            padding: 10px 13px;
            width: 100%;
        }
        input:focus {
            border-color: #c9577d;
            box-shadow: 0 0 0 3px rgba(201, 87, 125, .16);
            outline: none;
        }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .button {
            align-items: center;
            background: #be476f;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-weight: 800;
            min-height: 44px;
            padding: 10px 16px;
        }
        .button.secondary { background: #fff; border: 1px solid #ebc5d2; color: #8b2f4d; }
        .status {
            background: #fff;
            border: 1px solid #f0c7d3;
            border-left: 5px solid #c9577d;
            border-radius: 8px;
            color: #693143;
            margin-bottom: 18px;
            padding: 12px 14px;
        }
        .status.error { border-left-color: #b4233f; color: #b4233f; }
        .table-shell {
            background: #fff;
            border: 1px solid #f0d3dc;
            border-radius: 8px;
            box-shadow: 0 18px 45px rgba(117, 44, 69, .08);
            overflow-x: auto;
        }
        table { border-collapse: collapse; min-width: 760px; width: 100%; }
        th, td {
            border-bottom: 1px solid #f7e3e9;
            padding: 15px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #fff0f4;
            color: #81304c;
            font-size: 13px;
            letter-spacing: .02em;
            text-transform: uppercase;
        }
        tbody tr:hover { background: #fff9fb; }
        .code { color: #a13b60; font-weight: 800; }
        .name { color: #3f2730; font-weight: 800; }
        .muted { color: #8b6672; font-size: 13px; }
        .role-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 13px;
            font-weight: 900;
            padding: 7px 11px;
        }
        .role-admin { background: #fdeaf0; color: #b1325c; }
        .role-cong_tac_vien { background: #eaf2fd; color: #2f5fa6; }
        .you-tag {
            background: #fff4d6;
            border-radius: 999px;
            color: #8a5a00;
            font-size: 12px;
            font-weight: 800;
            margin-left: 8px;
            padding: 3px 8px;
        }
        .row-actions { display: flex; gap: 8px; }
        .link-action, .danger {
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 800;
            padding: 8px 10px;
        }
        .link-action { background: #fff4f7; border: 1px solid #f1cbd7; color: #8b2f4d; }
        .danger { background: #fff; border: 1px solid #f0b7c1; color: #b4233f; }
        .danger:disabled { cursor: not-allowed; opacity: .45; }
        .empty { color: #8b6672; padding: 36px; text-align: center; }
        .pagination {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: space-between;
            margin-top: 18px;
        }
        .pages { display: flex; flex-wrap: wrap; gap: 6px; }
        .page-link, .page-current {
            border-radius: 8px;
            display: inline-flex;
            font-weight: 800;
            min-width: 40px;
            padding: 10px 12px;
            place-content: center;
        }
        .page-link { background: #fff; border: 1px solid #f0d3dc; color: #8b2f4d; }
        .page-current { background: #be476f; color: #fff; }
        @media (max-width: 760px) {
            .topbar, .toolbar { align-items: stretch; grid-template-columns: 1fr; }
            .topbar { flex-direction: column; }
            .nav, .actions { width: 100%; }
            .nav a, .logout, .button { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>
<div class="layout">
@include('partials.sidebar', ['active' => 'users'])
<div class="page">

    <section class="hero">
        <h1>Quản lí người dùng</h1>
        <p>Tạo tài khoản, phân quyền admin / cộng tác viên và quản lý truy cập hệ thống.</p>
    </section>

    <main class="content">
        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="status error">{{ $errors->first() }}</div>
        @endif

        <div class="toolbar">
            <form class="search" method="GET" action="{{ route('users.index') }}">
                <label for="q">Tìm theo mã đăng nhập hoặc tên</label>
                <input id="q" name="q" type="search" value="{{ $query }}" placeholder="VD: admin, Linh...">
            </form>

            <div class="actions">
                <a class="button secondary" href="{{ route('users.index') }}">Làm mới</a>
                <a class="button" href="{{ route('users.create') }}">+ Tạo người dùng</a>
            </div>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Mã đăng nhập</th>
                        <th>Tên người dùng</th>
                        <th>Quyền</th>
                        <th>Tạo lúc</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="code">{{ $user->code }}</td>
                            <td class="name">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="you-tag">Bạn</span>
                                @endif
                            </td>
                            <td>
                                <span class="role-badge role-{{ $user->role }}">{{ $user->roleLabel() }}</span>
                            </td>
                            <td class="muted">{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="row-actions">
                                    <a class="link-action" href="{{ route('users.edit', $user) }}">Sửa</a>
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Xóa người dùng này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="danger" type="submit">Xóa</button>
                                        </form>
                                    @else
                                        <button class="danger" type="button" disabled title="Không thể xóa tài khoản đang đăng nhập">Xóa</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="empty" colspan="5">Chưa có người dùng phù hợp.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <nav class="pagination" aria-label="Phân trang">
                <div class="muted">
                    Hiển thị {{ $users->firstItem() }}-{{ $users->lastItem() }} trong {{ $users->total() }} người dùng
                </div>
                <div class="pages">
                    @if ($users->onFirstPage())
                        <span class="page-link">Trước</span>
                    @else
                        <a class="page-link" href="{{ $users->previousPageUrl() }}">Trước</a>
                    @endif

                    @for ($page = 1; $page <= $users->lastPage(); $page++)
                        @if ($page === $users->currentPage())
                            <span class="page-current">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $users->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($users->hasMorePages())
                        <a class="page-link" href="{{ $users->nextPageUrl() }}">Sau</a>
                    @else
                        <span class="page-link">Sau</span>
                    @endif
                </div>
            </nav>
        @endif
    </main>
    </div>
</div>
</body>
</html>

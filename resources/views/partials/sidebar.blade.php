{{-- Sidebar điều hướng dùng chung. Truyền $active: products | daily-stock | orders | stock | users --}}
@php $active = $active ?? ''; @endphp
<style>
    .layout { display: flex; align-items: stretch; min-height: 100vh; }
    .layout .page { flex: 1; min-width: 0; }
    .app-sidebar {
        background: #fff;
        border-right: 1px solid #f5d9e2;
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        gap: 6px;
        padding: 22px 16px;
        position: sticky;
        top: 0;
        align-self: flex-start;
        height: 100vh;
        overflow-y: auto;
        width: 240px;
        z-index: 20;
    }
    .app-sidebar .brand {
        color: #b63f68;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 24px;
        font-weight: 700;
        padding: 4px 12px 16px;
        text-decoration: none;
    }
    .app-sidebar .side-nav { display: flex; flex-direction: column; gap: 6px; }
    .app-sidebar .side-nav a {
        border-radius: 10px;
        color: #8b2f4d;
        font-weight: 700;
        padding: 11px 14px;
        text-decoration: none;
    }
    .app-sidebar .side-nav a:hover { background: #fff0f4; }
    .app-sidebar .side-nav a.active { background: #be476f; color: #fff; }
    .app-sidebar .spacer { flex: 1; min-height: 12px; }
    .app-sidebar .logout {
        background: #fff;
        border: 1px solid #e8b8c8;
        border-radius: 10px;
        color: #8b2f4d;
        cursor: pointer;
        font-weight: 700;
        padding: 11px 14px;
        text-align: left;
        width: 100%;
    }
    .app-sidebar .logout:hover { background: #fff0f4; }
    @media print {
        .app-sidebar { display: none !important; }
        .layout { display: block; }
    }
    @media (max-width: 860px) {
        .layout { flex-direction: column; }
        .app-sidebar {
            align-self: stretch;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 8px;
            height: auto;
            overflow: visible;
            position: static;
            width: 100%;
        }
        .app-sidebar .brand { padding: 4px 8px; width: 100%; }
        .app-sidebar .side-nav { flex-direction: row; flex-wrap: wrap; }
        .app-sidebar .spacer { display: none; }
        .app-sidebar .logout { width: auto; }
    }
</style>
<aside class="app-sidebar">
    <a class="brand" href="{{ route('products.index') }}">Tiba Boutique</a>
    <nav class="side-nav">
        <a class="{{ $active === 'products' ? 'active' : '' }}" href="{{ route('products.index') }}">Kho hàng</a>
        <a class="{{ $active === 'daily-stock' ? 'active' : '' }}" href="{{ route('products.daily-stock') }}">Tồn kho theo ngày</a>
        <a class="{{ $active === 'orders' ? 'active' : '' }}" href="{{ route('orders.index') }}">Đơn hàng</a>
        <a class="{{ $active === 'stock' ? 'active' : '' }}" href="{{ route('stock-import-histories.index') }}">Lịch sử nhập</a>
        @if (auth()->user()?->isAdmin())
            <a class="{{ $active === 'users' ? 'active' : '' }}" href="{{ route('users.index') }}">Người dùng</a>
        @endif
    </nav>
    <div class="spacer"></div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="logout" type="submit">Đăng xuất</button>
    </form>
</aside>

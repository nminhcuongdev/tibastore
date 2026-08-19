{{-- Sidebar điều hướng dùng chung. Truyền $active: products | daily-stock | orders | stock | revenue | users --}}
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
        overflow-x: hidden;
        overflow-y: auto;
        transition: width .18s ease;
        width: 240px;
        z-index: 20;
    }
    .app-sidebar.is-collapsed { padding-left: 10px; padding-right: 10px; width: 74px; }
    .sidebar-toggle {
        align-items: center;
        background: #fff;
        border: 1px solid #e8b8c8;
        border-radius: 10px;
        color: #8b2f4d;
        cursor: pointer;
        display: flex;
        font-size: 16px;
        font-weight: 900;
        justify-content: center;
        line-height: 1;
        margin-bottom: 6px;
        min-height: 38px;
        padding: 8px;
        width: 100%;
    }
    .sidebar-toggle:hover { background: #fff0f4; }
    /* Nhan chu an di khi thu gon, chi con icon o giua. */
    .app-sidebar .ico {
        display: inline-block;
        font-size: 17px;
        line-height: 1;
        text-align: center;
        width: 22px;
    }
    .app-sidebar.is-collapsed .txt { display: none; }
    .app-sidebar.is-collapsed .side-nav a,
    .app-sidebar.is-collapsed .logout { justify-content: center; padding-left: 8px; padding-right: 8px; }
    .app-sidebar.is-collapsed .brand { font-size: 20px; padding: 4px 0 16px; text-align: center; }
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
        align-items: center;
        border-radius: 10px;
        color: #8b2f4d;
        display: flex;
        font-weight: 700;
        gap: 10px;
        padding: 11px 14px;
        text-decoration: none;
        white-space: nowrap;
    }
    .app-sidebar .side-nav a:hover { background: #fff0f4; }
    .app-sidebar .side-nav a.active { background: #be476f; color: #fff; }
    .app-sidebar .spacer { flex: 1; min-height: 12px; }
    .app-sidebar .logout {
        align-items: center;
        background: #fff;
        border: 1px solid #e8b8c8;
        border-radius: 10px;
        color: #8b2f4d;
        cursor: pointer;
        display: flex;
        font-weight: 700;
        gap: 10px;
        padding: 11px 14px;
        text-align: left;
        white-space: nowrap;
        width: 100%;
    }
    .app-sidebar .logout:hover { background: #fff0f4; }
    @media print {
        .app-sidebar { display: none !important; }
        .layout { display: block; }
    }
    @media (max-width: 860px) {
        .layout { flex-direction: column; }
        /* Duoi 860px sidebar da nam ngang, bo qua trang thai thu gon. */
        .sidebar-toggle { display: none; }
        .app-sidebar.is-collapsed { padding: 22px 16px; width: 100%; }
        .app-sidebar.is-collapsed .txt { display: inline; }
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
<aside class="app-sidebar" data-sidebar>
    <button class="sidebar-toggle" type="button" data-sidebar-toggle
        aria-label="Thu gọn / mở rộng menu" aria-expanded="true" title="Thu gọn menu">
        <span data-sidebar-icon>&#9664;</span>
    </button>
    <a class="brand" href="{{ route('products.index') }}" title="Tiba Boutique">
        <span class="txt">Tiba Boutique</span>
        <span class="ico" style="display: none;" data-brand-short>T</span>
    </a>
    <nav class="side-nav">
        <a class="{{ $active === 'products' ? 'active' : '' }}" href="{{ route('products.index') }}" title="Kho hàng">
            <span class="ico">&#128087;</span><span class="txt">Kho hàng</span>
        </a>
        <a class="{{ $active === 'daily-stock' ? 'active' : '' }}" href="{{ route('products.daily-stock') }}" title="Tồn kho theo ngày">
            <span class="ico">&#128197;</span><span class="txt">Tồn kho theo ngày</span>
        </a>
        <a class="{{ $active === 'orders' ? 'active' : '' }}" href="{{ route('orders.index') }}" title="Đơn hàng">
            <span class="ico">&#129534;</span><span class="txt">Đơn hàng</span>
        </a>
        <a class="{{ $active === 'stock' ? 'active' : '' }}" href="{{ route('stock-import-histories.index') }}" title="Lịch sử nhập">
            <span class="ico">&#128229;</span><span class="txt">Lịch sử nhập</span>
        </a>
        <a class="{{ $active === 'revenue' ? 'active' : '' }}" href="{{ route('reports.revenue') }}" title="Báo cáo doanh thu">
            <span class="ico">&#128202;</span><span class="txt">Báo cáo doanh thu</span>
        </a>
        @if (auth()->user()?->isAdmin())
            <a class="{{ $active === 'users' ? 'active' : '' }}" href="{{ route('users.index') }}" title="Người dùng">
                <span class="ico">&#128101;</span><span class="txt">Người dùng</span>
            </a>
        @endif
    </nav>
    <div class="spacer"></div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="logout" type="submit" title="Đăng xuất">
            <span class="ico">&#128682;</span><span class="txt">Đăng xuất</span>
        </button>
    </form>
</aside>
<script>
    (function () {
        const KEY = 'tiba_sidebar_collapsed';
        const sidebar = document.querySelector('[data-sidebar]');
        if (!sidebar) return;

        const toggle = sidebar.querySelector('[data-sidebar-toggle]');
        const icon = sidebar.querySelector('[data-sidebar-icon]');
        const brandShort = sidebar.querySelector('[data-brand-short]');

        function apply(collapsed) {
            sidebar.classList.toggle('is-collapsed', collapsed);
            icon.innerHTML = collapsed ? '&#9654;' : '&#9664;';
            toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggle.title = collapsed ? 'Mở rộng menu' : 'Thu gọn menu';
            brandShort.style.display = collapsed ? 'inline-block' : 'none';
        }

        let collapsed = false;
        try { collapsed = localStorage.getItem(KEY) === '1'; } catch (e) {}

        // Chay ngay khi parse toi day nen sidebar da dung trang thai truoc khi ve.
        apply(collapsed);

        toggle.addEventListener('click', function () {
            collapsed = !sidebar.classList.contains('is-collapsed');
            apply(collapsed);
            try { localStorage.setItem(KEY, collapsed ? '1' : '0'); } catch (e) {}
        });
    })();
</script>

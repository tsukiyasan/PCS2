<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '生產管理系統')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <aside class="w-64 bg-slate-900 text-gray-300 flex-shrink-0 flex flex-col">
        <div class="p-6 text-white text-2xl font-bold border-b border-slate-800">
            NHT System
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="#" class="flex items-center px-4 py-3 text-white bg-blue-600 rounded-lg">
                <i class="fa-solid fa-chart-line mr-3"></i> 生產日報表
            </a>
            <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-boxes-stacked mr-3"></i> 製品振替查詢
            </a>
            <a href="#" class="flex items-center px-4 py-3 hover:bg-slate-800 hover:text-white rounded-lg transition">
                <i class="fa-solid fa-warehouse mr-3"></i> 倉庫管理
            </a>
        </nav>
        <div class="p-4 border-t border-slate-800 text-sm text-center">
            v1.0.26 - 2026
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10">
            <div class="flex items-center space-x-2 text-gray-500 text-sm">
                <span>首頁</span>
                <i class="fa-solid fa-chevron-right text-xs"></i>
                <span class="text-gray-900 font-medium">@yield('page_name', '當前頁面')</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">管理員: Zheng Qunyao</span>
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs">ZQ</div>
            </div>
        </header>

        <main class="flex-1 overflow-auto p-8">
            @yield('content')
        </main>

    </div>

    @stack('scripts')
</body>
</html>
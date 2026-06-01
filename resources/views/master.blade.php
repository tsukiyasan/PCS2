<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NHT System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 讓內容區可以滾動，選單固定的關鍵 */
        .main-content { height: calc(100vh - 4rem); }
        .sidebar-transition { transition: width 0.3s ease; }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <aside id="sidebar" class="sidebar-transition w-64 bg-[#111827] text-gray-300 flex-shrink-0 flex flex-col z-20">
        <div class="h-16 flex items-center px-6 text-white text-xl font-bold border-b border-gray-800">
            NHT System
        </div>
        <nav class="flex-1 py-4 px-3 space-y-1">
            <a href="./report" class="flex items-center px-3 py-2 text-white bg-blue-600 rounded-lg mb-2">
                <i class="fa-solid fa-chart-line w-6"></i>
                <span class="ml-3">生產量查詢</span>
            </a>
            <a href="./stock" class="flex items-center px-3 py-2 text-white bg-blue-600 rounded-lg mb-2">
                <i class="fa-solid fa-chart-line w-6"></i>
                <span class="ml-3">庫存查詢</span>
            </a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 bg-white">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 z-10">
            <div class="flex items-center space-x-4">
                <button id="toggleSidebar" class="text-gray-500 hover:text-gray-700">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="text-sm text-gray-500">
                    首頁 > @yield('page_name')
                </div>
            </div>
            <div class="text-sm font-medium text-gray-700">
                使用者: xxx
            </div>
        </header>

        <main class="flex-1 overflow-auto p-6 bg-gray-50">
            @yield('content')
        </main>
    </div>

    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-0'); // 或者用你的縮小邏輯
            sidebar.classList.toggle('overflow-hidden');
        });
    </script>
</body>
</html>
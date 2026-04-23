<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NHT System')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar-transition { transition: width 0.3s ease-in-out; }
        .sidebar-collapsed .menu-text, .sidebar-collapsed .sidebar-logo { display: none; }
        .sidebar-collapsed .sidebar-item { justify-content: center; }
        .filter-input {
            width: 100%; padding: 4px 8px; font-size: 0.875rem;
            border: 1px solid #d1d5db; border-radius: 0.375rem; transition: all 0.2s;
        }
        .filter-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2); }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <aside id="sidebar" class="sidebar-transition w-64 bg-slate-900 text-gray-300 flex-shrink-0 flex flex-col">
        </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-4 z-10">
             </header>

        <main class="flex-1 overflow-auto p-4 lg:p-6">
            @yield('content')
        </main>
    </div>

    <script>
        // Sidebar Toggle 邏輯
        document.getElementById('toggleSidebar')?.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-20');
            sidebar.classList.toggle('sidebar-collapsed');
        });
    </script>
    @stack('scripts')
</body>
</html>
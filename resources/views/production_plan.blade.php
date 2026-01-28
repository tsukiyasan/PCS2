<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>生產計劃表</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* 讓表格內的 input 看起來像 Excel 篩選框 */
        .table-filter-input {
            width: 100%;
            padding: 4px;
            font-size: 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            color: #374151;
        }
        .table-filter-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50 p-6">

<div class="max-w-[95%] mx-auto">
    
    <form method="GET" action="" id="searchForm">

        <div class="bg-white p-4 rounded-lg shadow mb-4 flex items-center gap-4">
            <h2 class="font-bold text-gray-700">查詢條件：</h2>
            
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">日期區間</label>
                <input type="date" name="date_start" value="{{ $dateStart }}" 
                       class="border rounded px-2 py-1 text-sm focus:ring-blue-500 border-gray-300">
                <span class="text-gray-400">~</span>
                <input type="date" name="date_end" value="{{ $dateEnd }}" 
                       class="border rounded px-2 py-1 text-sm focus:ring-blue-500 border-gray-300">
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm shadow">
                重新查詢
            </button>
            <a href="{{ url()->current() }}" class="text-gray-500 text-sm hover:underline ml-2">清除條件</a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider border border-gray-200">日期</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider border border-gray-200">計劃編號</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider border border-gray-200">線別</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider border border-gray-200">製品</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider border border-gray-200">訂單號</th>
                        </tr>

                    <tr class="bg-gray-50">
                        <td class="p-1 border border-gray-200"></td> 

                        <td class="p-1 border border-gray-200">
                            <input type="text" name="filters[keikaku_no]" value="{{ $filters['keikaku_no'] ?? '' }}" 
                                   class="table-filter-input" placeholder="篩選編號...">
                        </td>

                        <td class="p-1 border border-gray-200">
                            <select name="filters[line]" class="table-filter-input" onchange="document.getElementById('searchForm').submit()">
                                <option value="">全部</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line }}" {{ ($filters['line'] ?? '') == $line ? 'selected' : '' }}>
                                        {{ $line }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td class="p-1 border border-gray-200">
                            <input type="text" name="filters[seihin]" value="{{ $filters['seihin'] ?? '' }}" 
                                   class="table-filter-input" placeholder="輸入製品...">
                        </td>

                        <td class="p-1 border border-gray-200">
                            <input type="text" name="filters[order_no]" value="{{ $filters['order_no'] ?? '' }}" 
                                   class="table-filter-input" placeholder="輸入訂單...">
                        </td>

                        </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($plans as $row)
                        <tr class="hover:bg-blue-50">
                            <td class="px-3 py-2 text-sm text-gray-900 border border-gray-100">{{ $row->tonyu_yotei_ymd }}</td>
                            <td class="px-3 py-2 text-sm text-gray-500 border border-gray-100">{{ $row->keikaku_no }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900 border border-gray-100">{{ $row->line }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900 border border-gray-100">{{ $row->seihin }}</td>
                            <td class="px-3 py-2 text-sm text-gray-500 border border-gray-100">{{ $row->order_no }}</td>
                            </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-4">無資料</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $plans->links() }}
        </div>
        
    </form> </div>

</body>
</html>
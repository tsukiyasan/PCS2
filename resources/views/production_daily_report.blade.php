<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>生產計劃表</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* 自定義樣式：讓表格內的 input 更緊湊好看 */
        .filter-input {
            width: 100%;
            padding: 4px 8px;
            font-size: 0.875rem; /* 14px */
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        .filter-input:focus {
            outline: none;
            border-color: #2563eb; /* Blue-600 */
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100 p-6 text-gray-800 font-sans">

<div class="max-w-[98%] mx-auto">
    
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-700">生產計劃查詢</h1>
    </div>

    <form method="GET" action="" id="searchForm">

        <div class="bg-white p-4 rounded-lg shadow-sm mb-4 border border-gray-200 flex flex-wrap items-center gap-4">
            
            <div class="flex items-center gap-2">
                <label class="font-medium text-gray-600">投入日期：</label>
                <input type="date" name="date_start" value="{{ $dateStart }}" class="border-gray-300 border rounded px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none">
                <span class="text-gray-400">~</span>
                <input type="date" name="date_end" value="{{ $dateEnd }}" class="border-gray-300 border rounded px-3 py-1.5 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-1.5 rounded shadow transition">
                查詢
            </button>

            <a href="{{ url()->current() }}" class="text-gray-500 hover:text-red-500 underline text-sm ml-auto">
                清除所有條件
            </a>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-x-auto border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">日期</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">計畫編號</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">線別</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">製品</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">尺寸(S/L)</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">投入數</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">良品數</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">振替數</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">形態</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">厚度</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">訂單號</th>
                    </tr>
                    
                    <tr class="bg-gray-100">
                        <td class="p-2"></td> 

                        <td class="p-2">
                            <input type="text" name="filters[keikaku_no]" value="{{ $filters['keikaku_no'] ?? '' }}" 
                                   class="filter-input" placeholder="搜尋編號...">
                        </td>

                        <td class="p-2">
                            <select name="filters[line]" class="filter-input bg-white cursor-pointer" onchange="document.getElementById('searchForm').submit()">
                                <option value="">全部</option>
                                @foreach($lines as $lineOption)
                                    <option value="{{ $lineOption }}" {{ ($filters['line'] ?? '') == $lineOption ? 'selected' : '' }}>
                                        {{ $lineOption }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td class="p-2">
                            <input type="text" name="filters[seihin]" value="{{ $filters['seihin'] ?? '' }}" 
                                   class="filter-input" placeholder="搜尋製品...">
                        </td>

                        <td class="p-2 text-right"></td>
                        <td class="p-2 text-right">總投入數: {{ number_format($plans->sum('tonyu_su')) }}</td>
                        <td class="p-2 text-right">總良品數: {{ number_format($plans->sum('tonyu_su')) }}</td>
                        <td class="p-2 text-right">總振替數: {{ number_format($plans->sum('tonyu_su')) }}</td>
                        <td class="p-2 text-right"></td>
                        <td class="p-2 text-right"></td>
                        
                        <td class="p-2">
                            <input type="text" name="filters[order_no]" value="{{ $filters['order_no'] ?? '' }}" 
                                   class="filter-input" placeholder="搜尋訂單...">
                        </td>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($plans as $row)
                        <tr class="hover:bg-blue-50 transition duration-150 group">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $row->tonyu_yotei_ymd }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $row->keikaku_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 font-bold">{{ $row->line }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-blue-700 font-medium">{{ $row->seihin }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-center">
                                {{ $row->sunpo_s }} / {{ $row->sunpo_l }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-mono">
                                {{ number_format($row->TOTAL_SETSUDAN ?? 0) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-mono">
                                {{ number_format($row->tonyu_su) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-mono">
                                {{ number_format($row->tonyu_su) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $row->keitai_name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $row->itaatsu }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $row->order_no }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-500 bg-gray-50">
                                <div class="flex flex-col items-center">
                                    <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <span class="text-lg">查無資料</span>
                                    <span class="text-sm">請調整篩選條件</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pb-10">
            {{ $plans->links() }}
        </div>

    </form> </div>
<td class="px-4 py-3 text-right">
    @if(isset($row->TOTAL_SETSUDAN))
        {{ number_format($row->TOTAL_SETSUDAN) }}
    @elseif(isset($row->total_setsudan))
        {{ number_format($row->total_setsudan) }}
    @else
        <span title="{{ json_encode($row) }}" style="color:red;">0 (Debug)</span>
    @endif
</td>
<script>
    // 先轉 base64 再解碼，避開所有語法衝突
    const base64Data = "{{ base64_encode(json_encode($plans->items())) }}";
    const phpData = JSON.parse(atob(base64Data));
    
    console.log("--- 完整的資料列內容 ---");
    console.table(phpData); 
</script>
</body>
</html>
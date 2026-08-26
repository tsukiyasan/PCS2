@extends('master')

@section('title', '庫存查詢')
@section('page_name', '庫存查詢')

@section('content')
<div class="max-w-[100%] mx-auto">
    <form method="GET" action="" id="searchForm">
        {{-- 頂部搜尋列：依據庫存特性改為「捆包日期」區間查詢 --}}
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-200 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="font-semibold text-gray-700">捆包日期：</label>
                <input type="date" name="date_start" value="{{ $dateStart }}" class="border-gray-300 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                <span class="text-gray-400">~</span>
                <input type="date" name="date_end" value="{{ $dateEnd }}" class="border-gray-300 border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-md transition font-medium">
                <i class="fa-solid fa-magnifying-glass mr-2"></i>查詢
            </button>

            <a href="{{ url()->current() }}" class="text-gray-400 hover:text-red-500 transition text-sm ml-auto flex items-center">
                <i class="fa-solid fa-rotate-left mr-1"></i>清除所有條件
            </a>
        </div>

        {{-- 庫存表格區塊：加入枚數與最新受拂日 --}}
        <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        {{-- 🌟 修正點 1：表頭加入「枚數」與「最新受拂日」 --}}
                        @foreach(['年月', '客戶別', '用途', '製品', '捆包日', '枚數'] as $head)
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                {{ $head }}
                            </th>
                        @endforeach
                    </tr>
                    
                    {{-- 關鍵字過濾列 --}}
                    <tr class="bg-blue-50/50">
                        <td class="p-2"></td> {{-- 年月 --}}
                        <td class="p-2">
                            <select name="filters[yoto]" class="filter-input bg-white cursor-pointer" onchange="this.form.submit()">
                                <option value="">全部用途</option>
                                @foreach($yotoOptions ?? [] as $option)
                                    <option value="{{ $option }}" {{ ($filters['yoto'] ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-2"><input type="text" name="filters[seihin]" value="{{ $filters['seihin'] ?? '' }}" class="filter-input" placeholder="搜尋製品..."></td>
                        <td class="p-2">
                            <select name="filters[vendor]" class="filter-input bg-white cursor-pointer" onchange="this.form.submit()">
                                <option value="">全部製品</option>
                                @foreach($vendorOptions ?? [] as $option)
                                    <option value="{{ $option }}" {{ ($filters['vendor'] ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-2"></td> {{-- 捆包日 --}}
                        {{-- 🌟 修正點 2：補上過濾列對應的空白 td，保持版面不跑版 --}}
                        <td class="p-2"></td> {{-- 枚數 --}}
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($stocks as $row)
                        <tr class="hover:bg-blue-50/30 transition duration-150 text-sm text-gray-600">
                            {{-- 年月 --}}
                            <td class="px-6 py-3 whitespace-nowrap font-mono">{{ $row->nengetsu }}</td>
                            
                            {{-- 客戶別 --}}
                            <td class="px-6 py-3 whitespace-nowrap text-gray-700">{{ $row->vendor }}</td>
                            
                            {{-- 用途 --}}
                            <td class="px-6 py-3 whitespace-nowrap font-medium text-gray-900">{{ $row->yoto_name }}</td>
                            
                            {{-- 製品 --}}
                            <td class="px-6 py-3 whitespace-nowrap text-blue-700 font-semibold">{{ $row->seihin }}</td>
                            
                            {{-- 捆包日 --}}
                            <td class="px-6 py-3 whitespace-nowrap font-mono text-xs text-gray-500">{{ $row->konbao_ymd }}</td>

                            <td class="px-6 py-3 whitespace-nowrap font-semibold text-emerald-600">
                                {{ number_format($row->maisu) }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center opacity-40">
                                    <i class="fa-solid fa-boxes-stacked text-4xl mb-2"></i>
                                    <span class="text-lg">目前無庫存資料</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 分頁區塊 --}}
        <div class="mt-6 mb-10 px-4">
            {{ $stocks->links() }}
        </div>
    </form>
</div>

{{-- Debug Container --}}
<div id="debug-data-container" data-stocks='@json($stocks->items())' style="display:none;"></div>
@endsection
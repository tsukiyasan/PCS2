@extends('master')

@section('title', '生產量查詢')
@section('page_name', '生產量查詢')

@section('content')
<div class="max-w-[100%] mx-auto">
    <form method="GET" action="" id="searchForm">
        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 border border-gray-200 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="font-semibold text-gray-700">投入日期：</label>
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

        <div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach(['日期', '計畫編號', '線別', '製品', '尺寸(S/L)', '厚度', '良率', '投入數', '良品數', '振替數', '形態', '訂單號'] as $head)
                            <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ $head }}</th>
                        @endforeach
                    </tr>
                    
                    <tr class="bg-blue-50/50">
                        <td class="p-2"></td> 
                        <td class="p-2"><input type="text" name="filters[keikaku_no]" value="{{ $filters['keikaku_no'] ?? '' }}" class="filter-input" placeholder="搜尋..."></td>
                        <td class="p-2">
                            <select name="filters[line]" class="filter-input bg-white cursor-pointer" onchange="this.form.submit()">
                                <option value="">全部</option>
                                @foreach($lines as $lineOption)
                                    <option value="{{ $lineOption }}" {{ ($filters['line'] ?? '') == $lineOption ? 'selected' : '' }}>{{ $lineOption }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-2"><input type="text" name="filters[seihin]" value="{{ $filters['seihin'] ?? '' }}" class="filter-input" placeholder="搜尋..."></td>
                        <td class="p-2"></td>
                        <td class="p-2" colspan="1"></td>
                       <td class="p-2 text-right text-xs font-bold text-blue-600">總計：</td>
                        <td class="p-2 text-right text-xs font-bold text-gray-700">{{ number_format($plans->sum('total_setsudan')) }}</td>
                        <td class="p-2 text-right text-xs font-bold text-gray-700">{{ number_format($plans->sum('total_ryohin')) }}</td>
                        <td class="p-2 text-right text-xs font-bold text-gray-700">{{ number_format($plans->sum('total_furikae')) }}</td>
                        <td class="p-2" colspan="1"></td>
                        <td class="p-2"><input type="text" name="filters[order_no]" value="{{ $filters['order_no'] ?? '' }}" class="filter-input" placeholder="搜尋..."></td>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($plans as $row)
                        <tr class="hover:bg-blue-50/30 transition duration-150">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $row->tonyu_yotei_ymd }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->keikaku_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-slate-700">{{ $row->line }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-blue-700 font-semibold">{{ $row->seihin }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 text-center">{{ $row->sunpo_s }} / {{ $row->sunpo_l }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ number_format((float)$row->itaatsu, 2) }}</td>
                            
                            {{-- 良率變色邏輯 --}}
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-mono font-bold">
                                @php $rate = ($row->total_setsudan ?? 0) > 0 ? (($row->total_ryohin ?? 0) / $row->total_setsudan) * 100 : 0; @endphp
                                <span class="{{ $rate < 95 ? 'text-red-500' : 'text-green-600' }}">
                                    {{ number_format($rate, 1) }}%
                                </span>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-mono">{{ number_format($row->total_setsudan ?? 0) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-mono">{{ number_format($row->total_ryohin ?? 0) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-mono">
                                {{ number_format($row->total_furikae ?? 0) }}
                                @if(($row->total_furikae ?? 0) != 0 && !empty($row->mae_seihin))
                                    <span class="bg-gray-100 text-gray-500 text-[10px] px-1 rounded ml-1">({{ $row->mae_seihin }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $row->keitai_name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-400">{{ $row->order_no }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center opacity-40">
                                    <i class="fa-solid fa-database text-4xl mb-2"></i>
                                    <span class="text-lg">目前無生產實績資料</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 mb-10 px-4">
            {{ $plans->links() }}
        </div>
    </form>
</div>

<div id="debug-data-container" data-plans='@json($plans->items())' style="display:none;"></div>
@endsection

@push('scripts')
<script>
    console.log("NHT System Loaded: 2026-04-23");
    // 解析除錯資料 (如先前所述)
</script>
@endpush
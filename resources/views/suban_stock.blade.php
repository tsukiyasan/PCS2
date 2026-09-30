@extends('master')

@section('title', '素版庫存查詢')

@section('page_name', '素版庫存查詢')

@section('content')

<div class="max-w-[100%] mx-auto px-2">

    {{-- ============================================================
         假資料
         先不連後端，直接模擬圖片中的 Excel 樞紐表資料
    ============================================================ --}}
    @php

        $fakeStocks = [

            // --------------------------------------------------------
            // TI2
            // --------------------------------------------------------
            [
                'group'      => 'TI2',
                'l'          => '1350',
                'w'          => '2530',
                'thickness'  => '',
                'defect'     => '',
                'warehouse'  => '',
                'adopt'      => 183,
                'remaining'  => 72225,
            ],

            // --------------------------------------------------------
            // TF5
            // --------------------------------------------------------
            [
                'group'      => 'TF5',
                'l'          => '2000',
                'w'          => '2340',
                'thickness'  => '0.5',
                'defect'     => '泡沫載',
                'warehouse'  => '01倉庫',
                'adopt'      => 74,
                'remaining'  => 22317,
            ],
            [
                'group'      => '',
                'l'          => '',
                'w'          => '',
                'thickness'  => '',
                'defect'     => '',
                'warehouse'  => '科學城-新吉',
                'adopt'      => 56,
                'remaining'  => 18300,
            ],
            [
                'group'      => '',
                'l'          => '',
                'w'          => '',
                'thickness'  => '',
                'defect'     => '',
                'warehouse'  => '台灣第二工場倉庫',
                'adopt'      => 50,
                'remaining'  => 16499,
            ],
            [
                'group'      => '',
                'l'          => '',
                'w'          => '',
                'thickness'  => '0.7',
                'defect'     => '良品',
                'warehouse'  => '01倉庫',
                'adopt'      => 1,
                'remaining'  => 180,
            ],
            [
                'group'      => '',
                'l'          => '',
                'w'          => '',
                'thickness'  => '',
                'defect'     => '',
                'warehouse'  => '科學城-新吉',
                'adopt'      => 30,
                'remaining'  => 6000,
            ],
            [
                'group'      => '',
                'l'          => '2650',
                'w'          => '2340',
                'thickness'  => '0.4',
                'defect'     => '良品',
                'warehouse'  => '科學城-新吉',
                'adopt'      => 75,
                'remaining'  => 17260,
            ],
            [
                'group'      => '',
                'l'          => '',
                'w'          => '',
                'thickness'  => '0.72',
                'defect'     => '良品',
                'warehouse'  => '科學城-新吉',
                'adopt'      => 4,
                'remaining'  => 662,
            ],

            // --------------------------------------------------------
            // RTK3
            // --------------------------------------------------------
            [
                'group'      => 'RTK3',
                'l'          => '1275',
                'w'          => '2250',
                'thickness'  => '0.5',
                'defect'     => '良品',
                'warehouse'  => '01倉庫',
                'adopt'      => 16,
                'remaining'  => 3329,
            ],
            [
                'group'      => '',
                'l'          => '',
                'w'          => '',
                'thickness'  => '',
                'defect'     => '',
                'warehouse'  => '科學城-新吉',
                'adopt'      => 20,
                'remaining'  => 4299,
            ],

            // --------------------------------------------------------
            // RTT2
            // --------------------------------------------------------
            [
                'group'      => 'RTT2',
                'l'          => '1100',
                'w'          => '1300',
                'thickness'  => '0.52',
                'defect'     => '泡沫載',
                'warehouse'  => '01倉庫',
                'adopt'      => 31,
                'remaining'  => 18055,
            ],
        ];

        $totalAdopt = collect($fakeStocks)->sum('adopt');
        $totalRemaining = collect($fakeStocks)->sum('remaining');

    @endphp


    {{-- ============================================================
         查詢區
         目前只做畫面，不接後端
    ============================================================ --}}
    <div class="bg-white p-4 rounded-xl shadow-sm mb-4 border border-gray-200">

        <div class="flex flex-wrap items-center gap-4">

            <div class="flex items-center gap-2">

                <label class="font-semibold text-gray-700">
                    庫存年月：
                </label>

                <input
                    type="month"
                    value="2026-08"
                    class="border border-gray-300 rounded-lg px-3 py-2
                           focus:ring-2 focus:ring-blue-500
                           outline-none text-sm"
                >

            </div>

            <button
                type="button"
                class="bg-blue-600 hover:bg-blue-700 text-white
                       px-6 py-2 rounded-lg shadow-md
                       transition font-medium"
            >
                <i class="fa-solid fa-magnifying-glass mr-2"></i>
                查詢
            </button>

        </div>

    </div>


    {{-- ============================================================
         主要表格
    ============================================================ --}}
    <div class="bg-white border border-gray-300 shadow-sm overflow-x-auto">

        <table class="min-w-[1100px] w-full border-collapse text-sm">

            {{-- ====================================================
                 表頭
            ==================================================== --}}
            <thead>
                <tr class="bg-[#4F81BD] text-white">

                    {{-- 設備記號 --}}
                    <th class="relative border-r border-white/20 px-2 py-1.5 text-left font-bold whitespace-nowrap">
                        <div class="flex items-center justify-between gap-2">
                            <span>設備記號</span>

                            <button
                                type="button"
                                onclick="toggleFilter('equipment')"
                                class="filter-btn hover:bg-white/20 rounded px-1"
                            >
                                <i class="fa-solid fa-filter text-[11px]"></i>
                            </button>
                        </div>

                        <div
                            id="filter-equipment"
                            class="filter-dropdown hidden"
                        >
                            <label>
                                <input type="checkbox" value="ALL" checked>
                                全部
                            </label>

                            <label>
                                <input type="checkbox" value="TI2">
                                TI2
                            </label>

                            <label>
                                <input type="checkbox" value="TF5">
                                TF5
                            </label>

                            <label>
                                <input type="checkbox" value="RTK3">
                                RTK3
                            </label>

                            <label>
                                <input type="checkbox" value="RTT2">
                                RTT2
                            </label>

                            <button
                                type="button"
                                onclick="applyFilter('equipment')"
                                class="filter-apply"
                            >
                                套用
                            </button>
                        </div>
                    </th>


                    {{-- 可寸法L --}}
                    <th class="relative border-r border-white/20 px-2 py-1.5 text-right font-bold whitespace-nowrap">

                        <div class="flex items-center justify-between gap-2">
                            <span>可寸法L</span>

                            <button
                                type="button"
                                onclick="toggleFilter('l')"
                                class="filter-btn hover:bg-white/20 rounded px-1"
                            >
                                <i class="fa-solid fa-filter text-[11px]"></i>
                            </button>
                        </div>

                        <div
                            id="filter-l"
                            class="filter-dropdown hidden"
                        >
                            <label>
                                <input type="checkbox" value="ALL" checked>
                                全部
                            </label>

                            <label>
                                <input type="checkbox" value="1350">
                                1350
                            </label>

                            <label>
                                <input type="checkbox" value="2000">
                                2000
                            </label>

                            <label>
                                <input type="checkbox" value="2650">
                                2650
                            </label>

                            <label>
                                <input type="checkbox" value="1275">
                                1275
                            </label>

                            <label>
                                <input type="checkbox" value="1100">
                                1100
                            </label>

                            <button
                                type="button"
                                onclick="applyFilter('l')"
                                class="filter-apply"
                            >
                                套用
                            </button>
                        </div>
                    </th>


                    {{-- 可寸法W --}}
                    <th class="relative border-r border-white/20 px-2 py-1.5 text-right font-bold whitespace-nowrap">

                        <div class="flex items-center justify-between gap-2">
                            <span>可寸法W</span>

                            <button
                                type="button"
                                onclick="toggleFilter('w')"
                                class="filter-btn hover:bg-white/20 rounded px-1"
                            >
                                <i class="fa-solid fa-filter text-[11px]"></i>
                            </button>
                        </div>

                        <div
                            id="filter-w"
                            class="filter-dropdown hidden"
                        >
                            <label>
                                <input type="checkbox" value="ALL" checked>
                                全部
                            </label>

                            <label>
                                <input type="checkbox" value="2530">
                                2530
                            </label>

                            <label>
                                <input type="checkbox" value="2340">
                                2340
                            </label>

                            <label>
                                <input type="checkbox" value="2250">
                                2250
                            </label>

                            <label>
                                <input type="checkbox" value="1300">
                                1300
                            </label>

                            <button
                                type="button"
                                onclick="applyFilter('w')"
                                class="filter-apply"
                            >
                                套用
                            </button>
                        </div>
                    </th>


                    {{-- 厚度 --}}
                    <th class="relative border-r border-white/20 px-2 py-1.5 text-right font-bold whitespace-nowrap">

                        <div class="flex items-center justify-between gap-2">
                            <span>厚度</span>

                            <button
                                type="button"
                                onclick="toggleFilter('thickness')"
                                class="filter-btn hover:bg-white/20 rounded px-1"
                            >
                                <i class="fa-solid fa-filter text-[11px]"></i>
                            </button>
                        </div>

                        <div
                            id="filter-thickness"
                            class="filter-dropdown hidden"
                        >
                            <label>
                                <input type="checkbox" value="ALL" checked>
                                全部
                            </label>

                            <label>
                                <input type="checkbox" value="0.4">
                                0.4
                            </label>

                            <label>
                                <input type="checkbox" value="0.5">
                                0.5
                            </label>

                            <label>
                                <input type="checkbox" value="0.52">
                                0.52
                            </label>

                            <label>
                                <input type="checkbox" value="0.7">
                                0.7
                            </label>

                            <label>
                                <input type="checkbox" value="0.72">
                                0.72
                            </label>

                            <button
                                type="button"
                                onclick="applyFilter('thickness')"
                                class="filter-apply"
                            >
                                套用
                            </button>
                        </div>
                    </th>


                    {{-- 欠点區分 --}}
                    <th class="relative border-r border-white/20 px-2 py-1.5 text-left font-bold whitespace-nowrap">

                        <div class="flex items-center justify-between gap-2">
                            <span>欠点區分</span>

                            <button
                                type="button"
                                onclick="toggleFilter('defect')"
                                class="filter-btn hover:bg-white/20 rounded px-1"
                            >
                                <i class="fa-solid fa-filter text-[11px]"></i>
                            </button>
                        </div>

                        <div
                            id="filter-defect"
                            class="filter-dropdown hidden"
                        >
                            <label>
                                <input type="checkbox" value="ALL" checked>
                                全部
                            </label>

                            <label>
                                <input type="checkbox" value="泡沫載">
                                泡沫載
                            </label>

                            <label>
                                <input type="checkbox" value="良品">
                                良品
                            </label>

                            <button
                                type="button"
                                onclick="applyFilter('defect')"
                                class="filter-apply"
                            >
                                套用
                            </button>
                        </div>
                    </th>


                    {{-- 倉庫 --}}
                    <th class="relative px-2 py-1.5 text-left font-bold whitespace-nowrap">

                        <div class="flex items-center justify-between gap-2">
                            <span>倉庫</span>

                            <button
                                type="button"
                                onclick="toggleFilter('warehouse')"
                                class="filter-btn hover:bg-white/20 rounded px-1"
                            >
                                <i class="fa-solid fa-filter text-[11px]"></i>
                            </button>
                        </div>

                        <div
                            id="filter-warehouse"
                            class="filter-dropdown hidden"
                        >
                            <label>
                                <input type="checkbox" value="ALL" checked>
                                全部
                            </label>

                            <label>
                                <input type="checkbox" value="01倉庫">
                                01倉庫
                            </label>

                            <label>
                                <input type="checkbox" value="科學城-新吉">
                                科學城-新吉
                            </label>

                            <label>
                                <input type="checkbox" value="台灣第二工場倉庫">
                                台灣第二工場倉庫
                            </label>

                            <button
                                type="button"
                                onclick="applyFilter('warehouse')"
                                class="filter-apply"
                            >
                                套用
                            </button>
                        </div>
                    </th>

                </tr>
            </thead>

            {{-- ====================================================
                 表身
            ==================================================== --}}
            <tbody class="text-gray-700">

                @foreach($fakeStocks as $index => $row)

                    @php
                        $isGroup = !empty($row['group']);
                        $isWarning = in_array($row['group'], ['TI2', 'TF5', 'RTK3']);
                    @endphp

                    <tr
                        class="
                            hover:bg-blue-50
                            transition
                            {{ $isGroup ? 'border-t border-gray-300' : '' }}
                        "
                    >

                        {{-- =================================================
                             設備記號
                        ================================================= --}}
                        <td class="border-r border-b border-gray-200
                                   px-2 py-1.5 whitespace-nowrap">

                            @if($isGroup)

                                <div class="flex items-center">

                                    {{-- 模擬 Excel 的 +/- --}}
                                    <span
                                        class="inline-flex items-center justify-center
                                               w-4 h-4 mr-1
                                               text-[10px]
                                               text-gray-600
                                               border border-gray-400
                                               bg-gray-100"
                                    >
                                        −
                                    </span>

                                    <span class="font-semibold">
                                        {{ $row['group'] }}
                                    </span>

                                </div>

                            @endif

                        </td>


                        {{-- =================================================
                             可寸法L
                        ================================================= --}}
                        <td class="border-r border-b border-gray-200
                                   px-2 py-1.5 text-right whitespace-nowrap">

                            @if($row['l'])

                                <span class="inline-flex items-center gap-1">

                                    <span
                                        class="inline-flex items-center justify-center
                                               w-3.5 h-3.5
                                               text-[9px]
                                               text-gray-600
                                               border border-gray-400
                                               bg-gray-100"
                                    >
                                        −
                                    </span>

                                    {{ $row['l'] }}

                                </span>

                            @endif

                        </td>


                        {{-- =================================================
                             可寸法W
                        ================================================= --}}
                        <td class="border-r border-b border-gray-200
                                   px-2 py-1.5 text-right whitespace-nowrap">

                            @if($row['w'])

                                <span class="inline-flex items-center gap-1">

                                    <span
                                        class="inline-flex items-center justify-center
                                               w-3.5 h-3.5
                                               text-[9px]
                                               text-gray-600
                                               border border-gray-400
                                               bg-gray-100"
                                    >
                                        −
                                    </span>

                                    {{ $row['w'] }}

                                </span>

                            @endif

                        </td>


                        {{-- =================================================
                             厚度
                        ================================================= --}}
                        <td class="border-r border-b border-gray-200
                                   px-2 py-1.5 text-right whitespace-nowrap">

                            @if($row['thickness'])

                                <span class="inline-flex items-center gap-1">

                                    <span
                                        class="inline-flex items-center justify-center
                                               w-3.5 h-3.5
                                               text-[9px]
                                               text-gray-600
                                               border border-gray-400
                                               bg-gray-100"
                                    >
                                        −
                                    </span>

                                    {{ $row['thickness'] }}

                                </span>

                            @endif

                        </td>


                        {{-- =================================================
                             欠點區分
                        ================================================= --}}
                        <td class="border-r border-b border-gray-200
                                   px-2 py-1.5 whitespace-nowrap">

                            @if($row['defect'])

                                <span class="inline-flex items-center gap-1">

                                    <span
                                        class="inline-flex items-center justify-center
                                               w-3.5 h-3.5
                                               text-[9px]
                                               text-gray-600
                                               border border-gray-400
                                               bg-gray-100"
                                    >
                                        −
                                    </span>

                                    {{ $row['defect'] }}

                                </span>

                            @endif

                        </td>


                        {{-- =================================================
                             倉庫
                        ================================================= --}}
                        <td class="border-r border-b border-gray-200
                                   px-2 py-1.5 whitespace-nowrap">

                            {{ $row['warehouse'] }}

                        </td>


                        {{-- =================================================
                             採用枚數
                        ================================================= --}}
                        <td
                            class="
                                border-r border-b border-gray-200
                                px-2 py-1.5 text-right
                                whitespace-nowrap
                                {{ $isWarning ? 'bg-white' : '' }}
                            "
                        >

                            {{ number_format($row['adopt']) }}

                        </td>


                        {{-- =================================================
                             剩餘數
                        ================================================= --}}
                        <td
                            class="
                                border-b border-gray-200
                                px-2 py-1.5 text-right
                                whitespace-nowrap
                                {{ $row['remaining'] >= 20000
                                    ? 'bg-[#e6b8b7]'
                                    : 'bg-white'
                                }}
                            "
                        >

                            {{ number_format($row['remaining']) }}

                        </td>

                    </tr>

                @endforeach


                {{-- ========================================================
                     總計
                ======================================================== --}}
                <tr class="border-t-2 border-[#4f81bd] bg-white font-bold">

                    <td
                        colspan="7"
                        class="px-2 py-1.5 text-left text-gray-800"
                    >
                        總計
                    </td>

                    <td
                        class="px-2 py-1.5 text-right text-gray-800"
                    >
                        {{ number_format($totalAdopt) }}
                    </td>

                    <td
                        class="px-2 py-1.5 text-right text-gray-800"
                    >
                        {{ number_format($totalRemaining) }}
                    </td>

                </tr>

            </tbody>

        </table>

    </div>

</div>
<style>
    .filter-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;

        min-width: 180px;

        background: white;
        color: #374151;

        border: 1px solid #d1d5db;
        border-radius: 4px;

        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);

        padding: 8px;
    }

    .filter-dropdown label {
        display: flex;
        align-items: center;
        gap: 7px;

        padding: 5px 6px;

        font-size: 13px;
        font-weight: normal;

        cursor: pointer;

        white-space: nowrap;
    }

    .filter-dropdown label:hover {
        background: #eff6ff;
    }

    .filter-dropdown input[type="checkbox"] {
        width: 14px;
        height: 14px;
    }

    .filter-apply {
        width: 100%;

        margin-top: 6px;
        padding: 5px;

        background: #2563eb;
        color: white;

        border-radius: 4px;

        font-size: 12px;
    }

    .filter-apply:hover {
        background: #1d4ed8;
    }

    .filter-btn {
        cursor: pointer;
    }
</style>
<script>

function toggleFilter(field) {

    // 先關閉其他篩選
    document.querySelectorAll('.filter-dropdown').forEach(function (el) {

        if (el.id !== 'filter-' + field) {
            el.classList.add('hidden');
        }

    });

    const dropdown = document.getElementById('filter-' + field);

    dropdown.classList.toggle('hidden');
}


// 點頁面其他地方，自動關閉篩選
document.addEventListener('click', function (event) {

    if (
        !event.target.closest('.filter-dropdown') &&
        !event.target.closest('.filter-btn')
    ) {

        document.querySelectorAll('.filter-dropdown').forEach(function (el) {
            el.classList.add('hidden');
        });

    }

});


// 套用篩選
function applyFilter(field) {

    const dropdown = document.getElementById('filter-' + field);

    const checked = Array.from(
        dropdown.querySelectorAll('input[type="checkbox"]:checked')
    ).map(function (checkbox) {
        return checkbox.value;
    });

    console.log('篩選欄位：', field);
    console.log('選擇值：', checked);

    /*
     * 目前先測試用。
     *
     * 下一步再把這裡接到真正的假資料過濾。
     */

    dropdown.classList.add('hidden');
}

</script>

@endsection
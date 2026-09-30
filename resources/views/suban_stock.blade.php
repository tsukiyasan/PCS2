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

                <tr class="bg-[#4f81bd] text-white">

                    {{-- 設備記號 --}}
                    <th class="border-r border-[#d9e2f3] px-2 py-2
                               text-left font-bold whitespace-nowrap
                               w-[230px]">

                        <div class="flex items-center justify-between">

                            <span>設備記號</span>

                            <span class="text-white/90 text-xs">
                                ▼
                            </span>

                        </div>

                    </th>


                    {{-- 可寸法L --}}
                    <th class="border-r border-[#d9e2f3] px-2 py-2
                               text-right font-bold whitespace-nowrap
                               w-[120px]">

                        <div class="flex items-center justify-between">

                            <span>可寸法L</span>

                            <span class="text-white/90 text-xs">
                                ▼
                            </span>

                        </div>

                    </th>


                    {{-- 可寸法W --}}
                    <th class="border-r border-[#d9e2f3] px-2 py-2
                               text-right font-bold whitespace-nowrap
                               w-[120px]">

                        <div class="flex items-center justify-between">

                            <span>可寸法W</span>

                            <span class="text-white/90 text-xs">
                                ▼
                            </span>

                        </div>

                    </th>


                    {{-- 厚度 --}}
                    <th class="border-r border-[#d9e2f3] px-2 py-2
                               text-right font-bold whitespace-nowrap
                               w-[100px]">

                        <div class="flex items-center justify-between">

                            <span>厚度</span>

                            <span class="text-white/90 text-xs">
                                ▼
                            </span>

                        </div>

                    </th>


                    {{-- 欠点區分 --}}
                    <th class="border-r border-[#d9e2f3] px-2 py-2
                               text-left font-bold whitespace-nowrap
                               w-[150px]">

                        <div class="flex items-center justify-between">

                            <span>欠点區分</span>

                            <span class="text-white/90 text-xs">
                                ▼
                            </span>

                        </div>

                    </th>


                    {{-- 倉庫 --}}
                    <th class="border-r border-[#d9e2f3] px-2 py-2
                               text-left font-bold whitespace-nowrap
                               w-[170px]">

                        <div class="flex items-center justify-between">

                            <span>倉庫</span>

                            <span class="text-white/90 text-xs">
                                ▼
                            </span>

                        </div>

                    </th>


                    {{-- 值計數－採用枚數 --}}
                    <th class="border-r border-[#d9e2f3] px-2 py-2
                               text-right font-bold whitespace-nowrap
                               w-[150px]">

                        <div class="leading-tight text-center">
                            <div>值</div>
                            <div>計數－採用枚數</div>
                        </div>

                    </th>


                    {{-- 加總－剩餘數2 --}}
                    <th class="px-2 py-2
                               text-right font-bold whitespace-nowrap
                               w-[150px]">

                        <div class="leading-tight text-center">
                            <div>加總－剩餘數2</div>
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

@endsection
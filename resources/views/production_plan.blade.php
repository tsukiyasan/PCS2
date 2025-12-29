<!DOCTYPE html>
<html>
<head>
    <title>生產計劃表</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .search-box { margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="search-box">
        <h2>生產計劃查詢</h2>
        <form method="GET" action="">
            <label>投入預定日：</label>
            {{-- 讓使用者可以選日期 --}}
            <input type="text" name="date" value="{{ $targetDate }}" placeholder="YYYYMMDD">
            <button type="submit">查詢</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>計劃編號</th>
                <th>線別</th>
                <th>製品</th>
                <th>尺寸(S)</th>
                <th>尺寸(L)</th>
                <th>板厚</th>
                <th>投入數</th>
                <th>良品數</th>
                <th>直</th>
                <th>形態</th>
                <th>工程1</th>
                <th>工程2</th>
                <th>工程3</th>
                <th>訂單號</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $row)
                <tr>
                    {{-- 這裡的屬性名稱要對應 SQL 裡的欄位名稱 (通常是小寫) --}}
                    <td>{{ $row->keikaku_no }}</td>
                    <td>{{ $row->line }}</td>
                    <td>{{ $row->seihin }}</td>
                    <td>{{ $row->sunpo_s }}</td>
                    <td>{{ $row->sunpo_l }}</td>
                    <td>{{ $row->itaatsu }}</td>
                    <td>{{ number_format($row->tonyu_su) }}</td>
                    <td>{{ number_format($row->ryohin_su) }}</td>
                    <td>{{ $row->choku }}</td>
                    <td>{{ $row->keitai_name }}</td>
                    <td>{{ $row->kotei_name1 }}</td>
                    <td>{{ $row->kotei_name2 }}</td>
                    <td>{{ $row->kotei_name3 }}</td>
                    <td>{{ $row->order_no }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="14">查無資料</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
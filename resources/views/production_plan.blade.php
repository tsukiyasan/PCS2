<form method="GET" action="" class="flex flex-wrap items-end gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">日期起</label>
        <input type="date" name="date" value="{{ $targetDate }}" class="border-gray-300 rounded-md shadow-sm p-2">
    </div>
    
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">線別</label>
        <select name="line" class="border-gray-300 rounded-md shadow-sm p-2 w-32 bg-white">
            <option value="">全部</option>
            @foreach($lines as $lineOption)
                <option value="{{ $lineOption }}" {{ $filterLine == $lineOption ? 'selected' : '' }}>
                    {{ $lineOption }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">訂單號</label>
        <input type="text" name="order_no" value="{{ $filterOrder }}" placeholder="輸入訂單號..." 
               class="border-gray-300 rounded-md shadow-sm p-2 w-40">
    </div>

    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md shadow">
        查詢
    </button>
    
    <a href="{{ url()->current() }}" class="text-gray-500 hover:text-gray-700 underline text-sm pb-3">
        清除條件
    </a>
</form>
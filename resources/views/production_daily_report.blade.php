<td>
    {{-- 直接 dump 出這一行所有的內容，看看 TOTAL_SETSUDAN 是不是真的在裡面 --}}
    {{-- 有時候會變成小寫的 total_setsudan --}}
    @php dump($row->toArray()); @endphp
</td>
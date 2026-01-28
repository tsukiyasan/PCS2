<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pcsdb extends Model
{
    use HasFactory;

    // 1. 指定連線 (對應 config/database.php 裡的名稱)
    protected $connection = 'oracle';
    
    // 2. 指定資料表名稱 (Oracle 通常是大寫)
    // 如果資料表在不同 Schema，要寫成 'SCHEMA名稱.資料表'，例如 'STARK.HR_CARD_SOURCE'
    //protected $table = 'HR_CARD_SOURCE';

    // 3. ⚠️ 關鍵：關閉自動時間戳記
    // 因為舊的 Oracle 表通常沒有 `created_at` 和 `updated_at` 欄位
    // 如果沒加這行，一存檔或查詢就會報錯
    public $timestamps = false;

    // 4. (選用) 指定主鍵
    // 如果這張表的主鍵不是 'id' (例如是 'CARD_ID' 或 'EMP_NO')，一定要指定
    //protected $primaryKey = '您的主鍵欄位名稱';

    // 5. (選用) 如果主鍵不是數字 (例如是字串 'A001')
    // protected $keyType = 'string';
    // public $incrementing = false;
}
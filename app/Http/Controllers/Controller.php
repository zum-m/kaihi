<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}

public function store(Request $request)
{
    $posts = $request->all();
    $request->validate(['content' => 'required']);
    // dd($posts);
    // dump dieの略 → メソッドの引数の取った値を展開して止める → データ確認

    // ===== ここからトランザクション開始 ======
    DB::transaction(function() use($posts) {
        // メモIDをインサートして取得
        $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => \Auth::id()]);
        $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists(); 
        // 新規タグが入力されているかチェック
        // 新規タグが既にtagsテーブルに存在するのかチェック
        if( !empty($posts['new_tag']) && !$tag_exists ){
            // 新規タグが既に存在しなければ、tagsテーブルにインサート→IDを取得
            $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
            // memo_tagsにインサートして、メモとタグを紐付ける
            MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
        }
        // 既存タグが紐付けられた場合→memo_tagsにインサート
        if(!empty($posts['tags'][0])){
            foreach($posts['tags'] as $tag){
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
            }
        }
    });
    // ===== ここまでがトランザクションの範囲 ======


    return redirect( route('home') );
}

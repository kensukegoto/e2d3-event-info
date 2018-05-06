## 自分用メモ

 **get_post_meta($post_id, $key, $single)** 

$keyが配列の場合はシリアライズされている。
$singleをtrueとするとでデシリアライズされる。
$singleはデフォルトはfalse
この場合は、要素が１つのみの配列が返って来る。

[WPコーデックス(get_post_meta)](https://wpdocs.osdn.jp/%E9%96%A2%E6%95%B0%E3%83%AA%E3%83%95%E3%82%A1%E3%83%AC%E3%83%B3%E3%82%B9/get_post_meta)

---

 **ページに表示させるには** 

`require_once(WP_PLUGIN_DIR.'/e2d3-event-info/class-e2d3-event-info.php');`

投稿情報の配列。ループで必要なものを表示させる

`$e2d3Event = Event_Info_E2D3::getPosts());`

---

 **残作業（5/6）**

- 管理画面用のCSS（URLを消すなど）
- 日付をプルダウンで選べるように
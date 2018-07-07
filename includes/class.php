<?php

class Event_Info_E2D3{

    public static $name = "e2d3_event";

    public static function show_event_list(){

        $args = array(
            'post_type' => self::$name,
            'post_status' => 'publish',
            'numberposts'=> -1
        );

        $posts = get_posts($args);

        $ret = [];

        // 必要なキーの一覧
        $keys = array_keys(self::$meta_event_info);

        foreach($posts as $post){

            $title = get_the_title($post->ID);
            $meta = get_post_meta($post->ID);

            // タイトルが空でない、必要なメタがあるもののみ
            if(empty($title)) continue;

            // 日付のチェック
            if(!isset($meta['evDate']) || !preg_match('|\d{4}\-\d{1,2}\-\d{1,2}|', $meta['evDate'][0])) continue;

            // 更に
            // 9999-99-99 などとなっていないかチェック

            $arr = [];

            $dt =  new DateTime($meta['evDate'][0]);
            $week = array("日", "月", "火", "水", "木", "金", "土");
            $day = array(
              'year'=>$dt->format('Y'),
              'month'=>$dt->format('m'),
              'day'=>$dt->format('d'),
              'week'=>$week[(int)$dt->format('w')],
              'time'=>isset($meta['evTime'])?$meta['evTime'][0]:'',
            );

            // タグを , 区切りで分割
            $tagKind = isset($meta['evKind'])?$meta['evKind'][0]:array();
            if(!empty($tagKind)){
              $tagKind = explode(',',$tagKind);
            }

            $tagTarget = isset($meta['evTarget'])?$meta['evTarget'][0]:array();
            if(!empty($tagTarget)){
              $tagTarget = explode(',',$tagTarget);
            }

            $tagAccept = isset($meta['evAccept'])?$meta['evAccept'][0]:array();
            if(!empty($tagAccept)){
              $tagAccept = explode(',',$tagAccept);
            }

            $tags = array(
              'kind'=>$tagKind,
              'target'=>$tagTarget,
              'accept'=>$tagAccept
            );


            $ret[] = array(
              'title'=>$title,
              'day'=>$day,
              'at'=>isset($meta['evAt'])?$meta['evAt'][0]:'',
              'url'=>isset($meta['evUrl'])?$meta['evUrl'][0]:'',
              'descri'=>isset($meta['evDescri'])?$meta['evDescri'][0]:'',
              'tags'=>$tags
            );


        }

        ?>
        <ul class="eventInfo-coming">
        <?php

        for($i=0,$l=count($ret);$i<$l;$i++){

          $item = $ret[$i];

          $evUrl = (!empty($item['url']))?" href=\"{$item['url']}\"":"";

          $evDay = self::retHTMLStringForDay($item['day']);

          $evTags = array(
            0=>self::retHTMLStringForTag($item['tags']['kind'],'tag'),
            1=>self::retHTMLStringForTag($item['tags']['target'],'tag target'),
            2=>self::retHTMLStringForTag($item['tags']['accept'],'tag open')
          );

          $evTags = implode("",$evTags);


          ?>
          <li><a<?php echo $evUrl; ?>>
              <div class="event-date">
                  <?php echo $evDay; ?>
                  <span class="date-place"><?php echo $item['at']; ?></span>
              </div>
              <div class="event-description">
                  <div class="desc-tag">
                      <?php echo $evTags; ?>
                  </div>
                  <h3 class="desc-eventTitle"><?php echo $item['title']; ?></h3>
                  <p class="desc-text"><?php echo $item['descri']; ?></p>
              </div>
          </a></li>
          <?php
          }

          ?>
        </ul>
          <?php


        // return $ret;
    }


    public static function retHTMLStringForDay($day){
        $ret = "";
        $ret .= "<span class='date-year'>{$day['year']}</span>";
        $ret .= "<span class='date-day'>{$day['month']}/{$day['day']}({$day['week']})<br>{$day['time']}</span>";
        return $ret;
    }

    public static function retHTMLStringForTag($tag,$addclass){
        $ret = "";
        for($i=0,$l=count($tag);$i<$l;$i++){
          $ret .= "<span class='{$addclass}'>{$tag[$i]}</span>";
        }
        return $ret;
    }

    public static $meta_event_info = array(

        'evAt' => array(
            'title'=>'場所',
            'isString'=>true
        ),
        'evDate' => array(
            'title'=>'日付',
            'isString'=>true
        ),
        'evTime' => array(
            'title'=>'時間',
            'isString'=>true
        ),
        'evType' => array(
            'title'=>'内容',
            'isString'=>false,
            'option'=>array('もくもく会','講習会','学生向け','社会人向け','初心者向け')
        ),
        'evKind' => array(
            'title'=>'内容',
            'isString'=>true
        ),
        'evTarget' => array(
            'title'=>'対象',
            'isString'=>true
        ),
        'evAccept' => array(
            'title'=>'受付状況',
            'isString'=>true
        ),
        'evUrl' => array(
            'title'=>'イベントURL',
            'isString'=>true
        ),
        'evDescri' => array(
            'title'=>'イベント紹介文',
            'isString'=>true
        )

    );

    public function __construct(){
        // 既に有効化されてたら実行されない
        register_activation_hook(__FILE__,array($this,'activate'));
        // 読み込み時に有効に
        add_action('init',array($this,'register_post_type_event_info'));
        // 投稿画面を改造
        add_action('admin_head', array($this,'add_css'));
        add_action('admin_menu', array($this,'create_meta_box'));
        // 保存処理 （普通に保存しても処理されない？）
        add_action('save_post', array($this,'save_postdata'));

        add_action('admin_enqueue_scripts', array($this,'add_jquery_ui_css'));

    }



    public function register_post_type_event_info(){

        register_post_type( self::$name,

            array(
                'labels' => array(
                    'name' =>'イベント',
                    'singular_name' => 'イベント',
                    'all_items' =>'イベント一覧',
                    'add_new' => 'イベントを登録',
                    'add_new_item' => 'イベントを登録する',
                    'edit_item' => 'イベントを編集する',
                    'new_item' => '新しいイベント',
                    'view_item' => 'イベントを表示',
                    'search_items' => 'イベントを検索',
                    'not_found' =>  '検索したイベントが見つかりません',
                    'not_found_in_trash' => 'ゴミ箱にイベントはありません',
                    'parent_item_colon' => ''
                ),
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'query_var' => true,
                'menu_position' => 8,
                'menu_icon' => plugins_url() . '/e2d3-event-info/assets/ico_admin_event.png',
                'rewrite'	=> array( 'slug' => 'custom_type', 'with_front' => false ),
                'capability_type' => 'post',
                'hierarchical' => false,
                'supports' => array('title')
            )
        );

    }

    /*** カスタムフィールドコンテンツの作り込み ***/
    public function my_meta_boxes() {

        global $post;

        $meta_event_info = self::$meta_event_info;

        foreach($meta_event_info as $meta => $meta_val) {

            $true = $meta_val['isString'];
            // Ex. $evAtName = 場所
            $nam = $meta.'Name';
            $$nam = $meta_val['title'];
            //Ex. $evAtVal = DBに保存されている値
            $val = $meta.'Val';
            $$val = get_post_meta( $post->ID, $meta, $true );
            var_dump($$val);

        };

        ?>
        <div class="postbox postbox_estate">
            <dl>
                <dt>
                  <div><p>日付</p></div>
                </dt>
                <dd><input type="text" name="evDate" value="<?php echo $evDateVal ?>"></dd>
                <dt>
                  <div>
                    <p>時間</p>
                    <p>例）13時～,13時～18時,時間未定など</p>
                  </div>
                </dt>
                <dd><input type="text" name="evTime" value="<?php echo $evTimeVal ?>"></dd>
                <dt>
                  <div><p>場所</p></div>
                </dt>
                <dd><input type="text" name="evAt" value="<?php echo $evAtVal ?>"></dd>
                <dt>
                  <div><p>イベントURL</p></div>
                </dt>
                <dd><input type="text" name="evUrl" value="<?php echo $evUrlVal ?>"></dd>
                <dt>
                  <div>
                    <p>内容</p>
                    <p>例）もくもく会,講習会<br>
                    「,」(カンマ)区切りで複数表示可</p>
                  </div>
                </dt>
                <dd>
                  <input type="text" name="evKind" value="<?php echo $evKindVal ?>">
                <!-- <?php foreach ($meta_event_info['evType']['option'] as $optn): ?>

                    <label><input type="checkbox" name="evType[]" value="<?php echo $optn ?>"<?php if ( isset($evTypeVal[0]) && is_array($evTypeVal[0]) ) {if ( in_array($optn, $evTypeVal[0]) ) echo ' checked="checked"';} ?> /> <?php echo $optn ?></label>
                <?php endforeach ?> -->
                </dd>
                <dt>
                  <div>
                    <p>対象</p>
                    <p>例）学生向け,初心者向け<br>
                    「,」(カンマ)区切りで複数表示可</p>
                  </div>
                </dt>
                <dd><input type="text" name="evTarget" value="<?php echo $evTargetVal ?>"></dd>
                <dt>
                  <div>
                    <p>受付状況</p>
                    <p>例）予約受付中,受付終了<p>
                </div>
                </dt>
                <dd><input type="text" name="evAccept" value="<?php echo $evAcceptVal ?>"></dd>
                <dt>
                  <div><p>イベント紹介文</p></div>
                </dt>
                <dd><textarea name="evDescri" rows="8" cols="80"><?php
                    echo $evDescriVal;
                  ?></textarea></dd>
            </dl>
        </div>
        <?php

    }

    /*** 投稿画面にカスタムフィールドのセクションを追加 ***/
    public function create_meta_box() {

        if ( function_exists('add_meta_box') ){
            //  add_meta_box('id', 'title', 'callback', 'page', 'context', 'priority');
            // id = メタボックスであるdivタグのid属性値を指定
            // callback = メタボックスの内容を表示する関数名を指定

            // contextには、ここで作成されるmetaboxを何処で表示させるか
            // 例えば pageとするとpage全てに現れる
            // context = ページの種類として'post'、'page'、'link'、'dashboard'、カスタム投稿タイプ名の何れかを指定
            add_meta_box( 'my-meta-boxes', 'お知らせ登録カスタムフィールド', array($this,'my_meta_boxes'), self::$name, 'normal', 'high' );
        }
    }

    /*** カスタムフィールド入力値の保存 ***/
    public function save_postdata( $post_id) {

        global $post;

        // 新規登録時の一時保存処理をスキップ
        if(!isset($post) || $post->post_type!==self::$name){
            return;
        }

        $meta_event_info = self::$meta_event_info;

        foreach($meta_event_info as $meta => $val) {

            $true = $val['isString'];

            // **１つ１つのお知らせが投稿の扱い**
            // 今のカスタムフィールドを取得
            $meta_cur = get_post_meta($post_id, $meta, $true);


            // 保存時に受け取った値
            $meta_new = $_POST[$meta];

            // これだと更新のときと区別がつかん
            if(empty($meta_cur) && !empty($meta_new)){

                add_post_meta($post_id, $meta, $meta_new, true);

            } elseif (empty($meta_new)) {

                delete_post_meta($post_id, $meta);

            } elseif ( $meta_cur != $meta_new ) {

                update_post_meta($post_id, $meta, $meta_new);

            }

        }

    }

    public function add_css(){

        global $post;

        if($post->post_type==="e2d3_event"){
          ?>
          <link type="text/css" href="<?php echo plugins_url( 'style.css', __FILE__ ); ?>" rel="stylesheet" />
          <?php
        }

    }

    public function add_jquery_ui_css(){

      global $wp_scripts;
      $ui = $wp_scripts->query('jquery-ui-core');

      wp_enqueue_style(
        'jquery-ui-smoothness',
        "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css",
        false,
        null
      );

      wp_enqueue_script(
        'jquery-ui-datepicker',
        "//ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js",
        false,
        null
      );

      wp_enqueue_script(
        'my-calendar',
        plugins_url( 'calendar.js', __FILE__ ),
        false,
        null
      );


    }

    public function activate(){

        $this->register_post_type_event_info();
        flush_rewrite_rules();

    }



}

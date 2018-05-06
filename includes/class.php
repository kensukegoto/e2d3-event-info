<?php

class Event_Info_E2D3{
    
    public static $name = "e2d3_event";
    
    public static function getPosts(){
        
        $args = array(
            'post_type' => self::$name,
            'post_status' => 'publish',
            'numberposts'=> -1
        );
        
        $posts = get_posts($args);
        
        $ret = [];
        
        foreach($posts as $post){
//            $arr = [];
            $meta = get_post_meta($post->ID);
            $ret[] = $meta;
//            foreach(self::$meta_event_info as $key){
//                
//            }
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
        'evType' => array(
            'title'=>'内容',
            'isString'=>false,
            'option'=>array('もくもく会','講習会','学生向け','社会人向け','初心者向け')
        ),
        'evUrl' => array(
            'title'=>'イベントURL',
            'isString'=>true
        )
        
    );
    
    public function __construct(){
        // 既に有効化されてたら実行されない
        register_activation_hook(__FILE__,array($this,'activate'));
        // 読み込み時に有効に
        add_action('init',array($this,'register_post_type_event_info'));
        // 投稿画面を改造
        add_action('admin_menu', array($this,'create_meta_box'));
        // 保存処理 （普通に保存しても処理されない？）
        add_action('save_post', array($this,'save_postdata'));
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
                <dt>日付</dt>
                <dd><input type="text" name="evDate" value="<?php echo $evDateVal ?>"></dd>
                <dt>場所</dt>
                <dd><input type="text" name="evAt" value="<?php echo $evAtVal ?>"></dd>
                <dt>イベントURL</dt>
                <dd><input type="text" name="evUrl" value="<?php echo $evUrlVal ?>"></dd>
                <dt>内容</dt>
                <dd>
                <?php foreach ($meta_event_info['evType']['option'] as $optn): ?>
                      
                    <label><input type="checkbox" name="evType[]" value="<?php echo $optn ?>"<?php if ( isset($evTypeVal[0]) && is_array($evTypeVal[0]) ) {if ( in_array($optn, $evTypeVal[0]) ) echo ' checked="checked"';} ?> /> <?php echo $optn ?></label>
                <?php endforeach ?>
                </dd>
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
    
    public function activate(){
        
        $this->register_post_type_event_info();
        flush_rewrite_rules();
        
    }

}



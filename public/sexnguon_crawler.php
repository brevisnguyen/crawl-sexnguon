<?php

/**
 * The public-facing functionality of the plugin.
 * 
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 */
class SexNguon_Crawler {
    private $plugin_name;
    private $version;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function sexnguon_enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name . 'sexnguonjs', plugin_dir_url( __FILE__ ) . 'js/sexnguon.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( $this->plugin_name . 'bootstrapjs', plugin_dir_url( __FILE__ ) . 'js/bootstrap.bundle.min.js', array(), $this->version, false );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function sexnguon_enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sexnguon.css', array(), $this->version, 'all' );
    }

    /**
	 * Make CURL
	 *
	 * @param  string      $url       Url string
	 * @return string|bool $response  Response
	 */
    private function curl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
	 * wp_ajax_sexnguon_crawler_api action Callback function
	 *
	 * @param  string $api url
	 * @return json $page_array
	 */
    public function sexnguon_crawler_api()
    {
        $url = $_POST['api'];
        $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
        $full_url = $url . http_build_query(['ac' => 'list', 'limit' => 30, 'pg' => 1]);
        $latest_url = $url . http_build_query(['ac' => 'list', 'limit' => 30, 'pg' => 1, 'h' => 24]);

        $full_response = $this->curl($full_url);
        $latest_response = $this->curl($latest_url);

        $data = json_decode($full_response);
        $latest_data = json_decode($latest_response);
        if ( !$data ) {
            echo json_encode(['code' => 999, 'message' => 'Mẫu JSON không đúng, không hỗ trợ thu thập']);
            die();
        }
        $input_dom = '<div class="form-check form-check-inline removeable"><input class="form-check-input mt-0" type="radio" name="type_id" value="{type_id}" id="type_{type_id}"><label class="form-check-label" for="type_{type_id}">{type_name}</label></div>';
        $html = '';
        foreach ( $data->class as $type ) {
            $html .= str_replace(['{type_id}', '{type_name}'], [$type->type_id, $type->type_name], $input_dom);
        }
        $page_array = array(
            'code'              => 1,
            'last_page'         => $data->pagecount,
            'update_today'      => $latest_data->total,
            'total'             => $data->total,
            'type_list'         => $html,
            'full_list_page'    => range(1, $data->pagecount),
            'latest_list_page'  => range(1, $latest_data->pagecount),
        );
        echo json_encode($page_array);

        wp_die();
    }

    /**
	 * wp_ajax_sexnguon_get_movies_page action Callback function
	 *
	 * @param  string $api        url
	 * @param  string $param      query params
	 * @return json   $page_array List movies in page
	 */
    public function sexnguon_get_movies_page()
    {
        try {
            $url = $_POST['api'];
            $params = $_POST['param'];
            $type_id = $_POST['type_id'];
            
            $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
            if ( $type_id !== null || $type_id !== '0' ) {
                $params .= '&t=' . $type_id;
            }
            $response = $this->curl($url . $params);

            $data = json_decode($response);
            if ( !$data ) {
                echo json_encode(['code' => 999, 'message' => 'Mẫu JSON không đúng, không hỗ trợ thu thập']);
                die();
            }
            $page_array = array(
                'code'          => 1,
                'movies'        => $data->list,
            );
            echo json_encode($page_array);
    
            wp_die();
        } catch (\Throwable $th) {
            //throw $th;
            echo json_encode(['code' => 999, 'message' => $th]);
            wp_die();
        }
    }

    /**
	 * wp_ajax_sexnguon_crawl_by_id action Callback function
	 *
	 * @param  string $api        url
	 * @param  string $param      movie id
	 */
    public function sexnguon_crawl_by_id()
    {
        $url = $_POST['api'];
        $params = $_POST['param'];
        $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
        $response = $this->curl($url . $params);

        $response = $this->filter_tags($response);
        $data = json_decode($response, true);
        if ( !$data ) {
            echo json_encode(['code' => 999, 'message' => 'Mẫu JSON không đúng, không hỗ trợ thu thập']);
            die();
        }
        $movie_data = $this->refined_data($data['list']);

        $args = array(
			'post_type' => 'post',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_sexnguon_id',
					'value' => $movie_data['movie_id'],
				)
			)
		);
        $wp_query = new WP_Query($args);
        if ( $wp_query->have_posts() ) { // Trùng tên phim
            while ($wp_query->have_posts()) {
                $wp_query->the_post();
                global $post;
                $_halim_metabox_options = get_post_meta($post->ID, '_halim_metabox_options', true);

                if($_halim_metabox_options["halim_episode"] == $movie_data['episode']) { // Tập phim không thay đổi
                    $result = array(
                        'code' => 999,
                        'message' => $movie_data['org_title'] . ' : Không cần cập nhật',
                    );
                    echo json_encode($result);
                    wp_die();
                }

                $_halim_metabox_options["halim_movie_formality"] = $movie_data['type'];
                $_halim_metabox_options["halim_movie_status"] = strtolower($movie_data['status']);
                $_halim_metabox_options["halim_original_title"] = $movie_data['org_title'];
                $_halim_metabox_options["halim_runtime"] = $movie_data['duration'];
                $_halim_metabox_options["halim_episode"] = $movie_data['episode'];
                $_halim_metabox_options["halim_total_episode"] = '';
                $_halim_metabox_options["halim_quality"] = $movie_data['lang'] . ' - ' . $movie_data['quality'];
                update_post_meta($post->ID, '_halim_metabox_options', $_halim_metabox_options);

                update_post_meta($post->ID, '_halimmovies', json_encode($movie_data['episodes'], JSON_UNESCAPED_UNICODE));
                $result = array(
                    'code' => 1,
                    'message' => $movie_data['org_title'] . ' : Cập nhật thành công.',
                );
                echo json_encode($result);
                wp_die();
            }
        }

        $post_id = $this->insert_movie($movie_data);
        update_post_meta($post_id, '_halimmovies', json_encode($movie_data['episodes'], JSON_UNESCAPED_UNICODE));

        $result = array(
			'code' => 1,
			'message' => $movie_data['org_title'] . ' : Thu thập thành công.',
		);
        echo json_encode($result);
        wp_die();
    }

    /**
	 * Refine movie data from api response
	 *
	 * @param  array  $array_data   raw movie data
	 * @param  array  $movie_data   movie data
	 */
    private function refined_data($array_data)
    {
        foreach ($array_data as $key => $data) {
            $type = "single_movies";
            $status = 'completed';

            $categories = $this->format_text($data['type_name']);
            $tags = [];
            array_push($tags, sanitize_text_field($data['vod_name']));
            $tags = array_merge($tags, $this->format_text($data['type_name']));

            $strtime = date_create_from_format('H:i:s', $data['vod_duration']);
            $runtime = $strtime != FALSE ? $strtime->format('g\hi\ms\s') : '';
    
            $movie_data = [
                'title' => trim($data['vod_name']),
                'org_title' => trim($data['vod_name']),
                'pic_url' => $data['vod_pic'],
                'actor' => ['Đang cập nhật'],
                'director' => ['Đang cập nhật'],
                'episode' => 'Full',
                'episodes' => $this->get_play_url($data['vod_play_url'], $data['vod_blurb']),
                'country' => $data['vod_area'],
                'language' => 'Vietsub',
                'year' => date('Y'),
                'content' => trim($data['vod_name']),
                'tags' => $tags,
                'quality' => ['Full HD','HD', '1080P', '720P'][random_int(0, 3)],
                'type' => $type,
                'categories' => $categories,
                'duration' => $runtime,
                'status' => $status,
                'movie_id' => $data['vod_id'],
            ];
        }
        return $movie_data;
    }

    /**
	 * Insert movie to WP posts, save images
	 *
	 * @param  array  $data   movie data
	 */
    private function insert_movie($data)
    {
        $categories_id = [];
        foreach ($data['categories'] as $category) {
            if (!category_exists($category) && $category !== '') {
                wp_create_category($category);
            }
            $categories_id[] = get_cat_ID($category);
        }
        foreach ($data['tags'] as $tag) {
            if (!term_exists($tag) && $tag != '') {
                wp_insert_term($tag, 'post_tag');
            }
        }

        $post_data = array(
            'post_title'   		=> $data['title'],
            'post_content' 		=> $data['content'],
            'post_status'  		=> 'publish',
            'comment_status' 	=> 'closed',
            'ping_status'  		=> 'closed',
            'post_author'  		=> get_current_user_id()
        );
        $post_id = wp_insert_post($post_data);

        $this->save_images($data['pic_url'], $post_id, $data['title'], true);
        $thumb_image_url = get_the_post_thumbnail_url($post_id, 'movie-thumb');
        wp_set_object_terms($post_id, $data['status'], 'status', false);

        $post_format = halim_get_post_format_type($data['type']);
        set_post_format($post_id, $post_format);

        $post_meta_movies = array(
            'halim_movie_formality' => $data['type'],
            'halim_movie_status' => strtolower($data['status']),
            'halim_poster_url' => '',
            'halim_thumb_url' => $thumb_image_url,
            'halim_original_title' => $data['org_title'],
            'halim_trailer_url' => '',
            'halim_runtime' => $data['duration'],
            'halim_rating' => '',
            'halim_votes' => '',
            'halim_episode' => $data['episode'],
            'halim_total_episode' => '',
            'halim_quality' => $data['language'] . ' - ' . $data['quality'],
            'halim_movie_notice' => '',
            'halim_showtime_movies' => '',
            'halim_add_to_widget' => false,
            'save_poster_image' => false,
            'set_reatured_image' => false,
            'save_all_img' => false,
            'is_adult' => false,
            'is_copyright' => false,
        );

        $default_episode = array();
        $ep_sv_add['halimmovies_server_name'] = "Server #Embed";
        $ep_sv_add['halimmovies_server_data'] = array();
        array_push($default_episode, $ep_sv_add);

        wp_set_object_terms($post_id, $data['director'], 'director', false);
        wp_set_object_terms($post_id, $data['actor'], 'actor', false);
        wp_set_object_terms($post_id, sanitize_text_field($data['year']), 'release', false);
        wp_set_object_terms($post_id, $data['country'], 'country', false);
        wp_set_post_terms($post_id, $data['tags']);
        wp_set_post_categories($post_id, $categories_id);
        update_post_meta($post_id, '_halim_metabox_options', $post_meta_movies);
        update_post_meta($post_id, '_halimmovies', json_encode($default_episode, JSON_UNESCAPED_UNICODE));
        update_post_meta($post_id, '_edit_last', 1);
        add_post_meta($post_id, '_sexnguon_id', $data['movie_id']);
        return $post_id;
    }

    /**
	 * Save movie thumbail to WP
	 *
	 * @param  string   $image_url   thumbail url
	 * @param  int      $post_id     post id
	 * @param  string   $posttitle   post title
	 * @param  bool     $set_thumb   set thumb
	 */
    public function save_images($image_url, $post_id, $posttitle, $set_thumb = false)
    {
        require_once( ABSPATH . "/wp-admin/includes/file.php");

        $temp_file = download_url( $image_url, 10 );
        if ( ! is_wp_error( $temp_file ) ) {

            $mime_extensions = array(
                'jpg'          => 'image/jpg',
                'jpeg'         => 'image/jpeg',
                'gif'          => 'image/gif',
                'png'          => 'image/png',
                'webp'         => 'image/webp',
            );

            // Array based on $_FILE as seen in PHP file uploads.
            $file = array(
                'name'     => basename($image_url), // ex: wp-header-logo.png
                'type'     => $mime_extensions[pathinfo( $image_url, PATHINFO_EXTENSION )],
                'tmp_name' => $temp_file,
                'error'    => 0,
                'size'     => filesize( $temp_file ),
            );
        
            $overrides = array(
                'test_form' => false,
                'test_size' => true,
                'test_upload' => true,
            );
        
            // Move the temporary file into the uploads directory.
            $results = wp_handle_sideload( $file, $overrides );
        
            if ( ! empty( $results['error'] ) ) {
                // Insert any error handling here.
            } else {
                $attachment = array(
                    'guid' => $results['url'],
                    'post_mime_type' => $results['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($results['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $results['file'], $post_id);

                if ( $set_thumb != false ) {
                    set_post_thumbnail($post_id, $attach_id);
                }
            }
        }
    }

    /**
	 * Uppercase the first character of each word in a string
	 *
	 * @param  string   $string     format string
	 * @param  array    $arr        string array
	 */
    private function format_text($string)
    {
        $string = str_replace(array('/','，','|','、',',,,'),',',$string);
        $arr = explode(',', sanitize_text_field($string));
        foreach ($arr as &$item) {
            $item = ucwords(trim($item));
            $item = mb_strtoupper(mb_substr($item, 0, 1)).mb_substr($item, 1, mb_strlen($item));
        }
        return $arr;
    }

    /**
	 * Filter html tags in api response
	 *
	 * @param  string   $rs     response
	 * @param  array    $rs     response
	 */
    private function filter_tags($rs)
    {
        $rex = array('{:','<script','<iframe','<frameset','<object','onerror');
        if(is_array($rs)){
            foreach($rs as $k2=>$v2){
                if(!is_numeric($v2)){
                    $rs[$k2] = str_ireplace($rex,'*',$rs[$k2]);
                }
            }
        }
        else{
            if(!is_numeric($rs)){
                $rs = str_ireplace($rex,'*',$rs);
            }
        }
        return $rs;
    }

    /**
	 * Get eposide url
	 *
	 * @param  string    $servers_str
	 * @param  string    $note
	 * @param  string    $urls_str
	 */
    private function get_play_url($link_m3u8, $limk_blurd)
    {
        $server_add = array();
        $episodes = array($link_m3u8, $limk_blurd);
        
        foreach ($episodes as $key => $value) {

            $server_info["halimmovies_server_name"] = strpos($value, 'm3u8') !== false ? 'VIP #1' : 'Dự Phòng';
            $server_info["halimmovies_server_data"] = array();

            $ep_data['halimmovies_ep_name'] = 'Full';
            $ep_data['halimmovies_ep_slug'] = 'full';
            $ep_data['halimmovies_ep_type'] = strpos($value, 'm3u8') !== false ? 'link' : 'embed';
            $ep_data['halimmovies_ep_link'] = $value;
            $ep_data['halimmovies_ep_subs'] = [];
            $ep_data['halimmovies_ep_listsv'] = [];
            $slug_name = str_replace("-", "_", sanitize_title('full'));
            $server_info["halimmovies_server_data"][$slug_name] = $ep_data;
            
            array_push($server_add, $server_info);
        }
        return $server_add;
    }
}

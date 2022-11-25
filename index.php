 /**
  * @param $string
  * @return bool
*/
function is_serialized($string) {
  return (@unserialize($string) !== false);
}
        
add_filter( 'pll_copy_post_metas', function($metas, $sync, $from, $to, $lang) {
  
        // Put your post type here to avoid conflicts
        if(get_post_type($from) != '') {

            return $metas;

        }
        
        global $wpdb;

        $current_language           = pll_get_post_language($from);
        $translations               = pll_get_post_translations($from);

        if(empty($translations[$lang])) {

            $translations[$lang] = $to;

        }

        unset($translations[$current_language]);
  
        
        $meta_to_sync = [
          // Put linked post IDs to sync or keep empty if nothing to sync     
        ];

        foreach (get_post_meta($from) as $key => $value) {

            if(!in_array($key, $meta_to_sync)) {

                continue;

            }

            $new_meta_values = [];

            $is_serialized = is_serialized($value[0]);

            $meta_post_ids = (array)($is_serialized ? unserialize($value[0]) : $value);

            foreach ($meta_post_ids as $meta_post_id) {

                if(empty($meta_post_id)) continue;

                $meta_translations = pll_get_post_translations($meta_post_id);

                unset($meta_translations[$current_language]);

                foreach ($meta_translations as $meta_lang_code => $meta_translation) {

                    $new_meta_values[$meta_lang_code][] = $meta_translation;

                }

            }

            foreach ($translations as $lang_code => $post_id) {

                $meta_value = !empty($new_meta_values[$lang_code]) ? (
                $is_serialized ? serialize($new_meta_values[$lang_code]) : $new_meta_values[$lang_code][0]
                ) : '';

                if(!$sync) {

                    $wpdb->insert(
                        _get_meta_table('post'),
                        [
                            'meta_value' => $meta_value,
                            'post_id' => $post_id,
                            'meta_key' => $key
                        ]
                    );

                } else {

                    $wpdb->update(
                        _get_meta_table('post'),
                        ['meta_value' => $meta_value],
                        [
                            'post_id' => $post_id,
                            'meta_key' => $key
                        ]
                    );

                }

            }

        }

        $excluded_metas = array_merge ([
        
            // Put meta fields to desync
            
        ], $meta_to_sync);

        if ( is_array( $metas ) ) {

            foreach ( $metas as $key => $value ) {

                foreach ( $excluded_metas as $find ) {

                    if ( strpos( $value, $find ) !== false ) {

                        unset( $metas[$key] );

                    }

                }

            }

        }

        return $metas;

    }, 10, 5);

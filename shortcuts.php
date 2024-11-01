<?php
/**
 * Plugin Name: Shortcuts for Admin Bar
 * Description: For smart lazies.
 * Version:           1.0.2
 */
function shortcuts_add_posts_btn( $wp_admin_bar ) {
    $shortcuts_node = array(
        'id'    => 'shortcuts',
        'title' => 'Shortcuts',
    );
    $wp_admin_bar->add_node( $shortcuts_node );

    $return_to_pages_node = array(
        'id'     => 'return_to_pages',
        'title'  => 'Pages',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'edit.php?post_type=page' ),
    );
    $wp_admin_bar->add_node( $return_to_pages_node );

    $open_media_library_node = array(
        'id'     => 'open_media_library',
        'title'  => 'Media Library',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'upload.php' ),
    );
    $wp_admin_bar->add_node( $open_media_library_node );
    
    $open_posts_node = array(
        'id'     => 'open_posts',
        'title'  => 'Posts',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'edit.php' ),
    );
    $wp_admin_bar->add_node( $open_posts_node );
    
    $open_themes_node = array(
        'id'     => 'open_themes',
        'title'  => 'Themes',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'themes.php' ),
    );
    $wp_admin_bar->add_node( $open_themes_node );
    
    $open_permalinks_node = array(
        'id'     => 'open_permalinks',
        'title'  => 'Permalinks',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'options-permalink.php' ),
    );
    $wp_admin_bar->add_node( $open_permalinks_node );

    $menus_node = array(
        'id'     => 'menus',
        'title'  => 'Menus',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'nav-menus.php' ),
    );
    $wp_admin_bar->add_node( $menus_node );

    $all_posts_node = array(
        'id'     => 'all_posts',
        'title'  => 'Posts',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'edit.php' ),
    );

    $open_sliderrev_node = array(
        'id'     => 'open_sliderrev',
        'title'  => 'Slider Revolution',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'admin.php?page=revslider' ),
    );
    $wp_admin_bar->add_node( $open_sliderrev_node );
    
    $wp_admin_bar->add_node( $open_plugins_node );
        $open_plugins_node = array(
        'id'     => 'all_plugins',
        'title'  => 'Plugins',
        'parent' => 'shortcuts',
        'href'   => admin_url( 'plugins.php' ),
    );
    $wp_admin_bar->add_node( $open_plugins_node );
}
add_action( 'admin_bar_menu', 'shortcuts_add_posts_btn', 999 );


/* delete*/
function shortcuts_custom_linkbar( $wp_admin_bar ) {
    global $post;
    if( is_admin() || ! is_object( $post ) )
        return;
    if ( ! current_user_can( 'delete_pages' ) )
        return;
    if ( $post->post_type != 'page' )
        return;
    $args = array(
        'id'    => 'delete_link',
        'title' => 'Delete this page',
        'parent' => 'shortcuts',
        'href'  => get_delete_post_link( $post->ID ),
        'meta'  => array( 'class' => 'delete-link' )
    );
    $wp_admin_bar->add_node( $args );
}
add_action( 'admin_bar_menu', 'shortcuts_custom_linkbar', 999 );

/* Clone */
/**
 * Function creates post duplicate as a draft and redirects then to the edit post screen
 */
function shortcuts_duplicate_draftbtn() {
    global $wpdb;

    if (!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'shortcuts_duplicate_draftbtn' == $_REQUEST['action']))) {
        wp_die('No post to duplicate has been supplied!');
    }

    /* Get the original post id */
    $post_id = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']));

    /* Get the original post data */
    $post = get_post($post_id);

    /* Set post author to current user */
    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;

    /* If post data exists, create the post duplicate */
    if (isset($post) && $post != null) {

         /* Array of data to copy */
        $args = array(
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
            'post_author'    => $new_post_author,
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_name'      => $post->post_name,
            'post_parent'    => $post->post_parent,
            'post_password'  => $post->post_password,
            'post_status'    => 'draft',
            'post_title'     => $post->post_title,
            'post_type'      => $post->post_type,
            'to_ping'        => $post->to_ping,
            'menu_order'     => $post->menu_order
        );

        /* Insert data into new post */
        $new_post_id = wp_insert_post($args);

        /* Get all current post terms and set them to the new post draft */
        $taxonomies = get_object_taxonomies($post->post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        /* Duplicate all post meta just in two SQL queries */
        $post_meta_info = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
        if (count($post_meta_info) != 0) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
            foreach ($post_meta_info as $meta_info) {
                $meta_key = $meta_info->meta_key;
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }
            $sql_query.= implode(" UNION ALL ", $sql_query_sel);
            $wpdb->query($sql_query);
        }

        /* Redirect to the edit post screen for the new draft */
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
    } else {
        wp_die('Post duplication failed, could not find original post: ' . $post_id);
    }
}

add_action('admin_action_shortcuts_duplicate_draftbtn', 'shortcuts_duplicate_draftbtn');



/**
 * Add the duplicate link to action list for post_row_actions and page_row_actions
 * @param  [Array] $actions
 * @param  [Array] $post
 * @return [Array]
 */
function shortcuts_duplicateLink($actions, $post) {
    if (current_user_can('edit_posts')) {
        $actions['duplicate'] = '<a href="admin.php?action=shortcuts_duplicate_draftbtn&amp;post=' . $post->ID . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
    }

    return $actions;
}

add_filter('post_row_actions', 'shortcuts_duplicateLink', 10, 2);
add_filter('page_row_actions', 'shortcuts_duplicateLink', 10, 2);
// Classic Editor indirme ve etkinleştirme düğmesi ekle
add_action( 'admin_bar_menu', 'shortcuts_add_classic_editor', 999 );
function shortcuts_add_classic_editor( $wp_admin_bar ) {
    if ( ! current_user_can( 'install_plugins' ) ) {
        return;
    }
    if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
        $args = array(
            'id'    => 'install-classic-editor',
            'title' => 'Install Classic Editor',
            'href'  => wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=classic-editor' ), 'install-plugin_classic-editor' ),
            'parent' => 'shortcuts',
            'meta'  => array( 'class' => 'install-classic-editor' ),
        );
        $wp_admin_bar->add_node( $args );
    } else {
        $args = array(
            'id'    => 'activate-classic-editor',
            'title' => 'Install Classic Editor',
            'href'  => wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=classic-editor/classic-editor.php' ), 'activate-plugin_classic-editor/classic-editor.php' ),
            'parent' => 'shortcuts',
            'meta'  => array( 'class' => 'activate-classic-editor' ),
        );
        $wp_admin_bar->add_node( $args );
    }
}
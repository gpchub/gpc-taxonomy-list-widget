<?php
/**
 * Plugin Name: GPC Taxonomy List Widget
 * Description: A widget that lists terms from a taxonomy.
 */

class Gpc_Taxonomy_List_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'taxonomy_list_widget',
            __('GPC Taxonomy List Widget', 'text_domain'),
            array('description' => __('List taxonomy terms', 'text_domain'))
        );
    }

    public function widget($args, $instance) {
        $title      = apply_filters('widget_title', $instance['title']);
        $taxonomy   = $instance['taxonomy'];
        $include    = $instance['include'];
        $exclude    = $instance['exclude'];
        $show_thumb = !empty($instance['show_thumb']);
        $show_hierarchy = !empty($instance['show_hierarchy']);
        $show_count     = !empty($instance['show_count']);
        $orderby = !empty($instance['orderby']) ? $instance['orderby'] : 'name';
        $order = !empty($instance['order']) ? $instance['order'] : 'ASC';
        $custom_class = !empty($instance['custom_class']) ? sanitize_html_class($instance['custom_class']) : '';

        echo $args['before_widget'];
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $args_terms = array(
            'taxonomy'     => $taxonomy,
            'hide_empty'   => false,
            'orderby'      => $orderby,
            'order'        => $order,
            'hierarchical' => $show_hierarchy,
        );

        if (!empty($include)) {
            $args_terms['include'] = array_map('intval', explode(',', $include));
        }

        if (!empty($exclude)) {
            $args_terms['exclude'] = array_map('intval', explode(',', $exclude));
        }

        $terms = get_terms($args_terms);

        if (!empty($terms) && !is_wp_error($terms)) {
            echo '<ul class="taxonomy-list ' . esc_attr($custom_class) . '">';
            if ($show_hierarchy) {
                $term_tree = $this->build_term_tree($terms);
                $this->render_term_tree($term_tree, $taxonomy, $show_thumb, $show_count);
            } else {
                foreach ($terms as $term) {
                    echo '<li><div class="term-wrapper">';
                    if ($taxonomy === 'product_cat' && $show_thumb) {
                        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                        if ($thumbnail_id) {
                            $image = wp_get_attachment_image($thumbnail_id, 'thumbnail');
                            if ($image) {
                                echo '<div class="term-thumbnail">' . $image . '</div>';
                            }
                        }
                    }

                    echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
                    if ($show_count) {
                        echo ' <span class="term-count">(' . intval($term->count) . ')</span>';
                    }

                    echo '</div></li>';
                }
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title      = !empty($instance['title']) ? $instance['title'] : '';
        $taxonomy   = !empty($instance['taxonomy']) ? $instance['taxonomy'] : 'category';
        $include    = !empty($instance['include']) ? $instance['include'] : '';
        $exclude    = !empty($instance['exclude']) ? $instance['exclude'] : '';
        $show_thumb = !empty($instance['show_thumb']) ? (bool) $instance['show_thumb'] : false;
        $orderby    = !empty($instance['orderby']) ? $instance['orderby'] : 'name';
        $order    = !empty($instance['order']) ? $instance['order'] : 'asc';

        $taxonomies = get_taxonomies(array('public' => true), 'objects');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('taxonomy')); ?>"><?php _e('Taxonomy:'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('taxonomy')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('taxonomy')); ?>">
                <?php foreach ($taxonomies as $slug => $tax): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($taxonomy, $slug); ?>>
                        <?php echo esc_html($tax->labels->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('orderby')); ?>"><?php _e('Order terms by:'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('orderby')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('orderby')); ?>">
                <option value="name" <?php selected($orderby, 'name'); ?>><?php _e('Name'); ?></option>
                <option value="id" <?php selected($orderby, 'id'); ?>><?php _e('ID'); ?></option>
                <option value="menu_order" <?php selected($orderby, 'menu_order'); ?>><?php _e('Menu Order'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('order')); ?>"><?php _e('Order terms:'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('order')); ?>"
                    name="<?php echo esc_attr($this->get_field_name('order')); ?>">
                <option value="asc" <?php selected($order, 'asc'); ?>><?php _e('Asc'); ?></option>
                <option value="desc" <?php selected($order, 'desc'); ?>><?php _e('DESC'); ?></option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('include')); ?>"><?php _e('Include Term IDs (comma-separated):'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('include')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('include')); ?>" type="text"
                   value="<?php echo esc_attr($include); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('exclude')); ?>"><?php _e('Exclude Term IDs (comma-separated):'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('exclude')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('exclude')); ?>" type="text"
                   value="<?php echo esc_attr($exclude); ?>">
        </p>
        <p>
            <input class="checkbox" type="checkbox"
                   <?php checked($show_thumb); ?>
                   id="<?php echo esc_attr($this->get_field_id('show_thumb')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_thumb')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_thumb')); ?>">
                <?php _e('Show thumbnails (only for product categories)?'); ?>
            </label>
        </p>
        <p>
            <input class="checkbox" type="checkbox"
                <?php checked(!empty($instance['show_hierarchy'])); ?>
                id="<?php echo esc_attr($this->get_field_id('show_hierarchy')); ?>"
                name="<?php echo esc_attr($this->get_field_name('show_hierarchy')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_hierarchy')); ?>">
                <?php _e('Show hierarchy'); ?>
            </label>
        </p>
        <p>
            <input class="checkbox" type="checkbox"
                <?php checked(!empty($instance['show_count'])); ?>
                id="<?php echo esc_attr($this->get_field_id('show_count')); ?>"
                name="<?php echo esc_attr($this->get_field_name('show_count')); ?>" />
            <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>">
                <?php _e('Show post count'); ?>
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('custom_class')); ?>"><?php _e('Custom CSS class for list:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('custom_class')); ?>"
                name="<?php echo esc_attr($this->get_field_name('custom_class')); ?>" type="text"
                value="<?php echo esc_attr($instance['custom_class'] ?? ''); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title']      = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['taxonomy']   = (!empty($new_instance['taxonomy'])) ? strip_tags($new_instance['taxonomy']) : 'category';
        $instance['include']    = sanitize_text_field($new_instance['include']);
        $instance['exclude']    = sanitize_text_field($new_instance['exclude']);
        $instance['show_thumb'] = isset($new_instance['show_thumb']) ? (bool) $new_instance['show_thumb'] : false;
        $instance['show_hierarchy'] = isset($new_instance['show_hierarchy']) ? (bool) $new_instance['show_hierarchy'] : false;
        $instance['show_count']     = isset($new_instance['show_count']) ? (bool) $new_instance['show_count'] : false;
        $instance['orderby'] = in_array($new_instance['orderby'], ['name', 'id', 'menu_order']) ? $new_instance['orderby'] : 'name';
        $instance['order'] = in_array($new_instance['order'], ['asc', 'desc']) ? $new_instance['order'] : 'asc';
        $instance['custom_class'] = sanitize_html_class($new_instance['custom_class']);

        return $instance;
    }

    private function build_term_tree($terms) {
        $tree = array();
        $lookup = array();

        foreach ($terms as $term) {
            $term->children = array();
            $lookup[$term->term_id] = $term;
        }

        foreach ($lookup as $term_id => $term) {
            if ($term->parent && isset($lookup[$term->parent])) {
                $lookup[$term->parent]->children[] = $term;
            } else {
                $tree[] = $term;
            }
        }

        return $tree;
    }

    private function render_term_tree($terms, $taxonomy, $show_thumb, $show_count) {
        foreach ($terms as $term) {
            echo '<li><div class="term-wrapper">';
            if ($taxonomy === 'product_cat' && $show_thumb) {
                $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                if ($thumbnail_id) {
                    $image = wp_get_attachment_image($thumbnail_id, 'thumbnail');
                    if ($image) {
                        echo '<div class="term-thumbnail">' . $image . '</div>';
                    }
                }
            }

            echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
            if ($show_count) {
                echo ' <span class="term-count">(' . intval($term->count) . ')</span>';
            }

            echo '</div>';

            if (!empty($term->children)) {
                echo '<ul>';
                $this->render_term_tree($term->children, $taxonomy, $show_thumb, $show_count);
                echo '</ul>';
            }

            echo '</li>';
        }
    }
}

// Register the widget
function gpc_register_taxonomy_list_widget() {
    register_widget('Gpc_Taxonomy_List_Widget');
}
add_action('widgets_init', 'gpc_register_taxonomy_list_widget');

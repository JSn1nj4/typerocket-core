<?php
/**
 * Get model by recourse
 *
 * @param string $resource use the resource name to get model
 *
 * @return null
 */
function tr_get_model($resource) {

    $Resource = ucfirst($resource);
    $model = "\\TypeRocket\\Models\\{$Resource}Model";
    $object = null;

    if( ! class_exists($model) ) {
        $model = "\\" . TR_APP_NAMESPACE . "\\Models\\{$Resource}Model";
    }

    if( class_exists($model) ) {
        /** @var \TypeRocket\Models\Model $object */
        $object = new $model;
    }

    return $object;
}

/**
 * Register taxonomy
 *
 * @param $singular
 * @param null $plural
 * @param array $settings
 *
 * @return \TypeRocket\Taxonomy
 */
function tr_taxonomy(
    $singular,
    $plural = null,
    $settings = []
) {
    $obj = new \TypeRocket\Taxonomy($singular, $plural, $settings);
    $obj->addToRegistry();

    return $obj;
}

/**
 * Register post type
 *
 * @param string $singular Singular name for post type
 * @param string|null $plural Plural name for post type
 * @param array $settings The settings for the post type
 *
 * @return \TypeRocket\PostType
 */
function tr_post_type(
    $singular,
    $plural = null,
    $settings = []
) {
    $obj = new \TypeRocket\PostType($singular, $plural, $settings);
    $obj->addToRegistry();

    return $obj;
}

/**
 * Register meta box
 *
 * @param null $name
 * @param null $screen
 * @param array $settings
 *
 * @return \TypeRocket\MetaBox
 */
function tr_meta_box(
    $name = null,
    $screen = null,
    $settings = []
) {
    $obj = new \TypeRocket\MetaBox($name, $screen, $settings);
    $obj->addToRegistry();

    return $obj;
}

/**
 * @param $resource
 * @param $action
 * @param $title
 * @param array $settings
 *
 * @return \TypeRocket\Page
 */
function tr_page( $resource, $action, $title, array $settings = []) {
    $obj = new \TypeRocket\Page($resource, $action, $title, $settings);
    $obj->addToRegistry();

    return $obj;
}

/**
 * @param string $singular
 * @param string $plural
 * @param array $settings
 *
 * @return \TypeRocket\Page
 */
function tr_resource_pages( $singular, $plural = null, array $settings = [] ) {

    if( ! $plural ) {
        $plural = \TypeRocket\Inflect::pluralize($singular);
    }

    return tr_page($plural, 'index', $plural, $settings)->apply(
        tr_page($plural, 'edit', 'Edit ' . $singular )->useController()->addNewButton()->removeMenu(),
        tr_page($plural, 'show', $singular )->useController()->addNewButton()->removeMenu(),
        tr_page($plural, 'delete', 'Delete ' . $singular )->useController()->removeMenu(),
        tr_page($plural, 'add', 'Add ' . $singular )->useController()->setArgument('menu', 'Add New')
    )->addNewButton()->useController();
}

/**
 * Create tabs
 *
 * @return \TypeRocket\Layout\Tabs
 */
function tr_tabs()
{
    return new \TypeRocket\Layout\Tabs();
}

/**
 * Create tables
 *
 * @param $model
 *
 * @return \TypeRocket\Layout\Tables
 */
function tr_tables( \TypeRocket\Models\SchemaModel $model ) {
    return new \TypeRocket\Layout\Tables( $model );
}

/**
 * Create buffer
 *
 * @return \TypeRocket\Buffer
 */
function tr_buffer()
{
    return new \TypeRocket\Buffer();
}

/**
 * Instance the From
 *
 * @param string $resource posts, users, comments, options your own
 * @param string $action update or create
 * @param null|int $item_id you can set this to null or an integer
 *
 * @return \TypeRocket\Form
 */
function tr_form($resource = 'auto', $action = 'update', $item_id = null )
{
    return new \TypeRocket\Form($resource, $action, $item_id);
}

/**
 * Get the posts field
 *
 * @param string $name use dot notation
 * @param null $item_id
 *
 * @return array|mixed|null|string
 */
function tr_posts_field( $name, $item_id = null )
{
    global $post;

    if (isset( $post->ID ) && is_null( $item_id )) {
        $item_id = $post->ID;
    }

    $model = new \TypeRocket\Models\PostTypesModel();
    $model->findById($item_id);

    return $model->getFieldValue( $name );
}

/**
 * Get components
 *
 * @param string $name use dot notation
 * @param null $item_id
 */
function tr_posts_components_field( $name, $item_id = null ) {
    global $post;

    if (isset( $post->ID ) && is_null( $item_id )) {
        $item_id = $post->ID;
    }

    $model = new \TypeRocket\Models\PostTypesModel();
    $model->findById($item_id);

    $builder_data = $model->getFieldValue( $name );

    if( is_array($builder_data) ) {
        foreach($builder_data as $data) {
            $key = key($data);
            $component = strtolower(key($data));
            $function = 'tr_component_' . $name . '_' . $component;
            if(function_exists($function)) {
                $function($data[$key]);
            } else {
                echo "<div class=\"tr-dev-alert-helper\"><i class=\"icon tr-icon-bug\"></i> Add builder content here by defining: <code>function {$function}(\$data) {}</code></div>";
            }
        }
    }

}

/**
 * Get users field
 *
 * @param string $name use dot notation
 * @param null $item_id
 *
 * @return array|mixed|null|string
 */
function tr_users_field( $name, $item_id = null )
{
    global $user_id, $post;

    if (isset( $user_id ) && is_null( $item_id )) {
        $item_id = $user_id;
    } elseif (is_null( $item_id ) && isset( $post->ID )) {
        $item_id = get_the_author_meta( 'ID' );
    } elseif (is_null( $item_id )) {
        $item_id = get_current_user_id();
    }

    $model = new \TypeRocket\Models\UsersModel();
    $model->findById($item_id);

    return $model->getFieldValue( $name );
}

/**
 * Get options
 *
 * @param string $name use dot notation
 *
 * @return array|mixed|null|string
 */
function tr_options_field( $name )
{
    $model = new \TypeRocket\Models\OptionsModel();

    return $model->getFieldValue( $name );
}

/**
 * Get comments field
 *
 * @param string $name use dot notation
 * @param null $item_id
 *
 * @return array|mixed|null|string
 */
function tr_comments_field( $name, $item_id = null )
{
    global $comment;

    if (isset( $comment->comment_ID ) && is_null( $item_id )) {
        $item_id = $comment->comment_ID;
    }

    $model = new \TypeRocket\Models\CommentsModel();
    $model->findById($item_id);

    return $model->getFieldValue( $name );
}

/**
 *  Get taxonomy field
 *
 * @param string $name use dot notation
 * @param string $taxonomy taxonomy id
 * @param null|int $item_id
 *
 * @return array|mixed|null|string
 */
function tr_taxonomies_field( $name, $taxonomy, $item_id = null )
{
    /** @var \TypeRocket\Models\TaxonomiesModel $model */
    $model = tr_get_model($taxonomy);
    $model->findById($item_id);

    return $model->getFieldValue( $name );
}

/**
 * Get resource
 *
 * @param string $name use dot notation
 * @param string $resource
 * @param null|int $item_id
 *
 * @return array|mixed|null|string
 */
function tr_resource_field( $name, $resource, $item_id = null )
{
    /** @var \TypeRocket\Models\TaxonomiesModel $model */
    $model = tr_get_model($resource);
    $model->findById($item_id);

    return $model->getFieldValue( $name );
}

/**
 * Detect is JSON
 *
 * @param $string
 *
 * @return bool
 */
function tr_is_json( $string ) {
    $j = json_decode($string);
    $r = $j ? true : false;
    return $r;
}

/**
 * Enable TypeRocket on the front end of the website
 */
function tr_frontend() {
    $core = new TypeRocket\Core(false);
    $core->initFrontEnd();
}

/**
 * @return \TypeRocket\Http\Redirect
 */
function tr_redirect() {
    return new \TypeRocket\Http\Redirect();
}

/**
 * @return \TypeRocket\Http\Cookie
 */
function tr_cookie() {
    return new \TypeRocket\Http\Cookie();
}

/**
 * @param $dots
 * @param array $data
 *
 * @return \TypeRocket\View
 */
function tr_view( $dots , array $data = [] ) {
    return new \TypeRocket\View( $dots, $data );
}
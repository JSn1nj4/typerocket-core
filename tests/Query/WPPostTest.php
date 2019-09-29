<?php
declare(strict_types=1);

namespace Query;

use PHPUnit\Framework\TestCase;
use TypeRocket\Database\Results;
use TypeRocket\Models\WPPost;

class WPPostTest extends TestCase
{
    public function testBasicPostTypeSelect()
    {
        $post = new WPPost('post');
        $posts = $post->published()->get();
        $this->assertTrue( $posts instanceof Results);
    }

    public function testPostTypeSelectWhereMeta()
    {
        $post = new WPPost('post');
        $compiled = (string) $post->whereMeta('meta_key', 'like', 'Hello%')->getQuery();
        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta ON `wp_posts`.`ID` = `wp_postmeta`.`post_id` WHERE post_type = \'post\' AND (  `wp_postmeta`.`meta_key` = \'meta_key\' AND `wp_postmeta`.`meta_value` like \'Hello%\' ) ';
        $posts = $post->get();
        $this->assertTrue( $posts instanceof Results);
        $this->assertTrue( $sql === $compiled);
    }

    public function testPostTypeSelectWhereMetaTwice()
    {
        $post = new WPPost('post');
        $compiled = (string) $post->whereMeta('meta_key', 'like', 'Hello%')->whereMeta('meta_key', 'like', 'Hello%', 'OR')->getQuery();
        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta ON `wp_posts`.`ID` = `wp_postmeta`.`post_id` WHERE post_type = \'post\' AND (  `wp_postmeta`.`meta_key` = \'meta_key\' AND `wp_postmeta`.`meta_value` like \'Hello%\' )  OR (  `wp_postmeta`.`meta_key` = \'meta_key\' AND `wp_postmeta`.`meta_value` like \'Hello%\' ) ';
        $this->assertTrue( $sql === $compiled);
    }

    public function testPostTypeSelectWhereMetaDefault()
    {
        $post = new WPPost('post');
        $compiled = (string) $post->whereMeta('meta_key')->getQuery();
        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta ON `wp_posts`.`ID` = `wp_postmeta`.`post_id` WHERE post_type = \'post\' AND (  `wp_postmeta`.`meta_key` = \'meta_key\' AND `wp_postmeta`.`meta_value` != NULL ) ';
        $this->assertTrue( $sql === $compiled);
    }

    public function testPostTypeSelectWhereMetaArrayValue()
    {
        $compiled = (string) (new WPPost('post'))
            ->whereMeta([
                [
                    'column' => 'meta_key',
                    'operator' => 'like',
                    'value' => 'Hello%'
                ],
                'AND',
                [
                    'column' => 'meta_key',
                    'operator' => '!=',
                    'value' => null
                ]
            ])
            ->getQuery();

        $sql = 'SELECT DISTINCT `wp_posts`.* FROM wp_posts INNER JOIN wp_postmeta ON `wp_posts`.`ID` = `wp_postmeta`.`post_id` WHERE post_type = \'post\' AND (  (  `wp_postmeta`.`meta_key` = \'meta_key\' AND `wp_postmeta`.`meta_value` like \'Hello%\' )  AND (  `wp_postmeta`.`meta_key` = \'meta_key\' AND `wp_postmeta`.`meta_value` != NULL )  ) ';

        $this->assertTrue( $sql === $compiled);
    }

}

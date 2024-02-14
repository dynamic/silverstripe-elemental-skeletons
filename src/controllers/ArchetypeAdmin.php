<?php

namespace DNADesign\ElementalArchetypes\Controllers;

use DNADesign\ElementalSkeletons\Models\Skeleton;
use SilverStripe\Admin\ModelAdmin;

/**
 * @package elemental
 */
class SkeletonAdmin extends ModelAdmin {
    private static array $allowed_actions = [
        'createPage',
    ];

    private static $managed_models = array(
        Skeleton::class,
    );

    private static $menu_title = 'Element Skeletons';

    private static $url_segment = 'elemental-skeletons';
}

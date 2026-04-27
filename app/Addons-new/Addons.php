<?php 
namespace FlowRead\Addons;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Use necessary namespaces
use FlowRead\Addons\ReadingProgressBar\ReadingProgressBar;
use FlowRead\Addons\ArticleReadTime\ArticleReadTime;
use FlowRead\Addons\DynamicWordCounter\DynamicWordCounter;

class Addons {
    public function __construct() { 
        // Load Addons 
        $this->load_addons();
    }

    public function load_addons() { 
        // Load Reading Progress Bar Addon
        new ReadingProgressBar();

        // Load Article Read Time Addon
        new ArticleReadTime();

        // Load Dynamic Word Counter Addon
        new DynamicWordCounter();
        // echo "hello from addons";
        // exit;

    }

    // Load Addons content in admin settings page
    public function render_addons_content() { 
       
    }
}

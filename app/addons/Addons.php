<?php 
namespace FlowRead\Addons;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Use necessary namespaces
use FlowRead\Addons\ReadingProgressBar\ReadingProgressBar;

class Addons {
    public function __construct() { 
        // Load Addons 
        $this->load_addons();
    }

    public function load_addons() { 
        // Load Reading Progress Bar Addon
        new ReadingProgressBar();
        // echo "hello from addons";
        // exit;

    }

    // Load Addons content in admin settings page
    public function render_addons_content() { 
       
    }
}

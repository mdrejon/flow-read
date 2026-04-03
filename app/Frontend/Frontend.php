<?php
/**
 * Frontend Handler Class
 * 
 * @package FlowRead\Frontend
 * @since 1.0.0
 */

namespace FlowRead\Frontend;
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Frontend Class
 */
class Frontend {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     * 
     * @return void
     */
    private function init_hooks() { 
        //
    }
 
}

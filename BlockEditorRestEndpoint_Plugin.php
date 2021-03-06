<?php


include_once('BlockEditorRestEndpoint_LifeCycle.php');

class BlockEditorRestEndpoint_Plugin extends BlockEditorRestEndpoint_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'ATextInput' => array(__('Enter in some text', 'my-awesome-plugin')),
            'AmAwesome' => array(__('I like this awesome plugin', 'my-awesome-plugin'), 'false', 'true'),
            'CanDoSomething' => array(__('Which user role can do something', 'my-awesome-plugin'),
                                        'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber', 'Anyone')
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr) > 0) {
                    $this->addOption($key, $arr[0]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'Block Editor REST endpoint';
    }

    protected function getMainPluginFileName() {
        return 'block-editor-rest-endpoint.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37
        add_action('wp_footer', array(&$this, 'get_blocks_array'));
        add_action('rest_api_init', array(&$this, 'provide_post_as_block'));



        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }



    public function provide_post_as_block()
	{
		register_rest_route( 'stiegi/v1', 'post/(?P<id>\d+)',array(
			'methods'  => 'GET',
			'callback' => array(&$this, 'get_post')
		));
	}


	function get_post($request) {
		global $wpdb;
		$id = $request['id'];
		$post_content = $wpdb->get_results('SELECT post_content FROM ' . $wpdb->posts .  ' WHERE post_type = "post" AND ID = ' . $id . ' LIMIT 10');
		if (empty($post_content)) {
			return new WP_Error( 'empty_post', 'there is no post with this id', array('status' => 404) );
		}

		$response = new WP_REST_Response($this->get_blocks_array($id));
		$response->set_status(200);
		return $response;
	}

	public function get_blocks_array($id)
	{
		global $wpdb;
		$result = $wpdb->get_results('SELECT post_content FROM ' . $wpdb->posts .  ' WHERE post_type = "post" AND ID = "' . $id . '" LIMIT 10');
		$reg = '/<!--\swp:(.+?)\s-->\n(.*?)\n/';
		preg_match_all($reg, $result[0]->post_content, $matches);
		$output = array();
		for ($x = 1; $x < count($matches); $x++) {
			foreach($matches[$x] as $key => $match) {
				$property_name = 'type';
				if ($x === 2) {
					$property_name = "content";
				}
				$output[$key][$property_name] = $match;
			}
		}
		return $output;
	}


}

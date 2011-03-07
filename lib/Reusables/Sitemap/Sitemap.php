<?php

/**
 * Reusable component
 * @package xReusable
**/
class xSiteMap {

    /**
     * Path for generated sitemap html file.
     * If not set, cache mecanism is not used.
     * @var string
     */
    public $cache_path;
    
    /**
     * Controllers and actions to exlude from sitemap.
     * Note: Controller and actions names must be lowercase.
     * Structure convention:
     * <code>
     * array (
     *     'controller_name_1' => true, // Excude this controller from sitemap
     *     'controller_name_2' => array(
     *         'action_1', // Exclude action_1 from sitemap
     *         'action_2'  // Exclude action_2 from sitemap
     *     )
     * )
     * </code>
     * @var array
     */
    public $excludes = array();

    /**
     * Controllers custom titles for sitemap
     * Conventions:
     * array (
     *     'controller_name_1' => _('Custom Title 1'),
     *     'controller_name_2' => _('Custom Title 2')
     * )
     * @var array
     */
    public $controller_titles = array();

    public $prepends = array();
    public $appends = array();

    function __construct($options) {
        foreach ($options as $key => $value) $this->$key = $value;
    }

    function render() {
        if ($this->cache_path) {
            $lang = xContext::$lang;
            $cache_filename = "{$this->cache_path}/sitemap-generated.{$lang}.tpl";
            if (!file_exists($this->cache_path)) mkdir($this->cache_path, 0775, true);
            if (!file_exists($cache_filename)) file_put_contents($cache_filename, $this->generate());
            return file_get_contents($cache_filename);
        } else {
            return $this->generate();
        }
    }

    protected function generate() {
        xContext::$config->prevent_redirect = true;
        $map = $this->map();
        $view = xView::load(null);
        $view->path = dirname(__file__);
        return $view->apply('sitemap.tpl', array('items'=>$map));
    }

    /**
     * Return an array containing the following structure:
     * <code>
     * array(
     *    'controller_name' => array(
     *        'action_1_name' => array(
     *            'title' => 'title string',
     *            'url' => 'action url'
     *    ),
     *        'action_2_name' => array(
     *            'title' => 'title string',
     *            'url' => 'action url'
     *    )
     * )
     * </code>
     * @return array Map array structure reflecting site controllers and actions
     */
    protected function map() {
        $map = array();
        // Parses controllers
        $controllers = $this->controllers();
        foreach ($controllers as $controller) {
            // Builds controller name
            $controller_name = strtolower(substr(get_class($controller), 0, -strlen('Controller')));
            // Excludes controller if specified in $this->excludes
            $controller_excludes = array_shift(xUtil::filter_keys($this->excludes, $controller_name));
            if ($controller_excludes === true) continue;
            // Parses actions
            $actions = $this->actions($controller);
            foreach ($actions as $action) {
                // Excludes action if specified in $this->excludes
                if (@in_array($action, $this->excludes[$controller_name])) continue;
                // Creates map item
                try {
                    xContext::$log->log("Parsing $controller_name::$action", $this);
                    $controller_display = (isset($this->controller_titles[$controller_name])) ?
                        $this->controller_titles[$controller_name] : $controller_name;
                    $map[$controller_display][$action]['title'] = $this->title($controller, $action);
                    $map[$controller_display][$action]['url'] = xUtil::url("{$controller_name}/{$action}");
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        // TODO: Prepends map contents
        //xUtil::pre($this->prepends, $map);
        //$map = xUtil::array_merge($map, $this->prepends);
        return $map;
    }

    /**
     * @return array xController instances
     */
    protected function controllers() {
        $exclude_files = array('^\.');
        // Gets path files
        $path = xContext::$basepath.'/controllers';
        $files = scandir($path);
        // Filter files
        foreach ($files as $i => $file) {
            foreach ($exclude_files as $exclude_file) {
                if (preg_match("/{$exclude_file}/", $file)) unset($files[$i]);
                if (is_dir("{$path}/{$file}")) unset($files[$i]);
            }
        }
        // Get controller instance
        $controllers = array();
        foreach ($files as $file) {
            $class = substr($file, 0, -4);
            $controllers[] = xController::load($class);
        }
        return $controllers;
    }

    /**
     * @return array xController action names
     */
    protected function actions($controller_instance) {
        $methods = get_class_methods($controller_instance);
        // Filters action methods
        $actions = array();
        foreach ($methods as $i => $method) {
            if ($method{0} == '_') continue;
            $method = strtolower($method);
            if (substr($method, -strlen('action')) != 'action') continue;
            $actions[] = substr($method, 0, -strlen('action'));
        }
        return $actions;
    }

    /**
     * @return string xController action title metadata if existing,
     *                action name otherwise
     */
    protected function title($controller_instance, $action_name) {
        $controller_instance->call($action_name);
        $title = @$controller_instance->meta['title'];
        return $title ? $title : ucfirst($action_name);
    }

}

?>
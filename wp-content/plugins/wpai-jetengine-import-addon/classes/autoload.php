<?php

namespace Wpai\Metabox;

class PMJI_Autoloader {
    use \Wpai\AddonAPI\Singleton;

    public function __construct() {
        require PMJI_ROOT_DIR . '/classes/fields.php';
        require PMJI_ROOT_DIR . '/classes/cct-handler.php';
        require PMJI_ROOT_DIR . '/classes/cct-importer.php';
        require PMJI_ROOT_DIR . '/classes/addon.php';
        require PMJI_ROOT_DIR . '/classes/transformers.php';
        require PMJI_ROOT_DIR . '/classes/relations.php';

        spl_autoload_register([$this, 'autoload']);
    }

    public function loadIfFound(string $path) {
        $path = PMJI_ROOT_DIR . '/' . $path . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }

    public function autoload($class) {
        if (!str_contains($class, 'PMJI_')) return;

        $parts = explode('\\', $class);
        $className = end($parts);
        $className = str_replace('PMJI_', '', $className);
        $className = str_replace('_', '-', $className);
        $className = strtolower($className);
        $className = str_replace('-field', '', $className); // E.g. Rename "text-field" to "text"

        $this->loadIfFound('custom-fields/' . $className . '/' . $className);
    }
}

PMJI_Autoloader::getInstance();

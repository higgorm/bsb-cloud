<?php
    if(defined('VERSAO_GESTAO_EMPRESARIAL')==false) {
        define('VERSAO_GESTAO_EMPRESARIAL',"2.16");
    }

    if(defined('FORMATO_ESCRITA_DATA_HORA')==false) {
        define('FORMATO_ESCRITA_DATA_HORA','d-m-Y H:i:s');
    }

    if(defined('FORMATO_ESCRITA_DATA')==false) {
        define('FORMATO_ESCRITA_DATA','d-m-Y');
    }

    /**
     * This makes our life easier when dealing with paths. Everything is relative
     * to the application root now.
     */
    chdir(dirname(__DIR__));

    // Setup autoloading
    require 'init_autoloader.php';

    // Run the application!
    Zend\Mvc\Application::init(require 'config/application.config.php')->run();
<?php

class IUGULogger {
    /**
     * identifier to WC_Logger
     * @var string
     */
    private $identifier;

    /**
     * @var WC_Logger
     */
    private $main_logger;

    /**
     * @var boolean
     */
    private $is_active;

    public function __construct($identifier, $is_active) {
        $this->main_logger = new WC_Logger();
        $this->identifier  = $identifier;
        $this->is_active   = $is_active;
    }

    /**
     * @return boolean
     */
    public function add($identifier, $message) {
        if ($this->is_active) {
            $_identifier = $identifier;
            if ($_identifier == null) {
                $_identifier = $this->identifier;
            }
            $this->main_logger->add($_identifier, $message);
            return true;
        }
        return false;
    }
}

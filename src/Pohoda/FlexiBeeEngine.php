<?php

namespace FlexiPeeHP\Pohoda;

/**
 * Description of Engine
 *
 * @author vitex
 */
class FlexiBeeEngine extends \FlexiPeeHP\FlexiBeeRW {

    /**
     * Configuration
     * @var array
     */
    public $configuration = [];

    /**
     * Where do i get configuration ?
     * @var string
     */
    public $configFile = '../config.json';

    /**
     * Objekt Banky
     * @var \FlexiPeeHP\FakturaVydana
     */
    public $invoicer;

    /**
     * Objekt Banky
     * @var \FlexiPeeHP\Banka
     */
    public $banker;

    /**
     * Objekt Pokladny
     * @var \FlexiPeeHP\PokladniPohyb
     */
    public $casher;

    /**
     * Pole dat z původního XML
     * @var array
     */
    public $xmlData = [];

    /**
     * FlexiBee worker
     *
     * @param array $options Connection settings override
     */
    public function __construct($options = []) {
        if (isset($options['config'])) {
            $this->configFile = $options['config'];
        }
        \Ease\Shared::singleton()->loadConfig($this->configFile, true);
        parent::__construct(null, $options);
        $this->invoicer = new \FlexiPeeHP\FakturaVydana();
        $this->banker = new \FlexiPeeHP\Banka();
        $this->casher = new \FlexiPeeHP\PokladniPohyb();
    }

    /**
     * Loguje info o výsledku operace importu.
     *
     * @param \FlexiPeeHP\FlexiBeeRW $engine
     * @param string $id ID záznamu
     * @param string $caption
     */
    public function logOperationResult($engine, $id, $caption) {
        if ($engine->lastResponseCode == 201) {
            if ($engine->lastResult['stats']['created'] == 1) {
                $operation = 'Insert';
                $importStatus = 'success';
            } else {
                $operation = 'Update';
                $importStatus = 'warning';
            }
        } else {
            $importStatus = 'error';
            if ($engine->recordExists(['id' => $id])) {
                $operation = 'Update';
            } else {
                $operation = 'Insert';
            }
        }
        $engine->addStatusMessage($operation . ' ' . $id . ' ' . $caption, $importStatus);
    }

}

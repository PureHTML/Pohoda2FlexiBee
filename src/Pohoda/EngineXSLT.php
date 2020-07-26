<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace FlexiPeeHP\Pohoda;

/**
 * Description of EngineXSLT
 *
 * @author vitex
 */
class EngineXSLT extends FlexiBeeEngine {

    public function import($xmlFile) {
        $this->logBanner(\Ease\Functions::cfg('EASE_APPNAME'));
        if (!file_exists($xmlFile)) {
            throw new Exception(_('File not exists') . ': ' . $xmlFile);
        }
        return $this->importXML(file_get_contents($xmlFile));
    }

    public function importXML($xmlRaw) {
        if ($this->checkXSLTs() == false) {
            $this->updateXSLTs();
        }
        return $this->importXMLwithXSLT($xmlRaw, 'shoptet-faktury', 'faktury', true);
    }

    public function checkXSLTs() {
        return true; //TODO: check XSLT presence & freshness
    }

    /**
     * Import all files in ./xslt directory
     */
    public function updateXSLTs($dir = null) {

        $dir = empty($dir) ? dirname(__FILE__) . '/../xslt' : $dir;
        $evb = $this->getEvidence();
        $this->setEvidence('xslt');
        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if (($entry[0] != '.') && (substr($entry, -5) == '.xslt')) {
                $importdata = [];
                $importdata['nazev'] = str_replace('.xslt', '', $entry);
                $importdata['id'] = self::code(strtoupper($importdata['nazev']));
                $importdata['transformace'] = file_get_contents($dir . '/' . $entry);
                $this->insertToFlexiBee($importdata);
                $this->addStatusMessage('XSLT: ' . $importdata['nazev'],
                        $this->lastResponseCode == 201 ? 'success' : 'error' );
            }
        }
        $d->close();
        if (!empty($evb)) {
            $this->setEvidence($evb);
        }
    }

    /**
     * Import XML Data Using given XSLT
     *
     * @param string $xml
     * @param string $xslt
     *
     * @retur booelan operation restult status
     */
    public function importXMLwithXSLT($xml, $xslt, $label = '', $debug = false) {
        $this->errors = [];
        $this->setPostFields($xml);
        $this->setEvidence('faktura-vydana');

        $result = $this->performRequest('?format=' . self::code($xslt),
                'POST', 'xml');

        if ($this->lastResponseCode == 201) {
            $this->addStatusMessage('Data byla naimportována pomocí ' . $xslt,
                    'success');
            $state = 'bad';
        } else {
            $result = $this->lastResult;
            $this->addStatusMessage('Data nebyla naimportována  pomocí ' . $xslt . ' bez problémů',
                    'warning');
            $state = 'good';
        }

        if ($debug) {
            $this->saveXSLTizedXML($xml, $xslt, $label, $state);
        }

        return $result;
    }

    public function saveXSLTizedXML($xml, $xslt, $prefix, $suffix = '.') {
        $formatter = new \PrettyXml\Formatter();
        $inputFile = '../log/' . $prefix . '-input.xml';
        $outputFile = '../log/' . $prefix . '_' . $xslt . '-inport.xml';
        $transformFileRaw = dirname(__DIR__) . '/xslt/' . strtolower($xslt) . '.xslt';
        $transformFile = '../log/' . $xslt . '.xslt';
        file_put_contents($transformFile, file_get_contents( $transformFileRaw));
        file_put_contents($inputFile, $formatter->format($xml));
        $cmd = "/usr/share/flexibee/bin/flexibee2xml --from-xml $inputFile --run-xslt $transformFile --to-xml $outputFile";
        system("$cmd 2>&1 /dev/null");
        $errors = "\n";
        if (!empty($this->getErrors())) {
            foreach ($this->getErrors() as $errorCode => $error) {
                if (key($error) == 0) {
                    foreach ($error as $message) {
                        $errors .= $errorCode . ': ' . $message . "\n";
                    }
                } else {
                    $errors .= $errorCode . ': ' . isset($error['message']) ? $error['message'] : $error . "\n";
                }
            }
        }
        file_put_contents($outputFile,
                "<!-- \nXSLT: $transformFile\n$errors -->" . $formatter->format(file_get_contents($outputFile)));
        $this->addStatusMessage($suffix . ' XML Saved as: ' . realpath($outputFile), 'debug');
    }

}

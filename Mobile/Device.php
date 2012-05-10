<?php
/**
 * Mobile Device
 *
 * Copyright (c) 2011, Hans-Peter Buniat <hpbuniat@googlemail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in
 * the documentation and/or other materials provided with the
 * distribution.
 *
 * * Neither the name of Hans-Peter Buniat nor the names of his
 * contributors may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package Mobile Device
 * @author Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @copyright 2011 Hans-Peter Buniat <hpbuniat@googlemail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

class Mobile_Device {

    /**
     * Error Message, if there was a check for a unknown device-class
     *
     * @var string
     */
    const UNKNOWN_DEVICE = 'This mobile-device class is unknown';

    /**
     * The User-Agent
     *
     * @var string
     */
    protected $_sHttpAccept;

    /**
     * The HTTP-Accept-String
     *
     * @var string
     */
    protected $_sHttpUserAgent;
    
    /**
     * Types to ignore. Device-Types, listed here are not recognized as mobile
     *
     * @var array
     */
    protected $_aIgnore = array();

    
    /**
     * Known Devices
     *
     * @var array
     */
    protected $_aDevices = array(
        'android' => 'android',
        'blackberry' => 'blackberry',
        'iphone' => '(iphone|safari mobi|ipod)',
        'ipad' => '(ipad)',
        'opera' => '(opera mini|mini 9.5)',
        'palm' => '(pre\/|palm os|palm|webos|hiptop|treo|avantgo|plucker|xiino|blazer|elaine)',
        'windows' => '(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile)',
        'generic' => '(compal|wireless| mobi|ahong|xda_|foma|samsu|htc\/|htc_touch|ktouch|m4u\/|kddi|phone|lg |sonyericsson|samsung|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp)'
    );

    /**
     * Is the device a mobile ?
     *
     * @var boolean
     */
    protected $_bMobile = false;

    /**
     * The matched device class
     *
     *  @var string
     */
    protected $_sClass = null;

    /**
     * Init the Device-Detector
     *
     * @param  array $aEnv The Environment, $_SERVER if not set
     */
    public function __construct($aEnv = array()) {
        $this->detect($aEnv);
    }

    /**
     * Overloads the device-checking
     * - e.g. isAndroid, isandroid, android
     *
     * @param  string $name
     * @param  array $arguments
     *
     * @return boolean
     */
    public function __call($sName, $aArgs) {
        $sName = strtolower($sName);
        if (substr($sName, 0, 2) === 'is') {
            $sName = substr($sName, 2);
        }

        if (isset($this->_aDevices[$sName]) === true) {
            return $this->_match($sName);
        }

        throw new Exception(self::UNKNOWN_DEVICE);
    }
    
    /**
     *
     */
    public function ignore($sIgnore) {
        $this->_aIgnore[$sIgnore] = true;
        return $this;
    }

    /**
     * Returns true if any type of mobile device detected, including special ones
     *
     * @return boolean
     */
    public function isMobile() {
        return $this->_bMobile;
    }

    /**
     * Get the matched device-class
     *
     * @return string
     */
    public function getDeviceClass() {
        return $this->_sClass;
    }

    /**
     * Force a mobile device
     *
     * @return Mobile_Device
     */
    public function forceState() {
        $this->_bMobile = true;
        $this->_sClass = 'generic';
        return $this;
    }

    /**
     * Reset the state
     *
     * @return Mobile_Device
     */
    public function resetState() {
        $this->_bMobile = false;
        $this->_sClass = null;
        return $this;
    }

    /**
     * Detect a device
     *
     * @param  array $aEnv The Environment, $_SERVER if not set
     *
     * @return boolean
     */
    public function detect($aEnv = array()) {
        $aEnv = (empty($aEnv) === true) ? $_SERVER : $aEnv;
        $this->_sHttpAccept = (isset($aEnv['HTTP_ACCEPT']) === true) ? $aEnv['HTTP_ACCEPT'] : '';
        $this->_sHttpUserAgent = (isset($aEnv['HTTP_USER_AGENT']) === true) ? $aEnv['HTTP_USER_AGENT'] : '';
        if (isset($aEnv['HTTP_X_WAP_PROFILE']) === true or isset($aEnv['HTTP_PROFILE']) === true) {
            $this->_bMobile = true;
        }
        elseif (strpos($this->_sHttpAccept, 'text/vnd.wap.wml') !== false or strpos($this->_sHttpAccept, 'application/vnd.wap.xhtml+xml') !== false) {
            $this->_bMobile = true;
        }
        else {
            foreach (array_keys($this->_aDevices) as $sDevice) {
                $this->_bMobile = $this->_match($sDevice);
                if ($this->_bMobile === true) {
                    break;
                }
            }
        }

        if ($this->_bMobile === true and isset($this->_aIgnore[$this->_sClass]) === true and $this->_aIgnore[$this->_sClass] === true) {
            $this->_bMobile = false;
        }

        return $this->isMobile();
    }

    /**
     * Check the user-agent against a specific device
     *
     * @param  string $sDevice The specific device
     *
     * @return boolean
     */
    protected function _match($sDevice) {
        $bMatch = false;
        if (is_string($this->_aDevices[$sDevice]) === true) {
            $bMatch = (bool) preg_match('!' . $this->_aDevices[$sDevice] . '!i', $this->_sHttpUserAgent);
            if ($bMatch === true) {
                $this->_sClass = $sDevice;
            }
        }

        return $bMatch;
    }
}

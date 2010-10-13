<?php
/**
 * Components_Helper_InstallationRun:: provides a utility that records what has
 * already happened during an installation run.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_InstallationRun:: provides a utility that records what has
 * already happened during an installation run.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_InstallationRun
{
    /**
     * The environment we establish the tree for.
     *
     * @var Components_Pear_InstallLocation
     */
    private $_environment;

    /**
     * The list of channels already installed.
     *
     * @var array
     */
    private $_installed_channels = array();

    /**
     * The list of packages already installed.
     *
     * @var array
     */
    private $_installed_packages = array();

    /**
     * Constructor.
     *
     * @param Components_Pear_InstallLocation $environment The environment we
     *                                                     establish the tree for.
     */
    public function __construct(
        Components_Pear_InstallLocation $environment
    ) {
        $this->_environment = $environment;
    }

    /**
     * Ensure that the listed channels are available within the installation
     * environment. The channels are only going to be installed once during the
     * installation run represented by this instance.
     *
     * @return NULL
     */
    public function installChannelsOnce(array $channels)
    {
        foreach ($channels as $channel) {
            if (!in_array($channel, $this->_installed_channels)) {
                $this->_environment->provideChannel($channel);
                $this->_installed_channels[] = $channel;
            }
        }
    }

    /**
     * Ensure that the external package is available within the installation
     * environment. The package is only going to be installed once during the
     * installation run represented by this instance.
     *
     * @param string $package The package that should be installed.
     * @param string $channel The channel of the package.
     *
     * @return NULL
     */
    public function installExternalPackageOnce($channel, $package)
    {
        $key = $channel . '/' . $package;
        if (!in_array($key, $this->_installed_packages)) {
            $this->_environment->addPackageFromPackage(
                $channel, $package
            );
            $this->_installed_packages[] = $key;
        }
    }
    
    /**
     * Ensure that the horde package is available within the installation
     * environment. The package is only going to be installed once during the
     * installation run represented by this instance.
     *
     * @param string $package_file The package file indicating which Horde
     *                             source package should be installed.
     *
     * @return NULL
     */
    public function installHordePackageOnce($package_file)
    {
        if (!in_array($package_file, $this->_installed_packages)) {
            $this->_environment->addPackageFromSource(
                $package_file
            );
            $this->_installed_packages[] = $package_file;
        }
    }
}
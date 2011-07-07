<?php
/**
 * Represents a source component.
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
 * Represents a source component.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Components_Component_Source extends Components_Component_Base
{
    /**
     * Path to the source directory.
     *
     * @var string
     */
    private $_directory;

    /**
     * The package file representing the component.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_package;

    /**
     * The PEAR package file representing the component.
     *
     * @var PEAR_PackageFile
     */
    private $_package_file;

    /**
     * Constructor.
     *
     * @param string                  $directory Path to the source directory.
     * @param boolean                 $shift     Did identification of the
     *                                           component consume an argument?
     * @param Components_Config       $config    The configuration for the
     *                                           current job.
     * @param Components_Component_Factory $factory Generator for additional
     *                                              helpers.
     */
    public function __construct(
        $directory,
        Components_Config $config,
        Components_Component_Factory $factory
    )
    {
        $this->_directory = realpath($directory);
        parent::__construct($config, $factory);
    }

    /**
     * Indicate if the component has a local package.xml.
     *
     * @return boolean True if a package.xml exists.
     */
    public function hasLocalPackageXml()
    {
        return file_exists($this->_getPackageXmlPath());
    }

    /**
     * Returns the link to the change log.
     *
     * @param Components_Helper_ChangeLog $helper  The change log helper.
     *
     * @return string|null The link to the change log.
     */
    public function getChangelog($helper)
    {
        $base = $this->getFactory()->getGitRoot()->getRoot();
        return $helper->getChangelog(
            preg_replace(
                '#^' . $base . '#', '', realpath($this->_directory)
            ),
            $this->_directory
        );
    }

    /**
     * Return the path to the release notes.
     *
     * @return string|boolean The path to the release notes or false.
     */
    public function getReleaseNotesPath()
    {
        if (file_exists($this->_directory . '/docs/RELEASE_NOTES')) {
            return $this->_directory . '/docs/RELEASE_NOTES';
        }
        if (file_exists($this->_directory . '/doc/RELEASE_NOTES')) {
            return $this->_directory . '/doc/RELEASE_NOTES';
        }
        return false;
    }

    /**
     * Update the package.xml file for this component.
     *
     * @param string $action  The action to perform. Either "update", "diff",
     *                        or "print".
     * @param array  $options Options for this operation.
     *
     * @return NULL
     */
    public function updatePackageXml($action, $options)
    {
        if (!file_exists($this->_getPackageXmlPath())) {
            $this->getFactory()->createPackageFile($this->_directory);
        }

        $package_xml = $this->getPackageXml();
        $package_xml->updateContents(null, $options);
        switch($action) {
        case 'print':
            return (string) $package_xml;
        case 'diff':
            $new = (string) $package_xml;
            $old = file_get_contents($this->_getPackageXmlPath());
            $renderer = new Horde_Text_Diff_Renderer_Unified();
            return $renderer->render(
                new Horde_Text_Diff(
                    'auto', array(explode("\n", $old), explode("\n", $new))
                )
            );
        default:
            file_put_contents($this->_getPackageXmlPath(), (string) $package_xml);
            return true;
        }
    }

    /**
     * Update the component changelog.
     *
     * @param string                      $log     The log entry.
     * @param Components_Helper_ChangeLog $helper  The change log helper.
     * @param array                       $options Options for the operation.
     *
     * @return NULL
     */
    public function changed(
        $log, Components_Helper_ChangeLog $helper, $options
    )
    {
        $helper->commit(
            $log,
            $this->_directory,
            $options,
            $helper->packageXml(
                $log,
                $this->getPackageXml(),
                $this->_getPackageXmlPath(),
                $options
            ),
            $helper->changes(
                $log,
                $this->_directory,
                $options
            )
        );
    }

    /**
     * Timestamp the package.xml file with the current time.
     *
     * @param array $options Options for the operation.
     *
     * @return string The success message.
     */
    public function timestampAndSync($options)
    {
        if (empty($options['pretend'])) {
            $package = $this->getPackageXml();
            $package->timestamp();
            $package->syncCurrentVersion();
            file_put_contents($this->_getPackageXmlPath(), (string) $package);
            $result = sprintf(
                'Marked package.xml "%s" with current timestamp and synchronized the change log.',
                $this->_getPackageXmlPath()
            );
        } else {
            $result = sprintf(
                'Would timestamp "%s" now and synchronize its change log.',
                $this->_getPackageXmlPath()
            );
        }
        if (!empty($options['commit'])) {
            $options['commit']->add(
                $this->_getPackageXmlPath(), $this->_directory
            );
        }
        return $result;
    }

    /**
     * Add the next version to the package.xml.
     *
     * @param string $version           The new version number.
     * @param string $initial_note      The text for the initial note.
     * @param string $stability_api     The API stability for the next release.
     * @param string $stability_release The stability for the next release.
     * @param array $options Options for the operation.
     *
     * @return NULL
     */
    public function nextVersion(
        $version,
        $initial_note,
        $stability_api = null,
        $stability_release = null,
        $options = array()
    ) {
        if (empty($options['pretend'])) {
            $package = $this->getPackageXml();
            $package->addNextVersion(
                $version, $initial_note, $stability_api, $stability_release
            );
            file_put_contents($this->_getPackageXmlPath(), (string) $package);
            $result = sprintf(
                'Added next version "%s" with the initial note "%s" to %s.',
                $version,
                $initial_note,
                $this->_getPackageXmlPath()
            );
        } else {
            $result = sprintf(
                'Would add next version "%s" with the initial note "%s" to %s now.',
                $version,
                $initial_note,
                $this->_getPackageXmlPath()
            );
        }
        if ($stability_release !== null) {
            $result .= ' Release stability: "' . $stability_release . '".';
        }
        if ($stability_api !== null) {
            $result .= ' API stability: "' . $stability_api . '".';
        }

        if (!empty($options['commit'])) {
            $options['commit']->add(
                $this->_getPackageXmlPath(), $this->_directory
            );
        }
        return $result;
    }

    /**
     * Replace the current sentinel.
     *
     * @param string $changes New version for the CHANGES file.
     * @param string $app     New version for the Application.php file.
     * @param array  $options Options for the operation.
     *
     * @return string The success message.
     */
    public function currentSentinel($changes, $app, $options)
    {
        $sentinel = $this->getFactory()->createSentinel($this->_directory);
        if (empty($options['pretend'])) {
            $sentinel->replaceChanges($changes);
            $sentinel->updateApplication($app);
            $action = 'Did';
        } else {
            $action = 'Would';
        }
        $files = array(
            'changes' => $sentinel->changesFileExists(),
            'app'     => $sentinel->applicationFileExists(),
            'bundle'  => $sentinel->bundleFileExists()
        );
        $result = array();
        foreach ($files as $key => $file) {
            if (empty($file)) {
                continue;
            }
            if (!empty($options['commit'])) {
                $options['commit']->add($file, $this->_directory);
            }
            $version = ($key == 'changes') ? $changes : $app;
            $result[] = sprintf(
                '%s replace sentinel in %s with "%s" now.',
                $action,
                $file,
                $version
            );
        }
        return $result;
    }

    /**
     * Set the next sentinel.
     *
     * @param string $changes New version for the CHANGES file.
     * @param string $app     New version for the Application.php file.
     * @param array  $options Options for the operation.
     *
     * @return string The success message.
     */
    public function nextSentinel($changes, $app, $options)
    {
        $sentinel = $this->getFactory()->createSentinel($this->_directory);
        if (empty($options['pretend'])) {
            $sentinel->updateChanges($changes);
            $sentinel->updateApplication($app);
            $action = 'Did';
        } else {
            $action = 'Would';
        }
        $files = array(
            'changes' => $sentinel->changesFileExists(),
            'app'     => $sentinel->applicationFileExists(),
            'bundle'  => $sentinel->bundleFileExists()
        );
        $result = array();
        foreach ($files as $key => $file) {
            if (empty($file)) {
                continue;
            }
            if (!empty($options['commit'])) {
                $options['commit']->add($file, $this->_directory);
            }
            $version = ($key == 'changes') ? $changes : $app;
            $task = ($key == 'changes') ? 'extend' : 'replace';
            $result[] = sprintf(
                '%s %s sentinel in %s with "%s" now.',
                $action,
                $task,
                $file,
                $version
            );
        }
        return $result;
    }

    /**
     * Tag the component.
     *
     * @param string                   $tag     Tag name.
     * @param string                   $message Tag message.
     * @param Components_Helper_Commit $commit  The commit helper.
     *
     * @return NULL
     */
    public function tag($tag, $message, $commit)
    {
        $commit->tag($tag, $message, $this->_directory);
    }

    /**
     * Place the component source archive at the specified location.
     *
     * @param string $destination The path to write the archive to.
     * @param array  $options     Options for the operation.
     *
     * @return array An array with at least [0] the path to the resulting
     *               archive, optionally [1] an array of error strings, and [2]
     *               PEAR output.
     */
    public function placeArchive($destination, $options)
    {
        if (!file_exists($this->_getPackageXmlPath())) {
            throw new Components_Exception(
                sprintf(
                    'The component "%s" still lacks a package.xml file at "%s"!',
                    $this->getName(),
                    $this->_getPackageXmlPath()
                )
            );
        }

        if (empty($options['keep_version'])) {
            $version = preg_replace(
                '/([.0-9]+).*/',
                '\1dev' . strftime('%Y%m%d%H%M'),
                $this->getVersion()
            );
        } else {
            $version = $this->getVersion();
        }

        $this->createDestination($destination);

        $package = $this->_getPackageFile();
        $pkg = $this->getFactory()->pear()->getPackageFile(
            $this->_getPackageXmlPath(),
            $package->getEnvironment()
        );
        $pkg->_packageInfo['version']['release'] = $version;
        $pkg->setDate(date('Y-m-d'));
        $pkg->setTime(date('H:i:s'));
        if (isset($options['logger'])) {
            $pkg->setLogger($options['logger']);
        }
        $errors = array();
        ob_start();
        $old_dir = getcwd();
        chdir($destination);
        try {
            $result = Components_Exception_Pear::catchError(
                $pkg->getDefaultGenerator()->toTgz(new PEAR_Common())
            );
        } catch (Components_Exception_Pear $e) {
            $errors[] = $e->getMessage();
            $errors[] = '';
            $result = false;
            foreach ($pkg->getValidationWarnings() as $error) {
                $errors[] = isset($error['message']) ? $error['message'] : 'Unknown Error';
            }
        }
        chdir($old_dir);
        $output = array($result, $errors);
        $output[] = ob_get_clean();
        return $output;
    }

    /**
     * Identify the repository root.
     *
     * @param Components_Helper_Root $helper The root helper.
     *
     * @return NULL
     */
    public function repositoryRoot(Components_Helper_Root $helper)
    {
        if (($result = $helper->traverseHierarchy($this->_directory)) === false) {
            $this->_errors[] = sprintf(
                'Unable to determine Horde repository root from component path "%s"!',
                $this->_directory
            );
        }
        return $result;
    }

    /**
     * Return a PEAR package representation for the component.
     *
     * @return Horde_Pear_Package_Xml The package representation.
     */
    protected function getPackageXml()
    {
        if (!isset($this->_package)) {
            if (!file_exists($this->_getPackageXmlPath())) {
                throw new Components_Exception(
                    sprintf(
                        'The package.xml of the component at "%s" is missing.',
                        $this->_getPackageXmlPath()
                    )
                );
            }
            $this->_package = $this->getFactory()->createPackageXml(
                $this->_getPackageXmlPath()
            );
        }
        return $this->_package;
    }

    /**
     * Return a PEAR PackageFile representation for the component.
     *
     * @return PEAR_PackageFile The package representation.
     */
    private function _getPackageFile()
    {
        if (!isset($this->_package_file)) {
            $options = $this->getOptions();
            if (isset($options['pearrc'])) {
                $this->_package_file = $this->getFactory()->pear()
                    ->createPackageForPearConfig(
                        $this->_getPackageXmlPath(), $options['pearrc']
                    );
            } else {
                $this->_package_file = $this->getFactory()->pear()
                    ->createPackageForDefaultLocation(
                        $this->_getPackageXmlPath()
                    );
            }
        }
        return $this->_package_file;
    }

    /**
     * Return the path to the package.xml file of the component.
     *
     * @return string The path to the package.xml file.
     */
    private function _getPackageXmlPath()
    {
        return $this->_directory . '/package.xml';
    }
}
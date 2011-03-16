<?php
/**
 * Components_Release_Task_Base:: provides core functionality for release tasks.
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
 * Components_Release_Task_Base:: provides core functionality for release tasks.
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
class Components_Release_Task_Base
{
    /**
     * The tasks handler.
     *
     * @var Components_Release_Tasks
     */
    private $_tasks;

    /**
     * The release notes handler.
     *
     * @var Components_Release_Notes
     */
    private $_notes;

    /**
     * The task output.
     *
     * @var Components_Output
     */
    private $_output;

    /**
     * The package that should be released
     *
     * @var Components_Pear_Package
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param Components_Release_Tasks $tasks The task handler.
     * @param Components_Release_Notes $notes The release notes.
     * @param Components_Output $output Accepts output.
     */
    public function __construct(
        Components_Release_Tasks $tasks,
        Components_Release_Notes $notes,
        Components_Output $output
    ) {
        $this->_tasks = $tasks;
        $this->_notes = $notes;
        $this->_output = $output;
    }

    /**
     * Set the package this task should act upon.
     *
     * @param Components_Pear_Package $package The package to be released.
     *
     * @return NULL
     */
    public function setPackage(Components_Pear_Package $package)
    {
        $this->_package = $package;
        $this->_notes->setPackage($package);
    }

    /**
     * Get the package this task should act upon.
     *
     * @return Components_Pear_Package The package to be released.
     */
    protected function getPackage()
    {
        return $this->_package;
    }

    /**
     * Get the tasks handler.
     *
     * @return Components_Release_Tasks The release tasks handler.
     */
    protected function getTasks()
    {
        return $this->_tasks;
    }

    /**
     * Get the release notes.
     *
     * @return Components_Release_Notes The release notes.
     */
    protected function getNotes()
    {
        return $this->_notes;
    }

    /**
     * Get the output handler.
     *
     * @return Components_Output The output handler.
     */
    protected function getOutput()
    {
        return $this->_output;
    }

    /**
     * Validate the preconditions required for this release task.
     *
     * @return array An empty array if all preconditions are met and a list of
     *               error messages otherwise.
     */
    public function validate()
    {
        return array();
    }

    /**
     * Run a system call.
     *
     * @param string $call       The system call to execute.
     *
     * @return string The command output.
     */
    protected function system($call)
    {
        if (!$this->getTasks()->pretend()) {
            //@todo Error handling
            return system($call);
        } else {
            $this->getOutput()->info(sprintf('Would run "%s" now.', $call));
        }
    }

    /**
     * Run a system call.
     *
     * @param string $call       The system call to execute.
     * @param string $target_dir Run the command in the provided target path.
     *
     * @return string The command output.
     */
    protected function systemInDirectory($call, $target_dir)
    {
        $old_dir = getcwd();
        chdir($target_dir);
        $result = $this->system($call);
        chdir($old_dir);
        return $result;
    }
}
<?php
/**
 * PSR-0 Non-Namespaced compatibility wrapper for Horde\Components\Config\File
 * 
 * Horde\GitTools currently hooks into this class to leverage components.
 * 
 * TODO: Remove For Horde 7.
 * @deprecated Deprecated since Horde 6. Use the namespaced variant
 */
use Horde\Components\Config\File;
class Components_Config_File extends File {};

<?php
/**
 * Po4a.php
 *
 * PHP version 5
 *
 * @category Po4a
 * @package  Po4a
 * @author   Pedro Padron <ppadron@w3p.com.br>
 * @license  i'm not sure if I have to release this as GPL
 * @link     http://github.com/w3p/php-po4a
 * @see      http://po4a.alioth.debian.org/
 */

require_once 'Po4a/Exception.php';

/**
 * Wrapper around po4a command line tool
 *
 * This class provides means to build translations with po4a in two different
 * ways, using an existent configuration file or using this class to generate
 * one for you.
 *
 * Obviously enough, you need to install po4a to use this class. It is available
 * in most major Linux distros.
 *
 * About po4a:
 *
 *     The po4a (PO for anything) project goal is to ease translations
 *     (and more interestingly, the maintenance of translations) using gettext
 *     tools on areas where they were not expected like documentation.
 *
 *     po4a is Copyright 2002-2011 by SPI, inc.
 *     http://po4a.alioth.debian.org/
 *
 *     Authors:
 *         Denis Barbier <barbier@linuxfr.org>
 *         Nicolas François <nicolas.francois@centraliens.net>
 *         Martin Quinson <mquinson@debian.org>
 *
 * @category Po4a
 * @package  Po4a
 * @author   Pedro Padron <ppadron@w3p.com.br>
 * @license  i'm not sure if I have to release this as GPL
 * @link     http://github.com/w3p/php-po4a
 * @see      http://po4a.alioth.debian.org/
 */
class Po4a
{
    /**
     * po4a executable file name
     *
     * @var string
     */
    protected $binaryName = 'po4a';

    /**
     * Build options
     *
     * @var array
     */
    protected $buildOptions = array();

    /**
     * Build flags
     *
     * @var string
     */
    protected $buildFlags = array();

    /**
     * Base directory to be used for source files
     *
     * @var string
     */
    protected $sourceBaseDir;

    /**
     * Base directory to be used for destination files
     *
     * @var string
     */
    protected $destinationBaseDir;

    /**
     * Languages to which the documents will be translated to
     *
     * @var array
     */
    protected $targetLanguages = array();

    /**
     * Language of the source document
     *
     * @var string
     */
    protected $sourceLanguage;

    /**
     * Path to po4a configuration file
     *
     * @var string
     */
    protected $configFile;

    /**
     * Required file parameters
     *
     * @var array
     */
    protected $requiredFileParams = array(
        'masterFile', 'fileType', 'targetFile'
    );

    /**
     * Available build options
     *
     * @var array
     */
    protected $availableBuildOptions = array(
        'options'          => '-o',
        'keep'             => '-k',
        'masterCharset'    => '-M',
        'localizedCharset' => '-L',
        'addendumCharset'  => '-A',
        'emailAddress'     => '--msgid-bugs-address',
        'copyright'        => '--copyright-holder',
        'packageName'      => '--package-name',
        'packageVersion'   => '--package-version'
    );

    /**
     * Available build flags
     *
     * @var array
     */
    protected $availableBuildFlags = array(
        'force'  => '-f',
        'stamp'  => '--stamp'
    );

    /**
     * Path to master translation file (.pot)
     *
     * @var string
     */
    protected $masterTranslationFile;

    /**
     * Path to translation file (.po)
     *
     * @var string
     */
    protected $translationFile;

    /**
     * Class constructor
     *
     * @param array $params Array of options
     *
     * Available options are:
     *
     * array  buildOptions
     * array  buildFlags
     * stirng configFile
     * array  files
     * string masterTranslationFile
     * string sourceLanguage
     * array  targetLanguages
     * string translationFile
     *
     * @return void
     */
    public function __construct($params = array())
    {
        if (isset($params['buildOptions'])) {
            $this->setBuildOptions($params['buildOptions']);
            unset($params['buildOptions']);
        }

        if (isset($params['files'])) {
            $this->addFiles($params['files']);
            unset($params['files']);
        }

        if (isset($params['masterTranslationFile'])) {
            $this->setMasterTranslationFile($params['masterTranslationFile']);
            unset($params['masterTranslationFile']);
        }

        if (isset($params['translationFile'])) {
            $this->setTranslationFile($params['translationFile']);
            unset($params['translationFile']);
        }

        // if there are no more parameters, there's nothing to do
        if (empty($params)) {
            return;
        }

        // all other parameters can be set directly
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Sets the base directory for source files
     *
     * @param string $sourceBaseDir Base directory for source files
     *
     * @return void
     */
    public function setSourceBaseDir($sourceBaseDir)
    {
        $this->sourceBaseDir = $sourceBaseDir;
    }

    /**
     * Sets the base directory for destination files
     *
     * @param string $destinationBaseDir Base directory for destination files
     *
     * @return void
     */
    public function setDestinationBaseDir($destinationBaseDir)
    {
        $this->destinationBaseDir = $destinationBaseDir;
    }

    /**
     * Sets build options
     *
     * @param array $options Array of options
     *
     * @return void
     */
    public function setBuildOptions(array $options = array())
    {
        foreach ($options as $key => $value) {
            $this->setBuildOption($key, $value);
        }
    }

    /**
     * Sets the path to the configuration file that will be used
     *
     * @param string $configFile Path to config file
     *
     * @return void
     */
    public function setConfigFile($configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * Sets a value to a build option
     *
     * @param string $optionName  Option name
     * @param string $optionValue Option value
     *
     * @return void
     */
    public function setBuildOption($optionName, $optionValue)
    {
        if (!array_key_exists($optionName, $this->availableBuildOptions)) {
            continue;
        }

        $this->buildOptions[$optionName] = $optionValue;
    }

    /**
     * Adds a file to be translated
     *
     * @param string $fileType   File type (xhtml, docbook, man...)
     * @param string $masterFile Path to the master (source) file
     * @param string $targetFile Path to the translated file
     *
     * This parameter allows the use of the %lang placeholder, which will expand
     * to the language the file is being translated to.
     *
     * @return void
     */
    public function addFile($fileType, $masterFile, $targetFile)
    {
        $this->files[$masterFile] = array(
            'masterFile' => $masterFile,
            'fileType'   => $fileType,
            // po4a uses $ for variables. here we use % to avoid dumb mistakes.
            'targetFile' => str_replace('%', '$', $targetFile)
        );
    }

    /**
     * Adds multiple files to be translated
     *
     * @param array $files Files to be translated
     *
     * @return void
     */
    public function addFiles(array $files = array())
    {
        foreach ($files as $file) {
            $params = $this->parseFileParams($file);

            $this->addFile(
                $params['fileType'],
                $params['masterFile'],
                $params['targetFile']
            );
        }
    }

    /**
     * Replaces previously added files with new ones
     *
     * @param array $files Array of files
     *
     * @return void
     */
    public function setFiles(array $files = array())
    {
        $this->files = array();
        $this->addFiles($files);
    }

    /**
     * Sets the source document's language
     *
     * @param string $language Language name
     *
     * @return void
     */
    public function setSourceLanguage($language)
    {
        $this->sourceLanguage = $language;
    }

    /**
     * Sets the languages to which the source document will be translated to
     *
     * @param array $languages Array of languages
     *
     * @return void
     */
    public function setTargetLanguages(array $languages = array())
    {
        $this->targetLanguages = $languages;
    }

    /**
     * Returns the languages to which the source document will be translated to
     *
     * @return array
     */
    public function getTargetLanguages()
    {
        return $this->targetLanguages;
    }

    /**
     * Adds a language to which the source document will be translated to
     *
     * @param string $lang Language name
     *
     * @return void
     */
    public function addTargetLanguage($lang)
    {
        if (!in_array($lang, $this->targetLanguages)) {
            $this->targetLanguages[] = $lang;
        }
    }

    /**
     * Sets the path where translation inputs will be created
     *
     * @param string $translationFile Path where translation files will be saved
     *
     * There are placeholders available for use in $path:
     *
     * %lang   - expands to the target language;
     * %master - expands to the master document basename;
     *
     * If %master% is provided, translation inputs (.po files) will be generated
     * for each master document, this is called Split Mode.
     *
     * However, a big .pot file and a big .po file will also be available, and
     * if modified by translators the changes will be applied to all the
     * document .po files.
     *
     * From the po4a man page:
     *
     *   "When the split mode is used, a temporary big POT and temporary big POs
     *   are used. This permits to share the translations between all the POs."
     *
     * @return void
     */
    public function setTranslationFile($translationFile)
    {
        $this->translationFile = str_replace('%', '$', $translationFile);
    }

    /**
     * Sets the path to the master translation file (.pot)
     *
     * @see Po4a_Config::setTranslationFile
     * @param string $masterTranslationFile Path to master translation file
     *
     * You can use the %master% placeholder if you want to generate one pot file
     * per master file. This is called Split Mode.
     *
     * From the po4a man page:
     *
     *   "When the split mode is used, a temporary big POT and temporary big POs
     *   are used. This permits to share the translations between all the POs."
     *
     * Note that if you use %master% here, you'll need to use it when setting
     * the path to the translation files (.po), so the build program can
     * generate one .po file per master file.
     *
     * %lang   - expands to the target language;
     * %master - expands to the master document basename;
     *
     * @return void
     */
    public function setMasterTranslationFile($masterTranslationFile)
    {
        $this->masterTranslationFile = str_replace('%', '$', $masterTranslationFile);
    }

    /**
     * Returns the configuration string
     *
     * This method is really ugly. It builds the config file in the po4a format,
     * which is something like this:
     *
     * [po4a_langs] pt es en
     * [po4a_paths] translations/$master.pot $lang:translations/$master-$lang.po
     * [options] opt: "-M UTF-8 -L UTF-8 -k 0"
     * [type: xhtml] documents/document.html $lang:documents/document-$lang.html
     *
     * @return string
     */
    public function getConfigFileContents()
    {
        $masterTranslationFile = ($this->sourceBaseDir) ?
            '$(srcdir)' . $this->masterTranslationFile :
            $this->masterTranslationFile;

        $translationFile = ($this->destinationBaseDir) ?
            '$(destdir)' . $this->translationFile :
            $this->translationFile;

        $config  = '';
        $config .= sprintf(
            "[po4a_langs] %s %s\n",
            $this->sourceLanguage,
            join(" ", $this->targetLanguages)
        );

        $config .= sprintf(
            "[po4a_paths] %s \$lang:%s\n",
            $masterTranslationFile,
            $translationFile
        );

        $buildOptions = '';

        foreach ($this->buildOptions as $key => $value) {
            $buildOptions .= "{$this->availableBuildOptions[$key]} {$value} ";
        }

        $buildFlags = '';

        foreach ($this->buildFlags as $flag) {
            $buildFlags .= "{$this->availableBuildFlags[$flag]} ";
        }

        $config .= sprintf(
            "[options] opt: \"%s %s\"\n",
            $buildOptions,
            $buildFlags
        );

        foreach ($this->files as $file) {

            if ($this->destinationBaseDir) {
                $targetFile = '$(destdir)' . $file['targetFile'];
            } else {
                $targetFile = $file['targetFile'];
            }

            if ($this->sourceBaseDir) {
                $masterFile = '$(srcdir)' . $file['masterFile'];
            } else {
                $masterFile = $file['masterFile'];
            }

            $config .= sprintf(
                "[type: %s] %s \$lang:%s \n",
                $file['fileType'], $masterFile, $targetFile
            );
        }

        return $config;
    }

    /**
     * Writes the config file
     *
     * @param string $targetFile Path to config file
     * @param bool   $overwrite  Whether to overwrite if file exists
     *
     * @return bool
     */
    public function writeConfig($targetFile = 'po4a.cfg', $overwrite = false)
    {
        if (file_exists($targetFile) && !$overwrite) {
            throw new Po4a_Exception('Target file exists');
        }

        return file_put_contents($targetFile, $this->getConfigFileContents());
    }

    /**
     * Makes sure that an added file contains the required fields
     *
     * @throws Po4a_Exception If a required parameter is not found
     * @param array $file File to be added
     *
     * @return array
     */
    protected function parseFileParams(array $file = array())
    {
        foreach ($this->requiredFileParams as $param) {
            if (!array_key_exists($param, $file)) {
                throw new Po4a_Exception(
                    "Required parameter {$param} not found"
                );
            }
        }

        return $file;
    }

    /**
     * Runs the po4a binary with the generated configuration to build the project
     *
     * @throws Po4a_Exception If build fails
     *
     * @return bool
     */
    public function build()
    {
        exec("which {$this->binaryName}", $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Po4a_Exception(
                'Could not find po4a executable, please make sure it is installed'
            );
        }

        if (empty($this->configFile)) {
            $this->configFile = tempnam('', 'po4a');
            $this->writeConfig($this->configFile, true);
        }

        $command = sprintf(
            "%s --variable \"srcdir=%s\" --variable \"destdir=%s\" %s",
            $this->binaryName,
            $this->sourceBaseDir,
            $this->destinationBaseDir,
            $this->configFile
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Po4a_Exception('Build failed: ' . implode(" ", $output));
        }

        return true;
    }
}
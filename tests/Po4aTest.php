<?php

require_once 'Po4a.php';

class ConfigTest extends PHPUnit_Framework_TestCase
{
    protected $sourceLanguage = 'pt';
    protected $languages      = array('es', 'en');

    protected $translationsDir;
    protected $documentsDir;

    protected $file = array(
        'fileType'   => 'xhtml',
        'masterFile' => 'tests/files/documents/document.html',
        'targetFile' => 'tests/files/documents/document-%lang.html'
    );

    public function setUp()
    {
        $this->translationsDir = dirname(__FILE__) . '/files/translations';
        $this->documentsDir    = dirname(__FILE__) . '/files/documents';
    }

    public function tearDown()
    {
        foreach ($this->languages as $lang) {
            @unlink("{$this->translationsDir}/document.html-{$lang}.po");
            @unlink("{$this->documentsDir}/document-{$lang}.html");
        }
    }

    public function testSampleBuildWithSomeParameters()
    {
        $po = new Po4a();

        $po->setSourceLanguage($this->sourceLanguage);
        $po->setTargetLanguages($this->languages);
        $po->setMasterTranslationFile("{$this->translationsDir}/%master.pot");
        $po->setTranslationFile("{$this->translationsDir}/%master-%lang.po");

        $po->setBuildOptions(array(
            'masterCharset'    => 'UTF-8',
            'localizedCharset' => 'UTF-8',
            'keep'             => '0'
        ));

        $po->addFile(
            $this->file['fileType'],
            $this->file['masterFile'],
            $this->file['targetFile']
        );

        $this->assertTrue($po->build());

        // master file
        $this->assertFileExists("{$this->translationsDir}/document.html.pot");

        // translation inputs
        $this->assertFileExists("{$this->translationsDir}/document.html-en.po");
        $this->assertFileExists("{$this->translationsDir}/document.html-es.po");
        $this->assertFileExists("{$this->translationsDir}/document.html-pt.po");

        // translations
        $this->assertFileExists("{$this->documentsDir}/document-en.html");
        $this->assertFileExists("{$this->documentsDir}/document-pt.html");
        $this->assertFileExists("{$this->documentsDir}/document-es.html");
    }

    public function testAcceptsAllParametersAsArrayInTheConstructor()
    {
        $po = new Po4a(array(
            'masterTranslationFile' => "{$this->translationsDir}/%master.pot",
            'translationFile'       => "{$this->translationsDir}/%master-%lang.po",
            'sourceLanguage'  => $this->sourceLanguage,
            'targetLanguages' => $this->languages,
            'force'           => true,
            'files'           => array($this->file),
            'buildOptions'    => array(
                'masterCharset'    => 'UTF-8',
                'localizedCharset' => 'UTF-8',
                'keep'             => '0'
            )
        ));

        $this->assertTrue($po->build());

        $this->assertFileExists("{$this->documentsDir}/document-en.html");
        $this->assertFileExists("{$this->documentsDir}/document-pt.html");
        $this->assertFileExists("{$this->documentsDir}/document-es.html");
    }

    public function testBuildWithoutSplitMode()
    {
        $po = new Po4a(array(
            'masterTranslationFile' => "{$this->translationsDir}/bigfile.pot",
            'translationFile'       => "{$this->translationsDir}/%lang.po",
            'sourceLanguage'  => $this->sourceLanguage,
            'targetLanguages' => $this->languages,
            'force'           => true,
            'files'           => array($this->file),
            'buildOptions'    => array(
                'masterCharset'    => 'UTF-8',
                'localizedCharset' => 'UTF-8',
                'keep'             => '0'
            )
        ));

        $this->assertTrue($po->build());

        //
        $this->assertFileExists("{$this->translationsDir}/bigfile.pot");

        // translation inputs
        $this->assertFileExists("{$this->translationsDir}/es.po");
        $this->assertFileExists("{$this->translationsDir}/pt.po");
        $this->assertFileExists("{$this->translationsDir}/en.po");

        $this->assertFileExists("{$this->documentsDir}/document-en.html");
        $this->assertFileExists("{$this->documentsDir}/document-pt.html");
        $this->assertFileExists("{$this->documentsDir}/document-es.html");
    }

    public function testBuildWithConfigFile()
    {
        $po = new Po4a(array(
            'destinationBaseDir' => dirname(__FILE__) . '/files',
            'sourceBaseDir'      => dirname(__FILE__) . '/files',
            'configFile'         => dirname(__FILE__) . '/files/po4a.cfg'
        ));

        $this->assertTrue($po->build());

        // without split mode
        $this->assertFileExists("{$this->translationsDir}/bigfile.pot");

        // translation inputs
        $this->assertFileExists("{$this->translationsDir}/es.po");
        $this->assertFileExists("{$this->translationsDir}/pt.po");
        $this->assertFileExists("{$this->translationsDir}/en.po");

        // translated documents
        $this->assertFileExists("{$this->documentsDir}/document-en.html");
        $this->assertFileExists("{$this->documentsDir}/document-pt.html");
        $this->assertFileExists("{$this->documentsDir}/document-es.html");
    }
}